<?php
namespace PrimeSlider\Includes;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class WPML_PrimeSlider_Multiscroll
 * Handles translation of repeater 'slides' in the Multiscroll widget
 */
class WPML_PrimeSlider_Multiscroll extends WPML_Module_With_Items {

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
            'sub_title',
            'title',
            'title_link' => ['url'],
            'description',
            'slide_button',
            'button_link' => ['url'],
        );
    }

    /**
     * @param string $field
     * @return string
     */
    protected function get_title( $field ) {
        switch ( $field ) {
            case 'sub_title':
                return esc_html__( 'Sub Title', 'bdthemes-prime-slider-lite' );
            case 'title':
                return esc_html__( 'Title', 'bdthemes-prime-slider-lite' );
            case 'title_link':
                return esc_html__( 'Title Link', 'bdthemes-prime-slider-lite' );
            case 'description':
                return esc_html__( 'Description', 'bdthemes-prime-slider-lite' );
            case 'slide_button':
                return esc_html__( 'Button Text', 'bdthemes-prime-slider-lite' );
            case 'button_link':
                return esc_html__( 'Button Link', 'bdthemes-prime-slider-lite' );
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
            case 'description':
                return 'AREA';
            case 'sub_title':
            case 'title':
            case 'slide_button':
                return 'LINE';
            case 'title_link':
            case 'button_link':
                return 'LINK';
            default:
                return 'LINE';
        }
    }

}
