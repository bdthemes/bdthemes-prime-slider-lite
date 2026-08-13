<?php
/**
 * Security remediation module — compromised notification feed.
 *
 * Neutralises the remote-notification injection vector, detects the artefacts
 * left behind on sites that were already hit, and surfaces a dismissible
 * admin notice describing the issue.
 *
 * HOW TO USE
 * ----------
 * 1. Copy this file into the plugin (e.g. includes/security-remediation.php).
 * 2. Change the namespace on the line below and the CONFIGURATION block.
 * 3. Require and bootstrap it from the main plugin file, as early as possible:
 *
 *        require_once __DIR__ . '/includes/security-remediation.php';
 *        \BDThemes\ElementPack\Security_Remediation\bootstrap();
 *
 * This module is a safety net. It does NOT replace removing the notification
 * feed rendering from the plugin itself — do that too.
 *
 * @since 1.0.0
 */

namespace BDThemes\PrimeSlider\Security_Remediation; // <-- CHANGE PER PLUGIN.

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * CONFIGURATION — the only block that needs editing per plugin.
 * ---------------------------------------------------------------------- */

/** Human-readable plugin name, used in the notice. */
const PLUGIN_LABEL = 'Prime Slider';

/** The plugin's text domain. */
const TEXT_DOMAIN = 'bdthemes-prime-slider';

/** Prefix for options, hooks, nonces and user meta. Must be unique per plugin. */
const KEY_PREFIX = 'bdthemes_ps_secfix';

/** Bump to re-run the scan and re-show the notice to users who dismissed it. */
const MODULE_VERSION = '1.1.0';

/** Public advisory page. Leave empty to omit the link from the notice. */
const ADVISORY_URL = '';

/* -------------------------------------------------------------------------
 * INDICATORS OF COMPROMISE
 * ---------------------------------------------------------------------- */

/** Marker file dropped inside the backdoor plugin directory. */
const BACKDOOR_FILE = 'emer-run.php';

/**
 * Hosts the payload talks to. Any outbound request to these is hard-blocked.
 *
 * @return string[]
 */
function blocked_hosts() {
	return (array) apply_filters( KEY_PREFIX . '_blocked_hosts', array( 'ia-cdn.com' ) );
}

/**
 * Hosts serving the notification feed. Responses from these are inspected and
 * emptied if they carry a payload. Not blocked outright — they serve other,
 * legitimate endpoints.
 *
 * @return string[]
 */
function feed_hosts() {
	return (array) apply_filters( KEY_PREFIX . '_feed_hosts', array( 'api.sigmative.io' ) );
}

/**
 * Substrings that identify the payload. Note that the C2 hostname itself is
 * char-code encoded in the feed, so the encoder markers matter more than the
 * domain.
 *
 * @return string[]
 */
function payload_signatures() {
	return (array) apply_filters(
		KEY_PREFIX . '_payload_signatures',
		array(
			'onanimationstart',
			'String.fromCharCode',
			'fromCharCode',
			'ia-cdn.com',
			BACKDOOR_FILE,
			'__bdt_wg3',
			'__bdt_d3',
		)
	);
}

/**
 * Plugin slugs known to be dropped by the payload.
 *
 * @return string[]
 */
function known_backdoor_slugs() {
	return (array) apply_filters( KEY_PREFIX . '_backdoor_slugs', array( 'wp-smart-thumbnails' ) );
}

/**
 * Email domains that no legitimate account uses. A match here is conclusive:
 * these addresses are not deliverable and exist only because the payload
 * created them.
 *
 * @return string[]
 */
function rogue_email_domains() {
	return (array) apply_filters( KEY_PREFIX . '_rogue_email_domains', array( 'developer.wordpress.org' ) );
}

/**
 * Exact accounts observed in the wild, as login/email pairs.
 *
 * @return array[]
 */
function rogue_accounts() {
	return (array) apply_filters(
		KEY_PREFIX . '_rogue_accounts',
		array(
			array(
				'login' => 'recovery',
				'email' => 'recovery@wordpress.org',
			),
			array(
				'login' => 'service',
				'email' => 'service@wordpress.org',
			),
		)
	);
}

/**
 * Domains that warrant a look but are NOT proof of compromise on their own —
 * a real WordPress.org contributor may legitimately hold an account with such
 * an address. Accounts matched only by this list are reported, never removed.
 *
 * @return string[]
 */
function suspect_email_domains() {
	return (array) apply_filters( KEY_PREFIX . '_suspect_email_domains', array( 'wordpress.org' ) );
}

/**
 * Sites where the account indicators above mean nothing, because addresses on
 * these domains are the norm rather than the exception. User detection is
 * skipped entirely here — nothing is reported and nothing is offered for
 * removal. Every other check still runs.
 *
 * @return string[]
 */
function exempt_site_domains() {
	return (array) apply_filters( KEY_PREFIX . '_exempt_site_domains', array( 'wordpress.org' ) );
}

/**
 * Whether this install is one of the exempt sites.
 *
 * @return bool
 */
function is_exempt_site() {
	$domains = exempt_site_domains();

	if ( empty( $domains ) ) {
		return false;
	}

	$hosts = array(
		wp_parse_url( home_url(), PHP_URL_HOST ),
		wp_parse_url( site_url(), PHP_URL_HOST ),
	);

	if ( is_multisite() ) {
		$hosts[] = wp_parse_url( network_home_url(), PHP_URL_HOST );
	}

	foreach ( $hosts as $host ) {
		if ( $host && host_matches( $host, $domains ) ) {
			return true;
		}
	}

	return false;
}

/* -------------------------------------------------------------------------
 * BOOTSTRAP
 * ---------------------------------------------------------------------- */

/**
 * Registers every hook this module needs. Safe to call more than once.
 *
 * @return void
 */
function bootstrap() {
	if ( did_action( KEY_PREFIX . '_loaded' ) ) {
		return;
	}

	// Vector kill — always on, front end and admin, cheap and non-destructive.
	add_filter( 'pre_http_request', __NAMESPACE__ . '\\block_malicious_request', 1, 3 );
	add_filter( 'http_response', __NAMESPACE__ . '\\scrub_feed_response', 1, 3 );

	if ( is_admin() ) {
		add_action( 'admin_init', __NAMESPACE__ . '\\maybe_scan' );
		add_action( 'admin_notices', __NAMESPACE__ . '\\render_notice' );
		add_action( 'network_admin_notices', __NAMESPACE__ . '\\render_notice' );
		add_action( 'admin_post_' . KEY_PREFIX . '_clean', __NAMESPACE__ . '\\handle_clean' );
		add_action( 'wp_ajax_' . KEY_PREFIX . '_dismiss', __NAMESPACE__ . '\\handle_dismiss' );
	}

	do_action( KEY_PREFIX . '_loaded' );
}

/* -------------------------------------------------------------------------
 * VECTOR KILL
 * ---------------------------------------------------------------------- */

/**
 * Blocks any outbound HTTP request to a known C2 host, whatever code made it.
 *
 * @param false|array|\WP_Error $preempt Short-circuit value.
 * @param array                 $args    Request arguments.
 * @param string                $url     Request URL.
 * @return false|array|\WP_Error
 */
function block_malicious_request( $preempt, $args, $url ) {
	$host = wp_parse_url( $url, PHP_URL_HOST );

	if ( $host && host_matches( $host, blocked_hosts() ) ) {
		flag( 'c2_contact_seen', true );

		return new \WP_Error(
			'http_request_blocked',
			__( 'Request blocked: known malicious host.', TEXT_DOMAIN )
		);
	}

	return $preempt;
}

/**
 * Empties a notification-feed response that carries the payload, so nothing is
 * cached or rendered even if a compromised feed is still being served.
 *
 * @param array  $response Raw HTTP response.
 * @param array  $args     Request arguments.
 * @param string $url      Request URL.
 * @return array
 */
function scrub_feed_response( $response, $args, $url ) {
	$host = wp_parse_url( $url, PHP_URL_HOST );

	if ( ! $host || ! host_matches( $host, feed_hosts() ) ) {
		return $response;
	}

	$body = wp_remote_retrieve_body( $response );

	if ( is_string( $body ) && '' !== $body && contains_signature( $body ) ) {
		flag( 'feed_payload_seen', true );
		$response['body'] = '{}';
	}

	return $response;
}

/* -------------------------------------------------------------------------
 * DETECTION
 * ---------------------------------------------------------------------- */

/**
 * Runs the scan at most twice a day, and always after a module version bump.
 *
 * @return void
 */
function maybe_scan() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	$report = get_report();

	$fresh = ! empty( $report['scanned_at'] )
		&& isset( $report['module_version'] )
		&& MODULE_VERSION === $report['module_version']
		&& ( time() - (int) $report['scanned_at'] ) < 12 * HOUR_IN_SECONDS;

	if ( $fresh ) {
		return;
	}

	$report = scan();

	/**
	 * Opt in to unattended cleanup. Off by default: removing users and plugin
	 * directories without a human present is not something to do silently.
	 *
	 * @param bool  $auto_clean Whether to clean without confirmation.
	 * @param array $report     Current scan report.
	 */
	if ( has_confirmed( $report ) && apply_filters( KEY_PREFIX . '_auto_clean', false, $report ) ) {
		clean();
	}
}

/**
 * Looks for every known artefact and stores the result.
 *
 * @return array
 */
function scan() {
	$previous = get_report();

	$report = array(
		'module_version'    => MODULE_VERSION,
		'scanned_at'        => time(),
		'cleaned_at'        => isset( $previous['cleaned_at'] ) ? $previous['cleaned_at'] : 0,
		'c2_contact_seen'   => ! empty( $previous['c2_contact_seen'] ),
		'feed_payload_seen' => ! empty( $previous['feed_payload_seen'] ),
		'users'             => find_rogue_users(),
		'plugins'           => find_backdoor_plugins(),
		'mu_files'          => find_backdoor_mu_files(),
		'options'           => find_infected_options(),
		'log'               => isset( $previous['log'] ) ? $previous['log'] : array(),
	);

	update_option( KEY_PREFIX . '_report', $report, false );

	return $report;
}

/**
 * Accounts left behind by the payload. Role is not part of the test — a
 * demoted leftover still counts. Results carry a `confirmed` flag: only
 * confirmed entries are ever removed, suspects are reported for a human to
 * judge.
 *
 * @return array[]
 */
function find_rogue_users() {
	$found = array();

	if ( is_exempt_site() ) {
		return $found;
	}

	$candidates = array();

	// Known login/email pairs, looked up both ways.
	foreach ( rogue_accounts() as $account ) {
		foreach ( array( 'login' => $account['login'], 'email' => $account['email'] ) as $field => $value ) {
			$user = get_user_by( $field, $value );

			if ( $user ) {
				$candidates[ (int) $user->ID ] = $user;
			}
		}
	}

	// Anything on an indicator domain, including variants not yet observed.
	foreach ( array_merge( rogue_email_domains(), suspect_email_domains() ) as $domain ) {
		$users = get_users(
			array(
				'search'         => '*@' . $domain,
				'search_columns' => array( 'user_email' ),
				'number'         => 50,
			)
		);

		foreach ( $users as $user ) {
			$candidates[ (int) $user->ID ] = $user;
		}
	}

	foreach ( $candidates as $user ) {
		$verdict = classify_user( $user );

		if ( ! $verdict ) {
			continue;
		}

		$found[] = array(
			'id'         => (int) $user->ID,
			'login'      => $user->user_login,
			'email'      => $user->user_email,
			'registered' => $user->user_registered,
			'confirmed'  => $verdict['confirmed'],
			'reason'     => $verdict['reason'],
		);
	}

	return $found;
}

/**
 * Decides whether an account is a confirmed artefact, a suspect, or neither.
 *
 * @param \WP_User $user User to classify.
 * @return array|null
 */
function classify_user( $user ) {
	$login = strtolower( $user->user_login );
	$email = strtolower( $user->user_email );

	$indicator_domains = array_merge( rogue_email_domains(), suspect_email_domains() );

	foreach ( rogue_accounts() as $account ) {
		$account_email = strtolower( $account['email'] );
		$account_login = strtolower( $account['login'] );

		// The exact pair, or the exact address on its own, is conclusive.
		if ( $email === $account_email ) {
			return array(
				'confirmed' => true,
				'reason'    => __( 'matches an account created by this payload', TEXT_DOMAIN ),
			);
		}

		// A matching login only counts when the address is on an indicator
		// domain too — otherwise an ordinary "service" account gets swept up.
		if ( $login === $account_login && email_domain_matches( $email, $indicator_domains ) ) {
			return array(
				'confirmed' => true,
				'reason'    => __( 'matches an account created by this payload', TEXT_DOMAIN ),
			);
		}
	}

	if ( email_domain_matches( $email, rogue_email_domains() ) ) {
		return array(
			'confirmed' => true,
			'reason'    => __( 'address on a domain used only by this payload', TEXT_DOMAIN ),
		);
	}

	if ( email_domain_matches( $email, suspect_email_domains() ) ) {
		return array(
			'confirmed' => false,
			'reason'    => __( 'unusual address for this site — verify before removing', TEXT_DOMAIN ),
		);
	}

	return null;
}

/**
 * Whether an email address sits on one of the given domains, or a subdomain
 * of one.
 *
 * @param string   $email   Email address.
 * @param string[] $domains Domains to match against.
 * @return bool
 */
function email_domain_matches( $email, array $domains ) {
	$at = strrpos( (string) $email, '@' );

	if ( false === $at ) {
		return false;
	}

	return host_matches( substr( $email, $at + 1 ), $domains );
}

/**
 * Plugin directories carrying the backdoor marker file. Directories matching a
 * known slug but WITHOUT the marker are reported as suspects only, never
 * deleted automatically — slug collisions with legitimate plugins are possible.
 *
 * @return array[]
 */
function find_backdoor_plugins() {
	$found = array();
	$dirs  = glob( WP_PLUGIN_DIR . '/*', GLOB_ONLYDIR );

	if ( ! is_array( $dirs ) ) {
		return $found;
	}

	$slugs = array_map( 'strtolower', known_backdoor_slugs() );

	foreach ( $dirs as $dir ) {
		$slug     = basename( $dir );
		$has_file = file_exists( trailingslashit( $dir ) . BACKDOOR_FILE );
		$is_known = in_array( strtolower( $slug ), $slugs, true );

		if ( ! $has_file && ! $is_known ) {
			continue;
		}

		$found[] = array(
			'slug'      => $slug,
			'confirmed' => $has_file, // Only confirmed entries are auto-removed.
		);
	}

	return $found;
}

/**
 * The marker file appearing in mu-plugins, which the known chain does not do
 * but a variant might.
 *
 * @return string[]
 */
function find_backdoor_mu_files() {
	if ( ! defined( 'WPMU_PLUGIN_DIR' ) || ! is_dir( WPMU_PLUGIN_DIR ) ) {
		return array();
	}

	$found = array();
	$files = glob( WPMU_PLUGIN_DIR . '/*.php' );

	if ( ! is_array( $files ) ) {
		return $found;
	}

	foreach ( $files as $file ) {
		if ( BACKDOOR_FILE === basename( $file ) ) {
			$found[] = $file;
		}
	}

	return $found;
}

/**
 * Options and transients holding a cached copy of the payload.
 *
 * @return string[]
 */
function find_infected_options() {
	global $wpdb;

	$signatures = payload_signatures();

	if ( empty( $signatures ) ) {
		return array();
	}

	$where  = array();
	$params = array();

	foreach ( $signatures as $signature ) {
		$where[]  = 'option_value LIKE %s';
		$params[] = '%' . $wpdb->esc_like( $signature ) . '%';
	}

	// Exclude our own report — it legitimately stores indicator strings.
	$params[] = KEY_PREFIX . '_report';

	$sql = "SELECT option_name FROM {$wpdb->options} WHERE ( "
		. implode( ' OR ', $where )
		. ' ) AND option_name != %s LIMIT 200';

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- placeholders built above, values passed to prepare().
	$names = $wpdb->get_col( $wpdb->prepare( $sql, $params ) );

	return is_array( $names ) ? $names : array();
}

/* -------------------------------------------------------------------------
 * CLEANUP
 * ---------------------------------------------------------------------- */

/**
 * Removes every confirmed artefact. Returns a log of what happened, which is
 * also persisted into the report.
 *
 * @return array
 */
function clean() {
	$report = scan();
	$log    = array();

	$log = array_merge( $log, clean_options( $report['options'] ) );
	$log = array_merge( $log, clean_users( $report['users'] ) );
	$log = array_merge( $log, clean_plugins( $report['plugins'] ) );
	$log = array_merge( $log, clean_mu_files( $report['mu_files'] ) );

	$report               = scan();
	$report['cleaned_at'] = time();
	$report['log']        = $log;

	update_option( KEY_PREFIX . '_report', $report, false );

	/**
	 * Fires after a cleanup pass, for vendor telemetry or logging.
	 *
	 * @param array $log    Per-item results.
	 * @param array $report Post-cleanup report.
	 */
	do_action( KEY_PREFIX . '_cleaned', $log, $report );

	return $log;
}

/**
 * Deletes cached payload options, handling the transient timeout twins.
 *
 * @param string[] $names Option names.
 * @return array
 */
function clean_options( $names ) {
	$log = array();

	foreach ( (array) $names as $name ) {
		if ( 0 === strpos( $name, '_transient_' ) ) {
			$key = substr( $name, strlen( '_transient_' ) );
			delete_transient( $key );
		} elseif ( 0 === strpos( $name, '_site_transient_' ) ) {
			$key = substr( $name, strlen( '_site_transient_' ) );
			delete_site_transient( $key );
		}

		delete_option( $name );

		$log[] = array(
			'item'   => 'option: ' . $name,
			'result' => get_option( $name, null ) === null ? 'removed' : 'failed',
		);
	}

	return $log;
}

/**
 * Deletes rogue accounts, reassigning any content to the acting user.
 *
 * @param array[] $users Rogue user records.
 * @return array
 */
function clean_users( $users ) {
	$log = array();

	if ( empty( $users ) ) {
		return $log;
	}

	if ( ! current_user_can( 'delete_users' ) ) {
		return array(
			array(
				'item'   => 'users',
				'result' => 'skipped: insufficient capability',
			),
		);
	}

	require_once ABSPATH . 'wp-admin/includes/user.php';

	$reassign = get_current_user_id();

	foreach ( $users as $user ) {
		$id = (int) $user['id'];

		if ( empty( $user['confirmed'] ) ) {
			$log[] = array(
				'item'   => 'user: ' . $user['login'],
				'result' => 'left in place: reported for review, not removed automatically',
			);
			continue;
		}

		if ( $id === $reassign ) {
			$log[] = array(
				'item'   => 'user: ' . $user['login'],
				'result' => 'skipped: currently logged in as this account',
			);
			continue;
		}

		// Kill live sessions before removing the account.
		$sessions = \WP_Session_Tokens::get_instance( $id );
		if ( $sessions ) {
			$sessions->destroy_all();
		}

		if ( is_multisite() ) {
			require_once ABSPATH . 'wp-admin/includes/ms.php';
			$deleted = wpmu_delete_user( $id );
		} else {
			$deleted = wp_delete_user( $id, $reassign );
		}

		$log[] = array(
			'item'   => 'user: ' . $user['login'],
			'result' => $deleted ? 'removed' : 'failed',
		);
	}

	return $log;
}

/**
 * Deactivates and deletes confirmed backdoor plugin directories.
 *
 * @param array[] $plugins Plugin records from the scan.
 * @return array
 */
function clean_plugins( $plugins ) {
	$log = array();

	if ( empty( $plugins ) ) {
		return $log;
	}

	if ( ! current_user_can( 'delete_plugins' ) ) {
		return array(
			array(
				'item'   => 'plugins',
				'result' => 'skipped: insufficient capability',
			),
		);
	}

	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';

	if ( ! \WP_Filesystem() ) {
		return array(
			array(
				'item'   => 'plugins',
				'result' => 'skipped: direct filesystem access unavailable, remove manually',
			),
		);
	}

	$installed = get_plugins();

	foreach ( $plugins as $plugin ) {
		$slug = $plugin['slug'];

		if ( empty( $plugin['confirmed'] ) ) {
			$log[] = array(
				'item'   => 'plugin: ' . $slug,
				'result' => 'left in place: slug matches but marker file absent, review manually',
			);
			continue;
		}

		$files = array();

		foreach ( array_keys( $installed ) as $file ) {
			if ( dirname( $file ) === $slug ) {
				$files[] = $file;
			}
		}

		if ( $files ) {
			deactivate_plugins( $files, true );
			$result = delete_plugins( $files );
			$ok     = ( true === $result );
		} else {
			// No readable plugin header — remove the directory directly.
			global $wp_filesystem;
			$ok = $wp_filesystem->delete( trailingslashit( WP_PLUGIN_DIR ) . $slug, true );
		}

		$log[] = array(
			'item'   => 'plugin: ' . $slug,
			'result' => $ok ? 'removed' : 'failed, remove manually',
		);
	}

	return $log;
}

/**
 * Removes marker files found in mu-plugins.
 *
 * @param string[] $files Absolute paths.
 * @return array
 */
function clean_mu_files( $files ) {
	$log = array();

	if ( empty( $files ) ) {
		return $log;
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';

	if ( ! \WP_Filesystem() ) {
		return array(
			array(
				'item'   => 'mu-plugins',
				'result' => 'skipped: direct filesystem access unavailable, remove manually',
			),
		);
	}

	global $wp_filesystem;

	foreach ( $files as $file ) {
		$log[] = array(
			'item'   => 'mu-plugin file: ' . basename( $file ),
			'result' => $wp_filesystem->delete( $file ) ? 'removed' : 'failed, remove manually',
		);
	}

	return $log;
}

/* -------------------------------------------------------------------------
 * ADMIN NOTICE
 * ---------------------------------------------------------------------- */

/**
 * Renders the notice: an actionable warning when artefacts are present, a
 * short informational note otherwise.
 *
 * @return void
 */
function render_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	if ( get_user_meta( get_current_user_id(), KEY_PREFIX . '_dismissed', true ) === MODULE_VERSION ) {
		return;
	}

	$report    = get_report();
	$confirmed = has_confirmed( $report );
	$suspects  = has_suspects( $report );
	$class     = $confirmed ? 'notice-error' : ( $suspects ? 'notice-warning' : 'notice-info' );

	echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible" data-' . esc_attr( KEY_PREFIX ) . '-notice="1">';

	printf(
		'<p><strong>%s</strong></p>',
		sprintf(
		/* translators: %s: plugin name. */
			esc_html__( '%s — security update', TEXT_DOMAIN ),
			esc_html( PLUGIN_LABEL )
		)
	);

	echo '<p>' . esc_html__(
			'A malicious payload was distributed through the remote notification feed used by this plugin. It ran in the browser of logged-in administrators and could create an administrator account and install a backdoor plugin. This update disables the notification feed and blocks the associated host.',
			TEXT_DOMAIN
		) . '</p>';

	if ( $confirmed ) {
		echo '<p>' . esc_html__( 'This site shows signs of having been affected:', TEXT_DOMAIN ) . '</p><ul style="list-style:disc;margin-left:2em;">';

		foreach ( filter_items( $report['users'], true ) as $user ) {
			printf(
				'<li>%s</li>',
				sprintf(
				/* translators: %s: user login. */
					esc_html__( 'Unexpected user account: %s', TEXT_DOMAIN ),
					'<code>' . esc_html( $user['login'] ) . '</code>'
				)
			);
		}

		foreach ( filter_items( $report['plugins'], true ) as $plugin ) {
			printf(
				'<li>%s</li>',
				sprintf(
				/* translators: %s: plugin slug. */
					esc_html__( 'Unexpected plugin directory: %s', TEXT_DOMAIN ),
					'<code>' . esc_html( $plugin['slug'] ) . '</code>'
				)
			);
		}

		foreach ( $report['mu_files'] as $file ) {
			printf(
				'<li>%s</li>',
				sprintf(
				/* translators: %s: file name. */
					esc_html__( 'Unexpected must-use plugin file: %s', TEXT_DOMAIN ),
					'<code>' . esc_html( basename( $file ) ) . '</code>'
				)
			);
		}

		if ( ! empty( $report['options'] ) ) {
			printf(
				'<li>%s</li>',
				sprintf(
				/* translators: %d: number of database entries. */
					esc_html( _n( '%d stored database entry containing the payload', '%d stored database entries containing the payload', count( $report['options'] ), TEXT_DOMAIN ) ),
					count( $report['options'] )
				)
			);
		}

		echo '</ul>';

		echo '<p>' . esc_html__(
				'Cleaning removes those items. Afterwards, change the passwords of all administrator accounts and rotate the security keys in wp-config.php — the attacker may hold valid credentials.',
				TEXT_DOMAIN
			) . '</p>';

		printf(
			'<p><a href="%s" class="button button-primary">%s</a></p>',
			esc_url( clean_url() ),
			esc_html__( 'Clean up this site', TEXT_DOMAIN )
		);
	} elseif ( ! $suspects ) {
		echo '<p>' . esc_html__( 'No signs of compromise were found on this site.', TEXT_DOMAIN ) . '</p>';
	}

	if ( $suspects ) {
		echo '<p>' . esc_html__(
				'The following items resemble the ones used by this payload but are not proof of anything on their own. They are listed for review and are never removed automatically — check with whoever administers this site before touching them.',
				TEXT_DOMAIN
			) . '</p><ul style="list-style:disc;margin-left:2em;">';

		foreach ( filter_items( $report['users'], false ) as $user ) {
			printf(
				'<li>%s — %s</li>',
				sprintf(
				/* translators: 1: user login, 2: email address. */
					esc_html__( 'User account %1$s (%2$s)', TEXT_DOMAIN ),
					'<code>' . esc_html( $user['login'] ) . '</code>',
					'<code>' . esc_html( $user['email'] ) . '</code>'
				),
				esc_html( isset( $user['reason'] ) ? $user['reason'] : '' )
			);
		}

		foreach ( filter_items( $report['plugins'], false ) as $plugin ) {
			printf(
				'<li>%s</li>',
				sprintf(
				/* translators: %s: plugin slug. */
					esc_html__( 'Plugin directory %s — name matches, but the backdoor marker file is absent', TEXT_DOMAIN ),
					'<code>' . esc_html( $plugin['slug'] ) . '</code>'
				)
			);
		}

		echo '</ul>';
	}

	if ( ! empty( $report['log'] ) ) {
		echo '<p><strong>' . esc_html__( 'Last cleanup result:', TEXT_DOMAIN ) . '</strong></p><ul style="list-style:disc;margin-left:2em;">';

		foreach ( $report['log'] as $entry ) {
			printf(
				'<li><code>%s</code> — %s</li>',
				esc_html( $entry['item'] ),
				esc_html( $entry['result'] )
			);
		}

		echo '</ul>';
	}

	if ( '' !== ADVISORY_URL ) {
		printf(
			'<p><a href="%s" target="_blank" rel="noopener noreferrer">%s</a></p>',
			esc_url( ADVISORY_URL ),
			esc_html__( 'Read the full advisory', TEXT_DOMAIN )
		);
	}

	echo '</div>';

	print_dismiss_script();
}

/**
 * Persists dismissal when the core dismiss button is clicked.
 *
 * @return void
 */
function print_dismiss_script() {
	$handle = KEY_PREFIX;
	$action = KEY_PREFIX . '_dismiss';
	$nonce  = wp_create_nonce( $action );
	$ajax   = admin_url( 'admin-ajax.php' );

	?>
	<script>
		( function() {
			var notice = document.querySelector( '[data-<?php echo esc_js( $handle ); ?>-notice]' );
			if ( ! notice ) { return; }
			notice.addEventListener( 'click', function( event ) {
				if ( ! event.target.classList.contains( 'notice-dismiss' ) ) { return; }
				var body = new FormData();
				body.append( 'action', '<?php echo esc_js( $action ); ?>' );
				body.append( '_wpnonce', '<?php echo esc_js( $nonce ); ?>' );
				window.fetch( '<?php echo esc_js( $ajax ); ?>', {
					method: 'POST',
					credentials: 'same-origin',
					body: body
				} );
			} );
		}() );
	</script>
	<?php
}

/**
 * Stores the dismissal against the current module version.
 *
 * @return void
 */
function handle_dismiss() {
	check_ajax_referer( KEY_PREFIX . '_dismiss' );

	if ( ! current_user_can( 'activate_plugins' ) ) {
		wp_send_json_error( null, 403 );
	}

	update_user_meta( get_current_user_id(), KEY_PREFIX . '_dismissed', MODULE_VERSION );

	wp_send_json_success();
}

/**
 * Handles the cleanup button.
 *
 * @return void
 */
function handle_clean() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', TEXT_DOMAIN ), 403 );
	}

	check_admin_referer( KEY_PREFIX . '_clean' );

	clean();

	// Show the notice again so the operator sees the result.
	delete_user_meta( get_current_user_id(), KEY_PREFIX . '_dismissed' );

	$redirect = wp_get_referer();

	if ( ! $redirect ) {
		$redirect = admin_url();
	}

	wp_safe_redirect( add_query_arg( KEY_PREFIX . '_cleaned', '1', $redirect ) );
	exit;
}

/* -------------------------------------------------------------------------
 * HELPERS
 * ---------------------------------------------------------------------- */

/**
 * The nonce-protected cleanup URL.
 *
 * @return string
 */
function clean_url() {
	return wp_nonce_url(
		add_query_arg( 'action', KEY_PREFIX . '_clean', admin_url( 'admin-post.php' ) ),
		KEY_PREFIX . '_clean'
	);
}

/**
 * The stored report, with defaults filled in.
 *
 * @return array
 */
function get_report() {
	$defaults = array(
		'module_version'    => '',
		'scanned_at'        => 0,
		'cleaned_at'        => 0,
		'c2_contact_seen'   => false,
		'feed_payload_seen' => false,
		'users'             => array(),
		'plugins'           => array(),
		'mu_files'          => array(),
		'options'           => array(),
		'log'               => array(),
	);

	$report = get_option( KEY_PREFIX . '_report', array() );

	return is_array( $report ) ? array_merge( $defaults, $report ) : $defaults;
}

/**
 * Whether the report contains artefacts that can be removed automatically.
 *
 * @param array $report Report array.
 * @return bool
 */
function has_confirmed( $report ) {
	if ( ! empty( $report['mu_files'] ) || ! empty( $report['options'] ) ) {
		return true;
	}

	foreach ( array( 'users', 'plugins' ) as $group ) {
		foreach ( (array) $report[ $group ] as $item ) {
			if ( ! empty( $item['confirmed'] ) ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Whether the report contains anything flagged for human review only.
 *
 * @param array $report Report array.
 * @return bool
 */
function has_suspects( $report ) {
	foreach ( array( 'users', 'plugins' ) as $group ) {
		foreach ( (array) $report[ $group ] as $item ) {
			if ( empty( $item['confirmed'] ) ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Items in a group carrying (or lacking) the confirmed flag.
 *
 * @param array $items     Group of scan items.
 * @param bool  $confirmed Which side to return.
 * @return array[]
 */
function filter_items( $items, $confirmed ) {
	$out = array();

	foreach ( (array) $items as $item ) {
		if ( ! empty( $item['confirmed'] ) === (bool) $confirmed ) {
			$out[] = $item;
		}
	}

	return $out;
}

/**
 * Sets a boolean flag on the report without a full rescan.
 *
 * @param string $key   Flag name.
 * @param mixed  $value Flag value.
 * @return void
 */
function flag( $key, $value ) {
	$report = get_report();

	if ( isset( $report[ $key ] ) && $report[ $key ] === $value ) {
		return; // Avoid a write on every blocked request.
	}

	$report[ $key ] = $value;

	update_option( KEY_PREFIX . '_report', $report, false );
}

/**
 * Exact host match, or a subdomain of one of the needles.
 *
 * @param string   $host    Host to test.
 * @param string[] $needles Hosts to match against.
 * @return bool
 */
function host_matches( $host, array $needles ) {
	$host = strtolower( (string) $host );

	foreach ( $needles as $needle ) {
		$needle = strtolower( (string) $needle );

		if ( '' === $needle ) {
			continue;
		}

		if ( $host === $needle ) {
			return true;
		}

		$suffix = '.' . $needle;

		if ( substr( $host, - strlen( $suffix ) ) === $suffix ) {
			return true;
		}
	}

	return false;
}

/**
 * Whether a string carries any payload signature.
 *
 * @param string $haystack String to test.
 * @return bool
 */
function contains_signature( $haystack ) {
	foreach ( payload_signatures() as $signature ) {
		if ( false !== stripos( $haystack, $signature ) ) {
			return true;
		}
	}

	return false;
}
