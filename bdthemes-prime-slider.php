<?php

/**
 * Plugin Name: Prime Slider
 * Plugin URI: https://primeslider.pro/
 * Description: Elementor addon pack for building responsive headers and sliders (hero, posts, WooCommerce, and more).
 * Version: 4.5.3
 * Requires at least: 6.8
 * Requires PHP: 7.4
 * Author: BdThemes
 * Author URI: https://bdthemes.com/
 * Text Domain: bdthemes-prime-slider-lite
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Elementor requires at least: 4.0.0
 * Elementor tested up to: 4.2.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Some pre define value for easy use

if ( ! defined( 'BDTPS_CORE_VER' ) ) {
	define( 'BDTPS_CORE_VER', '4.5.3' );
}
if ( ! defined( 'BDTPS_CORE__FILE__' ) ) {
	define( 'BDTPS_CORE__FILE__', __FILE__ );
}

/**
 * Oldest Prime Slider Pro release that works with this version.
 *
 * 4.5.2 moved the Advanced Animation and Reveal Effects code out of this plugin
 * and into Prime Slider Pro, so an older Pro release would look for helpers that
 * no longer live here.
 */
if ( ! defined( 'BDTPS_CORE_PRO_REQUIRED_VERSION' ) ) {
	define( 'BDTPS_CORE_PRO_REQUIRED_VERSION', '4.5.2' );
}


/**
 * Loads translations
 *
 * @return void
 */

// Translations for plugins hosted on WordPress.org are loaded automatically
// since WP 4.6, so no manual load_plugin_textdomain() call is needed.

if ( ! function_exists( 'bdtps_is_pro_installed' ) ) {

	function bdtps_is_pro_installed() {

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$file_path         = 'bdthemes-prime-slider/bdthemes-prime-slider.php';
		$installed_plugins = get_plugins();

		return isset( $installed_plugins[ $file_path ] );
	}
}

if ( ! function_exists( 'bdtps_is_pro_activated' ) ) {

	function bdtps_is_pro_activated() {

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
if ( ! function_exists( 'bdtps_is_elementor_installed' ) ) {
	function bdtps_is_elementor_installed() {
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
}

add_action( 'plugins_loaded', 'prime_slider_load_plugin' );

/**
 * Warn when an out-of-date Prime Slider Pro is active.
 *
 * @return void
 */
function prime_slider_pro_outdated_notice() {
	if ( ! defined( 'BDTPS_PRO_VER' ) || ! current_user_can( 'update_plugins' ) ) {
		return;
	}

	if ( version_compare( BDTPS_PRO_VER, BDTPS_CORE_PRO_REQUIRED_VERSION, '>=' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		sprintf(
			/* translators: 1: installed Prime Slider Pro version, 2: required Prime Slider Pro version. */
			esc_html__( 'Prime Slider Pro %1$s is too old for this version of Prime Slider. Please update Prime Slider Pro to %2$s or later, otherwise some slider controls will not work.', 'bdthemes-prime-slider-lite' ),
			esc_html( BDTPS_PRO_VER ),
			esc_html( BDTPS_CORE_PRO_REQUIRED_VERSION )
		)
	);
}
add_action( 'admin_notices', 'prime_slider_pro_outdated_notice' );
/**
 * Check Elementor installed and activated correctly
 */
function prime_slider_fail_load() {
	$screen = get_current_screen();
	if ( isset( $screen->parent_file ) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id ) {
		return;
	}
	$plugin = 'elementor/elementor.php';

	if ( bdtps_is_elementor_installed() ) {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		$activation_url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin );
		$admin_message  = '<p>' . esc_html__( 'Ops! Prime Slider not working because you need to activate the Elementor plugin first.', 'bdthemes-prime-slider-lite' ) . '</p>';
		$admin_message .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $activation_url, esc_html__( 'Activate Elementor Now', 'bdthemes-prime-slider-lite' ) ) . '</p>';
	} else {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}
		$install_url   = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=elementor' ), 'install-plugin_elementor' );
		$admin_message = '<p>' . esc_html__( 'Ops! Prime Slider not working because you need to install the Elementor plugin', 'bdthemes-prime-slider-lite' ) . '</p>';
		$admin_message .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $install_url, esc_html__( 'Install Elementor Now', 'bdthemes-prime-slider-lite' ) ) . '</p>';
	}

	echo '<div class="error">' . wp_kses_post( $admin_message ) . '</div>';
}

/**
 * Review Automation Integration
 */

if ( ! function_exists( 'bdtps_reviews_collector_bootstrap' ) ) {
	function bdtps_reviews_collector_bootstrap() {

		require_once BDTPS_CORE_INC_PATH . 'feedback-hub/start.php';

		bdtps_reviews_collector_init( array(
			'sdk_version'  => '1.0.0',
			'plugin_name'  => 'Prime Slider',
			'plugin_icon'  => BDTPS_CORE_ASSETS_URL . 'images/logo.png',
			'slug'         => 'prime_slider_options',
			'menu'         => array(
				'slug' => 'prime_slider_options',
			),
			'review_url'   => 'https://bdt.to/prime-slider-elementor-addons-review',
			'plugin_title' => esc_html__('Yay! Great that you\'re using Prime Slider', 'bdthemes-prime-slider-lite'),
			'plugin_msg'   => '<p>' . esc_html__('Loved using Prime Slider on your website? Share your experience in a review and help us spread the love to everyone right now. Good words will help the community.', 'bdthemes-prime-slider-lite') . '</p>',
		) );

	}
	add_action( 'admin_init', 'bdtps_reviews_collector_bootstrap' );
}

