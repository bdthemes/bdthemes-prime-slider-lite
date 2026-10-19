<?php

namespace PrimeSlider\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load the Remote Data Handler
require_once __DIR__ . '/class-remote-data-handler.php';

use PrimeSlider\Admin\ModuleService;
use Elementor\Plugin;

/*
 * Quiet_Upgrader_Skin extends a wp-admin core class, so it is loaded lazily
 * from install_plugins() instead of here. Requiring core's upgrader at file
 * scope would pull wp-admin/includes/class-wp-upgrader.php into every
 * front-end request as well.
 */


class Setup_Wizard {

	// Singleton instance
	private static $instance = null;

	/**
	 * Whether this request renders the wizard. Set on admin_init, read when
	 * the assets are enqueued.
	 *
	 * @var bool
	 */
	private $is_wizard_request = false;

	// Constructor
	private function __construct() {
		if ( current_user_can( 'manage_options' ) ) {
			$this->init_hooks();
		}
	}

	// Get instance
	public static function get_instance() {
		if ( self::$instance == null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Newsletter list endpoint the welcome step's opt-in posts to.
	 */
	const SUBSCRIBE_ENDPOINT = 'https://marketing.sigmative.com/newsletter/rui/lists/6a9943aacbe70/embedded-form-subscribe';

	/**
	 * Customer identifier required by the newsletter endpoint.
	 */
	const SUBSCRIBE_CUSTOMER_UID = '6a93d39ce0ebd';

	// Initialize hooks
	private function init_hooks() {
		add_action( 'wp_ajax_bdtps_setup_wizard_install_plugins', array( $this, 'install_plugins' ) );
		add_action( 'wp_ajax_bdtps_setup_wizard_subscribe', array( $this, 'ajax_subscribe' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'dequeue_sibling_wizards' ), 100 );
		add_action( 'admin_init', array( $this, 'activate_default_widgets' ) );
		add_action( 'admin_init', array( $this, 'maybe_display_setup_wizard' ) );
		add_action( 'admin_init', array( $this, 'check_manual_wizard_request' ) );

		// NOTE: WordPress manages plugin/translation updates. Do not add filters
		// that interfere with the built-in update pipeline (wp.org Guideline).
	}

	// Check for manual wizard requests
	public function check_manual_wizard_request() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check of a GET flag to decide whether to render the setup wizard screen, no form data processed.
		$is_setup_wizard_request = isset($_GET['ps_setup_wizard']) && 'show' === sanitize_text_field(wp_unslash($_GET['ps_setup_wizard']));
		
		if ( $is_setup_wizard_request ) {
			$this->is_wizard_request = true;

			// Use the same approach as first activation - completely override the page
			add_action('admin_head', function() {
				?>
				<style>
					html, body {
						height: 100%;
						margin: 0;
						padding: 0;
						overflow: hidden;
					}
					#wpwrap, #wpcontent, #wpbody, #wpbody-content {
						height: 100%;
						padding: 0;
						margin: 0;
					}
					#adminmenumain, #wpadminbar {
						display: none;
					}
				</style>
				<script>
					jQuery(document).ready(function($) {
						$('body').addClass('bdt-setup-wizard-active');
					});
				</script>
				<?php
				
				// Display setup wizard using the same method as first activation
				$this->display_page();
			});
		}
	}

	// Display wizard in fullscreen mode
	public function display_wizard_fullscreen() {
		?>
		<style>
			html, body {
				height: 100%;
				margin: 0;
				padding: 0;
				overflow: hidden;
			}
			#wpwrap, #wpcontent, #wpbody, #wpbody-content {
				height: 100%;
				padding: 0;
				margin: 0;
			}
			#adminmenumain, #wpadminbar {
				display: none;
			}
		</style>
		<?php
		// Directly output the wizard content
		add_action('admin_footer', function() {
			echo '<div id="ps-setup-wizard-container">';
			$this->display_page();
			echo '</div>';
			?>
			<script>
				jQuery(document).ready(function($) {
					$('body').addClass('bdt-setup-wizard-active');
					// Hide all other content and show only our wizard
					$('#wpbody-content').html($('#ps-setup-wizard-container').html());
					$('#ps-setup-wizard-container').remove();
				});
			</script>
			<?php
		}, 999);
	}

	// Get wizard HTML content
	public function get_wizard_html() {
		ob_start();
		?>
		<div class="bdt-setup-wizard-overlay ps-setup-wizard ">
			<div class="bdt-setup-wizard content-loaded">
				<?php
				require_once plugin_dir_path( BDTPS_CORE__FILE__ ) . 'includes/setup-wizard/views/render.php';
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	// Check if this is first activation and display setup wizard if needed
	public function maybe_display_setup_wizard() {
		// admin_init also runs for admin-ajax.php (the Heartbeat, for one). The
		// wizard can only be shown on a page load, so an AJAX request must not
		// use up the first-run flag.
		if ( wp_doing_ajax() ) {
			return;
		}

		// Only check for first activation here
		if ( get_option( 'bdtps_setup_wizard_completed' ) === false ) {
			// Set the flag so it doesn't run again
			update_option( 'bdtps_setup_wizard_completed', true );

			$this->is_wizard_request = true;
			
			// Add a header to ensure proper full-page display
			add_action('admin_head', function() {
				?>
				<style>
					html, body {
						height: 100%;
						margin: 0;
						padding: 0;
						overflow: hidden;
					}
					#wpwrap, #wpcontent, #wpbody, #wpbody-content {
						height: 100%;
						padding: 0;
						margin: 0;
					}
					#adminmenumain, #wpadminbar {
						display: none;
					}
				</style>
				<script>
					jQuery(document).ready(function($) {
						$('body').addClass('bdt-setup-wizard-active');
					});
				</script>
				<?php
				
				// Display setup wizard
				$this->display_page();
			});
		}
	}

	// Keep the admin_menu method for reference but not hooked
	public function admin_menu() {
		add_submenu_page(
			'prime_slider_options',
			esc_html__( 'Setup Wizard', 'bdthemes-prime-slider-lite' ),
			esc_html__( 'Setup Wizard', 'bdthemes-prime-slider-lite' ),
			'manage_options',
			'prime-slider-setup-wizard',
			array( $this, 'display_page' )
		);
	}

	public function display_page() {
		?>
		<div class="bdt-setup-wizard-overlay ps-setup-wizard">
			<div class="bdt-setup-wizard content-loaded">
				<?php
				require_once plugin_dir_path( BDTPS_CORE__FILE__ ) . 'includes/setup-wizard/views/render.php';
				?>
			</div>
		</div>
		<?php
	}

	// Enqueue necessary scripts
	public function enqueue_scripts() {

		// Only the request that renders the wizard needs its assets. Loaded on
		// every admin screen, the script also attached itself to the other
		// BdThemes plugins' wizards, which share the .bdt-setup-wizard markup.
		if ( ! $this->is_wizard_request ) {
			return;
		}

		$direction_suffix = is_rtl() ? '.rtl' : '';

		wp_enqueue_style('bdt-uikit', BDTPS_CORE_ASSETS_URL . 'css/bdt-uikit' . $direction_suffix . '.css', [], '3.17.0');
		wp_enqueue_script('bdt-uikit', BDTPS_CORE_ASSETS_URL . 'js/bdt-uikit.min.js', ['jquery'], '3.17.0', true);

		wp_register_script( 'bdtps-setup-wizard', plugins_url( 'assets/js/setup-wizard.js', __FILE__ ), array( 'jquery' ), '1.0.0', true );
		
		wp_register_style( 'bdtps-setup-wizard', plugins_url( 'assets/css/setup-wizard.css', __FILE__ ), array(), '1.0.0' );

		wp_enqueue_script( 'bdtps-setup-wizard' );
		wp_enqueue_style( 'bdtps-setup-wizard' );

		wp_localize_script(
			'bdtps-setup-wizard',
			'BDT_SetupWizard',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'setup_wizard_nonce' ),
				'is_fullscreen' => true
			)
		);
	}

	/**
	 * Keep the other BdThemes plugins' setup wizards off this one.
	 *
	 * Those plugins enqueue their wizard script and stylesheet on every admin
	 * screen and select the same .bdt-setup-wizard markup, so on this page
	 * their handlers ran as well (Ultimate Post Kit's sent its own newsletter
	 * request from the welcome step). Only handles loaded from another
	 * plugin's includes/setup-wizard/ folder are removed.
	 */
	public function dequeue_sibling_wizards() {
		if ( ! $this->is_wizard_request ) {
			return;
		}

		foreach ( array( wp_scripts(), wp_styles() ) as $deps ) {
			foreach ( (array) $deps->queue as $handle ) {
				if ( 'bdtps-setup-wizard' === $handle || ! isset( $deps->registered[ $handle ] ) ) {
					continue;
				}

				$src = (string) $deps->registered[ $handle ]->src;

				if ( false !== strpos( $src, '/includes/setup-wizard/' ) && false === strpos( $src, '/' . dirname( BDTPS_CORE_PBNAME ) . '/' ) ) {
					$deps instanceof \WP_Scripts ? wp_dequeue_script( $handle ) : wp_dequeue_style( $handle );
				}
			}
		}
	}

	public static function get_widget_map() {
		$arr_obj = ModuleService::get_widget_settings(
			function ( $settings ) {
				$core_widgets = $settings['settings_fields']['prime_slider_active_modules'];
				return $core_widgets;
			}
		);
		return $arr_obj;
	}

	/**
	 * Handle the newsletter opt-in on the welcome step.
	 *
	 * Opt-in only: nothing is sent unless the administrator ticked the box,
	 * which is unticked by default. The choice is recorded either way so the
	 * wizard can show it again on a re-run. Runs server side so the
	 * cross-origin POST is not subject to CORS.
	 */
	public function ajax_subscribe() {
		check_ajax_referer( 'setup_wizard_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Unauthorized', 'bdthemes-prime-slider-lite' ) ) );
		}

		// Record the choice first, whichever way it went.
		$consent = isset( $_POST['consent'] ) && 'yes' === sanitize_text_field( wp_unslash( $_POST['consent'] ) );

		update_option( 'bdtps_subscribe_optin', $consent ? 'yes' : 'no' );

		if ( ! $consent ) {
			// No opt-in: the choice is stored and nothing leaves the site.
			wp_send_json_success(
				array(
					'subscribed' => false,
					'message'    => esc_html__( 'Preferences saved.', 'bdthemes-prime-slider-lite' ),
				)
			);
		}

		// Keep the raw value: sanitize_email() flattens anything malformed to an
		// empty string, which would otherwise be indistinguishable from "left blank".
		$raw_email = isset( $_POST['email'] ) ? sanitize_text_field( wp_unslash( $_POST['email'] ) ) : '';
		$email     = sanitize_email( $raw_email );

		if ( '' === trim( $raw_email ) ) {
			wp_send_json_success(
				array(
					'subscribed' => false,
					'message'    => esc_html__( 'Preferences saved.', 'bdthemes-prime-slider-lite' ),
				)
			);
		}

		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Please enter a valid email address.', 'bdthemes-prime-slider-lite' ) ) );
		}

		// Never subscribe the same address twice from this site.
		if ( get_option( 'bdtps_subscribed_email' ) === $email ) {
			wp_send_json_success(
				array(
					'subscribed' => true,
					'message'    => esc_html__( 'You are already subscribed.', 'bdthemes-prime-slider-lite' ),
				)
			);
		}

		$current_user = wp_get_current_user();

		$body = array(
			'customer_uid' => apply_filters( 'bdtps/setup_wizard/subscribe_customer_uid', self::SUBSCRIBE_CUSTOMER_UID ),
			'EMAIL'        => $email,
			'FIRST_NAME'   => $current_user ? $current_user->first_name : '',
			'LAST_NAME'    => $current_user ? $current_user->last_name : '',
		);

		$response = wp_safe_remote_post(
			apply_filters( 'bdtps/setup_wizard/subscribe_url', self::SUBSCRIBE_ENDPOINT ),
			array(
				'timeout'   => 15,
				'body'      => apply_filters( 'bdtps/setup_wizard/subscribe_body', $body, $email ),
				'headers'   => array( 'Accept' => '*/*' ),
				'sslverify' => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Could not reach the subscription service. Please try again later.', 'bdthemes-prime-slider-lite' ) ) );
		}

		$code = wp_remote_retrieve_response_code( $response );

		if ( $code < 200 || $code >= 400 ) {
			wp_send_json_error( array( 'message' => esc_html__( 'The subscription service rejected the request.', 'bdthemes-prime-slider-lite' ) ) );
		}

		update_option( 'bdtps_subscribed_email', $email );

		wp_send_json_success(
			array(
				'subscribed' => true,
				'message'    => esc_html__( 'Thanks for subscribing!', 'bdthemes-prime-slider-lite' ),
			)
		);
	}

	// Install plugins
	public function install_plugins() {
		check_ajax_referer( 'setup_wizard_nonce', 'nonce' );

		$plugin_slugs = isset( $_POST['plugins'] ) ? map_deep( wp_unslash( $_POST['plugins'] ), 'sanitize_text_field' ) : array();

		if ( empty( $plugin_slugs ) || ! is_array( $plugin_slugs ) ) {
			wp_send_json_error( array( 'message' => 'Invalid plugins array' ) );
		}

		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		// Core admin files are loaded here, inside the request handler, and each
		// is used immediately below. class-wp-upgrader.php also loads the
		// WP_Upgrader_Skin classes, so they are not required separately.
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once __DIR__ . '/class-quiet-upgrader-skin.php';

		$upgrader = new \Plugin_Upgrader( new Quiet_Upgrader_Skin() );
		$results  = array();

		foreach ( $plugin_slugs as $plugin_slug ) {
			// The wizard sends wordpress.org slugs ("live-copy-paste"); a main
			// file path ("live-copy-paste/live-copy-paste.php") is accepted too.
			$slug        = sanitize_key( strtok( (string) $plugin_slug, '/' ) );
			$plugin_file = $slug ? $this->get_plugin_file( $slug ) : false;

			if ( ! $slug ) {
				continue;
			}

			if ( $plugin_file && is_plugin_active( $plugin_file ) ) {
				$results[] = array(
					'slug'    => $slug,
					'success' => true,
					'message' => esc_html__( 'Already active.', 'bdthemes-prime-slider-lite' ),
				);
				continue;
			}

			// Download only what is not installed yet. Installing over an
			// existing folder fails with "Destination folder already exists".
			if ( ! $plugin_file ) {
				$api = plugins_api( 'plugin_information', array( 'slug' => $slug, 'fields' => array( 'sections' => false ) ) );

				if ( is_wp_error( $api ) ) {
					$results[] = array(
						'slug'    => $slug,
						'success' => false,
						'message' => $api->get_error_message(),
					);
					continue;
				}

				$installed = $upgrader->install( $api->download_link );

				if ( is_wp_error( $installed ) || ! $installed ) {
					$results[] = array(
						'slug'    => $slug,
						'success' => false,
						'message' => is_wp_error( $installed ) ? $installed->get_error_message() : esc_html__( 'Installation failed.', 'bdthemes-prime-slider-lite' ),
					);
					continue;
				}

				wp_clean_plugins_cache();
				$plugin_file = $upgrader->plugin_info() ? $upgrader->plugin_info() : $this->get_plugin_file( $slug );
			}

			if ( ! $plugin_file ) {
				$results[] = array(
					'slug'    => $slug,
					'success' => false,
					'message' => esc_html__( 'The plugin was installed, but its main file could not be found.', 'bdthemes-prime-slider-lite' ),
				);
				continue;
			}

			if ( ! current_user_can( 'activate_plugin', $plugin_file ) ) {
				$results[] = array(
					'slug'    => $slug,
					'success' => false,
					'message' => esc_html__( 'You are not allowed to activate this plugin.', 'bdthemes-prime-slider-lite' ),
				);
				continue;
			}

			$activated = activate_plugin( $plugin_file );

			$results[] = array(
				'slug'    => $slug,
				'success' => ! is_wp_error( $activated ),
				'message' => is_wp_error( $activated ) ? $activated->get_error_message() : esc_html__( 'Installed and activated successfully.', 'bdthemes-prime-slider-lite' ),
			);
		}

		// Discard anything an activated plugin printed into an open buffer, so
		// the response stays valid JSON. ob_clean() without a buffer raises a
		// notice, which is what the unconditional call here used to do.
		if ( ob_get_level() > 0 && ob_get_length() ) {
			ob_clean();
		}

		$failed = array_filter(
			$results,
			function ( $result ) {
				return empty( $result['success'] );
			}
		);

		if ( $failed ) {
			wp_send_json_error(
				array(
					'results' => $results,
					'message' => implode( ' ', wp_list_pluck( $failed, 'message' ) ),
				)
			);
		}

		wp_send_json_success( array( 'results' => $results ) );
	}

	/**
	 * Get the main plugin file path for a given slug.
	 *
	 * @param string $slug Plugin slug.
	 * @return string|false Plugin file path or false if not found.
	 */
	private function get_plugin_file( $slug ) {
		$plugins = get_plugins();

		foreach ( array_keys( $plugins ) as $file ) {
			// Match the folder exactly: a substring test would match
			// "spin-wheel-pro/…" for "spin-wheel".
			if ( dirname( $file ) === $slug ) {
				return $file;
			}
		}

		return false;
	}
    
    /**
     * Activate default widgets in setup wizard
     */
    public function activate_default_widgets() {
        // List of widgets to activate by default
        $default_active_widgets = array(
            'astoria',
            'avatar',
            'blog',
			'isolate',
			'general',
			'mount',
        );
        
        // Get current active modules
        $active_modules = get_option('prime_slider_active_modules', array());
        
        // Make sure $active_modules is an array
        if (!is_array($active_modules)) {
            $active_modules = array();
        }
        
        // Check if active_modules option exists and is not empty
        // If it's a new installation or option doesn't exist, we'll set our defaults
        $modified = false;
        
        foreach ($default_active_widgets as $widget) {
            // Only set if not already defined (prevents overriding user settings on existing installations)
            if (!isset($active_modules[$widget])) {
                $active_modules[$widget] = 'on';
                $modified = true;
            }
        }
        
        // Update the option if changes were made
        if ($modified) {
            update_option('prime_slider_active_modules', $active_modules);
        }
    }
}

// Initialize the Setup Wizard
Setup_Wizard::get_instance();

use Elementor\TemplateLibrary\Source_Local;

/**
 * Resolve a submitted template URL to a real file inside this plugin's own
 * bundled setup-wizard assets.
 *
 * The setup wizard only ever offers templates that ship with the plugin, so a
 * submitted URL that points anywhere else is rejected rather than fetched.
 *
 * @param string $url URL submitted with the import request.
 * @return string Absolute path to the bundled file, or '' when the URL is not one of ours.
 */
function bdtps_resolve_bundled_template( $url ) {

	$base_url  = BDTPS_CORE_URL . 'includes/setup-wizard/assets/';
	$base_path = BDTPS_CORE_INC_PATH . 'setup-wizard/assets/';

	if ( '' === $url || 0 !== strpos( $url, $base_url ) ) {
		return '';
	}

	$relative = substr( $url, strlen( $base_url ) );
	$relative = strtok( $relative, '?#' );
	$relative = rawurldecode( (string) $relative );

	if ( '' === $relative || false !== strpos( $relative, '..' ) ) {
		return '';
	}

	$root = realpath( $base_path );
	$file = realpath( $base_path . $relative );

	if ( false === $root || false === $file || 0 !== strpos( $file, $root ) || ! is_file( $file ) ) {
		return '';
	}

	return $file;
}

/**
 * Elementor import runners the setup wizard does not run for its bundled kits.
 *
 * @return string[]
 */
function bdtps_skipped_kit_runners() {
	return array( 'wp-content' );
}

	add_action('wp_ajax_bdtps_import_elementor_template', function () {
		check_ajax_referer( 'setup_wizard_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Unauthorized', 'bdthemes-prime-slider-lite' ) ) );
			wp_die();
		}

		$json_url = isset( $_POST['import_url'] ) ? esc_url_raw( wp_unslash( $_POST['import_url'] ) ) : '';

        // Only the templates bundled with this plugin can be imported, so the
        // file is read from disk instead of fetched over HTTP from an
        // arbitrary, request-supplied URL.
        $json_file = bdtps_resolve_bundled_template($json_url);

        if ('' === $json_file) {
            wp_send_json_error(['message' => esc_html__('Invalid import URL', 'bdthemes-prime-slider-lite')]);
            wp_die();
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading a template file that ships inside this plugin, resolved and path-checked above.
        $sourceData = file_get_contents($json_file);
        $sourceData2 = json_decode($sourceData, true);

        if (!$sourceData2 || !is_array($sourceData2)) {
            wp_send_json_error(['message' => esc_html__('Failed to fetch template from URL.', 'bdthemes-prime-slider-lite')]);
            wp_die();
        }

        // Elementor's uploads manager owns the temp file, so no direct
        // filesystem call is needed here.
        $temp_file = Plugin::$instance->uploads_manager->create_temp_file($sourceData, 'elementor_import.json');

        if (is_wp_error($temp_file)) {
            wp_send_json_error(['message' => esc_html__('Could not create a temporary file for the import.', 'bdthemes-prime-slider-lite')]);
            wp_die();
        }

        // Initialize Elementor's Template Importer
        if (!class_exists('\Elementor\TemplateLibrary\Source_Local')) {
            wp_delete_file($temp_file);
            wp_send_json_error(['message' => esc_html__('Elementor is not installed or activated!', 'bdthemes-prime-slider-lite')]);
            wp_die();
        }

        $manager = new Source_Local();
        $templateData = $manager->import_template('elementor_template', $temp_file);
        wp_delete_file($temp_file); // Delete temp file after import

        if (is_wp_error($templateData) || !is_array($templateData) || empty($templateData[0]['template_id'])) {
            wp_send_json_error(['message' => esc_html__('Failed to import template!', 'bdthemes-prime-slider-lite')]);
            wp_die();
        }

        $template_id = $templateData[0]['template_id'];
        $metaData = get_post_meta($template_id);

        $page_title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : esc_html__("No Title", 'bdthemes-prime-slider-lite');

        // Validate Elementor Data
        if (!isset($metaData['_elementor_data'][0])) {
            wp_send_json_error(['message' => esc_html__('Elementor data not found in template.', 'bdthemes-prime-slider-lite')]);
            wp_die();
        }

        $_elementor_data = wp_slash($metaData['_elementor_data'][0]);

        // Create New Page
        $new_post_id = wp_insert_post([
            'post_type'    => 'page',
            'post_status'  => empty($page_title) ? 'draft' : 'publish',
            'post_title'   => $page_title,
            'post_content' => '',
        ]);

        if (is_wp_error($new_post_id)) {
            wp_send_json_error(['message' => esc_html__('Failed to create page!', 'bdthemes-prime-slider-lite')]);
            wp_die();
        }

        // Assign Elementor Template Data
        update_post_meta($new_post_id, '_elementor_data', $_elementor_data);

        // Import Page Settings if available
        if (isset($metaData['_elementor_page_settings'][0])) {
            // Object injection is not possible here: allowed_classes is false, so
            // any object in the payload is discarded rather than instantiated.
            // The payload itself comes from a template file bundled with this
            // plugin, resolved by bdtps_resolve_bundled_template().
            $_elementor_page_settings = is_serialized($metaData['_elementor_page_settings'][0])
                // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize -- hardened with allowed_classes => false; input is a template bundled with this plugin.
                ? unserialize($metaData['_elementor_page_settings'][0], ['allowed_classes' => false])
                : $metaData['_elementor_page_settings'][0];
            update_post_meta($new_post_id, '_elementor_page_settings', $_elementor_page_settings);
        }

        update_post_meta($new_post_id, '_elementor_template_type', $sourceData2['type'] ?? '');
        update_post_meta($new_post_id, '_elementor_edit_mode', 'builder');
//        update_post_meta($new_post_id, '_wp_page_template', !empty($pageTemplate) ? $pageTemplate : 'elementor_header_footer');

        wp_send_json_success([
            'message'   => esc_html__('The template was imported successfully.', 'bdthemes-prime-slider-lite'),
            'ids'       => $new_post_id,
            'edit_link' => admin_url('post.php?post=' . $new_post_id . '&action=elementor'),
        ]);
	}
);


add_action('wp_ajax_bdtps_import_elementor_bundle_template', function () {
    check_ajax_referer('setup_wizard_nonce', 'nonce');

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => esc_html__( 'Unauthorized', 'bdthemes-prime-slider-lite' ) ) );
        wp_die();
    }

    $file_url = isset($_POST['import_url']) ? esc_url_raw(wp_unslash($_POST['import_url'])) : '';

    // Only the template bundles shipped inside this plugin can be imported, so
    // the archive is read from disk rather than fetched from a request-supplied
    // URL.
    $zip_file = bdtps_resolve_bundled_template($file_url);

    if ('' === $zip_file) {
        wp_send_json_error(['message' => esc_html__('Invalid import URL', 'bdthemes-prime-slider-lite')]);
    }

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading a template bundle that ships inside this plugin, resolved and path-checked above.
    $zip_contents = file_get_contents($zip_file);

    if (false === $zip_contents) {
        wp_send_json_error(['message' => esc_html__('Failed to read the bundled template.', 'bdthemes-prime-slider-lite')]);
    }

    $kit_zip_path = Plugin::$instance->uploads_manager->create_temp_file($zip_contents, 'kit.zip');

    if (is_wp_error($kit_zip_path)) {
        wp_send_json_error(['message' => esc_html__('Could not create a temporary file for the import.', 'bdthemes-prime-slider-lite')]);
    }

    $app = Plugin::$instance->app;
    if (!$app) {
        wp_send_json_error(['message' => esc_html__('Elementor app not available', 'bdthemes-prime-slider-lite')]);
    }

    $import_export_module = $app->get_component('import-export');

    try {
        $result = $import_export_module->upload_kit($kit_zip_path, 'local');
        $manifest = $result['manifest'] ?? [];
        $plugins = isset($manifest['plugins']) && is_array($manifest['plugins']) ? $manifest['plugins'] : [];

        $missingPlugins = [];
        foreach ($plugins as $plugin) {
            $pluginSlug = (isset($plugin['plugin']) ? $plugin['plugin'] : '') . ".php";
            if ('.php' === $pluginSlug || is_plugin_inactive($pluginSlug)) {
                $missingPlugins[] = $plugin;
            }
        }

        if (count($missingPlugins)) {
            wp_send_json_error([
                'plugins' => $missingPlugins,
                'message' => esc_html__('Missing plugins', 'bdthemes-prime-slider-lite'),
            ]);
        }

        $tmp_folder_id = $result['session'];
        $includes = [];
        $selectedCustomPostTypes = [];

        if (isset($manifest['templates'])) {
            $includes[] = 'templates';
        }

        if (isset($manifest['content'])) {
            $includes[] = 'content';
        }

        if (isset($manifest['site-settings'])) {
            $includes[] = 'settings';
        }

        if (isset($manifest['custom-post-type-title'])) {
            $selectedCustomPostTypes = array_keys($manifest['custom-post-type-title']);
        }

        $settings = [
            'id'                      => '',
            'session'                 => $tmp_folder_id,
            'include'                 => $includes,
            'overrideConditions'      => [],
            'selectedCustomPostTypes' => $selectedCustomPostTypes,
        ];

        $import = $import_export_module->import_kit($tmp_folder_id, $settings, true);

        // The bundled kits also carry three sample blog posts in wp-content/,
        // which none of the slider pages use. Skipping that runner imports the
        // slider page (elementor-content) without publishing unrelated posts.
        // Elementor's "Unfiltered file uploads" setting is left as the site
        // owner set it; nothing in these kits needs it.
        if (isset($import['runners']) && is_array($import['runners'])) {
            $import['runners'] = array_values(array_diff($import['runners'], bdtps_skipped_kit_runners()));
        }

        wp_send_json_success($import);
    } catch (\Throwable $e) {
        wp_send_json_error(['message' => esc_html__('Import failed: ', 'bdthemes-prime-slider-lite') . esc_html($e->getMessage())]);
    }
});

add_action('wp_ajax_bdtps_import_elementor_bundle_runner_template', function () {
    check_ajax_referer('setup_wizard_nonce', 'nonce');

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => esc_html__( 'Unauthorized', 'bdthemes-prime-slider-lite' ) ) );
        wp_die();
    }

    $runner = isset($_POST['runner']) ? sanitize_text_field(wp_unslash($_POST['runner'])) : '';
    $sessionId = isset($_POST['sessionId']) ? sanitize_text_field(wp_unslash($_POST['sessionId'])) : '';

    if (!$runner || !$sessionId) {
        wp_send_json_error(['message' => esc_html__('Required Param Is Missing.', 'bdthemes-prime-slider-lite')]);
    }

    if (in_array($runner, bdtps_skipped_kit_runners(), true)) {
        wp_send_json_success([]);
    }

    $app = Plugin::$instance->app;
    if (!$app) {
        wp_send_json_error(['message' => esc_html__('Elementor app not available.', 'bdthemes-prime-slider-lite')]);
    }

    try {
        // No execution-limit override here on purpose: Elementor runs one import
        // runner per request, so each request stays short. Hosts that need a
        // longer limit set it at the server level.
        $import_export_module = $app->get_component('import-export');
        $import = $import_export_module->import_kit_by_runner($sessionId, $runner);

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- hooking into Elementor's own action, not a plugin-defined hook.
        do_action('elementor/import-export/import-kit/runner/after-run', $import);
        wp_send_json_success($import);
    } catch (\Throwable $throwable) {
        wp_send_json_error(['message' => $throwable->getMessage()]);
    }
});
