<?php

namespace PrimeSlider\Includes;

defined('ABSPATH') || exit;

/**
 * Class WPML_PrimeSlider_Monster
 */
class WPML_PrimeSlider_Monster extends WPML_Module_With_Items {

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
            'sub_title',
            'title_link' => ['url'],
        ];
    }

    /**
     * Field labels shown in WPML Translation Editor
     */
    protected function get_title($field) {
        switch ($field) {
            case 'title':
                return esc_html__('Title', 'bdthemes-prime-slider');

            case 'sub_title':
                return esc_html__('Sub Title', 'bdthemes-prime-slider');

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
            case 'sub_title':
                return 'LINE';

            case 'title_link':
                return 'LINK';

            default:
                return '';
        }
    }
}

/**
 * Class WPML_PrimeSlider_Monster_Social_Link
 */
class WPML_PrimeSlider_Monster_Social_Link extends WPML_Module_With_Items {

    /**
     * Repeater field name
     */
    public function get_items_field() {
        return 'social_link_list';
    }

    /**
     * Fields inside repeater that should be translatable
     */
    protected function get_fields() {
        return [
            'social_link_title',
            'social_icon_link' => ['url'],
        ];
    }

    /**
     * Field labels shown in WPML Translation Editor
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