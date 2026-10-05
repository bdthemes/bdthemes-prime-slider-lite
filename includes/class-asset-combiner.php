<?php

namespace PrimeSlider\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Asset Optimization: joins the module files a page loads into combined files.
 *
 * Every widget declares its own `bdtps-<module>` (or, in Prime Slider Pro,
 * `ps-<module>`) stylesheet and script, so a page already asks for exactly the
 * modules it renders. With Asset Optimization on, right before WordPress prints a
 * queue, each run of consecutive module handles is replaced by one file built from
 * exactly those files. Pages with the same run share the file, named after a hash
 * of its inputs.
 *
 * The combined file takes the place of the run's last module file, so each module
 * still comes after everything it depends on. A run may continue past a handle in
 * between only when nothing breaks by moving the earlier modules after it:
 *
 * - a stylesheet only past Elementor's per-widget base CSS (`widget-*`,
 *   `e-animation-*`, Swiper), the icon fonts (Font Awesome, the Prime Slider icon
 *   font), which Elementor already enqueues in whatever order the widgets sit on the
 *   page. Never past post or kit CSS (the user's styling), inline styles or any
 *   other stylesheet;
 * - a script past any script of the same group that does not depend on one of the
 *   modules it would overtake.
 *
 * Inline code attached to a module ends or starts its run where it has to run next
 * to that module. UIkit, the site helper and vendor libraries keep their own files,
 * which every page shares from the browser cache. The inputs are already minified,
 * so a combined file is a concatenation, cheap enough to build in the request that
 * first needs it. Where one cannot be written, the page keeps the separate files.
 *
 * Prime Slider Pro adds its plugin folder through `prime_slider/optimization/asset_roots`
 * and its version through `prime_slider/optimization/fingerprint_inputs`, so its
 * module files join the same combined files.
 */
final class Asset_Combiner {

	/** Uploads subfolder for combined files; one folder per fingerprint(). */
	const DIR = 'prime-slider/sets';

	/** WP-Cron hook that removes the folders of superseded fingerprints. */
	const CLEANUP_HOOK = 'prime_slider_cleanup_asset_sets';

	/** Set for an hour after a combined file could not be written. */
	const FAILED_TRANSIENT = 'prime_slider_asset_sets_failed';

	/** Combined files of one type per folder; beyond this pages keep separate files. */
	const MAX_FILES = 2000;

	/** How long a superseded folder stays for pages still held by a page cache (7 days). */
	const GRACE_PERIOD = 604800;

	/**
	 * Whether the footer scripts are being printed.
	 *
	 * @var bool
	 */
	private static $in_footer = false;

	/**
	 * Cached result of is_active().
	 *
	 * @var bool|null
	 */
	private static $active = null;

	/**
	 * Cached fingerprint folder name.
	 *
	 * @var string|null
	 */
	private static $folder = null;

	public static function init() {
		add_filter( 'print_styles_array', [ __CLASS__, 'filter_styles' ], 20 );
		add_filter( 'print_scripts_array', [ __CLASS__, 'filter_scripts' ], 20 );
		add_action( 'wp_print_footer_scripts', [ __CLASS__, 'enter_footer' ], 1 );
		add_action( self::CLEANUP_HOOK, [ __CLASS__, 'cleanup' ] );
		add_action( 'elementor/core/files/clear_cache', [ __CLASS__, 'flush' ] );
	}

	/**
	 * Whether module files are combined on this request.
	 *
	 * Only on the frontend with Asset Optimization on. Not in the Elementor editor
	 * or preview, which load the separate files so the editor can refresh them.
	 *
	 * SCRIPT_DEBUG does not turn it off: the module files are the same minified
	 * files either way, and local development sites commonly define it, which would
	 * leave the setting with no visible effect. Switch Asset Optimization off, or use
	 * the filter below, to get the separate files.
	 *
	 * @return bool
	 */
	public static function is_active() {
		if ( null === self::$active ) {
			$active = prime_slider_is_asset_optimization_enabled()
				&& ! is_admin()
				&& ! self::is_editor_request();

			/**
			 * Filters whether Prime Slider joins the module files a page loads into
			 * combined files. Only consulted with Asset Optimization on.
			 *
			 * @param bool $active Whether to combine on this request.
			 */
			self::$active = (bool) apply_filters( 'prime_slider/optimization/combine_assets', (bool) $active );
		}

		return self::$active;
	}

	/**
	 * Whether this request is the Elementor editor or its preview iframe.
	 *
	 * @return bool
	 */
	private static function is_editor_request() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only picks which asset URLs to print.
		if ( isset( $_GET['elementor-preview'] ) ) {
			return true;
		}

		if ( ! class_exists( '\Elementor\Plugin' ) || empty( \Elementor\Plugin::$instance ) ) {
			return false;
		}

		$elementor = \Elementor\Plugin::$instance;

		if ( isset( $elementor->editor ) && $elementor->editor->is_edit_mode() ) {
			return true;
		}

		return isset( $elementor->preview ) && $elementor->preview->is_preview_mode();
	}

	public static function enter_footer() {
		self::$in_footer = true;
	}

	/**
	 * `print_styles_array`: combine runs of module stylesheets, in the head and in the
	 * late styles printed in the footer.
	 *
	 * @param string[] $to_do Style handles about to be printed.
	 * @return string[]
	 */
	public static function filter_styles( $to_do ) {
		if ( ! is_array( $to_do ) || count( $to_do ) < 2 || ! self::is_active() ) {
			return $to_do;
		}

		$styles = wp_styles();
		$items  = [];

		foreach ( $to_do as $handle ) {
			$items[ $handle ] = self::describe_style( $styles, $handle );
		}

		return self::combine( $styles, array_values( $to_do ), $items, 'css' );
	}

	/**
	 * `print_scripts_array`: combine runs of module scripts. Only in the footer, where
	 * every widget on the page has enqueued its script; module scripts print there.
	 *
	 * @param string[] $to_do Script handles about to be printed.
	 * @return string[]
	 */
	public static function filter_scripts( $to_do ) {
		if ( ! self::$in_footer || ! is_array( $to_do ) || count( $to_do ) < 2 || ! self::is_active() ) {
			return $to_do;
		}

		$scripts = wp_scripts();
		$items   = [];

		foreach ( $to_do as $handle ) {
			$items[ $handle ] = self::describe_script( $scripts, $handle );
		}

		return self::combine( $scripts, array_values( $to_do ), $items, 'js' );
	}

	/**
	 * Group the module handles of a print queue into runs to combine.
	 *
	 * Items describe each handle:
	 * - null: a hard boundary (anything no run may move across);
	 * - a module file: `module` true, `group`, and `starts`/`ends` when inline code
	 *   must run before it (localized data, `before` scripts; it can only open a run)
	 *   or after it (`after` code; it closes its run);
	 * - a handle a run may continue past: `module` false, `group`, and `requires`, the
	 *   handles it depends on. The run stops there if it depends on a module of the run,
	 *   since the combined file lands after it.
	 *
	 * @param string[]                  $to_do Handles in print order.
	 * @param array<string, array|null> $items Per handle, as above.
	 * @return array<int, array<string, int>> Runs of two or more modules, each mapping
	 *                                        handle => position in $to_do, in order.
	 */
	public static function plan_runs( array $to_do, array $items ) {
		$runs  = [];
		$run   = [];
		$group = null;

		foreach ( array_values( $to_do ) as $position => $handle ) {
			$item = isset( $items[ $handle ] ) ? $items[ $handle ] : null;

			if ( null === $item ) {
				self::close_run( $runs, $run );
				continue;
			}

			if ( empty( $item['module'] ) ) {
				$passable = $run
					&& $item['group'] === $group
					&& ! array_intersect( isset( $item['requires'] ) ? $item['requires'] : [], array_keys( $run ) );

				if ( ! $passable ) {
					self::close_run( $runs, $run );
				}
				continue;
			}

			if ( $run && ( ! empty( $item['starts'] ) || $item['group'] !== $group ) ) {
				self::close_run( $runs, $run );
			}

			$run[ $handle ] = $position;
			$group          = $item['group'];

			if ( ! empty( $item['ends'] ) ) {
				self::close_run( $runs, $run );
			}
		}

		self::close_run( $runs, $run );

		return $runs;
	}

	private static function close_run( array &$runs, array &$run ) {
		if ( count( $run ) > 1 ) {
			$runs[] = $run;
		}

		$run = [];
	}

	/**
	 * Stylesheets a run of module stylesheets may move past: Elementor's per-widget
	 * base CSS, which Elementor already enqueues in the order the widgets sit on the
	 * page, and the icon fonts, which only declare font faces and glyphs.
	 *
	 * @param string $handle
	 * @return bool
	 */
	public static function is_passable_style( $handle ) {
		return 1 === preg_match(
			'/^(?:widget-[a-z0-9-]+|e-animation-[a-zA-Z0-9-]+|e-apple-webkit|e-swiper|swiper|elementor-icons-fa-[a-z]+|elementor-icons-shared-[0-9]+|prime-slider-font)$/',
			$handle
		);
	}

	/**
	 * Whether a path relative to a plugin root is a module's own CSS or JS file: the
	 * files a `bdtps-<module>` or `ps-<module>` handle loads, as opposed to UIkit, the
	 * site helper and vendor libraries.
	 *
	 * @param string $relative Path relative to the plugin root.
	 * @param string $type     `css` or `js`.
	 * @return bool
	 */
	public static function is_module_asset( $relative, $type ) {
		if ( 'css' === $type ) {
			$is_module = 1 === preg_match( '#^assets/css/ps-[a-z0-9-]+(?:\.rtl)?\.css$#', $relative );
		} else {
			$is_module = 1 === preg_match( '#^assets/js/modules/ps-[a-z0-9-]+\.min\.js$#', $relative );
		}

		/**
		 * Filters whether a file under a plugin root is a module file to combine.
		 *
		 * @param bool   $is_module Whether the file may go into a combined file.
		 * @param string $relative  Path relative to the plugin root.
		 * @param string $type      `css` or `js`.
		 */
		return (bool) apply_filters( 'prime_slider/optimization/is_module_asset', $is_module, $relative, $type );
	}

	/**
	 * Resolve a relative URL against the URL of the folder it appeared in.
	 *
	 * @param string $base     Folder URL, ending in a slash.
	 * @param string $relative Relative URL, possibly with a query string or fragment.
	 * @return string
	 */
	public static function resolve_url( $base, $relative ) {
		$cut    = strcspn( $relative, '?#' );
		$suffix = (string) substr( $relative, $cut );
		$path   = (string) substr( $relative, 0, $cut );

		if ( ! preg_match( '#^((?:[a-z][a-z0-9+.-]*:)?//[^/]*)(.*)$#i', $base, $parts ) ) {
			$parts = [ '', '', $base ];
		}

		$segments = [];

		foreach ( explode( '/', $parts[2] . $path ) as $segment ) {
			if ( '.' === $segment ) {
				continue;
			}

			if ( '..' === $segment ) {
				// The first segment is the empty one before the leading slash.
				if ( count( $segments ) > 1 ) {
					array_pop( $segments );
				}
				continue;
			}

			$segments[] = $segment;
		}

		return $parts[1] . implode( '/', $segments ) . $suffix;
	}

	/**
	 * Point the relative `url()`s of a stylesheet at its original folder, so they keep
	 * working from the uploads folder (fonts, images). Absolute URLs, root-relative
	 * paths, data URIs and fragment references are left alone.
	 *
	 * @param string $css  Stylesheet source.
	 * @param string $base URL of the folder the stylesheet was served from.
	 * @return string
	 */
	public static function rewrite_css_urls( $css, $base ) {
		return (string) preg_replace_callback(
			'/url\(\s*(?:"([^"]*)"|\'([^\']*)\'|([^)"\'\s]*))\s*\)/i',
			function ( $match ) use ( $base ) {
				if ( null !== $match[1] ) {
					list( $url, $quote ) = [ $match[1], '"' ];
				} elseif ( null !== $match[2] ) {
					list( $url, $quote ) = [ $match[2], "'" ];
				} else {
					list( $url, $quote ) = [ (string) $match[3], '' ];
				}

				if ( '' === $url || preg_match( '#^(?:[a-z][a-z0-9+.-]*:|/|\#)#i', $url ) ) {
					return $match[0];
				}

				return 'url(' . $quote . self::resolve_url( $base, $url ) . $quote . ')';
			},
			$css,
			-1,
			$count,
			PREG_UNMATCHED_AS_NULL
		);
	}

	/**
	 * A module stylesheet as it goes into a combined file.
	 *
	 * @param string $css Stylesheet source.
	 * @param string $src URL it is registered with.
	 * @return string
	 */
	public static function prepare_css( $css, $src ) {
		// Only valid as the first statement of a stylesheet, and ignored anywhere else.
		$css  = (string) preg_replace( '/@charset\s+["\'][^"\']*["\']\s*;/i', '', $css );
		$css  = (string) preg_replace( '#/\*\#\s*sourceMappingURL=[^*]*\*/#', '', $css );
		$base = (string) preg_replace( '/[?#].*$/', '', $src );
		$base = substr( $base, 0, strrpos( $base, '/' ) + 1 );

		return trim( self::rewrite_css_urls( $css, $base ) );
	}

	/**
	 * A module script as it goes into a combined file.
	 *
	 * @param string $js Script source.
	 * @return string
	 */
	public static function prepare_js( $js ) {
		// A source map comment would point at the wrong file, and a trailing line
		// comment would swallow the separator after the script.
		return trim( (string) preg_replace( '#^\s*//[\#@]\s*sourceMappingURL=.*$#m', '', $js ) );
	}

	/**
	 * @param \WP_Styles $styles
	 * @param string     $handle
	 * @return array|null See plan_runs().
	 */
	private static function describe_style( $styles, $handle ) {
		$dep = isset( $styles->registered[ $handle ] ) ? $styles->registered[ $handle ] : null;

		if ( ! $dep ) {
			return null;
		}

		$module = self::describe_module_style( $dep );

		if ( null !== $module ) {
			return $module;
		}

		if ( ! self::is_passable_style( $handle ) ) {
			return null;
		}

		return [
			'module'   => false,
			'group'    => 0,
			'requires' => self::requires( $styles, $handle ),
		];
	}

	/**
	 * @param \_WP_Dependency $dep
	 * @return array|null
	 */
	private static function describe_module_style( $dep ) {
		if ( ! is_string( $dep->src ) || '' === $dep->src ) {
			return null;
		}

		// The `args` of a style is its media attribute.
		if ( is_string( $dep->args ) && '' !== $dep->args && 'all' !== $dep->args ) {
			return null;
		}

		foreach ( [ 'conditional', 'alt', 'title' ] as $key ) {
			if ( ! empty( $dep->extra[ $key ] ) ) {
				return null;
			}
		}

		// WordPress swaps in the .rtl.css file for these; keep that working.
		if ( ! empty( $dep->extra['rtl'] ) && is_rtl() ) {
			return null;
		}

		$path = self::module_file( $dep->src, 'css' );

		if ( null === $path ) {
			return null;
		}

		return [
			'module' => true,
			'path'   => $path,
			'src'    => $dep->src,
			'group'  => 0,
			'starts' => false,
			'ends'   => ! empty( $dep->extra['after'] ),
		];
	}

	/**
	 * @param \WP_Scripts $scripts
	 * @param string      $handle
	 * @return array|null See plan_runs().
	 */
	private static function describe_script( $scripts, $handle ) {
		$dep = isset( $scripts->registered[ $handle ] ) ? $scripts->registered[ $handle ] : null;

		if ( ! $dep ) {
			return null;
		}

		$group  = isset( $scripts->groups[ $handle ] ) ? (int) $scripts->groups[ $handle ] : 0;
		$module = self::describe_module_script( $dep, $group );

		if ( null !== $module ) {
			return $module;
		}

		// Moving a module later is safe for any script that does not depend on it.
		return [
			'module'   => false,
			'group'    => $group,
			'requires' => self::requires( $scripts, $handle ),
		];
	}

	/**
	 * @param \_WP_Dependency $dep
	 * @param int             $group
	 * @return array|null
	 */
	private static function describe_module_script( $dep, $group ) {
		if ( ! is_string( $dep->src ) || '' === $dep->src ) {
			return null;
		}

		// A deferred or async script, one behind a conditional comment and one with
		// translations each need their own tag.
		if ( ! empty( $dep->extra['conditional'] ) || ! empty( $dep->extra['strategy'] ) || ! empty( $dep->textdomain ) ) {
			return null;
		}

		$path = self::module_file( $dep->src, 'js' );

		if ( null === $path ) {
			return null;
		}

		return [
			'module' => true,
			'path'   => $path,
			'src'    => $dep->src,
			'group'  => $group,
			'starts' => ! empty( $dep->extra['data'] ) || ! empty( $dep->extra['before'] ),
			'ends'   => ! empty( $dep->extra['after'] ),
		];
	}

	/**
	 * Every handle a handle depends on, directly or not.
	 *
	 * @param \WP_Dependencies $deps
	 * @param string           $handle
	 * @param array            $seen
	 * @return string[]
	 */
	private static function requires( $deps, $handle, array &$seen = [] ) {
		if ( isset( $seen[ $handle ] ) || ! isset( $deps->registered[ $handle ] ) ) {
			return [];
		}

		$seen[ $handle ] = true;
		$requires        = [];

		foreach ( (array) $deps->registered[ $handle ]->deps as $dependency ) {
			$requires[] = $dependency;
			$requires   = array_merge( $requires, self::requires( $deps, $dependency, $seen ) );
		}

		return $requires;
	}

	/**
	 * The local file behind a registered module asset URL, or null for anything that
	 * is not a module's own file of this plugin or an add-on plugin.
	 *
	 * @param string $src  Registered URL.
	 * @param string $type `css` or `js`.
	 * @return string|null
	 */
	private static function module_file( $src, $type ) {
		$src = (string) preg_replace( [ '/[?#].*$/', '#^https?:#i' ], '', $src );

		foreach ( self::roots() as $url => $dir ) {
			if ( 0 !== strpos( $src, $url ) ) {
				continue;
			}

			$relative = substr( $src, strlen( $url ) );

			if ( ! self::is_module_asset( $relative, $type ) ) {
				return null;
			}

			return is_readable( $dir . $relative ) ? $dir . $relative : null;
		}

		return null;
	}

	/**
	 * Plugin roots whose module files may be combined: URL (without scheme) => folder.
	 *
	 * @return array<string, string>
	 */
	private static function roots() {
		static $roots = null;

		if ( null === $roots ) {
			/**
			 * Filters the plugin folders whose module files may be combined, as
			 * plugin URL => plugin path. Add-ons register their own folder here.
			 *
			 * @param array<string, string> $roots Plugin URL => plugin path.
			 */
			$filtered = (array) apply_filters( 'prime_slider/optimization/asset_roots', [ BDTPS_CORE_URL => BDTPS_CORE_PATH ] );
			$roots    = [];

			foreach ( $filtered as $url => $dir ) {
				if ( ! is_string( $url ) || ! is_string( $dir ) || '' === $url || '' === $dir ) {
					continue;
				}

				$roots[ (string) preg_replace( '#^https?:#i', '', trailingslashit( $url ) ) ] = trailingslashit( $dir );
			}
		}

		return $roots;
	}

	/**
	 * @param \WP_Dependencies          $deps
	 * @param string[]                  $to_do
	 * @param array<string, array|null> $items
	 * @param string                    $type
	 * @return string[]
	 */
	private static function combine( $deps, array $to_do, array $items, $type ) {
		// From the last run back, so the positions of earlier runs stay valid.
		foreach ( array_reverse( self::plan_runs( $to_do, $items ) ) as $run ) {
			$handles  = array_keys( $run );
			$combined = self::combined_handle( $deps, $handles, $items, $type );

			if ( null === $combined ) {
				continue;
			}

			// The combined file takes the last module's place; the others leave the
			// queue, from the back so the remaining positions stay valid.
			$positions                        = array_values( $run );
			$to_do[ array_pop( $positions ) ] = $combined;

			foreach ( array_reverse( $positions ) as $position ) {
				array_splice( $to_do, $position, 1 );
			}

			// Printed through the combined file. Marked done so that a later queue on
			// this page (late styles, footer scripts) does not print them again.
			$deps->done = array_merge( $deps->done, $handles );
		}

		return $to_do;
	}

	/**
	 * Register the combined file for a run, building it when missing.
	 *
	 * @param \WP_Dependencies          $deps
	 * @param string[]                  $handles
	 * @param array<string, array|null> $items
	 * @param string                    $type
	 * @return string|null The combined handle, or null to keep the separate files.
	 */
	private static function combined_handle( $deps, array $handles, array $items, $type ) {
		$location = self::location();

		if ( null === $location ) {
			return null;
		}

		$inputs = [];

		foreach ( $handles as $handle ) {
			$path     = $items[ $handle ]['path'];
			$inputs[] = [ $handle, $items[ $handle ]['src'], (int) filemtime( $path ), (int) filesize( $path ) ];
		}

		$key  = substr( md5( $type . '|' . wp_json_encode( $inputs ) ), 0, 12 );
		$file = self::folder() . '/ps-set-' . $key . '.' . $type;
		$path = $location['dir'] . '/' . $file;

		// Checked only when a file is missing, so a page whose files exist costs no query.
		if ( ! is_file( $path ) && ( get_transient( self::FAILED_TRANSIENT ) || ! self::build( $path, $handles, $items, $type ) ) ) {
			return null;
		}

		$combined = 'bdtps-set-' . $key;
		$first    = $handles[0];
		$last     = $handles[ count( $handles ) - 1 ];

		// The name is a hash of the inputs, so the file never changes: no version.
		$deps->add( $combined, $location['url'] . '/' . $file, [], null, 'css' === $type ? 'all' : null );

		if ( 'js' === $type ) {
			$group = $items[ $first ]['group'];
			$deps->add_data( $combined, 'group', $group );
			$deps->set_group( $combined, false, $group );

			// Only the first handle of a run may carry code that runs before it.
			foreach ( [ 'data', 'before' ] as $key_name ) {
				$value = $deps->get_data( $first, $key_name );

				if ( $value ) {
					$deps->add_data( $combined, $key_name, $value );
				}
			}
		}

		// Only the last handle of a run may carry code that runs after it.
		$after = $deps->get_data( $last, 'after' );

		if ( $after ) {
			$deps->add_data( $combined, 'after', $after );
		}

		return $combined;
	}

	/**
	 * Write a combined file atomically. Two requests building the same file write the
	 * same bytes, so no lock is needed.
	 *
	 * @param string                    $path
	 * @param string[]                  $handles
	 * @param array<string, array|null> $items
	 * @param string                    $type
	 * @return bool
	 */
	private static function build( $path, array $handles, array $items, $type ) {
		$dir        = dirname( $path );
		$new_folder = ! is_dir( $dir );

		if ( $new_folder && ! wp_mkdir_p( $dir ) ) {
			return self::failed();
		}

		if ( ! $new_folder && count( (array) glob( $dir . '/ps-set-*.' . $type ) ) >= self::MAX_FILES ) {
			return false;
		}

		$parts = [];

		foreach ( $handles as $handle ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local plugin file.
			$code = file_get_contents( $items[ $handle ]['path'] );

			if ( false === $code ) {
				return false;
			}

			$parts[] = 'css' === $type ? self::prepare_css( $code, $items[ $handle ]['src'] ) : self::prepare_js( $code );
		}

		$names   = implode( ', ', array_map( 'sanitize_key', $handles ) );
		$content = '/*! Prime Slider: ' . $names . " */\n" . implode( 'css' === $type ? "\n" : "\n;\n", $parts ) . "\n";
		$tmp     = $path . '.' . uniqid( '', true ) . '.tmp';

		// A read-only uploads folder must not print warnings into the page.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents, WordPress.PHP.NoSilencedErrors.Discouraged -- Atomic temp-file write; WP_Filesystem has no rename-over.
		if ( false === @file_put_contents( $tmp, $content, LOCK_EX ) ) {
			wp_delete_file( $tmp );

			return self::failed();
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.rename_rename, WordPress.PHP.NoSilencedErrors.Discouraged -- Atomic replace.
		if ( ! @rename( $tmp, $path ) ) {
			wp_delete_file( $tmp );

			return self::failed();
		}

		if ( $new_folder ) {
			// A new folder means a new fingerprint (plugin update): the previous
			// folder can go once page caches have moved on.
			self::schedule_cleanup();
		}

		return true;
	}

	private static function failed() {
		set_transient( self::FAILED_TRANSIENT, 1, HOUR_IN_SECONDS );

		return false;
	}

	/**
	 * Where combined files are written and served from.
	 *
	 * @return array{dir: string, url: string}|null
	 */
	private static function location() {
		$uploads = wp_upload_dir( null, false );

		if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) || empty( $uploads['baseurl'] ) ) {
			return null;
		}

		$location = [
			'dir' => untrailingslashit( $uploads['basedir'] ) . '/' . self::DIR,
			'url' => set_url_scheme( untrailingslashit( $uploads['baseurl'] ) . '/' . self::DIR ),
		];

		/**
		 * Filters where Prime Slider writes and serves combined module files.
		 *
		 * @param array{dir: string, url: string} $location Folder and its URL.
		 */
		$location = apply_filters( 'prime_slider/optimization/combined_assets_location', $location );

		if ( ! is_array( $location ) || empty( $location['dir'] ) || empty( $location['url'] ) ) {
			return null;
		}

		return [
			'dir' => untrailingslashit( $location['dir'] ),
			'url' => untrailingslashit( $location['url'] ),
		];
	}

	/**
	 * Fingerprint of everything the combined files are built from, other than the
	 * module files themselves (which are hashed per file): this plugin's version, and
	 * whatever add-ons contribute. A new fingerprint means a new folder, so a plugin
	 * update never serves a stale combination.
	 *
	 * @return string
	 */
	public static function fingerprint() {
		$inputs = [ 'prime-slider:' . BDTPS_CORE_VER ];

		/**
		 * Filters the inputs the combined-files fingerprint is built from. Add-ons
		 * append their own version here.
		 *
		 * @param string[] $inputs Fingerprint inputs.
		 */
		$inputs = (array) apply_filters( 'prime_slider/optimization/fingerprint_inputs', $inputs );

		return md5( implode( '|', array_map( 'strval', $inputs ) ) );
	}

	/**
	 * Folder of the current fingerprint.
	 *
	 * @return string
	 */
	private static function folder() {
		if ( null === self::$folder ) {
			self::$folder = substr( self::fingerprint(), 0, 8 );
		}

		return self::$folder;
	}

	private static function schedule_cleanup() {
		if ( ! wp_next_scheduled( self::CLEANUP_HOOK ) ) {
			wp_schedule_single_event( time() + self::GRACE_PERIOD + HOUR_IN_SECONDS, self::CLEANUP_HOOK );
		}
	}

	/**
	 * WP-Cron: remove the folders of superseded fingerprints once they have gone
	 * unused for the grace period.
	 *
	 * @return void
	 */
	public static function cleanup() {
		$location = self::location();

		if ( null === $location || ! is_dir( $location['dir'] ) ) {
			return;
		}

		$pending = false;

		foreach ( self::folders( $location['dir'] ) as $folder ) {
			if ( basename( $folder ) === self::folder() ) {
				continue;
			}

			// A folder's mtime is when its last file was written.
			if ( filemtime( $folder ) > time() - self::GRACE_PERIOD ) {
				$pending = true;
				continue;
			}

			self::delete_folder( $folder );
		}

		if ( $pending ) {
			self::schedule_cleanup();
		}
	}

	/**
	 * Remove every combined file, as Elementor's "Clear Files & Data" does with its
	 * own. Pages rebuild what they need on their next view.
	 *
	 * @return void
	 */
	public static function flush() {
		$location = self::location();

		if ( null !== $location && is_dir( $location['dir'] ) ) {
			foreach ( self::folders( $location['dir'] ) as $folder ) {
				self::delete_folder( $folder );
			}
		}

		delete_transient( self::FAILED_TRANSIENT );
	}

	/**
	 * Fingerprint folders inside the combined files folder.
	 *
	 * @param string $root
	 * @return string[]
	 */
	private static function folders( $root ) {
		return array_filter(
			(array) glob( $root . '/*', GLOB_ONLYDIR ),
			function ( $folder ) {
				return 1 === preg_match( '/^[a-f0-9]{8}$/', basename( (string) $folder ) );
			}
		);
	}

	private static function delete_folder( $folder ) {
		foreach ( (array) glob( $folder . '/*' ) as $file ) {
			if ( is_file( $file ) ) {
				wp_delete_file( $file );
			}
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir, WordPress.PHP.NoSilencedErrors.Discouraged -- Folder we created; a file that could not be deleted keeps it.
		@rmdir( $folder );
	}
}
