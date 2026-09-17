<?php
/**
 * Welcome Step
 */

namespace PrimeSlider\SetupWizard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="bdt-wizard-step bdt-text-center active" data-step="welcome">
    <div class="bdt-welcome-header">
        <div class="bdt-logo-container">
            <img src="<?php echo esc_url( BDTPS_CORE_ASSETS_URL . 'images/logo.png' ); ?>" alt="Prime Slider Logo" class="bdt-logo">
        </div>
        <h2><?php esc_html_e( 'Welcome to Prime Slider', 'bdthemes-prime-slider-lite' ); ?></h2>
    </div>

    <div class="bdt-welcome-scroll">
    <div class="bdt-welcome-features">
        <div class="bdt-features-grid">
            <div class="bdt-feature-item">
                <div class="bdt-feature-icon">
                    <span class="dashicons dashicons-admin-customizer"></span>
                </div>
                <h3><?php esc_html_e( '60+ Widgets', 'bdthemes-prime-slider-lite' ); ?></h3>
                <p><?php esc_html_e( 'Powerful elements for unlimited design possibilities', 'bdthemes-prime-slider-lite' ); ?></p>
            </div>
            <div class="bdt-feature-item">
                <div class="bdt-feature-icon">
                    <span class="dashicons dashicons-layout"></span>
                </div>
                <h3><?php esc_html_e( 'Ready Templates', 'bdthemes-prime-slider-lite' ); ?></h3>
                <p><?php esc_html_e( 'Professional templates to jumpstart your projects', 'bdthemes-prime-slider-lite' ); ?></p>
            </div>
            <div class="bdt-feature-item">
                <div class="bdt-feature-icon">
                    <span class="dashicons dashicons-performance"></span>
                </div>
                <h3><?php esc_html_e( 'Fast & Optimized', 'bdthemes-prime-slider-lite' ); ?></h3>
                <p><?php esc_html_e( 'Built with performance in mind for lightning-fast websites', 'bdthemes-prime-slider-lite' ); ?></p>
            </div>
            <div class="bdt-feature-item">
                <div class="bdt-feature-icon">
                    <span class="dashicons dashicons-smartphone"></span>
                </div>
                <h3><?php esc_html_e( 'Fully Responsive', 'bdthemes-prime-slider-lite' ); ?></h3>
                <p><?php esc_html_e( 'Slides adapt beautifully to phones, tablets and desktops', 'bdthemes-prime-slider-lite' ); ?></p>
            </div>
            <div class="bdt-feature-item">
                <div class="bdt-feature-icon">
                    <span class="dashicons dashicons-cart"></span>
                </div>
                <h3><?php esc_html_e( 'WooCommerce Ready', 'bdthemes-prime-slider-lite' ); ?></h3>
                <p><?php esc_html_e( 'Showcase products in eye-catching WooCommerce sliders', 'bdthemes-prime-slider-lite' ); ?></p>
            </div>
            <div class="bdt-feature-item">
                <div class="bdt-feature-icon">
                    <span class="dashicons dashicons-sos"></span>
                </div>
                <h3><?php esc_html_e( 'Dedicated Support', 'bdthemes-prime-slider-lite' ); ?></h3>
                <p><?php esc_html_e( 'Regular updates and expert help whenever you need a hand', 'bdthemes-prime-slider-lite' ); ?></p>
            </div>
        </div>
    </div>

    <?php require plugin_dir_path( BDTPS_CORE__FILE__ ) . 'includes/setup-wizard/views/subscribe.php'; ?>
    </div>

    <div class="bdt-wizard-navigation">
        <button class="bdt-button bdt-button-primary bdt-wizard-next" data-step="features">
            <?php esc_html_e( 'Get Started', 'bdthemes-prime-slider-lite' ); ?>
            <span><i class="dashicons dashicons-arrow-right-alt"></i></span>
        </button>
    </div>
</div>
