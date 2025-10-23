<?php
/**
 * WordPress Plugin API Data Fetcher
 * 
 * Fetches plugin data from WordPress.org Plugin API with caching
 */

namespace PrimeSlider\SetupWizard;

if (!defined('ABSPATH')) {
    exit;
}

class Plugin_Api_Fetcher {

    /**
     * Cache duration in seconds (7 days)
     */
    const CACHE_DURATION = 7 * DAY_IN_SECONDS;

    /**
     * WordPress.org Plugin API base URL
     */
    const API_BASE_URL = 'https://api.wordpress.org/plugins/info/1.2/';

    /**
     * Get plugin data from WordPress.org API with caching
     *
     * @param string $plugin_slug Plugin slug (e.g., 'bdthemes-element-pack-lite')
     * @return array|false Plugin data or false on failure
     */
    public static function get_plugin_data($plugin_slug) {
        // Check cache first
        $cached_data = self::get_cached_data($plugin_slug);
        if ($cached_data !== false) {
            return $cached_data;
        }

        // Fetch fresh data from API
        $plugin_data = self::fetch_from_api($plugin_slug);
        
        if ($plugin_data !== false) {
            // Cache the data
            self::cache_data($plugin_slug, $plugin_data);
            return $plugin_data;
        }

        return false;
    }

    /**
     * Fetch plugin data from WordPress.org API
     *
     * @param string $plugin_slug Plugin slug
     * @return array|false Plugin data or false on failure
     */
    private static function fetch_from_api($plugin_slug) {
        $api_url = add_query_arg([
            'action' => 'plugin_information',
            'request' => [
                'slug' => $plugin_slug,
                'fields' => [
                    'icons' => true,
                    'short_description' => true,
                    'active_installs' => true,
                    'rating' => true,
                    'num_ratings' => true,
                    'downloaded' => true,
                    'last_updated' => true,
                    'homepage' => true,
                    'sections' => false,
                    'compatibility' => false,
                    'tested' => true,
                    'requires' => true,
                    'requires_php' => true,
                    'donate_link' => false,
                    'contributors' => false,
                    'tags' => false,
                    'banners' => false,
                    'reviews' => false,
                    'versions' => false,
                    'installation' => false,
                    'faq' => false,
                    'changelog' => false,
                    'screenshots' => false
                ]
            ]
        ], self::API_BASE_URL);

        $response = wp_remote_get($api_url, [
            'timeout' => 30,
            'user-agent' => 'Prime Slider Setup Wizard'
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data) || !is_array($data)) {
            return false;
        }

        // Ensure we have a valid plugin data structure
        $formatted_data = self::format_plugin_data($data);
        
        // Double-check that we have essential fields
        if (empty($formatted_data['name']) && empty($formatted_data['slug'])) {
            return false;
        }

        return $formatted_data;
    }

    /**
     * Format plugin data for our use
     *
     * @param array $raw_data Raw API data
     * @return array Formatted plugin data
     */
    private static function format_plugin_data($raw_data) {
        // Get the best available icon with validation
        $icon_url = self::get_valid_plugin_icon($raw_data['icons'] ?? []);

        // Format active installs with null safety and real data
        $active_installs_raw = $raw_data['active_installs'] ?? 0;
        $active_installs = self::format_active_installs($active_installs_raw);
        $active_installs_count = self::get_numeric_active_installs($active_installs_raw);

        // Calculate rating percentage with null safety and real data
        $rating_percentage = 0;
        $rating_raw = $raw_data['rating'] ?? 0;
        $num_ratings_raw = $raw_data['num_ratings'] ?? 0;
        
        if (!empty($rating_raw) && !empty($num_ratings_raw)) {
            $rating_percentage = ($rating_raw / 100) * 5; // Convert to 5-star scale
        }

        // Get downloaded count for additional metrics
        $downloaded_count = $raw_data['downloaded'] ?? 0;

        return [
            'name' => $raw_data['name'] ?? '',
            'slug' => $raw_data['slug'] ?? '',
            'logo' => $icon_url,
            'description' => $raw_data['short_description'] ?? '',
            'active_installs' => $active_installs,
            'active_installs_count' => $active_installs_count,
            'rating' => round($rating_percentage, 1),
            'rating_percentage' => $rating_raw,
            'num_ratings' => $num_ratings_raw,
            'downloaded' => $downloaded_count,
            'downloaded_formatted' => self::format_downloaded_count($downloaded_count),
            'last_updated' => $raw_data['last_updated'] ?? '',
            'homepage' => $raw_data['homepage'] ?? '',
            'version' => $raw_data['version'] ?? '',
            'tested' => $raw_data['tested'] ?? '',
            'requires' => $raw_data['requires'] ?? '',
            'requires_php' => $raw_data['requires_php'] ?? '',
            'fetched_at' => current_time('timestamp')
        ];
    }

    /**
     * Get valid plugin icon with format validation
     *
     * @param array $icons Array of icon URLs
     * @return string Valid icon URL or empty string
     */
    private static function get_valid_plugin_icon($icons) {
        $valid_extensions = ['gif', 'png', 'jpg', 'jpeg', 'svg'];
        $icon_sizes = ['256', '128', 'default'];
        
        foreach ($icon_sizes as $size) {
            if (!empty($icons[$size])) {
                $icon_url = $icons[$size];
                
                // Check if URL is valid and has correct extension
                if (self::is_valid_image_url($icon_url, $valid_extensions)) {
                    return $icon_url;
                }
            }
        }
        
        return '';
    }

    /**
     * Validate image URL and extension
     *
     * @param string $url Image URL
     * @param array $valid_extensions Allowed extensions
     * @return bool True if valid
     */
    private static function is_valid_image_url($url, $valid_extensions) {
        if (empty($url) || !is_string($url)) {
            return false;
        }
        
        // Check if URL is valid
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        // Get file extension
        $path_info = pathinfo(parse_url($url, PHP_URL_PATH));
        $extension = strtolower($path_info['extension'] ?? '');
        
        return in_array($extension, $valid_extensions);
    }

    /**
     * Format active installs number with null safety
     *
     * @param mixed $installs Number of active installs
     * @return string Formatted installs string
     */
    private static function format_active_installs($installs) {
        // Handle null, empty, or non-numeric values
        if (is_null($installs) || $installs === '' || !is_numeric($installs)) {
            return '0';
        }
        
        $installs = intval($installs);
        
        if ($installs >= 1000000) {
            return round($installs / 1000000, 1) . 'M+';
        } elseif ($installs >= 1000) {
            return round($installs / 1000, 1) . 'K+';
        } else {
            return number_format($installs);
        }
    }

    /**
     * Get numeric active installs count
     *
     * @param mixed $installs Number of active installs
     * @return int Numeric installs count
     */
    private static function get_numeric_active_installs($installs) {
        // Handle null, empty, or non-numeric values
        if (is_null($installs) || $installs === '' || !is_numeric($installs)) {
            return 0;
        }
        
        return intval($installs);
    }

    /**
     * Format downloaded count
     *
     * @param mixed $downloaded Number of downloads
     * @return string Formatted downloads string
     */
    private static function format_downloaded_count($downloaded) {
        // Handle null, empty, or non-numeric values
        if (is_null($downloaded) || $downloaded === '' || !is_numeric($downloaded)) {
            return '0';
        }
        
        $downloaded = intval($downloaded);
        
        if ($downloaded >= 1000000) {
            return round($downloaded / 1000000, 1) . 'M+';
        } elseif ($downloaded >= 1000) {
            return round($downloaded / 1000, 1) . 'K+';
        } else {
            return number_format($downloaded);
        }
    }

    /**
     * Get cached plugin data
     *
     * @param string $plugin_slug Plugin slug
     * @return array|false Cached data or false if not found/expired
     */
    private static function get_cached_data($plugin_slug) {
        $cache_key = 'ps_plugin_data_' . md5($plugin_slug);
        $cached_data = get_transient($cache_key);

        if ($cached_data === false) {
            return false;
        }

        // Check if cache is expired
        if (isset($cached_data['fetched_at']) && 
            (current_time('timestamp') - $cached_data['fetched_at']) > self::CACHE_DURATION) {
            delete_transient($cache_key);
            return false;
        }

        return $cached_data;
    }

    /**
     * Cache plugin data
     *
     * @param string $plugin_slug Plugin slug
     * @param array $data Plugin data to cache
     */
    private static function cache_data($plugin_slug, $data) {
        $cache_key = 'ps_plugin_data_' . md5($plugin_slug);
        set_transient($cache_key, $data, self::CACHE_DURATION);
    }

    /**
     * Clear cache for a specific plugin
     *
     * @param string $plugin_slug Plugin slug
     */
    public static function clear_cache($plugin_slug) {
        $cache_key = 'ps_plugin_data_' . md5($plugin_slug);
        delete_transient($cache_key);
    }

    /**
     * Clear all plugin data cache
     */
    public static function clear_all_cache() {
        global $wpdb;
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_ps_plugin_data_%'
            )
        );
        
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_ps_plugin_data_%'
            )
        );
    }

    /**
     * Get multiple plugins data at once
     *
     * @param array $plugin_slugs Array of plugin slugs
     * @return array Array of plugin data
     */
    public static function get_multiple_plugins_data($plugin_slugs) {
        $results = [];
        
        foreach ($plugin_slugs as $slug) {
            $data = self::get_plugin_data($slug);
            if ($data !== false) {
                $results[$slug] = $data;
            }
        }
        
        return $results;
    }

    /**
     * Check if plugin exists on WordPress.org
     *
     * @param string $plugin_slug Plugin slug
     * @return bool True if plugin exists
     */
    public static function plugin_exists($plugin_slug) {
        $data = self::get_plugin_data($plugin_slug);
        return $data !== false && !empty($data['name']);
    }

    /**
     * Get plugin slug from plugin file path
     *
     * @param string $plugin_file Plugin file path (e.g., 'bdthemes-element-pack-lite/bdthemes-element-pack-lite.php')
     * @return string Plugin slug
     */
    public static function get_plugin_slug_from_file($plugin_file) {
        return dirname($plugin_file);
    }
}
