<?php

use PrimeSlider\Utils;
use PrimeSlider\Admin\ModuleService;
use Elementor\Modules\Usage\Module;
use Elementor\Tracker;
/**
 * Prime Slider Admin Settings Class
 */

// Include rollback version functionality
require_once BDTPS_CORE_ADMIN_PATH . 'class-rollback-version.php';
class PrimeSlider_Admin_Settings {

	public static $modules_list = null;
	public static $modules_names = null;

	public static $modules_list_only_widgets = null;
	public static $modules_names_only_widgets = null;

	public static $modules_list_only_3rdparty = null;
	public static $modules_names_only_3rdparty = null;

	const PAGE_ID = 'prime_slider_options';

	private $settings_api;

	public $responseObj;
	public $showMessage = false;
	private $is_activated = false;

	/**
	 * Rollback version instance
	 * 
	 * @var PrimeSlider_Rollback_Version
	 */
	public $rollback_version;

	function __construct() {
		$this->settings_api = new PrimeSlider_Settings_API;

		if (!defined('BDTPS_CORE_HIDE')) {
			add_action('admin_init', [$this, 'admin_init']);
			add_action('admin_menu', [$this, 'admin_menu'], 201);
		}

		/**
		 * Mini-Cart issue fixed
		 * Check if MiniCart activate in EP and Elementor
		 * If both is activated then Show Notice
		 */

		$ps_3rdPartyOption = get_option('prime_slider_third_party_widget');

		$el_use_mini_cart = get_option('elementor_use_mini_cart_template');

		if ($el_use_mini_cart !== false && $ps_3rdPartyOption !== false) {
			if ($ps_3rdPartyOption) {
				if ('yes' == $el_use_mini_cart && isset($ps_3rdPartyOption['wc-mini-cart']) && 'off' !== trim($ps_3rdPartyOption['wc-mini-cart'])) {
					add_action('admin_notices', [$this, 'el_use_mini_cart'], 10, 3);
				}
			}
		}

		// Handle white label access link
		$this->handle_white_label_access();

		// Add custom CSS/JS functionality
		$this->init_custom_code_functionality();

		// White label settings (admin only)
		add_action( 'wp_ajax_ps_save_white_label', [ $this, 'save_white_label_ajax' ] );
		add_action( 'wp_ajax_ps_revoke_white_label_token', [ $this, 'revoke_white_label_token_ajax' ] );
		add_action( 'admin_head', [ $this, 'inject_white_label_icon_css' ] );

		// Plugin installation (admin only)
		add_action('wp_ajax_ps_install_plugin', [$this, 'install_plugin_ajax']);

        // Initialize rollback version functionality
		$this->rollback_version = new PrimeSlider\Admin\PrimeSlider_Rollback_Version();
	}

	/**
	 * Initialize Custom Code Functionality
	 * 
	 * @access public
	 * @return void
	 */
	public function init_custom_code_functionality() {
		// AJAX handler for saving custom code (admin only)
		add_action( 'wp_ajax_ps_save_custom_code', [ $this, 'save_custom_code_ajax' ] );
		
		
		// Admin scripts (admin only)
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_custom_code_scripts' ] );
		
		// Frontend injection is now handled by global functions in the main plugin file
		self::init_frontend_injection();
	}

	/**
	 * Initialize frontend injection hooks (works on both admin and frontend)
	 * 
	 * @access public static
	 * @return void
	 */
	public static function init_frontend_injection() {
		// Frontend hooks are now registered in the main plugin file
		// This method is kept for backwards compatibility but does nothing
	}

	/**
	 * Enqueue scripts for custom code editor
	 * 
	 * @access public
	 * @return void
	 */
	public function enqueue_custom_code_scripts( $hook ) {
		if ( $hook !== 'toplevel_page_prime_slider_options' ) {
			return;
		}

		// Enqueue WordPress built-in CodeMirror 
		wp_enqueue_code_editor( array( 'type' => 'text/css' ) );
		wp_enqueue_code_editor( array( 'type' => 'application/javascript' ) );
		
		// Enqueue WordPress media library scripts
		wp_enqueue_media();
		
		// Enqueue the admin script if it exists
		$admin_script_path = BDTPS_CORE_ASSETS_PATH . 'js/ps-admin.js';
		if ( file_exists( $admin_script_path ) ) {
			wp_enqueue_script( 
				'ps-admin-script', 
				BDTPS_CORE_ASSETS_URL . 'js/ps-admin.js', 
				[ 'jquery', 'media-upload', 'media-views', 'code-editor' ], 
				BDTPS_CORE_VER, 
				true 
			);
			
			// Localize script with AJAX data
			wp_localize_script( 'ps-admin-script', 'ps_admin_ajax', [
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'ps_custom_code_nonce' ),
				'white_label_nonce' => wp_create_nonce( 'ps_white_label_nonce' )
			] );
		} else {
			// Fallback: localize to jquery if the admin script doesn't exist
			wp_localize_script( 'jquery', 'ps_admin_ajax', [
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'ps_custom_code_nonce' ),
				'white_label_nonce' => wp_create_nonce( 'ps_white_label_nonce' )
			] );
		}
	}

	/**
	 * AJAX handler for saving white label settings
	 * 
	 * @access public
	 * @return void
	 */
	public function save_white_label_ajax() {
		
		// Check nonce and permissions
		if (!wp_verify_nonce($_POST['nonce'], 'ps_white_label_nonce')) {
			wp_send_json_error(['message' => __('Security check failed', 'bdthemes-prime-slider')]);
		}

		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => __('You do not have permission to manage white label settings', 'bdthemes-prime-slider')]);
		}

		// Check license eligibility
		if (!self::is_white_label_license()) {
			wp_send_json_error(['message' => __('Your license does not support white label features', 'bdthemes-prime-slider')]);
		}

		// Get white label settings
		$white_label_enabled = isset($_POST['ps_white_label_enabled']) ? (bool) $_POST['ps_white_label_enabled'] : false;
		$hide_license = isset($_POST['ps_white_label_hide_license']) ? (bool) $_POST['ps_white_label_hide_license'] : false;
		$bdtps_hide = isset($_POST['ps_white_label_bdtps_hide']) ? (bool) $_POST['ps_white_label_bdtps_hide'] : false;
		$white_label_title = isset($_POST['ps_white_label_title']) ? sanitize_text_field($_POST['ps_white_label_title']) : '';
		$white_label_icon = isset($_POST['ps_white_label_icon']) ? esc_url_raw($_POST['ps_white_label_icon']) : '';
		$white_label_icon_id = isset($_POST['ps_white_label_icon_id']) ? absint($_POST['ps_white_label_icon_id']) : 0;
		$white_label_logo = isset($_POST['ps_white_label_logo']) ? esc_url_raw($_POST['ps_white_label_logo']) : '';
		$white_label_logo_id = isset($_POST['ps_white_label_logo_id']) ? absint($_POST['ps_white_label_logo_id']) : 0;
		
		// Save settings
		update_option('ps_white_label_enabled', $white_label_enabled);
		update_option('ps_white_label_hide_license', $hide_license);
		update_option('ps_white_label_bdtps_hide', $bdtps_hide);
		update_option('ps_white_label_title', $white_label_title);
		update_option('ps_white_label_icon', $white_label_icon);
		update_option('ps_white_label_icon_id', $white_label_icon_id);
		update_option('ps_white_label_logo', $white_label_logo);
		update_option('ps_white_label_logo_id', $white_label_logo_id);

		// Set license title status
		if ($white_label_enabled) {
			update_option('prime_slider_license_title_status', true);
		} else {
			delete_option('prime_slider_license_title_status');
		}

		// Only send access email if both white label mode AND BDTPS_CORE_HIDE are enabled
		if ($white_label_enabled && $bdtps_hide) {
			$email_sent = $this->send_white_label_access_email();
		}

		wp_send_json_success([
			'message' => __('White label settings saved successfully', 'bdthemes-prime-slider'),
			'bdtps_hide' => $bdtps_hide,
			'email_sent' => isset($email_sent) ? $email_sent : false
		]);
	}

	/**
	 * Send white label access email with special link
	 * 
	 * @access private
	 * @return bool
	 */
	private function send_white_label_access_email() {
		
		$license_email = self::get_license_email();
		$admin_email = get_bloginfo( 'admin_email' );
		$license_key = self::get_license_key();
		$site_name = get_bloginfo( 'name' );
		$site_url = get_bloginfo( 'url' );
		
		// Generate secure access token with additional entropy
		$access_token = wp_hash( $license_key . time() . wp_salt() . wp_generate_password( 32, false ) );
		
		// Store access token in database with no expiration
		$token_data = [
			'token' => $access_token,
			'license_key' => $license_key,
			'created_at' => current_time( 'timestamp' ),
			'user_id' => get_current_user_id()
		];
		
		update_option( 'ps_white_label_access_token', $token_data );
		
		// Generate access URL using token instead of license key for security
		// Add white_label_tab=1 parameter to automatically switch to White Label tab
		$access_url = admin_url( 'admin.php?page=prime_slider_options&ps_wl=1&token=' . $access_token . '&white_label_tab=1#prime_slider_extra_options' );
		
		// Email subject
		$subject = sprintf( '[%s] Prime Slider White Label Access Instructions', $site_name );
		
		// Email message
		$message = $this->get_white_label_email_template( $site_name, $site_url, $access_url, $license_key );
		
		// Email headers
		$headers = [
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $site_name . ' <' . $admin_email . '>'
		];
		
		$email_sent = false;
		
		// Send to license email
		if ( ! empty( $license_email ) && is_email( $license_email ) ) {
			$email_sent = wp_mail( $license_email, $subject, $message, $headers );
			
			// If on localhost or email failed, save email content for manual access
			if ( ! $email_sent || $this->is_localhost() ) {
				$this->save_email_content_for_localhost( $access_url, $message, $license_email );
			}
		}
		
		return $email_sent;
	}

	/**
	 * Check if running on localhost
	 * 
	 * @access private
	 * @return bool
	 */
	private function is_localhost() {
		$server_name = $_SERVER['SERVER_NAME'] ?? '';
		$server_addr = $_SERVER['SERVER_ADDR'] ?? '';
		
		$localhost_indicators = [
			'localhost',
			'127.0.0.1',
			'::1',
			'.local',
			'.test',
			'.dev'
		];
		
		foreach ( $localhost_indicators as $indicator ) {
			if ( strpos( $server_name, $indicator ) !== false || 
				 strpos( $server_addr, $indicator ) !== false ) {
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Save email content for localhost testing
	 * 
	 * @access private
	 * @param string $access_url
	 * @param string $email_content
	 * @param string $recipient_email
	 * @return void
	 */
	private function save_email_content_for_localhost( $access_url, $email_content, $recipient_email ) {
		$email_data = [
			'access_url' => $access_url,
			'email_content' => $email_content,
			'recipient_email' => $recipient_email,
			'message' => 'Email functionality not available on localhost. Use the access URL below:'
		];
		
		// Save for admin notice display
		update_option( 'ps_localhost_email_data', $email_data );
	}

	/**
	 * Get white label email template
	 * 
	 * @access private
	 * @param string $site_name
	 * @param string $site_url  
	 * @param string $access_url
	 * @param string $license_key
	 * @return string
	 */
	private function get_white_label_email_template( $site_name, $site_url, $access_url, $license_key ) {
		$masked_license = substr( $license_key, 0, 8 ) . '****-****-****-' . substr( $license_key, -4 );
		
		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<title>Prime Slider White Label Access</title>
			<style>
				body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
				.container { max-width: 600px; margin: 0 auto; padding: 20px; }
				.header { background: #2196F3; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
				.content { background: #f9f9f9; padding: 30px; border-radius: 0 0 8px 8px; }
				.access-link { background: #2196F3; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0; }
				.warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
				.footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
			</style>
		</head>
		<body>
			<div class="container">
				<div class="header">
					<h1>🔒 Prime Slider White Label Access</h1>
				</div>
				<div class="content">
					<h2>Important: Save This Email!</h2>
					
					<p>Hello,</p>
					
					<p>You have successfully enabled <strong>BDTPS_CORE_HIDE mode</strong> for Prime Slider Pro on <strong><?php echo esc_html( $site_name ); ?></strong>.</p>
					
					<div class="warning">
						<h3>⚠️ IMPORTANT</h3>
						<p>The plugin interface is hidden from your WordPress admin. Use below link to modify white label settings.</p>

						<p style="text-align: center;">
							<a href="<?php echo esc_url( $access_url ); ?>" class="access-link">Access White Label Settings</a>
						</p>
					</div>					
					
					<p><strong>Direct Link:</strong><br>
					<a href="<?php echo esc_url( $access_url ); ?>"><?php echo esc_html( $access_url ); ?></a></p>
					
					
					<h3>🔧 What You Can Do</h3>
					<p>Using the access link above, you can:</p>
					<ul>
						<li>Disable BDTPS_CORE_HIDE mode</li>
						<li>Modify white label settings</li>
					</ul>
					
					<p>Need help? <a href="https://bdthemes.com/support/" target="_blank">Contact support</a> with your license key.</p>
					
				</div>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Handle white label access link
	 * 
	 * @access private
	 * @return void
	 */
	private function handle_white_label_access() {
		// Check if this is a white label access request
		if ( ! isset( $_GET['ps_wl'] ) || ! isset( $_GET['token'] ) ) {
			return;
		}

		// Check user capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have sufficient permissions to access this page.' );
		}

		$ps_wl = sanitize_text_field( $_GET['ps_wl'] );
		$access_token = sanitize_text_field( $_GET['token'] );

		// Check if ps_wl is set to 1
		if ( $ps_wl !== '1' ) {
			$this->show_access_error( 'Invalid access parameter. Please use the correct link from your email.' );
			return;
		}

		// Validate the access token
		if ( ! $this->validate_white_label_access_token( $access_token ) ) {
			$this->show_access_error( 'Invalid or expired access token. Please use the correct access link from your email.' );
			return;
		}

		// Valid access - temporarily allow access by setting a flag
		add_action('admin_init', [$this, 'admin_init']);
        add_action('admin_menu', [$this, 'admin_menu'], 201);

		// Add success notice
		add_action( 'admin_notices', function() {
			echo '<div class="notice notice-success is-dismissible">';
			echo '<p><strong>✅ White Label Access Granted!</strong> You can now modify white label settings.</p>';
			echo '</div>';
		} );
	}

	/**
	 * Show access error page
	 * 
	 * @access private
	 * @param string $message
	 * @return void
	 */
	private function show_access_error( $message ) {
		wp_die( 
			'<h1>🔒 Prime Slider White Label Access</h1>' .
			'<p><strong>Access Denied:</strong> ' . esc_html( $message ) . '</p>' .
			'<p>If you need assistance, please contact support with your license information.</p>' .
			'<p><a href="' . admin_url() . '" class="button button-primary">← Return to Dashboard</a></p>',
			'Access Denied',
			[ 'response' => 403 ]
		);
	}

	/**
	 * Inject white label icon CSS
	 * 
	 * @access public
	 * @return void
	 */
	public function inject_white_label_icon_css() {
		$white_label_enabled = get_option('ps_white_label_enabled', false);
		$white_label_icon = get_option('ps_white_label_icon', '');
		
		// Only inject CSS when white label is enabled AND a custom icon is set
		if ( $white_label_enabled && ! empty( $white_label_icon ) ) {
			echo '<style type="text/css">';
			echo '#toplevel_page_prime_slider_options .wp-menu-image {';
			echo 'background-image: url(' . esc_url( $white_label_icon ) . ') !important;';
			echo 'background-size: 20px 20px !important;';
			echo 'background-repeat: no-repeat !important;';
			echo 'background-position: center !important;';
			echo '}';
			echo '#toplevel_page_prime_slider_options .wp-menu-image:before {';
			echo 'display: none !important;';
			echo '}';
			echo '#toplevel_page_prime_slider_options .wp-menu-image img {';
			echo 'display: none !important;';
			echo '}';
			echo '</style>';
		}
		// When white label is disabled or no icon is set, don't inject any CSS
		// This allows WordPress's original icon to display naturally
	}

	/**
	 * Get used widgets.
	 *
	 * @access public
	 * @return array
	 * @since 6.0.0
	 *
	 */
	public static function get_used_widgets() {

		$used_widgets = array();

		if (class_exists('Elementor\Modules\Usage\Module')) {

			$module     = Module::instance();
			
			$old_error_level = error_reporting();
 			error_reporting(E_ALL & ~E_WARNING); // Suppress warnings
 			$elements = $module->get_formatted_usage('raw');
 			error_reporting($old_error_level); // Restore
			
			$ps_widgets = self::get_ps_widgets_names();

			if (is_array($elements) || is_object($elements)) {

				foreach ($elements as $post_type => $data) {
					foreach ($data['elements'] as $element => $count) {
						if (in_array($element, $ps_widgets, true)) {
							if (isset($used_widgets[$element])) {
								$used_widgets[$element] += $count;
							} else {
								$used_widgets[$element] = $count;
							}
						}
					}
				}
			}
		}

		return $used_widgets;
	}

	/**
	 * Get used separate widgets.
	 *
	 * @access public
	 * @return array
	 * @since 6.0.0
	 *
	 */

	public static function get_used_only_widgets() {

		$used_widgets = array();

		if (class_exists('Elementor\Modules\Usage\Module')) {

			$module     = Module::instance();
			
			$old_error_level = error_reporting();
 			error_reporting(E_ALL & ~E_WARNING); // Suppress warnings
 			$elements = $module->get_formatted_usage('raw');
 			error_reporting($old_error_level); // Restore
			
			$ps_widgets = self::get_ps_only_widgets();

			if (is_array($elements) || is_object($elements)) {

				foreach ($elements as $post_type => $data) {
					foreach ($data['elements'] as $element => $count) {
						if (in_array($element, $ps_widgets, true)) {
							if (isset($used_widgets[$element])) {
								$used_widgets[$element] += $count;
							} else {
								$used_widgets[$element] = $count;
							}
						}
					}
				}
			}
		}

		return $used_widgets;
	}

	/**
	 * Get used only separate 3rdParty widgets.
	 *
	 * @access public
	 * @return array
	 * @since 6.0.0
	 *
	 */

	public static function get_used_only_3rdparty() {

		$used_widgets = array();

		if (class_exists('Elementor\Modules\Usage\Module')) {

			$module     = Module::instance();
			
			$old_error_level = error_reporting();
 			error_reporting(E_ALL & ~E_WARNING); // Suppress warnings
 			$elements = $module->get_formatted_usage('raw');
 			error_reporting($old_error_level); // Restore
			
			$ps_widgets = self::get_ps_only_3rdparty_names();

			if (is_array($elements) || is_object($elements)) {

				foreach ($elements as $post_type => $data) {
					foreach ($data['elements'] as $element => $count) {
						if (in_array($element, $ps_widgets, true)) {
							if (isset($used_widgets[$element])) {
								$used_widgets[$element] += $count;
							} else {
								$used_widgets[$element] = $count;
							}
						}
					}
				}
			}
		}

		return $used_widgets;
	}

	/**
	 * Get unused widgets.
	 *
	 * @access public
	 * @return array
	 * @since 6.0.0
	 *
	 */

	public static function get_unused_widgets() {

		if (!current_user_can('install_plugins')) {
			die();
		}

		$ps_widgets = self::get_ps_widgets_names();

		$used_widgets = self::get_used_widgets();

		$unused_widgets = array_diff($ps_widgets, array_keys($used_widgets));

		return $unused_widgets;
	}

	/**
	 * Get unused separate widgets.
	 *
	 * @access public
	 * @return array
	 * @since 6.0.0
	 *
	 */

	public static function get_unused_only_widgets() {

		if (!current_user_can('install_plugins')) {
			die();
		}

		$ps_widgets = self::get_ps_only_widgets();

		$used_widgets = self::get_used_only_widgets();

		$unused_widgets = array_diff($ps_widgets, array_keys($used_widgets));

		return $unused_widgets;
	}

	/**
	 * Get unused separate 3rdparty widgets.
	 *
	 * @access public
	 * @return array
	 * @since 6.0.0
	 *
	 */

	public static function get_unused_only_3rdparty() {

		if (!current_user_can('install_plugins')) {
			die();
		}

		$ps_widgets = self::get_ps_only_3rdparty_names();

		$used_widgets = self::get_used_only_3rdparty();

		$unused_widgets = array_diff($ps_widgets, array_keys($used_widgets));

		return $unused_widgets;
	}

	/**
	 * Get widgets name
	 *
	 * @access public
	 * @return array
	 * @since 6.0.0
	 *
	 */

	public static function get_ps_widgets_names() {
		$names = self::$modules_names;

		if (null === $names) {
			$names = array_map(
				function ($item) {
					return isset($item['name']) ? 'prime-slider-' . str_replace('_', '-', $item['name']) : 'none';
				},
				self::$modules_list
			);
		}

		return $names;
	}

	/**
	 * Get separate widgets name
	 *
	 * @access public
	 * @return array
	 * @since 6.0.0
	 *
	 */

	public static function get_ps_only_widgets() {
		$names = self::$modules_names_only_widgets;

		if (null === $names) {
			$names = array_map(
				function ($item) {
					return isset($item['name']) ? 'prime-slider-' . str_replace('_', '-', $item['name']) : 'none';
				},
				self::$modules_list_only_widgets
			);
		}

		return $names;
	}

	/**
	 * Get separate 3rdParty widgets name
	 *
	 * @access public
	 * @return array
	 * @since 6.0.0
	 *
	 */

	public static function get_ps_only_3rdparty_names() {
		$names = self::$modules_names_only_3rdparty;

		if (null === $names) {
			$names = array_map(
				function ($item) {
					return isset($item['name']) ? 'prime-slider-' . str_replace('_', '-', $item['name']) : 'none';
				},
				self::$modules_list_only_3rdparty
			);
		}

		return $names;
	}

	/**
	 * Get URL with page id
	 *
	 * @access public
	 *
	 */

	public static function get_url() {
		return admin_url('admin.php?page=' . self::PAGE_ID);
	}

	/**
	 * Init settings API
	 *
	 * @access public
	 *
	 */

	public function admin_init() {

		//set the settings
		$this->settings_api->set_sections($this->get_settings_sections());
		$this->settings_api->set_fields($this->prime_slider_admin_settings());

		//initialize settings
		$this->settings_api->admin_init();
		$this->ps_redirect_to_get_pro();
		if (true === _is_ps_pro_activated()) {
			$this->bdt_redirect_to_renew_link();
		}
	}

	/**
	 * Add Plugin Menus
	 *
	 * @access public
	 *
	 */

	// Redirect to Prime Slider Pro pricing page
	public function ps_redirect_to_get_pro() {
        if (isset($_GET['page']) && $_GET['page'] === self::PAGE_ID . '_get_pro') {
            wp_redirect('https://bdthemes.com/deals/?utm_source=WordPress_org&utm_medium=bfcm_cta&utm_campaign=prime_slider');
            exit;
        }
    }

	// Redirect to renew link
	public function bdt_redirect_to_renew_link() {
		if (isset($_GET['page']) && $_GET['page'] === self::PAGE_ID . '_license_renew') {
			wp_redirect('https://account.bdthemes.com/');
			exit;
		}
	}

	public function admin_menu() {
		add_menu_page(
			BDTPS_CORE_TITLE . ' ' . esc_html__('Dashboard', 'bdthemes-prime-slider'),
			BDTPS_CORE_TITLE,
			'manage_options',
			self::PAGE_ID,
			[$this, 'plugin_page'],
			$this->prime_slider_icon(),
			58
		);

		add_submenu_page(
			self::PAGE_ID,
			BDTPS_CORE_TITLE,
			esc_html__('Core Widgets', 'bdthemes-prime-slider'),
			'manage_options',
			self::PAGE_ID . '#prime_slider_active_modules',
			[$this, 'display_page']
		);

		add_submenu_page(
			self::PAGE_ID,
			BDTPS_CORE_TITLE,
			esc_html__('3rd Party Widgets', 'bdthemes-prime-slider'),
			'manage_options',
			self::PAGE_ID . '#prime_slider_third_party_widget',
			[$this, 'display_page']
		);

		add_submenu_page(
			self::PAGE_ID,
			BDTPS_CORE_TITLE,
			esc_html__('Extensions', 'bdthemes-prime-slider'),
			'manage_options',
			self::PAGE_ID . '#prime_slider_elementor_extend',
			[$this, 'display_page']
		);

		add_submenu_page(
			self::PAGE_ID,
			BDTPS_CORE_TITLE,
			esc_html__('Special Features', 'bdthemes-prime-slider'),
			'manage_options',
			self::PAGE_ID . '#prime_slider_other_settings',
			[$this, 'display_page']
		);

		add_submenu_page(
			self::PAGE_ID,
			BDTPS_CORE_TITLE,
			esc_html__('Extra Options', 'bdthemes-prime-slider'),
			'manage_options',
			self::PAGE_ID . '#prime_slider_extra_options',
			[$this, 'plugin_page']
		);
		
		add_submenu_page(
			self::PAGE_ID,
			BDTPS_CORE_TITLE,
			esc_html__('System Status', 'bdthemes-prime-slider'),
			'manage_options',
			self::PAGE_ID . '#prime_slider_analytics_system_req',
			[$this, 'plugin_page']
		);
		
		add_submenu_page(
			self::PAGE_ID,
			BDTPS_CORE_TITLE,
			esc_html__('Other Plugins', 'bdthemes-prime-slider'),
			'manage_options',
			self::PAGE_ID . '#prime_slider_other_plugins',
			[$this, 'plugin_page']
		);
		
		// add_submenu_page(
		// 	self::PAGE_ID,
		// 	BDTPS_CORE_TITLE,
		// 	esc_html__('Get Up to 60%', 'bdthemes-prime-slider'),
		// 	'manage_options',
		// 	self::PAGE_ID . '#prime_slider_affiliate',
		// 	[$this, 'plugin_page']
		// );
		
		add_submenu_page(
			self::PAGE_ID,
			BDTPS_CORE_TITLE,
			esc_html__('Rollback Version', 'bdthemes-prime-slider'),
			'manage_options',
			self::PAGE_ID . '#prime_slider_rollback_version',
			[$this, 'plugin_page']
		);

		if (true !== _is_ps_pro_activated()) {
			add_submenu_page(
				self::PAGE_ID,
				BDTPS_CORE_TITLE,
				esc_html__('Black Friday Limited Offer Up To 87%', 'bdthemes-prime-slider'),
				'manage_options',
				self::PAGE_ID . '_get_pro',
				[$this, 'display_page']
			);
		}
	}

	/**
	 * Get SVG Icons of Prime Slider
	 *
	 * @access public
	 * @return string
	 */

	public function prime_slider_icon() {
		return 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0idXRmLTgiPz4NCjwhLS0gR2VuZXJhdG9yOiBBZG9iZSBJbGx1c3RyYXRvciAyMy4wLjMsIFNWRyBFeHBvcnQgUGx1Zy1JbiAuIFNWRyBWZXJzaW9uOiA2LjAwIEJ1aWxkIDApICAtLT4NCjxzdmcgdmVyc2lvbj0iMS4xIiBpZD0iTGF5ZXJfMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiDQoJIHZpZXdCb3g9IjAgMCAyMzAuNyAyNTQuOCIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgMjMwLjcgMjU0Ljg7IiB4bWw6c3BhY2U9InByZXNlcnZlIj4NCjxzdHlsZSB0eXBlPSJ0ZXh0L2NzcyI+DQoJLnN0MHtmaWxsOnVybCgjU1ZHSURfMV8pO30NCgkuc3Qxe2ZpbGw6dXJsKCNTVkdJRF8yXyk7fQ0KCS5zdDJ7ZmlsbDp1cmwoI1NWR0lEXzNfKTt9DQoJLnN0M3tmaWxsOnVybCgjU1ZHSURfNF8pO30NCgkuc3Q0e2ZpbGw6dXJsKCNTVkdJRF81Xyk7fQ0KPC9zdHlsZT4NCjxnPg0KCTxsaW5lYXJHcmFkaWVudCBpZD0iU1ZHSURfMV8iIGdyYWRpZW50VW5pdHM9InVzZXJTcGFjZU9uVXNlIiB4MT0iMTY1Ljg4MTMiIHkxPSItOS4xNzQyIiB4Mj0iLTE0Ljk3ODMiIHkyPSIxOTIuNzE1NiI+DQoJCTxzdG9wICBvZmZzZXQ9IjAiIHN0eWxlPSJzdG9wLWNvbG9yOiNGQzZBMkMiLz4NCgkJPHN0b3AgIG9mZnNldD0iMSIgc3R5bGU9InN0b3AtY29sb3I6I0ZFNTE2QiIvPg0KCTwvbGluZWFyR3JhZGllbnQ+DQoJPHBhdGggY2xhc3M9InN0MCIgZD0iTTIwMi4yLDY5LjJoLTE3NGMtMywwLTUuNS0yLjUtNS41LTUuNVYzMS4xYzAtMywyLjUtNS41LDUuNS01LjVoMTc0YzMsMCw1LjUsMi41LDUuNSw1LjV2MzIuNg0KCQlDMjA3LjcsNjYuOCwyMDUuMiw2OS4yLDIwMi4yLDY5LjJ6Ii8+DQoJPGxpbmVhckdyYWRpZW50IGlkPSJTVkdJRF8yXyIgZ3JhZGllbnRVbml0cz0idXNlclNwYWNlT25Vc2UiIHgxPSIyMDUuNjI4MSIgeTE9IjI2LjQzMjMiIHgyPSIyNC43Njg1IiB5Mj0iMjI4LjMyMjEiPg0KCQk8c3RvcCAgb2Zmc2V0PSIwIiBzdHlsZT0ic3RvcC1jb2xvcjojRkM2QTJDIi8+DQoJCTxzdG9wICBvZmZzZXQ9IjEiIHN0eWxlPSJzdG9wLWNvbG9yOiNGRTUxNkIiLz4NCgk8L2xpbmVhckdyYWRpZW50Pg0KCTxwYXRoIGNsYXNzPSJzdDEiIGQ9Ik0yMDIuMiwxNDkuMmgtMTc0Yy0zLDAtNS41LTIuNS01LjUtNS41di0zMi42YzAtMywyLjUtNS41LDUuNS01LjVoMTc0YzMsMCw1LjUsMi41LDUuNSw1LjV2MzIuNg0KCQlDMjA3LjcsMTQ2LjgsMjA1LjIsMTQ5LjIsMjAyLjIsMTQ5LjJ6Ii8+DQoJPGxpbmVhckdyYWRpZW50IGlkPSJTVkdJRF8zXyIgZ3JhZGllbnRVbml0cz0idXNlclNwYWNlT25Vc2UiIHgxPSIyMjMuMDM5IiB5MT0iNDIuMDI5NSIgeDI9IjQyLjE3OTQiIHkyPSIyNDMuOTE5NCI+DQoJCTxzdG9wICBvZmZzZXQ9IjAiIHN0eWxlPSJzdG9wLWNvbG9yOiNGQzZBMkMiLz4NCgkJPHN0b3AgIG9mZnNldD0iMSIgc3R5bGU9InN0b3AtY29sb3I6I0ZFNTE2QiIvPg0KCTwvbGluZWFyR3JhZGllbnQ+DQoJPHBhdGggY2xhc3M9InN0MiIgZD0iTTEyMS42LDIyOS4ySDI4LjJjLTMsMC01LjUtMi41LTUuNS01LjV2LTMyLjZjMC0zLDIuNS01LjUsNS41LTUuNWg5My41YzMsMCw1LjUsMi41LDUuNSw1LjV2MzIuNg0KCQlDMTI3LjIsMjI2LjcsMTI0LjcsMjI5LjIsMTIxLjYsMjI5LjJ6Ii8+DQoJPGxpbmVhckdyYWRpZW50IGlkPSJTVkdJRF80XyIgZ3JhZGllbnRVbml0cz0idXNlclNwYWNlT25Vc2UiIHgxPSIxNDYuMDMzMSIgeTE9Ii0yNi45NTUiIHgyPSItMzQuODI2NiIgeTI9IjE3NC45MzQ4Ij4NCgkJPHN0b3AgIG9mZnNldD0iMCIgc3R5bGU9InN0b3AtY29sb3I6I0ZDNkEyQyIvPg0KCQk8c3RvcCAgb2Zmc2V0PSIxIiBzdHlsZT0ic3RvcC1jb2xvcjojRkU1MTZCIi8+DQoJPC9saW5lYXJHcmFkaWVudD4NCgk8cGF0aCBjbGFzcz0ic3QzIiBkPSJNNjYuMyw0NS43VjEyN2MwLDMtMi41LDUuNS01LjUsNS41SDI4LjJjLTMsMC01LjUtMi41LTUuNS01LjVWNDUuN2MwLTMsMi41LTUuNSw1LjUtNS41aDMyLjYNCgkJQzYzLjgsNDAuMiw2Ni4zLDQyLjcsNjYuMyw0NS43eiIvPg0KCTxsaW5lYXJHcmFkaWVudCBpZD0iU1ZHSURfNV8iIGdyYWRpZW50VW5pdHM9InVzZXJTcGFjZU9uVXNlIiB4MT0iMjY0LjcxMzQiIHkxPSI3OS4zNjI4IiB4Mj0iODMuODUzNyIgeTI9IjI4MS4yNTI2Ij4NCgkJPHN0b3AgIG9mZnNldD0iMCIgc3R5bGU9InN0b3AtY29sb3I6I0ZDNkEyQyIvPg0KCQk8c3RvcCAgb2Zmc2V0PSIxIiBzdHlsZT0ic3RvcC1jb2xvcjojRkU1MTZCIi8+DQoJPC9saW5lYXJHcmFkaWVudD4NCgk8cGF0aCBjbGFzcz0ic3Q0IiBkPSJNMjA3LjcsMTExLjF2MTEyLjZjMCwzLTIuNSw1LjUtNS41LDUuNWgtMzIuNmMtMywwLTUuNS0yLjUtNS41LTUuNVYxMTEuMWMwLTMsMi41LTUuNSw1LjUtNS41aDMyLjYNCgkJQzIwNS4yLDEwNS42LDIwNy43LDEwOCwyMDcuNywxMTEuMXoiLz4NCjwvZz4NCjwvc3ZnPg0K';
	}

	/**
	 * Get SVG Icons of Prime Slider
	 *
	 * @access public
	 * @return array
	 */

	public function get_settings_sections() {
		$sections = [
			[
				'id'    => 'prime_slider_active_modules',
				'title' => esc_html__('Core Widgets', 'bdthemes-prime-slider')
			],
			[
				'id'    => 'prime_slider_third_party_widget',
				'title' => esc_html__('3rd Party Widgets', 'bdthemes-prime-slider')
			],
			[
				'id'    => 'prime_slider_elementor_extend',
				'title' => esc_html__('Extensions', 'bdthemes-prime-slider')
			],
			[
				'id'    => 'prime_slider_other_settings',
				'title' => esc_html__('Special Features', 'bdthemes-prime-slider'),
			],
		];

		return $sections;
	}

	/**
	 * Merge Admin Settings
	 *
	 * @access protected
	 * @return array
	 */

	protected function prime_slider_admin_settings() {

		return ModuleService::get_widget_settings(function ($settings) {
			$settings_fields = $settings['settings_fields'];

			self::$modules_list               = array_merge($settings_fields['prime_slider_active_modules'], $settings_fields['prime_slider_third_party_widget']);
			self::$modules_list_only_widgets  = $settings_fields['prime_slider_active_modules'];
			self::$modules_list_only_3rdparty = $settings_fields['prime_slider_third_party_widget'];

			return $settings_fields;
		});
	}

	/**
	 * Get Welcome Panel
	 *
	 * @access public
	 * @return void
	 */

	public function prime_slider_welcome() {

		?>

		<div class="ps-dashboard-panel"
			bdt-scrollspy="target: > div > div > .bdt-card; cls: bdt-animation-slide-bottom-small; delay: 300">

			<div class="ps-dashboard-welcome-container">

				<div class="ps-dashboard-item ps-dashboard-welcome bdt-card bdt-card-body">
					<h1 class="ps-feature-title ps-dashboard-welcome-title">
						<?php esc_html_e('Welcome to Prime Slider!', 'bdthemes-prime-slider'); ?>
					</h1>
					<p class="ps-dashboard-welcome-desc">
						<?php esc_html_e('Empower your web creation with powerful widgets, advanced extensions, ready templates and more.', 'bdthemes-prime-slider'); ?>
					</p>
					<a href="<?php echo admin_url('?ps_setup_wizard=show'); ?>"
						class="bdt-button bdt-welcome-button bdt-margin-small-top"
						target="_blank"><?php esc_html_e('Setup Prime Slider', 'bdthemes-prime-slider'); ?></a>

					<div class="ps-dashboard-compare-section">
						<h4 class="ps-feature-sub-title">
							<?php printf(esc_html__('Unlock %sPremium Features%s', 'bdthemes-prime-slider'), '<strong class="ps-highlight-text">', '</strong>'); ?>
						</h4>
						<h1 class="ps-feature-title ps-dashboard-compare-title">
							<?php esc_html_e('Create Your Sleek Website with Prime Slider Pro!', 'bdthemes-prime-slider'); ?>
						</h1>
						<p><?php esc_html_e('Don\'t need more plugins. This pro addon helps you build complex or professional websites—visually stunning, functional and customizable.', 'bdthemes-prime-slider'); ?>
						</p>
						<ul>
							<li><?php esc_html_e('Dynamic Slider and Integrations', 'bdthemes-prime-slider'); ?></li>
							<li><?php esc_html_e('Live Copy Paste', 'bdthemes-prime-slider'); ?></li>
							<li><?php esc_html_e('Duplicator', 'bdthemes-prime-slider'); ?></li>
							<li><?php esc_html_e('Reveal Effects', 'bdthemes-prime-slider'); ?></li>
							<li><?php esc_html_e('Adaptive Background', 'bdthemes-prime-slider'); ?>
							</li>
						</ul>
						<div class="ps-dashboard-compare-section-buttons">
							<a href="https://primeslider.pro/pricing/"
								class="bdt-button bdt-welcome-button bdt-margin-small-right"
								target="_blank"><?php esc_html_e('Compare Free Vs Pro', 'bdthemes-prime-slider'); ?></a>
							<a href="https://primeslider.pro/pricing?utm_source=PrimeSlider&utm_medium=PluginPage&utm_campaign=PrimeSlider&coupon=FREETOPRO"
								class="bdt-button bdt-dashboard-sec-btn"
								target="_blank"><?php esc_html_e('Get Premium at 30% OFF', 'bdthemes-prime-slider'); ?></a>
						</div>
					</div>
				</div>

				<div class="ps-dashboard-item ps-dashboard-template-quick-access bdt-card bdt-card-body">
					<div class="ps-dashboard-template-section">
						<img src="<?php echo BDTPS_CORE_ADMIN_URL . 'assets/images/template.jpg'; ?>"
							alt="Prime Slider Dashboard Template">
						<h1 class="ps-feature-title ">
							<?php esc_html_e('Faster Web Creation with Sleek and Ready-to-Use Templates!', 'bdthemes-prime-slider'); ?>
						</h1>
						<p><?php esc_html_e('Build your wordpress websites of any niche—not from scratch and in a single click.', 'bdthemes-prime-slider'); ?>
						</p>
						<a href="https://primeslider.pro/demo/"
							class="bdt-button bdt-dashboard-sec-btn bdt-margin-small-top"
							target="_blank"><?php esc_html_e('View Templates', 'bdthemes-prime-slider'); ?></a>
					</div>

					<div class="ps-dashboard-quick-access bdt-margin-medium-top">
						<img src="<?php echo BDTPS_CORE_ADMIN_URL . 'assets/images/support.jpg'; ?>"
							alt="Prime Slider Dashboard Template">
						<h1 class="ps-feature-title">
							<?php esc_html_e('Getting Started with Quick Access', 'bdthemes-prime-slider'); ?>
						</h1>
						<ul>
							<li><a href="https://primeslider.pro/contact/"
									target="_blank"><?php esc_html_e('Contact Us', 'bdthemes-prime-slider'); ?></a></li>
							<li><a href="https://bdthemes.com/support/"
									target="_blank"><?php esc_html_e('Help Centre', 'bdthemes-prime-slider'); ?></a></li>
							<li><a href="https://feedback.bdthemes.com/b/6vr2250l/feature-requests/idea/new"
									target="_blank"><?php esc_html_e('Request a Feature', 'bdthemes-prime-slider'); ?></a>
							</li>
						</ul>
						<div class="ps-dashboard-support-section">
							<h1 class="ps-feature-title">
								<i class="dashicons dashicons-phone"></i>
								<?php esc_html_e('24/7 Support', 'bdthemes-prime-slider'); ?>
							</h1>
							<p><?php esc_html_e('Helping you get real-time solutions related to web creation with WordPress, Elementor, and Prime Slider.', 'bdthemes-prime-slider'); ?>
							</p>
							<a href="https://bdthemes.com/support/" class="bdt-margin-small-top"
								target="_blank"><?php esc_html_e('Get Your Support', 'bdthemes-prime-slider'); ?></a>
						</div>
					</div>
				</div>

				<div class="ps-dashboard-item ps-dashboard-request-feature bdt-card bdt-card-body">
					<h1 class="ps-feature-title ps-dashboard-template-quick-title">
						<?php esc_html_e('What\'s Stacking You?', 'bdthemes-prime-slider'); ?>
					</h1>
					<p><?php esc_html_e('We are always here to help you. If you have any feature request, please let us know.', 'bdthemes-prime-slider'); ?>
					</p>
					<a href="https://feedback.bdthemes.com/b/6vr2250l/feature-requests/idea/new"
						class="bdt-button bdt-dashboard-sec-btn bdt-margin-small-top"
						target="_blank"><?php esc_html_e('Request Your Features', 'bdthemes-prime-slider'); ?></a>
				</div>

				<a href="https://www.youtube.com/watch?v=sZwJDtxasTg&list=PLP0S85GEw7DP3-yJrkgwpIeDFoXy0PDlM" target="_blank"
					class="ps-dashboard-item ps-dashboard-footer-item ps-dashboard-video-tutorial bdt-card bdt-card-body bdt-card-small">
					<span class="ps-dashboard-footer-item-icon">
						<i class="dashicons dashicons-video-alt3"></i>
					</span>
					<h1 class="ps-feature-title"><?php esc_html_e('Watch Video Tutorials', 'bdthemes-prime-slider'); ?></h1>
					<p><?php esc_html_e('An invaluable resource for mastering WordPress, Elementor, and Web Creation', 'bdthemes-prime-slider'); ?>
					</p>
				</a>
				<a href="https://bdthemes.com/all-knowledge-base-of-prime-slider/" target="_blank"
					class="ps-dashboard-item ps-dashboard-footer-item ps-dashboard-documentation bdt-card bdt-card-body bdt-card-small">
					<span class="ps-dashboard-footer-item-icon">
						<i class="dashicons dashicons-admin-tools"></i>
					</span>
					</span>
					<h1 class="ps-feature-title"><?php esc_html_e('Read Easy Documentation', 'bdthemes-prime-slider'); ?></h1>
					<p><?php esc_html_e('A way to eliminate the challenges you might face', 'bdthemes-prime-slider'); ?></p>
				</a>
				<a href="https://www.facebook.com/bdthemes" target="_blank"
					class="ps-dashboard-item ps-dashboard-footer-item ps-dashboard-community bdt-card bdt-card-body bdt-card-small">
					<span class="ps-dashboard-footer-item-icon">
						<i class="dashicons dashicons-admin-users"></i>
					</span>
					<h1 class="ps-feature-title"><?php esc_html_e('Join Our Community', 'bdthemes-prime-slider'); ?></h1>
					<p><?php esc_html_e('A platform for the opportunity to network, collaboration and innovation', 'bdthemes-prime-slider'); ?>
					</p>
				</a>
				<a href="https://wordpress.org/support/plugin/bdthemes-prime-slider-lite/reviews/" target="_blank"
					class="ps-dashboard-item ps-dashboard-footer-item ps-dashboard-review bdt-card bdt-card-body bdt-card-small">
					<span class="ps-dashboard-footer-item-icon">
						<i class="dashicons dashicons-star-filled"></i>
					</span>
					<h1 class="ps-feature-title"><?php esc_html_e('Show Your Love', 'bdthemes-prime-slider'); ?></h1>
					<p><?php esc_html_e('A way of the assessment of code', 'bdthemes-prime-slider'); ?></p>
				</a>
			</div>

		</div>

		<?php

	}

	/**
	 * Get Pro
	 *
	 * @access public
	 * @return void
	 */

	function prime_slider_get_pro() {
	?>
		<div class="ps-dashboard-panel" bdt-scrollspy="target: > div > div > .bdt-card; cls: bdt-animation-slide-bottom-small; delay: 300">

			<div class="bdt-grid" bdt-grid bdt-height-match="target: > div > .bdt-card" style="max-width: 800px; margin-left: auto; margin-right: auto;">
				<div class="bdt-width-1-1@m ps-comparision bdt-text-center">
					<div class="bdt-flex bdt-flex-between bdt-flex-middle">
						<div class="bdt-text-left">
							<h1 class="bdt-text-bold"><?php echo esc_html__('WHY GO WITH PRO?', 'bdthemes-prime-slider'); ?></h1>
							<h2><?php echo esc_html__('Just Compare With ', 'bdthemes-prime-slider'); ?>Prime Slider<?php echo esc_html__(' Free Vs Pro', 'bdthemes-prime-slider'); ?></h2>
						</div>
						<?php if (true !== _is_ps_pro_activated()) : ?>
							<div class="ps-purchase-button">
								<a href="https://primeslider.pro/pricing/" target="_blank"><?php echo esc_html__('Purchase Now', 'bdthemes-prime-slider'); ?></a>
							</div>
						<?php endif; ?>
					</div>


					<div>

						<ul class="bdt-list bdt-list-divider bdt-text-left bdt-text-normal" style="font-size: 15px;">


							<li class="bdt-text-bold">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><?php echo esc_html__('Features', 'bdthemes-prime-slider'); ?></div>
									<div class="bdt-width-auto@m"><?php echo esc_html__('Free', 'bdthemes-prime-slider'); ?></div>
									<div class="bdt-width-auto@m"><?php echo esc_html__('Pro', 'bdthemes-prime-slider'); ?></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><span bdt-tooltip="pos: top-left; title: <?php echo esc_html__('Free have 27+ Widgets but Pro have 21+ core widgets', 'bdthemes-prime-slider'); ?>"><?php echo esc_html__('Core Widgets', 'bdthemes-prime-slider'); ?></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><span bdt-tooltip="pos: top-left; title: <?php echo esc_html__('Free have 3+ Widgets but Pro have 3+ 3rd party widgets', 'bdthemes-prime-slider'); ?>"><?php echo esc_html__('3rd Party Widgets', 'bdthemes-prime-slider'); ?></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><?php echo esc_html__('Theme Compatibility', 'bdthemes-prime-slider'); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><?php echo esc_html__('Dynamic Content & Custom Fields Capabilities', 'bdthemes-prime-slider'); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><?php echo esc_html__('Proper Documentation', 'bdthemes-prime-slider'); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><?php echo esc_html__('Updates & Support', 'bdthemes-prime-slider'); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><?php echo esc_html__('Ready Made Pages', 'bdthemes-prime-slider'); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><?php echo esc_html__('Ready Made Blocks', 'bdthemes-prime-slider'); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><?php echo esc_html__('Elementor Extended Widgets', 'bdthemes-prime-slider'); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><?php echo esc_html__('Live Copy or Paste', 'bdthemes-prime-slider'); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><?php echo esc_html__('Duplicator', 'bdthemes-prime-slider'); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m">Rooten<?php echo esc_html__(' Theme Pro Features', 'bdthemes-prime-slider'); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-no"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><?php echo esc_html__('Priority Support', 'bdthemes-prime-slider'); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-no"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><?php echo esc_html__('Reveal Effects', 'bdthemes-prime-slider'); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-no"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
						</ul>


						<!-- <div class="ps-dashboard-divider"></div> -->


						<div class="ps-more-features bdt-card bdt-card-body bdt-margin-medium-top bdt-padding-large">
							<ul class="bdt-list bdt-list-divider bdt-text-left" style="font-size: 15px;">
								<li>
									<div class="bdt-grid bdt-grid-small">
										<div class="bdt-width-1-3@m">
											<span class="dashicons dashicons-heart"></span><?php echo esc_html__(' Incredibly Advanced', 'bdthemes-prime-slider'); ?>
										</div>
										<div class="bdt-width-1-3@m">
											<span class="dashicons dashicons-heart"></span><?php echo esc_html__(' Refund or Cancel Anytime', 'bdthemes-prime-slider'); ?>
										</div>
										<div class="bdt-width-1-3@m">
											<span class="dashicons dashicons-heart"></span><?php echo esc_html__(' Dynamic Content', 'bdthemes-prime-slider'); ?>
										</div>
									</div>
								</li>

								<li>
									<div class="bdt-grid bdt-grid-small">
										<div class="bdt-width-1-3@m">
											<span class="dashicons dashicons-heart"></span><?php echo esc_html__(' Super-Flexible Widgets', 'bdthemes-prime-slider'); ?>
										</div>
										<div class="bdt-width-1-3@m">
											<span class="dashicons dashicons-heart"></span><?php echo esc_html__(' 24/7 Premium Support', 'bdthemes-prime-slider'); ?>
										</div>
										<div class="bdt-width-1-3@m">
											<span class="dashicons dashicons-heart"></span><?php echo esc_html__(' Third Party Plugins', 'bdthemes-prime-slider'); ?>
										</div>
									</div>
								</li>

								<li>
									<div class="bdt-grid bdt-grid-small">
										<div class="bdt-width-1-3@m">
											<span class="dashicons dashicons-heart"></span><?php echo esc_html__(' Special Discount!', 'bdthemes-prime-slider'); ?>
										</div>
										<div class="bdt-width-1-3@m">
											<span class="dashicons dashicons-heart"></span><?php echo esc_html__(' Custom Field Integration', 'bdthemes-prime-slider'); ?>
										</div>
										<div class="bdt-width-1-3@m">
											<span class="dashicons dashicons-heart"></span><?php echo esc_html__(' With Live Chat Support', 'bdthemes-prime-slider'); ?>
										</div>
									</div>
								</li>

								<li>
									<div class="bdt-grid bdt-grid-small">
										<div class="bdt-width-1-3@m">
											<span class="dashicons dashicons-heart"></span><?php echo esc_html__(' Trusted Payment Methods', 'bdthemes-prime-slider'); ?>
										</div>
										<div class="bdt-width-1-3@m">
											<span class="dashicons dashicons-heart"></span><?php echo esc_html__(' Interactive Effects', 'bdthemes-prime-slider'); ?>
										</div>
										<div class="bdt-width-1-3@m">
											<span class="dashicons dashicons-heart"></span><?php echo esc_html__(' Video Tutorial', 'bdthemes-prime-slider'); ?>
										</div>
									</div>
								</li>
							</ul>

							<?php if (true !== _is_ps_pro_activated()) : ?>
								<div class="ps-purchase-button bdt-margin-medium-top">
									<a href="https://primeslider.pro/pricing/" target="_blank"><?php echo esc_html__('Purchase Now', 'bdthemes-prime-slider'); ?></a>
								</div>
							<?php endif; ?>

						</div>

					</div>
				</div>
			</div>

		</div>
	<?php
	}

	 /**
	 * Display Plugin Page
	 *
	 * @access public
	 * @return void
	 */

	public function plugin_page() {

		?>

		<div class="wrap prime-slider-dashboard">
			<h1></h1> <!-- don't remove this div, it's used for the notice container -->
		
			<div class="ps-dashboard-wrapper bdt-margin-top">
				<div class="ps-dashboard-header bdt-flex bdt-flex-wrap bdt-flex-between bdt-flex-middle"
					bdt-sticky="offset: 32; animation: bdt-animation-slide-top-small; duration: 300">

					<div class="bdt-flex bdt-flex-wrap bdt-flex-middle">
						<!-- Header Shape Elements -->
						<div class="ps-header-elements">
							<span class="ps-header-element ps-header-circle"></span>
							<span class="ps-header-element ps-header-dots"></span>
							<span class="ps-header-element ps-header-line"></span>
							<span class="ps-header-element ps-header-square"></span>
							<span class="ps-header-element ps-header-wave"></span>
						</div>

						<div class="ps-logo">
							<?php 
							$white_label_enabled = get_option( 'ps_white_label_enabled', false );
							$white_label_logo 	 = get_option( 'ps_white_label_logo', '' );
							$white_label_title 	 = get_option( 'ps_white_label_title', '' );
							
							if ($white_label_enabled && !empty($white_label_logo)) {
							
								$alt_text = !empty($white_label_title) ? $white_label_title . ' Logo' : 'Custom Logo';
								echo '<img src="' . esc_url($white_label_logo) . '" alt="' . esc_attr($alt_text) . '" style="max-height: 40px;">';
							} else {
								echo '<img src="' . BDTPS_CORE_URL . 'assets/images/logo-with-text.svg" alt="Prime Slider Logo">';
							}
							?>
						</div>
					</div>

					<div class="ps-dashboard-new-page-wrapper bdt-flex bdt-flex-wrap bdt-flex-middle">
						

						<!-- Always render save button, JavaScript will control visibility -->
						<div class="ps-dashboard-save-btn" style="display: none;">
							<button class="bdt-button bdt-button-primary prime-slider-settings-save-btn" type="submit">
								<?php esc_html_e('Save Settings', 'bdthemes-prime-slider'); ?>
							</button>
						</div>

						<!-- Custom Code Save Button Section -->
						<div class="ps-code-save-section" style="display: none;">
							<button type="button" id="ps-save-custom-code" class="bdt-button bdt-button-primary prime-slider-custom-code-save-btn">
								<?php esc_html_e('Save Custom Code', 'bdthemes-prime-slider'); ?>
							</button>
							<button type="button" id="ps-reset-custom-code" class="bdt-button bdt-button-primary prime-slider-custom-code-reset-btn">
								<?php esc_html_e('Reset Code', 'bdthemes-prime-slider'); ?>
							</button>
						</div>

						<!--  White Label Save Button Section -->
						<?php if (self::is_white_label_license()): ?>
							<div class="ps-white-label-save-section" style="display: none;">
								<button type="button" 
										id="ps-save-white-label" 
										class="bdt-button bdt-button-primary prime-slider-white-label-save-btn">
										<?php esc_html_e('Save White Label Settings', 'bdthemes-prime-slider'); ?>
								</button>
							</div>
						<?php endif; ?>

						<div class="ps-dashboard-new-page">
							<a class="bdt-flex bdt-flex-middle" href="<?php echo esc_url(admin_url('post-new.php?post_type=page')); ?>" class=""><i class="dashicons dashicons-admin-page"></i>
								<?php echo esc_html__('Create New Page', 'bdthemes-prime-slider') ?>
							</a>
						</div>
					</div>
				</div>

				<div class="ps-dashboard-container bdt-flex">
					<div class="ps-dashboard-nav-container-wrapper">
						<div class="ps-dashboard-nav-container-inner" bdt-sticky="end: !.ps-dashboard-container; offset: 115; animation: bdt-animation-slide-top-small; duration: 300">

							<!-- Navigation Shape Elements -->
							<div class="ps-nav-elements">
								<span class="ps-nav-element ps-nav-circle"></span>
								<span class="ps-nav-element ps-nav-dots"></span>
								<span class="ps-nav-element ps-nav-line"></span>
								<span class="ps-nav-element ps-nav-square"></span>
								<span class="ps-nav-element ps-nav-triangle"></span>
								<span class="ps-nav-element ps-nav-plus"></span>
								<span class="ps-nav-element ps-nav-wave"></span>
							</div>

						<?php $this->settings_api->show_navigation(); ?>
						</div>
					</div>


					<div class="bdt-switcher bdt-tab-container bdt-container-xlarge bdt-flex-1">
						<div id="prime_slider_welcome_page" class="ps-option-page group">
							<?php $this->prime_slider_welcome(); ?>
						</div>

						<?php $this->settings_api->show_forms(); ?>

						<div id="prime_slider_extra_options_page" class="ps-option-page group">
							<?php $this->prime_slider_extra_options(); ?>
						</div>

						<div id="prime_slider_analytics_system_req_page" class="ps-option-page group">
							<?php $this->prime_slider_analytics_system_req_content(); ?>
						</div>

						<div id="prime_slider_other_plugins_page" class="ps-option-page group">
							<?php $this->prime_slider_others_plugin(); ?>
						</div>

						<!-- <div id="prime_slider_affiliate_page" class="ps-option-page group">
							<?php //$this->prime_slider_affiliate_content(); ?>
						</div> -->

						<div id="prime_slider_rollback_version_page" class="ps-option-page group">
							<?php $this->prime_slider_rollback_version_content(); ?>
						</div>

                        <?php if (_is_ps_pro_activated() !== true) : ?>
                            <div id="prime_slider_get_pro" class="ps-option-page group">
                                <?php $this->prime_slider_get_pro(); ?>
                            </div>
                        <?php endif; ?>

                        <div id="prime_slider_license_settings_page" class="ps-option-page group">

                            <?php
                            if (_is_ps_pro_activated() == true) {
                                apply_filters('ps_license_page', '');
                            }

                            ?>
                        </div>

					</div>
				</div>

				<?php if (!defined('BDTPS_CORE_WL') || false == self::license_wl_status()) {
					$this->footer_info();
				} ?>
			</div>

		</div>

		<?php

		$this->script();

	}


	/**
     * Tabbable JavaScript codes & Initiate Color Picker
     *
     * This code uses localstorage for displaying active tabs
     */
    function script() {
    ?>
        <script>
            jQuery(document).ready(function() {
                jQuery('.ps-no-result').removeClass('bdt-animation-shake');
            });

            function filterSearch(e) {
                var parentID = '#' + jQuery(e).data('id');
                var search = jQuery(parentID).find('.bdt-search-input').val().toLowerCase();

                jQuery(".ps-options .ps-option-item").filter(function() {
                    jQuery(this).toggle(jQuery(this).attr('data-widget-name').toLowerCase().indexOf(search) > -1)
                });

                if (!search) {
                    jQuery(parentID).find('.bdt-search-input').attr('bdt-filter-control', "");
                    jQuery(parentID).find('.ps-widget-all').trigger('click');
                } else {
                    jQuery(parentID).find('.bdt-search-input').attr('bdt-filter-control', "filter: [data-widget-name*='" + search + "']");
                    jQuery(parentID).find('.bdt-search-input').removeClass('bdt-active'); // Thanks to Bar-Rabbas
                    jQuery(parentID).find('.bdt-search-input').trigger('click');
                }
            }

            jQuery('.ps-options-parent').each(function(e, item) {
                var eachItem = '#' + jQuery(item).attr('id');
                jQuery(eachItem).on("beforeFilter", function() {
                    jQuery(eachItem).find('.ps-no-result').removeClass('bdt-animation-shake');
                });

                jQuery(eachItem).on("afterFilter", function() {

                    var isElementVisible = false;
                    var i = 0;

                    if (jQuery(eachItem).closest(".ps-options-parent").eq(i).is(":visible")) {} else {
                        isElementVisible = true;
                    }

                    while (!isElementVisible && i < jQuery(eachItem).find(".ps-option-item").length) {
                        if (jQuery(eachItem).find(".ps-option-item").eq(i).is(":visible")) {
                            isElementVisible = true;
                        }
                        i++;
                    }

                    if (isElementVisible === false) {
                        jQuery(eachItem).find('.ps-no-result').addClass('bdt-animation-shake');
                    }
                });


            });


            jQuery('.ps-widget-filter-nav li a').on('click', function(e) {
                jQuery(this).closest('.bdt-widget-filter-wrapper').find('.bdt-search-input').val('');
                jQuery(this).closest('.bdt-widget-filter-wrapper').find('.bdt-search-input').val('').attr('bdt-filter-control', '');
            });


            jQuery(document).ready(function($) {
                'use strict';

                function hashHandler() {
                    var $tab = jQuery('.prime-slider-dashboard .bdt-tab');
                    if (window.location.hash) {
                        var hash = window.location.hash.substring(1);
                        bdtUIkit.tab($tab).show(jQuery('#bdt-' + hash).data('tab-index'));
                    }
                }

                function onWindowLoad() {
                    hashHandler();
                }

                if (document.readyState === 'complete') {
					onWindowLoad();
				} else {
					jQuery(window).on('load', onWindowLoad);
				}

                window.addEventListener("hashchange", hashHandler, true);

                jQuery('.toplevel_page_prime_slider_options > ul > li > a ').on('click', function(event) {
                    jQuery(this).parent().siblings().removeClass('current');
                    jQuery(this).parent().addClass('current');
                });

                jQuery('#prime_slider_active_modules_page a.ps-active-all-widget').on('click', function(e) {
                    e.preventDefault();

                    jQuery('#prime_slider_active_modules_page .ps-option-item:not(.ps-pro-inactive) .checkbox:visible').each(function() {
                        jQuery(this).attr('checked', 'checked').prop("checked", true);
                    });

                    jQuery(this).addClass('bdt-active');
                    jQuery('a.ps-deactive-all-widget').removeClass('bdt-active');
                });

                jQuery('#prime_slider_active_modules_page a.ps-deactive-all-widget').on('click', function(e) {
                    e.preventDefault();
                    jQuery('#prime_slider_active_modules_page .ps-option-item:not(.ps-pro-inactive) .checkbox:visible').each(function() {
                        jQuery(this).removeAttr('checked');
                    });

                    jQuery(this).addClass('bdt-active');
                    jQuery('a.ps-active-all-widget').removeClass('bdt-active');
                });

				$('#prime_sliderthird_party_widget_page a.ps-active-all-widget').on('click', function (e) {
					e.preventDefault();

					$('#prime_sliderthird_party_widget_page .ps-option-item:not(.ps-pro-inactive) .checkbox:visible').each(function () {
						$(this).attr('checked', 'checked').prop("checked", true);
					});

					$(this).addClass('bdt-active');
					$('#prime_sliderthird_party_widget_page a.ps-deactive-all-widget').removeClass('bdt-active');
					
					// Ensure save button remains visible
					setTimeout(function() {
						$('.ps-dashboard-save-btn').show();
					}, 100);
				});

				$('#prime_sliderthird_party_widget_page a.ps-deactive-all-widget').on('click', function (e) {
					e.preventDefault();

					$('#prime_sliderthird_party_widget_page .checkbox:visible').each(function () {
						$(this).removeAttr('checked').prop("checked", false);
					});

					$(this).addClass('bdt-active');
					$('#prime_sliderthird_party_widget_page a.ps-active-all-widget').removeClass('bdt-active');
					
					// Ensure save button remains visible
					setTimeout(function() {
						$('.ps-dashboard-save-btn').show();
					}, 100);
				});

                jQuery('#prime_slider_elementor_extend_page a.ps-active-all-widget').on('click', function(e) {
                    e.preventDefault();

                    jQuery('#prime_slider_elementor_extend_page .checkbox:visible').each(function() {
                        jQuery(this).attr('checked', 'checked').prop("checked", true);
                    });

                    jQuery(this).addClass('bdt-active');
                    jQuery('a.ps-deactive-all-widget').removeClass('bdt-active');
                });

                jQuery('#prime_slider_elementor_extend_page a.ps-deactive-all-widget').on('click', function(e) {
                    e.preventDefault();
                    jQuery('#prime_slider_elementor_extend_page .checkbox:visible').each(function() {
                        jQuery(this).removeAttr('checked');
                    });

                    jQuery(this).addClass('bdt-active');
                    jQuery('a.ps-active-all-widget').removeClass('bdt-active');
                });

                // Activate/Deactivate all widgets functionality
				$('#prime_slider_active_modules_page a.ps-active-all-widget').on('click', function (e) {
					e.preventDefault();

					$('#prime_slider_active_modules_page .ps-option-item:not(.ps-pro-inactive) .checkbox:visible').each(function () {
						$(this).attr('checked', 'checked').prop("checked", true);
					});

					$(this).addClass('bdt-active');
					$('#prime_slider_active_modules_page a.ps-deactive-all-widget').removeClass('bdt-active');
					
					// Ensure save button remains visible
					setTimeout(function() {
						$('.ps-dashboard-save-btn').show();
					}, 100);
				});

				$('#prime_slider_active_modules_page a.ps-deactive-all-widget').on('click', function (e) {
					e.preventDefault();

					$('#prime_slider_active_modules_page .checkbox:visible').each(function () {
						$(this).removeAttr('checked').prop("checked", false);
					});

					$(this).addClass('bdt-active');
					$('#prime_slider_active_modules_page a.ps-active-all-widget').removeClass('bdt-active');
					
					// Ensure save button remains visible
					setTimeout(function() {
						$('.ps-dashboard-save-btn').show();
					}, 100);
				});

				$('#prime_slider_elementor_extend_page a.ps-active-all-widget').on('click', function (e) {
					e.preventDefault();

					$('#prime_slider_elementor_extend_page .ps-option-item:not(.ps-pro-inactive) .checkbox:visible').each(function () {
						$(this).attr('checked', 'checked').prop("checked", true);
					});

					$(this).addClass('bdt-active');
					$('#prime_slider_elementor_extend_page a.ps-deactive-all-widget').removeClass('bdt-active');
					
					// Ensure save button remains visible
					setTimeout(function() {
						$('.ps-dashboard-save-btn').show();
					}, 100);
				});

				$('#prime_slider_elementor_extend_page a.ps-deactive-all-widget').on('click', function (e) {
					e.preventDefault();

					$('#prime_slider_elementor_extend_page .checkbox:visible').each(function () {
						$(this).removeAttr('checked').prop("checked", false);
					});

					$(this).addClass('bdt-active');
					$('#prime_slider_elementor_extend_page a.ps-active-all-widget').removeClass('bdt-active');
					
					// Ensure save button remains visible
					setTimeout(function() {
						$('.ps-dashboard-save-btn').show();
					}, 100);
				});

				$('#prime_slider_active_modules_page, #prime_slider_third_party_widget_page, #prime_slider_elementor_extend_page, #prime_slider_other_settings_page').find('.ps-pro-inactive .checkbox').each(function () {
					$(this).removeAttr('checked');
					$(this).attr("disabled", true);
				});

            });

            jQuery(document).ready(function ($) {
                const getProLink = $('a[href="admin.php?page=prime_slider_options_get_pro"]');
                if (getProLink.length) {
                    getProLink.attr('target', '_blank');
                }
            });

            // License Renew Redirect
            jQuery(document).ready(function ($) {
                const renewalLink = $('a[href="admin.php?page=prime_slider_options_license_renew"]');
                if (renewalLink.length) {
                    renewalLink.attr('target', '_blank');
                }
            });

			// Dynamic Save Button Control
			jQuery(document).ready(function ($) {
				// Define pages that need save button - only specific settings pages
				const pagesWithSave = [
					'prime_slider_active_modules',        // Core widgets
					'prime_slider_third_party_widget',        // Core widgets
					'prime_slider_elementor_extend',      // Extensions
					'prime_slider_other_settings',        // Special features
					'prime_slider_api_settings'           // API settings
				];

				function toggleSaveButton() {
					const currentHash = window.location.hash.substring(1);
					const saveButton = $('.ps-dashboard-save-btn');
					
					// Check if current page should have save button
					if (pagesWithSave.includes(currentHash)) {
						saveButton.fadeIn(200);
					} else {
						saveButton.fadeOut(200);
					}
				}

				// Force save button to be visible for settings pages
				function forceSaveButtonVisible() {
					const currentHash = window.location.hash.substring(1);
					const saveButton = $('.ps-dashboard-save-btn');
					
					if (pagesWithSave.includes(currentHash)) {
						saveButton.show();
					}
				}

				// Initial check
				toggleSaveButton();

				// Listen for hash changes
				$(window).on('hashchange', function() {
					toggleSaveButton();
				});

				// Listen for tab clicks
				$('.bdt-dashboard-navigation a').on('click', function() {
					setTimeout(toggleSaveButton, 100);
				});

				// Also listen for navigation menu clicks (from show_navigation())
				$(document).on('click', '.bdt-tab a, .bdt-subnav a, .ps-dashboard-nav a, [href*="#prime_slider"]', function() {
					setTimeout(toggleSaveButton, 100);
				});

				// Listen for bulk active/deactive button clicks to maintain save button visibility
				$(document).on('click', '.ps-active-all-widget, .ps-deactive-all-widget', function() {
					setTimeout(forceSaveButtonVisible, 50);
				});

				// Listen for individual checkbox changes to maintain save button visibility
				$(document).on('change', '#prime_slider_third_party_widget_page .checkbox, #prime_slider_elementor_extend_page .checkbox, #prime_slider_active_modules_page .checkbox', function() {
					setTimeout(forceSaveButtonVisible, 50);
				});

				// Update URL when navigation items are clicked
				$(document).on('click', '.bdt-tab a, .bdt-subnav a, .ps-dashboard-nav a', function(e) {
					const href = $(this).attr('href');
					if (href && href.includes('#')) {
						const hash = href.substring(href.indexOf('#'));
						if (hash && hash.length > 1) {
							// Update browser URL with the hash
							const currentUrl = window.location.href.split('#')[0];
							const newUrl = currentUrl + hash;
							window.history.pushState(null, null, newUrl);
							
							// Trigger hash change event for other listeners
							$(window).trigger('hashchange');
						}
					}
				});

				// Handle save button click
				$(document).on('click', '.prime-slider-settings-save-btn', function(e) {
					e.preventDefault();
					
					// Find the active form in the current tab
					const currentHash = window.location.hash.substring(1);
					let targetForm = null;
					
					// Look for forms in the active tab content
					if (currentHash) {
						// Try to find form in the specific tab page
						targetForm = $('#' + currentHash + '_page form.settings-save');
						
						// If not found, try without _page suffix
						if (!targetForm || targetForm.length === 0) {
							targetForm = $('#' + currentHash + ' form.settings-save');
						}
						
						// Try to find any form in the active tab content
						if (!targetForm || targetForm.length === 0) {
							targetForm = $('#' + currentHash + '_page form');
						}
					}
					
					// Fallback to any visible form with settings-save class
					if (!targetForm || targetForm.length === 0) {
						targetForm = $('form.settings-save:visible').first();
					}
					
					// Last fallback - any visible form
					if (!targetForm || targetForm.length === 0) {
						targetForm = $('.bdt-switcher .group:visible form').first();
					}
					
					if (targetForm && targetForm.length > 0) {
						// Show loading notification
						// bdtUIkit.notification({
						// 	message: '<div bdt-spinner></div> <?php //esc_html_e('Please wait, Saving settings...', 'bdthemes-prime-slider') ?>',
						// 	timeout: false
						// });

						// Submit form using AJAX (same logic as existing form submission)
						targetForm.ajaxSubmit({
							success: function () {
								// Show success message using UIkit notification (same as main settings)
								bdtUIkit.notification.closeAll();
								bdtUIkit.notification({
									message: '<span class="dashicons dashicons-yes"></span> <?php esc_html_e('Settings Saved Successfully.', 'bdthemes-prime-slider') ?>',
									status: 'primary',
									pos: 'top-center'
								});
							},
							error: function (data) {
								bdtUIkit.notification.closeAll();
								bdtUIkit.notification({
									message: '<span bdt-icon=\'icon: warning\'></span> <?php esc_html_e('Unknown error, make sure access is correct!', 'bdthemes-prime-slider') ?>',
									status: 'warning'
								});
							}
						});
					} else {
						// Show error if no form found
						bdtUIkit.notification({
							message: '<span bdt-icon="icon: warning"></span> <?php esc_html_e('No settings form found to save.', 'bdthemes-prime-slider') ?>',
							status: 'warning'
						});
					}
				});

				//White Label Settings Functionality
				//Check if ps_admin_ajax is available
				if (typeof ps_admin_ajax === 'undefined') {
					window.ps_admin_ajax = {
						ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
						white_label_nonce: '<?php echo wp_create_nonce('ps_white_label_nonce'); ?>'
					};
				}				
				
				// Initialize CodeMirror editors for custom code
				var codeMirrorEditors = {};
				
				function initializeCodeMirrorEditors() {
					// CSS Editor 1
					if (document.getElementById('ps-custom-css')) {
						codeMirrorEditors['ps-custom-css'] = wp.codeEditor.initialize('ps-custom-css', {
							type: 'text/css',
							codemirror: {
								lineNumbers: true,
								mode: 'css',
								theme: 'default',
								lineWrapping: true,
								autoCloseBrackets: true,
								matchBrackets: true,
								lint: false
							}
						});
					}
					
					// JavaScript Editor 1
					if (document.getElementById('ps-custom-js')) {
						codeMirrorEditors['ps-custom-js'] = wp.codeEditor.initialize('ps-custom-js', {
							type: 'application/javascript',
							codemirror: {
								lineNumbers: true,
								mode: 'javascript',
								theme: 'default',
								lineWrapping: true,
								autoCloseBrackets: true,
								matchBrackets: true,
								lint: false
							}
						});
					}
					
					// CSS Editor 2
					if (document.getElementById('ps-custom-css-2')) {
						codeMirrorEditors['ps-custom-css-2'] = wp.codeEditor.initialize('ps-custom-css-2', {
							type: 'text/css',
							codemirror: {
								lineNumbers: true,
								mode: 'css',
								theme: 'default',
								lineWrapping: true,
								autoCloseBrackets: true,
								matchBrackets: true,
								lint: false
							}
						});
					}
					
					// JavaScript Editor 2
					if (document.getElementById('ps-custom-js-2')) {
						codeMirrorEditors['ps-custom-js-2'] = wp.codeEditor.initialize('ps-custom-js-2', {
							type: 'application/javascript',
							codemirror: {
								lineNumbers: true,
								mode: 'javascript',
								theme: 'default',
								lineWrapping: true,
								autoCloseBrackets: true,
								matchBrackets: true,
								lint: false
							}
						});
					}
					
					// Refresh all editors after a short delay to ensure proper rendering
					setTimeout(function() {
						refreshAllCodeMirrorEditors();
					}, 100);
				}
				
				// Function to refresh all CodeMirror editors
				function refreshAllCodeMirrorEditors() {
					Object.keys(codeMirrorEditors).forEach(function(editorKey) {
						if (codeMirrorEditors[editorKey] && codeMirrorEditors[editorKey].codemirror) {
							codeMirrorEditors[editorKey].codemirror.refresh();
						}
					});
				}
				
				// Function to refresh editors when tab becomes visible
				function refreshEditorsOnTabShow() {
					// Listen for tab changes (UIkit tab switching)
					if (typeof bdtUIkit !== 'undefined' && bdtUIkit.tab) {
						// When tab becomes active, refresh editors
						bdtUIkit.util.on(document, 'shown', '.bdt-tab', function() {
							setTimeout(function() {
								refreshAllCodeMirrorEditors();
							}, 50);
						});
					}
					
					// Also listen for direct tab clicks
					$('.bdt-tab a').on('click', function() {
						setTimeout(function() {
							refreshAllCodeMirrorEditors();
						}, 100);
					});
					
					// Listen for switcher changes (UIkit switcher)
					if (typeof bdtUIkit !== 'undefined' && bdtUIkit.switcher) {
						bdtUIkit.util.on(document, 'shown', '.bdt-switcher', function() {
							setTimeout(function() {
								refreshAllCodeMirrorEditors();
							}, 50);
						});
					}
				}
				
				// Initialize editors when page loads - with delay for better rendering
				setTimeout(function() {
					initializeCodeMirrorEditors();
				}, 100);
				
				// Setup tab switching handlers
				setTimeout(function() {
					refreshEditorsOnTabShow();
				}, 100);
				
				// Handle window resize events
				$(window).on('resize', function() {
					setTimeout(function() {
						refreshAllCodeMirrorEditors();
					}, 100);
				});
				
				// Handle page visibility changes (when switching browser tabs)
				document.addEventListener('visibilitychange', function() {
					if (!document.hidden) {
						setTimeout(function() {
							refreshAllCodeMirrorEditors();
						}, 200);
					}
				});
				
				// Force refresh when clicking on the Custom CSS & JS tab specifically
				$('a[href="#"]').on('click', function() {
					var tabText = $(this).text().trim();
					if (tabText === 'Custom CSS & JS') {
						setTimeout(function() {
							refreshAllCodeMirrorEditors();
						}, 150);
					}
				});

				//Toggle white label fields visibility
				$('#ps-white-label-enabled').on('change', function() {
					if ($(this).is(':checked')) {
						$('.ps-white-label-fields').slideDown(300);
					} else {
						$('.ps-white-label-fields').slideUp(300);
					}
				});

				//WordPress Media Library Integration for Icon Upload
				var mediaUploader;
				
				$('#ps-upload-icon').on('click', function(e) {
					e.preventDefault();
					
					// If the uploader object has already been created, reopen the dialog
					if (mediaUploader) {
						mediaUploader.open();
						return;
					}
					
					// Create the media frame
					mediaUploader = wp.media.frames.file_frame = wp.media({
						title: 'Select Icon',
						button: {
							text: 'Use This Icon'
						},
						library: {
							type: ['image/jpeg', 'image/jpg', 'image/png', 'image/svg+xml']
						},
						multiple: false
					});
					
					// When an image is selected, run a callback
					mediaUploader.on('select', function() {
						var attachment = mediaUploader.state().get('selection').first().toJSON();
						
						// Set the hidden inputs
						$('#ps-white-label-icon').val(attachment.url);
						$('#ps-white-label-icon-id').val(attachment.id);
						
						// Update preview
						$('#ps-icon-preview-img').attr('src', attachment.url);
						$('.ps-icon-preview-container').show();
					});
					
					// Open the uploader dialog
					mediaUploader.open();
				});
				
				//Remove icon functionality
				$('#ps-remove-icon').on('click', function(e) {
					e.preventDefault();
					
					// Clear the hidden inputs
					$('#ps-white-label-icon').val('');
					$('#ps-white-label-icon-id').val('');
					
					// Hide preview
					$('.ps-icon-preview-container').hide();
					$('#ps-icon-preview-img').attr('src', '');
				});

				// WordPress Media Library Integration for Logo Upload
				var logoUploader;
				
				$('#ps-upload-logo').on('click', function(e) {
					e.preventDefault();
					
					// If the uploader object has already been created, reopen the dialog
					if (logoUploader) {
						logoUploader.open();
						return;
					}
					
					// Create the media frame
					logoUploader = wp.media.frames.file_frame = wp.media({
						title: 'Select Logo',
						button: {
							text: 'Use This Logo'
						},
						library: {
							type: ['image/jpeg', 'image/jpg', 'image/png', 'image/svg+xml']
						},
						multiple: false
					});
					
					// When an image is selected, run a callback
					logoUploader.on('select', function() {
						var attachment = logoUploader.state().get('selection').first().toJSON();
						
						// Set the hidden inputs
						$('#ps-white-label-logo').val(attachment.url);
						$('#ps-white-label-logo-id').val(attachment.id);
						
						// Update preview
						$('#ps-logo-preview-img').attr('src', attachment.url);
						$('.ps-logo-preview-container').show();
					});
					
					// Open the uploader dialog
					logoUploader.open();
				});
				
				// Remove logo functionality
				$('#ps-remove-logo').on('click', function(e) {
					e.preventDefault();
					
					// Clear the hidden inputs
					$('#ps-white-label-logo').val('');
					$('#ps-white-label-logo-id').val('');
					
					// Hide preview
					$('.ps-logo-preview-container').hide();
					$('#ps-logo-preview-img').attr('src', '');
				});

				//BDTPS_CORE_HIDE Warning when checkbox is enabled
				$('#ps-white-label-bdtps-hide').on('change', function() {
					if ($(this).is(':checked')) {
						// Show warning modal/alert
						var warningMessage = '⚠️ WARNING: ADVANCED FEATURE\n\n' +
							'Enabling BDTPS_CORE_HIDE will activate advanced white label mode that:\n\n' +
							'• Hides ALL Prime Slider branding and menus\n' +
							'• Makes these settings difficult to access later\n' +
							'• Requires the special access link to return\n\n' +
							'An email with access instructions will be sent if you proceed.\n\n' +
							'Are you sure you want to enable this advanced mode?';
						
						if (!confirm(warningMessage)) {
							// User cancelled, uncheck the box
							$(this).prop('checked', false);
							return false;
						}
						
						// Show additional info message
						if ($('#ps-bdtps-hide-info').length === 0) {
							$(this).closest('.ps-option-item').after(
								'<div id="ps-bdtps-hide-info" class="bdt-alert bdt-alert-warning bdt-margin-small-top">' +
								'<p><strong>BDTPS_CORE_HIDE Mode Enabled</strong></p>' +
								'<p>When you save these settings, an email will be sent with instructions to access white label settings in the future.</p>' +
								'</div>'
							);
						}
					} else {
						// Remove info message when unchecked
						$('#ps-bdtps-hide-info').remove();
					}
				});

				// Save white label settings with confirmation
				$('#ps-save-white-label').on('click', function(e) {
					e.preventDefault();
					
					// Check if button is disabled (no license or no white label eligible license)
					if ($(this).prop('disabled')) {
						var buttonText = $(this).text().trim();
						var alertMessage = '';
						
						if (buttonText.includes('License Not Activated')) {
							alertMessage = '<div class="bdt-alert bdt-alert-danger" bdt-alert>' +
								'<a href="#" class="bdt-alert-close" onclick="$(this).parent().parent().hide(); return false;">&times;</a>' +
								'<p><strong>License Not Activated</strong><br>You need to activate your Prime Slider license to access White Label functionality. Please activate your license first.</p>' +
								'</div>';
						} else {
							alertMessage = '<div class="bdt-alert bdt-alert-warning" bdt-alert>' +
								'<a href="#" class="bdt-alert-close" onclick="$(this).parent().parent().hide(); return false;">&times;</a>' +
								'<p><strong>Eligible License Required</strong><br>White Label functionality is available for Agency, Extended, Developer, AppSumo Lifetime, and other eligible license holders. Please upgrade your license to access these features.</p>' +
								'</div>';
						}
						
						$('#ps-white-label-message').html(alertMessage).show();
						return false;
					}
					
					// Check if white label mode is being enabled
					var whiteLabelEnabled = $('#ps-white-label-enabled').is(':checked');
					var bdtpsHideEnabled = $('#ps-white-label-bdtps-hide').is(':checked');
					
					// Only show confirmation dialog if white label is enabled AND BDTPS_CORE_HIDE is enabled
					if (whiteLabelEnabled && bdtpsHideEnabled) {
						var confirmMessage = '🔒 FINAL CONFIRMATION\n\n' +
							'You are about to save settings with BDTPS_CORE_HIDE enabled.\n\n' +
							'This will:\n' +
							'• Hide Prime Slider from WordPress admin immediately\n' +
							'• Send access instructions to your email addresses\n' +
							'• Require the special link to modify these settings\n\n' +
							'Email will be sent to:\n' +
							'• License email: <?php echo esc_js(self::get_license_email()); ?>\n' +
							'Are you absolutely sure you want to proceed?';
						
						if (!confirm(confirmMessage)) {
							return false;
						}
					}
					
					var $button = $(this);
					var originalText = $button.html();
					
					// Show loading state
					$button.html('Saving...');
					$button.prop('disabled', true);
					
					// Collect form data
					var formData = {
						action: 'ps_save_white_label',
						nonce: ps_admin_ajax.white_label_nonce,
						ps_white_label_enabled: $('#ps-white-label-enabled').is(':checked') ? 1 : 0,
						ps_white_label_title: $('#ps-white-label-title').val(),
						ps_white_label_icon: $('#ps-white-label-icon').val(),
						ps_white_label_icon_id: $('#ps-white-label-icon-id').val(),
						ps_white_label_hide_license: $('#ps-white-label-hide-license').is(':checked') ? 1 : 0,
						ps_white_label_bdtps_hide: $('#ps-white-label-bdtps-hide').is(':checked') ? 1 : 0,
						ps_white_label_logo: $('#ps-white-label-logo').val(),
						ps_white_label_logo_id: $('#ps-white-label-logo-id').val()
					};
					
					// Send AJAX request
					$.post(ps_admin_ajax.ajax_url, formData)
						.done(function(response) {
							if (response.success) {
								// Show success message with countdown
								var countdown = 2;
								var successMessage = response.data.message;
								
								// Add email notification info if BDTPS_CORE_HIDE was enabled
								if (response.data.bdtps_hide && response.data.email_sent) {
									successMessage += '<br><br><strong>📧 Access Email Sent!</strong><br>Check your email for the access link to modify these settings in the future.';
								} else if (response.data.bdtps_hide && !response.data.email_sent && response.data.access_url) {
									// Localhost scenario - show the access URL directly
									successMessage += '<br><br><strong>📧 Localhost Email Notice:</strong><br>Email functionality is not available on localhost.<br><strong>Your Access URL:</strong><br><a href="' + response.data.access_url + '" target="_blank">Click here to access white label settings</a><br><small>Save this URL - you\'ll need it to modify settings when BDTPS_CORE_HIDE is active.</small>';
								} else if (response.data.bdtps_hide && !response.data.email_sent) {
									successMessage += '<br><br><strong>⚠️ Email Notice:</strong><br>There was an issue sending the access email. Please check your email settings or contact support.';
								}
								
								$('#ps-white-label-message').html(
									'<div class="bdt-alert bdt-alert-success" bdt-alert>' +
									'<a href="#" class="bdt-alert-close" onclick="$(this).parent().parent().hide(); return false;">&times;</a>' +
									'<p>' + successMessage + ' <span id="ps-reload-countdown">Reloading in ' + countdown + ' seconds...</span></p>' +
									'</div>'
								).show();
								
								// Update button text
								$button.html('Reloading...');
								
								// Countdown timer
								var countdownInterval = setInterval(function() {
									countdown--;
									if (countdown > 0) {
										$('#ps-reload-countdown').text('Reloading in ' + countdown + ' seconds...');
									} else {
										$('#ps-reload-countdown').text('Reloading now...');
										clearInterval(countdownInterval);
									}
								}, 1000);
								
								// Check if BDTPS_CORE_HIDE is enabled and redirect accordingly
								setTimeout(function() {
									if (response.data.bdtps_hide) {
										// Redirect to admin dashboard if BDTPS_CORE_HIDE is enabled
										window.location.href = '<?php echo admin_url('index.php'); ?>';
									} else {
										// Reload current page if BDTPS_CORE_HIDE is not enabled
										window.location.reload();
									}
								}, 1500);
							} else {
								// Show error message
								$('#ps-white-label-message').html(
									'<div class="bdt-alert bdt-alert-danger" bdt-alert>' +
									'<a href="#" class="bdt-alert-close" onclick="$(this).parent().parent().hide(); return false;">&times;</a>' +
									'<p>Error: ' + (response.data.message || 'Unknown error occurred') + '</p>' +
									'</div>'
								).show();
								
								// Restore button state for error case
								$button.html(originalText);
								$button.prop('disabled', false);
							}
						})
						.fail(function(xhr, status, error) {
							// Show error message
							$('#ps-white-label-message').html(
								'<div class="bdt-alert bdt-alert-danger" bdt-alert>' +
								'<a href="#" class="bdt-alert-close" onclick="$(this).parent().parent().hide(); return false;">&times;</a>' +
								'<p>Error: Failed to save settings. Please try again. (' + status + ')</p>' +
								'</div>'
							).show();
							
							// Restore button state for failure case
							$button.html(originalText);
							$button.prop('disabled', false);
						});
				});

				// Save custom code functionality (updated for CodeMirror)
				$('#ps-save-custom-code').on('click', function(e) {
					e.preventDefault();
					
					var $button = $(this);
					var originalText = $button.html();
					
					// Check if ps_admin_ajax is available
					if (typeof ps_admin_ajax === 'undefined') {
						$('#ps-custom-code-message').html(
							'<div class="bdt-alert bdt-alert-danger" bdt-alert>' +
							'<a href="#" class="bdt-alert-close" onclick="$(this).parent().parent().hide(); return false;">&times;</a>' +
							'<p>Error: AJAX configuration not loaded. Please refresh the page and try again.</p>' +
							'</div>'
						).show();
						return;
					}
					
					// Prevent multiple simultaneous saves
					if ($button.prop('disabled') || $button.hasClass('ps-saving')) {
						return;
					}
					
					// Mark as saving
					$button.addClass('ps-saving');
					
					// Get content from CodeMirror editors
					function getCodeMirrorContent(elementId) {
						if (codeMirrorEditors[elementId] && codeMirrorEditors[elementId].codemirror) {
							return codeMirrorEditors[elementId].codemirror.getValue();
						} else {
							// Fallback to textarea value
							return $('#' + elementId).val() || '';
						}
					}
					
					var cssContent = getCodeMirrorContent('ps-custom-css');
					var jsContent = getCodeMirrorContent('ps-custom-js');
					var css2Content = getCodeMirrorContent('ps-custom-css-2');
					var js2Content = getCodeMirrorContent('ps-custom-js-2');
					
					// Show loading state
					$button.prop('disabled', true);
					
					// Timeout safeguard - if AJAX doesn't complete in 30 seconds, restore button
					var timeoutId = setTimeout(function() {
						$button.removeClass('ps-saving');
						$button.html(originalText);
						$button.prop('disabled', false);
						$('#ps-custom-code-message').html(
							'<div class="bdt-alert bdt-alert-warning" bdt-alert>' +
							'<a href="#" class="bdt-alert-close" onclick="$(this).parent().parent().hide(); return false;">&times;</a>' +
							'<p>Save operation timed out. Please try again.</p>' +
							'</div>'
						).show();
					}, 30000);
					
					// Collect form data
					var formData = {
						action: 'ps_save_custom_code',
						nonce: ps_admin_ajax.nonce,
						custom_css: cssContent,
						custom_js: jsContent,
						custom_css_2: css2Content,
						custom_js_2: js2Content,
						excluded_pages: $('#ps-excluded-pages').val() || []
					};
					
					
					// Verify we have some content before sending (optional check)
					var totalContentLength = cssContent.length + jsContent.length + css2Content.length + js2Content.length;
					if (totalContentLength === 0) {
						var confirmEmpty = confirm('No content detected in any editor. Do you want to save empty content (this will clear all custom code)?');
						if (!confirmEmpty) {
							// Restore button state
							$button.html(originalText);
							$button.prop('disabled', false);
							return;
						}
					}
					
					// Send AJAX request
					$.post(ps_admin_ajax.ajax_url, formData)
						.done(function(response) {
							console.log('AJAX Response:', response); // Debug log
							
							if (response && response.success) {
								// Show success message
								var successMessage = response.data.message;
								if (response.data.excluded_count) {
									successMessage += ' (' + response.data.excluded_count + ' pages excluded)';
								}
								
								$('#ps-custom-code-message').html(
									'<div class="bdt-alert bdt-alert-success" bdt-alert>' +
									'<a href="#" class="bdt-alert-close" onclick="$(this).parent().parent().hide(); return false;">&times;</a>' +
									'<p>' + successMessage + '</p>' +
									'</div>'
								).show();
								
								// Auto-hide message after 5 seconds
								setTimeout(function() {
									$('#ps-custom-code-message').fadeOut();
								}, 5000);
								
							} else {
								// Show error message
								var errorMessage = 'Unknown error occurred';
								if (response && response.data && response.data.message) {
									errorMessage = response.data.message;
								} else if (response && response.message) {
									errorMessage = response.message;
								}
								
								$('#ps-custom-code-message').html(
									'<div class="bdt-alert bdt-alert-danger" bdt-alert>' +
									'<a href="#" class="bdt-alert-close" onclick="$(this).parent().parent().hide(); return false;">&times;</a>' +
									'<p>Error: ' + errorMessage + '</p>' +
									'</div>'
								).show();
							}
						})
						.fail(function(xhr, status, error) {
							console.log('AJAX Error:', xhr, status, error); // Debug log
							
							// Try to parse error response
							var errorMessage = 'Failed to save custom code. Please try again.';
							try {
								var errorResponse = JSON.parse(xhr.responseText);
								if (errorResponse.data && errorResponse.data.message) {
									errorMessage = errorResponse.data.message;
								} else if (errorResponse.message) {
									errorMessage = errorResponse.message;
								}
							} catch (e) {
								// Use default error message
							}
							
							// Show error message
							$('#ps-custom-code-message').html(
								'<div class="bdt-alert bdt-alert-danger" bdt-alert>' +
								'<a href="#" class="bdt-alert-close" onclick="$(this).parent().parent().hide(); return false;">&times;</a>' +
								'<p>Error: ' + errorMessage + ' (' + status + ')</p>' +
								'</div>'
							).show();
						})
						.always(function() {
							
							// Clear the timeout since AJAX completed
							clearTimeout(timeoutId);
							
							try {
								$button.removeClass('ps-saving');
								$button.html(originalText);
								$button.prop('disabled', false);
							} catch (e) {
								// Fallback: force button restoration
								$('#ps-save-custom-code').removeClass('ps-saving').html('<span class="dashicons dashicons-yes"></span> Save Custom Code').prop('disabled', false);
							}
						});
				});

				// Reset custom code functionality (updated for CodeMirror)
				$('#ps-reset-custom-code').on('click', function(e) {
					e.preventDefault();
					
					if (confirm('Are you sure you want to reset all custom code? This will clear all code.')) {
						var $button = $(this);
						var originalText = $button.html();
						
						// Clear CodeMirror editors
						function clearCodeMirrorEditor(elementId) {
							if (codeMirrorEditors[elementId] && codeMirrorEditors[elementId].codemirror) {
								codeMirrorEditors[elementId].codemirror.setValue('');
							} else {
								// Fallback to clearing textarea
								$('#' + elementId).val('');
							}
						}
						
						// Clear all editors
						clearCodeMirrorEditor('ps-custom-css');
						clearCodeMirrorEditor('ps-custom-js');
						clearCodeMirrorEditor('ps-custom-css-2');
						clearCodeMirrorEditor('ps-custom-js-2');
						
						// Clear exclusions
						$('#ps-excluded-pages').val([]).trigger('change');
						
						// Show clearing message
						$('#ps-custom-code-message').html(
							'<div class="bdt-alert bdt-alert-primary" bdt-alert>' +
							'<p><span bdt-spinner="ratio: 0.6"></span> Clearing custom code...</p>' +
							'</div>'
						).show();
						
						// Disable button during save
						$button.prop('disabled', true).html('<span bdt-spinner="ratio: 0.6"></span> Resetting...');
						
						// Prepare empty data for AJAX save
						var formData = {
							action: 'ps_save_custom_code',
							nonce: ps_admin_ajax.nonce,
							custom_css: '',
							custom_js: '',
							custom_css_2: '',
							custom_js_2: '',
							excluded_pages: []
						};
						
						// Send AJAX request to save empty values
						$.ajax({
							url: ps_admin_ajax.ajax_url,
							type: 'POST',
							data: formData,
							timeout: 30000,
							success: function(response) {
								if (response.success) {
									// Show success message
									$('#ps-custom-code-message').html(
										'<div class="bdt-alert bdt-alert-success" bdt-alert>' +
										'<a href="#" class="bdt-alert-close" onclick="$(this).parent().parent().hide(); return false;">&times;</a>' +
										'<p><span class="dashicons dashicons-yes"></span> All custom code has been reset successfully!</p>' +
										'</div>'
									).show();
									
									// Auto-hide message after 5 seconds
									setTimeout(function() {
										$('#ps-custom-code-message').fadeOut();
									}, 5000);
								} else {
									// Show error message
									$('#ps-custom-code-message').html(
										'<div class="bdt-alert bdt-alert-danger" bdt-alert>' +
										'<a href="#" class="bdt-alert-close" onclick="$(this).parent().parent().hide(); return false;">&times;</a>' +
										'<p><span class="dashicons dashicons-warning"></span> ' + (response.data.message || 'Failed to save reset. Please try again.') + '</p>' +
										'</div>'
									).show();
								}
								
								// Restore button
								$button.prop('disabled', false).html(originalText);
							},
							error: function(xhr, status, error) {
								// Show error message
								$('#ps-custom-code-message').html(
									'<div class="bdt-alert bdt-alert-danger" bdt-alert>' +
									'<a href="#" class="bdt-alert-close" onclick="$(this).parent().parent().hide(); return false;">&times;</a>' +
									'<p><span class="dashicons dashicons-warning"></span> Failed to save reset: ' + error + '</p>' +
									'</div>'
								).show();
								
								// Restore button
								$button.prop('disabled', false).html(originalText);
							}
						});
					}
				});				
			});

			// Chart.js initialization for system status canvas charts
			function initPrimeSliderCharts() {
				// Wait for Chart.js to be available
				if (typeof Chart === 'undefined') {
					setTimeout(initPrimeSliderCharts, 500);
					return;
				}

				// Chart instances storage
				window.psChartInstances = window.psChartInstances || {};
				window.psChartsInitialized = false;

				// Function to create a chart
				function createChart(canvasId) {
					var canvas = document.getElementById(canvasId);
					if (!canvas) {
						return;
					}

					var $canvas = jQuery('#' + canvasId);
					var valueStr = $canvas.data('value');
					var labelsStr = $canvas.data('labels');
					var bgStr = $canvas.data('bg');

					if (!valueStr || !labelsStr || !bgStr) {
						return;
					}

					// Parse data
					var values = valueStr.toString().split(',').map(v => parseInt(v.trim()) || 0);
					var labels = labelsStr.toString().split(',').map(l => l.trim());
					var colors = bgStr.toString().split(',').map(c => c.trim());

					// Destroy existing chart using Chart.js built-in method
					var existingChart = Chart.getChart(canvas);
					if (existingChart) {
						existingChart.destroy();
					}

					// Also destroy from our instance storage
					if (window.psChartInstances && window.psChartInstances[canvasId]) {
						window.psChartInstances[canvasId].destroy();
						delete window.psChartInstances[canvasId];
					}

					// Create new chart
					try {
						var newChart = new Chart(canvas, {
							type: 'doughnut',
							data: {
								labels: labels,
								datasets: [{
									data: values,
									backgroundColor: colors,
									borderWidth: 0
								}]
							},
							options: {
								responsive: true,
								maintainAspectRatio: false,
								plugins: {
									legend: { display: false },
									tooltip: { enabled: true }
								},
								cutout: '60%'
							}
						});
						
						// Store in our instance storage
						if (!window.psChartInstances) window.psChartInstances = {};
						window.psChartInstances[canvasId] = newChart;
					} catch (error) {
						// Do nothing
					}
				}

				// Update total widgets status
				function updateTotalStatus() {
					var coreCount = jQuery('#prime_slider_active_modules_page input:checked').length;
					var thirdPartyCount = jQuery('#prime_slider_third_party_widget_page input:checked').length;
					var extensionsCount = jQuery('#prime_slider_elementor_extend_page input:checked').length;

					jQuery('#bdt-total-widgets-status-core').text(coreCount);
					jQuery('#bdt-total-widgets-status-3rd').text(thirdPartyCount);
					jQuery('#bdt-total-widgets-status-extensions').text(extensionsCount);
					jQuery('#bdt-total-widgets-status-heading').text(coreCount + extensionsCount);
					
					jQuery('#bdt-total-widgets-status').attr('data-value', [coreCount, extensionsCount].join(','));
				}

				// Initialize all charts once
				function initAllCharts() {
					// Check if charts already exist and are properly rendered
					if (window.psChartInstances && Object.keys(window.psChartInstances).length >= 4) {
						return;
					}
					
					// Update total status first
					updateTotalStatus();
					
					// Create all charts
					var chartCanvases = [
						'bdt-db-total-status',
						'bdt-db-only-widget-status', 
						'bdt-db-only-3rdparty-status',
						'bdt-total-widgets-status'
					];

					var successfulCharts = 0;
					chartCanvases.forEach(function(canvasId) {
						var canvas = document.getElementById(canvasId);
						if (canvas && canvas.offsetParent !== null) { // Check if canvas is visible
							createChart(canvasId);
							if (window.psChartInstances && window.psChartInstances[canvasId]) {
								successfulCharts++;
							}
						}
					});
				}

				// Check if we're currently on system status tab and initialize
				function checkAndInitIfOnSystemStatus() {
					if (window.location.hash === '#prime_slider_analytics_system_req') {
						setTimeout(initAllCharts, 300);
					}
				}

				// Initialize charts when DOM is ready
				jQuery(document).ready(function() {
					// Only initialize if we're on the system status tab
					setTimeout(checkAndInitIfOnSystemStatus, 500);
				});

				// Add click handler for System Status tab to create/refresh charts
				jQuery(document).on('click', 'a[href="#prime_slider_analytics_system_req"], a[href*="prime_slider_analytics_system_req"]', function() {
					setTimeout(function() {
						// Always recreate charts when tab is clicked to ensure they're visible
						initAllCharts();
					}, 200);
				});
			}

			// Start the chart initialization
			setTimeout(initPrimeSliderCharts, 1000);

			// Handle plugin installation via AJAX
			jQuery(document).on('click', '.ps-install-plugin', function(e) {
				e.preventDefault();
				
				var $button = jQuery(this);
				var pluginSlug = $button.data('plugin-slug');
				var nonce = $button.data('nonce');
				var originalText = $button.text();
				
				// Disable button and show loading state
				$button.prop('disabled', true)
					   .text('<?php echo esc_js(__('Installing...', 'bdthemes-prime-slider')); ?>')
					   .addClass('bdt-installing');
				
				// Perform AJAX request
				jQuery.ajax({
					url: '<?php echo admin_url('admin-ajax.php'); ?>',
					type: 'POST',
					data: {
						action: 'ps_install_plugin',
						plugin_slug: pluginSlug,
						nonce: nonce
					},
					success: function(response) {
						if (response.success) {
							// Show success message
							$button.text('<?php echo esc_js(__('Installed!', 'bdthemes-prime-slider')); ?>')
								   .removeClass('bdt-installing')
								   .addClass('bdt-installed');
							
							// Show success notification
							if (typeof bdtUIkit !== 'undefined' && bdtUIkit.notification) {
								bdtUIkit.notification({
									message: '<span class="dashicons dashicons-yes"></span> ' + response.data.message,
									status: 'success'
								});
							}
							
							// Reload the page after 2 seconds to update button states
							setTimeout(function() {
								window.location.reload();
							}, 2000);
							
						} else {
							// Show error message
							$button.prop('disabled', false)
								   .text(originalText)
								   .removeClass('bdt-installing');
							
							// Show error notification
							if (typeof bdtUIkit !== 'undefined' && bdtUIkit.notification) {
								bdtUIkit.notification({
									message: '<span class="dashicons dashicons-warning"></span> ' + response.data.message,
									status: 'danger'
								});
							}
						}
					},
					error: function() {
						// Handle network/server errors
						$button.prop('disabled', false)
							   .text(originalText)
							   .removeClass('bdt-installing');
						
						// Show error notification
						if (typeof bdtUIkit !== 'undefined' && bdtUIkit.notification) {
							bdtUIkit.notification({
								message: '<span class="dashicons dashicons-warning"></span> <?php echo esc_js(__('Installation failed. Please try again.', 'bdthemes-prime-slider')); ?>',
								status: 'danger'
							});
						}
					}
				});
			});

			// Show/hide white label & custom code save button based on active tab
			function toggleWhiteLabelSaveButton() {
				
				// Check if we're on the extra options page
				if (window.location.hash === '#prime_slider_extra_options') {
					// Target specifically the tabs within the Extra Options section
					var extraOptionsTabs = jQuery('.ps-extra-options-tabs .bdt-tab li.bdt-active');
					var activeTab = extraOptionsTabs.index();
					
					if (activeTab === 1) { // White Label tab is the second tab (index 1)
						jQuery('.ps-white-label-save-section').show();
						jQuery('.ps-code-save-section').hide();
					} else {
						jQuery('.ps-white-label-save-section').hide();
						jQuery('.ps-code-save-section').show();
					}
				} else {
					jQuery('.ps-white-label-save-section').hide();
					jQuery('.ps-code-save-section').hide();
				}
			}

			// Wait for jQuery to be ready
			jQuery(document).ready(function($) {
				
				// Check if we should automatically switch to White Label tab
				var urlParams = new URLSearchParams(window.location.search);
				if (urlParams.get('white_label_tab') === '1') {
					// Wait a bit for UIkit to be ready, then switch to White Label tab
					setTimeout(function() {
						// Use UIkit's API to switch to the second tab (index 1)
						var tabElement = document.querySelector('.ps-extra-options-tabs [bdt-tab]');
						if (tabElement && typeof UIkit !== 'undefined') {
							UIkit.tab(tabElement).show(1); // Show tab at index 1 (White Label tab)
						} else {
							// Fallback: simply click the White Label tab link
							var whiteLabelTab = $('.ps-extra-options-tabs .bdt-tab li').eq(1);
							if (whiteLabelTab.length > 0) {
								whiteLabelTab.find('a')[0].click(); // Use native click
							}
						}
						
						// Check button visibility after tab switch
						setTimeout(function() {
							toggleWhiteLabelSaveButton();
						}, 300);
					}, 800);
				} else {
					toggleWhiteLabelSaveButton();
				}
				
				// Check on hash change (when navigating to extra options page)
				$(window).on('hashchange', function() {
					toggleWhiteLabelSaveButton();
				});

				// Listen for UIkit tab changes using multiple methods
				$(document).on('click', '.bdt-tab li a', function() {
					setTimeout(function() {
						toggleWhiteLabelSaveButton();
					}, 200);
				});

				// Listen for UIkit's internal tab change events
				$(document).on('shown', '[bdt-tab]', function() {
					setTimeout(function() {
						toggleWhiteLabelSaveButton();
					}, 200);
				});

				// Also listen for the specific tab content changes
				$(document).on('show', '#ps-extra-options-tab-content > div', function() {
					setTimeout(function() {
						toggleWhiteLabelSaveButton();
					}, 200);
				});

				// Alternative: Check periodically for tab changes
				setInterval(function() {
					if (window.location.hash === '#prime_slider_extra_options') {
						var currentActiveTab = $('.bdt-tab li.bdt-active').index();
						if (typeof window.lastActiveTab === 'undefined') {
							window.lastActiveTab = currentActiveTab;
						} else if (window.lastActiveTab !== currentActiveTab) {
							window.lastActiveTab = currentActiveTab;
							toggleWhiteLabelSaveButton();
						}
					}
				}, 500);
			});
			
        </script>
    <?php
    }

	/**
	 * Display Footer
	 *
	 * @access public
	 * @return void
	 */

	function footer_info() {
	?>

		<div class="prime-slider-footer-info bdt-margin-medium-top">

			<div class="bdt-grid ">

				<div class="bdt-width-auto@s ps-setting-save-btn">



				</div>

				<div class="bdt-width-expand@s bdt-text-right">
					<p class="">
						<?php 
						echo esc_html__('Prime Slider plugin made with love by', 'bdthemes-prime-slider') . ' <a target="_blank" href="https://bdthemes.com">BdThemes</a> ' . esc_html__('Team.', 'bdthemes-prime-slider');
						echo '<br>';
						echo esc_html__('All rights reserved by', 'bdthemes-prime-slider') . ' <a target="_blank" href="https://bdthemes.com">BdThemes.com</a>.';
						?>
					</p>
				</div>
			</div>

		</div>

<?php
	}

	/**
	 * Get all the pages
	 *
	 * @return array page names with key value pairs
	 */
	function get_pages() {
		$pages         = get_pages();
		$pages_options = [];
		if ($pages) {
			foreach ($pages as $page) {
				$pages_options[$page->ID] = $page->post_title;
			}
		}

		return $pages_options;
	}

	/**
	 * Check if current license supports white label features
	 * Now includes other_param checking for AppSumo WL flag
	 * 
	 * @access public static
	 * @return bool
	 */
	public static function is_white_label_license() {
		// Check if pro version is activated first
		if (!function_exists('_is_ps_pro_activated') || !_is_ps_pro_activated()) {
			return false;
		}
		
		// Since PrimeSliderPro\Base doesn't exist, return false for now
		// This should be replaced with actual pro license checking logic when available
		$license_info = PrimeSliderPro\Base\Prime_Slider_Base::GetRegisterInfo();
		
		// Security: Validate license info structure
		if (empty($license_info) || 
			!is_object($license_info) || 
			empty($license_info->license_title) || 
			empty($license_info->is_valid)) {
			return false;
		}
		
		// Sanitize license title to prevent any potential issues
		$license_title = sanitize_text_field(strtolower($license_info->license_title));
		
		// Check for other_param WL flag FIRST (for AppSumo and other special licenses)
		if (!empty($license_info->other_param)) {
			// Check if other_param contains WL flag
			if (is_array($license_info->other_param)) {
				if (in_array('WL', $license_info->other_param, true)) {
					return true;
				}
			} elseif (is_string($license_info->other_param)) {
				if (strpos($license_info->other_param, 'WL') !== false) {
					return true;
				}
			}
		}
		
		// Check standard license types (but NOT AppSumo - AppSumo requires WL flag)
		$allowed_types = self::get_white_label_allowed_license_types();
		$allowed_hashes = array_values($allowed_types);
		
		// Split license title into words and check each word
		$words = preg_split('/\s+/', $license_title, -1, PREG_SPLIT_NO_EMPTY);
		foreach ($words as $word) {
			$word = trim($word);
			if (empty($word) || strlen($word) > 50) { // Prevent extremely long strings
				continue;
			}
			
			// Use SHA-256 for enhanced security
			$hash = hash('sha256', $word);
			if (in_array($hash, $allowed_hashes, true)) { // Strict comparison
				return true;
			}
		}
		
		return false;
	}

	/**
	 * Render White Label Section
	 * 
	 * @access public
	 * @return void
	 */
	public function render_white_label_section() {
		//// Safely check if helper functions exist
		$is_pro_installed = function_exists('_is_pro_pro_installed') ? _is_pro_pro_installed() : false;
		$is_pro_activated = function_exists('_is_ps_pro_activated') ? _is_ps_pro_activated() : false;
	
		// Define plugin slug (adjust if needed)
		$plugin_slug = 'bdthemes-prime-slider/bdthemes-prime-slider.php';
	
		// Case 1: Pro not installed
		if ( ! $is_pro_installed ) : ?>
			<div class="bdt-alert bdt-alert-danger bdt-margin-medium-top" bdt-alert>
				<p><?php esc_html_e( 'Prime Slider Pro is not installed. Please install it to access White Label functionality.', 'bdthemes-prime-slider' ); ?></p>
				<div class="bdt-margin-small-top">
					<a href="https://primeslider.pro/pricing/" target="_blank" class="bdt-button bdt-btn-blue">
						<?php esc_html_e( 'Get Pro', 'bdthemes-prime-slider' ); ?>
					</a>
				</div>
			</div>
			<?php
			return;
		endif;
	
		// Case 2: Installed but not active
		if ( $is_pro_installed && ! $is_pro_activated ) :
			// Generate secure activation link
			$activate_url = wp_nonce_url(
				add_query_arg(
					array(
						'action' => 'activate',
						'plugin' => $plugin_slug,
					),
					admin_url( 'plugins.php' )
				),
				'activate-plugin_' . $plugin_slug
			);
			?>
			<div class="bdt-alert bdt-alert-warning bdt-margin-medium-top" bdt-alert>
				<p><?php esc_html_e( 'Prime Slider Pro is installed but not activated. Please activate it to access White Label functionality.', 'bdthemes-prime-slider' ); ?></p>
				<div class="bdt-margin-small-top">
					<a href="<?php echo esc_url( $activate_url ); ?>" class="bdt-button bdt-btn-blue">
						<?php esc_html_e( 'Activate Pro', 'bdthemes-prime-slider' ); ?>
					</a>
				</div>
			</div>
			<?php
			return;
		endif;
		?>
		<div class="ps-white-label-section">
			<h1 class="ps-feature-title"><?php esc_html_e('White Label Settings', 'bdthemes-prime-slider'); ?></h1>
			<p><?php esc_html_e('Enable white label mode to hide Prime Slider branding from the admin interface and widgets.', 'bdthemes-prime-slider'); ?></p>

			<?php 

			$is_license_active = false;
			if ( function_exists( 'ps_license_validation' ) && true === ps_license_validation() ) {
				$is_license_active = true;
			}
			$is_white_label_eligible = self::is_white_label_license();
			
			// Show appropriate notices based on license status
			if (!$is_license_active): ?>
				<div class="bdt-alert bdt-alert-danger bdt-margin-medium-top" bdt-alert>
					<p><strong><?php esc_html_e('License Not Activated', 'bdthemes-prime-slider'); ?></strong></p>
					<p><?php esc_html_e('You need to activate your Prime Slider license to access White Label functionality. Please activate your license first.', 'bdthemes-prime-slider'); ?></p>
					<div class="bdt-margin-small-top">
						<a href="<?php echo esc_url(admin_url('admin.php?page=prime_slider_options#prime_slider_license_settings')); ?>" class="bdt-button bdt-btn-blue bdt-margin-small-right">
							<?php esc_html_e('Activate License', 'bdthemes-prime-slider'); ?>
						</a>
						<a href="https://primeslider.pro/pricing/" target="_blank" class="bdt-button bdt-btn-blue">
							<?php esc_html_e('Get License', 'bdthemes-prime-slider'); ?>
						</a>
					</div>
				</div>
			<?php elseif ($is_license_active && !$is_white_label_eligible): ?>
				<div class="bdt-alert bdt-alert-warning bdt-margin-medium-top" bdt-alert>
					<p><strong><?php esc_html_e('Eligible License Required', 'bdthemes-prime-slider'); ?></strong></p>
					<p><?php esc_html_e('White Label functionality is available for Agency, Extended, Developer, AppSumo Lifetime, and other eligible license holders. Some licenses may include special white label permissions.', 'bdthemes-prime-slider'); ?></p>
					<a href="https://primeslider.pro/pricing/" target="_blank" class="bdt-button bdt-btn-blue bdt-margin-small-top">
						<?php esc_html_e('Upgrade License', 'bdthemes-prime-slider'); ?>
					</a>
				</div>
			<?php endif; ?>

			<div class="ps-white-label-options <?php echo (!$is_license_active || !$is_white_label_eligible) ? 'ps-white-label-locked' : ''; ?>">
				<div class="ps-option-item ">
					<div class="ps-option-item-inner bdt-card">
						<div class="bdt-flex bdt-flex-between bdt-flex-middle">
							<div>
								<h3 class="ps-option-title"><?php esc_html_e('Enable White Label Mode', 'bdthemes-prime-slider'); ?></h3>
								<p class="ps-option-description">
									<?php if ($is_license_active && $is_white_label_eligible): ?>
										<?php esc_html_e('When enabled, Prime Slider branding will be hidden from the admin interface and widgets.', 'bdthemes-prime-slider'); ?>
									<?php elseif (!$is_license_active): ?>
										<?php esc_html_e('This feature requires an active Prime Slider license. Please activate your license first.', 'bdthemes-prime-slider'); ?>
									<?php else: ?>
										<?php esc_html_e('This feature requires an eligible license (Agency, Extended, Developer, AppSumo Lifetime, etc.). Upgrade your license to access white label functionality.', 'bdthemes-prime-slider'); ?>
									<?php endif; ?>
								</p>
							</div>
							<div class="ps-option-switch">
								<?php
								$white_label_enabled = ($is_license_active && $is_white_label_eligible) ? get_option('ps_white_label_enabled', false) : false;
								// Convert to boolean to ensure proper comparison
								$white_label_enabled = (bool) $white_label_enabled;
								?>
								<label class="switch">
									<input type="checkbox" 
										   id="ps-white-label-enabled" 
										   name="ps_white_label_enabled" 
										   <?php checked($white_label_enabled, true); ?>
										   <?php disabled(!$is_license_active || !$is_white_label_eligible); ?>>
									<span class="slider"></span>
								</label>
							</div>
						</div>
					</div>
				</div>

				<!-- White Label Title Field (conditional) -->
				<div class="ps-option-item ps-white-label-fields" style="<?php echo ($white_label_enabled && $is_license_active && $is_white_label_eligible) ? '' : 'display: none;'; ?>">
					<div class="ps-option-item-inner bdt-card">
						<div class="ps-white-label-title-section bdt-margin-medium-bottom">
							<h3 class="ps-option-title"><?php esc_html_e('White Label Title', 'bdthemes-prime-slider'); ?></h3>
							<p class="ps-option-description"><?php esc_html_e('Enter a custom title to replace "Prime Slider" branding throughout the plugin.', 'bdthemes-prime-slider'); ?></p>
							<div class="ps-white-label-input-wrapper bdt-margin-small-top">
								<input type="text" 
									   id="ps-white-label-title" 
									   name="ps_white_label_title" 
									   class="ps-white-label-input" 
									   placeholder="<?php esc_attr_e('Enter your custom title...', 'bdthemes-prime-slider'); ?>"
									   value="<?php echo esc_attr(get_option('ps_white_label_title', '')); ?>"
									   <?php disabled(!$is_license_active || !$is_white_label_eligible); ?>>
							</div>
						</div>

						<hr class="bdt-divider-small">
						
						<!-- White Label Title Icon Field -->
						<div class="ps-white-label-icon-section bdt-margin-medium-top">
							<h3 class="ps-option-title"><?php esc_html_e('White Label Title Icon', 'bdthemes-prime-slider'); ?></h3>
							<p class="ps-option-description"><?php esc_html_e('Upload a custom icon to replace the Prime Slider menu icon. Supports JPG, PNG, and SVG formats.', 'bdthemes-prime-slider'); ?></p>
							
							<div class="ps-icon-upload-wrapper bdt-margin-small-top">
								<?php 
								$icon_url = get_option('ps_white_label_icon', '');
								$icon_id = get_option('ps_white_label_icon_id', '');
								?>
								<div class="ps-icon-preview-container" style="<?php echo $icon_url ? '' : 'display: none;'; ?>">
									<div class="ps-icon-preview">
										<img id="ps-icon-preview-img" src="<?php echo esc_url($icon_url); ?>" alt="Icon Preview" style="max-width: 64px; max-height: 64px; border: 1px solid #ddd; border-radius: 4px; padding: 8px; background: #fff;">
									</div>
									<button type="button" id="ps-remove-icon" class="bdt-button bdt-btn-grey bdt-flex bdt-flex-middle bdt-margin-small-top" style="padding: 8px 12px; font-size: 12px;">
										<span class="dashicons dashicons-trash"></span>
										<?php esc_html_e('Remove', 'bdthemes-prime-slider'); ?>
									</button>
								</div>
								
								<div class="ps-icon-upload-container">
									<button type="button" id="ps-upload-icon" class="bdt-button bdt-btn-blue bdt-margin-small-top" <?php disabled(!$is_license_active || !$is_white_label_eligible); ?>>
										<span class="dashicons dashicons-cloud-upload"></span>
										<?php esc_html_e('Upload Icon', 'bdthemes-prime-slider'); ?>
									</button>
									<input type="hidden" id="ps-white-label-icon" name="ps_white_label_icon" value="<?php echo esc_attr($icon_url); ?>">
									<input type="hidden" id="ps-white-label-icon-id" name="ps_white_label_icon_id" value="<?php echo esc_attr($icon_id); ?>">
								</div>
							</div>

							<p class="ps-input-help">
								<?php esc_html_e('Recommended size: 20x20 pixels. The icon will be automatically resized to fit the WordPress admin menu. Supported formats: JPG, PNG, SVG.', 'bdthemes-prime-slider'); ?>
							</p>
						</div>

						<!-- White Label Plugin Logo Field -->
						<div class="ps-white-label-logo-section bdt-margin-medium-top">
							<h3 class="ps-option-title"><?php esc_html_e('Plugin Logo', 'bdthemes-prime-slider'); ?></h3>
							<p class="ps-option-description"><?php esc_html_e('Upload a custom logo to replace the Prime Slider logo in the admin header. Supports JPG, PNG, and SVG formats.', 'bdthemes-prime-slider'); ?></p>
							<div class="ps-icon-upload-wrapper-inner">
								<div class="ps-logo-upload-wrapper bdt-margin-small-top">
									<?php 
									$logo_url = get_option('ps_white_label_logo', '');
									$logo_id = get_option('ps_white_label_logo_id', '');
									?>
									<div class="ps-logo-preview-container" style="<?php echo $logo_url ? '' : 'display: none;'; ?>">
										<div class="ps-logo-preview">
											<img id="ps-logo-preview-img" src="<?php echo esc_url($logo_url); ?>" alt="Logo Preview" style="max-width: 200px; max-height: 64px; border: 1px solid #ddd; border-radius: 4px; padding: 8px; background: #fff;">
										</div>
										<button type="button" id="ps-remove-logo" class="bdt-button bdt-btn-grey bdt-flex bdt-flex-middle bdt-margin-small-top" style="padding: 8px 12px; font-size: 12px;">
											<span class="dashicons dashicons-trash"></span>
										</button>
									</div>
									
									<div class="ps-logo-upload-container">
										<button type="button" id="ps-upload-logo" class="bdt-button bdt-btn-blue bdt-margin-small-top" <?php disabled(!$is_license_active || !$is_white_label_eligible); ?>>
											<span class="dashicons dashicons-cloud-upload"></span>
											<?php esc_html_e('Upload Logo', 'bdthemes-prime-slider'); ?>
										</button>
										<input type="hidden" id="ps-white-label-logo" name="ps_white_label_logo" value="<?php echo esc_attr($logo_url); ?>">
										<input type="hidden" id="ps-white-label-logo-id" name="ps_white_label_logo_id" value="<?php echo esc_attr($logo_id); ?>">
									</div>
								</div>
								<p class="ps-input-help">
									<?php esc_html_e('Recommended size: 200x40 pixels. The logo will be displayed in the admin header. Supported formats: JPG, PNG, SVG.', 'bdthemes-prime-slider'); ?>
								</p>
							</div>
						</div>
					</div>
				</div>

				<!-- License Hide Option (conditional) -->
				<div class="ps-option-item ps-white-label-fields" style="<?php echo ($white_label_enabled && $is_license_active && $is_white_label_eligible) ? '' : 'display: none;'; ?>">
					<div class="ps-option-item-inner bdt-card">
						<div class="bdt-flex bdt-flex-between bdt-flex-middle">
							<div>
								<h3 class="ps-option-title"><?php esc_html_e('Hide License Menu', 'bdthemes-prime-slider'); ?></h3>
								<p class="ps-option-description"><?php esc_html_e('Hide the license menu from the admin sidebar when white label mode is enabled.', 'bdthemes-prime-slider'); ?></p>
							</div>
							<div class="ps-option-switch">
								<?php
								$hide_license = get_option('ps_white_label_hide_license', false);
								// Convert to boolean to ensure proper comparison
								$hide_license = (bool) $hide_license;
								?>
								<label class="switch">
									<input type="checkbox" 
										   id="ps-white-label-hide-license" 
										   name="ps_white_label_hide_license" 
										   <?php checked($hide_license, true); ?>
										   <?php disabled(!$is_license_active || !$is_white_label_eligible); ?>>
									<span class="slider"></span>
								</label>
							</div>
						</div>
					</div>
				</div>

				<!-- BDTPS_CORE_HIDE Option (conditional) -->
				<div class="ps-option-item ps-white-label-fields" style="<?php echo ($white_label_enabled && $is_license_active && $is_white_label_eligible) ? '' : 'display: none;'; ?>">
					<div class="ps-option-item-inner bdt-card">
						<div class="bdt-flex bdt-flex-between bdt-flex-middle">
							<div>
								<h3 class="ps-option-title"><?php esc_html_e('Enable BDTPS_CORE_HIDE Constant', 'bdthemes-prime-slider'); ?></h3>
								<p class="ps-option-description"><?php esc_html_e('Define the BDTPS_CORE_HIDE constant to hide additional Prime Slider branding and features throughout the plugin.', 'bdthemes-prime-slider'); ?></p>
								<?php 
								$bdtps_hide = get_option('ps_white_label_bdtps_hide', false);
								if ($bdtps_hide): ?>
									<div class="bdt-alert bdt-alert-warning bdt-margin-small-top">
										<p><strong>⚠️ BDTPS_CORE_HIDE Currently Active</strong></p>
										<p>Advanced white label mode is currently enabled. Prime Slider menus are hidden from the admin interface.</p>
									</div>
								<?php endif; ?>
							</div>
							<div class="ps-option-switch">
								<?php
								// Convert to boolean to ensure proper comparison
								$bdtps_hide = (bool) $bdtps_hide;
								?>
								<label class="switch">
									<input type="checkbox" 
										   id="ps-white-label-bdtps-hide" 
										   name="ps_white_label_bdtps_hide" 
										   <?php checked($bdtps_hide, true); ?>
										   <?php disabled(!$is_license_active || !$is_white_label_eligible); ?>>
									<span class="slider"></span>
								</label>
							</div>
						</div>
						<?php if (!$bdtps_hide && $is_license_active && $is_white_label_eligible): ?>
						<div class="bdt-margin-small-top">
							<div class="bdt-alert bdt-alert-danger">
								<p>When you enable BDTPS_CORE_HIDE, an email will be automatically sent to:</p>
								<ul style="margin: 10px 0;">
									<li><strong>License Email:</strong> <?php echo esc_html(self::get_license_email()); ?></li>
								</ul>
								<p>This email will contain a special access link that allows you to return to these settings even when BDTPS_CORE_HIDE is active.</p>
							</div>
						</div>
						<?php endif; ?>
					</div>
				</div>
				

				<!-- Success/Error Messages -->
				<div id="ps-white-label-message" class="ps-white-label-message bdt-margin-small-top" style="display: none;">
					<div class="bdt-alert bdt-alert-success" bdt-alert>
						<a href class="bdt-alert-close" bdt-close></a>
						<p><?php esc_html_e('White label settings saved successfully!', 'bdthemes-prime-slider'); ?></p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

    public static function license_wl_status() {
		$status = get_option('prime_slider_license_title_status');
		
		if ($status) {
			return true;
		}
		
		return false;
	}



    /**
	 * Display Analytics and System Requirements
	 *
	 * @access public
	 * @return void
	 */

	public function prime_slider_analytics_system_req_content() {
		?>
		<div class="ps-dashboard-panel"
			bdt-scrollspy="target: > div > div > .bdt-card; cls: bdt-animation-slide-bottom-small; delay: 300">
			<div class="ps-dashboard-analytics-system">

				<?php $this->prime_slider_widgets_status(); ?>

				<div class="bdt-grid bdt-grid-medium bdt-margin-medium-top" bdt-grid
					bdt-height-match="target: > div > .bdt-card">
					<div class="bdt-width-1-1">
						<div class="bdt-card bdt-card-body ps-system-requirement">
							<h1 class="ps-feature-title bdt-margin-small-bottom">
								<?php esc_html_e('System Requirement', 'bdthemes-prime-slider'); ?>
							</h1>
							<?php $this->prime_slider_system_requirement(); ?>
						</div>
					</div>
				</div>

			</div>
		</div>
		<?php
	}

    /**
	 * Widgets Status
	 */

	public function prime_slider_widgets_status() {
		$track_nw_msg = '';
		if (!Tracker::is_allow_track()) {
			$track_nw = esc_html__('This feature is not working because the Elementor Usage Data Sharing feature is Not Enabled.', 'bdthemes-prime-slider');
			$track_nw_msg = 'bdt-tooltip="' . $track_nw . '"';
		}
		?>
		<div class="ps-dashboard-widgets-status">
			<div class="bdt-grid bdt-grid-medium" bdt-grid bdt-height-match="target: > div > .bdt-card">
				<div class="bdt-width-1-2@m bdt-width-1-4@l">
					<div class="ps-widget-status bdt-card bdt-card-body" <?php echo wp_kses_post($track_nw_msg); ?>>

						<?php
						$used_widgets    = count(self::get_used_widgets());
						$un_used_widgets = count(self::get_unused_widgets());
						?>


						<div class="ps-count-canvas-wrap">
							<h1 class="ps-feature-title"><?php echo esc_html__('All Widgets', 'bdthemes-prime-slider'); ?></h1>
							<div class="bdt-flex bdt-flex-between bdt-flex-middle">
								<div class="ps-count-wrap">
									<div class="ps-widget-count"><?php echo esc_html__('Used:', 'bdthemes-prime-slider'); ?> <b>
											<?php echo esc_html($used_widgets); ?>
										</b></div>
									<div class="ps-widget-count"><?php echo esc_html__('Unused:', 'bdthemes-prime-slider'); ?> <b>
											<?php echo esc_html($un_used_widgets); ?>
										</b></div>
									<div class="ps-widget-count"><?php echo esc_html__('Total:', 'bdthemes-prime-slider'); ?>
										<b>
											<?php echo esc_html($used_widgets + $un_used_widgets); ?>
										</b>
									</div>
								</div>

								<div class="ps-canvas-wrap">
									<canvas id="bdt-db-total-status" style="height: 100px; width: 100px;" data-label="<?php echo esc_html__('Total Widgets Status', 'bdthemes-prime-slider'); ?> - (<?php echo esc_html($used_widgets + $un_used_widgets); ?>)" data-labels="<?php echo esc_attr('Used, Unused'); ?>" data-value="<?php echo esc_attr($used_widgets) . ',' . esc_attr($un_used_widgets); ?>" data-bg="#FFD166, #fff4d9" data-bg-hover="#0673e1, #e71522"></canvas>
								</div>
							</div>
						</div>

					</div>
				</div>
				<div class="bdt-width-1-2@m bdt-width-1-4@l">
					<div class="ps-widget-status bdt-card bdt-card-body" <?php echo wp_kses_post($track_nw_msg); ?>>

						<?php
						$used_only_widgets   = count(self::get_used_only_widgets());
						$unused_only_widgets = count(self::get_unused_only_widgets());
						?>


						<div class="ps-count-canvas-wrap">
							<h1 class="ps-feature-title"><?php echo esc_html__('Core', 'bdthemes-prime-slider'); ?></h1>
							<div class="bdt-flex bdt-flex-between bdt-flex-middle">
								<div class="ps-count-wrap">
									<div class="ps-widget-count"><?php echo esc_html__('Used:', 'bdthemes-prime-slider'); ?> <b>
											<?php echo esc_html($used_only_widgets); ?>
										</b></div>
									<div class="ps-widget-count"><?php echo esc_html__('Unused:', 'bdthemes-prime-slider'); ?> <b>
											<?php echo esc_html($unused_only_widgets); ?>
										</b></div>
									<div class="ps-widget-count"><?php echo esc_html__('Total:', 'bdthemes-prime-slider'); ?>
										<b>
											<?php echo esc_html($used_only_widgets + $unused_only_widgets); ?>
										</b>
									</div>
								</div>

								<div class="ps-canvas-wrap">
									<canvas id="bdt-db-only-widget-status" style="height: 100px; width: 100px;" data-label="<?php echo esc_html__('Core Widgets Status', 'bdthemes-prime-slider'); ?> - (<?php echo esc_attr($used_only_widgets + $unused_only_widgets); ?>)" data-labels="<?php echo esc_attr('Used, Unused'); ?>" data-value="<?php echo esc_attr($used_only_widgets) . ',' . esc_attr($unused_only_widgets); ?>" data-bg="#EF476F, #ffcdd9" data-bg-hover="#0673e1, #e71522"></canvas>
								</div>
							</div>
						</div>

					</div>
				</div>
				<div class="bdt-width-1-2@m bdt-width-1-4@l">
					<div class="ps-widget-status bdt-card bdt-card-body" <?php echo wp_kses_post($track_nw_msg); ?>>

						<?php
						$used_only_3rdparty   = count(self::get_used_only_3rdparty());
						$unused_only_3rdparty = count(self::get_unused_only_3rdparty());
						?>


						<div class="ps-count-canvas-wrap">
							<h1 class="ps-feature-title"><?php echo esc_html__('3rd Party', 'bdthemes-prime-slider'); ?></h1>
							<div class="bdt-flex bdt-flex-between bdt-flex-middle">
								<div class="ps-count-wrap">
									<div class="ps-widget-count"><?php echo esc_html__('Used:', 'bdthemes-prime-slider'); ?> <b>
											<?php echo esc_html($used_only_3rdparty); ?>
										</b></div>
									<div class="ps-widget-count"><?php echo esc_html__('Unused:', 'bdthemes-prime-slider'); ?> <b>
											<?php echo esc_html($unused_only_3rdparty); ?>
										</b></div>
									<div class="ps-widget-count"><?php echo esc_html__('Total:', 'bdthemes-prime-slider'); ?>
										<b>
											<?php echo esc_html($used_only_3rdparty + $unused_only_3rdparty); ?>
										</b>
									</div>
								</div>

								<div class="ps-canvas-wrap">
									<canvas id="bdt-db-only-3rdparty-status" style="height: 100px; width: 100px;" data-label="<?php echo esc_html__('3rd Party Widgets Status', 'bdthemes-prime-slider'); ?> - (<?php echo esc_attr($used_only_3rdparty + $unused_only_3rdparty); ?>)" data-labels="<?php echo esc_attr('Used, Unused'); ?>" data-value="<?php echo esc_attr($used_only_3rdparty) . ',' . esc_attr($unused_only_3rdparty); ?>" data-bg="#06D6A0, #B6FFEC" data-bg-hover="#0673e1, #e71522"></canvas>
								</div>
							</div>
						</div>

					</div>
				</div>

				<div class="bdt-width-1-2@m bdt-width-1-4@l">
					<div class="ps-widget-status bdt-card bdt-card-body" <?php echo wp_kses_post($track_nw_msg); ?>>

						<div class="ps-count-canvas-wrap">
							<h1 class="ps-feature-title"><?php echo esc_html__('Active', 'bdthemes-prime-slider'); ?></h1>
							<div class="bdt-flex bdt-flex-between bdt-flex-middle">
								<div class="ps-count-wrap">
									<div class="ps-widget-count"><?php echo esc_html__('Core:', 'bdthemes-prime-slider'); ?> <b id="bdt-total-widgets-status-core"></b></div>
									<div class="ps-widget-count"><?php echo esc_html__('3rd Party:', 'bdthemes-prime-slider'); ?> <b id="bdt-total-widgets-status-3rd"></b></div>
									<div class="ps-widget-count"><?php echo esc_html__('Total:', 'bdthemes-prime-slider'); ?> <b id="bdt-total-widgets-status-heading"></b></div>
								</div>

								<div class="ps-canvas-wrap">
									<canvas id="bdt-total-widgets-status" style="height: 100px; width: 100px;" data-label="<?php echo esc_html__('Total Active Widgets Status', 'bdthemes-prime-slider'); ?>" data-labels="<?php echo esc_attr('Core, 3rd Party'); ?>" data-bg="#0680d6, #B0EBFF" data-bg-hover="#0673e1, #B0EBFF">
									</canvas>
								</div>
							</div>
						</div>

					</div>
				</div>
			</div>
		</div>

		<?php if (!Tracker::is_allow_track()): ?>
			<div class="bdt-border-rounded bdt-box-shadow-small bdt-alert-warning" bdt-alert>
				<a href class="bdt-alert-close" bdt-close></a>
				<div class="bdt-text-default">
				<?php
					printf(
						esc_html__('To view widgets analytics, Elementor %1$sUsage Data Sharing%2$s feature by Elementor needs to be activated. Please activate the feature to get widget analytics instantly ', 'bdthemes-prime-slider'),
						'<b>', '</b>'
					);

					echo ' <a href="' . esc_url(admin_url('admin.php?page=elementor-settings')) . '">' . esc_html__('from here.', 'bdthemes-prime-slider') . '</a>';
				?>
				</div>
			</div>
		<?php endif; ?>

		<?php
	}

    /**
	 * Display System Requirement
	 *
	 * @access public
	 * @return void
	 */

	public function prime_slider_system_requirement() {
		$php_version = phpversion();
		$max_execution_time = ini_get('max_execution_time');
		$memory_limit = ini_get('memory_limit');
		$post_limit = ini_get('post_max_size');
		$uploads = wp_upload_dir();
		$upload_path = $uploads['basedir'];
		$yes_icon = '<span class="valid"><i class="dashicons-before dashicons-yes"></i></span>';
		$no_icon = '<span class="invalid"><i class="dashicons-before dashicons-no-alt"></i></span>';

		$environment = Utils::get_environment_info();

		?>
		<ul class="check-system-status bdt-grid bdt-child-width-1-2@m  bdt-grid-small ">
			<li>
				<div>
					<span class="label1"><?php esc_html_e('PHP Version:', 'bdthemes-prime-slider'); ?></span>

					<?php
					if (version_compare($php_version, '7.4.0', '<')) {
						echo wp_kses_post($no_icon);
						echo '<span class="label2" title="' . esc_attr__('Min: 7.4 Recommended', 'bdthemes-prime-slider') . '" bdt-tooltip>' . esc_html__('Currently:', 'bdthemes-prime-slider') . ' ' . esc_html($php_version) . '</span>';
					} else {
						echo wp_kses_post($yes_icon);
						echo '<span class="label2">' . esc_html__('Currently:', 'bdthemes-prime-slider') . ' ' . esc_html($php_version) . '</span>';
					}
					?>
				</div>

			</li>

			<li>
				<div>
					<span class="label1"><?php esc_html_e('Max execution time:', 'bdthemes-prime-slider'); ?> </span>
					<?php
					if ($max_execution_time < '90') {
						echo wp_kses_post($no_icon);
						echo '<span class="label2" title="' . esc_attr__('Min: 90 Recommended', 'bdthemes-prime-slider') . '" bdt-tooltip>' . esc_html__('Currently:', 'bdthemes-prime-slider') . ' ' . esc_html($max_execution_time) . '</span>';
					} else {
						echo wp_kses_post($yes_icon);
						echo '<span class="label2">' . esc_html__('Currently:', 'bdthemes-prime-slider') . ' ' . esc_html($max_execution_time) . '</span>';
					}
					?>
				</div>
			</li>
			<li>
				<div>
					<span class="label1"><?php esc_html_e('Memory Limit:', 'bdthemes-prime-slider'); ?> </span>

					<?php
					if (intval($memory_limit) < '512') {
						echo wp_kses_post($no_icon);
						echo '<span class="label2" title="' . esc_attr__('Min: 512M Recommended', 'bdthemes-prime-slider') . '" bdt-tooltip>' . esc_html__('Currently:', 'bdthemes-prime-slider') . ' ' . esc_html($memory_limit) . '</span>';
					} else {
						echo wp_kses_post($yes_icon);
						echo '<span class="label2">' . esc_html__('Currently:', 'bdthemes-prime-slider') . ' ' . esc_html($memory_limit) . '</span>';
					}
					?>
				</div>
			</li>

			<li>
				<div>
					<span class="label1"><?php esc_html_e('Max Post Limit:', 'bdthemes-prime-slider'); ?> </span>

					<?php
					if (intval($post_limit) < '32') {
						echo wp_kses_post($no_icon);
						echo '<span class="label2" title="' . esc_attr__('Min: 32M Recommended', 'bdthemes-prime-slider') . '" bdt-tooltip>' . esc_html__('Currently:', 'bdthemes-prime-slider') . ' ' . wp_kses_post($post_limit) . '</span>';
					} else {
						echo wp_kses_post($yes_icon);
						echo '<span class="label2">' . esc_html__('Currently:', 'bdthemes-prime-slider') . ' ' . wp_kses_post($post_limit) . '</span>';
					}
					?>
				</div>
			</li>

			<li>
				<div>
					<span class="label1"><?php esc_html_e('Uploads folder writable:', 'bdthemes-prime-slider'); ?></span>

					<?php
					if (!is_writable($upload_path)) {
						echo wp_kses_post($no_icon);
					} else {
						echo wp_kses_post($yes_icon);
					}
					?>
				</div>

			</li>

			<li>
				<div>
					<span class="label1"><?php esc_html_e('MultiSite:', 'bdthemes-prime-slider'); ?></span>

					<?php
					if ($environment['wp_multisite']) {
						echo wp_kses_post($yes_icon);
						echo '<span class="label2">' . esc_html__('MultiSite Enabled', 'bdthemes-prime-slider') . '</span>';
					} else {
						echo wp_kses_post($yes_icon);
						echo '<span class="label2">' . esc_html__('Single Site', 'bdthemes-prime-slider') . '</span>';
					}
					?>
				</div>
			</li>

			<li>
				<div>
					<span class="label1"><?php esc_html_e('GZip Enabled:', 'bdthemes-prime-slider'); ?></span>

					<?php
					if ($environment['gzip_enabled']) {
						echo wp_kses_post($yes_icon);
					} else {
						echo wp_kses_post($no_icon);
					}
					?>
				</div>

			</li>

			<li>
				<div>
					<span class="label1"><?php esc_html_e('Debug Mode:', 'bdthemes-prime-slider'); ?></span>
					<?php
					if ($environment['wp_debug_mode']) {
						echo wp_kses_post($no_icon);
						echo '<span class="label2">' . esc_html__('Currently Turned On', 'bdthemes-prime-slider') . '</span>';
					} else {
						echo wp_kses_post($yes_icon);
						echo '<span class="label2">' . esc_html__('Currently Turned Off', 'bdthemes-prime-slider') . '</span>';
					}
					?>
				</div>

			</li>

		</ul>

		<div class="bdt-admin-alert">
			<strong><?php esc_html_e('Note:', 'bdthemes-prime-slider'); ?></strong>
			<?php
			/* translators: %s: Plugin name 'Prime Slider' */
			printf(
				esc_html__('If you have multiple addons like %s so you may need to allocate additional memory for other addons as well.', 'bdthemes-prime-slider'),
				'<b>Prime Slider</b>'
			);
			?>
		</div>

		<?php
	}

    /**
	 * Others Plugin
	 */

	public function prime_slider_others_plugin() {
		// Include the Plugin Integration Helper and API Fetcher
		require_once BDTPS_CORE_INC_PATH . 'setup-wizard/class-plugin-api-fetcher.php';
		require_once BDTPS_CORE_INC_PATH . 'setup-wizard/class-plugin-integration-helper.php';

		// Define plugin slugs to fetch data for (same as integration view)
		$plugin_slugs = array(
			'bdthemes-element-pack-lite',
			'bdthemes-prime-slider-lite',
			'ultimate-store-kit',
			'zoloblocks',
			'pixel-gallery',
			'live-copy-paste',
			'spin-wheel',
			'ai-image',
			'dark-reader',
			'ar-viewer',
			'smart-admin-assistant',
			'website-accessibility',
		);

		// Get plugin data using the helper (same as integration view)
		$ps_plugins = \PrimeSlider\SetupWizard\Plugin_Integration_Helper::build_plugin_data($plugin_slugs);

		// Helper function for time formatting (same as integration view)
		if (!function_exists('format_last_updated')) {
			function format_last_updated($date_string) {
				if (empty($date_string)) {
					return __('Unknown', 'bdthemes-prime-slider');
				}
				
				$date = strtotime($date_string);
				if (!$date) {
					return __('Unknown', 'bdthemes-prime-slider');
				}
				
				$diff = current_time('timestamp') - $date;
				
				if ($diff < 60) {
					return __('Just now', 'bdthemes-prime-slider');
				} elseif ($diff < 3600) {
					$minutes = floor($diff / 60);
					return sprintf(_n('%d minute ago', '%d minutes ago', $minutes, 'bdthemes-prime-slider'), $minutes);
				} elseif ($diff < 86400) {
					$hours = floor($diff / 3600);
					return sprintf(_n('%d hour ago', '%d hours ago', $hours, 'bdthemes-prime-slider'), $hours);
				} elseif ($diff < 2592000) { // 30 days
					$days = floor($diff / 86400);
					return sprintf(_n('%d day ago', '%d days ago', $days, 'bdthemes-prime-slider'), $days);
				} elseif ($diff < 31536000) { // 1 year
					$months = floor($diff / 2592000);
					return sprintf(_n('%d month ago', '%d months ago', $months, 'bdthemes-prime-slider'), $months);
				} else {
					$years = floor($diff / 31536000);
					return sprintf(_n('%d year ago', '%d years ago', $years, 'bdthemes-prime-slider'), $years);
				}
			}
		}

		// Helper function for fallback URLs (same as integration view)
		if (!function_exists('get_plugin_fallback_urls')) {
			function get_plugin_fallback_urls($plugin_slug) {
				// Handle different plugin slug formats
				if (strpos($plugin_slug, '/') !== false) {
					// If it's a file path like 'plugin-name/plugin-name.php', extract directory
					$plugin_slug_clean = dirname($plugin_slug);
				} else {
					// If it's just the plugin directory name, use it directly
					$plugin_slug_clean = $plugin_slug;
				}
				
				// Custom icon URLs for specific plugins that might not be on WordPress.org
				$custom_icons = [
					'bdthemes-element-pack-lite' => [
						'https://ps.w.org/bdthemes-element-pack-lite/assets/icon-256x256.png',
						'https://ps.w.org/bdthemes-element-pack-lite/assets/icon-128x128.png',
					],
					'live-copy-paste' => [
						'https://ps.w.org/live-copy-paste/assets/icon-256x256.png',
						'https://ps.w.org/live-copy-paste/assets/icon-128x128.png',
					],
					'spin-wheel' => [
						'https://ps.w.org/spin-wheel/assets/icon-256x256.png',
						'https://ps.w.org/spin-wheel/assets/icon-128x128.png',
					],
					'ai-image' => [
						'https://ps.w.org/ai-image/assets/icon-256x256.png',
						'https://ps.w.org/ai-image/assets/icon-128x128.png',
					],
					'smart-admin-assistant' => [
						'https://ps.w.org/smart-admin-assistant/assets/icon-256x256.png',
						'https://ps.w.org/smart-admin-assistant/assets/icon-128x128.png',
					],
					'website-accessibility' => [
						'https://ps.w.org/website-accessibility/assets/icon-256x256.png',
						'https://ps.w.org/website-accessibility/assets/icon-128x128.png',
					],
				];
				
				// Return custom icons if available, otherwise use default WordPress.org URLs
				if (isset($custom_icons[$plugin_slug_clean])) {
					return $custom_icons[$plugin_slug_clean];
				}
				
				return [
					"https://ps.w.org/{$plugin_slug_clean}/assets/icon-256x256.gif",  // Try GIF first
					"https://ps.w.org/{$plugin_slug_clean}/assets/icon-256x256.png",  // Then PNG
					"https://ps.w.org/{$plugin_slug_clean}/assets/icon-128x128.gif",  // Medium GIF
					"https://ps.w.org/{$plugin_slug_clean}/assets/icon-128x128.png",  // Medium PNG
				];
			}
		}
		?>
		<div class="ps-dashboard-panel"
			bdt-scrollspy="target: > div > div > .bdt-card; cls: bdt-animation-slide-bottom-small; delay: 300">
			<div class="ps-dashboard-others-plugin">
				
				<?php foreach ($ps_plugins as $plugin) : 
					$is_active = is_plugin_active($plugin['slug']);
					// $is_recommended = $plugin['recommended'] && !$is_active;
					
					// Get plugin logo with fallback
					$logo_url = $plugin['logo'] ?? '';
					$plugin_name = $plugin['name'] ?? '';
					$plugin_slug = $plugin['slug'] ?? '';
					
					if (empty($logo_url) || !filter_var($logo_url, FILTER_VALIDATE_URL)) {
						// Generate fallback URLs for WordPress.org
						$actual_slug = str_replace('.php', '', basename($plugin_slug));
						$fallback_urls = get_plugin_fallback_urls($actual_slug);
						$logo_url = $fallback_urls[0];
					}
				?>
				
				<div class="bdt-card bdt-card-body bdt-flex bdt-flex-middle bdt-flex-between">
					<div class="bdt-others-plugin-content bdt-flex bdt-flex-middle">
						<div class="bdt-plugin-logo-wrap bdt-flex bdt-flex-middle">
							<div class="bdt-plugin-logo-container">
								<img src="<?php echo esc_url($logo_url); ?>" 
									alt="<?php echo esc_attr($plugin_name); ?>" 
									class="bdt-plugin-logo"
									onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
								<div class="default-plugin-icon" style="display:none;">📦</div>
							</div>

							<div class="bdt-others-plugin-user-wrap bdt-flex bdt-flex-middle">
								<h1 class="ps-feature-title"><?php echo esc_html($plugin_name); ?></h1>
								
								<!-- <?php //if ($is_active) : ?>
									<span class="bdt-others-plugin-active"><?php //esc_html_e('ACTIVE', 'bdthemes-prime-slider'); ?></span>
								<?php //endif; ?> -->
								
							</div>
						</div>	
						<div class="bdt-others-plugin-content-text">
							
							
							
							
							
							<?php if (!empty($plugin['description'])) : ?>
								<p><?php echo esc_html($plugin['description']); ?></p>
							<?php endif; ?>

							<span class="active-installs bdt-margin-small-top">
								<?php esc_html_e('Active Installs: ', 'bdthemes-prime-slider'); 
								// echo wp_kses_post($plugin['active_installs'] ?? '0'); 
								if (isset($plugin['active_installs_count']) && $plugin['active_installs_count'] > 0) {
									echo ' <span class="installs-count">' . number_format($plugin['active_installs_count']) . '+' . '</span>';
								} else {
									echo ' <span class="installs-count">Fewer than 10' . '</span>';
								}
								?>
							</span>

							<?php if (isset($plugin['downloaded_formatted']) && !empty($plugin['downloaded_formatted'])): ?>
								<div class="downloads bdt-margin-small-top">
									<span><?php esc_html_e('Downloads: ', 'bdthemes-prime-slider'); ?><?php echo esc_html($plugin['downloaded_formatted']); ?></span>
								</div>
							<?php endif; ?>

							<div class="bdt-others-plugin-rating bdt-margin-small-top bdt-flex bdt-flex-middle">
								<span class="bdt-others-plugin-rating-stars">
									<?php 
									$rating = floatval($plugin['rating'] ?? 0);
									$full_stars = floor($rating);
									$has_half_star = ($rating - $full_stars) >= 0.5;
									$empty_stars = 5 - $full_stars - ($has_half_star ? 1 : 0);
									
									// Full stars
									for ($i = 0; $i < $full_stars; $i++) {
										echo '<i class="dashicons dashicons-star-filled"></i>';
									}
									
									// Half star
									if ($has_half_star) {
										echo '<i class="dashicons dashicons-star-half"></i>';
									}
									
									// Empty stars
									for ($i = 0; $i < $empty_stars; $i++) {
										echo '<i class="dashicons dashicons-star-empty"></i>';
									}
									?>
								</span>
								<span class="bdt-others-plugin-rating-text bdt-margin-small-left">
									<?php echo esc_html($plugin['rating'] ?? '0'); ?> <?php esc_html_e('out of 5 stars.', 'bdthemes-prime-slider'); ?>
									<?php if (isset($plugin['num_ratings']) && $plugin['num_ratings'] > 0): ?>
										<span class="rating-count">(<?php echo number_format($plugin['num_ratings']); ?> <?php esc_html_e('ratings', 'bdthemes-prime-slider'); ?>)</span>
									<?php endif; ?>
								</span>
							</div>
							
							<?php if (isset($plugin['last_updated']) && !empty($plugin['last_updated'])): ?>
								<div class="bdt-others-plugin-updated bdt-margin-small-top">
									<span><?php esc_html_e('Last Updated: ', 'bdthemes-prime-slider'); ?><?php echo esc_html(format_last_updated($plugin['last_updated'])); ?></span>
								</div>
							<?php endif; ?>
						</div>
					</div>
				
					<div class="bdt-others-plugins-link">
						<?php echo $this->get_plugin_action_button($plugin['slug'], 'https://wordpress.org/plugins/' . dirname($plugin['slug']) . '/'); ?>
						<?php if (!empty($plugin['homepage'])) : ?>
							<a class="bdt-button bdt-dashboard-sec-btn" target="_blank" href="<?php echo esc_url($plugin['homepage']); ?>">
								<?php esc_html_e('Learn More', 'bdthemes-prime-slider'); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
				
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

    /**
	 * Check plugin status (installed, active, or not installed)
	 * 
	 * @param string $plugin_path Plugin file path
	 * @return string 'active', 'installed', or 'not_installed'
	 */
	private function get_plugin_status($plugin_path) {
		// Check if plugin is active
		if (is_plugin_active($plugin_path)) {
			return 'active';
		}
		
		// Check if plugin is installed but not active
		$installed_plugins = get_plugins();
		if (isset($installed_plugins[$plugin_path])) {
			return 'installed';
		}
		
		// Plugin is not installed
		return 'not_installed';
	}

	/**
	 * AJAX handler for saving custom code
	 * 
	 * @access public
	 * @return void
	 */
	public function save_custom_code_ajax() {
		// Verify nonce
		if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'ps_custom_code_nonce' ) ) {
			wp_send_json_error( [ 'message' => 'Invalid security token.' ] );
		}

		// Check user capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Insufficient permissions.' ] );
		}

		// Sanitize and save the custom code
		$custom_css = isset( $_POST['custom_css'] ) ? wp_unslash( $_POST['custom_css'] ) : '';
		$custom_js = isset( $_POST['custom_js'] ) ? wp_unslash( $_POST['custom_js'] ) : '';
		$custom_css_2 = isset( $_POST['custom_css_2'] ) ? wp_unslash( $_POST['custom_css_2'] ) : '';
		$custom_js_2 = isset( $_POST['custom_js_2'] ) ? wp_unslash( $_POST['custom_js_2'] ) : '';

		// Handle excluded pages - ensure we get proper array format
		$excluded_pages = array();
		if ( isset( $_POST['excluded_pages'] ) ) {
			if ( is_array( $_POST['excluded_pages'] ) ) {
				$excluded_pages = $_POST['excluded_pages'];
			} elseif ( is_string( $_POST['excluded_pages'] ) && ! empty( $_POST['excluded_pages'] ) ) {
				// Handle case where it might be a single value
				$excluded_pages = [ $_POST['excluded_pages'] ];
			}
		}
		
		// Sanitize excluded pages - convert to integers and remove empty values
		$excluded_pages = array_map( 'intval', $excluded_pages );
		$excluded_pages = array_filter( $excluded_pages, function( $page_id ) {
			return $page_id > 0;
		} );

		// Save to database
		update_option( 'ps_custom_css', $custom_css );
		update_option( 'ps_custom_js', $custom_js );
		update_option( 'ps_custom_css_2', $custom_css_2 );
		update_option( 'ps_custom_js_2', $custom_js_2 );
		update_option( 'ps_excluded_pages', $excluded_pages );

		wp_send_json_success( [ 
			'message' => 'Custom code saved successfully!',
			'excluded_count' => count( $excluded_pages )
		] );
	}

	/**
	 * Handle AJAX plugin installation
	 * 
	 * @access public
	 * @return void
	 */
	public function install_plugin_ajax() {
		// Check nonce
		if (!wp_verify_nonce($_POST['nonce'], 'ps_install_plugin_nonce')) {
			wp_send_json_error(['message' => __('Security check failed', 'bdthemes-prime-slider')]);
		}

		// Check user capability
		if (!current_user_can('install_plugins')) {
			wp_send_json_error(['message' => __('You do not have permission to install plugins', 'bdthemes-prime-slider')]);
		}

		$plugin_slug = sanitize_text_field($_POST['plugin_slug']);

		if (empty($plugin_slug)) {
			wp_send_json_error(['message' => __('Plugin slug is required', 'bdthemes-prime-slider')]);
		}

		// Include necessary WordPress files
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';

		// Get plugin information
		$api = plugins_api('plugin_information', [
			'slug' => $plugin_slug,
			'fields' => [
				'sections' => false,
			],
		]);

		if (is_wp_error($api)) {
			wp_send_json_error(['message' => __('Plugin not found: ', 'bdthemes-prime-slider') . $api->get_error_message()]);
		}

		// Install the plugin
		$skin = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader($skin);
		$result = $upgrader->install($api->download_link);

		if (is_wp_error($result)) {
			wp_send_json_error(['message' => __('Installation failed: ', 'bdthemes-prime-slider') . $result->get_error_message()]);
		} elseif ($skin->get_errors()->has_errors()) {
			wp_send_json_error(['message' => __('Installation failed: ', 'bdthemes-prime-slider') . $skin->get_error_messages()]);
		} elseif (is_null($result)) {
			wp_send_json_error(['message' => __('Installation failed: Unable to connect to filesystem', 'bdthemes-prime-slider')]);
		}

		// Get installation status
		$install_status = install_plugin_install_status($api);
		
		wp_send_json_success([
			'message' => __('Plugin installed successfully!', 'bdthemes-prime-slider'),
			'plugin_file' => $install_status['file'],
			'plugin_name' => $api->name
		]);
	}

    /**
	 * Extract plugin slug from plugin path
	 * 
	 * @param string $plugin_path Plugin file path
	 * @return string Plugin slug
	 */
	private function extract_plugin_slug_from_path($plugin_path) {
		$parts = explode('/', $plugin_path);
		return isset($parts[0]) ? $parts[0] : '';
	}

    /**
	 * Get plugin action button HTML based on plugin status
	 * 
	 * @param string $plugin_path Plugin file path
	 * @param string $install_url Plugin installation URL
	 * @param string $plugin_slug Plugin slug for activation
	 * @return string Button HTML
	 */
	private function get_plugin_action_button($plugin_path, $install_url, $plugin_slug = '') {
		$status = $this->get_plugin_status($plugin_path);
		
		switch ($status) {
			case 'active':
				return '';
				
			case 'installed':
				$activate_url = wp_nonce_url(
					add_query_arg([
						'action' => 'activate',
						'plugin' => $plugin_path
					], admin_url('plugins.php')),
					'activate-plugin_' . $plugin_path
				);
				return '<a class="bdt-button bdt-welcome-button" href="' . esc_url($activate_url) . '">' . 
				       __('Activate', 'bdthemes-prime-slider') . '</a>';
				
			case 'not_installed':
			default:
				$plugin_slug = $this->extract_plugin_slug_from_path($plugin_path);
				$nonce = wp_create_nonce('ps_install_plugin_nonce');
				return '<a class="bdt-button bdt-welcome-button ps-install-plugin" 
				          data-plugin-slug="' . esc_attr($plugin_slug) . '" 
				          data-nonce="' . esc_attr($nonce) . '" 
				          href="#">' . 
				       __('Install', 'bdthemes-prime-slider') . '</a>';
		}
	}

    /**
	 * Extra Options Start Here
	 */

	/**
	 * Render Custom CSS & JS Section
	 * 
	 * @access public
	 * @return void
	 */
	public function render_custom_css_js_section() {
		?>
		<div class="ps-custom-code-section">
			<!-- Header Section -->
			<div class="ps-code-section-header">
				<h2 class="ps-section-title"><?php esc_html_e('Header Code Injection', 'bdthemes-prime-slider'); ?></h2>
				<p class="ps-section-description"><?php esc_html_e('Code added here will be injected into the &lt;head&gt; section of your website.', 'bdthemes-prime-slider'); ?></p>
			</div>
			<div class="ps-code-row bdt-grid bdt-grid-small" bdt-grid>
				<div class="bdt-width-1-2@m">
					<div class="ps-code-editor-wrapper">
						<h3 class="ps-code-editor-title"><?php esc_html_e('CSS', 'bdthemes-prime-slider'); ?></h3>
						<p class="ps-code-editor-description"><?php esc_html_e('Enter raw CSS code without &lt;style&gt; tags.', 'bdthemes-prime-slider'); ?></p>
						<div class="ps-codemirror-editor-container">
							<textarea id="ps-custom-css" name="ps_custom_css" class="ps-code-editor" data-mode="css" placeholder=".example {&#10;    background: red;&#10;    border-radius: 5px;&#10;    padding: 15px;&#10;}&#10;&#10;"><?php echo esc_textarea(get_option('ps_custom_css', '')); ?></textarea>
						</div>
					</div>
				</div>
				<div class="bdt-width-1-2@m">
					<div class="ps-code-editor-wrapper">
						<h3 class="ps-code-editor-title"><?php esc_html_e('JS', 'bdthemes-prime-slider'); ?></h3>
						<p class="ps-code-editor-description"><?php esc_html_e('Enter raw JavaScript code without &lt;script&gt; tags.', 'bdthemes-prime-slider'); ?></p>
						<div class="ps-codemirror-editor-container">
							<textarea id="ps-custom-js" name="ps_custom_js" class="ps-code-editor" data-mode="javascript" placeholder="alert('Hello, Prime Slider!');"><?php echo esc_textarea(get_option('ps_custom_js', '')); ?></textarea>
						</div>
					</div>
				</div>
			</div>

			<!-- Footer Section -->
			<div class="ps-code-section-header bdt-margin-medium-top">
				<h2 class="ps-section-title"><?php esc_html_e('Footer Code Injection', 'bdthemes-prime-slider'); ?></h2>
				<p class="ps-section-description"><?php esc_html_e('Code added here will be injected before the closing &lt;/body&gt; tag of your website.', 'bdthemes-prime-slider'); ?></p>
			</div>
			<div class="ps-code-row bdt-grid bdt-grid-small bdt-margin-small-top" bdt-grid>
				<div class="bdt-width-1-2@m">
					<div class="ps-code-editor-wrapper">
						<h3 class="ps-code-editor-title"><?php esc_html_e('CSS', 'bdthemes-prime-slider'); ?></h3>
						<p class="ps-code-editor-description"><?php esc_html_e('Enter raw CSS code without &lt;style&gt; tags.', 'bdthemes-prime-slider'); ?></p>
						<div class="ps-codemirror-editor-container">
							<textarea id="ps-custom-css-2" name="ps_custom_css_2" class="ps-code-editor" data-mode="css" placeholder=".example {&#10;    background: green;&#10;}&#10;&#10;"><?php echo esc_textarea(get_option('ps_custom_css_2', '')); ?></textarea>
						</div>
					</div>
				</div>
				<div class="bdt-width-1-2@m">
					<div class="ps-code-editor-wrapper">
						<h3 class="ps-code-editor-title"><?php esc_html_e('JS', 'bdthemes-prime-slider'); ?></h3>
						<p class="ps-code-editor-description"><?php esc_html_e('Enter raw JavaScript code without &lt;script&gt; tags.', 'bdthemes-prime-slider'); ?></p>
						<div class="ps-codemirror-editor-container">
							<textarea id="ps-custom-js-2" name="ps_custom_js_2" class="ps-code-editor" data-mode="javascript" placeholder="console.log('Hello, Prime Slider!');"><?php echo esc_textarea(get_option('ps_custom_js_2', '')); ?></textarea>
						</div>
					</div>
				</div>
			</div>

			<!-- Page Exclusion Section -->
			<div class="ps-code-section-header bdt-margin-medium-top">
				<h2 class="ps-section-title"><?php esc_html_e('Page & Post Exclusion Settings', 'bdthemes-prime-slider'); ?></h2>
				<p class="ps-section-description"><?php esc_html_e('Select pages and posts where you don\'t want any custom code to be injected. This applies to all sections above.', 'bdthemes-prime-slider'); ?></p>
			</div>
			<div class="ps-page-exclusion-wrapper">
				<label for="ps-excluded-pages" class="ps-exclusion-label">
					<?php esc_html_e('Exclude Pages & Posts:', 'bdthemes-prime-slider'); ?>
				</label>
				<select id="ps-excluded-pages" name="ps_excluded_pages[]" multiple class="ps-page-select">
					<option value=""><?php esc_html_e('-- Select pages/posts to exclude --', 'bdthemes-prime-slider'); ?></option>
					<?php
					$excluded_pages = get_option('ps_excluded_pages', array());
					if (!is_array($excluded_pages)) {
						$excluded_pages = array();
					}
					
					// Get all published pages
					$pages = get_pages(array(
						'sort_order' => 'ASC',
						'sort_column' => 'post_title',
						'post_status' => 'publish'
					));
					
					// Get recent posts (last 50)
					$posts = get_posts(array(
						'numberposts' => 50,
						'post_status' => 'publish',
						'post_type' => 'post',
						'orderby' => 'date',
						'order' => 'DESC'
					));
					
					// Display pages first
					if (!empty($pages)) {
						echo '<optgroup label="' . esc_attr__('Pages', 'bdthemes-prime-slider') . '">';
						foreach ($pages as $page) {
							$selected = in_array($page->ID, $excluded_pages) ? 'selected' : '';
							echo '<option value="' . esc_attr($page->ID) . '" ' . $selected . '>' . esc_html($page->post_title) . '</option>';
						}
						echo '</optgroup>';
					}
					
					// Then display posts
					if (!empty($posts)) {
						echo '<optgroup label="' . esc_attr__('Recent Posts', 'bdthemes-prime-slider') . '">';
						foreach ($posts as $post) {
							$selected = in_array($post->ID, $excluded_pages) ? 'selected' : '';
							$post_date = date('M j, Y', strtotime($post->post_date));
							echo '<option value="' . esc_attr($post->ID) . '" ' . $selected . '>' . esc_html($post->post_title) . ' (' . $post_date . ')</option>';
						}
						echo '</optgroup>';
					}
					?>
				</select>
				<p class="ps-exclusion-help">
					<?php esc_html_e('Hold Ctrl (or Cmd on Mac) to select multiple items. Selected pages and posts will not load any custom CSS or JavaScript code. The list shows all pages and the 50 most recent posts.', 'bdthemes-prime-slider'); ?>
				</p>
			</div>

			<!-- Success/Error Messages -->
			<div id="ps-custom-code-message" class="ps-code-message bdt-margin-small-top" style="display: none;">
				<div class="bdt-alert bdt-alert-success" bdt-alert>
					<a href class="bdt-alert-close" bdt-close></a>
					<p><?php esc_html_e('Custom code saved successfully!', 'bdthemes-prime-slider'); ?></p>
				</div>
			</div>
		</div>
		<?php
	}

    /**
	 * Extra Options Start Here
	 */

	public function prime_slider_extra_options() {
		?>
		<div class="ps-dashboard-panel"
			bdt-scrollspy="target: > div > div > .bdt-card; cls: bdt-animation-slide-bottom-small; delay: 300">
			<div class="ps-dashboard-extra-options">
				<div class="bdt-card bdt-card-body">
					<h1 class="ps-feature-title"><?php esc_html_e('Extra Options', 'bdthemes-prime-slider'); ?></h1>

					<div class="ps-extra-options-tabs">
						<ul class="bdt-tab" bdt-tab="connect: #ps-extra-options-tab-content; animation: bdt-animation-fade">
							<li class="bdt-active"><a
									href="#"><?php esc_html_e('Custom CSS & JS', 'bdthemes-prime-slider'); ?></a></li>
							<li><a href="#"><?php esc_html_e('White Label', 'bdthemes-prime-slider'); ?></a></li>
						</ul>

						<div id="ps-extra-options-tab-content" class="bdt-switcher">
							<!-- Custom CSS & JS Tab -->
							<div>
								<?php $this->render_custom_css_js_section(); ?>
							</div>
							
							<!-- White Label Tab -->
							<div>
								<?php $this->render_white_label_section(); ?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}


    /**
	 * Rollback Version Content
	 *
	 * @access public
	 * @return void
	 */
	public function prime_slider_rollback_version_content() {
		// Use the already initialized rollback version instance
		$this->rollback_version->prime_slider_rollback_version_content();
	}

	/**
	 * Get allowed white label license types (SHA-256 hashes)
	 * This centralized method makes it easy to add new license types in the future
	 * Note: AppSumo and Lifetime licenses require WL flag in other_param instead of automatic access
	 * 
	 * @access public static
	 * @return array Array of SHA-256 hashes for allowed license types
	 */
	public static function get_white_label_allowed_license_types() {
		$allowed_types = [
			'agency' => 'c4b2af4722ee54e317672875b2d8cf49aa884bf5820ec6091114fea5ec6560e4',
			'extended' => '4d7120eb6c796b04273577476eb2e20c34c51d7fa1025ec19c3414448abc241e',
			'developer' => '88fa0d759f845b47c044c2cd44e29082cf6fea665c30c146374ec7c8f3d699e3',
			// Note: AppSumo and Lifetime licenses removed from automatic access
			// They require WL flag in other_param for white label functionality
		];

		return $allowed_types;
	}

	/**
	 * Revoke white label access token
	 * 
	 * @access public
	 * @return bool
	 */
	public function revoke_white_label_access_token() {
		$token_data = get_option( 'ps_white_label_access_token', [] );
		
		if ( ! empty( $token_data ) ) {
			delete_option( 'ps_white_label_access_token' );
			return true;
		}
		
		return false;
	}

	/**
	 * Validate white label access token
	 * 
	 * @access public
	 * @param string $token
	 * @return bool
	 */
	public function validate_white_label_access_token( $token ) {
		$stored_token_data = get_option( 'ps_white_label_access_token', [] );
		
		if ( empty( $stored_token_data ) || ! isset( $stored_token_data['token'] ) ) {
			return false;
		}
		
		// Check token match
		if ( $stored_token_data['token'] !== $token ) {
			return false;
		}
		
		// Check if token was generated for current license
		$current_license_key = self::get_license_key();
		if ( $stored_token_data['license_key'] !== $current_license_key ) {
			return false;
		}
		
		return true;
	}

	/**
	 * AJAX handler for revoking white label access token
	 * 
	 * @access public
	 * @return void
	 */
	public function revoke_white_label_token_ajax() {
		// Check nonce and permissions
		if (!wp_verify_nonce($_POST['nonce'], 'ps_white_label_nonce')) {
			wp_send_json_error(['message' => __('Security check failed', 'bdthemes-prime-slider')]);
		}

		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => __('You do not have permission to manage white label settings', 'bdthemes-prime-slider')]);
		}

		// Check license eligibility
		if (!self::is_white_label_license()) {
			wp_send_json_error(['message' => __('Your license does not support white label features', 'bdthemes-prime-slider')]);
		}

		// Revoke the token
		$revoked = $this->revoke_white_label_access_token();

		if ($revoked) {
			wp_send_json_success([
				'message' => __('White label access token has been revoked successfully', 'bdthemes-prime-slider')
			]);
		} else {
			wp_send_json_error([
				'message' => __('No active access token found to revoke', 'bdthemes-prime-slider')
			]);
		}
	}

	/**
	 * Get License Key
	 *
	 * @access public
	 * @return string
	 */

	public static function get_license_key() {
		$license_key = get_option('prime_slider_license_key');
		return trim($license_key);
	}

	/**
	 * Get License Email
	 *
	 * @access public
	 * @return string
	 */

	 public static function get_license_email() {
		return trim(get_option('prime_slider_license_email', get_bloginfo('admin_email')));
	}
}

new PrimeSlider_Admin_Settings();
