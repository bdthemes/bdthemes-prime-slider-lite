<?php
/**
 *  Prime Slider Others Plugin - Standalone Plugin Manager
 * 
 * This file provides the enhanced plugin installation and management system
 * for Prime Slider, separated from the main admin settings for better maintainability.
 * 
 * @version 1.0.0
 * @author BDThemes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Prime Slider Others Plugin Manager
 */
class PrimeSlider_Others_Plugin_Manager {

    /**
     * Constructor
     */
    public function __construct() {
        // Add AJAX handlers
        add_action('wp_ajax_ps_get_plugins', [$this, 'ajax_get_plugins']);
        add_action('wp_ajax_nopriv_ps_get_plugins', [$this, 'ajax_get_plugins']);
        add_action('wp_ajax_ps_install_plugin', [$this, 'install_plugin_ajax']);
    }

    /**
     * Render the others plugin interface
     */
    public function render_others_plugin() {
        // Include the required classes
        require_once BDTPS_CORE_INC_PATH . 'setup-wizard/class-plugin-integration-helper.php';
        require_once BDTPS_CORE_INC_PATH . 'setup-wizard/class-remote-data-handler.php';
        
        // Define plugin slugs for reference (data has all; Prime Slider is skipped only when printing)
        $plugin_slugs = array(
            'bdthemes-prime-slider-lite/bdthemes-prime-slider.php',
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
        );

        // Helper function for time formatting
        if (!function_exists('format_last_updated_ps')) {
            function format_last_updated_ps($date_string) {
                if (empty($date_string)) {
                    return __('Unknown', 'bdthemes-prime-slider-lite');
                }
                
                $date = strtotime($date_string);
                if (!$date) {
                    return __('Unknown', 'bdthemes-prime-slider-lite');
                }
                
                $diff = current_time('timestamp') - $date;
                
                if ($diff < 60) {
                    return __('Just now', 'bdthemes-prime-slider-lite');
                } elseif ($diff < 3600) {
                    $minutes = floor($diff / 60);
                    return sprintf(_n('%d minute ago', '%d minutes ago', $minutes, 'bdthemes-prime-slider-lite'), $minutes);
                } elseif ($diff < 86400) {
                    $hours = floor($diff / 3600);
                    return sprintf(_n('%d hour ago', '%d hours ago', $hours, 'bdthemes-prime-slider-lite'), $hours);
                } elseif ($diff < 2592000) { // 30 days
                    $days = floor($diff / 86400);
                    return sprintf(_n('%d day ago', '%d days ago', $days, 'bdthemes-prime-slider-lite'), $days);
                } elseif ($diff < 31536000) { // 1 year
                    $months = floor($diff / 2592000);
                    return sprintf(_n('%d month ago', '%d months ago', $months, 'bdthemes-prime-slider-lite'), $months);
                } else {
                    $years = floor($diff / 31536000);
                    return sprintf(_n('%d year ago', '%d years ago', $years, 'bdthemes-prime-slider-lite'), $years);
                }
            }
        }

        // Helper function for fallback URLs
        if (!function_exists('get_plugin_fallback_urls_ps')) {
            function get_plugin_fallback_urls_ps($plugin_slug) {
                // Handle different plugin slug formats
                if (strpos($plugin_slug, '/') !== false) {
                    // If it's a file path like 'plugin-name/plugin-name.php', extract directory
                    $plugin_slug_clean = dirname($plugin_slug);
                } else {
                    // If it's just the plugin directory name, use it directly
                    $plugin_slug_clean = $plugin_slug;
                }
                
                // Custom icon URLs for specific plugins that might not be on WordPress.org
                $custom_icons = [
                    'ar-viewer' => [
                        'https://ps.w.org/ar-viewer/assets/icon-256x256.gif',
                        'https://ps.w.org/ar-viewer/assets/icon-128x128.gif',
                    ],
                ];
                
                // Return custom icons if available, otherwise use default WordPress.org URLs
                if (isset($custom_icons[$plugin_slug_clean])) {
                    return $custom_icons[$plugin_slug_clean];
                }
                
                return [
                    "https://ps.w.org/{$plugin_slug_clean}/assets/icon-256x256.png",  // Then PNG
                    "https://ps.w.org/{$plugin_slug_clean}/assets/icon-128x128.png",  // Medium PNG
                    "https://ps.w.org/{$plugin_slug_clean}/assets/icon-256x256.gif",  // Try GIF first
                    "https://ps.w.org/{$plugin_slug_clean}/assets/icon-128x128.gif",  // Medium GIF
                ];
            }
        }
        ?>
        
        <div class="ps-dashboard-panel"
            bdt-scrollspy="target: > div > div > .bdt-card; cls: bdt-animation-slide-bottom-small; delay: 300">
            <div class="ps-dashboard-others-plugin" id="ps-others-plugin-container">
                
                <!-- Loading state -->
                <div class="ps-plugins-loading" id="ps-plugins-loading">
                    <div class="bdt-flex bdt-flex-center bdt-flex-middle bdt-text-center" style="min-height: 200px;">
                        <div>
                            <div class="bdt-spinner bdt-spinner-primary"></div>
                            <p class="bdt-margin-small-top"><?php esc_html_e('Loading plugin data...', 'bdthemes-prime-slider-lite'); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Error state (hidden by default) -->
                <div class="ps-plugins-error" id="ps-plugins-error" style="display: none;">
                    <div class="bdt-alert bdt-alert-warning" bdt-alert>
                        <a class="bdt-alert-close" bdt-close></a>
                        <p><?php esc_html_e('Unable to load plugin data. Please try again later.', 'bdthemes-prime-slider-lite'); ?></p>
                        <button class="bdt-button bdt-button-small bdt-margin-small-top" id="ps-retry-load-plugins">
                            <?php esc_html_e('Retry', 'bdthemes-prime-slider-lite'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Plugins container (populated by AJAX) -->
                <div class="ps-plugins-list" id="ps-plugins-list" style="display: none;">
                    <!-- Plugin cards will be inserted here by JavaScript -->
                </div>
            </div>
        </div>
        
        <style type="text/css">
        .ps-loading-spinner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        
        .ps-loading-dots {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .ps-loading-dot {
            width: 12px;
            height: 12px;
            background-color: #FC6A2A;
            border-radius: 50%;
            animation: ps-wave 1.4s ease-in-out infinite both;
        }
        
        .ps-loading-dot:nth-child(1) { animation-delay: -0.32s; }
        .ps-loading-dot:nth-child(2) { animation-delay: -0.16s; }
        .ps-loading-dot:nth-child(3) { animation-delay: 0; }
        
        @keyframes ps-wave {
            0%, 80%, 100% {
                transform: scale(0.8);
                opacity: 0.5;
            }
            40% {
                transform: scale(1.2);
                opacity: 1;
            }
        }
        
        #ps-plugins-list {
            position: relative;
            min-height: 200px;
        }

        #ps-plugins-list p {
            max-width: none;
            margin-top: 60px !important;
        }
        </style>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var $container = $('#ps-others-plugin-container');
            var $loading = $('#ps-plugins-loading');
            var $error = $('#ps-plugins-error');
            var $list = $('#ps-plugins-list');
            
            // Function to load plugins via AJAX
            function loadPlugins() {
                $loading.hide();
                $error.hide();
                showLoading();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ps_get_plugins',
                        nonce: '<?php echo wp_create_nonce("ps_get_plugins_nonce"); ?>'
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            if (response.data.loading) {
                                // Still loading, show message and retry after delay
                                showLoading();
                                setTimeout(loadPlugins, 3000); // Retry after 3 seconds
                            } else {
                                renderPlugins(response.data.plugins);
                            }
                        } else {
                            showError();
                        }
                    },
                    error: function() {
                        showError();
                    }
                });
            }
            
            // Function to render plugins
            function renderPlugins(plugins) {
                var html = '';
                
                if (plugins.length === 0) {
                    html = '<div class="bdt-text-center bdt-padding-large"><p><?php esc_html_e('No plugins available.', 'bdthemes-prime-slider-lite'); ?></p></div>';
                } else {
                    plugins.forEach(function(plugin) {
                        // Skip own plugin (Prime Slider) when printing only; data still includes it for other plugins
                        if (plugin.slug === 'bdthemes-prime-slider-lite') return;
                        var isActive = false; // We'll determine this via PHP in the actual implementation
                        var logoUrl = plugin.logo || '';
                        var pluginName = plugin.name || '';
                        var pluginSlug = plugin.slug || '';
                        
                        // Generate fallback logo URL if needed
                        if (!logoUrl) {
                            var actualSlug = pluginSlug.replace('.php', '').split('/')[0];
                            logoUrl = 'https://ps.w.org/' + actualSlug + '/assets/icon-256x256.png';
                        }
                        
                        html += '<div class="bdt-card bdt-card-body bdt-flex bdt-flex-middle bdt-flex-between">' +
                            '<div class="bdt-others-plugin-content">' +
                                '<div class="bdt-plugin-logo-wrap bdt-flex bdt-flex-middle">' +
                                    '<div class="bdt-plugin-logo-container">' +
                                        '<img src="' + logoUrl + '" alt="' + pluginName + '" class="bdt-plugin-logo" ' +
                                            'onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">' +
                                        '<div class="default-plugin-icon" style="display:none;">📦</div>' +
                                    '</div>' +
                                    '<div class="bdt-others-plugin-user-wrap bdt-flex bdt-flex-middle">' +
                                        '<h1 class="ps-feature-title">' + pluginName + '</h1>' +
                                    '</div>' +
                                '</div>' +
                                '<div class="bdt-others-plugin-content-text bdt-margin-top">';
                        
                        if (plugin.description) {
                            html += '<p>' + plugin.description + '</p>';
                        }
                        
                        // Active installs
                        html += '<span class="active-installs bdt-margin-small-top">' +
                            '<?php esc_html_e("Active Installs: ", "bdthemes-prime-slider-lite"); ?> ';
                        if (plugin.active_installs_count > 0) {
                            html += '<span class="installs-count">' + plugin.active_installs_count.toLocaleString() + '+</span>';
                        } else {
                            html += '<span class="installs-count">Fewer than 10</span>';
                        }
                        html += '</span>';
                        
                        // Rating
                        html += '<div class="bdt-others-plugin-rating bdt-margin-small-top bdt-flex bdt-flex-middle">' +
                            '<span class="bdt-others-plugin-rating-stars">';
                        
                        var rating = parseFloat(plugin.rating) || 0;
                        var fullStars = Math.floor(rating);
                        var hasHalfStar = (rating - fullStars) >= 0.5;
                        var emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
                        
                        for (var i = 0; i < fullStars; i++) {
                            html += '<i class="dashicons dashicons-star-filled"></i>';
                        }
                        if (hasHalfStar) {
                            html += '<i class="dashicons dashicons-star-half"></i>';
                        }
                        for (var i = 0; i < emptyStars; i++) {
                            html += '<i class="dashicons dashicons-star-empty"></i>';
                        }
                        
                        html += '</span>' +
                            '<span class="bdt-others-plugin-rating-text bdt-margin-small-left">' +
                                rating + ' <?php esc_html_e("out of 5 stars.", "bdthemes-prime-slider-lite"); ?>';
                        
                        if (plugin.num_ratings > 0) {
                            html += '<span class="rating-count">(' + plugin.num_ratings.toLocaleString() + ' <?php esc_html_e("ratings", "bdthemes-prime-slider-lite"); ?>)</span>';
                        }
                        
                        html += '</span></div>';
                        
                        // Downloads
                        if (plugin.downloaded_formatted) {
                            html += '<div class="bdt-others-plugin-downloads bdt-margin-small-top">' +
                                '<span><?php esc_html_e("Downloads: ", "bdthemes-prime-slider-lite"); ?>' + plugin.downloaded_formatted + '</span>' +
                                '</div>';
                        }
                        
                        // Last updated
                        if (plugin.last_updated_formatted) {
                            html += '<div class="bdt-others-plugin-updated bdt-margin-small-top">' +
                                '<span><?php esc_html_e("Last Updated: ", "bdthemes-prime-slider-lite"); ?>' + plugin.last_updated_formatted + '</span>' +
                                '</div>';
                        }
                        
                        html += '</div></div>' +
                            '<div class="bdt-others-plugins-link">';
                        
                        // Show different buttons based on plugin status
                        if (plugin.status === 'active') {
                            html += '<span class="bdt-button bdt-button-success bdt-disabled">' +
                                '<span class="dashicons dashicons-yes"></span> ' +
                                '<?php esc_html_e("Active", "bdthemes-prime-slider-lite"); ?>' +
                                '</span>';
                        } else if (plugin.status === 'installed') {
                            var activateUrl = '<?php echo admin_url("plugins.php?action=activate&plugin="); ?>' + plugin.plugin_file + '&_wpnonce=' + plugin.activate_nonce;
                            html += '<a class="bdt-button bdt-welcome-button" href="' + activateUrl + '">' +
                                '<?php esc_html_e("Activate", "bdthemes-prime-slider-lite"); ?>' +
                                '</a>';
                        } else {
                            html += '<button class="bdt-button bdt-welcome-button ps-install-plugin" data-plugin-slug="' + pluginSlug + '" data-nonce="<?php echo wp_create_nonce('ps_install_plugin_nonce'); ?>">' +
                                '<?php esc_html_e("Install", "bdthemes-prime-slider-lite"); ?>' +
                                '</button>';
                        }
                        
                        if (plugin.homepage) {
                            html += '<a class="bdt-button bdt-dashboard-sec-btn" target="_blank" href="' + plugin.homepage + '">' +
                                '<?php esc_html_e("Learn More", "bdthemes-prime-slider-lite"); ?>' +
                                '</a>';
                        }
                        
                        html += '</div></div>';
                    });
                }
                
                $list.html(html);
                
                // Handle plugin action buttons
                $('.ps-install-plugin').on('click', function(e) {
                    e.preventDefault();
                    
                    var $button = $(this);
                    var pluginSlug = $button.data('plugin-slug');
                    var nonce = $button.data('nonce');
                    var originalText = $button.text();
                    
                    // Disable button and show loading state
                    $button.prop('disabled', true)
                           .text('<?php echo esc_js(__('Installing...', 'bdthemes-prime-slider-lite')); ?>')
                           .addClass('bdt-installing');
                    
                    // Perform AJAX request
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'ps_install_plugin',
                            plugin_slug: pluginSlug,
                            nonce: nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                // Show success message
                                $button.text('<?php echo esc_js(__('Installed!', 'bdthemes-prime-slider-lite')); ?>')
                                       .removeClass('bdt-installing')
                                       .addClass('bdt-installed');
                                
                                // Show success notification
                                if (typeof bdtUIkit !== 'undefined' && bdtUIkit.notification) {
                                    bdtUIkit.notification({
                                        message: '<span class="dashicons dashicons-yes"></span> ' + response.data.message,
                                        status: 'success'
                                    });
                                }
                                
                                // Reload the page after 2 seconds to update button states
                                setTimeout(function() {
                                    window.location.reload();
                                }, 2000);
                                
                            } else {
                                // Show error message
                                $button.prop('disabled', false)
                                       .text(originalText)
                                       .removeClass('bdt-installing');
                                
                                // Show error notification
                                if (typeof bdtUIkit !== 'undefined' && bdtUIkit.notification) {
                                    bdtUIkit.notification({
                                        message: '<span class="dashicons dashicons-warning"></span> ' + response.data.message,
                                        status: 'danger'
                                    });
                                } else {
                                    alert('Error: ' + response.data.message);
                                }
                            }
                        },
                        error: function(xhr, status, error) {
                            // Show error message
                            $button.prop('disabled', false)
                                   .text(originalText)
                                   .removeClass('bdt-installing');
                            
                            // Show error notification
                            if (typeof bdtUIkit !== 'undefined' && bdtUIkit.notification) {
                                bdtUIkit.notification({
                                    message: '<span class="dashicons dashicons-warning"></span> <?php echo esc_js(__('Installation failed. Please try again.', 'bdthemes-prime-slider-lite')); ?>',
                                    status: 'danger'
                                });
                            } else {
                                alert('<?php echo esc_js(__('Installation failed. Please try again.', 'bdthemes-prime-slider-lite')); ?>');
                            }
                        }
                    });
                });
            }
            
            // Function to show loading state
            function showLoading() {
                $list.html(
                    '<div class="bdt-text-center bdt-padding-large">' +
                        '<div class="ps-loading-spinner">' +
                            '<div class="ps-loading-dots">' +
                                '<div class="ps-loading-dot"></div>' +
                                '<div class="ps-loading-dot"></div>' +
                                '<div class="ps-loading-dot"></div>' +
                            '</div>' +
                        '</div>' +
                        '<p class="bdt-margin-small-top bdt-text-muted"><?php esc_html_e("Loading plugin data...", "bdthemes-prime-slider-lite"); ?></p>' +
                    '</div>'
                );
                $list.show();
            }
            
            // Function to show error
            function showError() {
                $error.show();
                $list.hide();
            }
            
            // Retry button handler
            $('#ps-retry-load-plugins').on('click', function() {
                loadPlugins();
            });
            
            // Initial load
            loadPlugins();
        });
        </script>
        <?php
    }

    /**
     * AJAX handler for getting plugins data
     */
    public function ajax_get_plugins() {
        // Verify nonce
        if (!check_ajax_referer('ps_get_plugins_nonce', 'nonce', false)) {
            wp_die(__('Security check failed.', 'bdthemes-prime-slider-lite'));
        }

        // Get cached data (includes all plugins; Prime Slider is skipped only when printing)
        $plugins_data = \PrimeSlider\SetupWizard\Remote_Data_Handler::get_remote_plugins();
        
        // If cache is empty, try to fetch immediately (but don't block)
        if (empty($plugins_data)) {
            // Schedule background fetch if not already done
            \PrimeSlider\SetupWizard\Remote_Data_Handler::schedule_remote_fetch();
            
            // Return empty response with flag indicating data is loading
            wp_send_json_success([
                'plugins' => [],
                'loading' => true,
                'message' => __('Loading plugin data...', 'bdthemes-prime-slider-lite')
            ]);
        }

        // Send response
        wp_send_json_success([
            'plugins' => $plugins_data,
            'loading' => false,
            'message' => __('Plugin data loaded successfully.', 'bdthemes-prime-slider-lite')
        ]);
    }

    /**
     * AJAX handler for plugin installation
     */
    public function install_plugin_ajax() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'ps_install_plugin_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'bdthemes-prime-slider-lite')]);
        }

        // Check user capability
        if (!current_user_can('install_plugins')) {
            wp_send_json_error(['message' => __('You do not have permission to install plugins', 'bdthemes-prime-slider-lite')]);
        }

        $plugin_slug = sanitize_text_field($_POST['plugin_slug']);

        if (empty($plugin_slug)) {
            wp_send_json_error(['message' => __('Plugin slug is required', 'bdthemes-prime-slider-lite')]);
        }

        // Include necessary WordPress files
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';

        // Get plugin information
        $api = plugins_api('plugin_information', [
            'slug' => $plugin_slug,
            'fields' => [
                'sections' => false,
            ],
        ]);

        if (is_wp_error($api)) {
            wp_send_json_error(['message' => __('Plugin not found: ', 'bdthemes-prime-slider-lite') . $api->get_error_message()]);
        }

        // Install the plugin
        $skin = new \WP_Ajax_Upgrader_Skin();
        $upgrader = new \Plugin_Upgrader($skin);
        $result = $upgrader->install($api->download_link);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => __('Installation failed: ', 'bdthemes-prime-slider-lite') . $result->get_error_message()]);
        } elseif ($skin->get_errors()->has_errors()) {
            wp_send_json_error(['message' => __('Installation failed: ', 'bdthemes-prime-slider-lite') . $skin->get_error_messages()]);
        } elseif (is_null($result)) {
            wp_send_json_error(['message' => __('Installation failed: Unable to connect to filesystem', 'bdthemes-prime-slider-lite')]);
        }

        // Get installation status
        $install_status = install_plugin_install_status($api);
        
        wp_send_json_success([
            'message' => __('Plugin installed successfully!', 'bdthemes-prime-slider-lite'),
            'plugin_file' => $install_status['file'],
            'plugin_name' => $api->name
        ]);
    }
}

// Initialize the manager
new PrimeSlider_Others_Plugin_Manager();

/**
 * Helper function for easy rendering
 */
function prime_slider_others_plugin() {
    $manager = new PrimeSlider_Others_Plugin_Manager();
    $manager->render_others_plugin();
}
