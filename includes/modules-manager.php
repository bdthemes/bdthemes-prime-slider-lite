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

    public function load_module_instance($module) {


        $direction = is_rtl() ? '.rtl' : '';
        $suffix    = '.min';

        $module_id  = $module['name'];
        $class_name = str_replace('-', ' ', $module_id);
        $class_name = str_replace(' ', '', ucwords($class_name));
        $class_name = __NAMESPACE__ . '\\Modules\\' . $class_name . '\\Module';

        
        if ( !prime_slider_is_preview() ) {
            // Every widget declares its `bdtps-<module>` handle, and Elementor enqueues
            // a page's widget handles before the head prints. The module files depend
            // on UIkit and the site helper, so those load exactly on the pages that
            // use a Prime Slider widget (see prime_slider_module_asset_depends()).

            // register widgets css
            if ( ModuleService::has_module_style($module_id, BDTPS_CORE_MODULES_PATH) ) {
                wp_register_style('bdtps-' . $module_id, BDTPS_CORE_URL . 'assets/css/ps-' . $module_id . $direction . '.css', prime_slider_module_asset_depends('css', $module_id), BDTPS_CORE_VER);
            }
            // register widget JS
            if ( ModuleService::has_module_script($module_id, BDTPS_CORE_MODULES_PATH) ) {
                wp_register_script('bdtps-' . $module_id, BDTPS_CORE_URL . 'assets/js/modules/ps-' . $module_id . $suffix . '.js', prime_slider_module_asset_depends('js', $module_id), BDTPS_CORE_VER, true);
            }
        }
        

         if(class_exists($class_name)){
            $class_name::instance();
        }
    }

    public function __construct() {

        $this->register_module_and_assets();
    }
}
