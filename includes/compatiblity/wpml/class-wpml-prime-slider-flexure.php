<?php

namespace PrimeSlider\Includes;

defined('ABSPATH') || exit;

/**
 * Class WPML_PrimeSlider_Flexure
 */
class WPML_PrimeSlider_Flexure extends WPML_Module_With_Items {

    /**
     * Repeater field name
     *
     * @return string
     */
    public function get_items_field() {
        return 'ps_slider';
    }

    /**
     * Fields inside repeater that should be translatable
     *
     * @return array
     */
    protected function get_fields() {
        return [
            'ps_flexure_title',
            'ps_flexure_content',
            'title_link' => ['url'],
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
            case 'ps_flexure_title':
                return esc_html__('Title', 'bdthemes-prime-slider');

            case 'title_link':
                return esc_html__('Title Link', 'bdthemes-prime-slider');

            case 'ps_flexure_content':
                return esc_html__('Content', 'bdthemes-prime-slider');

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
            case 'ps_flexure_title':
            case 'button_text':
            case 'social_link_title':
                return 'LINE';

            case 'ps_flexure_content':
                return 'VISUAL';

            case 'title_link':
                return 'LINK';

            default:
                return '';
        }
    }
}

/**
 * Class WPML_PrimeSlider_Flexure_Social_Link
 */
class WPML_PrimeSlider_Flexure_Social_Link extends WPML_Module_With_Items {

    public function get_items_field() {
        return 'social_link_list';
    }

    protected function get_fields() {
        return [
            'social_link_title',
            'social_icon_link' => ['url'],
        ];
    }

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