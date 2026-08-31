<?php
/**
 * Quiet upgrader skin used by the setup wizard's plugin installer.
 *
 * This file extends a WordPress core admin class, so it must only ever be
 * loaded from inside an admin request handler, immediately after core's
 * wp-admin/includes/class-wp-upgrader.php has been required. Never load it at
 * file scope.
 *
 * @package PrimeSlider
 */

namespace PrimeSlider\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\\Quiet_Upgrader_Skin' ) && class_exists( '\\WP_Upgrader_Skin' ) ) {

	/**
	 * Overwrites the feedback method of WP_Upgrader_Skin to suppress the
	 * normal installer output during an AJAX install.
	 */
	class Quiet_Upgrader_Skin extends \WP_Upgrader_Skin {

		/**
		 * Suppress normal upgrader feedback / output.
		 *
		 * @param string $string  Feedback string or message key.
		 * @param mixed  ...$args Optional text replacements.
		 */
		public function feedback( $string, ...$args ) {
			/* no output */
		}
	}
}
