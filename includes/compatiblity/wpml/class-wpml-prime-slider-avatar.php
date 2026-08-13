<?php

namespace PrimeSlider\Includes;

defined('ABSPATH') || die();

/**
 * Class WPML_PrimeSlider_Avatar
 */
class WPML_PrimeSlider_Avatar extends WPML_Module_With_Items {

    /**
     * @return string
     */
    public function get_items_field() {
        return 'slides';
    }

    /**
     * @return array
     */
    protected function get_fields() {
        return [
            'title',
            'excerpt',
            'slide_button_text',
            'title_link' => ['url'],
            'button_link' => ['url'],
        ];
    }

    /**
     * @param string $field
     *
     * @return string
     */
    protected function get_title($field) {
        switch ($field) {
            case 'title':
                return esc_html__('Title', 'bdthemes-prime-slider-lite');
            case 'title_link':
                return esc_html__('Title Link', 'bdthemes-prime-slider-lite');
            case 'excerpt':
                return esc_html__('Excerpt', 'bdthemes-prime-slider-lite');
            case 'slide_button_text':
                return esc_html__('Button Text', 'bdthemes-prime-slider-lite');
            case 'button_link':
                return esc_html__('Button Link', 'bdthemes-prime-slider-lite');
            default:
                return '';
        }
    }

    /**
     * @param string $field
     *
     * @return string
     */
    protected function get_editor_type($field) {
        switch ($field) {
            case 'title':
            case 'slide_button_text':
                return 'LINE';
            case 'excerpt':
                return 'VISUAL';
            case 'button_link':
            case 'title_link':
                return 'LINK';
            default:
                return '';
        }
    }
}
