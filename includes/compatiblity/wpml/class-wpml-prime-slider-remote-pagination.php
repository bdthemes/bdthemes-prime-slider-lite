<?php

namespace PrimeSlider\Includes;

defined('ABSPATH') || exit;

/**
 * Class WPML_PrimeSlider_Remote_Pagination
 */
class WPML_PrimeSlider_Remote_Pagination extends WPML_Module_With_Items {

    /**
     * Repeater field name
     */
    public function get_items_field() {
        return 'pagination_list';
    }

    /**
     * Fields inside repeater that should be translatable
     */
    protected function get_fields() {
        return [
            'pagination_number',
        ];
    }

    /**
     * Field labels shown in WPML Translation Editor
     */
    protected function get_title($field) {
        switch ($field) {
            case 'pagination_number':
                return esc_html__('Bullet Number', 'bdthemes-prime-slider');

            default:
                return '';
        }
    }

    /**
     * Editor type for WPML Translation Editor
     */
    protected function get_editor_type($field) {
        switch ($field) {
            case 'pagination_number':
                return 'LINE';

            default:
                return '';
        }
    }
}
