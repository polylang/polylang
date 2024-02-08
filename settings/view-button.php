<?php
/**
 * @package Polylang-Pro
 *
 * @param string $label
 * @param string $id    Optional.
 * @param string $class Optional.
 * @param array  $data  Optional.
 */

defined( 'ABSPATH' ) || exit;

printf(
	'<button %s class="button button-secondary %s" type="button" %s>%s</button>',
	! empty( $atts['id'] ) ? sprintf( 'id="pll-%s"', esc_attr( $atts['id'] ) ) : '',
	esc_attr( $atts['class'] ?? '' ),
	$this->build_attributes( $atts['data'] ?? array(), 'data-' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	esc_html( $atts['label'] )
);
