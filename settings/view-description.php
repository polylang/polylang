<?php
/**
 * @package Polylang-Pro
 *
 * @param string $message Can contain `<a>`, `<code>`, and `<span>` tags.
 * @param string $class   Optional.
 */

defined( 'ABSPATH' ) || exit;

$tags = array(
	'a'    => array(
		'href' => true,
	),
	'code' => true,
	'span' => array(
		'class'       => true,
		'aria-hidden' => true,
	),
);
printf(
	'<p class="description %s">%s</p>',
	esc_attr( $atts['class'] ?? '' ),
	wp_kses( $atts['message'], $tags )
);
