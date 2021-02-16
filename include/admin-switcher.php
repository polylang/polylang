<?php
/**
 * @package Polylang
 */

/**
 * A class to display a language switcher on Admin
 *
 * @since 3.0
 */
class PLL_Admin_Switcher extends PLL_Switcher {

	/**
	 * Displays a language switcher
	 * or returns the raw elements to build a custom language switcher.
	 *
	 * @since 0.1
	 *
	 * @param PLL_Links $links
	 * @param array     $args {
	 *   Optional array of arguments.
	 *
	 *   @type int    $dropdown               The list is displayed as dropdown if set, defaults to 0.
	 *   @type int    $echo                   Echoes the list if set to 1, defaults to 1.
	 *   @type int    $show_flags             Displays flags if set to 1, defaults to 0.
	 *   @type int    $show_names             Shows language names if set to 1, defaults to 1.
	 *   @type string $display_names_as       Whether to display the language name or its slug, valid options are 'slug' and 'name', defaults to name.
	 *   @type int    $hide_current           Hides the current language if set to 1, defaults to 0.
	 *   @type int    $post_id                Returns links to the translations of the post defined by post_id if set, defaults not set.
	 *   @type int    $raw                    Return a raw array instead of html markup if set to 1, defaults to 0.
	 *   @type string $item_spacing           Whether to preserve or discard whitespace between list items, valid options are 'preserve' and 'discard', defaults to 'preserve'.
	 * }
	 * @return string|array either the html markup of the switcher or the raw elements to build a custom language switcher
	 */
	public function the_languages( $links, $args = array() ) {
		$args['hide_if_empty'] = 0;

		list( $out, $args ) = $this->get_the_languages( $links, $args );

		if ( $args['echo'] ) {
			echo $out; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		return $out;
	}

	/**
	 * @param string[]     $classes CSS classes, without the leading '.'.
	 * @param array        $args {@see PLL_Admin_Switcher::the_languages()}.
	 * @param PLL_Links    $links
	 * @param PLL_Language $language
	 * @param string       $url
	 *
	 * @return array
	 */
	public function manage_url( $classes, $args, $links, $language, $url ) {
		$no_translation = false;
		$classes[] = 'no-translation';

		$url = $links->get_home_url( $language );

		return array( $url, $no_translation, $classes );
	}

	/**
	 * @param PLL_Links $links
	 *
	 * @return string
	 */
	public function get_current_language( $links ) {
		return $links->options['default_lang'];
	}
}
