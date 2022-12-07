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
		if ( isset( $language_data['slug'] ) && ( defined( 'PLL_CACHE_LANGUAGES' ) && ! PLL_CACHE_LANGUAGES ) ) { // @phpstan-ignore-line
			$language = self::create_from_transient( $language_data['slug'] );
			if ( ! empty( $language ) ) {
				return $language;
			}
		}

		$language_data = self::validate_data( $language_data );

		if ( empty( $language_data ) ) {
			return null;
		}

		return new PLL_Language( $language_data );
	}

	/**
	 * Returns a language object based on terms.
	 *
	 * @since 3.4
	 *
	 * @param WP_Term[] $terms List of language terms, with the type as array keys.
	 *                         `post` and `term` are mandatory keys.
	 * @return PLL_Language|null
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

		$data = self::validate_data( $data );

		if ( empty( $data ) ) {
			return null;
		}

		return new PLL_Language( $data );
	}

	/**
	 * Validates and sanitizes data, to be ready to be used in the constructor.
	 * This doesn't verify that the language terms exist.
	 *
	 * @since 3.4
	 *
	 * @param array $data Data to process.
	 * @return array|null {
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
	 * }|null
	 */
	private static function validate_data( array $data ) {
		// Sanitize and validate mandatory types.
		foreach ( array( 'language', 'term_language' ) as $taxo ) {
			if ( ! isset( $data['term_props'][ $taxo ] ) || ! is_array( $data['term_props'][ $taxo ] ) ) {
				return null;
			}

			$data['term_props'][ $taxo ] = self::validate_term_prop_group( $data['term_props'][ $taxo ] );

			if ( empty( $data['term_props'][ $taxo ] ) ) {
				return null;
			}
		}

		// Sanitize and validate other types.
		foreach ( array_diff_key( $data['term_props'], array( 'language' => null, 'term_language' => null ) ) as $taxo => $values ) {
			if ( ! is_array( $data['term_props'][ $taxo ] ) ) {
				unset( $data['term_props'][ $taxo ] );
				continue;
			}

			$data['term_props'][ $taxo ] = self::validate_term_prop_group( $data['term_props'][ $taxo ] );

			if ( empty( $data['term_props'][ $taxo ] ) ) {
				unset( $data['term_props'][ $taxo ] );
				continue;
			}
		}

		// Mandatory fields.
		$mandatory = array( 'name', 'slug', 'locale', 'w3c', 'flag_code' );

		foreach ( $mandatory as $field ) {
			if ( empty( $data[ $field ] ) || ! is_string( $data[ $field ] ) ) {
				return null;
			}

			$data[ $field ] = trim( $data[ $field ] );

			if ( empty( $data[ $field ] ) ) {
				return null;
			}
		}

		// Other fields.
		if ( ! empty( $data['term_group'] ) && is_numeric( $data['term_group'] ) ) {
			$data['term_group'] = (int) $data['term_group'];
		} else {
			$data['term_group'] = 0;
		}

		$data['is_rtl'] = ! empty( $data['is_rtl'] ) ? 1 : 0;

		if ( ! empty( $data['mo_id'] ) && is_numeric( $data['mo_id'] ) && $data['mo_id'] >= 1 ) {
			$data['mo_id'] = abs( (int) $data['mo_id'] );
		} else {
			unset( $data['mo_id'] );
		}

		$optional = array( 'facebook', 'home_url', 'search_url', 'host', 'flag_url', 'flag', 'custom_flag_url', 'custom_flag' );

		foreach ( $optional as $field ) {
			if ( empty( $data[ $field ] ) || ! is_string( $data[ $field ] ) ) {
				unset( $data[ $field ] );
				continue;
			}

			$data[ $field ] = trim( $data[ $field ] );

			if ( empty( $data[ $field ] ) ) {
				unset( $data[ $field ] );
				continue;
			}
		}

		$optional = array( 'page_on_front', 'page_for_posts' );

		foreach ( $optional as $field ) {
			if ( empty( $data[ $field ] ) || ! is_numeric( $data[ $field ] ) || $data[ $field ] < 1 ) {
				unset( $data[ $field ] );
				continue;
			}

			$data[ $field ] = abs( (int) $data[ $field ] );
		}

		/**
		 * @phpstan-var array{
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
		 * }
		 */
		return array_intersect_key(
			$data,
			array(
				'term_props'      => null,
				'name'            => null,
				'slug'            => null,
				'locale'          => null,
				'w3c'             => null,
				'flag_code'       => null,
				'term_group'      => null,
				'is_rtl'          => null,
				'mo_id'           => null,
				'facebook'        => null,
				'home_url'        => null,
				'search_url'      => null,
				'host'            => null,
				'flag_url'        => null,
				'flag'            => null,
				'custom_flag_url' => null,
				'custom_flag'     => null,
				'page_on_front'   => null,
				'page_for_posts'  => null,
			)
		);
	}

	/**
	 * Validates and sanitizes a `term_prop` entry.
	 * This doesn't verify that the term exists.
	 *
	 * @since 3.4
	 *
	 * @param array $data Data to process.
	 * @return int[]|null
	 *
	 * @phpstan-return array{
	 *     term_id: positive-int,
	 *     term_taxonomy_id: positive-int,
	 *     count: int<0, max>
	 * }|null
	 */
	private static function validate_term_prop_group( array $data ) {
		if ( empty( $data['term_id'] ) ) {
			return null;
		}

		if ( ! is_numeric( $data['term_id'] ) || $data['term_id'] < 1 ) {
			return null;
		}

		$data['term_id'] = abs( (int) $data['term_id'] );

		if ( empty( $data['term_taxonomy_id'] ) ) {
			return null;
		}

		if ( ! is_numeric( $data['term_taxonomy_id'] ) || $data['term_taxonomy_id'] < 1 ) {
			return null;
		}

		$data['term_taxonomy_id'] = abs( (int) $data['term_taxonomy_id'] );

		if ( empty( $data['count'] ) ) {
			$data['count'] = 0;
		} elseif ( $data['count'] < 0 ) {
			$data['count'] = 0;
		} else {
			$data['count'] = abs( (int) $data['count'] );
		}

		/**
		 * @phpstan-var array{
		 *     term_id: positive-int,
		 *     term_taxonomy_id: positive-int,
		 *     count: int<0, max>
		 * }
		 */
		return array_intersect_key(
			$data,
			array(
				'term_id'          => null,
				'term_taxonomy_id' => null,
				'count'            => null,
			)
		);
	}

	/**
	 * Returns a language object from `pll_language_list` transient based on a given slug.
	 *
	 * @since 3.4
	 *
	 * @param string $slug Slug of the required language.
	 * @return PLL_Language|null Language object if found, null otherwise.
	 */
	private static function create_from_transient( $slug ) {
		$languages = get_transient( 'pll_languages_list' );

		if ( empty( $languages ) ) {
			return null;
		}

		/** @var array $languages */
		foreach ( $languages as $i => $cached_language ) {
			if ( $cached_language['slug'] !== $slug ) {
				continue;
			}

			// Backward compatibility.
			$term_props = array(
				'term_id'             => array( 'language', 'term_id' ),
				'term_taxonomy_id'    => array( 'language', 'term_taxonomy_id' ),
				'count'               => array( 'language', 'count' ),
				'tl_term_id'          => array( 'term_language', 'term_id' ),
				'tl_term_taxonomy_id' => array( 'term_language', 'term_taxonomy_id' ),
				'tl_count'            => array( 'term_language', 'count' ),
			);

			foreach ( $term_props as $prop => $value ) {
				if ( ! empty( $cached_language[ $prop ] ) && empty( $cached_language['term_props'][ $value[0] ][ $value[1] ] ) ) {
					$cached_language['term_props'][ $value[0] ][ $value[1] ] = $cached_language[ $prop ];
					unset( $cached_language[ $prop ] );
					$languages[ $i ]['term_props'][ $value[0] ][ $value[1] ] = $cached_language[ $prop ];
				}
			}

			/** This filter is documented in include/model.php */
			$languages = apply_filters( 'pll_languages_list', $languages, null );

			set_transient( 'pll_languages_list', $languages );

			return new PLL_Language( $cached_language );
		}

		return null;
	}
}
