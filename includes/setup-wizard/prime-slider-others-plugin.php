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
        // Add AJAX handlers. This is an admin-only, plugin-install screen; the
        // handler must never be exposed to unauthenticated visitors.
        add_action('wp_ajax_bdtps_get_plugins', [$this, 'ajax_get_plugins']);
        add_action('wp_ajax_bdtps_install_plugin', [$this, 'install_plugin_ajax']);
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
        if (!function_exists('bdtps_format_last_updated')) {
            function bdtps_format_last_updated($date_string) {
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
                    /* translators: %d: number of minutes */
                    return sprintf(_n('%d minute ago', '%d minutes ago', $minutes, 'bdthemes-prime-slider-lite'), $minutes);
                } elseif ($diff < 86400) {
                    $hours = floor($diff / 3600);
                    /* translators: %d: number of hours */
                    return sprintf(_n('%d hour ago', '%d hours ago', $hours, 'bdthemes-prime-slider-lite'), $hours);
                } elseif ($diff < 2592000) { // 30 days
                    $days = floor($diff / 86400);
                    /* translators: %d: number of days */
                    return sprintf(_n('%d day ago', '%d days ago', $days, 'bdthemes-prime-slider-lite'), $days);
                } elseif ($diff < 31536000) { // 1 year
                    $months = floor($diff / 2592000);
                    /* translators: %d: number of months */
                    return sprintf(_n('%d month ago', '%d months ago', $months, 'bdthemes-prime-slider-lite'), $months);
                } else {
                    $years = floor($diff / 31536000);
                    /* translators: %d: number of years */
                    return sprintf(_n('%d year ago', '%d years ago', $years, 'bdthemes-prime-slider-lite'), $years);
                }
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
                        action: 'bdtps_get_plugins',
                        nonce: '<?php echo esc_attr( wp_create_nonce("ps_get_plugins_nonce") ); ?>'
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
            
            // Escape remote-sourced strings before they are concatenated into
            // markup. The plugin catalog comes from a remote endpoint; treat it
            // as untrusted so a poisoned/compromised feed cannot inject HTML/JS
            // into the admin dashboard (the 2026 notification-feed incident).
            function psEsc(s) {
                return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                });
            }
            function psSafeUrl(u) {
                u = String(u == null ? '' : u);
                return /^https?:\/\//i.test(u) ? u : '';
            }

            // Function to render plugins
            function renderPlugins(plugins) {
                var html = '';
                
                if (plugins.length === 0) {
                    html = '<div class="bdt-text-center bdt-padding-large"><p><?php echo esc_js(__('No plugins available.', 'bdthemes-prime-slider-lite')); ?></p></div>';
                } else {
                    plugins.forEach(function(plugin) {
                        // Skip own plugin (Prime Slider) when printing only; data still includes it for other plugins
                        if (plugin.slug === 'bdthemes-prime-slider-lite') return;
                        var isActive = false; // We'll determine this via PHP in the actual implementation
                        var logoUrl = plugin.logo || '';
                        var pluginName = plugin.name || '';
                        var pluginSlug = plugin.slug || '';

                        // The logo URL comes from the WordPress.org plugins API response. When it
                        // is missing we show the local placeholder icon rather than guessing a
                        // remote asset URL.
                        var logoMarkup = logoUrl
                            ? '<img src="' + psEsc(psSafeUrl(logoUrl)) + '" alt="' + psEsc(pluginName) + '" class="bdt-plugin-logo" ' +
                                  'onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">' +
                              '<div class="default-plugin-icon" style="display:none;">📦</div>'
                            : '<div class="default-plugin-icon" style="display:flex;">📦</div>';

                        html += '<div class="bdt-card bdt-card-body bdt-flex bdt-flex-middle bdt-flex-between">' +
                            '<div class="bdt-others-plugin-content">' +
                                '<div class="bdt-plugin-logo-wrap bdt-flex bdt-flex-middle">' +
                                    '<div class="bdt-plugin-logo-container">' +
                                        logoMarkup +
                                    '</div>' +
                                    '<div class="bdt-others-plugin-user-wrap bdt-flex bdt-flex-middle">' +
                                        '<h1 class="ps-feature-title">' + psEsc(pluginName) + '</h1>' +
                                    '</div>' +
                                '</div>' +
                                '<div class="bdt-others-plugin-content-text bdt-margin-top">';
                        
                        if (plugin.description) {
                            html += '<p>' + psEsc(plugin.description) + '</p>';
                        }
                        
                        // Active installs
                        var installsCount = Number(plugin.active_installs_count) || 0;
                        html += '<span class="active-installs bdt-margin-small-top">' +
                            '<?php echo esc_js(__('Active Installs: ', 'bdthemes-prime-slider-lite')); ?> ';
                        if (installsCount > 0) {
                            html += '<span class="installs-count">' + psEsc(installsCount.toLocaleString()) + '+</span>';
                        } else {
                            html += '<span class="installs-count"><?php echo esc_js(__('Fewer than 10', 'bdthemes-prime-slider-lite')); ?></span>';
                        }
                        html += '</span>';
                        
                        // Rating
                        html += '<div class="bdt-others-plugin-rating bdt-margin-small-top bdt-flex bdt-flex-middle">' +
                            '<span class="bdt-others-plugin-rating-stars">';
                        
                        // Clamp to 0-5 so malformed data cannot emit a runaway number of stars.
                        var rating = Math.min(5, Math.max(0, parseFloat(plugin.rating) || 0));
                        var fullStars = Math.floor(rating);
                        var hasHalfStar = (rating - fullStars) >= 0.5;
                        var emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
                        var i;

                        for (i = 0; i < fullStars; i++) {
                            html += '<i class="dashicons dashicons-star-filled"></i>';
                        }
                        if (hasHalfStar) {
                            html += '<i class="dashicons dashicons-star-half"></i>';
                        }
                        for (i = 0; i < emptyStars; i++) {
                            html += '<i class="dashicons dashicons-star-empty"></i>';
                        }

                        html += '</span>' +
                            '<span class="bdt-others-plugin-rating-text bdt-margin-small-left">' +
                                rating + ' <?php echo esc_js(__('out of 5 stars.', 'bdthemes-prime-slider-lite')); ?>';

                        var numRatings = Number(plugin.num_ratings) || 0;
                        if (numRatings > 0) {
                            html += '<span class="rating-count">(' + psEsc(numRatings.toLocaleString()) + ' <?php echo esc_js(__('ratings', 'bdthemes-prime-slider-lite')); ?>)</span>';
                        }
                        
                        html += '</span></div>';
                        
                        // Downloads
                        if (plugin.downloaded_formatted) {
                            html += '<div class="bdt-others-plugin-downloads bdt-margin-small-top">' +
                                '<span><?php echo esc_js(__('Downloads: ', 'bdthemes-prime-slider-lite')); ?>' + psEsc(plugin.downloaded_formatted) + '</span>' +
                                '</div>';
                        }

                        // Last updated
                        if (plugin.last_updated_formatted) {
                            html += '<div class="bdt-others-plugin-updated bdt-margin-small-top">' +
                                '<span><?php echo esc_js(__('Last Updated: ', 'bdthemes-prime-slider-lite')); ?>' + psEsc(plugin.last_updated_formatted) + '</span>' +
                                '</div>';
                        }
                        
                        html += '</div></div>' +
                            '<div class="bdt-others-plugins-link">';
                        
                        // Show different buttons based on plugin status
                        if (plugin.status === 'active') {
                            html += '<span class="bdt-button bdt-button-success bdt-disabled">' +
                                '<span class="dashicons dashicons-yes"></span> ' +
                                '<?php echo esc_js(__('Active', 'bdthemes-prime-slider-lite')); ?>' +
                                '</span>';
                        } else if (plugin.status === 'installed') {
                            // URL-encode the query values: plugin_file contains slashes and
                            // both parts land inside an href attribute.
                            var activateUrl = '<?php echo esc_url( admin_url('plugins.php?action=activate&plugin=') ); ?>' +
                                encodeURIComponent(plugin.plugin_file || '') +
                                '&_wpnonce=' + encodeURIComponent(plugin.activate_nonce || '');
                            html += '<a class="bdt-button bdt-welcome-button" href="' + psEsc(activateUrl) + '">' +
                                '<?php echo esc_js(__('Activate', 'bdthemes-prime-slider-lite')); ?>' +
                                '</a>';
                        } else {
                            html += '<button type="button" class="bdt-button bdt-welcome-button ps-install-plugin" data-plugin-slug="' + psEsc(pluginSlug) + '" data-nonce="<?php echo esc_attr( wp_create_nonce('ps_install_plugin_nonce') ); ?>">' +
                                '<?php echo esc_js(__('Install', 'bdthemes-prime-slider-lite')); ?>' +
                                '</button>';
                        }

                        if (plugin.homepage && psSafeUrl(plugin.homepage)) {
                            html += '<a class="bdt-button bdt-dashboard-sec-btn" target="_blank" rel="noopener noreferrer" href="' + psEsc(psSafeUrl(plugin.homepage)) + '">' +
                                '<?php echo esc_js(__('Learn More', 'bdthemes-prime-slider-lite')); ?>' +
                                '</a>';
                        }
                        
                        html += '</div></div>';
                    });
                }
                
                $list.html(html);

                // Handle plugin action buttons. Delegated from the list and
                // namespaced+unbound first: renderPlugins() runs again on every
                // retry, and a plain global bind stacked one handler per render,
                // firing duplicate install requests for a single click.
                $list.off('click.psInstall').on('click.psInstall', '.ps-install-plugin', function(e) {
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
                        url: '<?php echo esc_url( admin_url('admin-ajax.php') ); ?>',
                        type: 'POST',
                        data: {
                            action: 'bdtps_install_plugin',
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
                        '<p class="bdt-margin-small-top bdt-text-muted"><?php echo esc_js(__('Loading plugin data...', 'bdthemes-prime-slider-lite')); ?></p>' +
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
     * Decode display text coming from the WordPress.org plugins API.
     *
     * The API returns strings that are already HTML-encoded, e.g.
     * "Element Pack Lite &#8211; Addons for Elementor". The renderer escapes
     * again before injecting into the DOM, which turns the leading "&" into
     * "&amp;" and prints the entity literally instead of an en dash. Decoding
     * here means exactly one round of escaping happens, at output.
     *
     * @param mixed $text Raw value from the API.
     * @return string Plain text, still to be escaped at output.
     */
    private function decode_api_text($text) {
        if (!is_string($text) || '' === $text) {
            return '';
        }

        return html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Normalise cached plugin data for display.
     *
     * Applied on the response rather than when caching, so already-cached
     * entries are corrected without waiting for the transient to expire.
     *
     * @param mixed $plugins Cached plugin list.
     * @return array
     */
    private function prepare_plugins_for_display($plugins) {
        if (!is_array($plugins)) {
            return [];
        }

        foreach ($plugins as $index => $plugin) {
            if (!is_array($plugin)) {
                continue;
            }

            foreach (['name', 'description'] as $key) {
                if (isset($plugin[$key])) {
                    $plugins[$index][$key] = $this->decode_api_text($plugin[$key]);
                }
            }
        }

        return $plugins;
    }

    /**
     * AJAX handler for getting plugins data
     */
    public function ajax_get_plugins() {
        // Verify nonce. Respond with JSON -- the caller parses the response as
        // JSON, so wp_die() here would surface as a generic "unable to load".
        if (!check_ajax_referer('ps_get_plugins_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed.', 'bdthemes-prime-slider-lite')], 403);
        }

        // This data is only ever used on the plugin-install screen; gate it to
        // users who could act on it rather than exposing it to any visitor.
        if (!current_user_can('install_plugins')) {
            wp_send_json_error(['message' => __('You do not have permission to do this.', 'bdthemes-prime-slider-lite')], 403);
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
            'plugins' => $this->prepare_plugins_for_display($plugins_data),
            'loading' => false,
            'message' => __('Plugin data loaded successfully.', 'bdthemes-prime-slider-lite')
        ]);
    }

    /**
     * AJAX handler for plugin installation
     */
    public function install_plugin_ajax() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ps_install_plugin_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'bdthemes-prime-slider-lite')]);
        }

        // Check user capability
        if (!current_user_can('install_plugins')) {
            wp_send_json_error(['message' => __('You do not have permission to install plugins', 'bdthemes-prime-slider-lite')]);
        }

        $plugin_slug = isset($_POST['plugin_slug']) ? sanitize_text_field(wp_unslash($_POST['plugin_slug'])) : '';

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
