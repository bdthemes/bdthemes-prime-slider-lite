<?php
/**
 * Element Pack Remote Data Handler
 * 
 * Handles remote API data loading with proper caching and background processing
 * to prevent blocking admin pages.
 */

namespace PrimeSlider\SetupWizard;

if (!defined('ABSPATH')) {
    exit;
}



class Remote_Data_Handler {

    /**
     * Cache duration in seconds (7 days)
     */
    const CACHE_DURATION = 7 * DAY_IN_SECONDS;

    /**
     * Transient key for remote plugins data
     */
    const CACHE_KEY = 'bdt_remote_plugins_data';

    /**
     * Cron hook name for background fetch
     */
    const CRON_HOOK = 'bdt_fetch_remote_plugins_cron';

    /**
     * Initialize the remote data handler
     */
    public static function init() {
        add_action('init', [__CLASS__, 'schedule_cron']);
        add_action(self::CRON_HOOK, [__CLASS__, 'cron_fetch_plugins']);
        add_action('wp_ajax_ps_get_plugins', [__CLASS__, 'ajax_get_plugins']);
        add_action('wp_ajax_nopriv_ps_get_plugins', [__CLASS__, 'ajax_get_plugins']);
    }

    /**
     * WP-Cron callback for fetching plugins
     */
    public static function cron_fetch_plugins() {
        self::fetch_remote_plugins_now();
    }

    /**
     * Check if we're on the Element Pack options page
     * 
     * @return bool True if on Element Pack options page
     */
    public static function is_prime_slider_page() {
        if (!is_admin()) {
            return false;
        }

        // Check if this is an AJAX request for our plugins
        if (wp_doing_ajax() && isset($_REQUEST['action'])) {
            $action = sanitize_text_field($_REQUEST['action']);
            if (in_array($action, ['ps_get_plugins'])) {
                return true;
            }
        }

        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        return $page === 'element_pack_options';
    }

    /**
     * Get remote plugins data from cache
     * 
     * @return array Cached plugins data or empty array if not available
     */
    public static function get_remote_plugins() {
        $cached_data = get_transient(self::CACHE_KEY);
        
        if ($cached_data !== false) {
            return $cached_data;
        }

        // If no cache exists, schedule background fetch and return empty array
        self::schedule_remote_fetch();
        
        return [];
    }

    /**
     * Schedule a background fetch via WP-Cron
     * 
     * @return bool True if successfully scheduled
     */
    public static function schedule_remote_fetch() {
        // Schedule to run immediately if not already scheduled
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_single_event(time(), self::CRON_HOOK);
            return true;
        }
        
        return false;
    }

    /**
     * Fetch remote plugins data immediately (for background processing only)
     * 
     * @return array|false Plugins data or false on failure
     */
    public static function fetch_remote_plugins_now() {
        // Define plugin slugs to fetch (data includes all; Prime Slider is skipped only when printing)
        $plugin_slugs = [
            'bdthemes-element-pack-lite',
            'bdthemes-prime-slider-lite',
            'ultimate-post-kit', 
            'ultimate-store-kit',
            'zoloblocks',
            'pixel-gallery',
            'live-copy-paste',
            'spin-wheel',
            'ai-image',
            'dark-reader',
            'ar-viewer',
            'smart-admin-assistant',
            'website-accessibility',
        ];

        $results = [];
        $errors = [];

        foreach ($plugin_slugs as $slug) {
            // Direct API fetch - no external dependencies
            $data = self::fetch_plugin_from_api($slug);
            if ($data !== false) {
                $results[$slug] = $data;
            } else {
                $errors[] = $slug;
            }
        }

        // Cache the results for 7 days
        set_transient(self::CACHE_KEY, $results, self::CACHE_DURATION);
        
        return $results;
    }

    /**
     * AJAX handler for getting plugins data
     */
    public static function ajax_get_plugins() {
        // Verify nonce for security
        if (!check_ajax_referer('ps_get_plugins_nonce', 'nonce', false)) {
            wp_die(__('Security check failed.', 'bdthemes-element-pack'));
        }

        // Get cached data
        $plugins_data = self::get_remote_plugins();
        
        // If cache is empty, fetch immediately for better UX
        if (empty($plugins_data)) {
            // Try to fetch data immediately (this is an AJAX request, so it's async already)
            $plugins_data = self::fetch_remote_plugins_now();
            
            // If still empty after fetch, schedule background cron for retry
            if (empty($plugins_data)) {
                self::schedule_remote_fetch();
                
                // Return empty response with flag indicating data is loading
                wp_send_json_success([
                    'plugins' => [],
                    'loading' => true,
                    'message' => __('Loading plugin data...', 'bdthemes-element-pack')
                ]);
            }
        }

        // Get recommended flags from Plugin_Integration_Helper
        $predefined = [];
        $helper_file = __DIR__ . '/class-plugin-integration-helper.php';
        if (file_exists($helper_file)) {
            require_once $helper_file;
            $predefined = \PrimeSlider\SetupWizard\Plugin_Integration_Helper::get_predefined_plugins();
        }

        // Format the response for frontend use
        $formatted_plugins = [];
        foreach ($plugins_data as $slug => $data) {
            // Check plugin status
            $plugin_status = self::get_plugin_status_by_slug($slug);
            $plugin_file = self::get_plugin_file_by_slug($slug);
            
            // Format the last updated date
            $last_updated_formatted = '';
            if (!empty($data['last_updated'])) {
                $last_updated_formatted = self::format_last_updated($data['last_updated']);
            }
            
            $formatted_plugins[] = [
                'name' => $data['name'] ?? '',
                'slug' => $data['slug'] ?? '',
                'description' => $data['description'] ?? '',
                'logo' => $data['logo'] ?? '',
                'rating' => $data['rating'] ?? 0,
                'rating_percentage' => $data['rating_percentage'] ?? 0,
                'num_ratings' => $data['num_ratings'] ?? 0,
                'active_installs' => $data['active_installs'] ?? '0',
                'active_installs_count' => $data['active_installs_count'] ?? 0,
                'downloaded' => $data['downloaded'] ?? 0,
                'downloaded_formatted' => $data['downloaded_formatted'] ?? '',
                'version' => $data['version'] ?? '',
                'tested' => $data['tested'] ?? '',
                'last_updated' => $data['last_updated'] ?? '',
                'last_updated_formatted' => $last_updated_formatted,
                'homepage' => $data['homepage'] ?? '',
                'status' => $plugin_status,
                'plugin_file' => $plugin_file,
                'activate_nonce' => $plugin_file ? wp_create_nonce('activate-plugin_' . $plugin_file) : '',
                'recommended' => !empty($predefined[$slug]['recommended'])
            ];
        }

        wp_send_json_success([
            'plugins' => $formatted_plugins,
            'loading' => false,
            'message' => __('Plugin data loaded successfully.', 'bdthemes-element-pack')
        ]);
    }

    /**
     * Schedule the cron job on init
     */
    public static function schedule_cron() {
        // Make sure the cron hook is registered
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            // Don't schedule immediately, only when needed
        }
    }

    /**
     * Get plugin status by slug
     * 
     * @param string $slug Plugin slug
     * @return string Plugin status: 'active', 'installed', 'not_installed'
     */
    private static function get_plugin_status_by_slug($slug) {
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        // Get all installed plugins
        $installed_plugins = get_plugins();
        
        // Find the plugin file for this slug
        $plugin_file = self::get_plugin_file_by_slug($slug);
        
        if ($plugin_file && is_plugin_active($plugin_file)) {
            return 'active';
        } elseif ($plugin_file && isset($installed_plugins[$plugin_file])) {
            return 'installed';
        }
        
        return 'not_installed';
    }

    /**
     * Get plugin file path by slug
     * 
     * @param string $slug Plugin slug
     * @return string|null Plugin file path or null if not found
     */
    private static function get_plugin_file_by_slug($slug) {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $installed_plugins = get_plugins();
        
        // Look for the plugin file that matches the slug
        foreach ($installed_plugins as $plugin_file => $plugin_data) {
            $plugin_slug = dirname($plugin_file);
            if ($plugin_slug === $slug) {
                return $plugin_file;
            }
        }
        
        return null;
    }

    /**
     * Format date in human-readable format
     * 
     * @param string $date_string Date string to format
     * @return string Formatted date string
     */
    private static function format_last_updated($date_string) {
        if (empty($date_string)) {
            return __('Unknown', 'bdthemes-element-pack');
        }
        
        $date = strtotime($date_string);
        if (!$date) {
            return __('Unknown', 'bdthemes-element-pack');
        }
        
        $diff = current_time('timestamp') - $date;
        
        if ($diff < 60) {
            return __('Just now', 'bdthemes-element-pack');
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return sprintf(_n('%d minute ago', '%d minutes ago', $minutes, 'bdthemes-element-pack'), $minutes);
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return sprintf(_n('%d hour ago', '%d hours ago', $hours, 'bdthemes-element-pack'), $hours);
        } elseif ($diff < 2592000) { // 30 days
            $days = floor($diff / 86400);
            return sprintf(_n('%d day ago', '%d days ago', $days, 'bdthemes-element-pack'), $days);
        } elseif ($diff < 31536000) { // 1 year
            $months = floor($diff / 2592000);
            return sprintf(_n('%d month ago', '%d months ago', $months, 'bdthemes-element-pack'), $months);
        } else {
            $years = floor($diff / 31536000);
            return sprintf(_n('%d year ago', '%d years ago', $years, 'bdthemes-element-pack'), $years);
        }
    }

    /**
     * Fetch plugin data from WordPress.org API
     *
     * @param string $plugin_slug Plugin slug
     * @return array|false Plugin data or false on failure
     */
    private static function fetch_plugin_from_api($plugin_slug) {
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
                    'tested' => true,
                    'requires' => true,
                    'requires_php' => true,
                    'sections' => false,
                    'compatibility' => false,
                    'banners' => false,
                    'contributors' => false,
                    'tags' => false,
                    'reviews' => false,
                    'versions' => false,
                    'installation' => false,
                    'faq' => false,
                    'changelog' => false,
                    'screenshots' => false,
                    'donate_link' => false,
                ]
            ]
        ], 'https://api.wordpress.org/plugins/info/1.2/');

        // Security: Use wp_safe_remote_get instead of wp_remote_get
        $response = wp_safe_remote_get($api_url, [
            'timeout' => 30,
            'user-agent' => 'Element Pack Setup Wizard'
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data) || !is_array($data)) {
            return false;
        }

        $formatted_data = self::format_plugin_data($data);
        
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
}

// Initialize the handler
add_action('init', function() {
    Remote_Data_Handler::init();
});

// Global functions for backward compatibility and ease of use
if (!function_exists('ps_is_prime_slider_page')) {
    function ps_is_prime_slider_page() {
        return \PrimeSlider\SetupWizard\Remote_Data_Handler::is_prime_slider_page();
    }
}

if (!function_exists('ps_get_remote_plugins')) {
    function ps_get_remote_plugins() {
        return \PrimeSlider\SetupWizard\Remote_Data_Handler::get_remote_plugins();
    }
}

if (!function_exists('ps_schedule_remote_fetch')) {
    function ps_schedule_remote_fetch() {
        return \PrimeSlider\SetupWizard\Remote_Data_Handler::schedule_remote_fetch();
    }
}

if (!function_exists('ps_fetch_remote_plugins_now')) {
    function ps_fetch_remote_plugins_now() {
        return \PrimeSlider\SetupWizard\Remote_Data_Handler::fetch_remote_plugins_now();
    }
}
