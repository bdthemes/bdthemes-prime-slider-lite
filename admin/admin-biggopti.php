<?php

namespace PrimeSlider;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Biggopties class
 */
class Biggopties {

	/**
	 * Prefix for every dismissal key this class writes.
	 *
	 * Dismissal ids arrive from an admin AJAX request, so they are never used
	 * as a storage key directly: they are sanitized and namespaced through
	 * dismissal_key() so a request can only ever write inside this plugin's
	 * own transient / user-meta namespace.
	 */
	const DISMISSAL_KEY_PREFIX = 'bdtps_biggopti_';

	private static $biggopties = [];

	private static $instance;

	/**
	 * Build the namespaced storage key for a biggopti dismissal.
	 *
	 * @param string $id Raw biggopti id.
	 * @return string Prefixed key, or '' when the id is unusable.
	 */
	public static function dismissal_key( $id ) {
		// Keep the id readable (case included) so it still round-trips with the
		// markup, but allow only characters that are safe in a storage key.
		$id = preg_replace( '/[^A-Za-z0-9_\-]/', '', (string) $id );

		if ( '' === $id ) {
			return '';
		}

		// Transient keys are limited to 172 characters.
		return substr( self::DISMISSAL_KEY_PREFIX . $id, 0, 172 );
	}

	public static function get_instance() {
		if (!isset(self::$instance)) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	public function __construct() {

		//add_action('admin_notices', [$this, 'show_biggopties']);
		add_action('wp_ajax_prime_slider_biggopties', [$this, 'dismiss']);

		// AJAX endpoint to fetch API biggopties on demand (after page load)
		add_action('wp_ajax_bdtps_fetch_api_biggopties', [$this, 'ajax_fetch_api_biggopties']);
	}

	/**
	 * Get Remote Biggopties Data from API
	 *
	 * @return array|mixed
	 */
	private function get_api_biggopties_data() {
		// API endpoint for biggopties. Empty means the remote source is not in
		// use, in which case no external request is made at all.
		$api_url = '';

		if ('' === $api_url) {
			return [];
		}

		$response = wp_remote_get($api_url, [
			'timeout' => 30,
			'headers' => [
				'Accept' => 'application/json',
			],
		]);

		if (is_wp_error($response)) {
			return [];
		}

		$response_body = wp_remote_retrieve_body($response);
		$biggopties = json_decode($response_body);
		
		if( isset($biggopties) && isset($biggopties->{'prime-slider'}) ) {
			$data = $biggopties->{'prime-slider'};
			if (is_array($data)) {
				return $data;
			}
		}

		return [];
	}

	/**
	 * Check if a biggopti should be shown based on its enabled status and date range.
	 *
	 * @param object $biggopti The biggopti data from the API.
	 * @return bool True if the biggopti should be shown, false otherwise.
	 */
	private function should_show_biggopti($biggopti) {
		// Development override - set to true to bypass date checks for testing
		$development_mode = false; // Set to true to bypass date checks
		
		if ($development_mode) {
			return true;
		}
		
		// Check if the biggopti is enabled
		if (!isset($biggopti->is_enabled) || !$biggopti->is_enabled) {
			return false;
		}

		// Check plugin compatibility
		if (!$this->is_biggopti_compatible_with_plugin($biggopti)) {
			return false;
		}

		// Check if the biggopti has a start date and end date
		if (!isset($biggopti->start_date) || !isset($biggopti->end_date)) {
			return false;
		}

		// Get timezone from biggopti or default to UTC
		$timezone = isset($biggopti->timezone) ? $biggopti->timezone : 'UTC';
		
		// Create DateTime objects with proper timezone (using global namespace)
		$start_date = new \DateTime($biggopti->start_date, new \DateTimeZone($timezone));
		$end_date = new \DateTime($biggopti->end_date, new \DateTimeZone($timezone));
		$current_date = new \DateTime('now', new \DateTimeZone($timezone));

		// Convert to timestamps for comparison
		$start_timestamp = $start_date->getTimestamp();
		$end_timestamp = $end_date->getTimestamp();
		$current_timestamp = $current_date->getTimestamp();

		// Check if the current date is within the start and end dates
		if ($current_timestamp < $start_timestamp || $current_timestamp > $end_timestamp) {
			return false;
		}

		// Check if biggopti should be visible after a certain time
		if (isset($biggopti->visible_after) && $biggopti->visible_after > 0) {
			$visible_after_timestamp = $start_timestamp + $biggopti->visible_after;
			if ($current_timestamp < $visible_after_timestamp) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Should promotional notices be suppressed?
	 *
	 * Neutral extension point. Nothing in this plugin suppresses them; an
	 * add-on can, for example to stop advertising to customers who already
	 * bought the thing being advertised.
	 *
	 * @return bool
	 */
	private function suppress_promotions() {
		return (bool) apply_filters( 'prime_slider/promotions/suppress', false );
	}

	/**
	 * Check if a biggopti is compatible with the current plugin installation
	 *
	 * @param object $biggopti The biggopti data from the API.
	 * @return bool True if the biggopti should be shown, false otherwise.
	 */
	private function is_biggopti_compatible_with_plugin($biggopti) {
		// Get current plugin info
		$current_plugin_slug = $this->get_current_plugin_slug();
		$is_pro_active = function_exists('bdtps_is_pro_activated') ? bdtps_is_pro_activated() : false;
		$is_lite_active = $current_plugin_slug === 'bdthemes-prime-slider-lite';
		$is_pro_plugin = $current_plugin_slug === 'bdthemes-prime-slider';
		
		// Get biggopti targets from API, default to ['both']
		$client_targets = (isset($biggopti->client_targets) && is_array($biggopti->client_targets))
			? $biggopti->client_targets
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
					// Pro-only biggopties: show only if pro is active
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
	 * Render API biggopti HTML
	 *
	 * @param object $biggopti
	 * @return string
	 */
	private function render_api_biggopti($biggopti) {
		ob_start();

		// Prepare background styles
		$background_style = '';
		$wrapper_classes = 'bdt-biggopti-wrapper';
		
		if (isset($biggopti->background_color) && !empty($biggopti->background_color)) {
			$background_style .= 'background-color: ' . esc_attr($biggopti->background_color) . ';';
		}
		
		if (isset($biggopti->image) && !empty($biggopti->image)) {
			$background_style .= 'background-image: url(' . esc_url($biggopti->image) . ');';
			$wrapper_classes .= ' has-background-image';
		}
		
		?>
		<div class="<?php echo esc_attr($wrapper_classes); ?>" <?php echo $background_style ? 'style="' . esc_attr( $background_style ) . '"' : ''; ?>>
			
			
			<?php $title = (isset($biggopti->title) && !empty($biggopti->title)) ? $biggopti->title : ''; ?>

			<div class="bdt-api-biggopti-content">
				<div class="bdt-plugin-logo-wrapper">
					<img height="auto" width="40" src="<?php echo esc_url(BDTPS_CORE_ASSETS_URL); ?>images/logo.png" alt="Prime Slider Logo">
				</div>

				<div class="bdt-biggopti-content">
					<div class="bdt-biggopti-content-inner">
						<?php if (isset($biggopti->logo) && !empty($biggopti->logo)) : ?>
							<div class="bdt-biggopti-logo-wrapper">
								<img width="100" src="<?php echo esc_url($biggopti->logo); ?>" alt="Logo">
							</div>
						<?php endif; ?>
						<div class="bdt-biggopti-title-description">
							<?php if (isset($title) && !empty($title)) : ?>
								<h2 class="bdt-biggopti-title"><?php echo wp_kses_post($title); ?></h2>
							<?php endif; ?>
		
							<?php if (isset($biggopti->content) && !empty($biggopti->content)) : ?>
								<div class="bdt-biggopti-html-content">
									<?php echo wp_kses_post($biggopti->content); ?>
								</div>
							<?php endif; ?>
						</div>
					</div>

					<div class="bdt-biggopti-content-right">
						<?php 
						// Only show countdown if it's enabled, has an end date, and the end date is in the future
						$show_countdown = isset($biggopti->show_countdown) && $biggopti->show_countdown && isset($biggopti->end_date);
						if ($show_countdown) {
							$end_timestamp = strtotime($biggopti->end_date);
							$current_timestamp = current_time('timestamp');
							$show_countdown = $end_timestamp > $current_timestamp;
						}
						?>
						<?php if ($show_countdown) : ?>
							<div class="bdt-biggopti-countdown" data-end-date="<?php echo esc_attr($biggopti->end_date); ?>" data-timezone="<?php echo esc_attr($biggopti->timezone ? $biggopti->timezone : 'UTC'); ?>">
								<div class="countdown-timer">Loading...</div>
							</div>
						<?php endif; ?>
		
						<?php if (isset($biggopti->link) && !empty($biggopti->link)) : ?>
							<div class="bdt-biggopti-btn">
								<a href="<?php echo esc_url($biggopti->link); ?>" target="_blank">
									<div class="nm-biggopti-btn">
										<?php echo isset($biggopti->button_text) ? esc_html($biggopti->button_text) : 'Read More'; ?>
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

	public static function add_biggopti($args = []) {
		if (is_array($args)) {
			self::$biggopties[] = $args;
		}
	}

	/**
	 * AJAX: Build and return API biggopties HTML for dynamic injection
	 */
	public function ajax_fetch_api_biggopties() {
		$nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
		if (!wp_verify_nonce($nonce, 'prime-slider')) {
			wp_send_json_error([ 'message' => 'invalid_nonce' ]);
		}

		if (!current_user_can('manage_options')) {
			wp_send_json_error([ 'message' => 'forbidden' ]);
		}

		// An add-on may ask for promotional notices to be left out entirely.
		if ($this->suppress_promotions()) {
			wp_send_json_success([ 'html' => '' ]);
		}

		// Don't show biggopties on plugin/theme install and upload pages
		$current_url = isset($_POST['current_url']) ? sanitize_text_field(wp_unslash($_POST['current_url'])) : '';
		
		if (!empty($current_url)) {
			$excluded_patterns = [
				'plugin-install.php',
				'theme-install.php',
				'action=upload-plugin',
				'action=upload-theme'
			];
			
			foreach ($excluded_patterns as $pattern) {
				if (strpos($current_url, $pattern) !== false) {
					wp_send_json_success([ 'html' => '' ]);
				}
			}
		}

		$biggopties = $this->get_api_biggopties_data();
		$grouped_biggopties = [];

		if (is_array($biggopties)) {
			foreach ($biggopties as $index => $biggopti) {
				if ($this->should_show_biggopti($biggopti)) {
					$display_id = isset($biggopti->display_id) ? $biggopti->display_id : 'default-' . $index;
					if (!isset($grouped_biggopties[$display_id])) {
						$grouped_biggopties[$display_id] = $biggopti;
					}
				}
			}
		}

		// Build biggopties using the same pipeline as synchronous rendering
		foreach ($grouped_biggopties as $display_id => $biggopti) {
			$biggopti_id = isset($biggopti->id) ? $display_id : $biggopti->id;

			self::add_biggopti([
				'id' => 'api-biggopti-' . $biggopti_id,
				'type' => isset($biggopti->type) ? $biggopti->type : 'info',
				'category'         => isset($biggopti->category) ? $biggopti->category : 'regular',
				'dismissible' => true,
				'html_message' => $this->render_api_biggopti($biggopti),
				'dismissible-meta' => 'transient',
				'dismissible-time' => isset($biggopti->end_date) ? max((new \DateTime($biggopti->end_date, new \DateTimeZone('UTC')))->getTimestamp() - time(), 0) : WEEK_IN_SECONDS,
			]);
		}

		ob_start();
		$this->show_biggopties();
		$markup = ob_get_clean();

		wp_send_json_success([ 'html' => $markup ]);
	}

	/**
	 * Dismiss Biggopti.
	 */
	public function dismiss() {
		$nonce = (isset($_POST['_wpnonce'])) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
		$id   = isset($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : '';
		$time = isset($_POST['time']) ? absint(wp_unslash($_POST['time'])) : 0;
		$meta = isset($_POST['meta']) ? sanitize_text_field(wp_unslash($_POST['meta'])) : '';

		if ( ! wp_verify_nonce($nonce, 'bdthemes-prime-slider-lite') && ! wp_verify_nonce($nonce, 'prime-slider') ) {
			wp_send_json_error();
		}

		if ( ! current_user_can('manage_options') ) {
			wp_send_json_error();
		}

		/**
		 * Valid inputs?
		 */
		$key = self::dismissal_key($id);

		if ('' !== $key) {
			// Never trust the request-supplied lifetime either: clamp it to a
			// sane range so a dismissal cannot be stored effectively forever.
			$time = min(max($time, MINUTE_IN_SECONDS), YEAR_IN_SECONDS);

			// Handle regular biggopti
			if ('user' === $meta) {
				update_user_meta(get_current_user_id(), $key, true);
			} else {
				// Store in transient for backward compatibility
				set_transient($key, true, $time);

				// Also store in options table for persistence
				$dismissals_option = get_option('bdt_biggopti_dismissals', []);
				$dismissals_option[$key] = [
					'dismissed_at' => time(),
					'expires_at' => time() + $time,
				];
				update_option('bdt_biggopti_dismissals', $dismissals_option, false);
			}

			wp_send_json_success();
		}

		wp_send_json_error();
	}

	/**
	 * Biggopti Types
	 */
	public function show_biggopties() {

		$defaults = [
			'id'               => '',
			'type'             => 'info',
			'category'         => 'regular',
			'show_if'          => true,
			'message'          => '',
			'class'            => 'prime-slider-biggopti',
			'dismissible'      => false,
			'dismissible-meta' => 'transient',
			'dismissible-time' => WEEK_IN_SECONDS,
			'data'             => '',
		];

		foreach (self::$biggopties as $key => $biggopti) {

			$biggopti = wp_parse_args($biggopti, $defaults);

			$classes = ['biggopti'];

			$classes[] = $biggopti['class'];
			if (isset($biggopti['type'])) {
				$classes[] = 'biggopti-' . $biggopti['type'];
			}

			// Is biggopti dismissible?
			if (true === $biggopti['dismissible']) {
				$classes[] = 'is-dismissible';

				// Dismissable time.
				$biggopti['data'] = ' dismissible-time=' . esc_attr($biggopti['dismissible-time']) . ' ';
			}

			// Biggopti ID.
			$biggopti_id    = 'bdt-admin-biggopti-' . $biggopti['id'];
			$biggopti['id'] = $biggopti_id;
			if (!isset($biggopti['id'])) {
				$biggopti_id    = 'bdt-admin-biggopti-' . $biggopti['id'];
				$biggopti['id'] = $biggopti_id;
			} else {
				$biggopti_id = $biggopti['id'];
			}

			$biggopti['classes'] = implode(' ', $classes);

			// User meta.
			$biggopti_key = self::dismissal_key($biggopti_id);
			$biggopti['data'] .= ' dismissible-meta=' . esc_attr($biggopti['dismissible-meta']) . ' ';
			if ('user' === $biggopti['dismissible-meta']) {
				$expired = get_user_meta(get_current_user_id(), $biggopti_key, true);

				// Dismissals stored before the keys were namespaced.
				if (empty($expired)) {
					$expired = get_user_meta(get_current_user_id(), $biggopti_id, true);
				}
			} elseif ('transient' === $biggopti['dismissible-meta']) {
				// Check transient first
				$expired = get_transient($biggopti_key);

				// Dismissals stored before the keys were namespaced.
				if (false === $expired || empty($expired)) {
					$expired = get_transient($biggopti_id);
				}

				// If transient not found, check options table for persistent dismissal
				if (false === $expired || empty($expired)) {
					$dismissals_option = get_option('bdt_biggopti_dismissals', []);
					$stored_key        = isset($dismissals_option[$biggopti_key]) ? $biggopti_key : $biggopti_id;

					if (isset($dismissals_option[$stored_key])) {
						$dismissal = $dismissals_option[$stored_key];
						// Check if dismissal is still valid (not expired)
						if (isset($dismissal['expires_at']) && time() < $dismissal['expires_at']) {
							$expired = true;
						} else {
							// Clean up expired dismissal from options
							unset($dismissals_option[$stored_key]);
							update_option('bdt_biggopti_dismissals', $dismissals_option, false);
						}
					}
				}
			}

			// Biggopties visible after transient expire.
			if (isset($biggopti['show_if'])) {

				if (true === $biggopti['show_if']) {

					// Is transient expired?
					if (false === $expired || empty($expired)) {
						self::biggopti_layout($biggopti);
					}
				}
			} else {

				// No transient biggopties.
				self::biggopti_layout($biggopti);
			}
		}
	}

	/**
	 * New Biggopti Layout
	 * @param  array $biggopti Biggopti biggopti_layout.
	 * @return void
	 * @since 6.11.3
	 */

	public static function biggopti_layout($biggopti = []) {

		if( isset($biggopti['html_message']) && ! empty($biggopti['html_message']) ) {
			self::new_biggopti_layout($biggopti);
			return;
		}

	?>
		<div id="<?php echo esc_attr($biggopti['id']); ?>" class="<?php echo esc_attr($biggopti['classes']); ?>" <?php echo esc_attr($biggopti['data']); ?>>
			<div class="bdt-biggopti-wrapper">
				<div class="bdt-biggopti-icon-wrapper">
					<img height="25" width="25" src="<?php echo esc_url (BDTPS_CORE_ASSETS_URL ); ?>images/logo.png">
				</div>

				<div class="bdt-biggopti-content">
					<?php if (isset($biggopti['title']) && !empty($biggopti['title'])) : ?>
						<h2 class="bdt-biggopti-title"><?php echo wp_kses_post($biggopti['title']); ?></h2>
					<?php endif; ?>

					<p class="bdt-biggopti-text"><?php echo wp_kses_post($biggopti['message']); ?></p>

					<?php if (isset($biggopti['action_link']) && !empty($biggopti['action_link'])) : ?>
						<div class="bdt-biggopti-btn">
							<a href="#">Renew Now</a>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
		
<?php
	}

	public static function new_biggopti_layout( $biggopti = [] ) {
		?>
		<div id="<?php echo esc_attr( $biggopti['id'] ); ?>" class="<?php echo esc_attr( $biggopti['classes'] ); ?>" <?php echo esc_attr( $biggopti['data'] ); ?>>				
			<?php echo wp_kses_post( $biggopti['html_message'] );	?>
		</div>
		
		<?php
	}
}

Biggopties::get_instance();
