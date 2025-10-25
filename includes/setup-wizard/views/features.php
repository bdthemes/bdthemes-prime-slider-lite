<?php
/**
 * Integration Step
 */

namespace PrimeSlider\SetupWizard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


$widget_map     = \PrimeSlider\Includes\Setup_Wizard::get_widget_map();
$active_modules = get_option( 'prime_slider_active_modules', array() );


?>

<div class="bdt-wizard-step bdt-setup-wizard-features" data-step="features">
	<!-- <h2>Choose your features</h2>
	<p>You may enable the widgets and extensions you need for your current project while keeping others turned off.</p> -->
	<form method="post" action="admin-ajax.php?action=prime_slider_settings_save" id="ps_setup_wizard_modules">
		<input type="hidden" name="_wp_http_referer" value="/wp-admin/admin.php?page=prime_slider_options">
		<input type="hidden" name="id" value="prime_slider_active_modules">
		<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'prime-slider-settings-save-nonce' ) ); ?>">
		<input type="hidden" name="action" value="prime_slider_settings_save">

		<div class="bdt-features-list">
			<div class="widget-filter bdt-flex bdt-flex-wrap bdt-flex-between bdt-flex-middle">
				<div class="category-dropdown">
					<label for="category-select"><?php esc_html_e('Filter by:', 'bdthemes-prime-slider'); ?></label>
					<select id="category-select">
						<option value="all"><?php esc_html_e('All', 'bdthemes-prime-slider'); ?></option>
						<option value="new"><?php esc_html_e('New', 'bdthemes-prime-slider'); ?></option>
						<option value="static"><?php esc_html_e('Static', 'bdthemes-prime-slider'); ?></option>
						<option value="custom"><?php esc_html_e('Custom', 'bdthemes-prime-slider'); ?></option>
						<option value="carousel"><?php esc_html_e('Carousel', 'bdthemes-prime-slider'); ?></option>
						<option value="post"><?php esc_html_e('Post', 'bdthemes-prime-slider'); ?></option>
						<option value="ecommerce"><?php esc_html_e('eCommerce', 'bdthemes-prime-slider'); ?></option>
						<option value="swiper"><?php esc_html_e('Swiper', 'bdthemes-prime-slider'); ?></option>
						<option value="others"><?php esc_html_e('Others', 'bdthemes-prime-slider'); ?></option>
					</select>
				</div>
				<div class="input-btn-wrap bdt-flex bdt-flex-wrap bdt-flex-between">
					<input type="text" placeholder="<?php esc_attr_e('Search widgets...', 'bdthemes-prime-slider'); ?>" class="widget-search" value="">
					<div class="bulk-action-buttons bdt-flex">
						<button class="bulk-action activate"><?php esc_html_e('Activate All', 'bdthemes-prime-slider'); ?></button>
						<button class="bulk-action deactivate"><?php esc_html_e('Deactivate All', 'bdthemes-prime-slider'); ?></button>
					</div>
				</div>
			</div>
			
			<div class="widget-list-container">
				<ul class="widget-list">
					<?php foreach ( $widget_map as $widget ) : ?>
						<?php
						$is_checked = isset( $active_modules[ $widget['name'] ] ) && 'on' === $active_modules[ $widget['name'] ] ? 'checked' : '';

						$pro_class = '';
						if (!empty($widget['widget_type']) && 'pro' == $widget['widget_type'] && true !== _is_ps_pro_activated()) {
							$pro_class = ' ps-setup-wizard-pro-widget';
						}
						?>
						<li class="<?php echo esc_attr( $widget['widget_type'] . $pro_class ); ?>"
							data-type="<?php echo isset( $widget['content_type'] ) ? esc_attr( $widget['content_type'] ) : ''; ?>"
							data-label="<?php echo esc_attr( strtolower( $widget['label'] ) ); ?>">
							<div class="widget-item-clickable bdt-flex bdt-flex-middle bdt-flex-between">
								<span class="bdt-flex bdt-text-left"><?php echo esc_html( $widget['label'] ); ?></span>
								<label class="switch">
									<input type="hidden" name="prime_slider_active_modules[<?php echo esc_attr( $widget['name'] ); ?>]" value="off">
									<input type="checkbox" name="prime_slider_active_modules[<?php echo esc_attr( $widget['name'] ); ?>]" <?php echo esc_html( $is_checked ); ?> value="on" class="checkbox" id="bdt_ps_prime_slider_active_modules[<?php echo esc_attr( $widget['name'] ); ?>]">
									<span class="slider"></span>
								</label>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
		
		<div class="wizard-navigation bdt-margin-top">
			<button class="bdt-button bdt-button-primary" type="submit" id="save-and-continue">
				<?php esc_html_e('Save and Continue', 'bdthemes-prime-slider'); ?>
			</button>
			<div class="bdt-close-button bdt-margin-left bdt-wizard-next" data-step="integration"><?php esc_html_e('Skip', 'bdthemes-prime-slider'); ?></div>
		</div>
	</form>

	<div class="bdt-wizard-navigation">
		<button class="bdt-button bdt-button-secondary bdt-wizard-prev" data-step="welcome">
			<span><i class="dashicons dashicons-arrow-left-alt"></i></span>
			<?php esc_html_e( 'Previous Step', 'bdthemes-prime-slider' ); ?>
		</button>
	</div>
</div>

