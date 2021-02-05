<?php


class PLL_Frontend_Switcher extends PLL_Switcher {

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
	 * }
	 * @return string|array either the html markup of the switcher or the raw elements to build a custom language switcher
	 */
	public function the_languages( $links, $args = array() ) {
		list( $out, $args ) = $this->get_the_languages( $links, $args );

		// Javascript to switch the language when using a dropdown list
		if ( $args['dropdown'] ) {
			// Accept only few valid characters for the urls_x variable name ( as the widget id includes '-' which is invalid )
			$out .= sprintf(
				'<script type="text/javascript">
					//<![CDATA[
					var %1$s = %2$s;
					document.getElementById( "%3$s" ).onchange = function() {
						location.href = %1$s[this.value];
					}
					//]]>
				</script>',
				'urls_' . preg_replace( '#[^a-zA-Z0-9]#', '', $args['dropdown'] ),
				wp_json_encode( wp_list_pluck( $elements, 'url' ) ),
				esc_js( $args['name'] )
			);
		}

		if ( $args['echo'] ) {
			echo $out; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		return $out;
	}

	/**
	 * @param $classes
	 * @param $args
	 * @param $links
	 * @param $language
	 * @param $slug
	 *
	 * @return false|array
	 */
	public function manage_url( $classes, $args, $links, $language, $url ) {
		if ( null !== $args['post_id'] && ( $tr_id = $links->model->post->get( $args['post_id'], $language ) ) && $links->model->post->current_user_can_read( $tr_id ) ) {
			$url = get_permalink( $tr_id );
		} elseif ( null === $args['post_id'] ) {
			$url = $links->get_translation_url( $language );
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

		// If the page is not translated, link to the home page
		$url = empty( $url ) || $args['force_home'] ? $links->get_home_url( $language ) : $url;

		return array( $url, $no_translation, $classes );
	}

	/**
	 * @param $links
	 *
	 * @return mixed
	 */
	public function get_current_language( $links ) {
		return $links->curlang->slug;
	}

	/**
	 * @param $links
	 * @param $args
	 *
	 * @return mixed
	 */
	public function get_languages_list( $links, $args ) {
		return $links->model->get_languages_list( array( 'hide_empty' => $args['hide_if_empty'] ) );
	}
}
