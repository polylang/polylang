<?php
/**
 * @package Polylang
 */

/**
 * A class to display a language switcher
 *
 * @since 1.2
 */
abstract class PLL_Switcher {

	/**
	 * Define which class of the switcher to return.
	 *
	 * @param PLL_Base $polylang Polylang Object.
	 *
	 * @return PLL_Admin_Switcher|PLL_Frontend_Switcher|null
	 */
	public static function create( $polylang ) {
		if ( $polylang instanceof PLL_Frontend ) {
			return new PLL_Frontend_Switcher();
		} elseif ( $polylang instanceof PLL_Admin ) {
			return new PLL_Admin_Switcher();
		} else {
			return null;
		}
	}

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
	 * Get the language elements for use in a walker
	 *
	 * @since 1.2
	 *
	 * @param PLL_Links $links
	 * @param array     $args  Arguments passed to {@see PLL_Switcher::get_the_languages()}.
	 * @return array Language switcher elements.
	 */
	protected function get_elements( $links, $args ) {
		$first = true;
		$out   = array();

		foreach ( $links->model->get_languages_list( array( 'hide_empty' => $args['hide_if_empty'] ) ) as $language ) {
			list( $id, $order, $slug, $locale, $classes, $url ) = $this->init_foreach_language( $language );

			$curlang = $this->get_current_language( $links );

			$current_lang = $curlang == $slug;

			$manage_url = $this->manage_url( $classes, $args, $links, $language, $url );
			if ( $manage_url ) {
				list( $url, $no_translation, $classes ) = $manage_url;
			} else {
				continue;
			}

			$manage_current_lang = $this->manage_current_lang_display( $classes, $current_lang, $args, $language, $first );
			if ( $manage_current_lang ) {
				list( $classes, $name, $flag, $first ) = $manage_current_lang;
			} else {
				continue;
			}

			$out[ $slug ] = compact( 'id', 'order', 'slug', 'locale', 'name', 'url', 'flag', 'current_lang', 'no_translation', 'classes' );
		}

		return $out;
	}

	/**
	 * Inits all the variables used in the loop.
	 *
	 * @param PLL_Language $language
	 *
	 * @return array
	 */
	protected function init_foreach_language( $language ) {
		$id = (int) $language->term_id;
		$order = (int) $language->term_group;
		$slug = $language->slug;
		$locale = $language->get_locale( 'display' );
		$classes = array( 'lang-item', 'lang-item-' . $id, 'lang-item-' . esc_attr( $slug ) );
		$url = null; // Avoids potential notice

		return array( $id, $order, $slug, $locale, $classes, $url );
	}

	/**
	 * @param string[]     $classes CSS classes, without the leading '.'.
	 * @param bool         $current_lang
	 * @param array        $args {@see PLL_Switcher::get_the_languages()}.
	 * @param PLL_Language $language
	 * @param bool         $first
	 *
	 * @return array|bool
	 */
	protected function manage_current_lang_display( $classes, $current_lang, $args, $language, $first ) {
		if ( $current_lang ) {
			if ( $args['hide_current'] && ! ( $args['dropdown'] && ! $args['raw'] ) ) {
				return false; // Hide current language except for dropdown
			} else {
				$classes[] = 'current-lang';
			}
		}

		$name = $args['show_names'] || ! $args['show_flags'] || $args['raw'] ? ( 'slug' == $args['display_names_as'] ? $language->slug : $language->name ) : '';
		$flag = $args['raw'] && ! $args['show_flags'] ? $language->get_display_flag_url() : ( $args['show_flags'] ? $language->get_display_flag() : '' );

		if ( $first ) {
			$classes[] = 'lang-item-first';
			$first = false;
		}

		return array( $classes, $name, $flag, $first );
	}

	/**
	 * Get the default languages switcher options array.
	 *
	 * @return array
	 */
	private function get_default_the_languages() {
		return array(
			'dropdown'               => 0, // display as list and not as dropdown
			'echo'                   => 1, // echoes the list
			'hide_if_empty'          => 1, // hides languages with no posts ( or pages )
			'menu'                   => 0, // not for nav menu ( this argument is deprecated since v1.1.1 )
			'show_flags'             => 0, // don't show flags
			'show_names'             => 1, // show language names
			'display_names_as'       => 'name', // valid options are slug and name
			'force_home'             => 0, // tries to find a translation
			'hide_if_no_translation' => 0, // don't hide the link if there is no translation
			'hide_current'           => 0, // don't hide current language
			'post_id'                => null, // if not null, link to translations of post defined by post_id
			'raw'                    => 0, // set this to true to build your own custom language switcher
			'item_spacing'           => 'preserve', // 'preserve' or 'discard' whitespace between list items
			'admin_current_lang'     => null, // use when admin_render is set to 1, if not null use it instead of the current language
		);
	}

	/**
	 * Filters the args.
	 *
	 * @param array $args {@see PLL_Switcher::get_the_languages()}.
	 * @param array $defaults {@see PLL_Switcher::get_default_the_languages()}.
	 *
	 * @return mixed
	 */
	private function filter_arguments_pll_languages( $args, $defaults ) {
		$args = wp_parse_args( $args, $defaults );

		/**
		 * Filter the arguments of the 'pll_the_languages' template tag
		 *
		 * @since 1.5
		 *
		 * @param array $args
		 */
		$args = apply_filters( 'pll_the_languages_args', $args );

		// Prevents showing empty options in dropdown
		if ( $args['dropdown'] ) {
			$args['show_names'] = 1;
		}

		return $args;
	}

	/**
	 * Define PLL walker to use and filter the HTML.
	 *
	 * @param string $curlang
	 * @param array  $args {@see PLL_Switcher::get_the_languages()}.
	 * @param array  $elements {@see PLL_Switcher::get_elements()}.
	 *
	 * @return mixed
	 */
	private function prepare_pll_walker( $curlang, $args, $elements ) {
		if ( $args['dropdown'] ) {
			$args['name'] = 'lang_choice_' . $args['dropdown'];
			$walker = new PLL_Walker_Dropdown();
			$args['selected'] = $curlang;
		}
		else {
			$walker = new PLL_Walker_List();
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

		return array( $out, $args, $elements );
	}

	/**
	 * @param PLL_Links $links
	 * @param array     $args {
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
	 *
	 * @return mixed
	 */
	protected function get_the_languages( $links, $args ) {
		$defaults = $this->get_default_the_languages();

		$args = $this->filter_arguments_pll_languages( $args, $defaults );

		$elements = $this->get_elements( $links, $args );

		if ( $args['raw'] ) {
			return array( 'elements' => $elements );
		}

		return $this->prepare_pll_walker( $this->get_current_language( $links ), $args, $elements );
	}

	/**
	 * @param PLL_Links $links
	 *
	 * @return string
	 */
	abstract public function get_current_language( $links );

	/**
	 * @param string[]     $classes CSS classes, without the leading '.'.
	 * @param array        $args {@see PLL_Admin_Switcher::the_languages()}.
	 * @param PLL_Links    $links
	 * @param PLL_Language $language
	 * @param string       $url
	 *
	 * @return mixed
	 */
	abstract public function manage_url( $classes, $args, $links, $language, $url );
}
