<?php
/**
 * Displays the wizard licenses step
 *
 * @package Polylang
 *
 * @since 2.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
};

$licenses = apply_filters( 'pll_settings_licenses', array() );
$is_error = isset( $_GET['activate_error'] ) && 'i18n_license_key_error' === sanitize_key( $_GET['activate_error'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>
<p>
	<?php esc_html_e( 'You are using plugins which require a license key.', 'polylang' ); ?>
	<?php echo esc_html( _n( 'Please enter your license key:', 'Please enter your license keys:', count( $licenses ), 'polylang' ) ); ?>
</p>
<h2><?php esc_html_e( 'Licenses', 'polylang' ); ?></h2>
<div id="messages">
	<?php if ( $is_error ) : ?>
		<p class="error"><?php esc_html_e( 'There is an error with a license key.', 'polylang' ); ?></p>
	<?php endif; ?>
</div>
<div class="form-field">
	<table id="pll-licenses-table" class="form-table">
		<tbody>
		<?php
		foreach ( $licenses as $license ) {
			// Escaping is already done in get_form_field method.
			echo $license->get_form_field(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
		</tbody>
	</table>
</div>
