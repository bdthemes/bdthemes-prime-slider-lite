<?php

namespace PrimeSlider\Includes;

defined('ABSPATH') || exit;

/**
 * Class WPML_PrimeSlider_Motion
 */
class WPML_PrimeSlider_Motion extends WPML_Module_With_Items {

    /**
     * Repeater field name
     */
    public function get_items_field() {
        return 'list';
    }

    /**
     * Fields inside repeater that should be translatable
     */
    protected function get_fields() {
        return [
            'list_title',
            'list_content',
            'button_link' => ['url'],
        ];
    }

    /**
     * Field labels shown in WPML Translation Editor
     */
    protected function get_title($field) {
        switch ($field) {
            case 'list_title':
                return esc_html__('Title', 'bdthemes-prime-slider');

            case 'list_content':
                return esc_html__('Description', 'bdthemes-prime-slider');

            case 'button_link':
                return esc_html__('Button Link', 'bdthemes-prime-slider');

            default:
                return '';
        }
    }

    /**
     * Editor type for WPML Translation Editor
     */
    protected function get_editor_type($field) {
        switch ($field) {
            case 'list_title':
            case 'list_content':
                return 'LINE';

            case 'button_link':
                return 'LINK';

            default:
                return '';
        }
    }
}