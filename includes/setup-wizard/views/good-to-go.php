<?php
/**
 * Complete Step
 */

namespace PrimeSlider\SetupWizard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template partial included within a method; these variables are method-scoped, not global.

$templates_path = BDTPS_CORE_INC_PATH . 'setup-wizard/assets/data.json';

if ( function_exists( 'wp_json_file_decode' ) ) {
	$templates = wp_json_file_decode( $templates_path, [ 'associative' => true ] );
} elseif ( is_readable( $templates_path ) ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading a JSON file bundled with the plugin, not a remote resource.
	$templates = json_decode( file_get_contents( $templates_path ), true );
} else {
	$templates = null;
}

// A missing or malformed data.json must not take the whole wizard step down.
if ( ! is_array( $templates ) ) {
	$templates = [];
}

?>
<div class="bdt-wizard-step bdt-text-center" data-step="finish">

    <div class="bdt-templates-section">
		<div class="bdt-success-icon">
            <i class="dashicons dashicons-yes-alt"></i>
        </div>

        <h3><?php esc_html_e( 'Ready-to-Use Templates', 'bdthemes-prime-slider-lite' ); ?></h3>
        <p><?php esc_html_e( 'Get a head start with these professional templates. Just click on Import to add them to your site.', 'bdthemes-prime-slider-lite' ); ?></p>
        
        <div class="template-list">
            <?php foreach ( $templates as $template ) : ?>
            <?php
                $import_url = isset( $template['import_url'] ) ? BDTPS_CORE_URL . 'includes/setup-wizard/assets' . $template['import_url'] : '';
                $extension  = strtolower( pathinfo( $import_url, PATHINFO_EXTENSION ) );

                // Skip only this template. A `return` here would abort the whole
                // view, dropping the Helpful Resources and navigation markup below.
                if ( ! in_array( $extension, [ 'json', 'zip' ], true ) ) {
                    continue;
                }

                $template_title  = isset( $template['title'] ) ? $template['title'] : '';
                $thumbnail_url   = isset( $template['thumbnail'] ) ? BDTPS_CORE_URL . 'includes/setup-wizard/assets' . $template['thumbnail'] : '';
                $demo_url        = isset( $template['demo_url'] ) ? $template['demo_url'] : '';
                $extension_class = ( 'zip' === $extension ) ? 'bdt-ps-import-temp-zip' : 'bdt-ps-import-temp-json';
            ?>
                <div class="choose-template <?php echo esc_attr( $extension ); ?> <?php echo esc_attr( $extension_class ); ?>" data-import-url="<?php echo esc_url( $import_url ); ?>">
                    <div class="template-image">
                        <?php if ( $thumbnail_url ) : ?>
                            <img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="<?php echo esc_attr( $template_title ); ?>">
                        <?php endif; ?>
                        <div class="template-actions">
                            <?php if ( $demo_url ) : ?>
                                <a href="<?php echo esc_url( $demo_url ); ?>" target="_blank" rel="noopener noreferrer" class="template-preview">
                                    <i class="dashicons dashicons-visibility"></i> <?php esc_html_e( 'Preview', 'bdthemes-prime-slider-lite' ); ?>
                                </a>
                            <?php endif; ?>
                            <button type="button" class="template-import">
                                <i class="dashicons dashicons-download"></i> <?php esc_html_e( 'Import', 'bdthemes-prime-slider-lite' ); ?>
                            </button>
                        </div>
                    </div>
                    <div class="template-title"><?php echo esc_html( $template_title ); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="bdt-help-resources">
        <h3><?php esc_html_e( 'Helpful Resources', 'bdthemes-prime-slider-lite' ); ?></h3>
        
        <div class="bdt-resources-grid">
            <a href="<?php echo esc_url( 'https://bdthemes.com/knowledge-base/prime-slider/' ); ?>" target="_blank" rel="noopener noreferrer" class="bdt-resource-item">
                <div class="resource-icon">
                    <i class="dashicons dashicons-book"></i>
                </div>
                <h4><?php esc_html_e( 'Documentation', 'bdthemes-prime-slider-lite' ); ?></h4>
                <p><?php esc_html_e( 'Find detailed guides and documentation', 'bdthemes-prime-slider-lite' ); ?></p>
            </a>
            
            <a href="<?php echo esc_url( 'https://bdthemes.com/support/' ); ?>" target="_blank" rel="noopener noreferrer" class="bdt-resource-item">
                <div class="resource-icon">
                    <i class="dashicons dashicons-sos"></i>
                </div>
                <h4><?php esc_html_e( 'Get Support', 'bdthemes-prime-slider-lite' ); ?></h4>
                <p><?php esc_html_e( 'Contact our customer support team', 'bdthemes-prime-slider-lite' ); ?></p>
            </a>
            
            <a href="<?php echo esc_url( 'https://www.youtube.com/watch?v=sZwJDtxasTg&list=PLP0S85GEw7DP3-yJrkgwpIeDFoXy0PDlM' ); ?>" target="_blank" rel="noopener noreferrer" class="bdt-resource-item">
                <div class="resource-icon">
                    <i class="dashicons dashicons-video-alt3"></i>
                </div>
                <h4><?php esc_html_e( 'Video Tutorials', 'bdthemes-prime-slider-lite' ); ?></h4>
                <p><?php esc_html_e( 'Watch tutorials on our YouTube channel', 'bdthemes-prime-slider-lite' ); ?></p>
            </a>
        </div>
    </div>
    
	<div class="bdt-flex bdt-flex-between bdt-flex-wrap">
		<div class="bdt-wizard-navigation">
			<button class="bdt-button bdt-button-secondary bdt-wizard-prev" data-step="integration">
				<span><i class="dashicons dashicons-arrow-left-alt"></i></span>
				<?php esc_html_e( 'Previous Step', 'bdthemes-prime-slider-lite' ); ?>
			</button>
		</div>
	
		<div class="bdt-next-steps">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=prime_slider_options' ) ); ?>" class="bdt-button bdt-button-primary">
				<i class="dashicons dashicons-dashboard"></i>
				<?php esc_html_e( 'Go to Prime Slider Dashboard', 'bdthemes-prime-slider-lite' ); ?>
			</a>
			
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=page' ) ); ?>" class="bdt-button bdt-button-secondary">
				<i class="dashicons dashicons-edit"></i>
				<?php esc_html_e( 'Edit Your Pages', 'bdthemes-prime-slider-lite' ); ?>
			</a>
		</div>
	</div>

</div>