<?php

namespace PrimeSlider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


require_once BDTPS_CORE_ADMIN_PATH . 'class-settings-api.php';
if ( current_user_can( 'manage_options' ) ) {
	require_once BDTPS_CORE_ADMIN_PATH . 'admin-feeds.php';

}
// element pack admin settings here
require_once BDTPS_CORE_ADMIN_PATH . 'admin-settings.php';

/**
 * Admin class
 */

class Admin {

	public function __construct() {

		// Embed the Script on our Plugin's Option Page Only
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check for display/routing, no form data processed.
		if ( isset( $_GET['page'] ) && ( $_GET['page'] == 'prime_slider_options' ) ) {
			add_action( 'admin_init', [ $this, 'admin_script' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
		}

		add_action( 'admin_init', [ $this, 'admin_biggopti_script' ] );

		add_action( 'after_setup_theme', [ $this, 'whitelabel' ] );

		// register_activation_hook(BDTPS_CORE__FILE__, 'install_and_activate');
		
		add_action('admin_init', [ $this, 'biggopti_styles' ] );

		add_filter( 'plugin_action_links_' . BDTPS_CORE_PBNAME, [ $this, 'plugin_action_links' ] );

	}

	function biggopti_styles(){
		wp_enqueue_style('bdtps-admin-biggopti', BDTPS_CORE_ADMIN_URL . 'assets/css/ps-admin-biggopti.css', [], BDTPS_CORE_VER);
		wp_enqueue_style('bdt-product-feed', BDTPS_CORE_ADMIN_URL . 'assets/css/ps-product-feed.css', [], BDTPS_CORE_VER);
	}

	function install_and_activate() {

		// I don't know of any other redirect function, so this'll have to do.
		wp_safe_redirect( admin_url( 'admin.php?page=prime_slider_options' ) );
		exit;
	}

	/**
	 * Register plugin row and action meta links.
	 * @return void
	 */
	public function whitelabel() {
		add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );
		add_filter( 'plugin_action_links_' . BDTPS_CORE_PBNAME, [ $this, 'plugin_action_meta' ] );
	}

	/**
	 * Enqueue styles
	 * @access public
	 */

	public function enqueue_styles() {

		$direction_suffix = is_rtl() ? '.rtl' : '';

		wp_enqueue_style( 'bdt-uikit', BDTPS_CORE_ASSETS_URL . 'css/bdt-uikit' . $direction_suffix . '.css', [], '3.15.3' );
		wp_enqueue_style( 'prime-slider-font', BDTPS_CORE_ASSETS_URL . 'css/prime-slider-font' . $direction_suffix . '.css', [], BDTPS_CORE_VER );

		// wp_enqueue_style( 'bdtps-admin', BDTPS_CORE_ADMIN_URL . 'assets/css/ps-admin' . $direction_suffix . '.css', [], BDTPS_CORE_VER );

		wp_enqueue_style( 'bdtps-admin', BDTPS_CORE_ADMIN_URL . 'assets/css/ps-admin.css', [], BDTPS_CORE_VER );

		wp_enqueue_script( 'bdt-uikit', BDTPS_CORE_ASSETS_URL . 'js/bdt-uikit.min.js', [ 'jquery' ], '3.15.3', true );
	}

	/**
	 * Row meta
	 * @access public
	 * @return array
	 */

	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( BDTPS_CORE_PBNAME === $plugin_file ) {
			$row_meta = [ 
				'docs'  => '<a href="https://bdthemes.com/support/" aria-label="' . esc_attr( __( 'Go for Get Support', 'bdthemes-prime-slider-lite' ) ) . '" target="_blank">' . __( 'Get Support', 'bdthemes-prime-slider-lite' ) . '</a>',
				'video' => '<a href="https://www.youtube.com/playlist?list=PLP0S85GEw7DOJf_cbgUIL20qqwqb5x8KA" aria-label="' . esc_attr( __( 'View Prime Slider Video Tutorials', 'bdthemes-prime-slider-lite' ) ) . '" target="_blank">' . __( 'Video Tutorials', 'bdthemes-prime-slider-lite' ) . '</a>',
			];

			$plugin_meta = array_merge( $plugin_meta, $row_meta );
		}

		return $plugin_meta;
	}

	/**
	 * Action meta
	 * @access public
	 * @return array
	 */


	public function plugin_action_meta( $links ) {

		$links = array_merge( [ sprintf( '<a href="%s">%s</a>', prime_slider_dashboard_link( '#prime_slider_welcome' ), esc_html__( 'Settings', 'bdthemes-prime-slider-lite' ) ) ], $links );

		$links = array_merge( $links, [ 
			sprintf(
				'<a href="%s">%s</a>',
				prime_slider_dashboard_link( '#license' ),
				esc_html__( 'License', 'bdthemes-prime-slider-lite' )
			)
		] );

		return $links;
	}

	/**
	 * Plugin action links
	 * @access public
	 * @return array
	 */

	 public function plugin_action_links( $plugin_meta ) {

		$row_meta = [
			'settings' => '<a href="'.admin_url( 'admin.php?page=prime_slider_options' ) .'" aria-label="' . esc_attr(__('Go to settings', 'bdthemes-prime-slider-lite')) . '" >' . __('Settings', 'bdthemes-prime-slider-lite') . '</b></a>',
		];

        $plugin_meta = array_merge($plugin_meta, $row_meta);

        return $plugin_meta;
    }

	/**
	 * Change Prime Slider Name
	 * @access public
	 * @return string
	 */

	public function prime_slider_name_change( $translated_text, $text, $domain ) {
		switch ( $translated_text ) {
			case 'Prime Slider':
				$translated_text = BDTPS_CORE_TITLE;
				break;
		}

		return $translated_text;
	}

	/**
	 * Hiding plugins //still in testing purpose
	 * @access public
	 */

	public function hide_prime_slider() {
		global $wp_list_table;
		$hide_plg_array = array( 'bdthemes-prime-slider/bdthemes-prime-slider.php' );
		$all_plugins    = $wp_list_table->items;

		foreach ( $all_plugins as $key => $val ) {
			if ( in_array( $key, $hide_plg_array ) ) {
				unset( $wp_list_table->items[ $key ] );
			}
		}
	}
	
	/**
	 * Register admin script
	 * @access public
	 */

	public function admin_script() {
		$suffix = '.min';
		if ( is_admin() ) { // for Admin Dashboard Only
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-form' );

			wp_enqueue_script( 'bdtps-chart', BDTPS_CORE_ADMIN_URL . 'assets/js/chart.min.js', [ 'jquery' ], '4.5.1', true );
			wp_enqueue_script( 'bdtps-admin', BDTPS_CORE_ADMIN_URL . 'assets/js/ps-admin' . $suffix . '.js', [ 'jquery', 'bdtps-chart' ], BDTPS_CORE_VER, true );
		}
	}

	/**
	 * Register admin biggopti script
	 * @access public
	 */

	public function admin_biggopti_script() {
		$suffix = '.min';
		if ( is_admin() ) { // for Admin Dashboard Only

			wp_enqueue_script( 'bdtps-biggopti', BDTPS_CORE_ADMIN_URL . 'assets/js/ps-biggopti.js', [ 'jquery' ], BDTPS_CORE_VER, true );

			$dismissals = get_option('bdt_biggopti_dismissals', []);
			$dismissed_display_ids = [];
			// Keys written since the dismissal store was namespaced carry the
			// plugin prefix; older ones do not, so strip whichever is present.
			$namespace = class_exists( __NAMESPACE__ . '\\Biggopties' ) ? Biggopties::DISMISSAL_KEY_PREFIX : 'bdtps_biggopti_';
			$prefix = 'bdt-admin-biggopti-api-biggopti-';
			foreach (array_keys($dismissals) as $key) {
				if (strpos($key, $namespace) === 0) {
					$key = substr($key, strlen($namespace));
				}
				if (strpos($key, $prefix) === 0) {
					$key = substr($key, strlen($prefix));
				}
				$dismissed_display_ids[] = $key;
			}

			$current_sector = '';
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check for display/routing, no form data processed.
			if ( isset( $_GET['page'] ) && $_GET['page'] === 'prime_slider_options' ) {
				$current_sector = 'plugin_dashboard';
			}

			$script_config = [
				'ajaxurl'				=> admin_url('admin-ajax.php'),
				'nonce'					=> wp_create_nonce('prime-slider'),
				'isPro'             	=> function_exists('bdtps_is_pro_activated') && bdtps_is_pro_activated(),
				'assetsUrl'         	=> defined('BDTPS_CORE_ASSETS_URL') ? BDTPS_CORE_ASSETS_URL : '',
				'dismissedDisplayIds'	=> $dismissed_display_ids,
				'currentSector'      	=> $current_sector,
			];
			
			wp_localize_script('bdtps-biggopti', 'PrimeSliderBiggoptiConfig', $script_config);
		}
	}
}
