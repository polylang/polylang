<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Capabilities;

use PLL_Language;

defined( 'ABSPATH' ) || exit;

/**
 * A class allowing to determine if a user can translate content.
 *
 * @since 3.8
 */
class Hooks {
	/**
	 * Constructor.
	 *
	 * @since 3.8
	 */
	public function __construct() {
		add_filter( 'map_meta_cap', array( $this, 'map_caps_list' ), 10, 4 );
		add_filter( 'map_meta_cap', array( $this, 'post_type_cap' ), 10, 4 );
		add_filter( 'map_meta_cap', array( $this, 'taxonomy_cap' ), 10, 4 );
	}

	/**
	 * Filters capabilities to handle PLL's custom capabilities.
	 * PLL's capabilities are built as follow: `pll_cap|{Native WP capa}|{Custom PLL capa}|{Other custom PLL capa}`,
	 * where `pll_cap` is a prefix.
	 * This works like OR operators: `{Native WP capa}` OR `{Custom PLL capa}` OR `{Other custom PLL capa}`.
	 *
	 * @since 3.8
	 *
	 * @param string[] $caps    Primitive capabilities required of the user.
	 * @param string   $cap     Capability being checked.
	 * @param int      $user_id The user ID.
	 * @param array    $args    Adds context to the capability check, typically
	 *                          starting with an object ID.
	 * @return string[]
	 */
	public function map_caps_list( $caps, $cap, $user_id, $args ) {
		if ( ! Tools::is_serialized_capability( $cap ) ) {
			return $caps;
		}

		foreach ( Tools::unserialize_capabilities( $cap ) as $capa ) {
			if ( user_can( $user_id, $capa, ...$args ) ) {
				return array( $capa );
			}
		}

		return array( 'do_not_allow' );
	}

	/**
	 * Disallows translators to manage posts in other languages.
	 *
	 * @since 3.8
	 *
	 * @param string[] $caps    Primitive capabilities required of the user.
	 * @param string   $cap     Capability being checked.
	 * @param int      $user_id The user ID.
	 * @param array    $args    Adds context to the capability check, typically
	 *                          starting with an object ID.
	 * @return string[]
	 */
	public function post_type_cap( $caps, $cap, $user_id, $args ) {
		static $all_caps;

		if ( in_array( 'do_not_allow', $caps, true ) ) {
			return $caps;
		}

		if ( ! isset( $all_caps ) ) {
			// Ugly, must be improved.
			$post_types = PLL()->model->post_types->get_translated( false );
			$post_types = array_intersect_key( get_post_types( array(), 'objects' ), array_flip( $post_types ) );

			$all_caps = array();

			foreach ( $post_types as $type ) {
				$all_caps = array_merge( $all_caps, (array) $type->cap );
			}

			$all_caps = array_merge( $all_caps, array_keys( $all_caps ) );
			$all_caps = array_values( array_unique( $all_caps ) );
		}

		if ( ! in_array( $cap, $all_caps, true ) ) {
			// Not a post_type cap.
			return $caps;
		}

		$user = new User( $user_id );

		if ( ! $user->is_translator() ) {
			// Backward compatibility: no `translate_*` cap => don't prevent to translate.
			return $caps;
		}

		if ( empty( $args[0] ) ) {
			// No additional arguments.
			return $caps;
		}

		$language_slug = false;

		if ( is_numeric( $args[0] ) ) {
			// When a post ID is provided: `current_user_can( 'edit_post', 42 )`.
			$language      = PLL()->model->post->get_language( (int) $args[0] );
			$language_slug = $language ? $language->slug : false;
		} elseif ( is_string( $args[0] ) ) {
			// When a language slug is provided: `current_user_can( 'edit_posts', 'fr' )`.
			$language_slug = $args[0];
		} elseif ( $args[0] instanceof PLL_Language ) {
			// When a language object is provided: `current_user_can( 'edit_posts', $language )`.
			$language_slug = $args[0]->slug;
		}

		if ( $language_slug ) {
			$caps[] = Tools::get_translator_capability( $language_slug );
		}

		return $caps;
	}

	/**
	 * Disallows translators to manage taxonomy terms in other languages.
	 *
	 * @since 3.8
	 *
	 * @param string[] $caps    Primitive capabilities required of the user.
	 * @param string   $cap     Capability being checked.
	 * @param int      $user_id The user ID.
	 * @param array    $args    Adds context to the capability check, typically
	 *                          starting with an object ID.
	 * @return string[]
	 */
	public function taxonomy_cap( $caps, $cap, $user_id, $args ) {
		static $all_caps;

		if ( in_array( 'do_not_allow', $caps, true ) ) {
			return $caps;
		}

		if ( ! isset( $all_caps ) ) {
			// Ugly, must be improved.
			$taxonomies = PLL()->model->taxonomies->get_translated( false );
			$taxonomies = array_intersect_key( get_taxonomies( array(), 'objects' ), array_flip( $taxonomies ) );
			$all_caps   = array();

			foreach ( $taxonomies as $taxonomy ) {
				foreach ( (array) $taxonomy->cap as $k => $v ) {
					$all_caps[] = $v;
					$all_caps[] = $k;

					// WP does weird crap sometimes.
					if ( preg_match( '/^(.+)s$/', $k, $matches ) ) {
						// Remove the trailing `s`.
						$all_caps[] = $matches[1];
					}
				}
			}

			$all_caps = array_unique( $all_caps );
		}

		if ( ! in_array( $cap, $all_caps, true ) ) {
			// Not a taxo cap.
			return $caps;
		}

		$user = new User( $user_id );

		if ( ! $user->is_translator() ) {
			// Backward compatibility: no `translate_*` cap => don't prevent to translate.
			return $caps;
		}

		if ( empty( $args[0] ) ) {
			// No additional arguments.
			return $caps;
		}

		$language_slug = false;

		if ( is_numeric( $args[0] ) ) {
			// When a term ID is provided: `current_user_can( 'edit_term', 42 )`.
			$language      = PLL()->model->term->get_language( (int) $args[0] );
			$language_slug = $language ? $language->slug : false;
		} elseif ( is_string( $args[0] ) ) {
			// When a language slug is provided: `current_user_can( 'edit_terms', 'fr' )`.
			$language_slug = $args[0];
		} elseif ( $args[0] instanceof PLL_Language ) {
			// When a language object is provided: `current_user_can( 'edit_terms', $language )`.
			$language_slug = $args[0]->slug;
		}

		if ( $language_slug ) {
			$caps[] = Tools::get_translator_capability( $language_slug );
		}

		return $caps;
	}
}
