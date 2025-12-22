<?php
namespace PrimeSlider\Includes;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class WPML_PrimeSlider_Woocommerce
 * Handles translation of repeater 'social_link_list' in the Woocommerce widget
 */
class WPML_PrimeSlider_Woocommerce extends WPML_Module_With_Items {

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
