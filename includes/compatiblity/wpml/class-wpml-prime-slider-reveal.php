<?php

namespace PrimeSlider\Includes;

defined('ABSPATH') || exit;

/**
 * Class WPML_PrimeSlider_Reveal
 */
class WPML_PrimeSlider_Reveal extends WPML_Module_With_Items {

    /**
     * Repeater field name
     */
    public function get_items_field() {
        return 'slides';
    }

    /**
     * Fields inside repeater that should be translatable
     */
    protected function get_fields() {
        return [
            'title',
            'sub_title',
            'text',
            'slide_button_text',
            'image_caption',
            'title_link'  => [ 'url' ],
            'button_link' => [ 'url' ],
        ];
    }

    /**
     * Field labels shown in WPML Translation Editor
     */
    protected function get_title($field) {
        switch ($field) {
            case 'title':
                return esc_html__('Title', 'bdthemes-prime-slider-lite');

            case 'sub_title':
                return esc_html__('Sub Title', 'bdthemes-prime-slider-lite');

            case 'text':
                return esc_html__('Text', 'bdthemes-prime-slider-lite');

            case 'slide_button_text':
                return esc_html__('Button Text', 'bdthemes-prime-slider-lite');

            case 'image_caption':
                return esc_html__('Image Caption', 'bdthemes-prime-slider-lite');

            case 'title_link':
                return esc_html__('Title Link', 'bdthemes-prime-slider-lite');

            case 'button_link':
                return esc_html__('Button Link', 'bdthemes-prime-slider-lite');

            default:
                return '';
        }
    }

    /**
     * Editor type for WPML Translation Editor
     */
    protected function get_editor_type($field) {
        switch ($field) {
            case 'title':
            case 'sub_title':
            case 'slide_button_text':
            case 'image_caption':
                return 'LINE';

            case 'text':
                return 'VISUAL';

            case 'title_link':
            case 'button_link':
                return 'LINK';

            default:
                return '';
        }
    }
}
