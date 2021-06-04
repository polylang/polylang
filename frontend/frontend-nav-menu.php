<?php
/**
 * @package Polylang
 */

/**
 * Manages custom menus translations as well as the language switcher menu item on frontend
 *
 * @since 1.2
 */
class PLL_Frontend_Nav_Menu extends PLL_Nav_Menu {
	/**
	 * Current language.
	 *
	 * @var PLL_Language
	 */
	public $curlang;

	/**
	 * Constructor
	 *
	 * @since 1.2
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		parent::__construct( $polylang );

		$this->curlang = &$polylang->curlang;

		// Split the language switcher menu item in several language menu items
		add_filter( 'wp_get_nav_menu_items', array( $this, 'wp_get_nav_menu_items' ), 20 ); // after the customizer menus
		add_filter( 'wp_nav_menu_objects', array( $this, 'wp_nav_menu_objects' ) );
		add_filter( 'nav_menu_link_attributes', array( $this, 'nav_menu_link_attributes' ), 10, 2 );

		// Filters menus by language
		add_filter( 'theme_mod_nav_menu_locations', array( $this, 'nav_menu_locations' ), 20 );
		add_filter( 'wp_nav_menu_args', array( $this, 'wp_nav_menu_args' ) );

		// The customizer
		if ( isset( $_POST['wp_customize'], $_POST['customized'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			add_filter( 'wp_nav_menu_args', array( $this, 'filter_args_before_customizer' ) );
			add_filter( 'wp_nav_menu_args', array( $this, 'filter_args_after_customizer' ), 2000 );
		}
	}

	/**
	 * Sorts menu items by menu order.
	 *
	 * @since 1.7.9
	 *
	 * @param stdClass $a The first object to compare.
	 * @param stdClass $b The second object to compare.
	 * @return int -1 or 1 if $a is considered to be respectively less than or greater than $b.
	 */
	protected function usort_menu_items( $a, $b ) {
		return ( $a->menu_order < $b->menu_order ) ? -1 : 1;
	}

	/**
	 * Format a language switcher menu item title based on options
	 *
	 * @since 2.2.6
	 *
	 * @param string $flag    Formatted flag
	 * @param string $name    Language name
	 * @param array  $options Language switcher options
	 * @return string Formatted menu item title
	 */
	protected function get_item_title( $flag, $name, $options ) {
		if ( $options['show_flags'] ) {
			if ( $options['show_names'] ) {
				$title = sprintf( '%1$s<span style="margin-%2$s:0.3em;">%3$s</span>', $flag, is_rtl() ? 'right' : 'left', esc_html( $name ) );
			} else {
				$title = $flag;
			}
		} else {
			$title = esc_html( $name );
		}
		return $title;
	}

	/**
	 * Splits the one language switcher menu item of backend in several menu items on frontend.
	 * Takes care to menu_order as it is used later in wp_nav_menu().
	 *
	 * @since 1.1.1
	 *
	 * @param stdClass[] $items Menu items.
	 * @return stdClass[] Modified menu items.
	 */
	public function wp_get_nav_menu_items( $items ) {
		if ( doing_action( 'customize_register' ) ) { // needed since WP 4.3, doing_action available since WP 3.9
			return $items;
		}

		// The customizer menus does not sort the items and we need them to be sorted before splitting the language switcher
		usort( $items, array( $this, 'usort_menu_items' ) );

		$new_items = array();

		$offset = 0;

		foreach ( $items as $item ) {
			if ( $options = get_post_meta( $item->ID, '_pll_menu_item', true ) ) {
				/** This filter is documented in include/switcher.php */
				$options = apply_filters( 'pll_the_languages_args', $options ); // Honor the filter here for 'show_flags', 'show_names' and 'dropdown'.

				$switcher = new PLL_Switcher();
				$args = array_merge( array( 'raw' => 1 ), $options );

				/** @var array */
				$the_languages = $switcher->the_languages( PLL()->links, $args );

				// parent item for dropdown
				if ( ! empty( $options['dropdown'] ) ) {
					$name = isset( $options['display_names_as'] ) && 'slug' === $options['display_names_as'] ? $this->curlang->slug : $this->curlang->name;
					$item->title = $this->get_item_title( $this->curlang->get_display_flag(), $name, $options );
					$item->attr_title = '';
					$item->classes = array( 'pll-parent-menu-item' );
					$item->menu_order += $offset;
					$new_items[] = $item;
					$offset++;
				}

				$i = 0; // for incrementation of menu order only in case of dropdown
				foreach ( $the_languages as $lang ) {
					$i++;
					$lang_item = clone $item;
					$lang_item->ID = $lang_item->ID . '-' . $lang['slug']; // A unique ID
					$lang_item->title = $this->get_item_title( $lang['flag'], $lang['name'], $options );
					$lang_item->attr_title = '';
					$lang_item->url = $lang['url'];
					$lang_item->lang = $lang['locale']; // Save this for use in nav_menu_link_attributes
					$lang_item->classes = $lang['classes'];
					if ( ! empty( $options['dropdown'] ) ) {
						$lang_item->menu_order = $item->menu_order + $i;
						$lang_item->menu_item_parent = $item->db_id;
						$lang_item->db_id = 0; // to avoid recursion
					} else {
						$lang_item->menu_order += $offset;
					}
					$new_items[] = $lang_item;
					$offset++;
				}
				$offset--;
			} else {
				$item->menu_order += $offset;
				$new_items[] = $item;
			}
		}
		return $new_items;
	}

	/**
	 * Returns the ancestors of a menu item.
	 *
	 * @since 1.1.1
	 *
	 * @param stdClass $item Menu item.
	 * @return int[] Ancestors ids.
	 */
	public function get_ancestors( $item ) {
		$ids = array();
		$_anc_id = (int) $item->db_id;
		while ( ( $_anc_id = get_post_meta( $_anc_id, '_menu_item_menu_item_parent', true ) ) && ! in_array( $_anc_id, $ids ) ) {
			$ids[] = $_anc_id;
		}
		return $ids;
	}

	/**
	 * Removes current-menu and current-menu-ancestor classes to lang switcher when not on the home page.
	 *
	 * @since 1.1.1
	 *
	 * @param stdClass[] $items An array of menu items.
	 * @return stdClass[]
	 */
	public function wp_nav_menu_objects( $items ) {
		$r_ids = $k_ids = array();

		foreach ( $items as $item ) {
			if ( ! empty( $item->classes ) && is_array( $item->classes ) ) {
				if ( in_array( 'current-lang', $item->classes ) ) {
					$item->current = false;
					$item->classes = array_diff( $item->classes, array( 'current-menu-item' ) );
					$r_ids = array_merge( $r_ids, $this->get_ancestors( $item ) ); // Remove the classes for these ancestors
				} elseif ( in_array( 'current-menu-item', $item->classes ) ) {
					$k_ids = array_merge( $k_ids, $this->get_ancestors( $item ) ); // Keep the classes for these ancestors
				}
			}
		}

		$r_ids = array_diff( $r_ids, $k_ids );

		foreach ( $items as $item ) {
			if ( ! empty( $item->db_id ) && in_array( $item->db_id, $r_ids ) ) {
				$item->classes = array_diff( $item->classes, array( 'current-menu-ancestor', 'current-menu-parent', 'current_page_parent', 'current_page_ancestor' ) );
			}
		}

		return $items;
	}

	/**
	 * Adds hreflang attribute for the language switcher menu items.
	 * available since WP 3.6.
	 *
	 * @since 1.1
	 *
	 * @param string[] $atts HTML attributes applied to the menu item's `<a>` element.
	 * @param stdClass $item Menu item.
	 * @return string[] Modified attributes.
	 */
	public function nav_menu_link_attributes( $atts, $item ) {
		if ( isset( $item->lang ) ) {
			$atts['lang'] = $atts['hreflang'] = esc_attr( $item->lang );
		}
		return $atts;
	}

	/**
	 * Fills the theme nav menus locations with the right menu in the right language
	 * Needs to wait for the language to be defined
	 *
	 * @since 1.2
	 *
	 * @param array|bool $menus list of nav menus locations, false if menu locations have not been filled yet
	 * @return array|bool modified list of nav menus locations
	 */
	public function nav_menu_locations( $menus ) {
		if ( is_array( $menus ) && ! empty( $this->curlang ) ) {
			// First get multilingual menu locations from DB
			$theme = get_option( 'stylesheet' );

			foreach ( array_keys( $menus ) as $loc ) {
				$menus[ $loc ] = empty( $this->options['nav_menus'][ $theme ][ $loc ][ $this->curlang->slug ] ) ? 0 : $this->options['nav_menus'][ $theme ][ $loc ][ $this->curlang->slug ];
			}

			// Support for theme customizer
			if ( is_customize_preview() ) {
				global $wp_customize;
				foreach ( $wp_customize->unsanitized_post_values() as $key => $value ) {
					if ( false !== strpos( $key, 'nav_menu_locations[' ) ) {
						$loc = substr( trim( $key, ']' ), 19 );
						$infos = $this->explode_location( $loc );
						if ( $infos['lang'] === $this->curlang->slug ) {
							$menus[ $infos['location'] ] = (int) $value;
						} elseif ( $this->curlang->slug === $this->options['default_lang'] ) {
							$menus[ $loc ] = (int) $value;
						}
					}
				}
			}
		}
		return $menus;
	}

	/**
	 * Attempt to translate the nav menu when it is hardcoded or when no location is defined in wp_nav_menu
	 *
	 * @since 1.7.10
	 *
	 * @param array $args
	 * @return array modified $args
	 */
	public function wp_nav_menu_args( $args ) {
		$theme = get_option( 'stylesheet' );

		if ( empty( $this->curlang ) || empty( $this->options['nav_menus'][ $theme ] ) ) {
			return $args;
		}

		// Get the nav menu based on the requested menu
		$menu = wp_get_nav_menu_object( $args['menu'] );

		// Attempt to find a translation of this menu
		// This obviously does not work if the nav menu has no associated theme location
		if ( $menu ) {
			foreach ( $this->options['nav_menus'][ $theme ] as $menus ) {
				if ( in_array( $menu->term_id, $menus ) && ! empty( $menus[ $this->curlang->slug ] ) ) {
					$args['menu'] = $menus[ $this->curlang->slug ];
					return $args;
				}
			}
		}

		// Get the first menu that has items and and is in the current language if we still can't find a menu
		if ( ! $menu && ! $args['theme_location'] ) {
			$menus = wp_get_nav_menus();
			foreach ( $menus as $menu_maybe ) {
				if ( wp_get_nav_menu_items( $menu_maybe->term_id, array( 'update_post_term_cache' => false ) ) ) {
					foreach ( $this->options['nav_menus'][ $theme ] as $menus ) {
						if ( in_array( $menu_maybe->term_id, $menus ) && ! empty( $menus[ $this->curlang->slug ] ) ) {
							$args['menu'] = $menus[ $this->curlang->slug ];
							return $args;
						}
					}
				}
			}
		}

		return $args;
	}

	/**
	 * Filters the nav menu location before the customizer so that it matches the temporary location in the customizer
	 *
	 * @since 1.8
	 *
	 * @param array $args wp_nav_menu $args
	 * @return array modified $args
	 */
	public function filter_args_before_customizer( $args ) {
		if ( ! empty( $this->curlang ) ) {
			$args['theme_location'] = $this->combine_location( $args['theme_location'], $this->curlang );
		}
		return $args;
	}

	/**
	 * Filters the nav menu location after the customizer to get back the true nav menu location for the theme
	 *
	 * @since 1.8
	 *
	 * @param array $args wp_nav_menu $args
	 * @return array modified $args
	 */
	public function filter_args_after_customizer( $args ) {
		$infos = $this->explode_location( $args['theme_location'] );
		$args['theme_location'] = $infos['location'];
		return $args;
	}
}
