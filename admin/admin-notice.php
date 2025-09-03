<?php

namespace PrimeSlider;

/**
 * Notices class
 */
class Notices {

	private static $notices = [];

	private static $instance;

	public static function get_instance() {
		if (!isset(self::$instance)) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	public function __construct() {

		//add_action('admin_notices', [$this, 'show_notices']);
		add_action('wp_ajax_prime-slider-notices', [$this, 'dismiss']);

		// AJAX endpoint to fetch API notices on demand (after page load)
		add_action('wp_ajax_ps_fetch_api_notices', [$this, 'ajax_fetch_api_notices']);
	}

	/**
	 * Get Remote Notices Data from API
	 *
	 * @return array|mixed
	 */
	private function get_api_notices_data() {
		// 6-hour transient cache for API response
		$transient_key = 'ps_api_notices_prime_slider';
		$cached = get_transient($transient_key);
		if ($cached !== false && is_array($cached)) {
			return $cached;
		}

		// API endpoint for notices - you can change this to your actual endpoint
		$api_url = 'https://store.bdthemes.com/api/notices/api-data-records';

		$response = wp_remote_get($api_url, [
			'timeout' => 30,
			'headers' => [
				'Accept' => 'application/json',
				'X-ALLOW-KEY'  => 'bdthemes',
			],
		]);

		if (is_wp_error($response)) {
			return [];
		}

		$response_code = wp_remote_retrieve_response_code($response);
		$response_body = wp_remote_retrieve_body($response);
		$notices = json_decode($response_body);
		
		if( isset($notices->api) && isset($notices->api->{'prime-slider'}) ) {
			$data = $notices->api->{'prime-slider'};
			if (is_array($data)) {
				$ttl = apply_filters('ps_api_notices_cache_ttl', 6 * HOUR_IN_SECONDS);
				set_transient($transient_key, $data, $ttl);
				return $data;
			}
		}

		return [];
	}

	/**
	 * Check if a notice should be shown based on its enabled status and date range.
	 *
	 * @param object $notice The notice data from the API.
	 * @return bool True if the notice should be shown, false otherwise.
	 */
	private function should_show_notice($notice) {
		// Development override - set to true to bypass date checks for testing
		$development_mode = false; // Set to true to bypass date checks
		
		if ($development_mode) {
			return true;
		}
		
		// Check if the notice is enabled
		if (!isset($notice->is_enabled) || !$notice->is_enabled) {
			return false;
		}

		// Check plugin compatibility
		if (!$this->is_notice_compatible_with_plugin($notice)) {
			return false;
		}

		// Check if the notice has a start date and end date
		if (!isset($notice->start_date) || !isset($notice->end_date)) {
			return false;
		}

		// Get timezone from notice or default to UTC
		$timezone = isset($notice->timezone) ? $notice->timezone : 'UTC';
		
		// Create DateTime objects with proper timezone (using global namespace)
		$start_date = new \DateTime($notice->start_date, new \DateTimeZone($timezone));
		$end_date = new \DateTime($notice->end_date, new \DateTimeZone($timezone));
		$current_date = new \DateTime('now', new \DateTimeZone($timezone));

		// Convert to timestamps for comparison
		$start_timestamp = $start_date->getTimestamp();
		$end_timestamp = $end_date->getTimestamp();
		$current_timestamp = $current_date->getTimestamp();

		// Check if the current date is within the start and end dates
		if ($current_timestamp < $start_timestamp || $current_timestamp > $end_timestamp) {
			return false;
		}

		// Check if notice should be visible after a certain time
		if (isset($notice->visible_after) && $notice->visible_after > 0) {
			$visible_after_timestamp = $start_timestamp + $notice->visible_after;
			if ($current_timestamp < $visible_after_timestamp) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if a notice is compatible with the current plugin installation
	 *
	 * @param object $notice The notice data from the API.
	 * @return bool True if the notice should be shown, false otherwise.
	 */
	private function is_notice_compatible_with_plugin($notice) {
		// Get current plugin info
		$current_plugin_slug = $this->get_current_plugin_slug();
		$is_pro_active = function_exists('_is_ps_pro_activated') ? _is_ps_pro_activated() : false;
		$is_lite_active = $current_plugin_slug === 'bdthemes-prime-slider-lite';
		$is_pro_plugin = $current_plugin_slug === 'bdthemes-prime-slider';
		
		// Get notice targets from API, default to ['both']
		$client_targets = (isset($notice->client_targets) && is_array($notice->client_targets))
			? $notice->client_targets
			: ['both'];

		// True if 'pro_targeted' is one of the targets
		$pro_targeted = in_array('pro_targeted', $client_targets, true);

		
		// Ensure client_targets is always an array
		if (!is_array($client_targets)) {
			$client_targets = [$client_targets];
		}
		
		// Handle pro_targeted parameter (only for free version)
		if ($pro_targeted && $is_lite_active) {
			// If pro_targeted is true, only show if pro is NOT active
			$should_show = !$is_pro_active;
			return $should_show;
		}
		
		// Check if any of the client targets match current plugin status
		foreach ($client_targets as $target) {
			$target = trim($target); // Clean up any whitespace
			
			switch ($target) {
				case 'pro':
					// Pro-only notices: show only if pro is active
					if ($is_pro_active) {
						return true;
					}
					break;
					
				case 'free':
					if ($is_lite_active) {
						return true;
					}
					break;
			}
		}
		
		return false;
	}

	/**
	 * Get current plugin slug
	 *
	 * @return string
	 */
	private function get_current_plugin_slug() {
		// Get plugin basename from current file
		$plugin_file = plugin_basename(BDTPS_CORE__FILE__);
		
		// Extract plugin slug from basename
		$plugin_slug = dirname($plugin_file);
		
		return $plugin_slug;
	}

	/**
	 * Render API notice HTML
	 *
	 * @param object $notice
	 * @return string
	 */
	private function render_api_notice($notice) {
		ob_start();
		
		// Add custom CSS if provided
		if (isset($notice->custom_css) && !empty($notice->custom_css)) {
			echo '<style>' . wp_kses_post($notice->custom_css) . '</style>';
		}
		
		// Prepare background styles
		$background_style = '';
		$wrapper_classes = 'bdt-notice-wrapper';
		
		if (isset($notice->background_color) && !empty($notice->background_color)) {
			$background_style .= 'background-color: ' . esc_attr($notice->background_color) . ';';
		}
		
		if (isset($notice->image) && !empty($notice->image)) {
			$background_style .= 'background-image: url(' . esc_url($notice->image) . ');';
			$wrapper_classes .= ' has-background-image';
		}
		
		?>
		<div class="<?php echo esc_attr($wrapper_classes); ?>" <?php echo $background_style ? 'style="' . $background_style . '"' : ''; ?>>
			
			
			<?php $title = (isset($notice->title) && !empty($notice->title)) ? $notice->title : ''; ?>

			<div class="bdt-api-notice-content">
				<div class="bdt-plugin-logo-wrapper">
					<img height="auto" width="40" src="<?php echo esc_url(BDTPS_CORE_ASSETS_URL); ?>images/logo.png" alt="Prime Slider Logo">
				</div>

				<div class="bdt-notice-content">
					<div class="bdt-notice-content-inner">
						<?php if (isset($notice->logo) && !empty($notice->logo)) : ?>
							<div class="bdt-notice-logo-wrapper">
								<img width="100" src="<?php echo esc_url($notice->logo); ?>" alt="Logo">
							</div>
						<?php endif; ?>
						<div class="bdt-notice-title-description">
							<?php if (isset($title) && !empty($title)) : ?>
								<h2 class="bdt-notice-title"><?php echo wp_kses_post($title); ?></h2>
							<?php endif; ?>
		
							<?php if (isset($notice->content) && !empty($notice->content)) : ?>
								<div class="bdt-notice-html-content">
									<?php echo wp_kses_post($notice->content); ?>
								</div>
							<?php endif; ?>
						</div>
					</div>

					<div class="bdt-notice-content-right">
						<?php 
						// Only show countdown if it's enabled, has an end date, and the end date is in the future
						$show_countdown = isset($notice->show_countdown) && $notice->show_countdown && isset($notice->end_date);
						if ($show_countdown) {
							$end_timestamp = strtotime($notice->end_date);
							$current_timestamp = current_time('timestamp');
							$show_countdown = $end_timestamp > $current_timestamp;
						}
						?>
						<?php if ($show_countdown) : ?>
							<div class="bdt-notice-countdown" data-end-date="<?php echo esc_attr($notice->end_date); ?>" data-timezone="<?php echo esc_attr($notice->timezone ? $notice->timezone : 'UTC'); ?>">
								<div class="countdown-timer">Loading...</div>
							</div>
						<?php endif; ?>
		
						<?php if (isset($notice->link) && !empty($notice->link)) : ?>
							<div class="bdt-notice-btn">
								<a href="<?php echo esc_url($notice->link); ?>" target="_blank">
									<div class="nm-notice-btn">
										<?php echo isset($notice->button_text) ? esc_html($notice->button_text) : 'Read More'; ?>
										<span class="dashicons dashicons-arrow-right-alt"></span>
									</div>
								</a>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public static function add_notice($args = []) {
		if (is_array($args)) {
			self::$notices[] = $args;
		}
	}

	/**
	 * AJAX: Build and return API notices HTML for dynamic injection
	 */
	public function ajax_fetch_api_notices() {
		$nonce = isset($_POST['_wpnonce']) ? sanitize_text_field($_POST['_wpnonce']) : '';
		if (!wp_verify_nonce($nonce, 'prime-slider')) {
			wp_send_json_error([ 'message' => 'invalid_nonce' ]);
		}

		if (!current_user_can('manage_options')) {
			wp_send_json_error([ 'message' => 'forbidden' ]);
		}

		$notices = $this->get_api_notices_data();
		$grouped_notices = [];

		if (is_array($notices)) {
			foreach ($notices as $index => $notice) {
				if ($this->should_show_notice($notice)) {
					$notice_class = isset($notice->notice_class) ? $notice->notice_class : 'default-' . $index;
					if (!isset($grouped_notices[$notice_class])) {
						$grouped_notices[$notice_class] = $notice;
					}
				}
			}
		}

		// Build notices using the same pipeline as synchronous rendering
		foreach ($grouped_notices as $notice_class => $notice) {
			$notice_id = isset($notice->id) ? $notice_class : $notice->id;

			self::add_notice([
				'id' => 'api-notice-' . $notice_id,
				'type' => isset($notice->type) ? $notice->type : 'info',
				'category'         => isset($notice->category) ? $notice->category : 'regular',
				'dismissible' => true,
				'html_message' => $this->render_api_notice($notice),
				'dismissible-meta' => 'transient',
				'dismissible-time' => isset($notice->end_date) ? max((new \DateTime($notice->end_date, new \DateTimeZone('UTC')))->getTimestamp() - time(), 0) : WEEK_IN_SECONDS,
			]);
		}

		ob_start();
		$this->show_notices();
		$markup = ob_get_clean();

		wp_send_json_success([ 'html' => $markup ]);
	}

	/**
	 * Dismiss Notice.
	 */
	public function dismiss() {
		$nonce = (isset($_POST['_wpnonce'])) ? sanitize_text_field($_POST['_wpnonce']) : '';
		$id   = (isset($_POST['id'])) ? esc_attr($_POST['id']) : '';
		$time = (isset($_POST['time'])) ? esc_attr($_POST['time']) : '';
		$meta = (isset($_POST['meta'])) ? esc_attr($_POST['meta']) : '';

		if ( ! wp_verify_nonce($nonce, 'bdthemes-prime-slider-lite') && ! wp_verify_nonce($nonce, 'prime-slider') ) {
			wp_send_json_error();
		}

		if ( ! current_user_can('manage_options') ) {
			wp_send_json_error();
		}

		/**
		 * Valid inputs?
		 */
		if (!empty($id)) {
			// Handle regular notices
			if ('user' === $meta) {
				update_user_meta(get_current_user_id(), $id, true);
			} else {
				set_transient($id, true, $time);
			}

			wp_send_json_success();
		}

		wp_send_json_error();
	}

	/**
	 * Notice Types
	 */
	public function show_notices() {

		$defaults = [
			'id'               => '',
			'type'             => 'info',
			'category'         => 'regular',
			'show_if'          => true,
			'message'          => '',
			'class'            => 'prime-slider-notice',
			'dismissible'      => false,
			'dismissible-meta' => 'transient',
			'dismissible-time' => WEEK_IN_SECONDS,
			'data'             => '',
		];

		foreach (self::$notices as $key => $notice) {

			$notice = wp_parse_args($notice, $defaults);

			// Check if notice is for White Label
			if (defined('BDTPS_CORE_WL') && $notice['category'] === 'regular') {
				continue;
			}

			$classes = ['notice'];

			$classes[] = $notice['class'];
			if (isset($notice['type'])) {
				$classes[] = 'notice-' . $notice['type'];
			}

			// Is notice dismissible?
			if (true === $notice['dismissible']) {
				$classes[] = 'is-dismissible';

				// Dismissable time.
				$notice['data'] = ' dismissible-time=' . esc_attr($notice['dismissible-time']) . ' ';
			}

			// Notice ID.
			$notice_id    = 'bdt-admin-notice-' . $notice['id'];
			$notice['id'] = $notice_id;
			if (!isset($notice['id'])) {
				$notice_id    = 'bdt-admin-notice-' . $notice['id'];
				$notice['id'] = $notice_id;
			} else {
				$notice_id = $notice['id'];
			}

			$notice['classes'] = implode(' ', $classes);

			// User meta.
			$notice['data'] .= ' dismissible-meta=' . esc_attr($notice['dismissible-meta']) . ' ';
			if ('user' === $notice['dismissible-meta']) {
				$expired = get_user_meta(get_current_user_id(), $notice_id, true);
			} elseif ('transient' === $notice['dismissible-meta']) {
				$expired = get_transient($notice_id);
			}

			// Notices visible after transient expire.
			if (isset($notice['show_if'])) {

				if (true === $notice['show_if']) {

					// Is transient expired?
					if (false === $expired || empty($expired)) {
						self::notice_layout($notice);
					}
				}
			} else {

				// No transient notices.
				self::notice_layout($notice);
			}
		}
	}

	/**
	 * New Notice Layout
	 * @param  array $notice Notice notice_layout.
	 * @return void
	 * @since 6.11.3
	 */

	public static function notice_layout($notice = []) {

		if( isset($notice['html_message']) && ! empty($notice['html_message']) ) {
			self::new_notice_layout($notice);
			return;
		}

	?>
		<div id="<?php echo esc_attr($notice['id']); ?>" class="<?php echo esc_attr($notice['classes']); ?>" <?php echo esc_attr($notice['data']); ?>>
			<div class="bdt-notice-wrapper">
				<div class="bdt-notice-icon-wrapper">
					<img height="25" width="25" src="<?php echo esc_url (BDTPS_CORE_ASSETS_URL ); ?>images/logo.png">
				</div>

				<div class="bdt-notice-content">
					<?php if (isset($notice['title']) && !empty($notice['title'])) : ?>
						<h2 class="bdt-notice-title"><?php echo wp_kses_post($notice['title']); ?></h2>
					<?php endif; ?>

					<p class="bdt-notice-text"><?php echo wp_kses_post($notice['message']); ?></p>

					<?php if (isset($notice['action_link']) && !empty($notice['action_link'])) : ?>
						<div class="bdt-notice-btn">
							<a href="#">Renew Now</a>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		
<?php
	}

	public static function new_notice_layout( $notice = [] ) {
		?>
		<div id="<?php echo esc_attr( $notice['id'] ); ?>" class="<?php echo esc_attr( $notice['classes'] ); ?>" <?php echo esc_attr( $notice['data'] ); ?>>				
			<?php echo wp_kses_post( $notice['html_message'] );	?>
		</div>
		
		<?php
	}
}

Notices::get_instance();
