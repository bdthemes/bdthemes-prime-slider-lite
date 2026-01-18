<?php

namespace PrimeSlider\Includes;

defined('ABSPATH') || exit;

/**
 * Class WPML_PrimeSlider_WooHotspot
 */
class WPML_PrimeSlider_WooHotspot extends WPML_Module_With_Items {

    public function get_items_field() {
        return 'markers';
    }

    protected function get_fields() {
        return [
            'text',
            'product_title',
            'product_text',
            'product_price',
            'product_link_text',
            'product_link' => ['url'],
        ];
    }

    protected function get_title($field) {
        switch ($field) {
            case 'text':
                return esc_html__('Text', 'bdthemes-prime-slider');
            case 'product_title':
                return esc_html__('Product Title', 'bdthemes-prime-slider');
            case 'product_text':
                return esc_html__('Product Text', 'bdthemes-prime-slider');
            case 'product_price':
                return esc_html__('Product Price', 'bdthemes-prime-slider');
            case 'product_link_text':
                return esc_html__('Product Button', 'bdthemes-prime-slider');
            case 'product_link':
                return esc_html__('Product Link', 'bdthemes-prime-slider');
            default:
                return '';
        }
    }

    protected function get_editor_type($field) {
        switch ($field) {
            case 'text':
            case 'product_title':
            case 'product_price':
            case 'product_link_text':
                return 'LINE';
            case 'product_text':
                return 'VISUAL';
            case 'product_link':
                return 'LINK';
            default:
                return '';
        }
    }
}

/**
 * Class WPML_PrimeSlider_WooHotspot_Two
 */
class WPML_PrimeSlider_WooHotspot_Two extends WPML_Module_With_Items {

    public function get_items_field() {
        return 'two_markers';
    }

    protected function get_fields() {
        return [
            'two_text',
            'two_product_title',
            'two_product_text',
            'two_product_price',
            'two_product_link_text',
            'two_product_link' => ['url'],
        ];
    }

    protected function get_title($field) {
        switch ($field) {
            case 'two_text':
                return esc_html__('Text', 'bdthemes-prime-slider');
            case 'two_product_title':
                return esc_html__('Product Title', 'bdthemes-prime-slider');
            case 'two_product_text':
                return esc_html__('Product Text', 'bdthemes-prime-slider');
            case 'two_product_price':
                return esc_html__('Product Price', 'bdthemes-prime-slider');
            case 'two_product_link_text':
                return esc_html__('Product Button', 'bdthemes-prime-slider');
            case 'two_product_link':
                return esc_html__('Product Link', 'bdthemes-prime-slider');
            default:
                return '';
        }
    }

    protected function get_editor_type($field) {
        switch ($field) {
            case 'two_text':
            case 'two_product_title':
            case 'two_product_price':
            case 'two_product_link_text':
                return 'LINE';
            case 'two_product_text':
                return 'VISUAL';
            case 'two_product_link':
                return 'LINK';
            default:
                return '';
        }
    }
}

/**
 * Class WPML_PrimeSlider_WooHotspot_Three
 */
class WPML_PrimeSlider_WooHotspot_Three extends WPML_Module_With_Items {

    public function get_items_field() {
        return 'three_markers';
    }

    protected function get_fields() {
        return [
            'three_text',
            'three_product_title',
            'three_product_text',
            'three_product_price',
            'three_product_link_text',
            'three_product_link' => ['url'],
        ];
    }

    protected function get_title($field) {
        switch ($field) {
            case 'three_text':
                return esc_html__('Text', 'bdthemes-prime-slider');
            case 'three_product_title':
                return esc_html__('Product Title', 'bdthemes-prime-slider');
            case 'three_product_text':
                return esc_html__('Product Text', 'bdthemes-prime-slider');
            case 'three_product_price':
                return esc_html__('Product Price', 'bdthemes-prime-slider');
            case 'three_product_link_text':
                return esc_html__('Product Button', 'bdthemes-prime-slider');
            case 'three_product_link':
                return esc_html__('Product Link', 'bdthemes-prime-slider');
            default:
                return '';
        }
    }

    protected function get_editor_type($field) {
        switch ($field) {
            case 'three_text':
            case 'three_product_title':
            case 'three_product_price':
            case 'three_product_link_text':
                return 'LINE';
            case 'three_product_text':
                return 'VISUAL';
            case 'three_product_link':
                return 'LINK';
            default:
                return '';
        }
    }
}
