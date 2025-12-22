<?php
namespace PrimeSlider\Includes;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class WPML_PrimeSlider_WooLamp
 * Handles translation of repeater 'social_link_list' in the WooLamp widget
 */
class WPML_PrimeSlider_WooLamp extends WPML_Module_With_Items {

    /**
     * @return string
     */
    public function get_items_field() {
        return 'share_buttons';
    }

    /**
     * @return array
     */
    public function get_fields() {
        return array(
            'text',
            'button',
        );
    }

    /**
     * @param string $field
     * @return string
     */
    protected function get_title( $field ) {
        switch ( $field ) {
            case 'text':
                return esc_html__( 'Custom Label', 'bdthemes-prime-slider' );
            case 'button':
                return esc_html__( 'Social Media', 'bdthemes-prime-slider' );
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
            case 'text':
            case 'button':
                return 'LINE';
            default:
                return 'LINE';
        }
    }
}
