<?php
/**
 * @package Polylang-Pro
 *
 * @param string $option
 * @param string $id
 * @param array  $values
 */

defined( 'ABSPATH' ) || exit;

printf(
	'<select id="pll-%s" name="%s[%s]" %s>',
	esc_attr( $atts['id'] ),
	esc_attr( $this->get_input_base_name() ),
	esc_attr( $atts['option'] ),
	$this->build_attributes( $atts['data'] ?? array(), 'data-' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);

foreach ( $atts['values'] as $value => $label ) {
	printf(
		'<option value="%s" %s>%s</option>',
		esc_attr( (string) $value ),
		selected( $this->get_option( $atts['option'] ), $value, false ),
		esc_html( $label )
	);
}

echo '</select>';
