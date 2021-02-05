<?php


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
		$defaults = $this->get_default_the_languages();

		$args = $this->filter_arguments_pll_languages( $args, $defaults );

		$args['hide_if_empty'] = 0;
		// Force not to hide the language for the block preview even if the option is checked.
		$args['hide_if_no_translation'] = 0;

		$elements = $this->get_elements( $links, $args );

		if ( $args['raw'] ) {
			return $elements;
		}

		list( $out, $args ) = $this->prepare_pll_walker( $this->get_current_language( $links ), $args, $elements );

		if ( $args['echo'] ) {
			echo $out; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		return $out;
	}

	private function manage_url( $classes, $args, $links, $language, $url ) {
		if ( null !== $args['post_id'] && ( $tr_id = $links->model->post->get( $args['post_id'], $language ) ) && $links->model->post->current_user_can_read( $tr_id ) ) {
			$url = get_permalink( $tr_id );
		}

		if ( $no_translation = empty( $url ) ) {
			$classes[] = 'no-translation';
		}

		/**
		 * Filter the link in the language switcher
		 *
		 * @since 0.7
		 *
		 * @param string $url    the link
		 * @param string $slug   language code
		 * @param string $locale language locale
		 */
		$url = apply_filters( 'pll_the_language_link', $url, $language->slug, $language->locale );

		// Hide if no translation exists
		if ( empty( $url ) && $args['hide_if_no_translation'] ) {
			return false;
		}

		$url = empty( $url ) || $args['force_home'] ? $links->get_home_url( $language ) : $url; // If the page is not translated, link to the home page

		return array( $url, $no_translation, $classes );
	}

	/**
	 * @param $links
	 *
	 * @return mixed
	 */
	protected function get_current_language( $links ) {
		return $links->options['default_lang'];
	}

}
