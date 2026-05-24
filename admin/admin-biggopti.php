<?php

namespace PrimeSlider;

/**
 * Biggopties class
 */
class Biggopties {

	private static $biggopties = [];

	private static $instance;

	public static function get_instance() {
		if (!isset(self::$instance)) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	public function __construct() {

		//add_action('admin_notices', [$this, 'show_biggopties']);
		add_action('wp_ajax_prime_slider_biggopties', [$this, 'dismiss']);
	}

	public static function add_biggopti($args = []) {
		if (is_array($args)) {
			self::$biggopties[] = $args;
		}
	}

	/**
	 * Dismiss Biggopti.
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
			// Handle regular biggopti
			if ('user' === $meta) {
				update_user_meta(get_current_user_id(), $id, true);
			} else {
				// Store in transient for backward compatibility
				set_transient($id, true, $time);
				
				// Also store in options table for persistence
				$dismissals_option = get_option('bdt_biggopti_dismissals', []);
				$dismissals_option[$id] = [
					'dismissed_at' => time(),
					'expires_at' => time() + intval($time),
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

			// Check if biggopti is for White Label
			if (defined('BDTPS_WL') && $biggopti['category'] === 'regular') {
				continue;
			}

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
			$biggopti['data'] .= ' dismissible-meta=' . esc_attr($biggopti['dismissible-meta']) . ' ';
			if ('user' === $biggopti['dismissible-meta']) {
				$expired = get_user_meta(get_current_user_id(), $biggopti_id, true);
			} elseif ('transient' === $biggopti['dismissible-meta']) {
				// Check transient first
				$expired = get_transient($biggopti_id);
				
				// If transient not found, check options table for persistent dismissal
				if (false === $expired || empty($expired)) {
					$dismissals_option = get_option('bdt_biggopti_dismissals', []);
					if (isset($dismissals_option[$biggopti_id])) {
						$dismissal = $dismissals_option[$biggopti_id];
						// Check if dismissal is still valid (not expired)
						if (isset($dismissal['expires_at']) && time() < $dismissal['expires_at']) {
							$expired = true;
						} else {
							// Clean up expired dismissal from options
							unset($dismissals_option[$biggopti_id]);
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
