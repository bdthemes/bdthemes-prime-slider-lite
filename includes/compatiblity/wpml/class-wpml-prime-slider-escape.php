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
                return esc_html__('Title', 'bdthemes-prime-slider');

            case 'title_link':
                return esc_html__('Title Link', 'bdthemes-prime-slider');

            case 'slide_button_text':
                return esc_html__('Button Text', 'bdthemes-prime-slider');

            case 'button_link':
                return esc_html__('Button Link', 'bdthemes-prime-slider');

            case 'text':
                return esc_html__('Text', 'bdthemes-prime-slider');

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