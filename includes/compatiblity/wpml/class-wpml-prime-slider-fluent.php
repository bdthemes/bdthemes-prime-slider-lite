<?php

namespace PrimeSlider\Includes;

defined('ABSPATH') || exit;

/**
 * Class WPML_PrimeSlider_Fluent
 */
class WPML_PrimeSlider_Fluent extends WPML_Module_With_Items {

    /**
     * Repeater field name
     *
     * @return string
     */
    public function get_items_field() {
        return 'social_link_list';
    }

    /**
     * Fields inside repeater that should be translatable
     *
     * @return array
     */
    protected function get_fields() {
        return [
            'social_link_title',
            'social_icon_link' => ['url'],
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
            case 'social_link_title':
                return esc_html__('Social Link Title', 'bdthemes-prime-slider');

            case 'social_icon_link':
                return esc_html__('Social Icon Link', 'bdthemes-prime-slider');

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
            case 'social_link_title':
                return 'LINE';

            case 'social_icon_link':
                return 'LINK';

            default:
                return '';
        }
    }
}
