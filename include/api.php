<?php
/**
 * The Polylang public API.
 *
 * @package Polylang
 */

/**
 * Template tag: displays the language switcher.
 * The function does nothing if used outside the frontend.
 *
 * @api
 * @since 0.5
 *
 * @param array $args {
 *   Optional array of arguments.
 *
 *   @type int    $dropdown               The list is displayed as dropdown if set to 1, defaults to 0.
 *   @type int    $echo                   Echoes the list if set to 1, defaults to 1.
 *   @type int    $hide_if_empty          Hides languages with no posts ( or pages ) if set to 1, defaults to 1.
 *   @type int    $show_flags             Displays flags if set to 1, defaults to 0.
 *   @type int    $show_names             Shows language names if set to 1, defaults to 1.
 *   @type string $display_names_as       Whether to display the language name or its slug, valid options are 'slug' and 'name', defaults to name.
 *   @type int    $force_home             Will always link to the homepage in the translated language if set to 1, defaults to 0.
 *   @type int    $hide_if_no_translation Hides the link if there is no translation if set to 1, defaults to 0.
 *   @type int    $hide_current           Hides the current language if set to 1, defaults to 0.
 *   @type int    $post_id                Returns links to the translations of the post defined by post_id if set, defaults to not set.
 *   @type int    $raw                    Return a raw array instead of html markup if set to 1, defaults to 0.
 *   @type string $item_spacing           Whether to preserve or discard whitespace between list items, valid options are 'preserve' and 'discard', defaults to 'preserve'.
 * }
 * @return string|array Either the html markup of the switcher or the raw elements to build a custom language switcher.
 */
function pll_the_languages( $args = array() ) {
	if ( empty( PLL()->links ) ) {
		return empty( $args['raw'] ) ? '' : array();
	}

	$switcher = new PLL_Switcher();
	return $switcher->the_languages( PLL()->links, $args );
}

/**
 * Returns the current language on frontend.
 * Returns the language set in admin language filter on backend (false if set to all languages).
 *
 * @api
 * @since 0.8.1
 * @since 3.4 Accepts composite values.
 *
 * @param string $field Optional, the language field to return (@see PLL_Language), defaults to `'slug'`.
 *                      Pass `\OBJECT` constant to get the language object. A composite value can be used for language
 *                      term property values, in the form of `{language_taxonomy_name}:{property_name}` (see
 *                      {@see PLL_Language::get_tax_prop()} for the possible values). Ex: `term_language:term_taxonomy_id`.
 * @return string|int|bool|string[]|PLL_Language The requested field or object for the current language, `false` if the field isn't set or if current language doesn't exist yet.
 *
 * @phpstan-return (
 *     $field is \OBJECT ? PLL_Language : (
 *         $field is 'slug' ? non-empty-string : string|int|bool|list<non-empty-string>
 *     )
 * )|false
 */
function pll_current_language( $field = 'slug' ) {
	if ( empty( PLL()->curlang ) ) {
		return false;
	}

	if ( \OBJECT === $field ) {
		return PLL()->curlang;
	}

	return PLL()->curlang->get_prop( $field );
}

/**
 * Returns the default language.
 *
 * @api
 * @since 1.0
 * @since 3.4 Accepts composite values.
 *
 * @param string $field Optional, the language field to return (@see PLL_Language), defaults to `'slug'`.
 *                      Pass `\OBJECT` constant to get the language object. A composite value can be used for language
 *                      term property values, in the form of `{language_taxonomy_name}:{property_name}` (see
 *                      {@see PLL_Language::get_tax_prop()} for the possible values). Ex: `term_language:term_taxonomy_id`.
 * @return string|int|bool|string[]|PLL_Language The requested field or object for the default language, `false` if the field isn't set or if default language doesn't exist yet.
 *
 * @phpstan-return (
 *     $field is \OBJECT ? PLL_Language : (
 *         $field is 'slug' ? non-empty-string : string|int|bool|list<non-empty-string>
 *     )
 * )|false
 */
function pll_default_language( $field = 'slug' ) {
	$lang = PLL()->model->get_default_language();

	if ( empty( $lang ) ) {
		return false;
	}

	if ( \OBJECT === $field ) {
		return $lang;
	}

	return $lang->get_prop( $field );
}

/**
 * Among the post and its translations, returns the ID of the post which is in the language represented by $lang.
 *
 * @api
 * @since 0.5
 * @since 3.4 Returns 0 instead of false.
 * @since 3.4 $lang accepts PLL_Language or string.
 *
 * @param int                 $post_id Post ID.
 * @param PLL_Language|string $lang    Optional language (object or slug), defaults to the current language.
 * @return int|false The translation post ID if exists, otherwise the passed ID. False if the passed object has no language or if the language doesn't exist.
 *
 * @phpstan-return int<0, max>|false
 */
function pll_get_post( $post_id, $lang = '' ) {
	$lang = $lang ? $lang : pll_current_language();

	if ( empty( $lang ) ) {
		return false;
	}

	return PLL()->model->post->get( $post_id, $lang );
}

/**
 * Among the term and its translations, returns the ID of the term which is in the language represented by $lang.
 *
 * @api
 * @since 0.5
 * @since 3.4 Returns 0 instead of false.
 * @since 3.4 $lang accepts PLL_Language or string.
 *
 * @param int                 $term_id Term ID.
 * @param PLL_Language|string $lang    Optional language (object or slug), defaults to the current language.
 * @return int|false The translation term ID if exists, otherwise the passed ID. False if the passed object has no language or if the language doesn't exist.
 *
 * @phpstan-return int<0, max>|false
 */
function pll_get_term( $term_id, $lang = null ) {
	$lang = $lang ? $lang : pll_current_language();

	if ( empty( $lang ) ) {
		return false;
	}

	return PLL()->model->term->get( $term_id, $lang );
}

/**
 * Returns the home url in a language.
 *
 * @api
 * @since 0.8
 *
 * @param string $lang Optional language code, defaults to the current language.
 * @return string
 */
function pll_home_url( $lang = '' ) {
	if ( empty( $lang ) ) {
		$lang = pll_current_language();
	}

	if ( empty( $lang ) || empty( PLL()->links ) ) {
		return home_url( '/' );
	}

	return PLL()->links->get_home_url( $lang );
}

/**
 * Registers a string for translation in the "strings translation" panel.
 *
 * @api
 * @since 0.6
 *
 * @param string $name      A unique name for the string.
 * @param string $string    The string to register.
 * @param string $context   Optional, the group in which the string is registered, defaults to 'polylang'.
 * @param bool   $multiline Optional, true if the string table should display a multiline textarea,
 *                          false if should display a single line input, defaults to false.
 * @return void
 */
function pll_register_string( $name, $string, $context = 'Polylang', $multiline = false ) {
	if ( PLL() instanceof PLL_Admin_Base ) {
		PLL_Admin_Strings::register_string( $name, $string, $context, $multiline );
	}
}

/**
 * Translates a string ( previously registered with pll_register_string ).
 *
 * @api
 * @since 0.6
 *
 * @param string $string The string to translate.
 * @return string The string translated in the current language.
 */
function pll__( $string ) {
	if ( ! is_scalar( $string ) || '' === $string ) {
		return $string;
	}

	return __( $string, 'pll_string' ); // PHPCS:ignore WordPress.WP.I18n
}

/**
 * Translates a string ( previously registered with pll_register_string ) and escapes it for safe use in HTML output.
 *
 * @api
 * @since 2.1
 *
 * @param string $string The string to translate.
 * @return string The string translated in the current language.
 */
function pll_esc_html__( $string ) {
	return esc_html( pll__( $string ) );
}

/**
 * Translates a string ( previously registered with pll_register_string ) and escapes it for safe use in HTML attributes.
 *
 * @api
 * @since 2.1
 *
 * @param string $string The string to translate.
 * @return string The string translated in the current language.
 */
function pll_esc_attr__( $string ) {
	return esc_attr( pll__( $string ) );
}

/**
 * Echoes a translated string ( previously registered with pll_register_string )
 * It is an equivalent of _e() and is not escaped.
 *
 * @api
 * @since 0.6
 *
 * @param string $string The string to translate.
 * @return void
 */
function pll_e( $string ) {
	echo pll__( $string ); // phpcs:ignore
}

/**
 * Echoes a translated string ( previously registered with pll_register_string ) and escapes it for safe use in HTML output.
 *
 * @api
 * @since 2.1
 *
 * @param string $string The string to translate.
 * @return void
 */
function pll_esc_html_e( $string ) {
	echo pll_esc_html__( $string ); // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * Echoes a translated a string ( previously registered with pll_register_string ) and escapes it for safe use in HTML attributes.
 *
 * @api
 * @since 2.1
 *
 * @param string $string The string to translate.
 * @return void
 */
function pll_esc_attr_e( $string ) {
	echo pll_esc_attr__( $string ); // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * Translates a string ( previously registered with pll_register_string ).
 *
 * @api
 * @since 1.5.4
 *
 * @param string $string The string to translate.
 * @param string $lang   Language code.
 * @return string The string translated in the requested language.
 */
function pll_translate_string( $string, $lang ) {
	if ( PLL() instanceof PLL_Frontend && pll_current_language() === $lang ) {
		return pll__( $string );
	}

	if ( ! is_scalar( $string ) || '' === $string ) {
		return $string;
	}

	$lang = PLL()->model->get_language( $lang );

	if ( empty( $lang ) ) {
		return $string;
	}

	static $cache; // Cache object to avoid loading the same translations object several times.

	if ( empty( $cache ) ) {
		$cache = new PLL_Cache();
	}

	$mo = $cache->get( $lang->slug );

	if ( ! $mo instanceof PLL_MO ) {
		$mo = new PLL_MO();
		$mo->import_from_db( $lang );
		$cache->set( $lang->slug, $mo );
	}

	return $mo->translate( $string );
}

/**
 * Returns true if Polylang manages languages and translations for this post type.
 *
 * @api
 * @since 1.0.1
 *
 * @param string $post_type Post type name.
 * @return bool
 */
function pll_is_translated_post_type( $post_type ) {
	return PLL()->model->is_translated_post_type( $post_type );
}

/**
 * Returns true if Polylang manages languages and translations for this taxonomy.
 *
 * @api
 * @since 1.0.1
 *
 * @param string $tax Taxonomy name.
 * @return bool
 */
function pll_is_translated_taxonomy( $tax ) {
	return PLL()->model->is_translated_taxonomy( $tax );
}

/**
 * Returns the list of available languages.
 *
 * @api
 * @since 1.5
 *
 * @param array $args {
 *   Optional array of arguments.
 *
 *   @type bool   $hide_empty Hides languages with no posts if set to true ( defaults to false ).
 *   @type string $fields     Return only that field if set ( @see PLL_Language for a list of fields ), defaults to 'slug'.
 * }
 * @return string[]
 */
function pll_languages_list( $args = array() ) {
	$args = wp_parse_args( $args, array( 'fields' => 'slug' ) );
	return PLL()->model->get_languages_list( $args );
}

/**
 * Sets the post language.
 *
 * @api
 * @since 1.5
 * @since 3.4 $lang accepts PLL_Language or string.
 * @since 3.4 Returns a boolean.
 *
 * @param int                 $id   Post ID.
 * @param PLL_Language|string $lang Language (object or slug).
 * @return bool True when successfully assigned. False otherwise (or if the given language is already assigned to
 *              the post).
 */
function pll_set_post_language( $id, $lang ) {
	return PLL()->model->post->set_language( $id, $lang );
}

/**
 * Sets the term language.
 *
 * @api
 * @since 1.5
 * @since 3.4 $lang accepts PLL_Language or string.
 * @since 3.4 Returns a boolean.
 *
 * @param int                 $id   Term ID.
 * @param PLL_Language|string $lang Language (object or slug).
 * @return bool True when successfully assigned. False otherwise (or if the given language is already assigned to
 *              the term).
 */
function pll_set_term_language( $id, $lang ) {
	return PLL()->model->term->set_language( $id, $lang );
}

/**
 * Save posts translations.
 *
 * @api
 * @since 1.5
 * @since 3.4 Returns an associative array of translations.
 *
 * @param int[] $arr An associative array of translations with language code as key and post ID as value.
 * @return int[] An associative array with language codes as key and post IDs as values.
 *
 * @phpstan-return array<non-empty-string, positive-int>
 */
function pll_save_post_translations( $arr ) {
	$id = reset( $arr );
	if ( $id ) {
		return PLL()->model->post->save_translations( $id, $arr );
	}

	return array();
}

/**
 * Save terms translations
 *
 * @api
 * @since 1.5
 * @since 3.4 Returns an associative array of translations.
 *
 * @param int[] $arr An associative array of translations with language code as key and term ID as value.
 * @return int[] An associative array with language codes as key and term IDs as values.
 *
 * @phpstan-return array<non-empty-string, positive-int>
 */
function pll_save_term_translations( $arr ) {
	$id = reset( $arr );
	if ( $id ) {
		return PLL()->model->term->save_translations( $id, $arr );
	}

	return array();
}

/**
 * Returns the post language.
 *
 * @api
 * @since 1.5.4
 * @since 3.4 Accepts composite values for `$field`.
 *
 * @param int    $post_id Post ID.
 * @param string $field Optional, the language field to return (@see PLL_Language), defaults to `'slug'`.
 *                      Pass `\OBJECT` constant to get the language object. A composite value can be used for language
 *                      term property values, in the form of `{language_taxonomy_name}:{property_name}` (see
 *                      {@see PLL_Language::get_tax_prop()} for the possible values). Ex: `term_language:term_taxonomy_id`.
 * @return string|int|bool|string[]|PLL_Language The requested field or object for the post language, `false` if no language is associated to that post.
 *
 * @phpstan-return (
 *     $field is \OBJECT ? PLL_Language : (
 *         $field is 'slug' ? non-empty-string : string|int|bool|list<non-empty-string>
 *     )
 * )|false
 */
function pll_get_post_language( $post_id, $field = 'slug' ) {
	$lang = PLL()->model->post->get_language( $post_id );

	if ( empty( $lang ) || \OBJECT === $field ) {
		return $lang;
	}

	return $lang->get_prop( $field );
}

/**
 * Returns the term language.
 *
 * @api
 * @since 1.5.4
 * @since 3.4 Accepts composite values for `$field`.
 *
 * @param int    $term_id Term ID.
 * @param string $field Optional, the language field to return (@see PLL_Language), defaults to `'slug'`.
 *                      Pass `\OBJECT` constant to get the language object. A composite value can be used for language
 *                      term property values, in the form of `{language_taxonomy_name}:{property_name}` (see
 *                      {@see PLL_Language::get_tax_prop()} for the possible values). Ex: `term_language:term_taxonomy_id`.
 * @return string|int|bool|string[]|PLL_Language The requested field or object for the post language, `false` if no language is associated to that term.
 *
 * @phpstan-return (
 *     $field is \OBJECT ? PLL_Language : (
 *         $field is 'slug' ? non-empty-string : string|int|bool|list<non-empty-string>
 *     )
 * )|false
 */
function pll_get_term_language( $term_id, $field = 'slug' ) {
	$lang = PLL()->model->term->get_language( $term_id );

	if ( empty( $lang ) || \OBJECT === $field ) {
		return $lang;
	}

	return $lang->get_prop( $field );
}

/**
 * Returns an array of translations of a post.
 *
 * @api
 * @since 1.8
 *
 * @param int $post_id Post ID.
 * @return int[] An associative array of translations with language code as key and translation post ID as value.
 *
 * @phpstan-return array<non-empty-string, positive-int>
 */
function pll_get_post_translations( $post_id ) {
	return PLL()->model->post->get_translations( $post_id );
}

/**
 * Returns an array of translations of a term.
 *
 * @api
 * @since 1.8
 *
 * @param int $term_id Term ID.
 * @return int[] An associative array of translations with language code as key and translation term ID as value.
 *
 * @phpstan-return array<non-empty-string, positive-int>
 */
function pll_get_term_translations( $term_id ) {
	return PLL()->model->term->get_translations( $term_id );
}

/**
 * Counts posts in a language.
 *
 * @api
 * @since 1.5
 *
 * @param string $lang Language code.
 * @param array  $args {
 *   Optional array of arguments.
 *
 *   @type string $post_type   Post type.
 *   @type int    $m           YearMonth ( ex: 201307 ).
 *   @type int    $year        4 digit year.
 *   @type int    $monthnum    Month number (from 1 to 12).
 *   @type int    $day         Day of the month (from 1 to 31).
 *   @type int    $author      Author id.
 *   @type string $author_name Author nicename.
 *   @type string $post_format Post format.
 *   @type string $post_status Post status.
 * }
 * @return int Posts count.
 */
function pll_count_posts( $lang, $args = array() ) {
	$lang = PLL()->model->get_language( $lang );

	if ( empty( $lang ) ) {
		return 0;
	}

	return PLL()->model->count_posts( $lang, $args );
}

/**
 * Allows to access the Polylang instance.
 * However, it is always preferable to use API functions
 * as internal methods may be changed without prior notice.
 *
 * @since 1.8
 *
 * @return PLL_Frontend|PLL_Admin|PLL_Settings|PLL_REST_Request
 */
function PLL() { // PHPCS:ignore WordPress.NamingConventions.ValidFunctionName
	return $GLOBALS['polylang'];
}
