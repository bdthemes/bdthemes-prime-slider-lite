<?php

namespace PrimeSlider\Modules\Blog\Skins;

use PrimeSlider\Utils;
use Elementor\Group_Control_Image_Size;
use Elementor\Skin_Base as Elementor_Skin_Base;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Skin_Coral extends Elementor_Skin_Base
{

    public function get_id()
    {
        return 'coral';
    }

    public function get_title()
    {
        return esc_html__('Coral', 'bdthemes-prime-slider-lite');
    }


    public function render_navigation_arrows()
    {
        $settings = $this->parent->get_settings_for_display();

?>

        <?php if ($settings['show_navigation_arrows']) : ?>
            <div class="bdt-navigation-arrows bdt-position-center-right reveal-muted">
                <a class="bdt-prime-slider-previous" href="#" bdt-slidenav-previous bdt-slideshow-item="previous"></a>
                <a class="bdt-prime-slider-next" href="#" bdt-slidenav-next bdt-slideshow-item="next"></a>
            </div>
        <?php endif; ?>

        <div class="bdt-ps-thumbnav reveal-muted">

            <?php
            $wp_query    = $this->parent->query_posts();
            $total_slide = $wp_query->post_count;
            $slide_index = 1;

            while ($wp_query->have_posts()) {
                $wp_query->the_post();

                // Each preview shows the slide after the active one, so it is
                // marked active together with the slide before it, and a click
                // moves on to the slide it shows.
                $active_with = ($slide_index - 2 + $total_slide) % $total_slide;

            ?>

                <li class="bdt-slide-counter" bdt-slideshow-item="<?php echo esc_attr($active_with); ?>"
                    data-label="<?php echo esc_attr(str_pad($slide_index, 2, '0', STR_PAD_LEFT)); ?>">
                    <a href="#" bdt-slideshow-item="next" aria-label="<?php echo esc_attr(sprintf(
                        /* translators: %s: post title */
                        __('Next slide: %s', 'bdthemes-prime-slider-lite'),
                        wp_strip_all_tags(get_the_title())
                    )); ?>">
                        <?php $this->rendar_item_image(); ?>
                    </a>
                    <?php $slide_index++; ?>
                </li>

            <?php
            }

            wp_reset_postdata(); ?>

        </div>
    <?php

    }

    public function render_navigation_dots()
    {
        $settings = $this->parent->get_settings_for_display();

    ?>

        <?php if ($settings['show_navigation_dots']) : ?>

            <ul class="bdt-slideshow-nav bdt-dotnav bdt-dotnav-vertical bdt-position-center-left reveal-muted"></ul>

        <?php endif; ?>

        <ul class="bdt-ps-dotnav bdt-position-center-right reveal-muted">
            <?php $slide_index = 1;
            $wp_query          = $this->parent->query_posts();

            while ($wp_query->have_posts()) {
                $wp_query->the_post();

            ?>

                <li bdt-slideshow-item="<?php echo esc_attr($slide_index - 1); ?>" data-label="<?php echo esc_attr(str_pad($slide_index, 2, '0', STR_PAD_LEFT)); ?>">
                    <a href="#"><?php echo esc_html(str_pad($slide_index, 2, '0', STR_PAD_LEFT)); ?></a>

                    <?php $slide_index++; ?>

                </li>

            <?php
            }
            wp_reset_postdata();
            ?>
            <span><?php echo esc_html(str_pad($slide_index - 1, 2, '0', STR_PAD_LEFT)); ?></span>

        </ul>
    <?php
    }

    public function render_scroll_button_text()
    {
        $settings = $this->parent->get_settings_for_display();

        $this->parent->add_render_attribute('content-wrapper', 'class', 'bdt-scroll-down-content-wrapper');
        $this->parent->add_render_attribute('text', 'class', 'bdt-scroll-down-text');

    ?>
        <span bdt-scrollspy="cls: bdt-animation-slide-right; repeat: true" <?php $this->parent->print_render_attribute_string('content-wrapper'); ?>>
            <span class="bdt-scroll-icon">
                <span bdt-icon="icon: chevron-down" class="bdt-icon"><svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" data-svg="chevron-down">
                        <polyline fill="none" stroke="#000" stroke-width="1.03" points="16 7 10 13 4 7"></polyline>
                    </svg></span>
                <!-- <span class="bdt-icon" bdt-icon="icon:arrow-down"></span> -->
            </span>
            <span <?php $this->parent->print_render_attribute_string('text'); ?>><?php echo esc_html($settings['scroll_button_text']); ?></span>
        </span>
    <?php
    }

    public function render_scroll_button()
    {
        $settings = $this->parent->get_settings_for_display();

        if ('yes' !== $settings['show_scroll_button']) {
            return;
        }

        $this->parent->add_scroll_button_render_attributes();

    ?>
        <div <?php $this->parent->print_render_attribute_string('bdt-scroll-wrapper'); ?>>
            <button <?php $this->parent->print_render_attribute_string('bdt-scroll-down'); ?>>
                <?php $this->render_scroll_button_text(); ?>
            </button>
        </div>

    <?php
    }

    public function render_footer()
    {
    ?>

        </ul>

        <?php $this->render_navigation_arrows(); ?>
        <?php $this->render_navigation_dots(); ?>

        </div>
        <?php $this->parent->render_social_link($position = 'top', $label = false, $class = []); ?>
        <?php $this->render_scroll_button(); ?>
        </div>
        </div>
    <?php
    }


    public function rendar_item_image()
    {
        $settings = $this->parent->get_settings_for_display();

        $image_src       = Group_Control_Image_Size::get_attachment_image_src(get_post_thumbnail_id(), 'thumbnail_size', $settings);
        $image_final_src = $image_src ? $image_src : Utils::get_placeholder_image_src();

    ?>

        <div class="bdt-ps-slide-img" style="background-image: url('<?php echo esc_url($image_final_src); ?>')"></div>

    <?php
    }

    public function render_item_content($post)
    {
        $settings = $this->parent->get_settings_for_display();

        $parallax_title       = 'data-bdt-slideshow-parallax="y: 50,0,-50; opacity: 1,1,0"';
        $parallax_text         = 'data-bdt-slideshow-parallax="y: 60,0,-50; opacity: 1,1,0"';

        if ( ! empty( $settings['animation_status'] ) && 'yes' === $settings['animation_status'] && ! empty( $settings['animation_of'] ) ) {
        	if (in_array(".bdt-title-tag", $settings['animation_of'])) {
        	    $parallax_title = '';
        	}
        	if (in_array(".bdt-blog-text", $settings['animation_of'])) {
        	    $parallax_text = '';
        	}
        }

    ?>

        <div class="bdt-slideshow-content-wrapper">
            <div class="bdt-prime-slider-wrapper">
                <div class="bdt-prime-slider-content">
                    <div class="bdt-prime-slider-desc">
                        <div>

                            <div data-bdt-slideshow-parallax="y: 20,0,-20; opacity: 1,1,0">
                                <?php $this->parent->render_category(); ?>
                            </div>

                            <?php if ('yes' == $settings['show_title']) : ?>
                                <div class="bdt-main-title" data-reveal="reveal-active">
                                    <<?php echo esc_attr(Utils::get_valid_html_tag($settings['title_html_tag'])); ?> class="bdt-title-tag" <?php echo wp_kses_post($parallax_title); ?>>

                                        <a href="<?php echo esc_url(get_permalink($post->ID)); ?>">
                                            <?php echo wp_kses_post(prime_slider_first_word(get_the_title())); ?>
                                        </a>

                                    </<?php echo esc_attr(Utils::get_valid_html_tag($settings['title_html_tag'])); ?>>
                                </div>
                            <?php endif; ?>

                            <div <?php echo wp_kses_post($parallax_text); ?>>
                                <?php $this->parent->render_excerpt(); ?>
                            </div>

                            <!-- <div data-bdt-slideshow-parallax="y: 80,0,-60; opacity: 1,1,0"> -->
                            <?php $this->parent->render_meta(); ?>
                            <!-- </div> -->

                            <div class="bdt-coral-btn" data-reveal="reveal-active" data-bdt-slideshow-parallax="y: 150,0,-100; opacity: 1,1,0">
                                <?php $this->parent->render_button($post); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
    }

    public function render_slides_loop()
    {
        $settings = $this->parent->get_settings_for_display();

        $kenburns_reverse = $settings['kenburns_reverse'] ? ' bdt-animation-reverse' : '';

        $slide_index = 1;

        global $post;

        $wp_query = $this->parent->query_posts();

        while ($wp_query->have_posts()) {
            $wp_query->the_post();

        ?>

            <li class="bdt-slideshow-item bdt-flex bdt-flex-middle elementor-repeater-item-<?php echo esc_attr(get_the_ID()); ?>">

                <?php if ('yes' == $settings['kenburns_animation']) : ?>
                    <div class="bdt-position-cover bdt-animation-kenburns<?php echo esc_attr($kenburns_reverse); ?> bdt-transform-origin-center-left">
                    <?php endif; ?>

                    <?php $this->rendar_item_image(); ?>

                    <?php if ('yes' == $settings['kenburns_animation']) : ?>
                    </div>
                <?php endif; ?>

                <?php $this->parent->render_overlay(); ?>

                <?php $this->render_item_content($post); ?>

                <?php $slide_index++; ?>

            </li>


<?php
        }

        wp_reset_postdata();
    }

    public function render()
    {
        $skin_name = 'coral';

        if ( ! $this->parent->query_posts( true )->have_posts() ) {
            $this->parent->render_no_posts_notice();
            return;
        }

        $this->parent->render_header($skin_name);

        $this->render_slides_loop();

        $this->render_footer();
    }
}
