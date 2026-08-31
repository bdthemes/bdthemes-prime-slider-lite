<?php

namespace PrimeSlider\Modules\Tango\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Repeater;
use Elementor\Plugin;
use PrimeSlider\Utils;

use PrimeSlider\Traits\Global_Widget_Controls;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Tango extends Widget_Base {

	use Global_Widget_Controls;

	public function get_name() {
		return 'prime-slider-tango';
	}

	public function get_title() {
		return BDTPS . esc_html__('Tango', 'bdthemes-prime-slider-lite');
	}

	public function get_icon() {
		return 'bdt-widget-icon ps-wi-tango';
	}

	public function get_categories() {
		return ['prime-slider'];
	}

	public function get_keywords() {
		return ['prime slider', 'slider', 'tango', 'prime'];
	}

	public function get_style_depends() {
		return ['swiper', 'prime-slider-font', 'bdtps-tango'];
	}

	public function get_script_depends() {
		// Add-ons (e.g. Prime Slider Pro) append their own handles via this filter.
		return $this->addon_script_depends( [ 'swiper', 'bdtps-tango' ] );
	}

	public function get_custom_help_url() {
		return 'https://youtu.be/OdXH9cSgdz4';
	}

	public function has_widget_inner_wrapper(): bool {
        return ! \Elementor\Plugin::$instance->experiments->is_feature_active( 'e_optimized_markup' );
    }
	protected function is_dynamic_content(): bool {
		return false;
	}

	protected function register_controls() {

		$this->start_controls_section(
			'section_content_sliders',
			[
				'label' => esc_html__('Sliders', 'bdthemes-prime-slider-lite'),
			]
		);

		$repeater = new Repeater();

		$repeater->add_control(
			'sub_title',
			[
				'label'       => esc_html__('Label', 'bdthemes-prime-slider-lite'),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'dynamic'     => ['active' => true],
			]
		);

		/**
         * Repeater Title Controls
         */
        $this->register_repeater_title_controls($repeater);

		/**
         * Repeater Title Link Controls
         */
        $this->register_repeater_title_link_controls($repeater);

		/**
         * Repeater Image Controls
         */
        $this->register_repeater_image_controls($repeater);

		$this->add_control(
			'slides',
			[
				'label'   => esc_html__('Slider Items', 'bdthemes-prime-slider-lite'),
				'type'    => Controls_Manager::REPEATER,
				'fields'  => $repeater->get_controls(),
				'default' => [
					[
						'sub_title' => esc_html__('Label', 'bdthemes-prime-slider-lite'),
						'title'     => esc_html__('Item One', 'bdthemes-prime-slider-lite'),
						'image'     => ['url' => BDTPS_CORE_ASSETS_URL . 'images/gallery/item-1.svg']
					],
					[
						'sub_title' => esc_html__('Label', 'bdthemes-prime-slider-lite'),
						'title'     => esc_html__('Item Two', 'bdthemes-prime-slider-lite'),
						'image'     => ['url' => BDTPS_CORE_ASSETS_URL . 'images/gallery/item-4.svg']
					],
					[
						'sub_title' => esc_html__('Label', 'bdthemes-prime-slider-lite'),
						'title'     => esc_html__('Item Three', 'bdthemes-prime-slider-lite'),
						'image'     => ['url' => BDTPS_CORE_ASSETS_URL . 'images/gallery/item-5.svg']
					],
					[
						'sub_title' => esc_html__('Label', 'bdthemes-prime-slider-lite'),
						'title'     => esc_html__('Item Four', 'bdthemes-prime-slider-lite'),
						'image'     => ['url' => BDTPS_CORE_ASSETS_URL . 'images/gallery/item-6.svg']
					],
				],
				'title_field' => '{{ title }}',
			]
		);

		$this->end_controls_section();
		
		$this->start_controls_section(
			'section_content_layout',
			[
				'label' => esc_html__('Additional Options', 'bdthemes-prime-slider-lite'),
			]
		);

		$this->add_responsive_control(
			'columns',
			[
				'label'          => __( 'Columns', 'bdthemes-prime-slider-lite' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => 3,
				'tablet_default' => 3,
				'mobile_default' => 1,
				'options'        => [
					1 => '1',
					2 => '2',
					3 => '3',
					4 => '4',
					5 => '5',
					6 => '6',
				],
			]
		);

		$this->add_responsive_control(
			'item_gap',
			[
				'label'   => __('Item Gap', 'bdthemes-prime-slider-lite'),
				'type'    => Controls_Manager::SLIDER,
				'default' => [
					'size' => 40,
				],
				'tablet_default' => [
					'size' => 30,
				],
				'mobile_default' => [
					'size' => 0,
				],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
			]
		);

		$this->add_responsive_control(
			'slider_min_height',
			[
				'label' => esc_html__('Height', 'bdthemes-prime-slider-lite'),
				'type'  => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 50,
						'max' => 1024,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bdt-prime-slider-tango .bdt-item' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'slider_bottom_spacing',
			[
				'label' => esc_html__('Slider Bottom Spacing', 'bdthemes-prime-slider-lite'),
				'type'  => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 10,
						'max' => 200,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bdt-prime-slider-tango' => 'margin-bottom: {{SIZE}}{{UNIT}};', 
					'{{WRAPPER}} .bdt-prime-slider-tango .swiper-pagination' => 'transform: translateX(-50%) translateY({{SIZE}}{{UNIT}});',
				],
			]
		);

		/**
		* Show Title Controls
		*/
		$this->register_show_title_controls();

		$this->add_control(
			'show_sub_title',
			[
				'label'   => esc_html__('Show Label', 'bdthemes-prime-slider-lite'),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		/**
		* Show Navigation Controls
		*/
		$this->register_show_navigation_controls();

		$this->add_control(
			'navigation_center_arrows',
			[
				'label'   => esc_html__('Center Arrows', 'bdthemes-prime-slider-lite'),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
				'condition' => [
					'show_navigation_arrows' => 'yes'
				]
			]
		);

		/**
		* Show Pagination Controls
		*/
		$this->register_show_pagination_controls();

		$this->add_control(
			'hide_on_mobile',
			[
				'label'   => esc_html__('Pagination Hide on Mobile', 'bdthemes-prime-slider-lite'),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
				'condition' => [
					'show_navigation_dots' => 'yes'
				],
				'prefix_class' => 'bdt-pagination-hide-',
			]
		);

		$this->add_responsive_control(
            'content_alignment',
            [
                'label'   => esc_html__( 'Alignment', 'bdthemes-prime-slider-lite' ),
                'type'    => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__( 'Left', 'bdthemes-prime-slider-lite' ),
                        'icon'  => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__( 'Center', 'bdthemes-prime-slider-lite' ),
                        'icon'  => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__( 'Right', 'bdthemes-prime-slider-lite' ),
                        'icon'  => 'eicon-text-align-right',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider-tango .bdt-content-wrap' => 'text-align: {{VALUE}};',
                ],
            ]
		);

		$this->add_control(
			'item_up_down',
			[
				'label'   => esc_html__('Item Up Down', 'bdthemes-prime-slider-lite') . BDTPS_CORE_NC,
				'type'    => Controls_Manager::SWITCHER,
				'prefix_class' => 'bdt-item-up-down-',
				'render_type'  => 'template',
			]
		);

		$this->add_control(
			'item_wrapper_link',
			[
				'label'   => esc_html__('Item Wrapper Link', 'bdthemes-prime-slider-lite') . BDTPS_CORE_NC,
				'type'    => Controls_Manager::SWITCHER,
			]
		);

		/**
		* Thumbnail Size Controls
		*/
		$this->register_thumbnail_size_controls();

		$this->end_controls_section();

		$this->start_controls_section(
			'section_carousel_settings',
			[
				'label' => __( 'Slider Settings', 'bdthemes-prime-slider-lite' ),
			]
		);

		$this->add_control(
			'skin',
			[
				'label'   => esc_html__( 'Layout', 'bdthemes-prime-slider-lite' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'carousel',
				'options' => [
					'carousel'  => esc_html__( 'Carousel', 'bdthemes-prime-slider-lite' ),
					'coverflow' => esc_html__( 'Coverflow', 'bdthemes-prime-slider-lite' ),
				],
				'prefix_class' => 'bdt-carousel-style-',
				'render_type'  => 'template',
			]
		);

		$this->add_control(
            'coverflow_toggle',
            [
                'label' => __( 'Coverflow Effect', 'bdthemes-prime-slider-lite' ),
                'type' => Controls_Manager::POPOVER_TOGGLE,
                'return_value' => 'yes',
				'condition' => [
					'skin' => 'coverflow'
				]
            ]
		);

		$this->start_popover();
		
		$this->add_control(
			'coverflow_rotate',
			[
				'label'   => esc_html__( 'Rotate', 'bdthemes-prime-slider-lite' ),
				'type'    => Controls_Manager::SLIDER,
				'default' => [
					'size' => 0,
				],
				'range' => [
					'px' => [
						'min'  => -360,
						'max'  => 360,
						'step' => 5,
					],
				],
				'condition' => [
                    'coverflow_toggle' => 'yes'
				],
				'render_type'  => 'template',
			]
		);

        $this->add_control(
			'coverflow_stretch',
			[
				'label' => __( 'Stretch', 'bdthemes-prime-slider-lite' ),
				'type'  => Controls_Manager::SLIDER,
				'default' => [
					'size' => 180,
				],
				'range' => [
					'px' => [
						'min'  => 0,
						'step' => 10,
						'max'  => 200,
					],
				],
				'condition' => [
                    'coverflow_toggle' => 'yes'
				],
				'render_type'  => 'template',
			]
		);

        $this->add_control(
			'coverflow_modifier',
			[
				'label' => __( 'Modifier', 'bdthemes-prime-slider-lite' ),
				'type'  => Controls_Manager::SLIDER,
				'default' => [
					'size' => 1,
				],
				'range' => [
					'px' => [
						'min'  => 1,
						'step' => 1,
						'max'  => 10,
					],
				],
				'condition' => [
                    'coverflow_toggle' => 'yes'
				],
				'render_type'  => 'template',
			]
		);

		$this->add_control(
			'coverflow_depth',
			[
				'label' => __( 'Depth', 'bdthemes-prime-slider-lite' ),
				'type'  => Controls_Manager::SLIDER,
				'default' => [
					'size' => 100,
				],
				'range' => [
					'px' => [
						'min'  => 0,
						'step' => 10,
						'max'  => 1000,
					],
				],
				'condition' => [
                    'coverflow_toggle' => 'yes'
				],
				'render_type'  => 'template',
			]
		);

		$this->add_control(
			'slide_shadows',
			[
				'label'       => __( 'Slide Shadows', 'bdthemes-prime-slider-lite' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => 'yes',
				'render_type' => 'template',
			]
		);

		$this->end_popover();

		$this->add_control(
			'match_height',
			[
				'label' => __( 'Item Match Height', 'bdthemes-prime-slider-lite' ),
				'type'  => Controls_Manager::SWITCHER,
			]
		);

		/**
		 * Autoplay Controls
		 */
		$this->register_autoplay_controls();

		$this->add_responsive_control(
			'slides_to_scroll',
			[
				'type'      => Controls_Manager::SELECT,
				'label'     => esc_html__( 'Slides to Scroll', 'bdthemes-prime-slider-lite' ),
				'default'        => 1,
				'tablet_default' => 1,
				'mobile_default' => 1,
				'options'   => [
					1 => '1',
					2 => '2',
					3 => '3',
					4 => '4',
					5 => '5',
					6 => '6',
				],
			]
		);

		/**
		 * Grab Cursor Controls
		 */
		$this->register_grab_cursor_controls();

		/**
		 * Loop Controls
		 */
		$this->register_loop_controls();

		/**
		 * Speed & Observer Controls
		 */
		$this->register_speed_observer_controls();

		$this->end_controls_section();

		/**
		 * Extension point: add-ons (e.g. Prime Slider Pro) register their own
		 * controls here. This plugin registers none of its own.
		 */
		$this->register_addon_controls();

		//style
		$this->start_controls_section(
			'section_style_layout',
			[
				'label'     => __( 'Content', 'bdthemes-prime-slider-lite' ),
				'tab'       => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'item_padding',
			[
				'label'      => __( 'Content Padding', 'bdthemes-prime-slider-lite' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'{{WRAPPER}} .bdt-prime-slider-tango .bdt-content-wrap' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
				],
			]
		);


		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_image',
			[
				'label'     => __( 'Image', 'bdthemes-prime-slider-lite' ),
				'tab'       => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'image_overlay',
				'label' => esc_html__('Background', 'bdthemes-prime-slider-lite'),
				'types' => ['classic', 'gradient'],
				// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Elementor widget query built from user-configured controls; caching/query shape is expected.
				'exclude' => ['image'],
				'selector' => '{{WRAPPER}} .bdt-prime-slider-tango .bdt-image-wrap::before',
				'fields_options' => [
					'background' => [
						'label' => esc_html__('Overlay Color', 'bdthemes-prime-slider-lite'),
						'default' => 'gradient',
					],
					'color' => [
						'default' => '#000',
					],
					'color_b' => [
						'default' => '#00000000',
					],
					'gradient_type' => [
						'default' => 'linear',
					],
					'gradient_angle' => [
						'default' => [
							'unit' => 'deg',
							'size' => 360,
						],
					],
				],
			]
		);

		$this->add_responsive_control(
			'image_border_radius',
			[
				'label'      => __( 'Border Radius', 'bdthemes-prime-slider-lite' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .bdt-prime-slider-tango .bdt-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; clip-path: inset(10% 0 10% 0 round {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}});',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_title',
			[
				'label'     => __( 'Title', 'bdthemes-prime-slider-lite' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_title' => 'yes',
				],
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => __( 'Color', 'bdthemes-prime-slider-lite' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bdt-prime-slider-tango .bdt-title, {{WRAPPER}} .bdt-prime-slider-tango .bdt-title a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'title_hover_color',
			[
				'label'     => __( 'Hover Color', 'bdthemes-prime-slider-lite' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bdt-prime-slider-tango .bdt-title:hover, {{WRAPPER}} .bdt-prime-slider-tango .bdt-title a:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .bdt-prime-slider-tango .bdt-title',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name' => 'title_text_shadow',
				'label' => __( 'Text Shadow', 'bdthemes-prime-slider-lite'),
				'selector' => '{{WRAPPER}} .bdt-prime-slider-tango .bdt-title, {{WRAPPER}} .bdt-prime-slider-tango .bdt-title a',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_sub_title',
			[
				'label'     => __( 'Label', 'bdthemes-prime-slider-lite' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_sub_title' => 'yes',
				],
			]
		);

		$this->add_control(
			'sub_title_color',
			[
				'label'     => __( 'Color', 'bdthemes-prime-slider-lite' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bdt-prime-slider-tango .bdt-subtitle' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'sub_title_shadow',
				'selector' => '{{WRAPPER}} .bdt-prime-slider-tango .bdt-subtitle',
			]
		);

		$this->add_responsive_control(
			'sub_title_spacing',
			[
				'label' => esc_html__('Spacing', 'bdthemes-prime-slider-lite'),
				'type'  => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bdt-prime-slider-tango .bdt-subtitle' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'sub_title_typography',
				'selector' => '{{WRAPPER}} .bdt-prime-slider-tango .bdt-subtitle',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_navigation',
			[
				'label'     => __('Navigation', 'bdthemes-prime-slider-lite'),
				'tab'       => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'arrows_heading',
			[
				'label'     => __('Arrows', 'bdthemes-prime-slider-lite'),
				'type'      => Controls_Manager::HEADING,
				'condition' => [
					'show_navigation_arrows' => ['yes'],
				],
			]
		);

		$this->start_controls_tabs('tabs_arrows_style');

        $this->start_controls_tab(
            'tab_arrows_normal',
            [
                'label' => esc_html__('Normal', 'bdthemes-prime-slider-lite'),
				'condition' => [
					'show_navigation_arrows' => ['yes'],
				],
            ]
        );

		$this->add_control(
			'arrows_color',
			[
				'label'     => __('Color', 'bdthemes-prime-slider-lite'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bdt-prime-slider-tango .bdt-navigation-arrows .bdt-navigation-next, {{WRAPPER}} .bdt-prime-slider-tango .bdt-navigation-arrows .bdt-navigation-prev' => 'color: {{VALUE}}',
				],
				'condition' => [
					'show_navigation_arrows' => ['yes'],
				],
			]
		);

		$this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'arrows_background_color',
                'selector' => '{{WRAPPER}} .bdt-prime-slider-tango .bdt-navigation-arrows .bdt-navigation-next, {{WRAPPER}} .bdt-prime-slider-tango .bdt-navigation-arrows .bdt-navigation-prev',
				'condition' => [
					'show_navigation_arrows' => ['yes'],
				],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'arrows_border',
                'selector' => '{{WRAPPER}} .bdt-prime-slider-tango .bdt-navigation-arrows .bdt-navigation-next, {{WRAPPER}} .bdt-prime-slider-tango .bdt-navigation-arrows .bdt-navigation-prev',
				'condition' => [
					'show_navigation_arrows' => ['yes'],
				],
            ]
        );

        $this->add_responsive_control(
            'arrows_border_radius',
            [
                'label' => __('Border Radius', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider-tango .bdt-navigation-arrows .bdt-navigation-next, {{WRAPPER}} .bdt-prime-slider-tango .bdt-navigation-arrows .bdt-navigation-prev' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
				'condition' => [
					'show_navigation_arrows' => ['yes'],
				],
            ]
        );

        $this->add_responsive_control(
            'arrows_text_padding',
            [
                'label' => __('Padding', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider-tango .bdt-navigation-arrows .bdt-navigation-next, {{WRAPPER}} .bdt-prime-slider-tango .bdt-navigation-arrows .bdt-navigation-prev' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
				'condition' => [
					'show_navigation_arrows' => ['yes'],
				],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'arrows_box_shadow',
                'selector' => '{{WRAPPER}} .bdt-prime-slider-tango .bdt-navigation-arrows .bdt-navigation-next, {{WRAPPER}} .bdt-prime-slider-tango .bdt-navigation-arrows .bdt-navigation-prev',
				'condition' => [
					'show_navigation_arrows' => ['yes'],
				],
            ]
        );

		$this->add_responsive_control(
			'arrows_size',
			[
				'label' => esc_html__('Size', 'bdthemes-prime-slider-lite'),
				'type'  => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 10,
						'max' => 50,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bdt-prime-slider-tango .bdt-navigation-arrows .bdt-navigation-next, {{WRAPPER}} .bdt-prime-slider-tango .bdt-navigation-arrows .bdt-navigation-prev' => 'font-size: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'show_navigation_arrows' => ['yes'],
				],
			]
		);

		$this->add_responsive_control(
			'arrows_spacing',
			[
				'label' => esc_html__('Spacing', 'bdthemes-prime-slider-lite'),
				'type'  => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 10,
						'max' => 500,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bdt-prime-slider-tango .bdt-navigation-arrows .bdt-navigation-prev' => 'margin-right: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'show_navigation_arrows' => ['yes'],
					'navigation_center_arrows' => ''
				],
			]
		);

		$this->add_responsive_control(
			'arrows_acx_position',
			[
				'label'   => __( 'Spacing', 'bdthemes-prime-slider-lite' ),
				'type'    => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => -200,
						'max' => 200,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .bdt-prime-slider-tango .bdt-navigation-arrows .bdt-navigation-prev' => 'left: {{SIZE}}px;',
					'{{WRAPPER}} .bdt-prime-slider-tango .bdt-navigation-arrows .bdt-navigation-next' => 'right: {{SIZE}}px;',
				],
				'condition' => [
					'show_navigation_arrows' => 'yes',
					'navigation_center_arrows' => 'yes'
				]
			]
		);

		$this->end_controls_tab();

        $this->start_controls_tab(
            'tab_arrows_hover',
            [
                'label' => esc_html__('Hover', 'bdthemes-prime-slider-lite'),
				'condition' => [
					'show_navigation_arrows' => ['yes'],
				],
            ]
        );

		$this->add_control(
			'arrows_hover_color',
			[
				'label'     => __('Color', 'bdthemes-prime-slider-lite'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bdt-prime-slider-tango .bdt-navigation-arrows .bdt-navigation-next:hover, {{WRAPPER}} .bdt-prime-slider-tango .bdt-navigation-arrows .bdt-navigation-prev:hover' => 'color: {{VALUE}}',
				],
				'condition' => [
					'show_navigation_arrows' => ['yes'],
				],
			]
		);

		$this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name' => 'arrows_background_color_hover',
                'selector' => '{{WRAPPER}} .bdt-prime-slider-tango .bdt-navigation-arrows .bdt-navigation-next:hover, {{WRAPPER}} .bdt-prime-slider-tango .bdt-navigation-arrows .bdt-navigation-prev:hover',
				'condition' => [
					'show_navigation_arrows' => ['yes'],
				],
            ]
        );

		$this->add_control(
            'arrows_hover_border_color',
            [
                'label' => __('Border Color', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::COLOR,
                'condition' => [
                    'arrows_border_border!' => '',
					'show_navigation_arrows' => ['yes'],
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider-tango .bdt-navigation-arrows .bdt-navigation-next:hover, {{WRAPPER}} .bdt-prime-slider-tango .bdt-navigation-arrows .bdt-navigation-prev:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_control(
			'pagination_heading',
			[
				'label'     => __('Pagination', 'bdthemes-prime-slider-lite'),
				'type'      => Controls_Manager::HEADING,
				'condition' => [
					'show_navigation_dots' => ['yes'],
				],
				'separator' => 'before'
			]
		);

		$this->add_control(
			'pagination_color',
			[
				'label'     => __('Color', 'bdthemes-prime-slider-lite'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bdt-prime-slider-tango .swiper-pagination .swiper-pagination-bullet' => 'background: {{VALUE}}',
				],
				'condition' => [
					'show_navigation_dots' => ['yes'],
				],
			]
		);

		$this->add_control(
			'pagination_active_color',
			[
				'label'     => __('Active Color', 'bdthemes-prime-slider-lite'),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .bdt-prime-slider-tango .swiper-pagination .swiper-pagination-bullet.swiper-pagination-bullet-active' => 'background: {{VALUE}}',
				],
				'condition' => [
					'show_navigation_dots' => ['yes'],
				],
			]
		);

		$this->add_responsive_control(
			'pagination_size',
			[
				'label' => esc_html__('Size', 'bdthemes-prime-slider-lite'),
				'type'  => Controls_Manager::SLIDER,
				'selectors' => [
					'{{WRAPPER}} .bdt-prime-slider-tango .swiper-pagination .swiper-pagination-bullet' => 'width: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'show_navigation_dots' => ['yes'],
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render_header() {
		$settings   = $this->get_settings_for_display();
		$id         = 'bdt-prime-slider-' . $this->get_id();

		$this->add_render_attribute( 'prime-slider-tango', 'id', $id );
		$this->add_render_attribute( 'prime-slider-tango', 'class', [ 'bdt-prime-slider-tango', 'elementor-swiper' ] );

		/**
		 * Reveal Effects
		 */
		$this->add_addon_render_attributes('prime-slider-tango');

		$elementor_vp_lg = get_option( 'elementor_viewport_lg' );
		$elementor_vp_md = get_option( 'elementor_viewport_md' );
		$viewport_lg     = !empty($elementor_vp_lg) ? $elementor_vp_lg - 1 : 1024;
		$viewport_md     = !empty($elementor_vp_md) ? $elementor_vp_md - 1 : 768;

		if ( 'yes' == $settings['match_height'] ) {
			$this->add_render_attribute( 'prime-slider-tango', 'bdt-height-match', 'target: > div > div > div > .bdt-item' );
		}

		$this->add_render_attribute(
			[
				'prime-slider-tango' => [
					'data-settings' => [
						wp_json_encode(array_filter([
							"autoplay"       => ( "yes" == $settings["autoplay"] ) ? [ "delay" => $settings["autoplay_speed"] ] : false,
							"loop"           => ($settings["loop"] == "yes") ? true : false,
							"speed"          => $settings["speed"]["size"],
							"pauseOnHover"   => ("yes" == $settings["pauseonhover"]) ? true : false,
							"slidesPerView"  => isset($settings["columns_mobile"]) ? (int)$settings["columns_mobile"] : 1,
							"slidesPerGroup" => isset($settings["slides_to_scroll_mobile"]) ? (int)$settings["slides_to_scroll_mobile"] : 1,
							"spaceBetween"   => !empty($settings["item_gap_mobile"]["size"]) ? (int)$settings["item_gap_mobile"]["size"] : 0,
							"centeredSlides" => true,
							"grabCursor"     => ($settings["grab_cursor"] === "yes") ? true : false,
							"effect"         => $settings["skin"],
							"observer"       => ($settings["observer"]) ? true : false,
							"observeParents" => ($settings["observer"]) ? true : false,
							"breakpoints"    => [
								(int) $viewport_md => [
									"slidesPerView"  => isset($settings["columns_tablet"]) ? (int)$settings["columns_tablet"] : 2,
									"spaceBetween"   => !empty($settings["item_gap_tablet"]["size"]) ? (int)$settings["item_gap_tablet"]["size"] : 0,
									"slidesPerGroup" => isset($settings["slides_to_scroll_tablet"]) ? (int)$settings["slides_to_scroll_tablet"] : 1,
								],
								(int) $viewport_lg => [
									"slidesPerView"  => isset($settings["columns"]) ? (int)$settings["columns"] : 3,
									"spaceBetween"   => !empty($settings["item_gap"]["size"]) ? (int)$settings["item_gap"]["size"] : 0,
									"slidesPerGroup" => isset($settings["slides_to_scroll"]) ? (int)$settings["slides_to_scroll"] : 1,
								]
							],
							"navigation"         => [
								"nextEl" => "#" . $id . " .bdt-navigation-next",
								"prevEl" => "#" . $id . " .bdt-navigation-prev",
							],
							"pagination"         => [
								"el"             => "#" . $id . " .swiper-pagination",
								// "type"           => 'fraction',
								"clickable"      => "true",
							],

							// "scrollbar" => [
							// 	"el" => "#" . $id . ".swiper-scrollbar",
							// 	// "hide" => true,
							// ],
								


							'coverflowEffect' => [
								'rotate'       => ( "yes" == $settings["coverflow_toggle"] ) ? $settings["coverflow_rotate"]["size"]   : 0,
								'stretch'      => ( "yes" == $settings["coverflow_toggle"] ) ? $settings["coverflow_stretch"]["size"]  : 180,
								'depth'        => ( "yes" == $settings["coverflow_toggle"] ) ? $settings["coverflow_depth"]["size"]    : 100,
								'modifier'     => ( "yes" == $settings["coverflow_toggle"] ) ? $settings["coverflow_modifier"]["size"] : 1,
								'slideShadows' => ($settings["slide_shadows"] === "yes") ? true : false,
							],

            ]))
					]
				]
			]
		);

		$this->add_render_attribute( 'prime-slider', 'class', 'bdt-prime-slider' );

		$direction = is_rtl() ? 'rtl' : 'ltr';
		$this->add_render_attribute([
			'swiper' => [
				'class' => 'swiper-tango swiper',
				'role' => 'region',
				'aria-roledescription' => 'carousel',
				'aria-label' => esc_attr( $this->get_title() . ' ' . esc_html__( 'Slider', 'bdthemes-prime-slider-lite' ) ),
				'dir' => $direction,
			],
		]);

		?>
		<div <?php $this->print_render_attribute_string( 'prime-slider' ); ?>>
		<div <?php $this->print_render_attribute_string( 'prime-slider-tango' ); ?>>
			<div <?php $this->print_render_attribute_string( 'swiper' ); ?>>
				<div class="swiper-wrapper">
		<?php
	}

	public function render_navigation_arrows() {
		$settings = $this->get_settings_for_display();
		
		if ( 'yes' == $settings['navigation_center_arrows'] ) {
			$this->add_render_attribute( 'prime-slider-arrows', 'class', 'bdt-arrows-center' );
		} else {
			$this->add_render_attribute( 'prime-slider-arrows', 'class', 'bdt-arrows-bottom' );
		}
		$this->add_render_attribute( 'prime-slider-arrows', 'class', 'bdt-navigation-arrows bdt-position-z-index reveal-muted' );


		?>

			<?php if ($settings['show_navigation_arrows']) : ?>
			<div <?php $this->print_render_attribute_string( 'prime-slider-arrows' ); ?>>
				<div class="bdt-navigation-prev bdt-slidenav"><i class="ps-wi-arrow-left-5"></i></div>
				<div class="bdt-navigation-next bdt-slidenav"><i class="ps-wi-arrow-right-5"></i></div>
			</div>
			<?php endif; ?>

		<?php
	}

	public function render_navigation_dots() {
		$settings = $this->get_settings_for_display();
		
		?>
			<?php if ($settings['show_navigation_dots']) : ?>
			
			<div class="swiper-pagination reveal-muted"></div>
			<!-- <div class="swiper-scrollbar"></div> -->
			
			<?php endif; ?>
		<?php
	}

	public function render_footer() {
		$settings = $this->get_settings_for_display();
		
		?>
				</div>
			</div>
			
			<?php $this->render_navigation_dots(); ?>
			<?php $this->render_navigation_arrows(); ?>
		</div>
		</div>

		<?php
	}

	public function rendar_item_image($slide) {
		$settings = $this->get_settings_for_display();

		$thumb_url = Group_Control_Image_Size::get_attachment_image_src($slide['image']['id'], 'thumbnail_size', $settings);
		if (!$thumb_url) {
			printf('<img src="%1$s" alt="%2$s" class="bdt-img">', esc_url($slide['image']['url']), esc_html($slide['title']));
		} else {
			print(wp_get_attachment_image(
				$slide['image']['id'],
				$settings['thumbnail_size_size'],
				false,
				[
					'class' => 'bdt-img',
					'alt' => esc_html($slide['title'])
				]
			));
		}
	}

	public function render_slides_loop() {
        $settings = $this->get_settings_for_display();

        foreach ($settings['slides'] as $slide) :
			$title_link_key = 'title-link-' . $slide['_id'];

			if( ! empty($slide['title_link']['url']) && $slide['title']){
				$this->add_link_attributes($title_link_key, $slide['title_link'], true);
			}

			?>

            <div class="swiper-slide bdt-item">
				<div class="bdt-image-wrap">
					<?php $this->rendar_item_image($slide); ?>
				</div>
				<div class="bdt-content-wrap">

				<?php if ($slide['sub_title'] && ('yes' == $settings['show_sub_title'])) : ?>
						<div class="bdt-subtitle" data-reveal="reveal-active">
							<?php echo esc_html( $slide['sub_title'] ); ?>
						</div>
					<?php endif; ?>

					<?php if ($slide['title'] && ('yes' == $settings['show_title'])) : ?>
						<<?php echo esc_attr(Utils::get_valid_html_tag($settings['title_html_tag'])); ?> class="bdt-title" data-reveal="reveal-active">
							<?php if ('' !== $slide['title_link']['url']) : ?>
								<a <?php $this->print_render_attribute_string($title_link_key); ?>>
								<?php endif; ?>
								<?php echo wp_kses( prime_slider_first_word( $slide['title'] ), [ 'span' => [ 'class' => [] ] ] ); ?>
								<?php if ('' !== $slide['title_link']['url']) : ?>
								</a>
							<?php endif; ?>
						</<?php echo esc_attr(Utils::get_valid_html_tag($settings['title_html_tag'])); ?>>
					<?php endif; ?>
				</div>
				<?php if ($settings['item_wrapper_link'] == 'yes' and '' !== $slide['title_link']['url']) : ?>
					<a class="bdt-tango-item-wrap-link" <?php $this->print_render_attribute_string($title_link_key); ?>></a>
				<?php endif; ?>
			</div>

        <?php endforeach;
    }

	public function render() {
		$this->render_header();
		$this->render_slides_loop();
		$this->render_footer();
	}
}
