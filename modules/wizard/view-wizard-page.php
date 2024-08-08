<?php
/**
 * Displays the wizard
 *
 * @package Polylang
 *
 * @since 2.7
 *
 * @var array[]    $steps {
 *   List of steps.
 *
 *     @type array {
 *         List of step properties.
 *
 *         @type string   $name    I18n string which names the step.
 *         @type callable $view    The callback function use to display the step content.
 *         @type callable $handler The callback function use to process the step after it is submitted.
 *         @type array    $scripts List of script handles needed by the step.
 *         @type array    $styles  The list of style handles needed by the step.
 *     }
 * }
 * @var string   $current_step Current step.
 * @var string[] $styles       List of wizard page styles.
 */

defined( 'ABSPATH' ) || exit;

$admin_body_class = array( 'pll-wizard', 'wp-core-ui' );
if ( is_rtl() ) {
	$admin_body_class[] = 'rtl';
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta name="viewport" content="width=device-width" />
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>
		<?php
		printf(
			/* translators: %s is the plugin name */
			esc_html__( '%s &rsaquo; Setup', 'polylang' ),
			esc_html( POLYLANG )
		);
		?>
		</title>
		<script>
			var ajaxurl = '<?php echo esc_url( admin_url( 'admin-ajax.php', 'relative' ) ); ?>';
		</script>
		<?php wp_print_scripts( $steps[ $current_step ]['scripts'] ); ?>
		<?php wp_print_styles( array_merge( $styles, $steps[ $current_step ]['styles'] ) ); ?>
		<?php do_action( 'admin_head' ); ?>
	</head>
	<body class="<?php echo join( ' ', array_map( 'sanitize_key', $admin_body_class ) ); ?>">
		<h1 id="pll-logo">
			<a href="https://polylang.pro/" class="title">
				<span><img src="<?php echo esc_url( plugins_url( '/modules/wizard/images/polylang-logo.png', POLYLANG_FILE ) ); ?>" /></span>
				<?php echo esc_html( POLYLANG ); ?>
			</a>
		</h1>
		<ol class="pll-wizard-steps">
			<?php
			foreach ( $steps as $step_key => $step ) {
				$is_completed = array_search( $current_step, array_keys( $steps ), true ) > array_search( $step_key, array_keys( $steps ), true );

				if ( $step_key === $current_step ) {
					?>
					<li class="active"><?php echo esc_html( $step['name'] ); ?></li>
					<?php
				} elseif ( $is_completed ) {
					?>
					<li class="done">
						<a
							href="<?php echo esc_url( add_query_arg( 'step', $step_key, remove_query_arg( 'activate_error' ) ) ); ?>"
						>
							<?php echo esc_html( $step['name'] ); ?>
						</a>
					</li>
					<?php
				} else {
					?>
					<li><?php echo esc_html( $step['name'] ); ?></li>
					<?php
				}
			}
			?>
		</ol>
		<div class="pll-wizard-content">
			<form method="post" class="<?php echo esc_attr( "{$current_step}-step" ); ?>">
				<?php
				wp_nonce_field( 'pll-wizard', '_pll_nonce' );

				if ( ! empty( $steps[ $current_step ]['view'] ) ) {
					call_user_func( $steps[ $current_step ]['view'] );
				}
				?>
				<?php if ( 'last' !== $current_step ) : ?>
				<p class="pll-wizard-actions step">
					<button
						type="submit"
						class="button-primary button button-large button-next"
						value="continue"
						name="save_step"
					>
						<?php esc_html_e( 'Continue', 'polylang' ); ?><span class="dashicons dashicons-arrow-right-alt2"></span>
					</button>
				</p>
				<?php endif; ?>
			</form>
		</div>
		<div class="pll-wizard-footer">
			<?php if ( 'last' !== $current_step ) : ?>
				<a
					class="pll-wizard-footer-links"
					href="<?php echo esc_url( admin_url() ); ?>"
				>
					<?php esc_html_e( 'Not right now', 'polylang' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</body>
</html>
