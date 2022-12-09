<?php
/**
 * @package Polylang
 */

/**
 * PLL_Language factory.
 *
 * @since 3.4
 */
class PLL_Language_Factory {
	/**
	 * Returns a language object matching the given data, looking up in cached transient.
	 *
	 * @since 3.4
	 *
	 * @param array $language_data {
	 *     Language object properties stored as an array.
	 *
	 *     @type array[] $term_props      An array of language term properties. Array keys are language taxonomy names
	 *                                    (`language` and `term_language` are mandatory), array values are arrays of
	 *                                    language term properties (`term_id`, `term_taxonomy_id`, and `count`).
	 *     @type string  $name            Language name. Ex: English.
	 *     @type string  $slug            Language code used in URL. Ex: en.
	 *     @type string  $locale          WordPress language locale. Ex: en_US.
	 *     @type string  $w3c             W3C locale.
	 *     @type string  $flag_code       Code of the flag.
	 *     @type int     $term_group      Order of the language when displayed in a list of languages.
	 *     @type int     $is_rtl          `1` if the language is rtl, `0` otherwise.
	 *     @type int     $mo_id           Optional. ID of the post storing strings translations.
	 *     @type string  $facebook        Optional. Facebook locale.
	 *     @type string  $home_url        Optional. Home URL in this language.
	 *     @type string  $search_url      Optional. Home URL to use in search forms.
	 *     @type string  $host            Optional. Host corresponding to this language.
	 *     @type string  $flag_url        Optional. URL of the flag.
	 *     @type string  $flag            Optional. HTML markup of the flag.
	 *     @type string  $custom_flag_url Optional. URL of the custom flag if it exists.
	 *     @type string  $custom_flag     Optional. HTML markup of the custom flag if it exists.
	 *     @type int     $page_on_front   Optional. ID of the page on front in this language.
	 *     @type int     $page_for_posts  Optional. ID of the page for posts in this language.
	 * }
	 *
	 * @return PLL_Language|null A language object if given data pass sanitization, null otherwise.
	 *
	 * @phpstan-param array{
	 *     term_props?: array{
	 *         language: array{
	 *             term_id: positive-int,
	 *             term_taxonomy_id: positive-int,
	 *             count: int<0, max>
	 *         },
	 *         term_language: array{
	 *             term_id: positive-int,
	 *             term_taxonomy_id: positive-int,
	 *             count: int<0, max>
	 *         }
	 *     },
	 *     term_id?: positive-int,
	 *     term_taxonomy_id?: positive-int,
	 *     count?: int<0, max>,
	 *     tl_term_id?: positive-int,
	 *     tl_term_taxonomy_id?: positive-int,
	 *     tl_count?: int<0, max>,
	 *     name: non-empty-string,
	 *     slug: non-empty-string,
	 *     locale: non-empty-string,
	 *     w3c: non-empty-string,
	 *     flag_code: non-empty-string,
	 *     term_group: int,
	 *     is_rtl: int<0, 1>,
	 *     mo_id?: positive-int,
	 *     facebook?: non-empty-string,
	 *     home_url?: non-empty-string,
	 *     search_url?: non-empty-string,
	 *     host?: non-empty-string,
	 *     flag_url?: non-empty-string,
	 *     flag?: non-empty-string,
	 *     custom_flag_url?: non-empty-string,
	 *     custom_flag?: non-empty-string,
	 *     page_on_front?:positive-int,
	 *     page_for_posts?:positive-int
	 * } $language_data
	 */
	public static function create( $language_data ) {
		return new PLL_Language( self::sanitize_data( $language_data ) );
	}

	/**
	 * Returns a language object based on terms.
	 *
	 * @since 3.4
	 *
	 * @param WP_Term[] $terms List of language terms, with the type as array keys.
	 *                         `post` and `term` are mandatory keys.
	 * @return PLL_Language
	 *
	 * @phpstan-param array{post:WP_Term, term:WP_Term} $terms
	 */
	public static function create_from_terms( array $terms ) {
		$languages = include POLYLANG_DIR . '/settings/languages.php';
		$data      = array(
			'name'       => $terms['post']->name,
			'slug'       => $terms['post']->slug,
			'term_group' => $terms['post']->term_group,
			'term_props' => array(),
			'mo_id'      => PLL_MO::get_id_from_term_id( $terms['post']->term_id ),
		);

		foreach ( $terms as $term ) {
			$data['term_props'][ $term->taxonomy ] = array(
				'term_id'          => $term->term_id,
				'term_taxonomy_id' => $term->term_taxonomy_id,
				'count'            => $term->count,
			);
		}

		// The description field can contain any property.
		$description = maybe_unserialize( $terms['post']->description );

		if ( is_array( $description ) ) {
			$description = array_intersect_key(
				$description,
				array( 'locale' => null, 'rtl' => null, 'flag_code' => null )
			);

			foreach ( $description as $prop => $value ) {
				if ( 'rtl' === $prop ) {
					$data['is_rtl'] = $value;
				} else {
					$data[ $prop ] = $value;
				}
			}
		}

		if ( ! empty( $data['locale'] ) ) {
			if ( isset( $languages[ $data['locale'] ]['w3c'] ) ) {
				$data['w3c'] = $languages[ $data['locale'] ]['w3c'];
			} else {
				$data['w3c'] = str_replace( '_', '-', $data['locale'] );
			}

			if ( isset( $languages[ $data['locale'] ]['facebook'] ) ) {
				$data['facebook'] = $languages[ $data['locale'] ]['facebook'];
			}
		}

		return new PLL_Language( self::sanitize_data( $data ) );
	}

	/**
	 * Sanitizes data, to be ready to be used in the constructor.
	 * This doesn't verify that the language terms exist.
	 *
	 * @since 3.4
	 *
	 * @param array $data Data to process.
	 * @return array {
	 *     Data to process. Null if the data is invalid.
	 *
	 *     @type array[] $term_props      An array of language term properties. Array keys are language taxonomy names
	 *                                    (`language` and `term_language` are mandatory), array values are arrays of
	 *                                    language term properties (`term_id`, `term_taxonomy_id`, and `count`).
	 *     @type string  $name            Language name. Ex: English.
	 *     @type string  $slug            Language code used in URL. Ex: en.
	 *     @type string  $locale          WordPress language locale. Ex: en_US.
	 *     @type string  $w3c             W3C locale.
	 *     @type string  $flag_code       Code of the flag.
	 *     @type int     $term_group      Order of the language when displayed in a list of languages.
	 *     @type int     $is_rtl          `1` if the language is rtl, `0` otherwise.
	 *     @type int     $mo_id           ID of the post storing strings translations.
	 *     @type string  $facebook        Optional. Facebook locale.
	 *     @type string  $home_url        Home URL in this language.
	 *     @type string  $search_url      Home URL to use in search forms.
	 *     @type string  $host            Host corresponding to this language.
	 *     @type string  $flag_url        URL of the flag.
	 *     @type string  $flag            HTML markup of the flag.
	 *     @type string  $custom_flag_url Optional. URL of the custom flag if it exists.
	 *     @type string  $custom_flag     Optional. HTML markup of the custom flag if it exists.
	 *     @type int     $page_on_front   ID of the page on front in this language.
	 *     @type int     $page_for_posts  ID of the page for posts in this language.
	 * }
	 *
	 * @phpstan-return array{
	 *     term_props: array{
	 *         language: array{
	 *             term_id: positive-int,
	 *             term_taxonomy_id: positive-int,
	 *             count: int<0, max>
	 *         },
	 *         term_language: array{
	 *             term_id: positive-int,
	 *             term_taxonomy_id: positive-int,
	 *             count: int<0, max>
	 *         }
	 *     },
	 *     name: non-empty-string,
	 *     slug: non-empty-string,
	 *     locale: non-empty-string,
	 *     w3c: non-empty-string,
	 *     flag_code: non-empty-string,
	 *     term_group: int,
	 *     is_rtl: int<0, 1>,
	 *     mo_id: positive-int,
	 *     facebook?: string,
	 *     home_url: non-empty-string,
	 *     search_url: non-empty-string,
	 *     host: non-empty-string,
	 *     flag_url: non-empty-string,
	 *     flag: non-empty-string,
	 *     custom_flag_url?: string,
	 *     custom_flag?: string,
	 *     page_on_front:positive-int,
	 *     page_for_posts:positive-int
	 * }
	 */
	private static function sanitize_data( array $data ) {
		foreach ( $data['term_props'] as $taxo => $values ) {
			$values['term_id']             = (int) $values['term_id'];
			$values['term_taxonomy_id']    = (int) $values['term_taxonomy_id'];
			$values['count']               = ! empty( $values['count'] ) ? (int) $values['count'] : 0;
			$data  ['term_props'][ $taxo ] = $values;
		}

		$string_fields = array( 'name', 'slug', 'locale', 'w3c', 'flag_code', 'facebook', 'home_url', 'search_url', 'host', 'flag_url', 'flag', 'custom_flag_url', 'custom_flag' );

		foreach ( $string_fields as $field ) {
			if ( empty( $data[ $field ] ) || ! is_string( $data[ $field ] ) ) {
				$data[ $field ] = '';
			}

			$data[ $field ] = trim( $data[ $field ] );
		}

		if ( ! empty( $data['term_group'] ) && is_numeric( $data['term_group'] ) ) {
			$data['term_group'] = (int) $data['term_group'];
		} else {
			$data['term_group'] = 0;
		}

		$data['is_rtl'] = ! empty( $data['is_rtl'] ) ? 1 : 0;

		$positive_fields = array( 'term_group', 'mo_id', 'page_on_front', 'page_for_posts' );

		foreach( $positive_fields as $field ) {
			$data[ $field ] = (int) $data[ $field ];
		}

		return $data;
	}
}
