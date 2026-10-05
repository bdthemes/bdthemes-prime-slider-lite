<?php

/**
 * Prime Slider widget filters
 * @since 3.0.0
 */

use PrimeSlider\Admin\ModuleService;


if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Settings Filters
if (!function_exists('bdtps_is_dashboard_enabled')) {
    function bdtps_is_dashboard_enabled() {
        return apply_filters('PrimeSlider/settings/dashboard', true);
    }
}

if (!function_exists('prime_slider_is_widget_enabled')) {
    function prime_slider_is_widget_enabled($widget_id, $options = []) {

        if(!$options){
            $options = get_option('prime_slider_active_modules', []);
        }

        if( ModuleService::is_module_active($widget_id, $options)){
            $widget_id = str_replace('-','_', $widget_id);
            return apply_filters("PrimeSlider/widget/{$widget_id}", true);
        }
    }
}

if (!function_exists('prime_slider_is_extend_enabled')) {
    function prime_slider_is_extend_enabled($widget_id, $options = []) {

        if(!$options){
            $options = get_option('prime_slider_elementor_extend', []);
        }

        if( ModuleService::is_module_active($widget_id, $options)){
            $widget_id = str_replace('-','_', $widget_id);
            return apply_filters("PrimeSlider/extend/{$widget_id}", true);
        }
    }
}

if (!function_exists('prime_slider_is_third_party_enabled')) {
    function prime_slider_is_third_party_enabled($widget_id, $options = []) {

        if(!$options){
            $options = get_option('prime_slider_third_party_widget', []);
        }

        if( ModuleService::is_module_active($widget_id, $options)){
            $widget_id = str_replace('-','_', $widget_id);
            return apply_filters("PrimeSlider/widget/{$widget_id}", true);
        }
    }
}

if ( ! function_exists( 'prime_slider_is_conditional_assets_enabled' ) ) {
	function prime_slider_is_conditional_assets_enabled() {
		return apply_filters( 'PrimeSlider/optimization/conditional_assets', true );
	}
}

if ( ! function_exists( 'prime_slider_is_asset_optimization_enabled' ) ) {
	/**
	 * Whether Asset Optimization (Prime Slider > Special Features) is switched on.
	 *
	 * With it on, the module CSS and JS files a page loads are joined into one
	 * combined stylesheet and one combined script for that page; see
	 * PrimeSlider\Includes\Asset_Combiner.
	 *
	 * @return bool
	 */
	function prime_slider_is_asset_optimization_enabled() {
		$enabled = ( 'on' === prime_slider_option( 'asset-manager', 'prime_slider_other_settings', 'off' ) );

		return (bool) apply_filters( 'PrimeSlider/optimization/asset_manager', $enabled );
	}
}

if ( ! function_exists( 'prime_slider_module_asset_depends' ) ) {
	/**
	 * The shared handles every module stylesheet or script depends on.
	 *
	 * UIkit and the site helper are not enqueued on every page: a page without a
	 * Prime Slider widget has no use for them. Every `bdtps-<module>` handle depends
	 * on them instead, so a page loads them exactly when it loads a module, and in
	 * the head, because Elementor enqueues a page's widget handles before the head
	 * prints. Add-ons (Prime Slider Pro) append their own shared handles here.
	 *
	 * @param string $type      'css' or 'js'.
	 * @param string $module_id Module slug.
	 * @return string[]
	 */
	function prime_slider_module_asset_depends( $type, $module_id = '' ) {
		if ( 'js' === $type ) {
			$depends = [ 'jquery', 'bdt-uikit', 'prime-slider-site', 'prime-slider-a11y' ];
		} else {
			$depends = [ 'bdt-uikit', 'prime-slider-site' ];
		}

		/**
		 * Filters the handles a Prime Slider module stylesheet or script depends on.
		 *
		 * @param string[] $depends   Handles the module file depends on.
		 * @param string   $type      'css' or 'js'.
		 * @param string   $module_id Module slug.
		 */
		$depends = apply_filters( 'prime_slider/module/asset_depends', $depends, $type, $module_id );

		return array_values( array_unique( array_filter( (array) $depends, 'is_string' ) ) );
	}
}


