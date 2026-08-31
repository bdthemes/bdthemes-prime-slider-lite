<?php

namespace PrimeSlider\Modules\Isolate\Skins;

use Elementor\Group_Control_Image_Size;
use Elementor\Icons_Manager;
use Elementor\Skin_Base as Elementor_Skin_Base;
use PrimeSlider\Utils;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Skin_Slice extends Elementor_Skin_Base {

    public function get_id() {
        return 'slice';
    }

    public function get_title() {
        return esc_html__('Slice', 'bdthemes-prime-slider-lite');
    }

    public function render_navigation_arrows() {
		$settings = $this->parent->get_settings_for_display();

		?>

            <?php if ($settings['show_navigation_arrows']) : ?>
            <div class="bdt-navigation-arrows reveal-muted">
                <a class="bdt-prime-slider-previous" href="#" bdt-slideshow-item="previous"><i class="ps-wi-arrow-left-5"></i></a>
    
                <a class="bdt-prime-slider-next" href="#" bdt-slideshow-item="next"><i class="ps-wi-arrow-right-5"></i></a>
            </div>

        
			<?php endif; ?>

		<?php
	}

    public function render_navigation_dots() {
        $settings = $this->parent->get_settings_for_display();

        ?>

        <?php if ($settings['show_navigation_dots']) : ?>

            <ul class="bdt-ps-dotnav reveal-muted">
                <?php $slide_index = 1; foreach ( $settings['slides'] as $slide ) : ?>
                    <li bdt-slideshow-item="<?php echo esc_attr($slide_index - 1); ?>" data-label="<?php echo esc_attr(str_pad( $slide_index, 2, '0', STR_PAD_LEFT)); ?>" ><a href="#"><?php echo esc_attr(str_pad( $slide_index, 2, '0', STR_PAD_LEFT)); ?></a></li>
                <?php $slide_index++;  endforeach; ?>

                <span><?php echo esc_attr(str_pad( $slide_index - 1, 2, '0', STR_PAD_LEFT)); ?></span>
            </ul>

        <?php endif; ?>

        <?php
    }

    public function render_footer() {
        $settings = $this->parent->get_settings_for_display();

        ?>

                </ul>

                <div class="bdt-flex bdt-position-bottom reveal-muted">
                    <div class="bdt-width-1-1">
                        <div class="bdt-grid">
                            <div class="bdt-width-1-1">
                                <div class="bdt-slide-text-btn-area">
                                <?php $slide_index = 1;
                                foreach ($settings['slides'] as $slide) : ?>
                                    <div class="bdt-slide-nav-arrows" bdt-slideshow-item="<?php echo esc_attr($slide_index - 1); ?>">

                                        <?php if ($slide['excerpt'] && ('yes' == $settings['show_excerpt'])) : ?>
                                            <div class="bdt-slider-excerpt">
                                                <?php echo wp_kses_post($slide['excerpt']); ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="bdt-skin-slide-btn">
                                            <?php $this->render_button($slide); ?>
                                        </div>
                                    </div>
                                <?php $slide_index++;
                                endforeach; ?>
                                    
                                    <?php $this->render_navigation_arrows(); ?>
                                    <?php $this->render_navigation_dots(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


            </div>
            <?php $this->render_social_link(); ?>
        </div>
        </div>
        <?php
    }

    public function render_social_link($position = 'left', $class = []) {
		$settings  = $this->parent->get_active_settings();

		if ('' == $settings['show_social_icon']) {
			return;
		}

		$this->parent->add_render_attribute('social-icon', 'class', 'bdt-prime-slider-social-icon reveal-muted');
		$this->parent->add_render_attribute('social-icon', 'class', $class);

		?>

			<div <?php $this->parent->print_render_attribute_string('social-icon'); ?>>

                <?php
                foreach ( $settings['social_link_list'] as $index => $link ) :
                    
                    $link_key = 'link_' . $index;
   

                    $tooltip = '';
                    if ( 'yes' === $settings['social_icon_tooltip'] ) {
                        $social_link_title = isset( $link['social_link_title'] ) ? (string) $link['social_link_title'] : '';
                        $tooltip_text      = wp_kses_post( wp_strip_all_tags( $social_link_title ) );
                        $tooltip = 'title: ' . htmlspecialchars( $tooltip_text, ENT_QUOTES ) . '; pos: ' . esc_attr( $position );
                    }

                    if ( isset($link['social_icon_link']['url']) && ! empty($link['social_icon_link']['url']) ) {
                        $this->parent->add_link_attributes($link_key, $link['social_icon_link']);
                    }
                    
                    ?>
                    <a <?php $this->parent->print_render_attribute_string($link_key); ?> data-bdt-tooltip="<?php echo esc_attr( $tooltip ); ?>">
                        <?php Icons_Manager::render_icon( $link['social_icon'], [ 'aria-hidden' => 'true', 'class' => 'fa-fw' ] ); ?>
                    </a>
                <?php endforeach; ?>
			</div>

		<?php
	}

    public function render_item_content($slide_content) {
        $settings = $this->parent->get_settings_for_display();

        $parallax_sub_title = $parallax_title = '';
        if ( $settings['animation_parallax'] == 'yes' ) {
            $parallax_sub_title     = 'data-bdt-slideshow-parallax="y: 50,0,-50; opacity: 1,1,0"';
            $parallax_title 	    = ' data-bdt-slideshow-parallax="y: 75,0,-75; opacity: 1,1,0"'; 
        }

        if ( ! empty( $settings['animation_status'] ) && 'yes' === $settings['animation_status'] && ! empty( $settings['animation_of'] ) ) {
        	if (in_array(".bdt-ps-sub-title", $settings['animation_of'])) {
        	    $parallax_sub_title = '';
        	}
        	if (in_array(".bdt-title-tag", $settings['animation_of'])) {
        	    $parallax_title = '';
        	}
        }

        $title_link_key = 'title-link-' . $slide_content['_id'];

        if ($slide_content['title']) {
            $this->parent->add_link_attributes($title_link_key, $slide_content['title_link'], true);
        }

        ?>
            <div class="bdt-prime-slider-wrapper">
                <div class="bdt-prime-slider-content">
                    <div class="bdt-prime-slider-desc bdt-flex bdt-flex-column">

                                
                        <?php if ( ( $slide_content['sub_title'] && ('yes' == $settings['show_sub_title']) ) || ( $slide_content['title'] && ('yes' == $settings['show_title']) ) ) : ?>
                        <div class="bdt-main-title">
                            <?php if ($slide_content['sub_title'] && ('yes' == $settings['show_sub_title'])) : ?>
                            <<?php echo esc_attr(Utils::get_valid_html_tag($settings['sub_title_html_tag'])); ?> class="bdt-ps-sub-title bdt-sub-title-tag" data-reveal="reveal-active" <?php echo wp_kses_post($parallax_sub_title); ?>>
                                <?php echo wp_kses_post(prime_slider_first_word($slide_content['sub_title'])); ?>
                            </<?php echo esc_attr(Utils::get_valid_html_tag($settings['sub_title_html_tag'])); ?>>
                            <?php endif; ?>
                            <?php if ($slide_content['title'] && ('yes' == $settings['show_title'])) : ?>
                            <<?php echo esc_attr(Utils::get_valid_html_tag($settings['title_html_tag'])); ?>
                            class="bdt-title-tag" data-reveal="reveal-active"  <?php echo wp_kses_post($parallax_title); ?>>
                                <?php if ('' !== $slide_content['title_link']['url']) : ?>
                                    <a <?php $this->parent->print_render_attribute_string($title_link_key);?>>
                                    <?php endif; ?>
                                    <?php echo wp_kses_post(prime_slider_first_word($slide_content['title'])); ?>
                                    <?php if ('' !== $slide_content['title_link']['url']) : ?>
                                    </a>
                                <?php endif; ?>
                            </<?php echo esc_attr(Utils::get_valid_html_tag($settings['title_html_tag'])); ?>>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        
                    </div>

                </div>
            </div>

        <?php
    }

    public function render_slides_loop() {
        $settings = $this->parent->get_settings_for_display();

        $kenburns_reverse = $settings['kenburns_reverse'] ? ' bdt-animation-reverse' : '';
        $index = 0;
        foreach ($settings['slides'] as $slide) : 
            $index += 1; ?>

            <li class="bdt-slideshow-item bdt-flex bdt-flex-column bdt-flex-middle elementor-repeater-item-<?php echo esc_attr($slide['_id']); ?>">
                <div class="bdt-width-1-1 bdt-width-1-2@s">
                    <?php $this->render_item_content($slide); ?>
                </div>
                <div class="bdt-width-1-1 bdt-width-1-2@s">
                    <div class="bdt-position-relative bdt-text-center bdt-slide-overlay" data-reveal="reveal-active">
                        <?php if ('yes' == $settings['kenburns_animation']) : ?>
                            <div class="bdt-animation-kenburns<?php echo esc_attr($kenburns_reverse); ?> bdt-transform-origin-center-left">
                            <?php endif; ?>
        
                                <?php $this->parent->rendar_item_image($slide); ?>
                                
                                <?php if ('yes' == $settings['kenburns_animation']) : ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php $this->parent->render_play_button($slide, $index);?>
        
                        <?php if ('none' !== $settings['overlay']) :
                                        $blend_type = ('blend' == $settings['overlay']) ? ' bdt-blend-' . $settings['blend_type'] : ''; ?>
                            <div class="bdt-overlay-default bdt-position-cover<?php echo esc_attr($blend_type); ?>"></div>
                        <?php endif; ?>
                    </div>
				</div>
            </li>

        <?php endforeach;
    }

    public function render_button($content) {
		$settings = $this->parent->get_settings_for_display();

		$slider_button_key = 'slider-button-' . $content['_id'];

		$this->parent->add_render_attribute($slider_button_key, 'class', 'bdt-slide-btn', true);

		if ($content['slide_button_text']) {
			$this->parent->add_link_attributes($slider_button_key, $content['button_link'], true);

			if (! empty($content['button_link']['url'])) {
				$button_target = ! empty($content['button_link']['is_external']) ? '_blank' : '_self';

				$this->parent->add_render_attribute(
					$slider_button_key,
					'onclick',
					'window.open(' . wp_json_encode(esc_url_raw($content['button_link']['url'])) . ',' . wp_json_encode($button_target) . ');return false;'
				);
			}
		}

		?>

		<?php if ($content['slide_button_text'] && ('yes' == $settings['show_button_text']) && !empty($content['button_link']['url'])) : ?>

			<a <?php $this->parent->print_render_attribute_string($slider_button_key); ?>>

				<?php

							$this->parent->add_render_attribute([
								'content-wrapper' => [
									'class' => 'bdt-prime-slider-button-wrapper',
								],
								'text' => [
									'class' => 'bdt-prime-slider-button-text bdt-flex bdt-flex-middle bdt-flex-inline',
								],
							], '', '', true);

							?>
				<span <?php $this->parent->print_render_attribute_string('content-wrapper'); ?>>

					<span <?php $this->parent->print_render_attribute_string('text'); ?>><?php echo wp_kses($content['slide_button_text'], prime_slider_allow_tags('title')); ?><span class="bdt-slide-btn-icon"><svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" data-svg="arrow-right"><polyline fill="none" stroke="#000" points="10 5 15 9.5 10 14"></polyline><line fill="none" stroke="#000" x1="4" y1="9.5" x2="15" y2="9.5"></line></svg></span></span>

				</span>


			</a>
		<?php endif;
	}
    
    public function render() {
            
        $settings = $this->parent->get_settings_for_display();

        $skin_name = 'slice';

        $this->parent->render_header( $skin_name );

        $this->render_slides_loop();

        $this->render_footer();
    }
}
