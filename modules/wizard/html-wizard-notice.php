<?php

/**
 * Displays the wizard notice content
 *
 * @since 2.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
};

$wizard_url = add_query_arg(
	array(
		'page' => 'mlang_wizard',
	),
	admin_url( 'admin.php' )
);
?>
<p>
	<strong>
	<?php
	printf(
		/* translators: %s is the plugin name */
		esc_html__( 'Welcome to %s', 'polylang' ),
		esc_html( POLYLANG )
	);
	?>
	</strong>
	<?php
	echo ' &#8211; ';
	esc_html_e( 'You&lsquo;re almost ready to translate your contents!', 'polylang' );
	?>
</p>
<p class="buttons">
	<a
		href="<?php echo esc_url( $wizard_url ); ?>"
		class="button button-primary"
	>
		<?php esc_html_e( 'Run the Setup Wizard', 'polylang' ); ?>
	</a>
	<a
		class="button button-secondary skip"
		href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'pll-hide-notice', 'wizard' ), 'wizard', '_pll_notice_nonce' ) ); ?>"
	>
		<?php esc_html_e( 'Skip setup', 'polylang' ); ?>
	</a>
</p>
