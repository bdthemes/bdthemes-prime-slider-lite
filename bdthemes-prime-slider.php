<?php

/**
 * Plugin Name: Prime Slider
 * Plugin URI: https://primeslider.pro/
 * Description: Prime Slider is a pack of elementor widget that gives you some awesome header and slider combination for your website.
 * Version: 4.0.3
 * Author: BdThemes
 * Author URI: https://bdthemes.com/
 * Text Domain: bdthemes-prime-slider
 * Domain Path: /languages
 * License: GPL3
 * Elementor requires at least: 3.28
 * Elementor tested up to: 3.32.5
 */

// Some pre define value for easy use

if ( ! defined( 'BDTPS_CORE_VER' ) ) {
	define( 'BDTPS_CORE_VER', '4.0.3' );
}
if ( ! defined( 'BDTPS_CORE__FILE__' ) ) {
	define( 'BDTPS_CORE__FILE__', __FILE__ );
}


// Load white label configuration if it exists (before defining BDTPS_CORE_TITLE)
if ( ! defined( 'BDTPS_CORE_WL' ) ) {
    if ( get_option( 'ps_white_label_enabled' ) ) {
        define( 'BDTPS_CORE_WL', true );
		$white_label_config = dirname( __FILE__ ) . '/includes/white-label-config.php';
		if ( file_exists( $white_label_config ) ) {
			require_once( $white_label_config );
		}
	}
}


/**
 * Loads translations
 *
 * @return void
 */

if ( ! function_exists( 'prime_slider_load_textdomain' ) ) {
	function prime_slider_load_textdomain() {
		load_plugin_textdomain( 'bdthemes-prime-slider', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}
	add_action( 'init', 'prime_slider_load_textdomain' );
}

if ( ! function_exists( '_is_pro_pro_installed' ) ) {

	function _is_pro_pro_installed() {

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$file_path         = 'bdthemes-prime-slider/bdthemes-prime-slider.php';
		$installed_plugins = get_plugins();

		return isset( $installed_plugins[ $file_path ] );
	}
}

if ( ! function_exists( '_is_ps_pro_activated' ) ) {

	function _is_ps_pro_activated() {

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$file_path = 'bdthemes-prime-slider/bdthemes-prime-slider.php';

		if ( is_plugin_active( $file_path ) ) {
			return true;
		}

		return false;
	}
}

// Helper function here
include dirname( __FILE__ ) . '/includes/helper.php';
require_once BDTPS_CORE_INC_PATH . 'class-pro-widget-map.php';
include dirname( __FILE__ ) . '/includes/utils.php';

/**
 * Check the elementor installed or not
 */
if ( ! function_exists( '_is_elementor_installed' ) ) {
	function _is_elementor_installed() {
		$file_path         = 'elementor/elementor.php';
		$installed_plugins = get_plugins();
		return isset( $installed_plugins[ $file_path ] );
	}
}


/**
 * Plugin load here correctly
 * Also loaded the language file from here
 */
function prime_slider_load_plugin() {

	if ( ! did_action( 'elementor/loaded' ) ) {
		add_action( 'admin_notices', 'prime_slider_fail_load' );
		return;
	}

	/**
	 * Setup Wizard Initialization
	 */
	require_once( dirname( __FILE__ ) . '/includes/setup-wizard/init.php' );

	// Filters for developer
	require BDTPS_CORE_PATH . 'includes/prime-slider-filters.php';
	// Prime Slider widget and assets loader
	require BDTPS_CORE_PATH . 'loader.php';

	// Initialize custom CSS/JS injection on frontend
	add_action( 'wp_head', 'ps_inject_header_custom_code', 999 );
	add_action( 'wp_footer', 'ps_inject_footer_custom_code', 999 );
}

add_action( 'plugins_loaded', 'prime_slider_load_plugin' );
/**
 * Check Elementor installed and activated correctly
 */
function prime_slider_fail_load() {
	$screen = get_current_screen();
	if ( isset( $screen->parent_file ) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id ) {
		return;
	}
	$plugin = 'elementor/elementor.php';

	if ( _is_elementor_installed() ) {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		$activation_url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin );
		$admin_message  = '<p>' . esc_html__( 'Ops! Prime Slider not working because you need to activate the Elementor plugin first.', 'bdthemes-prime-slider' ) . '</p>';
		$admin_message .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $activation_url, esc_html__( 'Activate Elementor Now', 'bdthemes-prime-slider' ) ) . '</p>';
	} else {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}
		$install_url   = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=elementor' ), 'install-plugin_elementor' );
		$admin_message = '<p>' . esc_html__( 'Ops! Prime Slider not working because you need to install the Elementor plugin', 'bdthemes-prime-slider' ) . '</p>';
		$admin_message .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $install_url, esc_html__( 'Install Elementor Now', 'bdthemes-prime-slider' ) ) . '</p>';
	}

	echo '<div class="error">' . wp_kses_post( $admin_message ) . '</div>';
}

/**
 * Review Automation Integration
 */

if ( ! function_exists( 'rc_ps_lite_plugin' ) ) {
	function rc_ps_lite_plugin() {

		require_once BDTPS_CORE_INC_PATH . 'feedback-hub/start.php';

		rc_dynamic_init( array(
			'sdk_version'  => '1.0.0',
			'plugin_name'  => 'Prime Slider',
			'plugin_icon'  => BDTPS_CORE_ASSETS_URL . 'images/logo.png',
			'slug'         => 'prime_slider_options',
			'menu'         => array(
				'slug' => 'prime_slider_options',
			),
			'review_url'   => 'https://bdt.to/prime-slider-elementor-addons-review',
			'plugin_title' => esc_html__('Yay! Great that you\'re using Prime Slider', 'bdthemes-prime-slider'),
			'plugin_msg'   => '<p>' . esc_html__('Loved using Prime Slider on your website? Share your experience in a review and help us spread the love to everyone right now. Good words will help the community.', 'bdthemes-prime-slider') . '</p>',
		) );

	}
	add_action( 'admin_init', 'rc_ps_lite_plugin' );
}

