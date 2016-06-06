<?php

/**
 * Compatibility with WPML API. See http://wpml.org/documentation/support/wpml-coding-api/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // don't access directly
};

/**
 * defines two WPML constants once the language has been defined
 * the compatibility with WPML is not perfect on admin side as the constants are defined
 * in 'setup_theme' by Polylang ( based on user info ) and 'plugins_loaded' by WPML ( based on cookie )
 *
 * @since 0.9.5
 */
function pll_define_wpml_constants() {
	if ( ! empty( PLL()->curlang ) ) {
		if ( ! defined( 'ICL_LANGUAGE_CODE' ) ) {
			define( 'ICL_LANGUAGE_CODE', PLL()->curlang->slug );
		}

		if ( ! defined( 'ICL_LANGUAGE_NAME' ) ) {
			define( 'ICL_LANGUAGE_NAME', PLL()->curlang->name );
		}
	}

	elseif ( PLL_ADMIN ) {
		if ( ! defined( 'ICL_LANGUAGE_CODE' ) ) {
			define( 'ICL_LANGUAGE_CODE', 'all' );
		}

		if ( ! defined( 'ICL_LANGUAGE_NAME' ) ) {
			define( 'ICL_LANGUAGE_NAME', '' );
		}
	}
}

add_action( 'pll_language_defined', 'pll_define_wpml_constants' );

/**
 * link to the home page in the active language
 *
 * @since 0.9.4
 *
 * @return string
 */
if ( ! function_exists( 'icl_get_home_url' ) ) {
	function icl_get_home_url() {
		return pll_home_url();
	}
}

/**
 * used for building custom language selectors
 * available only on frontend
 *
 * list of paramaters accepted in $args
 *
 * skip_missing  => wether to skip missing translation or not, 0 or 1, defaults to 0
 * orderby       => 'id', 'code', 'name', defaults to 'id'
 * order         => 'ASC' or 'DESC', defaults to 'ASC'
 * link_empty_to => link to use when the translation is missing {$lang} is replaced by the language code
 *
 * list of parameters returned per language:
 *
 * id               => the language id
 * active           => wether this is the active language or no, 0 or 1
 * native_name      => the language name
 * missing          => wether the translation is missing or not, 0 or 1
 * translated_name  => empty, does not exist in Polylang
 * language_code    => the language code ( slug )
 * country_flag_url => the url of the flag
 * url              => the url of the translation
 *
 * @since 1.0
 *
 * @param string|array $args optional
 * @return array array of arrays per language
 */
if ( ! function_exists( 'icl_get_languages' ) ) {
	function icl_get_languages( $args = '' ) {
		$args = wp_parse_args( $args, array( 'skip_missing' => 0, 'orderby' => 'id', 'order' => 'ASC' ) );
		$orderby = ( isset( $args['orderby'] ) && 'code' == $args['orderby'] ) ? 'slug' : ( isset( $args['orderby'] ) && 'name' == $args['orderby'] ? 'name' : 'id' );
		$order = ( ! empty( $args['order'] ) && 'desc' == $args['order'] ) ? 'DESC' : 'ASC';

		$arr = array();

		foreach ( PLL()->model->get_languages_list( array( 'hide_empty' => true, 'orderby' => $orderby, 'order' => $order ) ) as $lang ) {
			// we can find a translation only on frontend
			if ( method_exists( PLL()->links, 'get_translation_url' ) ) {
				$url = PLL()->links->get_translation_url( $lang );
			}

			// it seems that WPML does not bother of skip_missing parameter on admin side and before the $wp_query object has been filled
			if ( empty( $url ) && ! empty( $args['skip_missing'] ) && ! is_admin() && did_action( 'parse_query' ) ) {
				continue;
			}

			$arr[ $lang->slug ] = array(
				'id'               => $lang->term_id,
				'active'           => isset( PLL()->curlang->slug ) && PLL()->curlang->slug == $lang->slug ? 1 : 0,
				'native_name'      => $lang->name,
				'missing'          => empty( $url ) ? 1 : 0,
				'translated_name'  => '', // does not exist in Polylang
				'language_code'    => $lang->slug,
				'country_flag_url' => $lang->flag_url,
				'url'              => ! empty( $url ) ? $url :
					( empty( $args['link_empty_to'] ) ? PLL()->links->get_home_url( $lang ) :
					str_replace( '{$lang}', $lang->slug, $args['link_empty_to'] ) ),
			);
		}
		return $arr;
	}
}

/**
 * used for creating language dependent links in themes
 *
 * @since 1.0
 *
 * @param int    $id     object id
 * @param string $type   optional, post type or taxonomy name of the object, defaults to 'post'
 * @param string $text   optional the link text. If not specified will produce the name of the element in the current language
 * @param array  $args   optional an array of arguments to add to the link, defaults to empty
 * @param string $anchor optional the anchor to add to teh link, defaults to empty
 * @return string a language dependent link
 */
if ( ! function_exists( 'icl_link_to_element' ) ) {
	function icl_link_to_element( $id, $type = 'post', $text = '', $args = array(), $anchor = '' ) {
		if ( 'tag' == $type ) {
			$type = 'post_tag';
		}

		$pll_type = ( 'post' == $type || pll_is_translated_post_type( $type ) ) ? 'post' : ( 'term' == $type || pll_is_translated_taxonomy( $type ) ? 'term' : false );
		if ( $pll_type && ( $lang = pll_current_language() ) && ( $tr_id = PLL()->model->$pll_type->get_translation( $id, $lang ) ) && PLL()->links->current_user_can_read( $tr_id ) ) {
			$id = $tr_id;
		}

		if ( post_type_exists( $type ) ) {
			$link = get_permalink( $id );
			if ( empty( $text ) ) {
				$text = get_the_title( $id );
			}
		}
		elseif ( taxonomy_exists( $type ) ) {
			$link = get_term_link( $id, $type );
			if ( empty( $text ) && ( $term = get_term( $id, $type ) ) && ! empty( $term ) && ! is_wp_error( $term ) ) {
				$text = $term->name;
			}
		}

		if ( empty( $link ) || is_wp_error( $link ) ) {
			return '';
		}

		if ( ! empty( $args ) ) {
			$link .= ( false === strpos( $link, '?' ) ? '?' : '&'  ) . http_build_query( $args );
		}

		if ( ! empty( $anchor ) ) {
			$link .= '#' . $anchor;
		}

		return sprintf( '<a href="%s">%s</a>', esc_url( $link ), esc_html( $text ) );
	}
}

/**
 * used for calculating the IDs of objects ( usually categories ) in the current language
 *
 * @since 0.9.5
 *
 * @param int $id object id
 * @param string $type, post type or taxonomy name of the object, defaults to 'post'
 * @param bool   $return_original_if_missing optional, true if Polylang should return the original id if the translation is missing, defaults to false
 * @param string $lang optional language code, defaults to current language
 * @return int|null the object id of the translation, null if the translation is missing and $return_original_if_missing set to false
 */
if ( ! function_exists( 'icl_object_id' ) ) {
	function icl_object_id( $id, $type, $return_original_if_missing = false, $lang = false ) {
		$pll_type = ( 'post' === $type || pll_is_translated_post_type( $type ) ) ? 'post' : ( 'term' === $type || pll_is_translated_taxonomy( $type ) ? 'term' : false );
		return $pll_type && ( $lang = $lang ? $lang : pll_current_language() ) && ( $tr_id = PLL()->model->$pll_type->get_translation( $id, $lang ) ) ? $tr_id :
			( $return_original_if_missing ? $id : null );
	}
}

/**
 * undocumented function used by the theme Maya
 * returns the post language
 * @see original WPML code at https://wpml.org/forums/topic/canonical-urls-for-wpml-duplicated-posts/#post-52198
 *
 * @since 1.8
 *
 * @param int $post_id
 * @return array
 */
if ( ! function_exists( 'wpml_get_language_information' ) ) {
	function wpml_get_language_information( $post_id = null ) {
		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}

		// FIXME WPML may return a WP_Error object
		return false === $lang = PLL()->model->post->get_language( $post_id ) ? array() : array(
			'locale' => $lang->locale,
			'text_direction' => $lang->is_rtl,
			'display_name' => $lang->name, // seems to be the post language name displayed in the current language, not a feature in Polylang
			'native_name' => $lang->name,
			'different_language' => $lang->slug != pll_current_language(),
		);
	}
}

/**
 * registers a string for translation in the "strings translation" panel
 *
 * @since 0.9.3
 *
 * @param string $context the group in which the string is registered, defaults to 'polylang'
 * @param string $name    a unique name for the string
 * @param string $string  the string to register
 */
if ( ! function_exists( 'icl_register_string' ) ) {
	function icl_register_string( $context, $name, $string ) {
		PLL_WPML_Compat::instance()->register_string( $context, $name, $string );
	}
}

/**
 * removes a string from the "strings translation" panel
 *
 * @since 1.0.2
 *
 * @param string $context the group in which the string is registered, defaults to 'polylang'
 * @param string $name    a unique name for the string
 */
if ( ! function_exists( 'icl_unregister_string' ) ) {
	function icl_unregister_string( $context, $name ) {
		PLL_WPML_Compat::instance()->unregister_string( $context, $name );
	}
}

/**
 * gets the translated value of a string ( previously registered with icl_register_string or pll_register_string )
 *
 * @since 0.9.3
 *
 * @param string $context the group in which the string is registered
 * @param string $name    a unique name for the string
 * @param string $string  the string to translate, optional for strings registered with icl_register_string
 * @return string the translated string in the current language
 */
if ( ! function_exists( 'icl_t' ) ) {
	function icl_t( $context, $name, $string = false ) {
		if ( empty( $string ) ) {
			$string = PLL_WPML_Compat::instance()->get_string_by_context_and_name( $context, $name );
		}
		return pll__( $string );
	}
}

/**
 * undocumented function used by NextGen Gallery
 * seems to be used to both register and translate a string
 * used in PLL_Plugins_Compat for Jetpack with only 3 arguments
 *
 * @since 1.0.2
 *
 * @param string $context the group in which the string is registered, defaults to 'polylang'
 * @param string $name    a unique name for the string
 * @param string $string  the string to register
 * @param bool   $bool    optional, not used by Polylang
 * @return string the translated string in the current language
 */
if ( ! function_exists( 'icl_translate' ) ) {
	function icl_translate( $context, $name, $string, $bool = false ) {
		PLL_WPML_Compat::instance()->register_string( $context, $name, $string );
		return pll__( $string );
	}
}

/**
 * undocumented function used by Types
 * FIXME: tested only with Types
 * probably incomplete as Types locks the custom fields for a new post, but not when edited
 * this is probably linked to the fact that WPML has always an original post in the default language and not Polylang :)
 *
 * @since 1.1.2
 *
 * @return array
 */
if ( ! function_exists( 'wpml_get_copied_fields_for_post_edit' ) ) {
	function wpml_get_copied_fields_for_post_edit() {
		if ( empty( $_GET['from_post'] ) ) {
			return array();
		}

		// don't know what WPML does but Polylang does copy all public meta keys by default
		foreach ( $keys = array_unique( array_keys( get_post_custom( (int) $_GET['from_post'] ) ) ) as $k => $meta_key ) {
			if ( is_protected_meta( $meta_key ) ) {
				unset( $keys[ $k ] );
			}
		}

		// apply our filter and fill the expected output ( see /types/embedded/includes/fields-post.php )
		/** This filter is documented in modules/sync/admin-sync.php */
		$arr['fields'] = array_unique( apply_filters( 'pll_copy_post_metas', empty( $keys ) ? array() : $keys, false ) );
		$arr['original_post_id'] = (int) $_GET['from_post'];
		return $arr;
	}
}

/**
 * undocumented function used by Warp 6 by Yootheme
 *
 * @since 1.0.5
 *
 * @return string default language code
 */
if ( ! function_exists( 'icl_get_default_language' ) ) {
	function icl_get_default_language() {
		return pll_default_language();
	}
}

/**
 * undocumented function reported to be used by Table Rate Shipping for WooCommerce
 * @see https://wordpress.org/support/topic/add-wpml-compatibility-function
 *
 * @since 1.8.2
 *
 * @return string default language code
 */
if ( ! function_exists( 'wpml_get_default_language' ) ) {
	function wpml_get_default_language() {
		return pll_default_language();
	}
}

/**
 * registers strings in a persistent way as done by WPML
 *
 * @since 1.0.2
 */
class PLL_WPML_Compat {
	static protected $instance; // for singleton
	static protected $strings; // used for cache

	/**
	 * constructor
	 *
	 * @since 1.0.2
	 */
	protected function __construct() {
		self::$strings = get_option( 'polylang_wpml_strings', array() );

		add_action( 'pll_get_strings', array( &$this, 'get_strings' ) );
	}

	/**
	 * access to the single instance of the class
	 *
	 * @since 1.7
	 *
	 * @return object
	 */
	static public function instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * unlike pll_register_string, icl_register_string stores the string in database
	 * so we need to do the same as some plugins or themes may expect this
	 * we use a serialized option to do this
	 *
	 * @since 1.0.2
	 *
	 * @param string $context the group in which the string is registered, defaults to 'polylang'
	 * @param string $name    a unique name for the string
	 * @param string $string  the string to register
	 */
	public function register_string( $context, $name, $string ) {
		// registers the string if it does not exist yet
		$to_register = array( 'context' => $context, 'name' => $name, 'string' => $string, 'multiline' => false, 'icl' => true );
		if ( ! in_array( $to_register, self::$strings ) && $to_register['string'] ) {
			self::$strings[] = $to_register;
			update_option( 'polylang_wpml_strings', self::$strings );
		}
	}

	/**
	 * removes a string from the registered strings list
	 *
	 * @since 1.0.2
	 *
	 * @param string $context the group in which the string is registered, defaults to 'polylang'
	 * @param string $name    a unique name for the string
	 */
	public function unregister_string( $context, $name ) {
		foreach ( self::$strings as $key => $string ) {
			if ( $string['context'] == $context && $string['name'] == $name ) {
				unset( self::$strings[ $key ] );
				update_option( 'polylang_wpml_strings', self::$strings );
			}
		}
	}

	/**
	 * adds strings registered by icl_register_string to those registered by pll_register_string
	 *
	 * @since 1.0.2
	 *
	 * @param array $strings existing registered strings
	 * @return array registered strings with added strings through WPML API
	 */
	public function get_strings( $strings ) {
		return empty( self::$strings ) ? $strings : array_merge( $strings, self::$strings );
	}

	/**
	 * Get a registered string by its context and name
	 *
	 * @since 1.9.2
	 *
	 * @param string $context the group in which the string is registered
	 * @param string $name    a unique name for the string
	 * @return bool|string the registered string, false if none was found
	 */
	public function get_string_by_context_and_name( $context, $name ) {
		foreach ( self::$strings as $string ) {
			if ( $string['context'] == $context && $string['name'] == $name ) {
				return $string['string'];
			}
		}
		return false;
	}
}
