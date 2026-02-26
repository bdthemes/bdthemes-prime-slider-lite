<?php

namespace PrimeSlider;

/**
 * Admin Api Biggopties class
 */
class AdminApiBiggopties {

	private static $instance;

	public static function get_instance() {
		if (!isset(self::$instance)) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	public function __construct() {
		add_action('wp_ajax_bdt_admin_api_biggopti_dismiss', [$this, 'bdt_admin_api_biggopti_dismiss']);
	}

	/**
	 * Dismiss Admin API Biggopti.
	 */
	public function bdt_admin_api_biggopti_dismiss() {
		$nonce = (isset($_POST['_wpnonce'])) ? sanitize_text_field($_POST['_wpnonce']) : '';
		$display_id = (isset($_POST['display_id'])) ? sanitize_text_field($_POST['display_id']) : '';
		$id   = (isset($_POST['id'])) ? esc_attr($_POST['id']) : '';
		$meta = (isset($_POST['meta'])) ? esc_attr($_POST['meta']) : '';

		if ( ! wp_verify_nonce($nonce, 'prime-slider') ) {
			wp_send_json_error();
		}

		if ( ! current_user_can('manage_options') ) {
			wp_send_json_error();
		}

		// Prefer display_id; fallback: extract from id (bdt-admin-api-biggopti-{display_id})
		if (empty($display_id) && !empty($id)) {
			$prefix = 'bdt-admin-api-biggopti-';
			if (strpos($id, $prefix) === 0) {
				$display_id = substr($id, strlen($prefix));
			} else {
				$display_id = $id;
			}
		}

		/**
		 * Valid inputs?
		 */
		if (!empty($display_id)) {
			if ('user' === $meta) {
				$user_key = 'bdt-admin-api-biggopti-' . $display_id;
				update_user_meta(get_current_user_id(), $user_key, true);
			} else {
				// Save to options table only - display_id based, no end-time expiration
				$dismissals_option = get_option('bdt_biggopti_dismissals', []);
				$dismissals_option[$display_id] = ['dismissed_at' => time()];
				update_option('bdt_biggopti_dismissals', $dismissals_option, false);
			}

			wp_send_json_success();
		}

		wp_send_json_error();
	}
}

AdminApiBiggopties::get_instance();