<?php

namespace PrimeSlider;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin Feeds
 */
class Admin_Feeds {

	private $settings;

	/**
	 * Static variable to track if the feed has been displayed
	 */
	private static $feed_displayed = false;

	/**
	 * Admin_Feeds constructor.
	 */
	public function __construct( $settings ) {
		$this->settings = $settings;
		add_action( 'wp_dashboard_setup', [ $this, 'register_rss_feeds' ] );
	}

	/**
	 * Register RSS Feeds for Prime Slider admin dashboard.
	 */
	public function register_rss_feeds() {
		if ( self::$feed_displayed ) {
			/**
			 * If the feed has already been displayed, do not add it again
			 */
			return;
		}

		wp_add_dashboard_widget(
			'bdt-dashboard-overview',
			esc_html( $this->settings['feed_title'] ),
			[ $this, 'display_rss_feeds_content' ],
			null,
			null,
			'column4',
			'core'
		);

		/**
		 * Mark the feed as displayed
		 */
		self::$feed_displayed = true;
	}

	/**
	 * Display RSS Feeds Content
	 */
	public function display_rss_feeds_content() {
		echo wp_kses_post( $this->get_rss_posts_data() );
	}

	/**
	 * Get RSS Posts Data
	 *
	 * @return string
	 */
	private function get_rss_posts_data() {
		$transient_key = $this->settings['transient_key'] . '_rss';
		$cached_data   = get_transient( $transient_key );

		if ( ! empty( $cached_data ) ) {
			/**
			 * Decode as associative array
			 */
			$rss_items = json_decode( $cached_data, true );
		} else {
			// Core's feed API, loaded here and used on the very next line.
			require_once ABSPATH . WPINC . '/feed.php';

			$rss = fetch_feed( $this->settings['feed_link'] );

			if ( is_wp_error( $rss ) ) {
				return '<li>' . esc_html__( 'Items Not Found', 'bdthemes-prime-slider-lite' ) . '.</li>';
			}

			$maxitems  = $rss->get_item_quantity( 5 );
			$rss_items = $rss->get_items( 0, $maxitems );

			/**
			 * Convert RSS items to a simpler array to avoid serialization issues
			 */
			$simplified_rss_items = array_map( function ($item) {
				return [ 
					'title'   => $item->get_title(),
					'link'    => $item->get_permalink(),
					'date'    => $item->get_date( 'U' ),
					'content' => $item->get_content(),
				];
			}, $rss_items );

			set_transient( $transient_key, wp_json_encode( $simplified_rss_items ), 6 * HOUR_IN_SECONDS );
			$rss_items = $simplified_rss_items;
		}

		ob_start();
		?>
		<div class="bdt-widget">
			<ul>
				<?php if ( empty( $rss_items ) ) : ?>
					<li><?php esc_html_e( 'Items Not Found', 'bdthemes-prime-slider-lite' ); ?>.</li>
				<?php else : ?>
					<?php foreach ( $rss_items as $item ) : ?>
						<li>
							<a target="_blank" href="<?php echo esc_url( $item['link'] ); ?>"
								title="<?php echo esc_html( $item['date'] ); ?>">
								<?php if ( $this->is_feed_item_new( $item['date'] ) ) : ?>
									<span class="bdt-feed-badge bdt-feed-badge--new"><?php esc_html_e( 'New', 'bdthemes-prime-slider-lite' ); ?></span>
								<?php endif; ?>
								<?php echo esc_html( $item['title'] ); ?>
							</a>
							<span class="bdt-date" style="display: block; margin: 0;">
								<?php echo esc_html( human_time_diff( $item['date'], current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'bdthemes-prime-slider-lite' ) ); ?>
							</span>
							<div class="bdt-summary">
								<?php echo esc_html( wp_html_excerpt( $item['content'], 120 ) . ' [...]' ); ?>
							</div>
						</li>
					<?php endforeach; ?>
				<?php endif; ?>
			</ul>
		</div>
		<p class="community-events-footer" style="margin: 12px -12px 6px -12px; padding: 12px 12px 0px;">
			<?php
			foreach ( $this->settings['footer_links'] as $link ) {
				printf(
					'<a href="%s" target="_blank">%s <span class="screen-reader-text"> (opens in a new tab)</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a>',
					esc_url( $link['url'] ),
					esc_html( $link['title'] )
				);

				if ( next( $this->settings['footer_links'] ) ) {
					echo ' | ';
				}
			}
			?>
		</p>
		<?php
		return ob_get_clean();
	}

	/**
	 * Check if a feed item is "new" (published within the last 7 days).
	 *
	 * @param int|string $date Unix timestamp.
	 * @return bool
	 */
	private function is_feed_item_new( $date ) {
		$timestamp = is_numeric( $date ) ? (int) $date : strtotime( $date );
		if ( ! $timestamp ) {
			return false;
		}
		$cutoff = time() - ( 7 * DAY_IN_SECONDS );
		return $timestamp >= $cutoff;
	}
}

$bdtps_admin_feed_settings = array(
	'feed_title'       => 'BdThemes News & Updates',
	'transient_key'    => 'bdthemes_product_feeds',
	'feed_link'        => 'https://bdthemes.com/feed',
	'text_domain'      => 'bdthemes-prime-slider-lite',
	'footer_links'     => [ 
		[ 
			'url'   => 'https://bdthemes.com/blog/',
			'title' => 'Blog',
		],
		[ 
			'url'   => 'https://bdthemes.com/knowledge-base/',
			'title' => 'Docs',
		],
		[ 
			'url'   => 'https://store.bdthemes.com/',
			'title' => 'Get Pro',
		],
		[ 
			'url'   => 'https://feedback.bdthemes.com/announcements?category=category_7wo5zoxl',
			'title' => 'Changelog',
		],
	],
);

new Admin_Feeds( $bdtps_admin_feed_settings );

