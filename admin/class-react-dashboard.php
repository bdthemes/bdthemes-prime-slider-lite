<?php
/**
 * Prime Slider — React admin dashboard.
 *
 * Replaces the body of the Prime Slider dashboard page with a React app built
 * from src/admin/app (output in build/admin), laid out like the Element Pack
 * dashboard: app bar, tab strip, cards.
 *
 * Nothing new is exposed to the network: settings are saved through the
 * existing nonce- and capability-checked `prime_slider_settings_save` AJAX
 * action, and every value the app shows is passed in once, on page load.
 *
 * Panels that are rendered by PHP — the Other Plugins installer and the tabs an
 * add-on registers through `prime_slider_dashboard_extra_tabs` or the
 * `prime_slider/license_page` hook (Prime Slider Pro: Rollback, White Label,
 * Custom CSS & JS, License) — are still rendered here, hidden, and moved into
 * the app's matching route, so their own markup, scripts and forms keep
 * working unchanged.
 *
 * When the build is missing, or the `prime_slider/admin/react_dashboard`
 * filter returns false, the classic dashboard renders instead.
 *
 * @package PrimeSlider
 */

use PrimeSlider\Admin\ModuleService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PrimeSlider_React_Dashboard {

	const HANDLE  = 'prime-slider-react-admin';
	const PAGE_ID = 'prime_slider_options';

	/**
	 * Settings sections the app manages, in tab order.
	 */
	const SECTIONS = [
		'prime_slider_active_modules',
		'prime_slider_third_party_widget',
		'prime_slider_elementor_extend',
		'prime_slider_other_settings',
	];

	/**
	 * Whether the React dashboard renders on this request.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$built = is_readable( BDTPS_CORE_PATH . 'build/admin/index.js' )
			&& is_readable( BDTPS_CORE_PATH . 'build/admin/index.asset.php' );

		/**
		 * Filters whether the React dashboard replaces the classic one.
		 *
		 * @param bool $enabled Whether the built app is available.
		 */
		return (bool) apply_filters( 'prime_slider/admin/react_dashboard', $built );
	}

	public function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
		add_filter( 'admin_body_class', [ $this, 'body_class' ] );
	}

	/**
	 * Whether the current admin screen is the Prime Slider dashboard.
	 *
	 * @param string $hook_suffix Admin page hook suffix.
	 * @return bool
	 */
	private function is_dashboard_screen( $hook_suffix = '' ) {
		if ( 'toplevel_page_' . self::PAGE_ID === $hook_suffix ) {
			return true;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only picks which assets load.
		return is_admin() && isset( $_GET['page'] ) && self::PAGE_ID === sanitize_key( wp_unslash( $_GET['page'] ) );
	}

	/**
	 * `admin_body_class`: full-bleed layout hooks for the dashboard page.
	 *
	 * @param string $classes Body classes.
	 * @return string
	 */
	public function body_class( $classes ) {
		if ( $this->is_dashboard_screen() && self::is_enabled() ) {
			$classes .= ' ps-react-admin';
		}

		return $classes;
	}

	/**
	 * `admin_enqueue_scripts`: the app bundle and its bootstrap data.
	 *
	 * @param string $hook_suffix Admin page hook suffix.
	 * @return void
	 */
	public function enqueue( $hook_suffix ) {
		if ( ! $this->is_dashboard_screen( $hook_suffix ) || ! self::is_enabled() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$asset = include BDTPS_CORE_PATH . 'build/admin/index.asset.php';
		$deps  = isset( $asset['dependencies'] ) ? (array) $asset['dependencies'] : [];
		$ver   = isset( $asset['version'] ) ? $asset['version'] : BDTPS_CORE_VER;

		wp_enqueue_script( self::HANDLE, BDTPS_CORE_URL . 'build/admin/index.js', $deps, $ver, true );

		if ( is_readable( BDTPS_CORE_PATH . 'build/admin/index.css' ) ) {
			wp_enqueue_style( self::HANDLE, BDTPS_CORE_URL . 'build/admin/index.css', [], $ver );
			wp_style_add_data( self::HANDLE, 'rtl', 'replace' );
			wp_style_add_data( self::HANDLE, 'suffix', '' );
		}

		wp_set_script_translations( self::HANDLE, 'bdthemes-prime-slider-lite', BDTPS_CORE_PATH . 'languages' );

		wp_localize_script( self::HANDLE, 'PrimeSliderAdmin', $this->get_config() );
	}

	/**
	 * Render the dashboard page: the app's mount node, and the PHP-rendered
	 * panels the app moves into its own routes.
	 *
	 * @param PrimeSlider_Admin_Settings $settings The classic settings page, which renders those panels.
	 * @return void
	 */
	public function render( $settings ) {
		?>
		<div class="wrap ps-react-wrap">
			<h1 class="screen-reader-text"><?php echo esc_html( BDTPS_CORE_TITLE ); ?></h1>
			<div id="ps-admin-app"></div>

			<div id="ps-admin-panels" hidden>
				<?php foreach ( $this->get_panels() as $panel ) : ?>
					<div class="ps-admin-panel prime-slider-dashboard" data-ps-panel="<?php echo esc_attr( $panel['id'] ); ?>">
						<?php call_user_func( $panel['render'], $settings ); ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * PHP-rendered panels: id, title, icon (a dashicons class) and renderer.
	 *
	 * @return array[]
	 */
	private function get_panels() {
		$panels = [
			[
				'id'     => 'prime_slider_other_plugins',
				'title'  => __( 'Other Plugins', 'bdthemes-prime-slider-lite' ),
				'icon'   => 'dashicons dashicons-admin-plugins',
				'render' => function ( $settings ) {
					$settings->prime_slider_others_plugin();
				},
			],
		];

		/** This filter is documented in admin/class-settings-api.php */
		foreach ( (array) apply_filters( 'prime_slider_dashboard_extra_tabs', [] ) as $tab ) {
			if ( empty( $tab['id'] ) || empty( $tab['title'] ) || ! isset( $tab['callback'] ) || ! is_callable( $tab['callback'] ) ) {
				continue;
			}

			$callback = $tab['callback'];
			$panels[] = [
				'id'     => sanitize_key( $tab['id'] ),
				'title'  => html_entity_decode( wp_strip_all_tags( (string) $tab['title'] ), ENT_QUOTES, 'UTF-8' ),
				'icon'   => isset( $tab['icon'] ) ? (string) $tab['icon'] : 'dashicons dashicons-screenoptions',
				'render' => function () use ( $callback ) {
					call_user_func( $callback );
				},
			];
		}

		if ( function_exists( 'bdtps_is_pro_activated' ) && true === bdtps_is_pro_activated() ) {
			$panels[] = [
				'id'     => 'prime_slider_license_settings',
				'title'  => __( 'License', 'bdthemes-prime-slider-lite' ),
				'icon'   => 'dashicons dashicons-admin-network',
				'render' => function () {
					apply_filters( 'prime_slider/license_page', '' );
				},
			];
		}

		return $panels;
	}

	/**
	 * Bootstrap data for the app (window.PrimeSliderAdmin).
	 *
	 * @return array
	 */
	private function get_config() {
		$user   = wp_get_current_user();
		$is_pro = function_exists( 'bdtps_is_pro_activated' ) && true === bdtps_is_pro_activated();

		$panels = array_map(
			function ( $panel ) {
				return [
					'id'    => $panel['id'],
					'title' => $panel['title'],
					'icon'  => $panel['icon'],
				];
			},
			$this->get_panels()
		);

		return [
			'version'        => BDTPS_CORE_VER,
			'title'          => BDTPS_CORE_TITLE,
			'whiteLabel'     => 'Prime Slider' !== BDTPS_CORE_TITLE,
			'isPro'          => $is_pro,
			'userName'       => $user ? $user->display_name : '',
			'adminUrl'       => admin_url(),
			'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
			'saveNonce'      => wp_create_nonce( 'prime-slider-settings-save-nonce' ),
			'logoUrl'        => BDTPS_CORE_URL . 'assets/images/logo-with-text.svg',
			'logoIconUrl'    => BDTPS_CORE_URL . 'assets/images/logo.svg',
			'newPageUrl'     => admin_url( 'post-new.php?post_type=page' ),
			'setupWizardUrl' => admin_url( '?ps_setup_wizard=show' ),
			'docsUrl'        => 'https://bdthemes.com/knowledge-base/prime-slider/',
			'supportUrl'     => 'https://bdthemes.com/support/',
			'videosUrl'      => 'https://www.youtube.com/watch?v=sZwJDtxasTg&list=PLP0S85GEw7DP3-yJrkgwpIeDFoXy0PDlM',
			'demoUrl'        => 'https://primeslider.pro/demo/',
			'featureUrl'     => 'https://feedback.bdthemes.com/b/6vr2250l/feature-requests/idea/new',
			'reviewUrl'      => 'https://wordpress.org/support/plugin/bdthemes-prime-slider-lite/reviews/',
			'whatsNewUrl'    => 'https://wordpress.org/plugins/bdthemes-prime-slider-lite/#developers',
			'pricingUrl'     => 'https://primeslider.pro/pricing/',
			'getProUrl'      => 'https://primeslider.pro/pricing?utm_source=PrimeSlider&utm_medium=PluginPage&utm_campaign=PrimeSlider&coupon=FREETOPRO',
			'sections'       => $this->get_sections(),
			'system'         => $this->get_system_checks(),
			'releases'       => $this->get_releases(),
			'panels'         => $panels,
			'assets'         => $this->get_asset_stats(),
		];
	}

	/**
	 * Every settings section with its fields and current values.
	 *
	 * @return array
	 */
	private function get_sections() {
		$fields_by_section = ModuleService::get_widget_settings(
			function ( $settings ) {
				return $settings['settings_fields'];
			}
		);

		$is_pro      = function_exists( 'bdtps_is_pro_activated' ) && true === bdtps_is_pro_activated();
		$used        = class_exists( 'PrimeSlider_Admin_Settings' ) ? (array) PrimeSlider_Admin_Settings::get_used_widgets() : [];
		$has_usage   = class_exists( '\Elementor\Modules\Usage\Module' );
		$can_install = current_user_can( 'install_plugins' );
		$can_activate = current_user_can( 'activate_plugins' );

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$installed_plugins = get_plugins();
		$sections          = [];

		foreach ( self::SECTIONS as $section_id ) {
			$fields = isset( $fields_by_section[ $section_id ] ) ? (array) $fields_by_section[ $section_id ] : [];
			$stored = get_option( $section_id, [] );
			$stored = is_array( $stored ) ? $stored : [];
			$items  = [];
			$group  = null;

			foreach ( $fields as $field ) {
				$type = isset( $field['type'] ) ? $field['type'] : '';

				if ( 'start_group' === $type ) {
					$group = [
						'title' => isset( $field['label'] ) ? html_entity_decode( wp_strip_all_tags( (string) $field['label'] ), ENT_QUOTES, 'UTF-8' ) : '',
						'desc'  => isset( $field['desc'] ) ? wp_kses_post( (string) $field['desc'] ) : '',
					];
					continue;
				}

				if ( 'end_group' === $type ) {
					$group = null;
					continue;
				}

				if ( 'checkbox' !== $type || empty( $field['name'] ) ) {
					continue;
				}

				$name    = (string) $field['name'];
				$default = isset( $field['default'] ) ? (string) $field['default'] : 'off';
				$value   = isset( $stored[ $name ] ) ? (string) $stored[ $name ] : $default;
				$slug    = 'prime-slider-' . str_replace( ' ', '-', strtolower( $name ) );
				$count   = isset( $used[ $slug ] ) ? (int) $used[ $slug ] : 0;

				if ( 0 === $count ) {
					$alt   = str_replace( '_', '-', $slug );
					$count = isset( $used[ $alt ] ) ? (int) $used[ $alt ] : 0;
				}

				$item = [
					'name'         => $name,
					'label'        => isset( $field['label'] ) ? html_entity_decode( wp_strip_all_tags( (string) $field['label'] ), ENT_QUOTES, 'UTF-8' ) : $name,
					'enabled'      => 'on' === $value,
					'default'      => $default,
					'widget_type'  => isset( $field['widget_type'] ) && 'pro' === $field['widget_type'] ? 'pro' : 'free',
					'content_type' => isset( $field['content_type'] ) ? (string) $field['content_type'] : '',
					'demo_url'     => ! empty( $field['demo_url'] ) ? esc_url_raw( $field['demo_url'] ) : '',
					'video_url'    => ! empty( $field['video_url'] ) ? esc_url_raw( $field['video_url'] ) : '',
					'usage_count'  => $count,
					'has_usage'    => $has_usage,
					'is_locked'    => isset( $field['widget_type'] ) && 'pro' === $field['widget_type'] && ! $is_pro,
				];

				if ( $group ) {
					$item['group'] = $group;
				}

				if ( ! empty( $field['plugin_path'] ) ) {
					$plugin_path = (string) $field['plugin_path'];
					$plugin_slug = ! empty( $field['plugin_name'] ) ? (string) $field['plugin_name'] : dirname( $plugin_path );
					$installed   = isset( $installed_plugins[ $plugin_path ] );
					$active      = $installed && is_plugin_active( $plugin_path );

					$item['requires_plugin'] = $plugin_slug;
					$item['plugin_active']   = $active;
					$item['action_url']      = '';

					if ( $installed && ! $active && $can_activate ) {
						$item['action']     = 'activate';
						$item['action_url'] = wp_nonce_url( admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( $plugin_path ) ), 'activate-plugin_' . $plugin_path );
					} elseif ( ! $installed && ! empty( $field['paid'] ) ) {
						$item['action']     = 'download';
						$item['action_url'] = esc_url_raw( $field['paid'] );
					} elseif ( ! $installed && $can_install ) {
						$item['action']     = 'install';
						$item['action_url'] = wp_nonce_url( admin_url( 'update.php?action=install-plugin&plugin=' . rawurlencode( $plugin_slug ) ), 'install-plugin_' . $plugin_slug );
					}
				}

				$items[] = $item;
			}

			$sections[ $section_id ] = [
				'id'     => $section_id,
				'fields' => $items,
				// Every stored value, so a save never drops fields the app does not list.
				'stored' => array_map( 'strval', array_filter( $stored, 'is_scalar' ) ),
			];
		}

		return $sections;
	}

	/**
	 * Asset Optimization state and the combined files it has written.
	 *
	 * @return array
	 */
	private function get_asset_stats() {
		$stats = class_exists( '\PrimeSlider\Includes\Asset_Combiner' )
			? \PrimeSlider\Includes\Asset_Combiner::stats()
			: [ 'css' => 0, 'js' => 0, 'bytes' => 0 ];

		return [
			'enabled' => function_exists( 'prime_slider_is_asset_optimization_enabled' ) && prime_slider_is_asset_optimization_enabled(),
			'css'     => (int) $stats['css'],
			'js'      => (int) $stats['js'],
			'size'    => size_format( (int) $stats['bytes'] ),
		];
	}

	/**
	 * Server environment checks for the System Status page.
	 *
	 * @return array[]
	 */
	private function get_system_checks() {
		$php_version   = phpversion();
		$max_execution = (int) ini_get( 'max_execution_time' );
		$memory_limit  = (string) ini_get( 'memory_limit' );
		$post_limit    = (string) ini_get( 'post_max_size' );
		$uploads       = wp_upload_dir( null, false );
		$debug         = defined( 'WP_DEBUG' ) && WP_DEBUG;

		return [
			[
				'id'    => 'php',
				'label' => __( 'PHP version', 'bdthemes-prime-slider-lite' ),
				'value' => $php_version,
				'pass'  => version_compare( $php_version, '7.4.0', '>=' ),
				'hint'  => __( 'Min: 7.4', 'bdthemes-prime-slider-lite' ),
			],
			[
				'id'    => 'max_execution_time',
				'label' => __( 'Max execution time', 'bdthemes-prime-slider-lite' ),
				'value' => (string) $max_execution,
				'pass'  => 0 === $max_execution || $max_execution >= 90,
				'hint'  => __( 'Min: 90 recommended', 'bdthemes-prime-slider-lite' ),
			],
			[
				'id'    => 'memory_limit',
				'label' => __( 'Memory limit', 'bdthemes-prime-slider-lite' ),
				'value' => $memory_limit,
				'pass'  => '-1' === $memory_limit || wp_convert_hr_to_bytes( $memory_limit ) >= 512 * MB_IN_BYTES,
				'hint'  => __( 'Min: 512M recommended', 'bdthemes-prime-slider-lite' ),
			],
			[
				'id'    => 'post_max_size',
				'label' => __( 'Max post size', 'bdthemes-prime-slider-lite' ),
				'value' => $post_limit,
				'pass'  => wp_convert_hr_to_bytes( $post_limit ) >= 32 * MB_IN_BYTES,
				'hint'  => __( 'Min: 32M recommended', 'bdthemes-prime-slider-lite' ),
			],
			[
				'id'    => 'uploads_writable',
				'label' => __( 'Uploads folder writable', 'bdthemes-prime-slider-lite' ),
				'value' => empty( $uploads['error'] ) && wp_is_writable( $uploads['basedir'] ) ? __( 'Yes', 'bdthemes-prime-slider-lite' ) : __( 'No', 'bdthemes-prime-slider-lite' ),
				'pass'  => empty( $uploads['error'] ) && wp_is_writable( $uploads['basedir'] ),
			],
			[
				'id'    => 'gzip',
				'label' => __( 'GZip enabled', 'bdthemes-prime-slider-lite' ),
				'value' => function_exists( 'gzencode' ) ? __( 'Yes', 'bdthemes-prime-slider-lite' ) : __( 'No', 'bdthemes-prime-slider-lite' ),
				'pass'  => function_exists( 'gzencode' ),
			],
			[
				'id'    => 'multisite',
				'label' => __( 'Multisite', 'bdthemes-prime-slider-lite' ),
				'value' => is_multisite() ? __( 'Multisite', 'bdthemes-prime-slider-lite' ) : __( 'Single site', 'bdthemes-prime-slider-lite' ),
				'pass'  => true,
			],
			[
				'id'    => 'debug',
				'label' => __( 'Debug mode', 'bdthemes-prime-slider-lite' ),
				'value' => $debug ? __( 'On', 'bdthemes-prime-slider-lite' ) : __( 'Off', 'bdthemes-prime-slider-lite' ),
				'pass'  => ! $debug,
			],
		];
	}

	/**
	 * The latest releases from readme.txt, for the Recent activity card.
	 *
	 * @param int $limit Number of releases.
	 * @return array[]
	 */
	private function get_releases( $limit = 5 ) {
		$readme = BDTPS_CORE_PATH . 'readme.txt';

		if ( ! is_readable( $readme ) ) {
			return [];
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local plugin file.
		$text = (string) file_get_contents( $readme );
		$at   = strpos( $text, '== Changelog ==' );

		if ( false === $at ) {
			return [];
		}

		$text     = substr( $text, $at );
		$releases = [];

		if ( ! preg_match_all( '/^=\s*([0-9][0-9.]*)\s*(?:\[([^\]]*)\])?\s*=\s*$/m', $text, $heads, PREG_OFFSET_CAPTURE | PREG_SET_ORDER ) ) {
			return [];
		}

		foreach ( $heads as $index => $head ) {
			if ( count( $releases ) >= $limit ) {
				break;
			}

			$start = $head[0][1] + strlen( $head[0][0] );
			$end   = isset( $heads[ $index + 1 ] ) ? $heads[ $index + 1 ][0][1] : strlen( $text );
			$body  = substr( $text, $start, $end - $start );

			preg_match_all( '/^\*\s*(?:([A-Za-z]+):)?\s*(.+)$/m', $body, $lines, PREG_SET_ORDER );

			$sections   = [];
			$highlights = [];

			foreach ( $lines as $line ) {
				$kind = strtolower( trim( (string) $line[1] ) );
				$map  = [
					'added'      => 'added',
					'new'        => 'added',
					'fixed'      => 'fixed',
					'fix'        => 'fixed',
					'security'   => 'fixed',
					'improved'   => 'updated',
					'updated'    => 'updated',
					'changed'    => 'updated',
					'compliance' => 'updated',
					'removed'    => 'removed',
				];
				$key  = isset( $map[ $kind ] ) ? $map[ $kind ] : 'updated';

				$sections[ $key ] = isset( $sections[ $key ] ) ? $sections[ $key ] + 1 : 1;
				$highlights[]     = wp_strip_all_tags( trim( (string) $line[2] ) );
			}

			$releases[] = [
				'version'    => $head[1][0],
				'date'       => isset( $head[2][0] ) ? trim( (string) $head[2][0] ) : '',
				'is_current' => BDTPS_CORE_VER === $head[1][0],
				'sections'   => $sections,
				'highlights' => array_slice( $highlights, 0, 2 ),
			];
		}

		return $releases;
	}
}
