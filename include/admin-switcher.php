<?php


class PLL_Admin_Switcher extends PLL_Switcher {

	/**
	 * Get the language elements for use in a walker
	 *
	 * @since 1.2
	 *
	 * @param PLL_Frontend_Links $links Instance of PLL_Frontend_Links.
	 * @param array              $args  Arguments passed to {@see PLL_Switcher::the_languages()}.
	 * @return array Language switcher elements.
	 */
	protected function get_elements( $links, $args ) {
		$first = true;
		$out   = array();

		foreach ( $links->model->get_languages_list( array( 'hide_empty' => $args['hide_if_empty'] ) ) as $language ) {
			list( $id, $order, $slug, $locale, $classes, $url ) = $this->init_foreach_language( $language );

			$curlang = $args['admin_current_lang'];

			$current_lang = $curlang == $slug;

			if ( $this->manage_current_lang_display( $classes, $current_lang, $args, $language, $first ) ) {
				list( $classes, $name, $flag, $first ) = $this->manage_current_lang_display( $classes, $current_lang, $args, $language, $first );
			} else {
				continue;
			}

			$out[ $slug ] = compact( 'id', 'order', 'slug', 'locale', 'name', 'url', 'flag', 'current_lang', 'classes' );
		}

		return $out;
	}

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
		$defaults = $this->get_default_the_languages();

		$args = $this->filter_arguments_pll_languages( $args, $defaults );

		$elements = $this->get_elements( $links, $args );

		if ( $args['raw'] ) {
			return $elements;
		}

		list( $out, $args ) = $this->prepare_pll_walker( $args['admin_current_lang'], $args, $elements );

		if ( $args['echo'] ) {
			echo $out; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		return $out;
	}

}
