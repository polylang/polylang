<?php
/**
 * @package Polylang-Pro
 *
 * @param string $message Can contain `<br>` and `<code>` tags.
 * @param string $slug
 * @param string $type    Optional. Possible values are `success`, `warning`, `error`, and `info`. Default is `error`.
 */

defined( 'ABSPATH' ) || exit;

$tags = array(
	'br'   => true,
	'code' => true,
);

$atts['type'] = ! empty( $atts['type'] ) && in_array( $atts['type'], array( 'success', 'warning', 'info' ), true ) ? $atts['type'] : 'error';
?>
<div class="pll-<?php echo esc_attr( $atts['slug'] ); ?>-notice pll-inner-notice notice-<?php echo esc_attr( $atts['type'] ); ?>">
	<p><strong><?php echo wp_kses( $atts['message'], $tags ); ?></strong></p>
</div>
