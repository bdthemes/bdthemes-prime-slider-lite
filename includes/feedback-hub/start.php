<?php
/**
 * Main File
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bdtps_reviews_collector_init' ) ) {
	function bdtps_reviews_collector_init( $params ) {

		if ( is_admin() ) :

			$menu_slug    = isset( $params['menu']['slug'] ) ? $params['menu']['slug'] : false;
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only check of the current admin page slug for display routing, no form data processed.
			$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : false;

			/**
			 * Attach SDK to current page
			 */
			$params['current_page'] = $current_page;
			$params['menu_slug']    = $menu_slug;

			/**
			 * Include SDK
			 */
			require_once dirname( __FILE__ ) . '/rc-biggopti.php';
			if ( function_exists( 'bdtps_reviews_collector_automate' ) ) {
				bdtps_reviews_collector_automate( $params );
			}

		endif;
	}
}
