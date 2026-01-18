<?php

namespace PrimeSlider\Includes;

defined('ABSPATH') || exit;

/**
 * Class WPML_PrimeSlider_Astoria
 */
class WPML_PrimeSlider_Astoria extends WPML_Module_With_Items {

    /**
     * Repeater field name
     *
     * @return string
     */
    public function get_items_field() {
        return 'slides';
    }

    /**
     * Fields inside repeater that are translatable
     *
     * @return array
     */
    protected function get_fields() {
        return [
            'title',
            'sub_title',
            'text',
            'slide_button_text',
            'title_link' => ['url'],
            'button_link' => ['url'],
        ];
    }

    /**
     * Field title shown in WPML Translation Editor
     *
     * @param string $field
     * @return string
     */
    protected function get_title($field) {
        switch ($field) {
            case 'title':
                return esc_html__('Title', 'bdthemes-prime-slider');

            case 'sub_title':
                return esc_html__('Sub Title', 'bdthemes-prime-slider');

            case 'title_link':
                return esc_html__('Title Link', 'bdthemes-prime-slider');

            case 'text':
                return esc_html__('Text', 'bdthemes-prime-slider');

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
            case 'sub_title':
            case 'slide_button_text':
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

/**
 * Class WPML_PrimeSlider_Astoria_Social_Link
 */
class WPML_PrimeSlider_Astoria_Social_Link extends WPML_Module_With_Items {

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