<?php
/**
 * @package Polylang-Pro
 *
 * @param string $option
 * @param string $id
 * @param array  $data Optional.
 */

defined( 'ABSPATH' ) || exit;

printf(
	'<input id="pll-%s" name="%s[%s]" type="password" value="%s" class="regular-text code" %s/>',
	esc_attr( $atts['id'] ),
	esc_attr( $this->get_input_base_name() ),
	esc_attr( $atts['option'] ),
	esc_attr( $this->get_option( $atts['option'] ) ),
	$this->build_attributes( $atts['data'] ?? array(), 'data-' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
);
