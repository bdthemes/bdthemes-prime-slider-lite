<?php
namespace PrimeSlider\Includes;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class WPML_PrimeSlider_Tango
 * Handles translation of repeater 'slides' in the Tango widget
 */
class WPML_PrimeSlider_Tango extends WPML_Module_With_Items {

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
        );
    }

    /**
     * @param string $field
     * @return string
     */
    protected function get_title( $field ) {
        switch ( $field ) {
            case 'sub_title':
                return esc_html__( 'Sub Title', 'bdthemes-prime-slider' );
            case 'title':
                return esc_html__( 'Title', 'bdthemes-prime-slider' );
            case 'title_link':
                return esc_html__( 'Title Link', 'bdthemes-prime-slider' );
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
            case 'sub_title':
            case 'title':
                return 'LINE';
            case 'title_link':
                return 'URL';
            default:
                return 'LINE';
        }
    }

}
