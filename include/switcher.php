<?php
/**
 * @package Polylang
 */

/**
 * A class to display a language switcher on frontend
 *
 * @since 1.2
 */
class PLL_Switcher {
	const DEFAULTS = array(
		'dropdown'               => 0, // Display as list and not as dropdown.
		'echo'                   => 1, // Echoes the list.
		'hide_if_empty'          => 1, // Hides languages with no posts (or pages).
		'show_flags'             => 0, // Don't show flags.
		'show_names'             => 1, // Show language names.
		'display_names_as'       => 'name', // Display the language name.
		'force_home'             => 0, // Tries to find a translation.
		'hide_if_no_translation' => 0, // Don't hide the link if there is no translation.
		'hide_current'           => 0, // Don't hide the current language.
		'post_id'                => null, // Link to the translations of the current page.
		'raw'                    => 0, // Build the language switcher.
		'item_spacing'           => 'preserve', // Preserve whitespace between list items.
		'admin_render'           => 0, // Make the switcher in a frontend context.
		'admin_current_lang'     => null, // Use the global current language.
	);

	/**
	 * @var PLL_Links|null
	 */
	protected $links;

	/**
	 * Returns options available for the language switcher - menu or widget
	 * either strings to display the options or default values
	 *
	 * @since 0.7
	 *
	 * @param string $type optional either 'menu', 'widget' or 'block', defaults to 'widget'
	 * @param string $key  optional either 'string' or 'default', defaults to 'string'
	 * @return array list of switcher options strings or default values
	 */
	public static function get_switcher_options( $type = 'widget', $key = 'string' ) {
		$options = array(
			'dropdown'               => array( 'string' => __( 'Displays as a dropdown', 'polylang' ), 'default' => 0 ),
			'show_names'             => array( 'string' => __( 'Displays language names', 'polylang' ), 'default' => 1 ),
			'show_flags'             => array( 'string' => __( 'Displays flags', 'polylang' ), 'default' => 0 ),
			'force_home'             => array( 'string' => __( 'Forces link to front page', 'polylang' ), 'default' => 0 ),
			'hide_current'           => array( 'string' => __( 'Hides the current language', 'polylang' ), 'default' => 0 ),
			'hide_if_no_translation' => array( 'string' => __( 'Hides languages with no translation', 'polylang' ), 'default' => 0 ),
		);
		return wp_list_pluck( $options, $key );
	}

	/**
	 * Returns the current language code.
	 *
	 * @since 3.0
	 *
	 * @param array $args Arguments passed to {@see PLL_Switcher::the_languages()}.
	 * @return string
	 */
	protected function get_current_language( $args ) {
		if ( $args['admin_current_lang'] ) {
			return $args['admin_current_lang'];
		}

		if ( isset( $this->links->curlang ) ) {
			return $this->links->curlang->slug;
		}

		return $this->links->options['default_lang'];
	}

	/**
	 * Returns the link for a given language.
	 *
	 * @since 3.0
	 *
	 * @param PLL_Language $language Language.
	 * @param array        $args     Arguments passed to {@see PLL_Switcher::the_languages()}.
	 * @return string|null
	 */
	protected function get_link( $language, $args ) {
		global $post;

		// Priority to the post passed in parameters.
		if ( null !== $args['post_id'] ) {
			$tr_id = $this->links->model->post->get( $args['post_id'], $language );
			if ( $tr_id && $this->links->model->post->current_user_can_read( $tr_id ) ) {
				return get_permalink( $tr_id );
			}
		}

		// If we are on frontend.
		if ( $this->links instanceof PLL_Frontend_Links ) {
			return $this->links->get_translation_url( $language );
		}

		// For blocks in posts in REST requests.
		if ( $post instanceof WP_Post ) {
			$tr_id = $this->links->model->post->get( $post->ID, $language );
			if ( $tr_id && $this->links->model->post->current_user_can_read( $tr_id ) ) {
				return get_permalink( $tr_id );
			}
		}

		return null;
	}

	/**
	 * Get the language elements for use in a walker
	 *
	 * @since 1.2
	 *
	 * @param array $args  Arguments passed to {@see PLL_Switcher::the_languages()}.
	 * @return array Language switcher elements.
	 */
	protected function get_elements( $args ) {
		$first = true;
		$out   = array();

		foreach ( $this->links->model->get_languages_list( array( 'hide_empty' => $args['hide_if_empty'] ) ) as $language ) {
			$id = (int) $language->term_id;
			$order = (int) $language->term_group;
			$slug = $language->slug;
			$locale = $language->get_locale( 'display' );
			$item_classes = array( 'lang-item', 'lang-item-' . $id, 'lang-item-' . esc_attr( $slug ) );
			$classes = isset( $args['classes'] ) && is_array( $args['classes'] ) ?
				array_merge(
					$item_classes,
					$args['classes']
				) :
				$item_classes;
			$link_classes = isset( $args['link_classes'] ) ? $args['link_classes'] : array();
			$current_lang = $this->get_current_language( $args ) === $slug;

			if ( $current_lang ) {
				if ( $args['hide_current'] && ! ( $args['dropdown'] && ! $args['raw'] ) ) {
					continue; // Hide current language except for dropdown
				} else {
					$classes[] = 'current-lang';
				}
			}

			$url = $this->get_link( $language, $args );

			if ( $no_translation = empty( $url ) ) {
				$classes[] = 'no-translation';
			}

			/**
			 * Filter the link in the language switcher
			 *
			 * @since 0.7
			 *
			 * @param string|null $url    The link, null if no translation was found.
			 * @param string      $slug   The language code.
			 * @param string      $locale The language locale
			 */
			$url = apply_filters( 'pll_the_language_link', $url, $slug, $language->locale );

			// Hide if no translation exists
			if ( empty( $url ) && $args['hide_if_no_translation'] ) {
				continue;
			}

			$url = empty( $url ) || $args['force_home'] ? $this->links->get_home_url( $language ) : $url; // If the page is not translated, link to the home page

			$name = $args['show_names'] || ! $args['show_flags'] || $args['raw'] ? ( 'slug' == $args['display_names_as'] ? $slug : $language->name ) : '';

			if ( $args['raw'] && ! $args['show_flags'] ) {
				$flag = $language->get_display_flag_url();
			} elseif ( $args['show_flags'] ) {
				$flag = $language->get_display_flag( empty( $args['show_names'] ) ? 'alt' : 'no-alt' );
			} else {
				$flag = '';
			}

			if ( $first ) {
				$classes[] = 'lang-item-first';
				$first = false;
			}

			$out[ $slug ] = compact( 'id', 'order', 'slug', 'locale', 'name', 'url', 'flag', 'current_lang', 'no_translation', 'classes', 'link_classes' );
		}

		return $out;
	}

	/**
	 * Displays a language switcher
	 * or returns the raw elements to build a custom language switcher.
	 *
	 * @since 0.1
	 *
	 * @param PLL_Links $links Instance of PLL_Links.
	 * @param array     $args {
	 *   Optional array of arguments.
	 *
	 *   @type int      $dropdown               The list is displayed as dropdown if set, defaults to 0.
	 *   @type int      $echo                   Echoes the list if set to 1, defaults to 1.
	 *   @type int      $hide_if_empty          Hides languages with no posts ( or pages ) if set to 1, defaults to 1.
	 *   @type int      $show_flags             Displays flags if set to 1, defaults to 0.
	 *   @type int      $show_names             Shows language names if set to 1, defaults to 1.
	 *   @type string   $display_names_as       Whether to display the language name or its slug, valid options are 'slug' and 'name', defaults to name.
	 *   @type int      $force_home             Will always link to home in translated language if set to 1, defaults to 0.
	 *   @type int      $hide_if_no_translation Hides the link if there is no translation if set to 1, defaults to 0.
	 *   @type int      $hide_current           Hides the current language if set to 1, defaults to 0.
	 *   @type int      $post_id                Returns links to the translations of the post defined by post_id if set, defaults not set.
	 *   @type int      $raw                    Return a raw array instead of html markup if set to 1, defaults to 0.
	 *   @type string   $item_spacing           Whether to preserve or discard whitespace between list items, valid options are 'preserve' and 'discard', defaults to 'preserve'.
	 *   @type int      $admin_render           Allows to force the current language code in an admin context if set, default to 0. Need to set the admin_current_lang argument below.
	 *   @type string   $admin_current_lang     The current language code in an admin context. Need to set the admin_render to 1, defaults not set.
	 *   @type string[] $classes                A list of CSS classes to set to each elements outputted.
	 *   @type string[] $link_classes           A list of CSS classes to set to each link outputted.
	 * }
	 * @return string|array either the html markup of the switcher or the raw elements to build a custom language switcher
	 */
	public function the_languages( $links, $args = array() ) {

		$this->links = $links;
		$args = wp_parse_args( $args, self::DEFAULTS );

		/**
		 * Filter the arguments of the 'pll_the_languages' template tag
		 *
		 * @since 1.5
		 *
		 * @param array $args
		 */
		$args = apply_filters( 'pll_the_languages_args', $args );

		// Force not to hide the language for the widget preview even if the option is checked.
		if ( $this->links instanceof PLL_Admin_Links ) {
			$args['hide_if_no_translation'] = 0;
		}

		// Prevents showing empty options in `<select>`.
		if ( $args['dropdown'] && ! $args['raw'] ) {
			$args['show_names'] = 1;
		}

		$elements = $this->get_elements( $args );

		if ( $args['raw'] ) {
			return $elements;
		}

		if ( $args['dropdown'] ) {
			$args['name'] = 'lang_choice_' . $args['dropdown'];
			$args['class'] = 'pll-switcher-select';
			$args['value'] = 'url';
			$args['selected'] = $this->get_link( $this->links->model->get_language( $this->get_current_language( $args ) ), $args );
			$walker = new PLL_Walker_Dropdown();
		} else {
			$walker = new PLL_Walker_List();
		}

		// Cast each element to stdClass because $walker::walk() expects an array of objects.
		foreach ( $elements as $i => $element ) {
			$elements[ $i ] = (object) $element;
		}

		/**
		 * Filter the whole html markup returned by the 'pll_the_languages' template tag
		 *
		 * @since 0.8
		 *
		 * @param string $html html returned/outputted by the template tag
		 * @param array  $args arguments passed to the template tag
		 */
		$out = apply_filters( 'pll_the_languages', $walker->walk( $elements, -1, $args ), $args );

		// Javascript to switch the language when using a dropdown list.
		if ( $args['dropdown'] && 0 === $args['admin_render'] ) {
			// Accept only few valid characters for the urls_x variable name (as the widget id includes '-' which is invalid).
			$out .= sprintf(
				'<script%1$s>
					document.getElementById( "%2$s" ).addEventListener( "change", function ( event ) { location.href = event.currentTarget.value; } )
				</script>',
				current_theme_supports( 'html5', 'script' ) ? '' : ' type="text/javascript"',
				esc_js( $args['name'] )
			);
		}

		if ( $args['echo'] ) {
			echo $out; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		return $out;
	}
}
