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
	 * @param PLL_Frontend_Links $links Instance of PLL_Frontend_Links.
	 * @param array              $args {
	 *   Optional array of arguments.
	 *
	 *   @type int    $dropdown               The list is displayed as dropdown if set, defaults to 0.
	 *   @type int    $echo                   Echoes the list if set to 1, defaults to 1.
	 *   @type int    $hide_if_empty          Hides languages with no posts ( or pages ) if set to 1, defaults to 1.
	 *   @type int    $show_flags             Displays flags if set to 1, defaults to 0.
	 *   @type int    $show_names             Shows language names if set to 1, defaults to 1.
	 *   @type string $display_names_as       Whether to display the language name or its slug, valid options are 'slug' and 'name', defaults to name.
	 *   @type int    $force_home             Will always link to home in translated language if set to 1, defaults to 0.
	 *   @type int    $hide_if_no_translation Hides the link if there is no translation if set to 1, defaults to 0.
	 *   @type int    $hide_current           Hides the current language if set to 1, defaults to 0.
	 *   @type int    $post_id                Returns links to the translations of the post defined by post_id if set, defaults not set.
	 *   @type int    $raw                    Return a raw array instead of html markup if set to 1, defaults to 0.
	 *   @type string $item_spacing           Whether to preserve or discard whitespace between list items, valid options are 'preserve' and 'discard', defaults to 'preserve'.
	 *   @type string $admin_current_lang     The current language code in an admin context. Need to set the admin_render to 1, defaults not set.
	 * }
	 * @return string|array either the html markup of the switcher or the raw elements to build a custom language switcher
	 */
	public function the_languages( $links, $args = array() ) {
		list( $out, $args ) = $this->get_the_languages( $links, $args );

		if ( $args['echo'] ) {
			echo $out; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		return $out;
	}

	/**
	 * @param array              $classes
	 * @param array              $args
	 * @param PLL_Frontend_Links $links
	 * @param PLL_Language       $language
	 * @param string             $url
	 *
	 * @return array|mixed
	 */
	public function manage_url( $classes, $args, $links, $language, $url ) {
		$no_translation = false;
		$classes[] = 'no-translation';

		// If the page is not translated, link to the home page
		$url = $links->get_home_url( $language );

		return array( $url, $no_translation, $classes );
	}

	/**
	 * @param PLL_Frontend_Links $links
	 *
	 * @return mixed
	 */
	public function get_current_language( $links ) {
		return $links->options['default_lang'];
	}

	/**
	 * @param PLL_Frontend_Links $links
	 * @param array              $args
	 *
	 * @return mixed
	 */
	public function get_languages_list( $links, $args ) {
		return $links->model->get_languages_list();
	}
}
