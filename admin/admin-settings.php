<?php

use PrimeSlider\Utils;
use PrimeSlider\Admin\ModuleService;
use Elementor\Modules\Usage\Module;
use Elementor\Tracker;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Prime Slider Admin Settings Class
 */

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

		// Plugin installation (admin only)
		add_action('wp_ajax_bdtps_install_plugin', [$this, 'install_plugin_ajax']);
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
			
			$elements = $module->get_formatted_usage('raw');

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
			
			$elements = $module->get_formatted_usage('raw');

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
			
			$elements = $module->get_formatted_usage('raw');

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
	}

	/**
	 * Add Plugin Menus
	 *
	 * @access public
	 *
	 */

	// Redirect to Prime Slider Pro pricing page
	public function ps_redirect_to_get_pro() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check for display/routing, no form data processed.
        if (isset($_GET['page']) && $_GET['page'] === self::PAGE_ID . '_get_pro') {
            wp_redirect('https://primeslider.pro/pricing/'); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Intentional redirect to the external Prime Slider pricing page; wp_safe_redirect() would block this off-site host.
            exit;
        }
    }

	public function admin_menu() {
		add_menu_page(
			BDTPS_CORE_TITLE . ' ' . esc_html__('Dashboard', 'bdthemes-prime-slider-lite'),
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
			esc_html__('Core Widgets', 'bdthemes-prime-slider-lite'),
			'manage_options',
			self::PAGE_ID . '#prime_slider_active_modules',
			[$this, 'display_page']
		);

		add_submenu_page(
			self::PAGE_ID,
			BDTPS_CORE_TITLE,
			esc_html__('3rd Party Widgets', 'bdthemes-prime-slider-lite'),
			'manage_options',
			self::PAGE_ID . '#prime_slider_third_party_widget',
			[$this, 'display_page']
		);

		add_submenu_page(
			self::PAGE_ID,
			BDTPS_CORE_TITLE,
			esc_html__('Extensions', 'bdthemes-prime-slider-lite'),
			'manage_options',
			self::PAGE_ID . '#prime_slider_elementor_extend',
			[$this, 'display_page']
		);

		add_submenu_page(
			self::PAGE_ID,
			BDTPS_CORE_TITLE,
			esc_html__('Special Features', 'bdthemes-prime-slider-lite'),
			'manage_options',
			self::PAGE_ID . '#prime_slider_other_settings',
			[$this, 'display_page']
		);

		add_submenu_page(
			self::PAGE_ID,
			BDTPS_CORE_TITLE,
			esc_html__('System Status', 'bdthemes-prime-slider-lite'),
			'manage_options',
			self::PAGE_ID . '#prime_slider_analytics_system_req',
			[$this, 'plugin_page']
		);
		
		add_submenu_page(
			self::PAGE_ID,
			BDTPS_CORE_TITLE,
			esc_html__('Other Plugins', 'bdthemes-prime-slider-lite'),
			'manage_options',
			self::PAGE_ID . '#prime_slider_other_plugins',
			[$this, 'plugin_page']
		);
		
		// add_submenu_page(
		// 	self::PAGE_ID,
		// 	BDTPS_CORE_TITLE,
		// 	esc_html__('Get Up to 60%', 'bdthemes-prime-slider-lite'),
		// 	'manage_options',
		// 	self::PAGE_ID . '#prime_slider_affiliate',
		// 	[$this, 'plugin_page']
		// );
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
				'title' => esc_html__('Core Widgets', 'bdthemes-prime-slider-lite')
			],
			[
				'id'    => 'prime_slider_third_party_widget',
				'title' => esc_html__('3rd Party Widgets', 'bdthemes-prime-slider-lite')
			],
			[
				'id'    => 'prime_slider_elementor_extend',
				'title' => esc_html__('Extensions', 'bdthemes-prime-slider-lite')
			],
			[
				'id'    => 'prime_slider_other_settings',
				'title' => esc_html__('Special Features', 'bdthemes-prime-slider-lite'),
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
						<?php esc_html_e('Welcome to Prime Slider!', 'bdthemes-prime-slider-lite'); ?>
					</h1>
					<p class="ps-dashboard-welcome-desc">
						<?php esc_html_e('Empower your web creation with powerful widgets, advanced extensions, ready templates and more.', 'bdthemes-prime-slider-lite'); ?>
					</p>
					<a href="<?php echo esc_url( admin_url('?ps_setup_wizard=show') ); ?>"
						class="bdt-button bdt-welcome-button bdt-margin-small-top"
						target="_blank"><?php esc_html_e('Setup Prime Slider', 'bdthemes-prime-slider-lite'); ?></a>

					<div class="ps-dashboard-compare-section">
						<h4 class="ps-feature-sub-title">
							<?php /* translators: 1: opening tag, 2: closing tag */ ?>
							<?php printf(esc_html__('Unlock %1$sPremium Features%2$s', 'bdthemes-prime-slider-lite'), '<strong class="ps-highlight-text">', '</strong>'); ?>
						</h4>
						<h1 class="ps-feature-title ps-dashboard-compare-title">
							<?php esc_html_e('Create Your Sleek Website with Prime Slider Pro!', 'bdthemes-prime-slider-lite'); ?>
						</h1>
						<p><?php esc_html_e('Don\'t need more plugins. This pro addon helps you build complex or professional websites—visually stunning, functional and customizable.', 'bdthemes-prime-slider-lite'); ?>
						</p>
						<ul>
							<li><?php esc_html_e('Dynamic Slider and Integrations', 'bdthemes-prime-slider-lite'); ?></li>
							<li><?php esc_html_e('Live Copy Paste', 'bdthemes-prime-slider-lite'); ?></li>
							<li><?php esc_html_e('Duplicator', 'bdthemes-prime-slider-lite'); ?></li>
							<li><?php esc_html_e('Reveal Effects', 'bdthemes-prime-slider-lite'); ?></li>
							<li><?php esc_html_e('Adaptive Background', 'bdthemes-prime-slider-lite'); ?>
							</li>
						</ul>
						<div class="ps-dashboard-compare-section-buttons">
							<a href="https://primeslider.pro/pricing/"
								class="bdt-button bdt-welcome-button bdt-margin-small-right"
								target="_blank"><?php esc_html_e('Compare Free Vs Pro', 'bdthemes-prime-slider-lite'); ?></a>
							<a href="https://primeslider.pro/pricing?utm_source=PrimeSlider&utm_medium=PluginPage&utm_campaign=PrimeSlider&coupon=FREETOPRO"
								class="bdt-button bdt-dashboard-sec-btn"
								target="_blank"><?php esc_html_e('Get Premium at 30% OFF', 'bdthemes-prime-slider-lite'); ?></a>
						</div>
					</div>
				</div>

				<div class="ps-dashboard-item ps-dashboard-template-quick-access bdt-card bdt-card-body">
					<div class="ps-dashboard-template-section">
						<img src="<?php echo esc_url( BDTPS_CORE_ADMIN_URL . 'assets/images/template.jpg' ); ?>"
							alt="Prime Slider Dashboard Template">
						<h1 class="ps-feature-title ">
							<?php esc_html_e('Faster Web Creation with Sleek and Ready-to-Use Templates!', 'bdthemes-prime-slider-lite'); ?>
						</h1>
						<p><?php esc_html_e('Build your wordpress websites of any niche—not from scratch and in a single click.', 'bdthemes-prime-slider-lite'); ?>
						</p>
						<a href="https://primeslider.pro/demo/"
							class="bdt-button bdt-dashboard-sec-btn bdt-margin-small-top"
							target="_blank"><?php esc_html_e('View Templates', 'bdthemes-prime-slider-lite'); ?></a>
					</div>

					<div class="ps-dashboard-quick-access bdt-margin-medium-top">
						<img src="<?php echo esc_url( BDTPS_CORE_ADMIN_URL . 'assets/images/support.jpg' ); ?>"
							alt="Prime Slider Dashboard Template">
						<h1 class="ps-feature-title">
							<?php esc_html_e('Getting Started with Quick Access', 'bdthemes-prime-slider-lite'); ?>
						</h1>
						<ul>
							<li><a href="https://primeslider.pro/contact/"
									target="_blank"><?php esc_html_e('Contact Us', 'bdthemes-prime-slider-lite'); ?></a></li>
							<li><a href="https://bdthemes.com/support/"
									target="_blank"><?php esc_html_e('Help Centre', 'bdthemes-prime-slider-lite'); ?></a></li>
							<li><a href="https://feedback.bdthemes.com/b/6vr2250l/feature-requests/idea/new"
									target="_blank"><?php esc_html_e('Request a Feature', 'bdthemes-prime-slider-lite'); ?></a>
							</li>
						</ul>
						<div class="ps-dashboard-support-section">
							<h1 class="ps-feature-title">
								<i class="dashicons dashicons-phone"></i>
								<?php esc_html_e('24/7 Support', 'bdthemes-prime-slider-lite'); ?>
							</h1>
							<p><?php esc_html_e('Helping you get real-time solutions related to web creation with WordPress, Elementor, and Prime Slider.', 'bdthemes-prime-slider-lite'); ?>
							</p>
							<a href="https://bdthemes.com/support/" class="bdt-margin-small-top"
								target="_blank"><?php esc_html_e('Get Your Support', 'bdthemes-prime-slider-lite'); ?></a>
						</div>
					</div>
				</div>

				<div class="ps-dashboard-item ps-dashboard-request-feature bdt-card bdt-card-body">
					<h1 class="ps-feature-title ps-dashboard-template-quick-title">
						<?php esc_html_e('What\'s Stacking You?', 'bdthemes-prime-slider-lite'); ?>
					</h1>
					<p><?php esc_html_e('We are always here to help you. If you have any feature request, please let us know.', 'bdthemes-prime-slider-lite'); ?>
					</p>
					<a href="https://feedback.bdthemes.com/b/6vr2250l/feature-requests/idea/new"
						class="bdt-button bdt-dashboard-sec-btn bdt-margin-small-top"
						target="_blank"><?php esc_html_e('Request Your Features', 'bdthemes-prime-slider-lite'); ?></a>
				</div>

				<a href="https://www.youtube.com/watch?v=sZwJDtxasTg&list=PLP0S85GEw7DP3-yJrkgwpIeDFoXy0PDlM" target="_blank"
					class="ps-dashboard-item ps-dashboard-footer-item ps-dashboard-video-tutorial bdt-card bdt-card-body bdt-card-small">
					<span class="ps-dashboard-footer-item-icon">
						<i class="dashicons dashicons-video-alt3"></i>
					</span>
					<h1 class="ps-feature-title"><?php esc_html_e('Watch Video Tutorials', 'bdthemes-prime-slider-lite'); ?></h1>
					<p><?php esc_html_e('An invaluable resource for mastering WordPress, Elementor, and Web Creation', 'bdthemes-prime-slider-lite'); ?>
					</p>
				</a>
				<a href="https://bdthemes.com/knowledge-base/prime-slider/" target="_blank"
					class="ps-dashboard-item ps-dashboard-footer-item ps-dashboard-documentation bdt-card bdt-card-body bdt-card-small">
					<span class="ps-dashboard-footer-item-icon">
						<i class="dashicons dashicons-admin-tools"></i>
					</span>
					</span>
					<h1 class="ps-feature-title"><?php esc_html_e('Read Easy Documentation', 'bdthemes-prime-slider-lite'); ?></h1>
					<p><?php esc_html_e('A way to eliminate the challenges you might face', 'bdthemes-prime-slider-lite'); ?></p>
				</a>
				<a href="https://www.facebook.com/bdthemes" target="_blank"
					class="ps-dashboard-item ps-dashboard-footer-item ps-dashboard-community bdt-card bdt-card-body bdt-card-small">
					<span class="ps-dashboard-footer-item-icon">
						<i class="dashicons dashicons-admin-users"></i>
					</span>
					<h1 class="ps-feature-title"><?php esc_html_e('Join Our Community', 'bdthemes-prime-slider-lite'); ?></h1>
					<p><?php esc_html_e('A platform for the opportunity to network, collaboration and innovation', 'bdthemes-prime-slider-lite'); ?>
					</p>
				</a>
				<a href="https://wordpress.org/support/plugin/bdthemes-prime-slider-lite/reviews/" target="_blank"
					class="ps-dashboard-item ps-dashboard-footer-item ps-dashboard-review bdt-card bdt-card-body bdt-card-small">
					<span class="ps-dashboard-footer-item-icon">
						<i class="dashicons dashicons-star-filled"></i>
					</span>
					<h1 class="ps-feature-title"><?php esc_html_e('Show Your Love', 'bdthemes-prime-slider-lite'); ?></h1>
					<p><?php esc_html_e('A way of the assessment of code', 'bdthemes-prime-slider-lite'); ?></p>
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
							<h1 class="bdt-text-bold"><?php echo esc_html__('WHY GO WITH PRO?', 'bdthemes-prime-slider-lite'); ?></h1>
							<h2><?php echo esc_html__('Just Compare With ', 'bdthemes-prime-slider-lite'); ?>Prime Slider<?php echo esc_html__(' Free Vs Pro', 'bdthemes-prime-slider-lite'); ?></h2>
						</div>
						<?php if (true !== bdtps_is_pro_activated()) : ?>
							<div class="ps-purchase-button">
								<a href="https://primeslider.pro/pricing/" target="_blank"><?php echo esc_html__('Purchase Now', 'bdthemes-prime-slider-lite'); ?></a>
							</div>
						<?php endif; ?>
					</div>


					<div>

						<ul class="bdt-list bdt-list-divider bdt-text-left bdt-text-normal" style="font-size: 15px;">


							<li class="bdt-text-bold">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><?php echo esc_html__('Features', 'bdthemes-prime-slider-lite'); ?></div>
									<div class="bdt-width-auto@m"><?php echo esc_html__('Free', 'bdthemes-prime-slider-lite'); ?></div>
									<div class="bdt-width-auto@m"><?php echo esc_html__('Pro', 'bdthemes-prime-slider-lite'); ?></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><span bdt-tooltip="pos: top-left; title: <?php echo esc_html__('Free have 27+ Widgets but Pro have 21+ core widgets', 'bdthemes-prime-slider-lite'); ?>"><?php echo esc_html__('Core Widgets', 'bdthemes-prime-slider-lite'); ?></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><span bdt-tooltip="pos: top-left; title: <?php echo esc_html__('Free have 3+ Widgets but Pro have 3+ 3rd party widgets', 'bdthemes-prime-slider-lite'); ?>"><?php echo esc_html__('3rd Party Widgets', 'bdthemes-prime-slider-lite'); ?></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><?php echo esc_html__('Theme Compatibility', 'bdthemes-prime-slider-lite'); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><?php echo esc_html__('Dynamic Content & Custom Fields Capabilities', 'bdthemes-prime-slider-lite'); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><?php echo esc_html__('Proper Documentation', 'bdthemes-prime-slider-lite'); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><?php echo esc_html__('Updates & Support', 'bdthemes-prime-slider-lite'); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><?php echo esc_html__('Ready Made Pages', 'bdthemes-prime-slider-lite'); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><?php echo esc_html__('Ready Made Blocks', 'bdthemes-prime-slider-lite'); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><?php echo esc_html__('Elementor Extended Widgets', 'bdthemes-prime-slider-lite'); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><?php echo esc_html__('Live Copy or Paste', 'bdthemes-prime-slider-lite'); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><?php echo esc_html__('Duplicator', 'bdthemes-prime-slider-lite'); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m">Rooten<?php echo esc_html__(' Theme Pro Features', 'bdthemes-prime-slider-lite'); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-no"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><?php echo esc_html__('Priority Support', 'bdthemes-prime-slider-lite'); ?></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-no"></span></div>
									<div class="bdt-width-auto@m"><span class="dashicons dashicons-yes"></span></div>
								</div>
							</li>
							<li class="">
								<div class="bdt-grid">
									<div class="bdt-width-expand@m"><?php echo esc_html__('Reveal Effects', 'bdthemes-prime-slider-lite'); ?></div>
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
											<span class="dashicons dashicons-heart"></span><?php echo esc_html__(' Incredibly Advanced', 'bdthemes-prime-slider-lite'); ?>
										</div>
										<div class="bdt-width-1-3@m">
											<span class="dashicons dashicons-heart"></span><?php echo esc_html__(' Refund or Cancel Anytime', 'bdthemes-prime-slider-lite'); ?>
										</div>
										<div class="bdt-width-1-3@m">
											<span class="dashicons dashicons-heart"></span><?php echo esc_html__(' Dynamic Content', 'bdthemes-prime-slider-lite'); ?>
										</div>
									</div>
								</li>

								<li>
									<div class="bdt-grid bdt-grid-small">
										<div class="bdt-width-1-3@m">
											<span class="dashicons dashicons-heart"></span><?php echo esc_html__(' Super-Flexible Widgets', 'bdthemes-prime-slider-lite'); ?>
										</div>
										<div class="bdt-width-1-3@m">
											<span class="dashicons dashicons-heart"></span><?php echo esc_html__(' 24/7 Premium Support', 'bdthemes-prime-slider-lite'); ?>
										</div>
										<div class="bdt-width-1-3@m">
											<span class="dashicons dashicons-heart"></span><?php echo esc_html__(' Third Party Plugins', 'bdthemes-prime-slider-lite'); ?>
										</div>
									</div>
								</li>

								<li>
									<div class="bdt-grid bdt-grid-small">
										<div class="bdt-width-1-3@m">
											<span class="dashicons dashicons-heart"></span><?php echo esc_html__(' Special Discount!', 'bdthemes-prime-slider-lite'); ?>
										</div>
										<div class="bdt-width-1-3@m">
											<span class="dashicons dashicons-heart"></span><?php echo esc_html__(' Custom Field Integration', 'bdthemes-prime-slider-lite'); ?>
										</div>
										<div class="bdt-width-1-3@m">
											<span class="dashicons dashicons-heart"></span><?php echo esc_html__(' With Live Chat Support', 'bdthemes-prime-slider-lite'); ?>
										</div>
									</div>
								</li>

								<li>
									<div class="bdt-grid bdt-grid-small">
										<div class="bdt-width-1-3@m">
											<span class="dashicons dashicons-heart"></span><?php echo esc_html__(' Trusted Payment Methods', 'bdthemes-prime-slider-lite'); ?>
										</div>
										<div class="bdt-width-1-3@m">
											<span class="dashicons dashicons-heart"></span><?php echo esc_html__(' Interactive Effects', 'bdthemes-prime-slider-lite'); ?>
										</div>
										<div class="bdt-width-1-3@m">
											<span class="dashicons dashicons-heart"></span><?php echo esc_html__(' Video Tutorial', 'bdthemes-prime-slider-lite'); ?>
										</div>
									</div>
								</li>
							</ul>

							<?php if (true !== bdtps_is_pro_activated()) : ?>
								<div class="ps-purchase-button bdt-margin-medium-top">
									<a href="https://primeslider.pro/pricing/" target="_blank"><?php echo esc_html__('Purchase Now', 'bdthemes-prime-slider-lite'); ?></a>
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
								<img src="<?php echo esc_url( BDTPS_CORE_URL . 'assets/images/logo-with-text.svg' ); ?>" alt="Prime Slider Logo">
						</div>
					</div>

					<div class="ps-dashboard-new-page-wrapper bdt-flex bdt-flex-wrap bdt-flex-middle">
						

						<!-- Always render save button, JavaScript will control visibility -->
						<div class="ps-dashboard-save-btn" style="display: none;">
							<button class="bdt-button bdt-button-primary prime-slider-settings-save-btn" type="submit">
								<?php esc_html_e('Save Settings', 'bdthemes-prime-slider-lite'); ?>
							</button>
						</div>


						<div class="ps-dashboard-new-page">
							<a class="bdt-flex bdt-flex-middle" href="<?php echo esc_url(admin_url('post-new.php?post_type=page')); ?>" class=""><i class="dashicons dashicons-admin-page"></i>
								<?php echo esc_html__('Create New Page', 'bdthemes-prime-slider-lite') ?>
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


						<div id="prime_slider_analytics_system_req_page" class="ps-option-page group">
							<?php $this->prime_slider_analytics_system_req_content(); ?>
						</div>

						<div id="prime_slider_other_plugins_page" class="ps-option-page group">
							<?php $this->prime_slider_others_plugin(); ?>
						</div>

						<!-- <div id="prime_slider_affiliate_page" class="ps-option-page group">
							<?php //$this->prime_slider_affiliate_content(); ?>
						</div> -->


                        <?php if (bdtps_is_pro_activated() !== true) : ?>
                            <div id="prime_slider_get_pro" class="ps-option-page group">
                                <?php $this->prime_slider_get_pro(); ?>
                            </div>
                        <?php endif; ?>

                        <?php
                        // Render tab bodies for any add-on-registered dashboard tabs.
                        // The core plugin provides only this extension point; the tab
                        // ids/order match the nav items built in show_navigation().
                        foreach ($this->settings_api->get_extra_dashboard_tabs() as $ps_extra_tab) {
                            if (empty($ps_extra_tab['id'])) {
                                continue;
                            }
                            echo '<div id="' . esc_attr($ps_extra_tab['id']) . '_page" class="ps-option-page group">';
                            if (isset($ps_extra_tab['callback']) && is_callable($ps_extra_tab['callback'])) {
                                call_user_func($ps_extra_tab['callback']);
                            }
                            echo '</div>';
                        }
                        ?>

                        <div id="prime_slider_license_settings_page" class="ps-option-page group">

                            <?php
                            if (bdtps_is_pro_activated() == true) {
                                apply_filters('prime_slider/license_page', '');
                            }

                            ?>
                        </div>

					</div>
				</div>

				<?php if (!defined('BDTPS_WL') || false == self::license_wl_status()) {
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

				$('#prime_slider_third_party_widget_page a.ps-active-all-widget').on('click', function (e) {
					e.preventDefault();

					$('#prime_slider_third_party_widget_page .ps-option-item:not(.ps-pro-inactive) .checkbox:visible').each(function () {
						$(this).attr('checked', 'checked').prop("checked", true);
					});

					$(this).addClass('bdt-active');
					$('#prime_slider_third_party_widget_page a.ps-deactive-all-widget').removeClass('bdt-active');
					
					// Ensure save button remains visible
					setTimeout(function() {
						$('.ps-dashboard-save-btn').show();
					}, 100);
				});

				$('#prime_slider_third_party_widget_page a.ps-deactive-all-widget').on('click', function (e) {
					e.preventDefault();

					$('#prime_slider_third_party_widget_page .checkbox:visible').each(function () {
						$(this).removeAttr('checked').prop("checked", false);
					});

					$(this).addClass('bdt-active');
					$('#prime_slider_third_party_widget_page a.ps-active-all-widget').removeClass('bdt-active');
					
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
						// 	message: '<div bdt-spinner></div> <?php //esc_html_e('Please wait, Saving settings...', 'bdthemes-prime-slider-lite') ?>',
						// 	timeout: false
						// });

						// Submit form using AJAX (same logic as existing form submission)
						targetForm.ajaxSubmit({
							success: function () {
								// Show success message using UIkit notification (same as main settings)
								bdtUIkit.notification.closeAll();
								bdtUIkit.notification({
									message: '<span class="dashicons dashicons-yes"></span> <?php esc_html_e('Settings Saved Successfully.', 'bdthemes-prime-slider-lite') ?>',
									status: 'primary',
									pos: 'top-center'
								});
							},
							error: function (data) {
								bdtUIkit.notification.closeAll();
								bdtUIkit.notification({
									message: '<span bdt-icon=\'icon: warning\'></span> <?php esc_html_e('Unknown error, make sure access is correct!', 'bdthemes-prime-slider-lite') ?>',
									status: 'warning'
								});
							}
						});
					} else {
						// Show error if no form found
						bdtUIkit.notification({
							message: '<span bdt-icon="icon: warning"></span> <?php esc_html_e('No settings form found to save.', 'bdthemes-prime-slider-lite') ?>',
							status: 'warning'
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
					   .text('<?php echo esc_js(__('Installing...', 'bdthemes-prime-slider-lite')); ?>')
					   .addClass('bdt-installing');
				
				// Perform AJAX request
				jQuery.ajax({
					url: '<?php echo esc_url( admin_url('admin-ajax.php') ); ?>',
					type: 'POST',
					data: {
						action: 'bdtps_install_plugin',
						plugin_slug: pluginSlug,
						nonce: nonce
					},
					success: function(response) {
						if (response.success) {
							// Show success message
							$button.text('<?php echo esc_js(__('Installed!', 'bdthemes-prime-slider-lite')); ?>')
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
								message: '<span class="dashicons dashicons-warning"></span> <?php echo esc_js(__('Installation failed. Please try again.', 'bdthemes-prime-slider-lite')); ?>',
								status: 'danger'
							});
						}
					}
				});
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
						echo esc_html__('Prime Slider plugin made with love by', 'bdthemes-prime-slider-lite') . ' <a target="_blank" href="https://bdthemes.com">BdThemes</a> ' . esc_html__('Team.', 'bdthemes-prime-slider-lite');
						echo '<br>';
						echo esc_html__('All rights reserved by', 'bdthemes-prime-slider-lite') . ' <a target="_blank" href="https://bdthemes.com">BdThemes.com</a>.';
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
	 * Whether an add-on has supplied a white-label title.
	 *
	 * This only decides whether BdThemes branding is shown in the dashboard. It
	 * does not enable, disable or limit any feature of this plugin.
	 *
	 * @return bool
	 */
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
								<?php esc_html_e('System Requirement', 'bdthemes-prime-slider-lite'); ?>
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
			$track_nw = esc_html__('This feature is not working because the Elementor Usage Data Sharing feature is Not Enabled.', 'bdthemes-prime-slider-lite');
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
							<h1 class="ps-feature-title"><?php echo esc_html__('All Widgets', 'bdthemes-prime-slider-lite'); ?></h1>
							<div class="bdt-flex bdt-flex-between bdt-flex-middle">
								<div class="ps-count-wrap">
									<div class="ps-widget-count"><?php echo esc_html__('Used:', 'bdthemes-prime-slider-lite'); ?> <b>
											<?php echo esc_html($used_widgets); ?>
										</b></div>
									<div class="ps-widget-count"><?php echo esc_html__('Unused:', 'bdthemes-prime-slider-lite'); ?> <b>
											<?php echo esc_html($un_used_widgets); ?>
										</b></div>
									<div class="ps-widget-count"><?php echo esc_html__('Total:', 'bdthemes-prime-slider-lite'); ?>
										<b>
											<?php echo esc_html($used_widgets + $un_used_widgets); ?>
										</b>
									</div>
								</div>

								<div class="ps-canvas-wrap">
									<canvas id="bdt-db-total-status" style="height: 100px; width: 100px;" data-label="<?php echo esc_html__('Total Widgets Status', 'bdthemes-prime-slider-lite'); ?> - (<?php echo esc_html($used_widgets + $un_used_widgets); ?>)" data-labels="<?php echo esc_attr('Used, Unused'); ?>" data-value="<?php echo esc_attr($used_widgets) . ',' . esc_attr($un_used_widgets); ?>" data-bg="#FFD166, #fff4d9" data-bg-hover="#0673e1, #e71522"></canvas>
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
							<h1 class="ps-feature-title"><?php echo esc_html__('Core', 'bdthemes-prime-slider-lite'); ?></h1>
							<div class="bdt-flex bdt-flex-between bdt-flex-middle">
								<div class="ps-count-wrap">
									<div class="ps-widget-count"><?php echo esc_html__('Used:', 'bdthemes-prime-slider-lite'); ?> <b>
											<?php echo esc_html($used_only_widgets); ?>
										</b></div>
									<div class="ps-widget-count"><?php echo esc_html__('Unused:', 'bdthemes-prime-slider-lite'); ?> <b>
											<?php echo esc_html($unused_only_widgets); ?>
										</b></div>
									<div class="ps-widget-count"><?php echo esc_html__('Total:', 'bdthemes-prime-slider-lite'); ?>
										<b>
											<?php echo esc_html($used_only_widgets + $unused_only_widgets); ?>
										</b>
									</div>
								</div>

								<div class="ps-canvas-wrap">
									<canvas id="bdt-db-only-widget-status" style="height: 100px; width: 100px;" data-label="<?php echo esc_html__('Core Widgets Status', 'bdthemes-prime-slider-lite'); ?> - (<?php echo esc_attr($used_only_widgets + $unused_only_widgets); ?>)" data-labels="<?php echo esc_attr('Used, Unused'); ?>" data-value="<?php echo esc_attr($used_only_widgets) . ',' . esc_attr($unused_only_widgets); ?>" data-bg="#EF476F, #ffcdd9" data-bg-hover="#0673e1, #e71522"></canvas>
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
							<h1 class="ps-feature-title"><?php echo esc_html__('3rd Party', 'bdthemes-prime-slider-lite'); ?></h1>
							<div class="bdt-flex bdt-flex-between bdt-flex-middle">
								<div class="ps-count-wrap">
									<div class="ps-widget-count"><?php echo esc_html__('Used:', 'bdthemes-prime-slider-lite'); ?> <b>
											<?php echo esc_html($used_only_3rdparty); ?>
										</b></div>
									<div class="ps-widget-count"><?php echo esc_html__('Unused:', 'bdthemes-prime-slider-lite'); ?> <b>
											<?php echo esc_html($unused_only_3rdparty); ?>
										</b></div>
									<div class="ps-widget-count"><?php echo esc_html__('Total:', 'bdthemes-prime-slider-lite'); ?>
										<b>
											<?php echo esc_html($used_only_3rdparty + $unused_only_3rdparty); ?>
										</b>
									</div>
								</div>

								<div class="ps-canvas-wrap">
									<canvas id="bdt-db-only-3rdparty-status" style="height: 100px; width: 100px;" data-label="<?php echo esc_html__('3rd Party Widgets Status', 'bdthemes-prime-slider-lite'); ?> - (<?php echo esc_attr($used_only_3rdparty + $unused_only_3rdparty); ?>)" data-labels="<?php echo esc_attr('Used, Unused'); ?>" data-value="<?php echo esc_attr($used_only_3rdparty) . ',' . esc_attr($unused_only_3rdparty); ?>" data-bg="#06D6A0, #B6FFEC" data-bg-hover="#0673e1, #e71522"></canvas>
								</div>
							</div>
						</div>

					</div>
				</div>

				<div class="bdt-width-1-2@m bdt-width-1-4@l">
					<div class="ps-widget-status bdt-card bdt-card-body" <?php echo wp_kses_post($track_nw_msg); ?>>

						<div class="ps-count-canvas-wrap">
							<h1 class="ps-feature-title"><?php echo esc_html__('Active', 'bdthemes-prime-slider-lite'); ?></h1>
							<div class="bdt-flex bdt-flex-between bdt-flex-middle">
								<div class="ps-count-wrap">
									<div class="ps-widget-count"><?php echo esc_html__('Core:', 'bdthemes-prime-slider-lite'); ?> <b id="bdt-total-widgets-status-core"></b></div>
									<div class="ps-widget-count"><?php echo esc_html__('3rd Party:', 'bdthemes-prime-slider-lite'); ?> <b id="bdt-total-widgets-status-3rd"></b></div>
									<div class="ps-widget-count"><?php echo esc_html__('Total:', 'bdthemes-prime-slider-lite'); ?> <b id="bdt-total-widgets-status-heading"></b></div>
								</div>

								<div class="ps-canvas-wrap">
									<canvas id="bdt-total-widgets-status" style="height: 100px; width: 100px;" data-label="<?php echo esc_html__('Total Active Widgets Status', 'bdthemes-prime-slider-lite'); ?>" data-labels="<?php echo esc_attr('Core, 3rd Party'); ?>" data-bg="#0680d6, #B0EBFF" data-bg-hover="#0673e1, #B0EBFF">
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
						/* translators: 1: opening bold tag, 2: closing bold tag */
						esc_html__('To view widgets analytics, Elementor %1$sUsage Data Sharing%2$s feature by Elementor needs to be activated. Please activate the feature to get widget analytics instantly ', 'bdthemes-prime-slider-lite'),
						'<b>', '</b>'
					);

					echo ' <a href="' . esc_url(admin_url('admin.php?page=elementor-settings')) . '">' . esc_html__('from here.', 'bdthemes-prime-slider-lite') . '</a>';
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
					<span class="label1"><?php esc_html_e('PHP Version:', 'bdthemes-prime-slider-lite'); ?></span>

					<?php
					if (version_compare($php_version, '7.4.0', '<')) {
						echo wp_kses_post($no_icon);
						echo '<span class="label2" title="' . esc_attr__('Min: 7.4 Recommended', 'bdthemes-prime-slider-lite') . '" bdt-tooltip>' . esc_html__('Currently:', 'bdthemes-prime-slider-lite') . ' ' . esc_html($php_version) . '</span>';
					} else {
						echo wp_kses_post($yes_icon);
						echo '<span class="label2">' . esc_html__('Currently:', 'bdthemes-prime-slider-lite') . ' ' . esc_html($php_version) . '</span>';
					}
					?>
				</div>

			</li>

			<li>
				<div>
					<span class="label1"><?php esc_html_e('Max execution time:', 'bdthemes-prime-slider-lite'); ?> </span>
					<?php
					if ($max_execution_time < '90') {
						echo wp_kses_post($no_icon);
						echo '<span class="label2" title="' . esc_attr__('Min: 90 Recommended', 'bdthemes-prime-slider-lite') . '" bdt-tooltip>' . esc_html__('Currently:', 'bdthemes-prime-slider-lite') . ' ' . esc_html($max_execution_time) . '</span>';
					} else {
						echo wp_kses_post($yes_icon);
						echo '<span class="label2">' . esc_html__('Currently:', 'bdthemes-prime-slider-lite') . ' ' . esc_html($max_execution_time) . '</span>';
					}
					?>
				</div>
			</li>
			<li>
				<div>
					<span class="label1"><?php esc_html_e('Memory Limit:', 'bdthemes-prime-slider-lite'); ?> </span>

					<?php
					if (intval($memory_limit) < '512') {
						echo wp_kses_post($no_icon);
						echo '<span class="label2" title="' . esc_attr__('Min: 512M Recommended', 'bdthemes-prime-slider-lite') . '" bdt-tooltip>' . esc_html__('Currently:', 'bdthemes-prime-slider-lite') . ' ' . esc_html($memory_limit) . '</span>';
					} else {
						echo wp_kses_post($yes_icon);
						echo '<span class="label2">' . esc_html__('Currently:', 'bdthemes-prime-slider-lite') . ' ' . esc_html($memory_limit) . '</span>';
					}
					?>
				</div>
			</li>

			<li>
				<div>
					<span class="label1"><?php esc_html_e('Max Post Limit:', 'bdthemes-prime-slider-lite'); ?> </span>

					<?php
					if (intval($post_limit) < '32') {
						echo wp_kses_post($no_icon);
						echo '<span class="label2" title="' . esc_attr__('Min: 32M Recommended', 'bdthemes-prime-slider-lite') . '" bdt-tooltip>' . esc_html__('Currently:', 'bdthemes-prime-slider-lite') . ' ' . wp_kses_post($post_limit) . '</span>';
					} else {
						echo wp_kses_post($yes_icon);
						echo '<span class="label2">' . esc_html__('Currently:', 'bdthemes-prime-slider-lite') . ' ' . wp_kses_post($post_limit) . '</span>';
					}
					?>
				</div>
			</li>

			<li>
				<div>
					<span class="label1"><?php esc_html_e('Uploads folder writable:', 'bdthemes-prime-slider-lite'); ?></span>

					<?php
					if (!wp_is_writable($upload_path)) {
						echo wp_kses_post($no_icon);
					} else {
						echo wp_kses_post($yes_icon);
					}
					?>
				</div>

			</li>

			<li>
				<div>
					<span class="label1"><?php esc_html_e('MultiSite:', 'bdthemes-prime-slider-lite'); ?></span>

					<?php
					if ($environment['wp_multisite']) {
						echo wp_kses_post($yes_icon);
						echo '<span class="label2">' . esc_html__('MultiSite Enabled', 'bdthemes-prime-slider-lite') . '</span>';
					} else {
						echo wp_kses_post($yes_icon);
						echo '<span class="label2">' . esc_html__('Single Site', 'bdthemes-prime-slider-lite') . '</span>';
					}
					?>
				</div>
			</li>

			<li>
				<div>
					<span class="label1"><?php esc_html_e('GZip Enabled:', 'bdthemes-prime-slider-lite'); ?></span>

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
					<span class="label1"><?php esc_html_e('Debug Mode:', 'bdthemes-prime-slider-lite'); ?></span>
					<?php
					if ($environment['wp_debug_mode']) {
						echo wp_kses_post($no_icon);
						echo '<span class="label2">' . esc_html__('Currently Turned On', 'bdthemes-prime-slider-lite') . '</span>';
					} else {
						echo wp_kses_post($yes_icon);
						echo '<span class="label2">' . esc_html__('Currently Turned Off', 'bdthemes-prime-slider-lite') . '</span>';
					}
					?>
				</div>

			</li>

		</ul>

		<div class="bdt-admin-alert">
			<strong><?php esc_html_e('Note:', 'bdthemes-prime-slider-lite'); ?></strong>
			<?php
			printf(
				/* translators: %s: Plugin name 'Prime Slider' */
				esc_html__('If you have multiple addons like %s so you may need to allocate additional memory for other addons as well.', 'bdthemes-prime-slider-lite'),
				'<b>Prime Slider</b>'
			);
			?>
		</div>

		<?php
	}

    /**
	 * Others Plugin
	 */

	/**
	 * Others Plugin - Using standalone plugin manager
	 */
	public function prime_slider_others_plugin() {
		// Include and render the standalone others plugin manager
		require_once BDTPS_CORE_INC_PATH . 'setup-wizard/prime-slider-others-plugin.php';
		
		// Call the helper function to render the plugin manager
		prime_slider_others_plugin();
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
	 * Handle AJAX plugin installation
	 * 
	 * @access public
	 * @return void
	 */
	public function install_plugin_ajax() {
		// Check nonce
		if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ps_install_plugin_nonce')) {
			wp_send_json_error(['message' => __('Security check failed', 'bdthemes-prime-slider-lite')]);
		}

		// Check user capability
		if (!current_user_can('install_plugins')) {
			wp_send_json_error(['message' => __('You do not have permission to install plugins', 'bdthemes-prime-slider-lite')]);
		}

		$plugin_slug = isset($_POST['plugin_slug']) ? sanitize_text_field(wp_unslash($_POST['plugin_slug'])) : '';

		if (empty($plugin_slug)) {
			wp_send_json_error(['message' => __('Plugin slug is required', 'bdthemes-prime-slider-lite')]);
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
			wp_send_json_error(['message' => __('Plugin not found: ', 'bdthemes-prime-slider-lite') . $api->get_error_message()]);
		}

		// Install the plugin
		$skin = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader($skin);
		$result = $upgrader->install($api->download_link);

		if (is_wp_error($result)) {
			wp_send_json_error(['message' => __('Installation failed: ', 'bdthemes-prime-slider-lite') . $result->get_error_message()]);
		} elseif ($skin->get_errors()->has_errors()) {
			wp_send_json_error(['message' => __('Installation failed: ', 'bdthemes-prime-slider-lite') . $skin->get_error_messages()]);
		} elseif (is_null($result)) {
			wp_send_json_error(['message' => __('Installation failed: Unable to connect to filesystem', 'bdthemes-prime-slider-lite')]);
		}

		// Get installation status
		$install_status = install_plugin_install_status($api);
		
		wp_send_json_success([
			'message' => __('Plugin installed successfully!', 'bdthemes-prime-slider-lite'),
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
				       __('Activate', 'bdthemes-prime-slider-lite') . '</a>';
				
			case 'not_installed':
			default:
				$plugin_slug = $this->extract_plugin_slug_from_path($plugin_path);
				$nonce = wp_create_nonce('ps_install_plugin_nonce');
				return '<a class="bdt-button bdt-welcome-button ps-install-plugin" 
				          data-plugin-slug="' . esc_attr($plugin_slug) . '" 
				          data-nonce="' . esc_attr($nonce) . '" 
				          href="#">' . 
				       __('Install', 'bdthemes-prime-slider-lite') . '</a>';
		}
	}


}

new PrimeSlider_Admin_Settings();
