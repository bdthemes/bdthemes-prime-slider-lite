<?php

namespace PrimeSlider;

use PrimeSlider\Admin\ModuleService;

if ( !defined('ABSPATH') ) {
    exit;
} // Exit if accessed directly

final class Manager {


    public function register_module_and_assets() {

        ModuleService::get_widget_settings(function ($settings) {
            $core_widgets        = $settings['settings_fields']['prime_slider_active_modules'];
            // $extensions          = $settings['settings_fields']['prime_slider_elementor_extend'];
            $third_party_widgets = $settings['settings_fields']['prime_slider_third_party_widget'];

            /**
             * Our Widget
             */
            foreach ( $core_widgets as $widget ) {
                if ( prime_slider_is_widget_enabled($widget['name']) ) {
                    $this->load_module_instance($widget);
                }
            }

            /**
             * Extension
             */
            // foreach ( $extensions as $extension ) {
            //     if ( prime_slider_is_extend_enabled($extension['name']) ) {
            //         $this->load_module_instance($extension);
            //     }
            // }

            /**
             * Third Party Widget
             */
            foreach ( $third_party_widgets as $widget ) {
                if ( prime_slider_is_third_party_enabled($widget['name']) ) {
                    if ( isset($widget['plugin_path']) && ModuleService::is_plugin_active($widget['plugin_path']) ) {
                        $this->load_module_instance($widget);
                    }
                }
            }
            // Static module if need
            $this->load_module_instance(['name' => 'elementor']);

        });
    }

    /**
     * Register frontend CSS/JS for a single module.
     *
     * @param string $module_id Module ID.
     * @return void
     */
    public static function register_module_assets( $module_id ) {
        if ( 'elementor' === $module_id ) {
            return;
        }

        $direction = is_rtl() ? '.rtl' : '';
        $suffix    = '.min';
        $style_id  = 'ps-' . $module_id;
        $script_id = 'ps-' . $module_id;

        if ( ModuleService::has_module_style( $module_id, BDTPS_CORE_MODULES_PATH ) && ! wp_style_is( $style_id, 'registered' ) ) {
            wp_register_style( $style_id, BDTPS_CORE_URL . 'assets/css/ps-' . $module_id . $direction . '.css', [], BDTPS_CORE_VER );
        }

        if ( ModuleService::has_module_script( $module_id, BDTPS_CORE_MODULES_PATH ) && ! wp_script_is( $script_id, 'registered' ) ) {
            wp_register_script( $script_id, BDTPS_CORE_URL . 'assets/js/modules/ps-' . $module_id . $suffix . '.js', [ 'jquery', 'bdt-uikit' ], BDTPS_CORE_VER, true );
        }
    }

    public function load_module_instance($module) {

        $module_id  = $module['name'];
        $class_name = str_replace('-', ' ', $module_id);
        $class_name = str_replace(' ', '', ucwords($class_name));
        $class_name = __NAMESPACE__ . '\\Modules\\' . $class_name . '\\Module';

         if(class_exists($class_name)){
            $class_name::instance();
        }
    }

    public function __construct() {

        $this->register_module_and_assets();
    }
}
