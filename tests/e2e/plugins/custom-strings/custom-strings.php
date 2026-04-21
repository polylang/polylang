<?php
/**
 * Plugin Name: Custom Strings E2E
 * Description: Registers Polylang strings for E2E tests and appends them to post content on the frontend.
 * Version: 0.1.0
 * License: GPL-2.0-or-later
 *
 * @package Polylang-E2E
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'init',
	function () {
		pll_register_string(
			'e2e_custom_greeting',
			'Hello Polylang E2E',
			'Polylang E2E',
			false
		);

		pll_register_string(
			'e2e_custom_multiline',
			"Line one\nLine two",
			'Polylang E2E',
			true
		);
	},
	20
);

add_filter(
	'the_content',
	function ( $content ) {
		$single = sprintf(
			'<p class="pll-e2e-custom-string">%s</p>',
			pll__( 'Hello Polylang E2E' )
		);

		$multiline = sprintf(
			'<div class="pll-e2e-custom-string-multiline">%s</div>',
			pll__( "Line one\nLine two" )
		);

		return $content . $single . $multiline;
	},
	20
);
