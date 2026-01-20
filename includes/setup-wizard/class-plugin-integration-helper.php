<?php
/**
 * Plugin Integration Helper
 * 
 * Helper functions for managing plugin integrations in setup wizard
 */

namespace PrimeSlider\SetupWizard;

if (!defined('ABSPATH')) {
    exit;
}

class Plugin_Integration_Helper {

    /**
     * Get predefined plugin configurations
     *
     * @return array Plugin configurations
     */
    public static function get_predefined_plugins() {
        return [
            'bdthemes-element-pack-lite' => [
                'recommended' => true,
                'fallback' => [
                    'name' => 'Element Pack',
                    'description' => 'Create eye-catching website quickly and easily with 355+ modern Elementor slider widgets.',
                    'logo' => 'https://ps.w.org/bdthemes-element-pack-lite/assets/icon-256x256.gif',
                    'rating' => 4.7,
                    'num_ratings' => 500,
                    'active_installs' => '100,000+'
                ]
            ],
            // 'bdthemes-prime-slider-lite/bdthemes-prime-slider.php' => [
            //     'recommended' => true,
            //     'fallback' => [
            //         'name' => 'Prime Slider',
            //         'description' => 'Create eye-catching sliders for your website quickly and easily with 55+ modern Elementor slider widgets.',
            //         'logo' => 'https://ps.w.org/bdthemes-prime-slider-lite/assets/icon-256x256.png',
            //         'rating' => 4.7,
            //         'num_ratings' => 500,
            //         'active_installs' => '100,000+'
            //     ]
            // ],
            'ultimate-post-kit' => [
                'recommended' => true,
                'fallback' => [
                    'name' => 'Ultimate Post Kit',
                    'description' => 'Design beautiful post layouts with simple, ready-made blocks.',
                    'logo' => 'https://ps.w.org/ultimate-post-kit/assets/icon-256x256.png',
                    'rating' => 4.8,
                    'num_ratings' => 1000,
                    'active_installs' => '50,000+'
                ]
            ],
            'live-copy-paste' => [
                'recommended' => false,
                'fallback' => [
                    'name' => 'Live Copy Paste',
                    'description' => 'Copy and paste website elements between WordPress sites instantly.',
                    'logo' => 'https://ps.w.org/live-copy-paste/assets/icon-256x256.png',
                    'rating' => 4.9,
                    'num_ratings' => 200,
                    'active_installs' => '10,000+'
                ]
            ],
            'ultimate-store-kit' => [
                'recommended' => true,
                'fallback' => [
                    'name' => 'Ultimate Store Kit',
                    'description' => 'Improve your online store with tools to display products better.',
                    'logo' => 'https://ps.w.org/ultimate-store-kit/assets/icon-256x256.png',
                    'rating' => 4.6,
                    'num_ratings' => 300,
                    'active_installs' => '20,000+'
                ]
            ],
            // 'pixel-gallery' => [
            //     'recommended' => false,
            //     'fallback' => [
            //         'name' => 'Pixel Gallery',
            //         'description' => 'Show off your photos in a stylish, responsive gallery.',
            //         'logo' => 'https://ps.w.org/pixel-gallery/assets/icon-256x256.gif',
            //         'rating' => 4.5,
            //         'num_ratings' => 150,
            //         'active_installs' => '5,000+'
            //     ]
            // ],
            // 'zoloblocks' => [
            //     'recommended' => false,
            //     'fallback' => [
            //         'name' => 'ZoloBlocks',
            //         'description' => 'Build amazing WordPress pages with helpful and flexible Gutenberg blocks.',
            //         'logo' => 'https://ps.w.org/zoloblocks/assets/icon-256x256.gif',
            //         'rating' => 4.3,
            //         'num_ratings' => 100,
            //         'active_installs' => '3,000+'
            //     ]
            // ],
            // 'spin-wheel' => [
            //     'recommended' => false,
            //     'fallback' => [
            //         'name' => 'Spin Wheel',
            //         'description' => 'Add a fun, interactive spin wheel to offer instant coupons, boost engagement, and grow your email list.',
            //         'logo' => 'https://ps.w.org/spin-wheel/assets/icon-256x256.gif',
            //         'rating' => 4.2,
            //         'num_ratings' => 50,
            //         'active_installs' => '1,000+'
            //     ]
            // ],
            // 'ai-image' => [
            //     'recommended' => false,
            //     'fallback' => [
            //         'name' => 'Instant Image Generator',
            //         'description' => 'Instant Image Generator (One Click Image Uploads from Pixabay, Pexels and OpenAI).',
            //         'logo' => 'https://ps.w.org/ai-image/assets/icon-256x256.gif',
            //         'rating' => 4.0,
            //         'num_ratings' => 25,
            //         'active_installs' => '500+'
            //     ]
            // ],
            // 'dark-reader' => [
            //     'recommended' => false,
            //     'fallback' => [
            //         'name' => 'Dark Reader',
            //         'description' => 'Add beautiful dark mode to your WordPress site with customizable settings.',
            //         'logo' => 'https://ps.w.org/dark-reader/assets/icon-256x256.gif',
            //         'rating' => 4.1,
            //         'num_ratings' => 30,
            //         'active_installs' => '800+'
            //     ]
            // ],
            // 'ar-viewer' => [
            //     'recommended' => false,
            //     'fallback' => [
            //         'name' => 'AR Viewer',
            //         'description' => 'Augmented Reality Viewer – 3D Model Viewer.',
            //         'logo' => 'https://ps.w.org/ar-viewer/assets/icon-256x256.gif',
            //         'rating' => 3.9,
            //         'num_ratings' => 15,
            //         'active_installs' => '200+'
            //     ]
            // ]
        ];
    }

    /**
     * Build plugin data array with API data and fallbacks
     *
     * @param array $plugin_slugs Array of plugin slugs to include
     * @return array Complete plugin data array
     */
    public static function build_plugin_data($plugin_slugs = []) {
        $predefined = self::get_predefined_plugins();
        
        // Use new non-blocking approach - get cached data only from Remote_Data_Handler
        $fetched_data = [];
        if (function_exists('ps_get_remote_plugins')) {
            $fetched_data = ps_get_remote_plugins();
        }
        
        $plugins = [];

        foreach ($plugin_slugs as $slug) {
            $config = $predefined[$slug] ?? ['recommended' => false, 'fallback' => []];
            
            // Try to get data from remote cache first
            $api_slug = (strpos($slug, '/') !== false) ? dirname($slug) : $slug;
            $api_data = $fetched_data[$api_slug] ?? null;

            // Ensure api_data is a valid array with required fields
            if ($api_data && self::validate_plugin_data($api_data)) {
                // Determine the correct plugin slug format
                // If slug already contains .php, use it as-is, otherwise append default format
                $plugin_slug = (strpos($slug, '.php') !== false) ? $slug : $slug . '/' . $slug . '.php';
                
                // Use API data with fallbacks and proper null checking
                $plugins[] = [
                    'logo' => $api_data['logo'] ?? ($config['fallback']['logo'] ?? ''),
                    'rating' => $api_data['rating'] ?? 0,
                    'rating_percentage' => $api_data['rating_percentage'] ?? 0,
                    'num_ratings' => $api_data['num_ratings'] ?? 0,
                    'name' => $api_data['name'] ?? ($config['fallback']['name'] ?? $slug),
                    'slug' => $plugin_slug,
                    'description' => $api_data['description'] ?? ($config['fallback']['description'] ?? ''),
                    'active_installs' => $api_data['active_installs'] ?? ($config['fallback']['active_installs'] ?? '0'),
                    'active_installs_count' => $api_data['active_installs_count'] ?? 0,
                    'downloaded' => $api_data['downloaded'] ?? 0,
                    'downloaded_formatted' => $api_data['downloaded_formatted'] ?? '',
                    'recommended' => $config['recommended'],
                    'version' => $api_data['version'] ?? '',
                    'tested' => $api_data['tested'] ?? '',
                    'last_updated' => $api_data['last_updated'] ?? '',
                    'homepage' => $api_data['homepage'] ?? ''
                ];
            } else {
                // Determine the correct plugin slug format
                // If slug already contains .php, use it as-is, otherwise append default format
                $plugin_slug = (strpos($slug, '.php') !== false) ? $slug : $slug . '/' . $slug . '.php';
                
                // Use fallback data with proper null checking
                $fallback = $config['fallback'] ?? [];
                $plugins[] = [
                    'logo' => $fallback['logo'] ?? '',
                    'rating' => $fallback['rating'] ?? 0,
                    'rating_percentage' => 0,
                    'num_ratings' => $fallback['num_ratings'] ?? 0,
                    'name' => $fallback['name'] ?? $slug,
                    'slug' => $plugin_slug,
                    'description' => $fallback['description'] ?? '',
                    'active_installs' => $fallback['active_installs'] ?? '0',
                    'active_installs_count' => 0,
                    'downloaded' => 0,
                    'downloaded_formatted' => '',
                    'recommended' => $config['recommended'],
                    'version' => '',
                    'tested' => '',
                    'last_updated' => '',
                    'homepage' => ''
                ];
            }
        }

        return $plugins;
    }

    /**
     * Get recommended plugins only
     *
     * @return array Recommended plugins data
     */
    public static function get_recommended_plugins() {
        $predefined = self::get_predefined_plugins();
        $recommended_slugs = array_keys(array_filter($predefined, function($config) {
            return $config['recommended'];
        }));

        return self::build_plugin_data($recommended_slugs);
    }

    /**
     * Get all available plugins
     *
     * @return array All plugins data
     */
    public static function get_all_plugins() {
        $predefined = self::get_predefined_plugins();
        $all_slugs = array_keys($predefined);

        return self::build_plugin_data($all_slugs);
    }

    /**
     * Add a new plugin to the predefined list
     *
     * @param string $slug Plugin slug
     * @param array $config Plugin configuration
     */
    public static function add_plugin($slug, $config) {
        // This would typically be stored in a database or options table
        // For now, it's just a helper method
        $predefined = self::get_predefined_plugins();
        $predefined[$slug] = $config;
        
        // In a real implementation, you'd save this to options
        // update_option('ps_predefined_plugins', $predefined);
    }

    /**
     * Check if a plugin is installed and active
     *
     * @param string $plugin_file Plugin file path
     * @return bool True if plugin is active
     */
    public static function is_plugin_active($plugin_file) {
        return is_plugin_active($plugin_file);
    }

    /**
     * Get plugin installation status
     *
     * @param string $plugin_file Plugin file path
     * @return string Status: 'active', 'installed', 'not_installed'
     */
    public static function get_plugin_status($plugin_file) {
        if (self::is_plugin_active($plugin_file)) {
            return 'active';
        }

        $installed_plugins = get_plugins();
        if (isset($installed_plugins[$plugin_file])) {
            return 'installed';
        }

        return 'not_installed';
    }

    /**
     * Validate plugin data structure
     *
     * @param array $plugin_data Plugin data to validate
     * @return bool True if valid
     */
    public static function validate_plugin_data($plugin_data) {
        if (!is_array($plugin_data)) {
            return false;
        }

        $required_fields = ['name', 'slug', 'description', 'active_installs', 'rating', 'num_ratings'];
        
        foreach ($required_fields as $field) {
            if (!array_key_exists($field, $plugin_data)) {
                return false;
            }
        }

        return true;
    }
}