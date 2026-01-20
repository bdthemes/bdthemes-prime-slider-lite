<?php
namespace PrimeSlider\Includes;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class WPML_PrimeSlider_Isolate
 * Handles translation of repeater 'slides' in the Isolate widget
 */
class WPML_PrimeSlider_Isolate extends WPML_Module_With_Items {

    /**
     * @return string
     */
    public function get_items_field() {
        return 'slides';
    }

    /**
     * @return array
     */
    public function get_fields() {
        return array(
            'title',
            'sub_title',
            'excerpt',
            'slide_button_text',
            'button_link' => ['url'],
            'title_link' => ['url'],
            'image_link_video' => ['url'],
            'lightbox_link' => ['url'],
            'image_link_vimeo' => ['url'],
            'image_link_google_map' => ['url'],
            'image_link_website' => ['url'],
        );
    }

    /**
     * @param string $field
     * @return string
     */
    protected function get_title( $field ) {
        switch ( $field ) {
            case 'title':
                return esc_html__( 'Title', 'bdthemes-prime-slider' );
            case 'sub_title':
                return esc_html__( 'Sub Title', 'bdthemes-prime-slider' );
            case 'excerpt':
                return esc_html__( 'Excerpt', 'bdthemes-prime-slider' );
            case 'slide_button_text':
                return esc_html__( 'Read More', 'bdthemes-prime-slider' );
            case 'button_link':
                return esc_html__( 'Button Link', 'bdthemes-prime-slider' );
            case 'title_link':
                return esc_html__( 'Title Link', 'bdthemes-prime-slider' );
            case 'image_link_video':
                return esc_html__( 'Video Link', 'bdthemes-prime-slider' );
            case 'lightbox_link':
                return esc_html__( 'YouTube Link', 'bdthemes-prime-slider' );
            case 'image_link_vimeo':
                return esc_html__( 'Vimeo Link', 'bdthemes-prime-slider' );
            case 'image_link_google_map':
                return esc_html__( 'Google Map Link', 'bdthemes-prime-slider' );
            case 'image_link_website':
                return esc_html__( 'Website Link', 'bdthemes-prime-slider' );
            default:
                return '';
        }
    }

    /**
     * @param string $field
     * @return string
     */
    protected function get_editor_type( $field ) {
        switch ( $field ) {
            case 'excerpt':
                return 'VISUAL';
            case 'title':
            case 'sub_title':
            case 'slide_button_text':
                return 'LINE';
            case 'button_link':
            case 'title_link':
            case 'image_link_video':
            case 'lightbox_link':
            case 'image_link_vimeo':
            case 'image_link_google_map':
            case 'image_link_website':
                return 'LINK';
            default:
                return 'LINE';
        }
    }
}

/**
 * Class WPML_PrimeSlider_Isolate_Social_Link
 * Handles translation of repeater 'social_link_list' in the Isolate widget
 */
class WPML_PrimeSlider_Isolate_Social_Link extends WPML_Module_With_Items {

    /**
     * @return string
     */
    public function get_items_field() {
        return 'social_link_list';
    }

    /**
     * @return array
     */
    public function get_fields() {
        return array(
            'social_link_title',
            'social_icon_link' => ['url'],
        );
    }

    /**
     * @param string $field
     * @return string
     */
    protected function get_title( $field ) {
        switch ( $field ) {
            case 'social_link_title':
                return esc_html__( 'Social Link Title', 'bdthemes-prime-slider' );
            case 'social_icon_link':
                return esc_html__( 'Social Icon Link', 'bdthemes-prime-slider' );
            default:
                return '';
        }
    }

    /**
     * @param string $field
     * @return string
     */
    protected function get_editor_type( $field ) {
        switch ( $field ) {
            case 'social_link_title':
                return 'LINE';
            case 'social_icon_link':
                return 'URL';
            default:
                return 'LINE';
        }
    }
}
