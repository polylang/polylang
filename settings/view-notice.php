<?php
/**
 * @package Polylang-Pro
 *
 * @param string $message Can contain `<br>` and `<code>` tags.
 * @param string $slug
 */

defined( 'ABSPATH' ) || exit;

$tags = array(
	'br'   => true,
	'code' => true,
);
?>
<div class="pll-<?php echo esc_attr( $atts['slug'] ); ?>-notice pll-inner-notice notice-error">
	<p><strong><?php echo wp_kses( $atts['message'], $tags ); ?></strong></p>
</div>
