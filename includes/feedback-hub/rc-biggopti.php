<?php

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('Bdtps_Reviews_Collector')) {
	class Bdtps_Reviews_Collector {

		public $version = '1.0.0';

		/**
		 * Prefix for every option / transient key this SDK writes.
		 */
		const KEY_PREFIX = 'bdtps_rc_';

		public $rc_name;
		public $rc_allow_name;
		public $rc_date_name;
		public $legacy_rc_name;
		public $legacy_allow_name;
		public $legacy_date_name;
		public $rc_count_name;
		public $nonce;
		public $params;
		public $review_url;

		private static $instance = null;

		/**
		 * Get Instance
		 * 
		 * @since 0.0.0
		 */
		public static function get_instance($params) {
			if (!isset(self::$instance)) {
				self::$instance = new self($params);
			}

			return self::$instance;
		}

		/**
		 * Insights SDK Version
		 * param array $params
		 * @return void
		 */
		public function __construct($params) {
			$this->params = $params;
			$this->review_url = isset($params['review_url']) ? $params['review_url'] : false;

			// add_action( 'admin_enqueue_scripts', array( $this, 'rc_enqueue_scripts' ) );
			add_action('wp_ajax_bdtps_reviews_insights', array($this, 'rc_sdk_insights'));
			add_action('wp_ajax_bdtps_reviews_dismiss', array($this, 'rc_sdk_dismiss_biggopti'));

			$security_key = md5($params['plugin_name']);
			$base = str_replace('-', '_', sanitize_title($params['plugin_name']) . '_' . $security_key);

			// Storage keys carry this plugin's own prefix. The pre-4.5.2 keys are
			// kept so a visitor who already answered is not asked again.
			$this->rc_name             = self::KEY_PREFIX . $base;
			$this->legacy_rc_name      = 'rc_' . $base;
			$this->rc_allow_name       = self::KEY_PREFIX . 'allow_' . $base;
			$this->legacy_allow_name   = 'rc_allow_rc_' . $base;
			$this->rc_date_name        = self::KEY_PREFIX . 'date_' . $base;
			$this->legacy_date_name    = 'rc_date_rc_' . $base;
			$rc_count_name             = self::KEY_PREFIX . 'attempt_count_' . $base;
			$rc_status_db = get_option($this->rc_allow_name, get_option($this->legacy_allow_name, false));

			$this->nonce = wp_create_nonce($this->rc_allow_name);

			/**
			 * Show Biggopti after 3 days
			 * Now 5 minutes
			 */
			$installed = get_option($this->rc_date_name . '_installed', get_option($this->legacy_date_name . '_installed', false));

			if (!$installed) {
				$installed = time();
				update_option($this->rc_date_name . '_installed', $installed);
			}

			// if ( $installed && ( time() - $installed ) < 1 * MINUTE_IN_SECONDS ) {
			if ($installed && (time() - $installed) < 3 * DAY_IN_SECONDS) {
				return;
			}

			/**
			 * Show Biggopti
			 */
			if (!$rc_status_db) {
				$this->display_biggopti();
				return;
			}

			/**
			 * If Disallow
			 */
			if ('disallow' == $rc_status_db) {
				return;
			}

			/**
			 * Skip & Date Not Expired
			 * Show Biggopti
			 */
			if ('skip' == $rc_status_db && true == $this->check_date()) {
				$this->display_biggopti();
				return;
			}

			/**
			 * Allowed & Date not Expired
			 * No need send data to server
			 * Else Send Data to Server
			 */
			if (!$this->check_date()) {
				return;
			}

			/**
			 * Count attempt every time
			 */
			$rc_attempt = get_option($rc_count_name, 0);

			if (!$rc_attempt) {
				update_option($rc_count_name, 1);
			}
			update_option($rc_count_name, $rc_attempt + 1);
		}

		/**
		 * Biggopti Modal
		 *
		 * @return void
		 */
		public function display_biggopti() {
			add_action('admin_enqueue_scripts', array($this, 'rc_enqueue_scripts'));

			if (!get_transient(self::KEY_PREFIX . 'dismissed_' . $this->rc_name) && !get_transient('dismissed_biggopti_' . $this->legacy_rc_name)) {
				add_action('admin_notices', array($this, 'display_global_biggopti'));
			}
		}

		/**
		 * If date is expired immidiate action
		 *
		 * @return boolean
		 */
		public function check_date() {
			$current_date = strtotime(gmdate('Y-m-d'));
			$rc_status_date = strtotime(get_option($this->rc_date_name, get_option($this->legacy_date_name, false)));

			if (!$rc_status_date) {
				return true;
			}

			if ($rc_status_date && $current_date >= $rc_status_date) {
				return true;
			}
			return false;
		}

		/**
		 * Reset Options Settings
		 * @return void
		 */
		public function reset_settings() {
			delete_option($this->rc_allow_name);
			delete_option($this->rc_date_name);
			delete_option($this->legacy_allow_name);
			delete_option($this->legacy_date_name);
		}

		/**
		 * Ajax callback
		 */
		public function rc_sdk_insights() {
			$sanitized_status = isset($_POST['button_val']) ? sanitize_text_field(wp_unslash($_POST['button_val'])) : '';
			$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
			$allow_name = isset($_POST['allow_name']) ? sanitize_text_field(wp_unslash($_POST['allow_name'])) : '';
			$date_name = isset($_POST['date_name']) ? sanitize_text_field(wp_unslash($_POST['date_name'])) : '';

			// Confine the writes to this SDK's own option namespace so a request
			// cannot use these to overwrite an arbitrary WordPress option.
			if (0 !== strpos($allow_name, self::KEY_PREFIX . 'allow_')) {
				$allow_name = '';
			}
			if (0 !== strpos($date_name, self::KEY_PREFIX . 'date_')) {
				$date_name = '';
			}

			if (!wp_verify_nonce($nonce, self::KEY_PREFIX . 'sdk')) {
				wp_send_json(array(
					'status' => 'error',
					'title' => 'Error',
					'message' => 'Nonce verification failed',
				));
				wp_die();
			}

			if (!current_user_can('manage_options')) {
				wp_send_json(array(
					'status' => 'error',
					'title' => 'Error',
					'message' => 'Denied, you don\'t have right permission',
				));
				wp_die();
			}

			if ('disallow' == $sanitized_status && $allow_name) {
				update_option($allow_name, 'disallow');
			}

			if ($sanitized_status == 'skip' && $allow_name) {
				update_option($allow_name, 'skip');
				/**
				 * Next schedule date for attempt
				 */
				if ($date_name) {
					update_option($date_name, gmdate('Y-m-d', strtotime("+1 month")));
				}
			} elseif ($sanitized_status == 'yes' && $allow_name) {
				update_option($allow_name, 'yes');
			}

			wp_send_json(array(
				'status' => 'success',
				'title' => 'Success',
				'message' => 'Success.',
				'action' => $sanitized_status,
			));
			wp_die();
		}

		/**
		 * Enqueue scripts and styles.
		 *
		 * @since 1.0.0
		 */
		public function rc_enqueue_scripts() {
			wp_enqueue_style('bdtps-reviews-sdk', plugins_url('assets/rc.css', __FILE__), array(), '1.0.0');
			wp_enqueue_script('bdtps-reviews-sdk', plugins_url('assets/rc.js', __FILE__), array('jquery'), '1.0.0', true);

			// Add inline style to hide all but the first biggopti on page load
			$inline_css = '.rc-global-biggopti { display: none; }';
			wp_add_inline_style( 'bdtps-reviews-sdk', $inline_css );
		}

		/**
		 * Display Global Biggopti
		 *
		 * @return void
		 */
		public function display_global_biggopti() {
			$plugin_title = isset($this->params['plugin_title']) ? $this->params['plugin_title'] : '';
			$plugin_msg = isset($this->params['plugin_msg']) ? $this->params['plugin_msg'] : '';
			$plugin_icon = isset($this->params['plugin_icon']) ? $this->params['plugin_icon'] : '';

?>
			<div class="rc-global-biggopti biggopti biggopti-success is-dismissible <?php echo esc_attr(substr($this->rc_name, 0, -33)); ?>">
				<div class="rc-global-header">
					<?php if (!empty($plugin_icon)) : ?>
						<div class="bdt-biggopti-rc-logo">
							<img src="<?php echo esc_url($plugin_icon); ?>" alt="icon">
						</div>
					<?php endif; ?>

					<div class="bdt-biggopti-rc-content">
						<h3>
							<?php printf(wp_kses_post($plugin_title)); ?>
						</h3>
						<?php printf(wp_kses_post($plugin_msg)); ?>
						<input type="hidden" name="rc_name" value="<?php echo esc_html($this->rc_name); ?>">
						<input type="hidden" name="nonce" value="<?php echo esc_html(wp_create_nonce(self::KEY_PREFIX . 'sdk')); ?>">
						<div class="bdt-biggopti-rc-buttons">
							<button data-rc_name="<?php echo esc_html($this->rc_name); ?>" data-date_name="<?php echo esc_html($this->rc_date_name); ?>" data-allow_name="<?php echo esc_html($this->rc_allow_name); ?>" data-nonce="<?php echo esc_html(wp_create_nonce(self::KEY_PREFIX . 'sdk')); ?>" data-review_url="<?php echo esc_html($this->review_url); ?>" name="rc_allow_status" value="yes" class="rc-button-allow">
								<span class="dashicons dashicons-star-filled" style="margin-top: 3px;"></span> Give us your Review
							</button>
							<button data-rc_name="<?php echo esc_html($this->rc_name); ?>" data-date_name="<?php echo esc_html($this->rc_date_name); ?>" data-allow_name="<?php echo esc_html($this->rc_allow_name); ?>" data-nonce="<?php echo esc_html(wp_create_nonce(self::KEY_PREFIX . 'sdk')); ?>" data-review_url="<?php echo esc_html($this->review_url); ?>" name="rc_allow_status" value="skip" class="rc-button-skip">
								I'll skip for now
							</button>
							<button data-rc_name="<?php echo esc_html($this->rc_name); ?>" data-date_name="<?php echo esc_html($this->rc_date_name); ?>" data-allow_name="<?php echo esc_html($this->rc_allow_name); ?>" data-nonce="<?php echo esc_html(wp_create_nonce(self::KEY_PREFIX . 'sdk')); ?>" data-review_url="<?php echo esc_html($this->review_url); ?>" name="rc_allow_status" value="disallow" class="rc-button-disallow rc-button-danger">
								Hide and Don't show again
							</button>
						</div>
					</div>
				</div>
			</div>
<?php
		}

		/**
		 * Dismiss Biggopti
		 *
		 * @return void
		 */
		public function rc_sdk_dismiss_biggopti() {
			$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
			$rc_name = isset($_POST['rc_name']) ? sanitize_text_field(wp_unslash($_POST['rc_name'])) : '';

			if (!wp_verify_nonce($nonce, self::KEY_PREFIX . 'sdk')) {
				wp_send_json(array(
					'status' => 'error',
					'title' => 'Error',
					'message' => 'Nonce verification failed',
				));
				wp_die();
			}

			if (!current_user_can('manage_options')) {
				wp_send_json(array(
					'status' => 'error',
					'title' => 'Error',
					'message' => 'Denied, you don\'t have right permission',
				));
				wp_die();
			}

			set_transient(self::KEY_PREFIX . 'dismissed_' . $rc_name, true, 30 * DAY_IN_SECONDS);

			wp_send_json(array(
				'status' => 'success',
				'title' => 'Success',
				'message' => 'Success.',
			));
			wp_die();
		}
	}
}

/**
 * Main Insights Function
 */
if (!function_exists('bdtps_reviews_collector_automate')) {
	function bdtps_reviews_collector_automate($params) {
		if (class_exists('Bdtps_Reviews_Collector')) {
			new Bdtps_Reviews_Collector($params);
		}
	}
}
