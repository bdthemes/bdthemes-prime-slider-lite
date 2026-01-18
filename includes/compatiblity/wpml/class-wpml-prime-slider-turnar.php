<?php

namespace PrimeSlider\Includes;

defined('ABSPATH') || exit;

/**
 * Class WPML_PrimeSlider_Turnar
 */
class WPML_PrimeSlider_Turnar extends WPML_Module_With_Items {

    /**
     * Repeater field name
     */
    public function get_items_field() {
        return 'turnar_items';
    }

    /**
     * Fields inside repeater that should be translatable
     */
    protected function get_fields() {
        return [
            'turnar_title',
            'turnar_description',
            'turnar_url' => ['url'],
        ];
    }

    /**
     * Field labels shown in WPML Translation Editor
     */
    protected function get_title($field) {
        switch ($field) {
            case 'turnar_title':
                return esc_html__('Title', 'bdthemes-prime-slider');

            case 'turnar_description':
                return esc_html__('Description', 'bdthemes-prime-slider');

            case 'turnar_url':
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
            case 'turnar_title':
                return 'LINE';

            case 'turnar_description':
                return 'VISUAL';

            case 'turnar_url':
                return 'LINK';

            default:
                return '';
        }
    }
}