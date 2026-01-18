<?php

namespace PrimeSlider\Includes;

defined('ABSPATH') || exit;

/**
 * Class WPML_PrimeSlider_Prism
 */
class WPML_PrimeSlider_Prism extends WPML_Module_With_Items {

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
            'text',
            'title_link' => [ 'url' ],
        ];
    }

    /**
     * Field labels shown in WPML Translation Editor
     */
    protected function get_title($field) {
        switch ($field) {
            case 'title':
                return esc_html__('Title', 'bdthemes-prime-slider');

            case 'text':
                return esc_html__('Text', 'bdthemes-prime-slider');

            case 'title_link':
                return esc_html__('Title Link', 'bdthemes-prime-slider');

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
                return 'LINE';

            case 'text':
                return 'VISUAL';

            case 'title_link':
                return 'LINK';

            default:
                return '';
        }
    }
}
