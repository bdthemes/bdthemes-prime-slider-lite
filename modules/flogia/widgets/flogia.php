<?php

namespace PrimeSlider\Modules\Flogia\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Text_Stroke;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use PrimeSlider\Traits\Global_Widget_Controls;
use PrimeSlider\Traits\QueryControls\GroupQuery\Group_Control_Query;
use PrimeSlider\Utils;
use WP_Query;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

class Flogia extends Widget_Base {

    use Group_Control_Query;
    use Global_Widget_Controls;

    public function get_name() {
        return 'prime-slider-flogia';
    }

    public function get_title() {
        return BDTPS . esc_html__('Flogia', 'bdthemes-prime-slider-lite');
    }

    public function get_icon() {
        return 'bdt-widget-icon ps-wi-flogia';
    }

    public function get_categories() {
        return ['prime-slider'];
    }

    public function get_keywords() {
        return ['prime slider', 'slider', 'blog', 'prime', 'flogia'];
    }

    public function get_style_depends() {
        return ['bdtps-flogia'];
    }

    public function get_script_depends() {
    	// Add-ons (e.g. Prime Slider Pro) append their own handles via this filter.
    	return $this->addon_script_depends( [ 'bdtps-mthumbnail-scroller', 'bdtps-flogia' ] );
    }

    public function get_custom_help_url() {
        return 'https://youtu.be/Ayo1oEALF_8';
    }

    public function has_widget_inner_wrapper(): bool {
        return ! \Elementor\Plugin::$instance->experiments->is_feature_active( 'e_optimized_markup' );
    }

	protected function register_controls() {
        $this->start_controls_section(
            'section_content_layout',
            [
                'label' => esc_html__('Layout', 'bdthemes-prime-slider-lite'),
            ]
        );

        /**
         * Slider Height Controls
         */
        $this->register_slider_height_controls();

        $this->add_responsive_control(
            'content_max_width',
            [
                'label' => esc_html__('Content Max Width', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 220,
                        'max' => 1600,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider-flogia .bdt-ps-container' => 'max-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        //content align controls
        $this->add_responsive_control(
            'content_position',
            [
                'label' => esc_html__('Content Position', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [
                        'title' => esc_html__('Start', 'bdthemes-prime-slider-lite'),
                        'icon' => 'eicon-v-align-top',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'bdthemes-prime-slider-lite'),
                        'icon' => 'eicon-v-align-middle',
                    ],
                    'flex-end' => [
                        'title' => esc_html__('End', 'bdthemes-prime-slider-lite'),
                        'icon' => 'eicon-v-align-bottom',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-slideshow-item' => 'align-items: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'content_alignment',
            [
                'label' => esc_html__('Alignment', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'bdthemes-prime-slider-lite'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'bdthemes-prime-slider-lite'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'bdthemes-prime-slider-lite'),
                        'icon' => 'eicon-text-align-right',
                    ],
                    'justify' => [
                        'title' => esc_html__('Justify', 'bdthemes-prime-slider-lite'),
                        'icon' => 'eicon-text-align-justify',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-ps-content' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        /**
		* Thumbnail Size Controls
		*/
		$this->register_thumbnail_size_controls();

        //Global background settings Controls
        $this->register_background_settings('.bdt-prime-slider .bdt-slideshow-item>.bdt-ps-slide-img');

        /**
		* Show Title Controls
		*/
		$this->register_show_title_controls();

        /**
         * Show Post Excerpt Controls
         */
        $this->register_show_post_excerpt_controls();

        /**
         * Show Category Controls
         */
        $this->register_show_category_controls();

        $this->add_control(
            'show_admin_info',
            [
                'label' => esc_html__('Show Author', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'published_by',
            [
                'label' => esc_html__('Published By', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'condition' => [
                    'show_admin_info' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_thumbnav',
            [
                'label' => esc_html__('Show Thumbs', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'thumbs_hide_on',
            [
                'label' => __('Thumbs Hide On', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'label_block' => false,
                'options' => [
                    'desktop' => __('Desktop', 'bdthemes-prime-slider-lite'),
                    'tablet' => __('Tablet', 'bdthemes-prime-slider-lite'),
                    'mobile' => __('Mobile', 'bdthemes-prime-slider-lite'),
                ],
                'frontend_available' => true,
                'condition' => [
                    'show_thumbnav' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_navigation_arrows_dots',
            [
                'label' => esc_html__('Show Navigation', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'separator' => 'before',
            ]
        );
        $this->add_control(
            'navigation_position',
            [
                'label' => esc_html__('Position', 'bdthemes-prime-slider-lite') . BDTPS_CORE_NC,
                'type' => Controls_Manager::SELECT,
                'default' => 'center-left',
                'options' => [
                    'center-left' => esc_html__('Center Left', 'bdthemes-prime-slider-lite'),
                    'center-right' => esc_html__('Center Right', 'bdthemes-prime-slider-lite'),
                    'top-left' => esc_html__('Top Left', 'bdthemes-prime-slider-lite'),
                    'top-right' => esc_html__('Top Right', 'bdthemes-prime-slider-lite'),
                    'bottom-left' => esc_html__('Bottom Left', 'bdthemes-prime-slider-lite'),
                    'bottom-center' => esc_html__('Bottom Center', 'bdthemes-prime-slider-lite'),
                    'bottom-right' => esc_html__('Bottom Right', 'bdthemes-prime-slider-lite'),
                ],
                'condition' => [
                    'show_navigation_arrows_dots' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_post_query_builder',
            [
                'label' => __('Query', 'bdthemes-prime-slider-lite'),
                'tab' => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->register_query_builder_controls();

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_animation',
            [
                'label' => esc_html__('Slider Settings', 'bdthemes-prime-slider-lite'),
            ]
        );

        /**
         * Slider Settings Controls
         */
        $this->register_slider_settings_controls();

        /**
         * Ken Burns Controls
         */
        $this->register_ken_burns_controls();

        $this->end_controls_section();

        /**
         * Extension point: add-ons (e.g. Prime Slider Pro) register their own
         * controls here. This plugin registers none of its own.
         */
        $this->register_addon_controls();


        //Style Start
        $this->start_controls_section(
            'section_style_sliders',
            [
                'label' => esc_html__('Sliders', 'bdthemes-prime-slider-lite'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'overlay',
            [
                'label' => esc_html__('Overlay', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::SELECT,
                'default' => 'background',
                'options' => [
                    'none' => esc_html__('None', 'bdthemes-prime-slider-lite'),
                    'background' => esc_html__('Background', 'bdthemes-prime-slider-lite'),
                    'blend' => esc_html__('Blend', 'bdthemes-prime-slider-lite'),
                ],
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'overlay_color',
            [
                'label' => esc_html__('Overlay Color', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::COLOR,
                'condition' => [
                    'overlay' => ['background', 'blend'],
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-slideshow .bdt-overlay-default' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'blend_type',
            [
                'label' => esc_html__('Blend Type', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::SELECT,
                'default' => 'multiply',
                'options' => prime_slider_blend_options(),
                'condition' => [
                    'overlay' => 'blend',
                ],
            ]
        );

        $this->add_responsive_control(
            'ps_content_innner_padding',
            [
                'label' => esc_html__('Content Padding', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-ps-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_title',
            [
                'label'     => __('Title', 'bdthemes-prime-slider-lite'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_title' => ['yes'],
                ],
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => esc_html__('Color', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-ps-content .bdt-title-tag a' => 'color: {{VALUE}};',
                ],
                // 'condition' => [
                //     'show_title' => ['yes'],
                // ],
            ]
        );

        $this->add_control(
            'title_hover_color',
            [
                'label' => esc_html__('Hover Color', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-ps-content .bdt-title-tag a:hover, {{WRAPPER}} .bdt-prime-slider .bdt-ps-content .bdt-title-tag a:hover span' => 'color: {{VALUE}};',
                ],
                // 'condition' => [
                //     'show_title' => ['yes'],
                // ],
            ]
        );

        $this->add_control(
            'first_word_title_color',
            [
                'label' => esc_html__('First Word Color', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-ps-content .bdt-title-tag a span' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => esc_html__('Typography', 'bdthemes-prime-slider-lite'),
                'selector' => '{{WRAPPER}} .bdt-prime-slider .bdt-ps-content .bdt-title-tag',
                // 'condition' => [
                //     'show_title' => ['yes'],
                // ],
            ]
        );

        $this->add_group_control(
            Group_Control_Text_Shadow::get_type(),
            [
                'name' => 'title_text_shadow',
                'selector' => '{{WRAPPER}} .bdt-prime-slider .bdt-ps-content .bdt-title-tag a',
                // 'condition' => [
                //     'show_title' => ['yes'],
                // ],
            ]
        );

        $this->add_group_control(
            Group_Control_Text_Stroke::get_type(),
            [
                'name' => 'title_text_stroke',
                'selector' => '{{WRAPPER}} .bdt-prime-slider .bdt-ps-content .bdt-title-tag a',
                'fields_options' => [
                    'text_stroke_type' => [
                        'label' => esc_html__('Text Stroke', 'bdthemes-prime-slider-lite'),
                    ],
                ],
                // 'condition' => [
                //     'show_title' => ['yes'],
                // ],
            ]
        );

        $this->add_responsive_control(
            'prime_slider_title_spacing',
            [
                'label' => esc_html__('Spacing', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-ps-content .bdt-title-tag' => 'padding-bottom: {{SIZE}}{{UNIT}};',
                ],
                // 'condition' => [
                //     'show_title' => ['yes'],
                // ],
            ]
        );

        $this->add_responsive_control(
            'title_width',
            [
                'label' => esc_html__('Title Width', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 220,
                        'max' => 1200,
                    ],
                    '%' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-main-title' => 'max-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_text',
            [
                'label'     => esc_html__('Text', 'bdthemes-prime-slider-lite'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_excerpt' => ['yes'],
                ],
            ]
        );

        $this->add_control(
            'excerpt_color',
            [
                'label' => esc_html__('Color', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-blog-text' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'excerpt_typography',
                'label' => esc_html__('Typography', 'bdthemes-prime-slider-lite'),
                'selector' => '{{WRAPPER}} .bdt-prime-slider .bdt-blog-text',
            ]
        );

        $this->add_responsive_control(
            'excerpt_width',
            [
                'label' => __('Width (px)', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::SLIDER,
                'default' => [
                    'unit' => 'px',
                ],
                'tablet_default' => [
                    'unit' => 'px',
                ],
                'mobile_default' => [
                    'unit' => 'px',
                ],
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 100,
                        'max' => 800,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-blog-text' => 'max-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'prime_slider_excerpt_spacing',
            [
                'label' => esc_html__('Spacing', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 200,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-blog-text' => 'padding-bottom: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'show_excerpt' => ['yes'],
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_category',
            [
                'label'     => __('Category', 'bdthemes-prime-slider-lite'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_category' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'category_normal_heading',
            [
                'label' => __('NORMAL', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::HEADING,
            ]
        );

        $this->add_control(
            'category_icon_color',
            [
                'label' => __('Color', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-ps-category a' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'category_icon_background_color',
            [
                'label' => __('Background Color', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-ps-category a' => 'background: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'category_border',
                'label' => esc_html__('Border', 'bdthemes-prime-slider-lite'),
                'selector' => '{{WRAPPER}} .bdt-prime-slider .bdt-ps-category a',
            ]
        );

        $this->add_responsive_control(
            'category_border_radius',
            [
                'label' => esc_html__('Border Radius', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-ps-category a' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'category_padding',
            [
                'label' => esc_html__('Padding', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-ps-category a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'category_typography',
                'label' => esc_html__('Typography', 'bdthemes-prime-slider-lite'),
                'selector' => '{{WRAPPER}} .bdt-prime-slider .bdt-ps-category a',
            ]
        );

        $this->add_responsive_control(
            'ps_category_spacing',
            [
                'label' => esc_html__('Spacing', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-ps-category-wrapper' => 'padding-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'ps_category_space_between',
            [
                'label' => esc_html__('Space Between', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-ps-category a + a' => 'margin-left: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'category_hover_heading',
            [
                'label' => __('HOVER', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before'
            ]
        );

        $this->add_control(
            'category_hover_color',
            [
                'label' => __('Color', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-ps-category a:hover' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'category_hover_background_color',
            [
                'label' => __('Background Color', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-ps-category a:hover' => 'background: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'category_hover_border_color',
            [
                'label'     => __('Border Color', 'bdthemes-prime-slider-lite'),
                'type'      => Controls_Manager::COLOR,
                'condition' => [
                    'category_border_border!' => '',
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-ps-category a:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_author',
            [
                'label'     => __('Author', 'bdthemes-prime-slider-lite'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_admin_info' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'meta_text_color',
            [
                'label' => __('Text Color', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-prime-slider-meta .bdt-author, {{WRAPPER}} .bdt-prime-slider .bdt-prime-slider-meta .bdt-author a' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'meta_text_hover_color',
            [
                'label' => __('Hover Color', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-prime-slider-meta .bdt-author a:hover' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'meta_typography',
                'label' => esc_html__('Typography', 'bdthemes-prime-slider-lite'),
                'selector' => '{{WRAPPER}} .bdt-prime-slider .bdt-prime-slider-meta .bdt-author',
            ]
        );

        $this->add_control(
            'author_avatar_heading',
            [
                'label' => __('AVATAR', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before'
            ]
        );

        $this->add_control(
            'avatar_size',
            [
                'label'      => esc_html__( 'Size', 'bdthemes-prime-slider-lite' ),
                'type'       => Controls_Manager::SELECT,
                'default'    => '42',
                'options'    => [
                    '24'        => '24 X 24',
                    '36'        => '36 X 36',
                    '42'        => '42 X 42',
                    '48'        => '48 X 48',
                    '60'        => '60 X 60',
                    '70'        => '70 X 70',
                    '90'        => '90 X 90',
                ],
            ]
        );

        $this->add_responsive_control(
            'avatar_spacing',
            [
                'label' => esc_html__('Spacing', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::SLIDER,
                'selectors' => [
                    '{{WRAPPER}} .bdt-post-slider-author' => 'margin-right: {{SIZE}}{{UNIT}} !important;',
                ],
            ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_featured_post',
            [
                'label' => esc_html__('Thumbs', 'bdthemes-prime-slider-lite'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_thumbnav' => 'yes',
                ],
            ]
        );

        $this->start_controls_tabs('tabs_featured_post_style');
        $this->start_controls_tab(
            'tab_featured_post_normal',
            [
                'label' => esc_html__('Normal', 'bdthemes-prime-slider-lite'),
            ]
        );
        
        $this->add_control(
            'featured_post_title_color',
            [
                'label' => __('Color', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-ps-thumbnav>a span' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'featured_post_background_color',
            [
                'label' => __('Background Color', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-ps-thumbnav>a span' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'featured_post_overlay_color',
            [
                'label' => __('Overlay Color', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-ps-thumbnav .bdt-thumb-content:before' => 'background: {{VALUE}}',
                ],
            ]
        );

        $this->add_responsive_control(
            'featured_thumbs_border_radius',
            [
                'label' => esc_html__('Border Radius', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .bdt-ps-thumbnav .bdt-thumb-content' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'featured_thumbs_padding',
            [
                'label' => esc_html__('Title Padding', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider-flogia .bdt-ps-thumbnav>a span' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'featured_thumbs_title_margin',
            [
                'label' => esc_html__('Title Margin', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider-flogia .bdt-ps-thumbnav>a span' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'featured_thumbs_margin',
            [
                'label' => esc_html__('Thumbs Margin', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider-flogia .bdt-thumb-wrapper' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'featured_thumbs_gap',
            [
                'label' => esc_html__('Gap', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::SLIDER,
                'selectors' => [
                    '{{WRAPPER}} .bdt-thumbnav-scroller .bdt-slider-items' => 'gap: {{SIZE}}{{UNIT}};',
                ],
                'render_type' => 'template',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'featured_typography',
                'selector' => '{{WRAPPER}} .bdt-prime-slider .bdt-ps-thumbnav>a span',
            ]
        );

        $this->add_control(
            'featured_post_alignment',
            [
                'label' => __('Alignment', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => __('Left', 'bdthemes-prime-slider-lite'),
                        'icon' => 'eicon-h-align-left',
                    ],
                    'center' => [
                        'title' => __('Center', 'bdthemes-prime-slider-lite'),
                        'icon' => 'eicon-h-align-center',
                    ],
                    'right' => [
                        'title' => __('Right', 'bdthemes-prime-slider-lite'),
                        'icon' => 'eicon-h-align-right',
                    ],
                ],
                'default' => 'center',
                'toggle' => false,
            ]
        );

        $this->add_control(
			'thumbs_size_toggle',
			[ 
				'label'        => __( 'Size', 'bdthemes-prime-slider-lite' ) . BDTPS_CORE_NC,
				'type'         => Controls_Manager::POPOVER_TOGGLE,
				'label_off'    => __( 'None', 'bdthemes-prime-slider-lite' ),
				'label_on'     => __( 'Custom', 'bdthemes-prime-slider-lite' ),
				'return_value' => 'yes',
			]
		);
		$this->start_popover();
        $this->add_responsive_control(
            'thumbs_height',
            [
                'label' => __( 'Height', 'bdthemes-prime-slider-lite' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em' ],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 200,
                    ],
                    'em' => [
                        'min' => 1,
                        'max' => 10,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider-flogia .bdt-ps-thumbnav .bdt-thumb-content' => 'height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'thumbs_size_toggle' => 'yes',
                ],
                'render_type' => 'template',
            ]
        );
        $this->add_responsive_control(
            'thumbs_width',
            [
                'label' => __( 'Width', 'bdthemes-prime-slider-lite' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em' ],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 300,
                    ],
                    'em' => [
                        'min' => 1,
                        'max' => 20,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider-flogia .bdt-ps-thumbnav .bdt-thumb-content' => 'width: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'thumbs_size_toggle' => 'yes',
                ],
                'render_type' => 'template',
            ]
        );
        $this->end_popover();

        $this->end_controls_tab();
        $this->start_controls_tab(
            'tab_featured_post_active',
            [
                'label' => esc_html__('Active', 'bdthemes-prime-slider-lite'),
            ]
        );

        $this->add_control(
            'featured_post_title_color_active',
            [
                'label' => __('Color', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-ps-thumbnav.bdt-active>a span' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'featured_post_background_color_active',
            [
                'label' => __('Background Color', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-ps-thumbnav.bdt-active>a span' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'featured_post_overlay_color_active',
            [
                'label' => __('Overlay Color', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-ps-thumbnav.bdt-active .bdt-thumb-content:before' => 'background: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'featured_thumbs_border',
                'label' => __('Border', 'bdthemes-prime-slider-lite'),
                'selector' => '{{WRAPPER}} .bdt-ps-thumbnav.bdt-active .bdt-thumb-content',
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->end_controls_section();

        $this->start_controls_section(
            'section_style_navigation',
            [
                'label' => __('Navigation', 'bdthemes-prime-slider-lite'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_navigation_arrows_dots' => ['yes'],
                ],
            ]
        );

        $this->add_responsive_control(
            'nav_offset',
            [
                'label' => __('Horizontal Offset', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 200,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider-flogia .bdt-navigation-arrows' => 'margin-right: {{SIZE}}{{UNIT}}; margin-left: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'nav_vertical_offset',
            [
                'label' => __('Vertical Offset', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 200,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider-flogia .bdt-navigation-arrows' => 'margin-top: {{SIZE}}{{UNIT}}; margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->start_controls_tabs('tabs_navigation_style');

        $this->start_controls_tab(
            'tab_nav_arrows_dots_style',
            [
                'label' => __('Normal', 'bdthemes-prime-slider-lite'),
            ]
        );

        $this->add_control(
            'arrows_color',
            [
                'label' => __('Arrows Color', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-prime-slider-previous svg, {{WRAPPER}} .bdt-prime-slider .bdt-prime-slider-next svg' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'dots_color',
            [
                'label' => __('Dots Color', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-ps-dotnav li a:before' => 'background: {{VALUE}}',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_nav_arrows_dots_hover_style',
            [
                'label' => __('Hover', 'bdthemes-prime-slider-lite'),
            ]
        );

        $this->add_control(
            'arrows_hover_color',
            [
                'label' => __('Arrows Color', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-prime-slider-previous:hover svg, {{WRAPPER}} .bdt-prime-slider .bdt-prime-slider-next:hover svg' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'dots_hover_color',
            [
                'label' => __('Dots Color', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-ps-dotnav li:hover a:before' => 'background: {{VALUE}}',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_nav_arrows_dots_active_style',
            [
                'label' => __('Active', 'bdthemes-prime-slider-lite'),
            ]
        );

        $this->add_control(
            'dots_active_color',
            [
                'label' => __('Dots Color', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-ps-dotnav li.bdt-active a:before' => 'background: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'dots_active_border_color',
            [
                'label' => __('Dots Border Color', 'bdthemes-prime-slider-lite'),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .bdt-prime-slider .bdt-ps-dotnav li.bdt-active a:after' => 'border-color:{{VALUE}}',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    public function query_posts() {
        $settings = $this->get_settings();
        $args = [];

        if ($settings['posts_limit']) {
            $args['posts_per_page'] = $settings['posts_limit'];
            $args['paged'] = max(1, get_query_var('paged'), get_query_var('page'));
        }

        $default = $this->getGroupControlQueryArgs();
        $args = array_merge($default, $args);

        $query = new WP_Query($args);

        return $query;
    }

    public function render_header($skin_name = 'flogia') {
        $settings = $this->get_settings_for_display();

        /**
         * Advanced Animation
         */
        $this->add_addon_render_attributes('slideshow');
        $this->add_render_attribute('slideshow', 'id', 'bdt-' . $this->get_id());

        $this->add_render_attribute('prime-slider', 'class', 'bdt-prime-slider-' . $skin_name);


        /**
         * Slideshow Settings
         */
        $this->render_slideshows_settings('520');
    }

    public function render_category() {

        $post_id = get_the_ID();
        ?>
        <span class="bdt-ps-category" data-reveal="reveal-active">
            <?php echo wp_kses_post( $this->ps_get_taxonomy_list( $post_id, $this->ps_taxonomy_switcher() ) ); ?>
        </span>
        <?php
    }

    public function render_navigation_arrows_dots() {
        $settings = $this->get_settings_for_display();

        if (!$settings['show_navigation_arrows_dots']) {
            return;
        }
        
        if ($settings['navigation_position'] == 'center-left' || $settings['navigation_position'] == 'center-right') {
            $this->add_render_attribute('navigation_arrows_dots', 'class', 'bdt-navigation-arrows bdt-position-large bdt-position-z-index reveal-muted bdt-position-' . $settings['navigation_position']);
        } else {
            $this->add_render_attribute('navigation_arrows_dots', 'class', 'bdt-flex bdt-flex-middle bdt-navigation-arrows bdt-position-large bdt-position-z-index reveal-muted bdt-position-' . $settings['navigation_position']);
        }

        if ($settings['navigation_position'] == 'center-left' || $settings['navigation_position'] == 'center-right') {
            $this->add_render_attribute('dotnav_direction', 'class', 'bdt-slideshow-nav bdt-ps-dotnav bdt-dotnav bdt-dotnav-vertical');
        } else {
            $this->add_render_attribute('dotnav_direction', 'class', 'bdt-slideshow-nav bdt-ps-dotnav bdt-dotnav bdt-dotnav-horizontal');
        }

        ?>

        <?php if ($settings['show_navigation_arrows_dots']): ?>
            <div <?php $this->print_render_attribute_string('navigation_arrows_dots');?>>
                <a class="bdt-prime-slider-previous" href="#" bdt-slidenav-previous bdt-slideshow-item="previous"></a>

                <ul <?php $this->print_render_attribute_string('dotnav_direction');?>></ul>

                <a class="bdt-prime-slider-next" href="#" bdt-slidenav-next bdt-slideshow-item="next"></a>
            </div>
        <?php endif;?>

        <?php
    }

    public function render_thumbnav() {
        $settings = $this->get_settings_for_display();

        $thumbs_hide_on_setup = '';
        if (!empty($settings['thumbs_hide_on'])) {
            foreach ($settings['thumbs_hide_on'] as $element) {

                if ($element == 'desktop') {
                    $thumbs_hide_on_setup .= ' bdt-desktop';
                }
                if ($element == 'tablet') {
                    $thumbs_hide_on_setup .= ' bdt-tablet';
                }
                if ($element == 'mobile') {
                    $thumbs_hide_on_setup .= ' bdt-mobile';
                }
            }
        }

        ?>

        <?php if ('yes' == $settings['show_thumbnav']): ?>
            <div class="bdt-thumb-wrapper bdt-position-bottom-<?php echo esc_attr($settings['featured_post_alignment']); ?> bdt-position-large <?php echo esc_attr($thumbs_hide_on_setup); ?>">
            <div class="bdt-thumbnav-scroller">
                <ul class="bdt-slider-items">
                    <?php
            $slide_index = 1;

            $wp_query = $this->query_posts();

            if (!$wp_query->found_posts) {
                return;
            }

            while ($wp_query->have_posts()) {
                $wp_query->the_post();

            ?>

            <li class="bdt-ps-thumbnav bdt-position-relative" bdt-slideshow-item="<?php echo esc_attr($slide_index - 1); ?>">
                <a href="#">
                    <div class="bdt-thumb-content">
                        <?php $this->rendar_thumb_image();?>
                        <span><?php echo esc_html( get_the_title() ); ?></span>
                    </div>
                </a>
                <?php $slide_index++;?>
            </li>

            <?php
            }

        wp_reset_postdata();?>

                    </ul>
                </div>
                </div>
            <?php endif;?>

        <?php
    }

    public function render_footer() {
        ?>

                    </ul>

                    <?php $this->render_navigation_arrows_dots();?>

                    <?php $this->render_thumbnav();?>

                </div>

            </div>
        </div>
        <?php
    }

    public function rendar_thumb_image() {
        $settings = $this->get_settings_for_display();

        $placeholder_image_src = Utils::get_placeholder_image_src();
        $image_src = Group_Control_Image_Size::get_attachment_image_src(get_post_thumbnail_id(), 'thumbnail_size', $settings);

        if ($image_src) {
            $image_final_src = $image_src;
        } elseif ($placeholder_image_src) {
            $image_final_src = $placeholder_image_src;
        } else {
            return;
        }

        ?>

            <img src="<?php echo esc_url($image_final_src); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>">

        <?php
    }

    public function render_excerpt() {
        $settings = $this->get_settings_for_display();

        if (!$this->get_settings('show_excerpt')) {
            return;
        }

        $strip_shortcode = $this->get_settings_for_display('strip_shortcode');

        $parallax_text = 'data-bdt-slideshow-parallax="y: 100,0,-60; opacity: 1,1,0"';

        if ( ! empty( $settings['animation_status'] ) && 'yes' === $settings['animation_status'] && ! empty( $settings['animation_of'] ) ) {
        	if (in_array(".bdt-blog-text", $settings['animation_of'])) {
        	    $parallax_text = '';
        	}
        }

        ?>
            <div class="bdt-blog-text" <?php echo wp_kses_post($parallax_text); ?> data-reveal="reveal-active">
                <?php
                if (has_excerpt()) {
                    the_excerpt();
                } else {
                    echo wp_kses_post(prime_slider_custom_excerpt($this->get_settings_for_display('excerpt_length'), $strip_shortcode));
                }
                ?>
            </div>
        <?php
    }

    public function render_item_content($post) {
        $settings = $this->get_settings_for_display();

        $parallax_title = 'data-bdt-slideshow-parallax="y: 80,0,-80; opacity: 1,1,0"';

        if ( ! empty( $settings['animation_status'] ) && 'yes' === $settings['animation_status'] && ! empty( $settings['animation_of'] ) ) {
        	if (in_array(".bdt-title-tag", $settings['animation_of'])) {
        	    $parallax_title = '';
        	}
        }

        $avatar_size = $settings['avatar_size'];

        ?>

        <div class="bdt-ps-container">
            <div class="bdt-ps-content">

                <?php if ('yes' == $settings['show_category']): ?>
                    <div class="bdt-ps-category-wrapper" data-bdt-slideshow-parallax="y: 50,0,-50; opacity: 1,1,0">
                        <?php $this->render_category();?>
                    </div>
                <?php endif;?>

                <?php if ('yes' == $settings['show_title'] || 'yes' == $settings['show_excerpt']): ?>
                    <div class="bdt-main-title">
                        <?php if ('yes' == $settings['show_title']): ?>
                            <<?php echo esc_attr(Utils::get_valid_html_tag($settings['title_html_tag'])); ?> class="bdt-title-tag" <?php echo wp_kses_post($parallax_title); ?> data-reveal="reveal-active">

                                <a href="<?php echo esc_url(get_permalink($post->ID)); ?>">
                                    <?php echo wp_kses_post(prime_slider_first_word(get_the_title())); ?>
                                </a>

                            </<?php echo esc_attr(Utils::get_valid_html_tag($settings['title_html_tag'])); ?>>
                        <?php endif;?>


                        <?php $this->render_excerpt();?>

                    </div>
                <?php endif;?>

                <?php if ('yes' == $settings['show_admin_info']): ?>
                    <div class="bdt-prime-slider-meta bdt-flex-inline bdt-flex-middle" data-reveal="reveal-active" data-bdt-slideshow-parallax="y: 70,-30">
                        <div class="bdt-post-slider-author bdt-flex bdt-margin-small-right bdt-border-circle bdt-overflow-hidden">
                            <?php echo get_avatar(get_the_author_meta('ID'), $avatar_size); ?>
                        </div>
                        <div class="bdt-meta-author bdt-flex bdt-flex-middle">
                            <span class="bdt-author">
                                <?php if ($settings['published_by'] == 'yes'): ?>
                                    <?php echo esc_html_x('Published by ', 'Frontend', 'bdthemes-prime-slider-lite'); ?>
                                <?php endif;?>
                                <a href="<?php echo esc_url(get_author_posts_url(get_the_author_meta('ID'))); ?>"><?php echo esc_attr(get_the_author()); ?></a>
                            </span>
                        </div>
                    </div>
                <?php endif;?>

            </div>
        </div>

        <?php
    }

    public function render_slides_loop() {
        $settings = $this->get_settings_for_display();

        $kenburns_reverse = $settings['kenburns_reverse'] ? ' bdt-animation-reverse' : '';

        $slide_index = 1;

        global $post;

        $wp_query = $this->query_posts();

        if (!$wp_query->found_posts) {
            return;
        }

        while ($wp_query->have_posts()) {
            $wp_query->the_post();

            ?>

                <li class="bdt-slideshow-item bdt-flex elementor-repeater-item-<?php echo esc_attr(get_the_ID()); ?>">

                    <?php if ('yes' == $settings['kenburns_animation']): ?>
                        <div class="bdt-position-cover bdt-animation-kenburns<?php echo esc_attr($kenburns_reverse); ?> bdt-transform-origin-center-left">
                        <?php endif;?>

                        <?php $this->rendar_post_image("bdt-ps-slide-img"); ?>

                        <?php if ('yes' == $settings['kenburns_animation']): ?>
                        </div>
                    <?php endif;?>

                    <?php if ('none' !== $settings['overlay']):
                        $blend_type = ('blend' == $settings['overlay']) ? ' bdt-blend-' . $settings['blend_type'] : '';?>
                        <div class="bdt-overlay-default bdt-position-cover<?php echo esc_attr($blend_type); ?>"></div>
                    <?php endif;?>

                    <?php $this->render_item_content($post);?>

                    <?php $slide_index++;?>

                </li>


            <?php
        }

        wp_reset_postdata();
    }

    public function render() {

        $this->render_header();

        $this->render_slides_loop();

        $this->render_footer();
    }
}
