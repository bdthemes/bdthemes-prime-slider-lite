<?php
namespace PrimeSlider\Includes;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class WPML_PrimeSlider_Mount
 * Handles translation of repeater 'slides' in the Mount widget
 */
class WPML_PrimeSlider_Mount extends WPML_Module_With_Items {

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
            'title_link' => ['url'],
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
            case 'title':
            case 'sub_title':
                return 'LINE';
            case 'title_link':
                return 'LINK';
            default:
                return 'LINE';
        }
    }
}

/**
 * Class WPML_PrimeSlider_Mount_Social_Link
 * Handles translation of repeater 'social_link_list' in the Mount widget
 */
class WPML_PrimeSlider_Mount_Social_Link extends WPML_Module_With_Items {

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
