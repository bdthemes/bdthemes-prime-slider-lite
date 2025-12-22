<?php
namespace PrimeSlider\Includes;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class WPML_PrimeSlider_Pagepiling
 * Handles translation of repeater 'slides' in the Pagepiling widget
 */
class WPML_PrimeSlider_Pagepiling extends WPML_Module_With_Items {

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
            'lightbox_link' => ['url'],
            'button_link' => ['url'],
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
                return esc_html__( 'Subtitle', 'bdthemes-prime-slider' );
            case 'excerpt':
                return esc_html__( 'Description', 'bdthemes-prime-slider' );
            case 'slide_button_text':
                return esc_html__( 'Button Text', 'bdthemes-prime-slider' );
            case 'lightbox_link':
                return esc_html__( 'Lightbox Link', 'bdthemes-prime-slider' );
            case 'button_link':
                return esc_html__( 'Button Link', 'bdthemes-prime-slider' );
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
            case 'lightbox_link':
            case 'button_link':
                return 'LINK';
            default:
                return 'LINE';
        }
    }
}

/**
 * Class WPML_PrimeSlider_Pagepiling_Social_Link
 * Handles translation of repeater 'social_link_list' in the Pagepiling widget
 */
class WPML_PrimeSlider_Pagepiling_Social_Link extends WPML_Module_With_Items {

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
                return 'LINK';
            default:
                return 'LINE';
        }
    }
}
