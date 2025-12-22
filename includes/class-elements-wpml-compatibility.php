<?php

namespace PrimeSlider\Includes;

/**
 * PrimeSlider_WPML class
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class PrimeSlider_WPML {

	/**
	 * A reference to an instance of this class.
	 * @since 3.1.0
	 * @var   object
	 */
	private static $instance = null;

	/**
	 * Constructor for the class
	 */
	public function init() {

		// WPML existence check - register nodes when WPML core or String Translation is present
		if ( defined( 'WPML_ST_VERSION' ) || defined( 'WPML_VERSION' ) || defined( 'ICL_SITEPRESS_VERSION' ) || function_exists( 'icl_register_string' ) ) {
			add_filter( 'wpml_elementor_widgets_to_translate', array( $this, 'add_translatable_nodes' ) );
		}

	}

	/**
	 * Load wpml required repeater class files.
	 * @return void
	 */
	public function load_wpml_modules() {

		require_once( BDTPS_CORE_PATH . 'includes/compatiblity/wpml/wpml-module-with-items.php' );
		
		require_once( BDTPS_CORE_PATH . 'includes/compatiblity/wpml/class-wpml-prime-slider-blog.php' );
		require_once( BDTPS_CORE_PATH . 'includes/compatiblity/wpml/class-wpml-prime-slider-dragon.php' );
		require_once( BDTPS_CORE_PATH . 'includes/compatiblity/wpml/class-wpml-prime-slider-elysium.php' );
		require_once( BDTPS_CORE_PATH . 'includes/compatiblity/wpml/class-wpml-prime-slider-general.php' );
		require_once( BDTPS_CORE_PATH . 'includes/compatiblity/wpml/class-wpml-prime-slider-isolate.php' );
		require_once( BDTPS_CORE_PATH . 'includes/compatiblity/wpml/class-wpml-prime-slider-mount.php' );
		require_once( BDTPS_CORE_PATH . 'includes/compatiblity/wpml/class-wpml-prime-slider-multiscroll.php' );
		require_once( BDTPS_CORE_PATH . 'includes/compatiblity/wpml/class-wpml-prime-slider-omatic.php' );
		require_once( BDTPS_CORE_PATH . 'includes/compatiblity/wpml/class-wpml-prime-slider-pagepiling.php' );
		require_once( BDTPS_CORE_PATH . 'includes/compatiblity/wpml/class-wpml-prime-slider-sequester.php' );
		require_once( BDTPS_CORE_PATH . 'includes/compatiblity/wpml/class-wpml-prime-slider-sniper.php' );
		require_once( BDTPS_CORE_PATH . 'includes/compatiblity/wpml/class-wpml-prime-slider-tango.php' );
		require_once( BDTPS_CORE_PATH . 'includes/compatiblity/wpml/class-wpml-prime-slider-woocommerce.php' );
		require_once( BDTPS_CORE_PATH . 'includes/compatiblity/wpml/class-wpml-prime-slider-woolamp.php' );
	}

	/**
	 * Add prime slider translation nodes
	 * @param array $nodes_to_translate
	 * @return array
	 */
	public function add_translatable_nodes( $nodes_to_translate ) {

		$this->load_wpml_modules();

		$nodes_to_translate['prime-slider-blog'] = [
			'conditions'        => [
				'widgetType' => 'prime-slider-blog',
			],
			'integration-class' => __NAMESPACE__ . '\\WPML_PrimeSlider_Blog',
			'fields'            => [
				[
					'field'       => 'button_text',
					'type'        => esc_html__( 'Read More', 'bdthemes-prime-slider' ),
					'editor_type' => 'LINE',
				],
				[
					'field'       => 'scroll_button_text',
					'type'        => esc_html__( 'Scroll Down', 'bdthemes-prime-slider' ),
					'editor_type' => 'LINE',
				],
				[
					'field'       => 'follow_us_text',
					'type'        => esc_html__( 'Follow Us', 'bdthemes-prime-slider' ),
					'editor_type' => 'LINE',
				]
			]
		];

		$nodes_to_translate['prime-slider-dragon'] = [
			'conditions'        => [
				'widgetType' => 'prime-slider-dragon',
			],
			'integration-class' => [
				__NAMESPACE__ . '\\WPML_PrimeSlider_Dragon',
				__NAMESPACE__ . '\\WPML_PrimeSlider_Dragon_Social_Link',
			],
			'fields'            => [],
		];

		$nodes_to_translate['prime-slider-elysium'] = [
			'conditions'        => [
				'widgetType' => 'prime-slider-elysium',
			],
			'integration-class' => __NAMESPACE__ . '\\WPML_PrimeSlider_Elysium',
			'fields'            => [
				[
					'field'       => 'navigation_previous_text',
					'type'        => esc_html__( 'Previous Slide', 'bdthemes-prime-slider' ),
					'editor_type' => 'LINE',
				],
				[
					'field'       => 'navigation_next_text',
					'type'        => esc_html__( 'Next Slide', 'bdthemes-prime-slider' ),
					'editor_type' => 'LINE',
				],
			],
		];

        $nodes_to_translate['prime-slider-general'] = [
			'conditions'        => [
				'widgetType' => 'prime-slider-general',
			],
			'integration-class' => [
				__NAMESPACE__ . '\\WPML_PrimeSlider_General',
				__NAMESPACE__ . '\\WPML_PrimeSlider_General_Social_Link',
			],
			'fields' => [
				[
					'field'       => 'general_follow_us_text',
					'type'        => esc_html__( 'Follow Us', 'bdthemes-prime-slider' ),
					'editor_type' => 'LINE',
				],
				[
					'field'       => 'scroll_button_text',
					'type'        => esc_html__( 'Scroll Down', 'bdthemes-prime-slider' ),
					'editor_type' => 'LINE',
				],
			],
		];

        $nodes_to_translate['prime-slider-isolate'] = [
			'conditions'        => [
				'widgetType' => 'prime-slider-isolate',
			],
			'integration-class' => [
				__NAMESPACE__ . '\\WPML_PrimeSlider_Isolate',
				__NAMESPACE__ . '\\WPML_PrimeSlider_Isolate_Social_Link',
			],
			'fields'            => [
				[
					'field' 	  => 'scroll_button_text',
					'type'  	  => esc_html__( 'Follow Us', 'bdthemes-prime-slider' ),
					'editor_type' => 'LINE',
				],
			],
		];

        $nodes_to_translate['prime-slider-mount'] = [
			'conditions' 		=> [
				'widgetType' => 'prime-slider-mount',
			],
			'integration-class' => [
				__NAMESPACE__ . '\\WPML_PrimeSlider_Mount',
				__NAMESPACE__ . '\\WPML_PrimeSlider_Mount_Social_Link',
			],
			'fields'            => [
				[
					'field'       => 'follow_us_text',
					'type'        => esc_html__( 'Follow Us', 'bdthemes-prime-slider' ),
					'editor_type' => 'LINE',
				],
			],
		];

        $nodes_to_translate['prime-slider-multiscroll'] = [
			'conditions'        => [
				'widgetType' => 'prime-slider-multiscroll',
			],
			'integration-class' => __NAMESPACE__ . '\\WPML_PrimeSlider_Multiscroll',
			'fields'            => [],
		];

        $nodes_to_translate['prime-slider-omatic'] = [
			'conditions'        => [
				'widgetType' => 'prime-slider-omatic',
			],
			'integration-class' => __NAMESPACE__ . '\\WPML_PrimeSlider_Omatic',
			'fields'            => [],
		];

        $nodes_to_translate['prime-slider-pacific'] = [
			'conditions'        => [
				'widgetType' => 'prime-slider-pacific',
			],
			'fields'            => [
				[
					'field'       => 'read_more_text',
					'type'        => esc_html__( 'Read More', 'bdthemes-prime-slider' ),
					'editor_type' => 'LINE',
				],
			]
		];

		$nodes_to_translate['prime-slider-pagepiling'] = [
			'conditions'        => [
				'widgetType' => 'prime-slider-pagepiling',
			],
			'integration-class' => [
				__NAMESPACE__ . '\\WPML_PrimeSlider_Pagepiling',
				__NAMESPACE__ . '\\WPML_PrimeSlider_Pagepiling_Social_Link',
			],
			'fields'            => [
				[
					'field'       => 'social_main_title',
					'type'        => esc_html__( 'Follow Us', 'bdthemes-prime-slider' ),
					'editor_type' => 'LINE',
				]
			],
		];

		$nodes_to_translate['prime-slider-sequester'] = [
			'conditions'        => [
				'widgetType' => 'prime-slider-sequester',
			],
			'integration-class' => [
				__NAMESPACE__ . '\\WPML_PrimeSlider_Sequester',
				__NAMESPACE__ . '\\WPML_PrimeSlider_Sequester_Social_Link',
			],
			'fields'            => [],
		];

		$nodes_to_translate['prime-slider-sniper'] = [
			'conditions'        => [
				'widgetType' => 'prime-slider-sniper',
			],
			'integration-class' => __NAMESPACE__ . '\\WPML_PrimeSlider_Sniper',
			'fields'            => [],
		];

		$nodes_to_translate['prime-slider-tango'] = [
			'conditions'        => [
				'widgetType' => 'prime-slider-tango',
			],
			'integration-class' => __NAMESPACE__ . '\\WPML_PrimeSlider_Tango',
			'fields'            => [],
		];

		$nodes_to_translate['prime-slider-woocommerce'] = [
			'conditions'        => [
				'widgetType' => 'prime-slider-woocommerce',
			],
			'integration-class' => __NAMESPACE__ . '\\WPML_PrimeSlider_Woocommerce',
			'fields'            => [
				[
					'field'       => 'scroll_button_text',
					'type'        => esc_html__( 'Scroll Down', 'bdthemes-prime-slider' ),
					'editor_type' => 'LINE',
				],
			],
		];

		$nodes_to_translate['prime-slider-woolamp'] = [
			'conditions'        => [
				'widgetType' => 'prime-slider-woolamp',
			],
			'integration-class' => __NAMESPACE__ . '\\WPML_PrimeSlider_WooLamp',
			'fields'            => [],
		];
		
		return $nodes_to_translate;
	}

	/**
	 * Returns the instance.
	 * @since  3.1.0
	 * @return object
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}
}
