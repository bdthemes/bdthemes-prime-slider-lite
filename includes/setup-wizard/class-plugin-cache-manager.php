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
        
        // Add admin tools
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('wp_ajax_ps_clear_plugin_cache', [__CLASS__, 'ajax_clear_cache']);
        add_action('wp_ajax_ps_refresh_plugin_data', [__CLASS__, 'ajax_refresh_data']);
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

    /**
     * Add admin menu for cache management
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            __('Plugin Cache Manager', 'bdthemes-prime-slider'),
            __('Plugin Cache', 'bdthemes-prime-slider'),
            'manage_options',
            'ps-plugin-cache',
            [__CLASS__, 'admin_page']
        );
    }

    /**
     * Admin page for cache management
     */
    public static function admin_page() {
        $cache_stats = self::get_cache_stats();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Plugin Cache Manager', 'bdthemes-prime-slider'); ?></h1>
            
            <div class="card">
                <h2><?php esc_html_e('Cache Statistics', 'bdthemes-prime-slider'); ?></h2>
                <p><?php printf(esc_html__('Total cached plugins: %d', 'bdthemes-prime-slider'), $cache_stats['total_cached']); ?></p>
                <p><?php printf(esc_html__('Cache size: %s', 'bdthemes-prime-slider'), size_format($cache_stats['cache_size'])); ?></p>
                <p><?php printf(esc_html__('Oldest cache entry: %s', 'bdthemes-prime-slider'), $cache_stats['oldest_entry']); ?></p>
            </div>

            <div class="card">
                <h2><?php esc_html_e('Cache Actions', 'bdthemes-prime-slider'); ?></h2>
                <p>
                    <button type="button" class="button" id="refresh-cache">
                        <?php esc_html_e('Refresh All Cache', 'bdthemes-prime-slider'); ?>
                    </button>
                    <button type="button" class="button" id="clear-cache">
                        <?php esc_html_e('Clear All Cache', 'bdthemes-prime-slider'); ?>
                    </button>
                </p>
                <div id="cache-message"></div>
            </div>

            <div class="card">
                <h2><?php esc_html_e('Cached Plugins', 'bdthemes-prime-slider'); ?></h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Plugin', 'bdthemes-prime-slider'); ?></th>
                            <th><?php esc_html_e('Cached At', 'bdthemes-prime-slider'); ?></th>
                            <th><?php esc_html_e('Age', 'bdthemes-prime-slider'); ?></th>
                            <th><?php esc_html_e('Actions', 'bdthemes-prime-slider'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cache_stats['plugins'] as $plugin): ?>
                        <tr>
                            <td><?php echo esc_html($plugin['name']); ?></td>
                            <td><?php echo esc_html($plugin['cached_at']); ?></td>
                            <td><?php echo esc_html($plugin['age']); ?></td>
                            <td>
                                <button type="button" class="button button-small refresh-single" data-slug="<?php echo esc_attr($plugin['slug']); ?>">
                                    <?php esc_html_e('Refresh', 'bdthemes-prime-slider'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#refresh-cache').on('click', function() {
                refreshCache();
            });
            
            $('#clear-cache').on('click', function() {
                clearCache();
            });
            
            $('.refresh-single').on('click', function() {
                var slug = $(this).data('slug');
                refreshSingle(slug);
            });
            
            function refreshCache() {
                $('#cache-message').html('<p><?php esc_html_e('Refreshing cache...', 'bdthemes-prime-slider'); ?></p>');
                
                $.post(ajaxurl, {
                    action: 'ps_refresh_plugin_data',
                    nonce: '<?php echo wp_create_nonce('ps_cache_nonce'); ?>'
                }, function(response) {
                    $('#cache-message').html('<p style="color: green;"><?php esc_html_e('Cache refreshed successfully!', 'bdthemes-prime-slider'); ?></p>');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                });
            }
            
            function clearCache() {
                if (confirm('<?php esc_html_e('Are you sure you want to clear all cache?', 'bdthemes-prime-slider'); ?>')) {
                    $('#cache-message').html('<p><?php esc_html_e('Clearing cache...', 'bdthemes-prime-slider'); ?></p>');
                    
                    $.post(ajaxurl, {
                        action: 'ps_clear_plugin_cache',
                        nonce: '<?php echo wp_create_nonce('ps_cache_nonce'); ?>'
                    }, function(response) {
                        $('#cache-message').html('<p style="color: green;"><?php esc_html_e('Cache cleared successfully!', 'bdthemes-prime-slider'); ?></p>');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    });
                }
            }
            
            function refreshSingle(slug) {
                $('#cache-message').html('<p><?php esc_html_e('Refreshing plugin data...', 'bdthemes-prime-slider'); ?></p>');
                
                $.post(ajaxurl, {
                    action: 'ps_refresh_plugin_data',
                    slug: slug,
                    nonce: '<?php echo wp_create_nonce('ps_cache_nonce'); ?>'
                }, function(response) {
                    $('#cache-message').html('<p style="color: green;"><?php esc_html_e('Plugin data refreshed!', 'bdthemes-prime-slider'); ?></p>');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                });
            }
        });
        </script>
        <?php
    }

    /**
     * Get cache statistics
     *
     * @return array Cache statistics
     */
    private static function get_cache_stats() {
        global $wpdb;
        
        $cache_keys = $wpdb->get_col(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_ps_plugin_data_%'"
        );
        
        $plugins = [];
        $total_size = 0;
        $oldest_timestamp = time();
        
        foreach ($cache_keys as $cache_key) {
            $transient_key = str_replace('_transient_', '', $cache_key);
            $cached_data = get_transient($transient_key);
            
            if ($cached_data && isset($cached_data['fetched_at'])) {
                $slug = self::extract_slug_from_cache_key($transient_key);
                $age = current_time('timestamp') - $cached_data['fetched_at'];
                
                $plugins[] = [
                    'slug' => $slug,
                    'name' => $cached_data['name'] ?? $slug,
                    'cached_at' => date('Y-m-d H:i:s', $cached_data['fetched_at']),
                    'age' => sprintf(
                        esc_html__( '%s ago', 'bdthemes-prime-slider' ),
                        human_time_diff( $cached_data['fetched_at'], current_time( 'timestamp' ) )
                    ),
                ];
                
                $total_size += strlen(serialize($cached_data));
                
                if ($cached_data['fetched_at'] < $oldest_timestamp) {
                    $oldest_timestamp = $cached_data['fetched_at'];
                }
            }
        }
        
        return [
            'total_cached' => count($plugins),
            'cache_size' => $total_size,
            'oldest_entry' => sprintf(
                esc_html__( '%s ago', 'bdthemes-prime-slider' ),
                human_time_diff( $oldest_timestamp, current_time( 'timestamp' ) )
            ),
            'plugins' => $plugins
        ];
    }

    /**
     * AJAX handler for clearing cache
     */
    public static function ajax_clear_cache() {
        check_ajax_referer('ps_cache_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        Plugin_Api_Fetcher::clear_all_cache();
        wp_send_json_success();
    }

    /**
     * AJAX handler for refreshing plugin data
     */
    public static function ajax_refresh_data() {
        check_ajax_referer('ps_cache_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $slug = sanitize_text_field($_POST['slug'] ?? '');
        
        if ($slug) {
            // Clear specific plugin cache
            Plugin_Api_Fetcher::clear_cache($slug);
            // Fresh data will be fetched on next request
        } else {
            // Refresh all expired cache
            self::refresh_expired_cache();
        }
        
        wp_send_json_success();
    }
}

// Initialize cache manager
Plugin_Cache_Manager::init();
