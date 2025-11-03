<?php
/**
 * Integration Step
 */

namespace PrimeSlider\SetupWizard;

if (!defined('ABSPATH')) {
    exit;
}

// Include the plugin API fetcher and helper
require_once __DIR__ . '/../class-plugin-api-fetcher.php';
require_once __DIR__ . '/../class-plugin-integration-helper.php';

// Function to format date in human-readable format
function format_last_updated($date_string) {
    if (empty($date_string)) {
        return '';
    }
    
    // Try to parse the date
    $date = strtotime($date_string);
    if ($date === false) {
        return $date_string; // Return original if parsing fails
    }
    
    // Calculate time difference
    $now = time();
    $diff = $now - $date;
    
    // Convert to human-readable format
    if ($diff < 60) {
        return __('just now', 'bdthemes-prime-slider');
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return sprintf(_n('%d minute ago', '%d minutes ago', $minutes, 'bdthemes-prime-slider'), $minutes);
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return sprintf(_n('%d hour ago', '%d hours ago', $hours, 'bdthemes-prime-slider'), $hours);
    } elseif ($diff < 2592000) { // 30 days
        $days = floor($diff / 86400);
        return sprintf(_n('%d day ago', '%d days ago', $days, 'bdthemes-prime-slider'), $days);
    } elseif ($diff < 31536000) { // 1 year
        $months = floor($diff / 2592000);
        return sprintf(_n('%d month ago', '%d months ago', $months, 'bdthemes-prime-slider'), $months);
    } else {
        $years = floor($diff / 31536000);
        return sprintf(_n('%d year ago', '%d years ago', $years, 'bdthemes-prime-slider'), $years);
    }
}

// Function to generate fallback URLs for plugin logos
function get_plugin_fallback_urls($plugin_slug) {
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
        'bdthemes-element-pack-lite' => [
            'https://ps.w.org/bdthemes-element-pack-lite/assets/icon-256x256.png',
            'https://ps.w.org/bdthemes-element-pack-lite/assets/icon-128x128.png',
        ],
        'live-copy-paste' => [
            'https://ps.w.org/live-copy-paste/assets/icon-256x256.png',
            'https://ps.w.org/live-copy-paste/assets/icon-128x128.png',
        ],
        'spin-wheel' => [
            'https://ps.w.org/spin-wheel/assets/icon-256x256.png',
            'https://ps.w.org/spin-wheel/assets/icon-128x128.png',
        ],
        'ai-image' => [
            'https://ps.w.org/ai-image/assets/icon-256x256.png',
            'https://ps.w.org/ai-image/assets/icon-128x128.png',
        ],
        'smart-admin-assistant' => [
            'https://ps.w.org/smart-admin-assistant/assets/icon-256x256.png',
            'https://ps.w.org/smart-admin-assistant/assets/icon-128x128.png',
        ],
        'website-accessibility' => [
            'https://ps.w.org/website-accessibility/assets/icon-256x256.png',
            'https://ps.w.org/website-accessibility/assets/icon-128x128.png',
        ],
    ];
    
    // Return custom icons if available, otherwise use default WordPress.org URLs
    if (isset($custom_icons[$plugin_slug_clean])) {
        return $custom_icons[$plugin_slug_clean];
    }
    
    return [
        "https://ps.w.org/{$plugin_slug_clean}/assets/icon-256x256.gif",  // Try GIF first
        "https://ps.w.org/{$plugin_slug_clean}/assets/icon-256x256.png",  // Then PNG
        "https://ps.w.org/{$plugin_slug_clean}/assets/icon-128x128.gif",  // Medium GIF
        "https://ps.w.org/{$plugin_slug_clean}/assets/icon-128x128.png",  // Medium PNG
    ];
}

// Define plugin slugs to fetch data for
$plugin_slugs = array(
    'bdthemes-element-pack-lite',
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

    // Add more plugin slugs here as needed
);

// Get plugin data using the helper
$ps_plugins = Plugin_Integration_Helper::build_plugin_data($plugin_slugs);
?>

<div class="bdt-wizard-step bdt-setup-wizard-integration" data-step="integration">
    <h2><?php esc_html_e('Add More Firepower', 'bdthemes-prime-slider'); ?></h2>
    <p><?php esc_html_e('You can onboard additional powerful plugins to extend your web design capabilities.', 'bdthemes-prime-slider'); ?></p>

    <div class="progress-bar-container">
        <div id="plugin-install-progress" class="progress-bar"></div>
    </div>

    <form method="POST" id="ps-install-plugins">
        <div class="bdt-plugin-list">
            <?php
            foreach ($ps_plugins as $plugin) :
                $is_active = is_plugin_active($plugin['slug']);
                $is_recommended = $plugin['recommended'] && !$is_active;
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
                                ?>
                                <img src="<?php echo esc_url($logo_url); ?>" 
                                    alt="<?php echo esc_attr($plugin_name); ?>" 
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                >
                                <div class="default-plugin-icon" style="display:none;">📦</div>
                                <?php
                            } else {
                                // Generate fallback URLs for WordPress.org
                                // Extract the actual plugin slug from the file path format
                                $actual_slug = str_replace('.php', '', basename($plugin_slug));
                                $fallback_urls = get_plugin_fallback_urls($actual_slug);
                                ?>
                                <img src="<?php echo esc_url($fallback_urls[0]); ?>" 
                                    alt="<?php echo esc_attr($plugin_name); ?>" 
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                >
                                <div class="default-plugin-icon" style="display:none;">📦</div>
                                <?php
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
                        <?php if (!$is_active) : ?>
                            <label class="switch">
                                <input type="checkbox" class="plugin-slider-checkbox"
                                    <?php checked(!empty($plugin['recommended'])); ?>
                                    name="plugins[<?php echo esc_attr($plugin['slug']); ?>]">
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
                        
                    <!-- <span class="plugin-text">
						<?php //echo wp_kses_post($plugin['description']); ?>
					</span> -->
                    <span class="active-installs">
                        <?php 
                        esc_html_e('Active Installs: ', 'bdthemes-prime-slider'); 
                        if (isset($plugin['active_installs_count']) && $plugin['active_installs_count'] > 0) {
                            echo '<span class="installs-count">' . esc_html(number_format($plugin['active_installs_count'])) . '+</span>';
                        }
                        ?>
                    </span>

                    <?php if (isset($plugin['downloaded_formatted']) && !empty($plugin['downloaded_formatted'])): ?>
                    <span class="downloads"><?php esc_html_e('Downloads: ', 'bdthemes-prime-slider'); echo wp_kses_post($plugin['downloaded_formatted']); ?></span>
                    <?php endif; ?>
                    
                    <div class="rating-section">
                        <!-- <span class="rating-label"><?php //esc_html_e('Ratings', 'bdthemes-prime-slider'); ?></span> -->
                        <div class="wporg-ratings" 
                            title="<?php echo esc_attr(($plugin['rating'] ?? '0') . ' out of 5 stars'); ?>" 
                            style="color:var(--wp--preset--color--pomegrade-1, #e26f56);">
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
                            <?php 
                            /* translators: %s: rating value out of 5 */
                            echo esc_html(sprintf(__('%s out of 5 stars.', 'bdthemes-prime-slider'), ($plugin['rating'] ?? '0'))); 
                            ?>
                            <?php if (isset($plugin['num_ratings']) && $plugin['num_ratings'] > 0): ?>
                                <span class="rating-count">
                                    <?php 
                                    /* translators: %s: number of ratings */
                                    echo esc_html(sprintf(__('(%s ratings)', 'bdthemes-prime-slider'), number_format($plugin['num_ratings']))); 
                                    ?>
                                </span>
                            <?php endif; ?>
                        </span>
                    </div>

                    <?php if (isset($plugin['last_updated']) && !empty($plugin['last_updated'])): ?>
                    <span class="last-updated"><?php esc_html_e('Last Updated: ', 'bdthemes-prime-slider'); echo esc_html(format_last_updated($plugin['last_updated'])); ?></span>
                    <?php endif; ?>
                </label>
            <?php
            endforeach; ?>
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
