<?php

namespace PrimeSlider\Modules\Blog\Skins;

use PrimeSlider\Utils;
use Elementor\Group_Control_Image_Size;
use Elementor\Icons_Manager;
use Elementor\Skin_Base as Elementor_Skin_Base;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Skin_Folio extends Elementor_Skin_Base {

  public function get_id() {
    return 'folio';
  }

  public function get_title() {
    return esc_html__('Folio', 'bdthemes-prime-slider');
  }

  /**
	 * Social Icons Here
	 */
	public function render_social_link( $position = 'right', $label = false, $class = [] ) {
		$settings = $this->parent->get_settings_for_display();

		if ( '' == $settings['show_social_icon'] ) {
			return;
		}

		$this->parent->add_render_attribute( 'social-icon', 'class', 'bdt-social-icon reveal-muted' );
		$this->parent->add_render_attribute( 'social-icon', 'class', $class );

		?>
		<div <?php $this->parent->print_render_attribute_string( 'social-icon' ); ?>>

			<?php if ( $label ) : ?>
				<h3>
					<?php esc_html_e( 'Follow Us', 'bdthemes-prime-slider' ); ?>
				</h3>
			<?php endif; ?>
      <div class="bdt-social-icon-item-wrap bdt-flex bdt-flex-middle">

			<?php
			foreach ( $settings['social_link_list'] as $index => $link ) :

				$link_key = 'link_' . $index;

				$tooltip = '';
				if ( 'yes' === $settings['social_icon_tooltip'] ) {

					$tooltip_text = wp_kses_post(strip_tags( $link['social_link_title'])); // Escape for safe attribute usage
					$tooltip = 'title: ' . htmlspecialchars($tooltip_text, ENT_QUOTES) . ';'; // Build the tooltip attribute safely

				}

				if ( isset( $link['social_icon_link']['url'] ) && ! empty( $link['social_icon_link']['url'] ) ) {
					$this->parent->add_link_attributes( $link_key, $link['social_icon_link'] );
				}

				?>
        
          <a <?php $this->parent->print_render_attribute_string( $link_key ); ?> data-bdt-tooltip="<?php echo esc_attr( $tooltip ); ?>">
            <span><span>
                <?php Icons_Manager::render_icon( $link['social_icon'], [ 'aria-hidden' => 'true', 'class' => 'fa-fw' ] ); ?>
              </span></span>
          </a>
			<?php endforeach; ?>
      </div>
		</div>
		<?php
	}

  public function render_footer() {
    $settings = $this->parent->get_settings_for_display();
  ?>

    </ul>
    </div>

    <div class="bdt-ps-meta-content bdt-position-bottom bdt-flex bdt-flex-between bdt-flex-middle reveal-muted">
      <?php $this->render_social_link($position = 'top', $label = true, $class = ['bdt-flex bdt-flex-middle']); ?>
    </div>

    </div>
    </div>
  <?php
  }
  public function render_meta() {
    $settings = $this->parent->get_settings_for_display();

    if ('yes' !== $settings['show_meta']) {
      return;
    }

    ?>
    <div class="bdt-ps-meta-wrap">

        <?php if ('yes' == $settings['show_admin_info']) : ?>
          <div class="bdt-prime-slider-meta bdt-flex bdt-flex-middle">
            <div class="bdt-post-slider-author bdt-margin-small-right bdt-border-circle bdt-overflow-hidden bdt-visible@s">
              <?php echo get_avatar(get_the_author_meta('ID'), 100); ?>
            </div>
            <div class="bdt-meta-author">
              <span class="bdt-author bdt-text-capitalize">
                <strong><?php esc_html_e('Written by&nbsp;', 'bdthemes-prime-slider'); ?></strong><br>
                <?php echo esc_attr(get_the_author()); ?> </span>
            </div>
          </div>
        <?php endif; ?>

        <?php if ('yes' == $settings['show_date']) : ?>
          <div class="bdt-ps-meta bdt-visible@m">
            <div class="bdt-ps-meta-item bdt-flex bdt-flex-middle">
              <div class="bdt-meta-icon">
                <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="calendar-day" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" class="svg-inline--fa fa-calendar-day fa-w-14 fa-2x">
                  <path fill="currentColor" d="M0 464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V192H0v272zm64-192c0-8.8 7.2-16 16-16h96c8.8 0 16 7.2 16 16v96c0 8.8-7.2 16-16 16H80c-8.8 0-16-7.2-16-16v-96zM400 64h-48V16c0-8.8-7.2-16-16-16h-32c-8.8 0-16 7.2-16 16v48H160V16c0-8.8-7.2-16-16-16h-32c-8.8 0-16 7.2-16 16v48H48C21.5 64 0 85.5 0 112v48h448v-48c0-26.5-21.5-48-48-48z" class=""></path>
                </svg>
              </div>
              <div class="bdt-meta-text">
                <span>
                  <strong><?php esc_html_e('Published on', 'bdthemes-prime-slider'); ?></strong><br> <?php echo get_the_date(); ?>
                </span>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <?php if ('yes' == $settings['show_comments']) : ?>
          <div class="bdt-ps-meta bdt-visible@m">
            <div class="bdt-ps-meta-item bdt-flex bdt-flex-middle">
              <div class="bdt-meta-icon">
                <svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="comment" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="svg-inline--fa fa-comment fa-w-16 fa-2x">
                  <path fill="currentColor" d="M256 32C114.6 32 0 125.1 0 240c0 47.6 19.9 91.2 52.9 126.3C38 405.7 7 439.1 6.5 439.5c-6.6 7-8.4 17.2-4.6 26S14.4 480 24 480c61.5 0 110-25.7 139.1-46.3C192 442.8 223.2 448 256 448c141.4 0 256-93.1 256-208S397.4 32 256 32zm0 368c-26.7 0-53.1-4.1-78.4-12.1l-22.7-7.2-19.5 13.8c-14.3 10.1-33.9 21.4-57.5 29 7.3-12.1 14.4-25.7 19.9-40.2l10.6-28.1-20.6-21.8C69.7 314.1 48 282.2 48 240c0-88.2 93.3-160 208-160s208 71.8 208 160-93.3 160-208 160z" class=""></path>
                </svg>
              </div>
              <div class="bdt-meta-text">
                <span>
                  <strong><?php esc_html_e('Comments By', 'bdthemes-prime-slider'); ?></strong><br>
                  <?php echo esc_attr(get_comments_number()); ?>
                </span>
              </div>
            </div>
          </div>
        <?php endif; ?>

    </div>
    <?php
  }

  public function rendar_item_image() {
    $settings = $this->parent->get_settings_for_display();

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

    <div class="bdt-ps-slide-img" style="background-image: url('<?php echo esc_url($image_final_src); ?>')"></div>

  <?php
  }

  public function render_item_content($post) {
    $settings = $this->parent->get_settings_for_display();

    $parallax_title       = 'data-bdt-slideshow-parallax="y: 100,0,-80; opacity: 1,1,0"';
    $parallax_text         = 'data-bdt-slideshow-parallax="y: 110,0,-90; opacity: 1,1,0"';

    if ( true === _is_ps_pro_activated() ) {
      if ($settings['animation_status'] == 'yes' && !empty($settings['animation_of'])) {

        if (in_array(".bdt-title-tag", $settings['animation_of'])) {
          $parallax_title = '';
        }
        if (in_array(".bdt-blog-text", $settings['animation_of'])) {
          $parallax_text = '';
        }
      }
    }

  ?>

    <div class="bdt-container">
      <div class="bdt-prime-slider-wrapper">
        <div class="bdt-prime-slider-content">
          <div class="bdt-prime-slider-desc bdt-text-center">

            <?php if ('yes' == $settings['show_category']) : ?>
              <div data-bdt-slideshow-parallax="y: 80,0,-80; opacity: 1,1,0">
                <?php $this->parent->render_category(); ?>
              </div>
            <?php endif; ?>

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
              <?php if ('yes' == $settings['show_button_text']) : ?>
              <div class="bdt-ps-blog-btn" data-reveal="reveal-active" data-bdt-slideshow-parallax="y: 150,0,-100; opacity: 1,1,0">
                  <?php $this->parent->render_button($post); ?>
              </div>
              <?php endif; ?>

          </div>
        </div>
      </div>
    </div>

    <?php
  }

  public function render_slides_loop() {
    $settings = $this->parent->get_settings_for_display();

    $kenburns_reverse = $settings['kenburns_reverse'] ? ' bdt-animation-reverse' : '';

    $slide_index = 1;

    global $post;

    $wp_query = $this->parent->query_posts();

    if (!$wp_query->found_posts) {
      return;
    }

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

        <?php if ('none' !== $settings['overlay']) :
          $blend_type = ('blend' == $settings['overlay']) ? ' bdt-blend-' . $settings['blend_type'] : ''; ?>
          <div class="bdt-overlay-default bdt-position-cover<?php echo esc_attr($blend_type); ?>"></div>
        <?php endif; ?>

        <?php $this->render_item_content($post); ?>

        <?php $this->render_meta(); ?>

        <?php $slide_index++; ?>

      </li>


<?php
    }

    wp_reset_postdata();
  }

  public function render() {
    $skin_name = 'folio';

    $this->parent->render_header($skin_name);

    $this->render_slides_loop();

    $this->render_footer();
  }
}
