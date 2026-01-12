<?php
/**
 * Plugin Data Cache Manager
 * 
 * Manages automatic cache refresh and provides admin tools
 */

namespace PrimeSlider\SetupWizard;

if (!defined('ABSPATH')) {
    exit;
}

class Plugin_Cache_Manager {

    /**
     * Initialize cache management
     */
    public static function init() {
        // Schedule daily cache check
        add_action('wp', [__CLASS__, 'schedule_cache_refresh']);
        add_action('ps_refresh_plugin_cache', [__CLASS__, 'refresh_expired_cache']);
    }

    /**
     * Schedule cache refresh
     */
    public static function schedule_cache_refresh() {
        if (!wp_next_scheduled('ps_refresh_plugin_cache')) {
            wp_schedule_event(time(), 'daily', 'ps_refresh_plugin_cache');
        }
    }

    /**
     * Refresh expired cache entries
     */
    public static function refresh_expired_cache() {
        global $wpdb;
        
        // Get all cached plugin data
        $cache_keys = $wpdb->get_col(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_ps_plugin_data_%'"
        );
        
        foreach ($cache_keys as $cache_key) {
            $transient_key = str_replace('_transient_', '', $cache_key);
            $cached_data = get_transient($transient_key);
            
            if ($cached_data && isset($cached_data['fetched_at'])) {
                $age = current_time('timestamp') - $cached_data['fetched_at'];
                
                // Refresh if older than 6 days (refresh before expiration)
                if ($age > (6 * DAY_IN_SECONDS)) {
                    $plugin_slug = self::extract_slug_from_cache_key($transient_key);
                    if ($plugin_slug) {
                        Plugin_Api_Fetcher::clear_cache($plugin_slug);
                        // Fresh data will be fetched on next request
                    }
                }
            }
        }
    }

    /**
     * Extract plugin slug from cache key
     *
     * @param string $cache_key Cache key
     * @return string|false Plugin slug or false
     */
    private static function extract_slug_from_cache_key($cache_key) {
        if (preg_match('/ps_plugin_data_(.+)/', $cache_key, $matches)) {
            return $matches[1];
        }
        return false;
    }

}

// Initialize cache manager
Plugin_Cache_Manager::init();
