<?php
/**
 * Integration Step
 */

namespace PrimeSlider\SetupWizard;

if (!defined('ABSPATH')) {
    exit;
}

// Include the required classes
require_once __DIR__ . '/../class-plugin-integration-helper.php';
require_once __DIR__ . '/../class-remote-data-handler.php';

if (!defined('PRIME_SLIDER_WPORG_ASSET_BASE')) {
    define('PRIME_SLIDER_WPORG_ASSET_BASE', 'https://ps.w.org');
}

if (!function_exists('get_plugin_asset_base_url_ps')) {
    function get_plugin_asset_base_url_ps() {
        return apply_filters('prime_slider_wporg_asset_base_url', PRIME_SLIDER_WPORG_ASSET_BASE);
    }
}

// Helper function for time formatting
if (!function_exists('format_last_updated_ps')) {
    function format_last_updated_ps($date_string) {
        if (empty($date_string)) {
            return __('Unknown', 'bdthemes-prime-slider');
        }
        
        $date = strtotime($date_string);
        if (!$date) {
            return __('Unknown', 'bdthemes-prime-slider');
        }
        
        $diff = current_time('timestamp') - $date;
        
        if ($diff < MINUTE_IN_SECONDS) {
            return __('Just now', 'bdthemes-prime-slider');
        } elseif ($diff < HOUR_IN_SECONDS) {
            $minutes = floor($diff / MINUTE_IN_SECONDS);
            return sprintf(_n('%d minute ago', '%d minutes ago', $minutes, 'bdthemes-prime-slider'), $minutes);
        } elseif ($diff < DAY_IN_SECONDS) {
            $hours = floor($diff / HOUR_IN_SECONDS);
            return sprintf(_n('%d hour ago', '%d hours ago', $hours, 'bdthemes-prime-slider'), $hours);
        } elseif ($diff < MONTH_IN_SECONDS) {
            $days = floor($diff / DAY_IN_SECONDS);
            return sprintf(_n('%d day ago', '%d days ago', $days, 'bdthemes-prime-slider'), $days);
        } elseif ($diff < YEAR_IN_SECONDS) {
            $months = floor($diff / MONTH_IN_SECONDS);
            return sprintf(_n('%d month ago', '%d months ago', $months, 'bdthemes-prime-slider'), $months);
        } else {
            $years = floor($diff / YEAR_IN_SECONDS);
            return sprintf(_n('%d year ago', '%d years ago', $years, 'bdthemes-prime-slider'), $years);
        }
    }
}

if (!function_exists('get_integration_i18n_ps')) {
    function get_integration_i18n_ps() {
        return array(
            'unableToLoadData'      => __( 'Unable to load plugin data.', 'bdthemes-prime-slider' ),
            'networkError'          => __( 'Network error occurred while loading plugin data.', 'bdthemes-prime-slider' ),
            'noPluginsFound'        => __( 'No plugins found.', 'bdthemes-prime-slider' ),
            'retry'                 => __( 'Retry', 'bdthemes-prime-slider' ),
            'recommended'           => __( 'Recommended', 'bdthemes-prime-slider' ),
            'activeBadge'           => __( 'ACTIVE', 'bdthemes-prime-slider' ),
            'activeInstallsLabel'   => __( 'Active Installs:', 'bdthemes-prime-slider' ),
            'downloadsLabel'        => __( 'Downloads:', 'bdthemes-prime-slider' ),
            'fewerThanTen'          => __( 'Fewer than 10', 'bdthemes-prime-slider' ),
            'outOfFiveStars'        => __( 'out of 5 stars', 'bdthemes-prime-slider' ),
            'outOfFiveStarsSentence'=> __( 'out of 5 stars.', 'bdthemes-prime-slider' ),
            'ratingsLabel'          => __( 'ratings', 'bdthemes-prime-slider' ),
            'lastUpdatedLabel'      => __( 'Last Updated:', 'bdthemes-prime-slider' ),
        );
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
        $base_url = untrailingslashit(get_plugin_asset_base_url_ps());
        $custom_icons = [
            'ar-viewer' => [
                "{$base_url}/ar-viewer/assets/icon-256x256.gif",
                "{$base_url}/ar-viewer/assets/icon-128x128.gif",
            ],
        ];
        
        // Return custom icons if available, otherwise use default WordPress.org URLs
        if (isset($custom_icons[$plugin_slug_clean])) {
            return $custom_icons[$plugin_slug_clean];
        }
        
        return [
            "{$base_url}/{$plugin_slug_clean}/assets/icon-256x256.png",  // Large PNG
            "{$base_url}/{$plugin_slug_clean}/assets/icon-128x128.png",  // Medium PNG
        ];
    }
}

$self_plugin_slugs = apply_filters(
    'prime_slider_setup_self_plugin_slugs',
    array(
        'bdthemes-prime-slider',
        'bdthemes-prime-slider-lite',
    )
);

// Get enhanced plugin data using the remote data handler
$ps_plugins = Remote_Data_Handler::get_remote_plugins();

// Check if we have cached data
$has_cached_data = !empty($ps_plugins);

// If no cached data, don't fetch immediately - let JavaScript handle it
if (!$has_cached_data) {
    $ps_plugins = []; // Empty array for initial load
}
?>

<div class="bdt-wizard-step bdt-setup-wizard-integration" data-step="integration">
    <h2><?php esc_html_e('Add More Firepower', 'bdthemes-prime-slider'); ?></h2>
    <p><?php esc_html_e('You can onboard additional powerful plugins to extend your web design capabilities.', 'bdthemes-prime-slider'); ?></p>

    <div class="progress-bar-container">
        <div id="plugin-install-progress" class="progress-bar"></div>
    </div>

    <form method="POST" id="ps-install-plugins">
        <!-- Loading state - shown during plugin installation -->
        <div class="ps-loading-state" id="ps-install-loading" style="display: none; text-align: center; padding: 40px;">
            <div class="ps-loading-dots">
                <div class="ps-loading-dot"></div>
                <div class="ps-loading-dot"></div>
                <div class="ps-loading-dot"></div>
            </div>
            <p style="margin-top: 20px;" id="ps-loading-message"><?php esc_html_e('Installing plugins...', 'bdthemes-prime-slider'); ?></p>
        </div>

        <!-- Initial loading state - shown while fetching plugin data -->
        <?php if (!$has_cached_data): ?>
        <div class="ps-loading-state" id="ps-initial-loading" style="text-align: center; padding: 40px;">
            <div class="ps-loading-dots">
                <div class="ps-loading-dot"></div>
                <div class="ps-loading-dot"></div>
                <div class="ps-loading-dot"></div>
            </div>
            <p style="margin-top: 20px;"><?php esc_html_e('Loading plugin data...', 'bdthemes-prime-slider'); ?></p>
        </div>
        <?php endif; ?>

        <div class="bdt-plugin-list" id="ps-integration-plugin-list">
            <?php if ($has_cached_data): ?>
                <?php
                $predefined = \PrimeSlider\SetupWizard\Plugin_Integration_Helper::get_predefined_plugins();
                foreach ($ps_plugins as $slug_key => $plugin) :
                    // Skip own plugin (Prime Slider)
                    if (in_array($slug_key, $self_plugin_slugs, true)) {
                        continue;
                    }
                    // Use enhanced status if available, otherwise fall back to old method
                    $plugin_status = $plugin['status'] ?? 'unknown';
                    if ($plugin_status === 'unknown') {
                        // Fallback to old method for compatibility
                        $is_active = is_plugin_active($plugin['slug']);
                    } else {
                        // Use enhanced status
                        $is_active = ($plugin_status === 'active');
                    }
                    $plugin_recommended = !empty($predefined[ $slug_key ]['recommended']);
                    $is_recommended = $plugin_recommended && !$is_active;
                ?>
                    <label class="plugin-item" data-slug="<?php echo esc_attr($plugin['slug']); ?>">
                        <span class="bdt-flex bdt-flex-middle bdt-flex-between bdt-margin-small-bottom">
                            <span class="bdt-plugin-logo">
                                <?php 
                                $logo_url = $plugin['logo'] ?? '';
                                $plugin_name = $plugin['name'] ?? '';
                                $plugin_slug = $plugin['slug'] ?? '';
                                
                                if (!empty($logo_url) && filter_var($logo_url, FILTER_VALIDATE_URL)) {
                                    // Show the original logo from API
                                    echo '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($plugin_name) . '" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">';
                                    echo '<div class="default-plugin-icon" style="display:none;">📦</div>';
                                } else {
                                    // Generate fallback URLs for WordPress.org
                                    $actual_slug = (strpos($plugin_slug, '/') !== false) ? dirname($plugin_slug) : $plugin_slug;
                                    $fallback_urls = get_plugin_fallback_urls_ps($actual_slug);
                                    
                                    echo '<img src="' . esc_url($fallback_urls[0]) . '" alt="' . esc_attr($plugin_name) . '" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">';
                                    echo '<div class="default-plugin-icon" style="display:none;">📦</div>';
                                }
                                ?>
                            </span>
                            
                            <div class="bdt-plugin-badge-switch-wrap">

                            <?php if ($is_recommended) : ?>
                                <span class="recommended-badge"><?php esc_html_e('Recommended', 'bdthemes-prime-slider'); ?></span>
                            <?php endif; ?>
                            
                            <?php if ($is_active) : ?>
                                <span class="active-badge"><?php esc_html_e('ACTIVE', 'bdthemes-prime-slider'); ?></span>
                            <?php endif; ?>
                             <?php
                             if (!$is_active) : ?>
                                 <label class="switch">
                                     <input type="checkbox" class="plugin-slider-checkbox" <?php echo $plugin_recommended ? 'checked' : ''; ?>
                                            name="plugins[]<?php echo isset($plugin['slug']) ? wp_kses_post($plugin['slug']) : ''; ?>">
                                     <span class="slider round"></span>
                                 </label>
                             <?php
                             endif;
                             ?>
                            </div>
                        </span>
                        <div class="bdt-flex bdt-flex-middle">
                                <span class="bdt-plugin-name">
                                    <?php echo wp_kses_post($plugin['name']); ?>
                                </span>
                            </div>
                            
                        <span class="active-installs">
                            <?php esc_html_e('Active Installs: ', 'bdthemes-prime-slider'); 
                            if (isset($plugin['active_installs_count']) && $plugin['active_installs_count'] > 0) {
                                echo ' <span class="installs-count">' . number_format_i18n((int) $plugin['active_installs_count']) . '+' . '</span>';
                            } else {
                                echo '<span class="installs-count">' . esc_html__('Fewer than 10', 'bdthemes-prime-slider') . '</span>';
                            }
                            ?>
                        </span>

                        <?php if (isset($plugin['downloaded_formatted']) && !empty($plugin['downloaded_formatted'])): ?>
                        <span class="downloads"><?php esc_html_e('Downloads: ', 'bdthemes-prime-slider'); echo wp_kses_post($plugin['downloaded_formatted']); ?></span>
                        <?php endif; ?>
                        
                        <div class="rating-section">
                            <div class="wporg-ratings" title="<?php echo esc_attr(sprintf(__('%s out of 5 stars', 'bdthemes-prime-slider'), (string) ($plugin['rating'] ?? '0'))); ?>" style="color:var(--wp--preset--color--pomegrade-1, #e26f56);">
                                <?php 
                                $rating = floatval($plugin['rating'] ?? 0);
                                $full_stars = floor($rating);
                                $has_half_star = ($rating - $full_stars) >= 0.5;
                                $empty_stars = 5 - $full_stars - ($has_half_star ? 1 : 0);
                                
                                // Full stars
                                for ($i = 0; $i < $full_stars; $i++) {
                                    echo '<span class="dashicons dashicons-star-filled"></span>';
                                }
                                
                                // Half star
                                if ($has_half_star) {
                                    echo '<span class="dashicons dashicons-star-half"></span>';
                                }
                                
                                // Empty stars
                                for ($i = 0; $i < $empty_stars; $i++) {
                                    echo '<span class="dashicons dashicons-star-empty"></span>';
                                }
                                ?>
                            </div>
                            <span class="rating-text">
                                <?php echo esc_html($plugin['rating'] ?? '0'); ?> <?php esc_html_e('out of 5 stars.', 'bdthemes-prime-slider'); ?>
                                <?php if (isset($plugin['num_ratings']) && $plugin['num_ratings'] > 0): ?>
                                    <span class="rating-count">(<?php echo esc_html(number_format_i18n((int) $plugin['num_ratings'])); ?> <?php esc_html_e('ratings', 'bdthemes-prime-slider'); ?>)</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <?php 
                        // Use the enhanced last_updated_formatted if available, otherwise fall back to formatting
                        if (isset($plugin['last_updated_formatted']) && !empty($plugin['last_updated_formatted'])): ?>
                        <span class="last-updated"><?php esc_html_e('Last Updated: ', 'bdthemes-prime-slider'); echo esc_html($plugin['last_updated_formatted']); ?></span>
                        <?php elseif (isset($plugin['last_updated']) && !empty($plugin['last_updated'])): ?>
                        <span class="last-updated"><?php esc_html_e('Last Updated: ', 'bdthemes-prime-slider'); echo esc_html(format_last_updated_ps($plugin['last_updated'])); ?></span>
                        <?php endif; ?>

                    </label>
                <?php
                endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="wizard-navigation bdt-margin-top">
            <button class="bdt-button bdt-button-primary d-none" type="submit" id="ps-install-plugins-btn">
                <?php esc_html_e('Install and Continue', 'bdthemes-prime-slider'); ?>
            </button>
            <div class="bdt-close-button bdt-margin-left bdt-wizard-next" data-step="finish"><?php esc_html_e('Skip', 'bdthemes-prime-slider'); ?></div>
        </div>
    </form>

    <div class="bdt-wizard-navigation">
        <button class="bdt-button bdt-button-secondary bdt-wizard-prev" data-step="features">
            <span><i class="dashicons dashicons-arrow-left-alt"></i></span>
            <?php esc_html_e('Previous Step', 'bdthemes-prime-slider'); ?>
        </button>
    </div>
</div>

<style>
.ps-loading-dots {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin: 20px 0;
}

.ps-loading-dot {
    width: 12px;
    height: 12px;
    background-color: #FC6A2A;
    border-radius: 50%;
    animation: ps-wave 1.4s ease-in-out infinite both;
}

.ps-loading-dot:nth-child(1) {
    animation-delay: -0.32s;
}

.ps-loading-dot:nth-child(2) {
    animation-delay: -0.16s;
}

.ps-loading-dot:nth-child(3) {
    animation-delay: 0s;
}

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
</style>

<?php
$ps_integration_i18n = get_integration_i18n_ps();
$ps_integration_config = array(
    'ajaxAction' => 'ps_get_plugins',
    'nonce' => wp_create_nonce('ps_get_plugins_nonce'),
    'wporgAssetBase' => get_plugin_asset_base_url_ps(),
    'selfPluginSlugs' => $self_plugin_slugs,
);
?>
<script>
jQuery(document).ready(function($) {
    const psIntegrationI18n = <?php echo wp_json_encode($ps_integration_i18n); ?>;
    const psIntegrationConfig = <?php echo wp_json_encode($ps_integration_config); ?>;
    let integrationDataLoaded = false;
    
    // Function to load integration data
    function loadIntegrationData() {
        if (integrationDataLoaded) return;
        
        const $pluginList = $('#ps-integration-plugin-list');
        const $initialLoading = $('#ps-initial-loading');
        
        // Don't add another loading state if initial loading is visible
        // Just keep the existing one
        
        // Make AJAX request to get plugin data
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: psIntegrationConfig.ajaxAction,
                nonce: psIntegrationConfig.nonce
            },
            success: function(response) {
                if (response.success && response.data.plugins) {
                    // Hide the initial loading div
                    $initialLoading.hide();
                    renderPluginList(response.data.plugins);
                    integrationDataLoaded = true;
                } else {
                    showError(psIntegrationI18n.unableToLoadData);
                }
            },
            error: function() {
                showError(psIntegrationI18n.networkError);
            }
        });
    }
    
    // Function to render plugin list
    function renderPluginList(plugins) {
        const $pluginList = $('#ps-integration-plugin-list');
        let html = '';
        
        if (plugins.length === 0) {
            html = `<div class="ps-no-plugins" style="text-align: center; padding: 40px;"><p>${psIntegrationI18n.noPluginsFound}</p></div>`;
        } else {
            plugins.forEach(function(plugin) {
                // Skip own plugin (Prime Slider) when printing only; data still includes it for other plugins
                if (Array.isArray(psIntegrationConfig.selfPluginSlugs) && psIntegrationConfig.selfPluginSlugs.includes(plugin.slug)) return;
                const isActive = plugin.status === 'active';
                const isRecommended = plugin.recommended && !isActive;
                
                html += `
                    <label class="plugin-item" data-slug="${plugin.slug}">
                        <span class="bdt-flex bdt-flex-middle bdt-flex-between bdt-margin-small-bottom">
                            <span class="bdt-plugin-logo">
                                ${generatePluginLogo(plugin)}
                            </span>
                            <div class="bdt-plugin-badge-switch-wrap">
                                ${isRecommended ? `<span class="recommended-badge">${psIntegrationI18n.recommended}</span>` : ''}
                                ${isActive ? `<span class="active-badge">${psIntegrationI18n.activeBadge}</span>` : ''}
                                ${!isActive ? `
                                    <label class="switch">
                                        <input type="checkbox" class="plugin-slider-checkbox" ${plugin.recommended ? 'checked' : ''} name="plugins[]${plugin.slug}">
                                        <span class="slider round"></span>
                                    </label>
                                ` : ''}
                            </div>
                        </span>
                        <div class="bdt-flex bdt-flex-middle">
                            <span class="bdt-plugin-name">${plugin.name}</span>
                        </div>
                        <span class="active-installs">
                            ${psIntegrationI18n.activeInstallsLabel}
                            <span class="installs-count">${plugin.active_installs_count > 0 ? plugin.active_installs_count.toLocaleString() + '+' : psIntegrationI18n.fewerThanTen}</span>
                        </span>
                        ${plugin.downloaded_formatted ? `<span class="downloads">${psIntegrationI18n.downloadsLabel} ${plugin.downloaded_formatted}</span>` : ''}
                        <div class="rating-section">
                            <div class="wporg-ratings" title="${plugin.rating} ${psIntegrationI18n.outOfFiveStars}" style="color:var(--wp--preset--color--pomegrade-1, #e26f56);">
                                ${generateStarRating(plugin.rating)}
                            </div>
                            <span class="rating-text">
                                ${plugin.rating} ${psIntegrationI18n.outOfFiveStarsSentence}
                                ${plugin.num_ratings > 0 ? `<span class="rating-count">(${plugin.num_ratings.toLocaleString()} ${psIntegrationI18n.ratingsLabel})</span>` : ''}
                            </span>
                        </div>
                        ${plugin.last_updated_formatted ? `<span class="last-updated">${psIntegrationI18n.lastUpdatedLabel} ${plugin.last_updated_formatted}</span>` : ''}
                    </label>
                `;
            });
        }
        
        $pluginList.html(html);
    }
    
    // Helper function to generate plugin logo
    function generatePluginLogo(plugin) {
        if (plugin.logo && plugin.logo.match(/^https?:\/\//)) {
            return `<img src="${plugin.logo}" alt="${plugin.name}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="default-plugin-icon" style="display:none;">📦</div>`;
        } else {
            const slug = plugin.slug.includes('/') ? plugin.slug.split('/')[0] : plugin.slug;
            const wporgAssetBase = String(psIntegrationConfig.wporgAssetBase || 'https://ps.w.org').replace(/\/$/, '');
            return `<img src="${wporgAssetBase}/${slug}/assets/icon-256x256.png" alt="${plugin.name}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="default-plugin-icon" style="display:none;">📦</div>`;
        }
    }
    
    // Helper function to generate star rating
    function generateStarRating(rating) {
        const fullStars = Math.floor(rating);
        const hasHalfStar = (rating - fullStars) >= 0.5;
        const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
        
        let html = '';
        for (let i = 0; i < fullStars; i++) {
            html += '<span class="dashicons dashicons-star-filled"></span>';
        }
        if (hasHalfStar) {
            html += '<span class="dashicons dashicons-star-half"></span>';
        }
        for (let i = 0; i < emptyStars; i++) {
            html += '<span class="dashicons dashicons-star-empty"></span>';
        }
        return html;
    }
    
    // Function to show error
    function showError(message) {
        const $pluginList = $('#ps-integration-plugin-list');
        const $initialLoading = $('#ps-initial-loading');
        
        // Hide initial loading
        $initialLoading.hide();
        
        // Show error in plugin list
        $pluginList.html(`
            <div class="ps-error-state" style="text-align: center; padding: 40px;">
                <p style="color: #d63638;">${message}</p>
                <button type="button" class="bdt-button bdt-button-secondary" onclick="location.reload()">${psIntegrationI18n.retry}</button>
            </div>
        `);
    }
    
    // Detect when integration tab becomes active
    function observeIntegrationTab() {
        // Check if integration step is currently visible
        const $integrationStep = $('.bdt-setup-wizard-integration');
        
        if ($integrationStep.length) {
            // Create a MutationObserver to detect when the integration step becomes visible
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        const $target = $(mutation.target);
                        if ($target.hasClass('bdt-setup-wizard-integration') && $target.is(':visible')) {
                            loadIntegrationData();
                            observer.disconnect(); // Stop observing after first load
                        }
                    }
                });
            });
            
            // Start observing
            observer.observe($integrationStep[0], {
                attributes: true,
                attributeFilter: ['class']
            });
            
            // Also check immediately if it's already visible
            if ($integrationStep.is(':visible') && !integrationDataLoaded) {
                loadIntegrationData();
            }
        }
    }
    
    // Initialize tab observation
    observeIntegrationTab();
    
    // Fallback: Also try to detect tab clicks (for different wizard implementations)
    $(document).on('click', '[data-step="integration"], .bdt-wizard-step[data-step="integration"]', function() {
        if (!integrationDataLoaded) {
            setTimeout(loadIntegrationData, 100);
        }
    });
});
</script>