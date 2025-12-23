<?php

namespace WP_Syntex\Polylang\Tests\Strings;

use PLL_UnitTestCase;
use WP_Syntex\Polylang\Strings\Translator;

class Translator_Test extends PLL_UnitTestCase {
	/**
	 * Test default sanitization (uses no sanitization when user has `unfiltered_html` capability).
	 *
	 * @testWith ["<script>alert('xss')</script><p>Safe</p>"]
	 *           [""]
	 *           ["<script>alert('xss')</script>\n<p>Line 1</p>\n<p>Line 2</p>"]
	 *           ["&#60;script&#62;alert('XSS')&#60;/script&#62;"]
	 *
	 * @param string $input Input string to sanitize.
	 * @return void
	 */
	public function test_sanitize_default_with_user_having_unfiltered_html_capability( $input ) {
		wp_set_current_user( 1 );

		new Translator( 'Original String', 'test_string', 'FooContext' );

		$sanitized = apply_filters( 'pll_sanitize_string_translation', $input, 'test_string', 'FooContext', 'Original String' );

		$this->assertSame( $input, $sanitized );
	}

	/**
	 * Test default sanitization (uses wp_kses_post when user lacks `unfiltered_html` capability).
	 *
	 * @testWith ["<script>alert('xss')</script><p>Safe</p>", "alert('xss')<p>Safe</p>"]
	 *           ["", ""]
	 *           ["<script>alert('xss')</script>\n<p>Line 1</p>\n<p>Line 2</p>", "alert('xss')\n<p>Line 1</p>\n<p>Line 2</p>"]
	 *           ["A normal string", "A normal string"]
	 *
	 * @param string $input    Input string to sanitize.
	 * @param string $expected Expected sanitized output.
	 * @return void
	 */
	public function test_sanitize_default_with_user_lacking_unfiltered_html_capability( $input, $expected ) {
		wp_set_current_user( 0 );

		new Translator( 'Original String', 'test_string', 'FooContext' );

		$sanitized = apply_filters( 'pll_sanitize_string_translation', $input, 'test_string', 'FooContext', 'Original String' );

		$this->assertSame( $expected, $sanitized );
	}

	/**
	 * Test custom sanitization callbacks.
	 *
	 * @testWith ["sanitize_key", "My String With Spaces & Special Chars!", "mystringwithspacesspecialchars"]
	 *           ["sanitize_title", "My String With Spaces & Special Chars!", "my-string-with-spaces-special-chars"]
	 *
	 * @param string $callback_name Callback function name.
	 * @param string $input         Input string to sanitize.
	 * @param string $expected      Expected sanitized output.
	 * @return void
	 */
	public function test_sanitize_custom_callback( $callback_name, $input, $expected ) {
		new Translator(
			'My String',
			'my_string',
			'FooContext',
			false,
			$callback_name
		);

		$sanitized = apply_filters( 'pll_sanitize_string_translation', $input, 'my_string', 'FooContext', 'My String' );

		$this->assertSame( $expected, $sanitized );
	}

	/**
	 * Test custom sanitization with closure callback.
	 *
	 * @return void
	 */
	public function test_sanitize_custom_closure() {
		$custom_callback = function ( $string ) {
			return strtoupper( $string );
		};

		new Translator(
			'Hello World',
			'hello_world',
			'FooContext',
			false,
			$custom_callback
		);

		$input     = 'hello world';
		$sanitized = apply_filters( 'pll_sanitize_string_translation', $input, 'hello_world', 'FooContext', 'Hello World' );

		$this->assertSame( 'HELLO WORLD', $sanitized );
	}

	/**
	 * Test sanitization with non-matching parameters with user lacking `unfiltered_html` capability.
	 *
	 * @testWith ["different_name", "FooContext"]
	 *           ["test_string", "DifferentContext"]
	 *
	 * @param string $name    Name parameter.
	 * @param string $context Context parameter.
	 * @return void
	 */
	public function test_sanitize_non_matching_with_user_lacking_unfiltered_html_capability( $name, $context ) {
		wp_set_current_user( 0 );

		new Translator( 'Original String', 'test_string', 'FooContext' );

		$input     = '<script>alert("xss")</script><p>Safe</p>';
		$sanitized = apply_filters( 'pll_sanitize_string_translation', $input, $name, $context, 'Original String' );

		$this->assertSame( $input, $sanitized, 'Expected input to be unchanged when name and context do not match' );
	}

	/**
	 * Test that multiple translators with same string don't interfere.
	 *
	 * @return void
	 */
	public function test_sanitize_multiple_translators_same_string() {
		wp_set_current_user( 0 );

		// First with default sanitization.
		new Translator( 'Test String', 'test_string', 'FooContext' );

		// Second with custom sanitization for same string but different context.
		new Translator(
			'Test String',
			'test_string',
			'BazContext',
			false,
			'sanitize_key'
		);

		$input = '<script>alert("xss")</script><p>Safe</p>';

		// Should use default sanitization (wp_kses_post) for FooContext.
		$sanitized1 = apply_filters( 'pll_sanitize_string_translation', $input, 'test_string', 'FooContext', 'Test String' );
		$this->assertSame( 'alert("xss")<p>Safe</p>', $sanitized1 );

		// Should use sanitize_key for BarContext.
		$sanitized2 = apply_filters( 'pll_sanitize_string_translation', $input, 'test_string', 'BazContext', 'Test String' );
		$this->assertSame( 'scriptalertxssscriptpsafep', $sanitized2 );
	}

	/**
	 * Data provider for control character tests.
	 *
	 * @return array
	 */
	public function control_characters_provider() {
		return array(
			'null byte'     => array( "test\x00string", 'teststring' ),
			'newline'       => array( "test\nstring", "test\nstring" ),
			'tab character' => array( "test\tstring", "test\tstring" ),
		);
	}

	/**
	 * Test null byte and control character handling.
	 *
	 * @dataProvider control_characters_provider
	 *
	 * @param string $input    Input with control characters.
	 * @param string $expected Expected sanitized output.
	 * @return void
	 */
	public function test_sanitize_control_characters( $input, $expected ) {
		wp_set_current_user( 0 );

		new Translator( 'Original String', 'test_string', 'FooContext' );

		$sanitized = apply_filters( 'pll_sanitize_string_translation', $input, 'test_string', 'FooContext', 'Original String' );

		$this->assertSame( $expected, $sanitized );
	}
}
