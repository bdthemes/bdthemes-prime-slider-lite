<?php
/**
 * Newsletter opt-in section
 *
 * Rendered inside the welcome step, below the feature grid. The checkbox is
 * unticked by default: nothing is sent unless the administrator opts in.
 * Submitted when the user moves on with "Get Started" — see subscribeSubmit()
 * in the setup wizard script.
 */

namespace PrimeSlider\SetupWizard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Template partial: the variables below are file-scoped for this view, not
// plugin globals, so the global-prefix rule does not apply.
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View-local variables.

$current_user = wp_get_current_user();

$subscribe_email = '';
if ( $current_user && is_email( $current_user->user_email ) ) {
	$subscribe_email = $current_user->user_email;
} else {
	$subscribe_email = get_option( 'admin_email' );
}

// Opt-in only: the box is ticked solely when the administrator opted in before.
$subscribe_optin = 'yes' === get_option( 'bdtps_subscribe_optin' );

?>

<div class="bdt-setup-wizard-subscribe">

	<div class="bdt-subscribe-card">

		<div class="bdt-subscribe-card-head">
			<span class="bdt-subscribe-guarantee">
				<i class="dashicons dashicons-shield-alt"></i>
				<?php esc_html_e( 'NO SPAM GUARANTEE', 'bdthemes-prime-slider-lite' ); ?>
			</span>
		</div>

		<div class="bdt-subscribe-field">
			<input type="email"
				id="bdt-subscribe-email"
				class="bdt-subscribe-email"
				name="bdt_subscribe_email"
				autocomplete="email"
				placeholder="<?php esc_attr_e( 'Enter your email address', 'bdthemes-prime-slider-lite' ); ?>"
				value="<?php echo esc_attr( $subscribe_email ); ?>">
		</div>

		<div class="bdt-subscribe-consent">
			<label class="bdt-subscribe-consent-label" for="bdt-subscribe-consent">
				<input type="checkbox"
					id="bdt-subscribe-consent"
					class="bdt-subscribe-consent"
					value="yes"
					<?php checked( $subscribe_optin ); ?>>
				<span>
					<?php esc_html_e( 'Email me the BdThemes newsletter: product news, tips and security notices. You can unsubscribe at any time.', 'bdthemes-prime-slider-lite' ); ?>
				</span>
			</label>
		</div>

	</div>

</div>
