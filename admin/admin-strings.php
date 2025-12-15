<?php
/**
 * @package Polylang
 */

use WP_Syntex\Polylang\Strings\Translator;

/**
 * A fully static class to manage strings translations on admin side
 *
 * @since 1.6
 */
class PLL_Admin_Strings {
	/**
	 * Stores the strings to translate.
	 *
	 * @var array {
	 *   @type string $name      A unique name for the string.
	 *   @type string $string    The actual string to translate.
	 *   @type string $context   The group in which the string is registered.
	 *   @type bool   $multiline Whether the string table should display a multiline textarea or a single line input.
	 * }
	 */
	protected static $strings = array();

	/**
	 * The strings to register by default.
	 *
	 * @var string[]|null
	 */
	protected static $default_strings;

	/**
	 * Register strings for translation making sure it is not duplicate or empty
	 *
	 * @since 0.6
	 *
	 * @param string $name      A unique name for the string
	 * @param string $string    The string to register
	 * @param string $context   Optional, the group in which the string is registered, defaults to 'polylang'
	 * @param bool   $multiline Optional, whether the string table should display a multiline textarea or a single line input, defaults to single line
	 * @return void
	 */
	public static function register_string( $name, $string, $context = 'Polylang', $multiline = false ) {
		if ( $string && is_scalar( $string ) ) {
			self::$strings[ md5( $string ) ] = compact( 'name', 'string', 'context', 'multiline' );
		}
	}

	/**
	 * Get registered strings
	 *
	 * @since 0.6.1
	 *
	 * @return array list of all registered strings
	 */
	public static function &get_strings() {
		self::$default_strings = array(
			'widget_title' => __( 'Widget title', 'polylang' ),
			'widget_text'  => __( 'Widget text', 'polylang' ),
		);

		global $wp_registered_widgets;
		$sidebars = wp_get_sidebars_widgets();
		foreach ( $sidebars as $sidebar => $widgets ) {
			if ( 'wp_inactive_widgets' == $sidebar || empty( $widgets ) ) {
				continue;
			}

			foreach ( $widgets as $widget ) {
				// Nothing can be done if the widget is created using pre WP2.8 API. There is no object, so we can't access it to get the widget options.
				if ( ! isset( $wp_registered_widgets[ $widget ]['callback'][0] ) || ! $wp_registered_widgets[ $widget ]['callback'][0] instanceof WP_Widget ) {
					continue;
				}

				$widget_instance = $wp_registered_widgets[ $widget ]['callback'][0];
				$widget_settings = $widget_instance->get_settings();
				$number          = $wp_registered_widgets[ $widget ]['params'][0]['number'];

				// Don't enable widget translation if the widget is visible in only one language or if there is no title.
				if ( ! empty( $widget_settings[ $number ]['pll_lang'] ) ) {
					continue;
				}

				// Widget title.
				if ( ! empty( $widget_settings[ $number ]['title'] ) ) {
					new Translator( $widget_settings[ $number ]['title'], self::$default_strings['widget_title'], 'Widget', false, 'sanitize_text_field' );
				}

				// Text of the Widget text.
				if ( ! empty( $widget_settings[ $number ]['text'] ) ) {
					new Translator( $widget_settings[ $number ]['text'], self::$default_strings['widget_text'], 'Widget', true );
				}

				// Content of the widget custom html.
				if ( $widget_instance instanceof WP_Widget_Custom_HTML && ! empty( $widget_settings[ $number ]['content'] ) ) {
					new Translator( $widget_settings[ $number ]['content'], self::$default_strings['widget_text'], 'Widget', true );
				}
			}
		}

		/**
		 * Filter the list of strings registered for translation
		 * Mainly for use by our PLL_WPML_Compat class
		 *
		 * @since 1.0.2
		 *
		 * @param array $strings list of strings
		 */
		self::$strings = apply_filters( 'pll_get_strings', self::$strings );
		return self::$strings;
	}
}
