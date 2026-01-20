<?php

namespace PrimeSlider\Includes;

defined('ABSPATH') || exit;

/**
 * Class WPML_PrimeSlider_Crossroad
 */
class WPML_PrimeSlider_Crossroad extends WPML_Module_With_Items {

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
            'meta_text',
            'modal_title',
            'modal_meta_text',
            'excerpt',
            'slide_button_text',
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

            case 'meta_text':
                return esc_html__('Meta Text', 'bdthemes-prime-slider');

            case 'modal_title':
                return esc_html__('Modal Title', 'bdthemes-prime-slider');

            case 'modal_meta_text':
                return esc_html__('Modal Meta Text', 'bdthemes-prime-slider');

            case 'excerpt':
                return esc_html__('Excerpt', 'bdthemes-prime-slider');

            case 'slide_button_text':
                return esc_html__('Button Text', 'bdthemes-prime-slider');

            case 'button_link':
                return esc_html__('Button Link', 'bdthemes-prime-slider');

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
            case 'meta_text':
            case 'modal_title':
            case 'modal_meta_text':
            case 'slide_button_text':
                return 'LINE';

            case 'button_link':
                return 'LINK';

            case 'excerpt':
                return 'VISUAL';

            default:
                return '';
        }
    }
}