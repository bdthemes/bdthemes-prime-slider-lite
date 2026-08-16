<?php

namespace PrimeSlider\Includes;

defined('ABSPATH') || exit;

/**
 * Class WPML_PrimeSlider_Escape
 */
class WPML_PrimeSlider_Escape extends WPML_Module_With_Items {

    /**
     * Repeater field name
     *
     * @return string
     */
    public function get_items_field() {
        return 'slides';
    }

    /**
     * Fields inside repeater that should be translatable
     *
     * @return array
     */
    protected function get_fields() {
        return [
            'title',
            'text',
            'slide_button_text',
            'title_link' => ['url'],
            'button_link' => ['url'],
        ];
    }

    /**
     * Field labels shown in WPML Translation Editor
     *
     * @param string $field
     * @return string
     */
    protected function get_title($field) {
        switch ($field) {
            case 'title':
                return esc_html__('Title', 'bdthemes-prime-slider-lite');

            case 'title_link':
                return esc_html__('Title Link', 'bdthemes-prime-slider-lite');

            case 'slide_button_text':
                return esc_html__('Button Text', 'bdthemes-prime-slider-lite');

            case 'button_link':
                return esc_html__('Button Link', 'bdthemes-prime-slider-lite');

            case 'text':
                return esc_html__('Text', 'bdthemes-prime-slider-lite');

            default:
                return '';
        }
    }

    /**
     * Editor type for WPML Translation Editor
     *
     * @param string $field
     * @return string
     */
    protected function get_editor_type($field) {
        switch ($field) {
            case 'title':
            case 'slide_button_text':
                return 'LINE';

            case 'title_link':
            case 'button_link':
                return 'LINK';

            case 'text':
                return 'VISUAL';

            default:
                return '';
        }
    }
}