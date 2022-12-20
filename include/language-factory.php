<?php
/**
 * @package Polylang
 */

/**
 * PLL_Language factory.
 *
 * @since 3.4
 *
 * @phpstan-import-type LanguageData from PLL_Language
 */
class PLL_Language_Factory {
	/**
	 * Predefined languages.
	 *
	 * @var array[]
	 *
	 * @phpstan-var array<string, array<string, string>>
	 */
	private static $languages;

	/**
	 * Returns a language object matching the given data, looking up in cached transient.
	 *
	 * @since 3.4
	 *
	 * @param array $language_data Language object properties stored as an array. See `PLL_Language::__construct()`
	 *                             for information on accepted properties.
	 *
	 * @return PLL_Language|null A language object if given data pass sanitization, null otherwise.
	 *
	 * @phpstan-param LanguageData $language_data
	 */
	public static function get( $language_data ) {
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
	 * @phpstan-param array{post:WP_Term, term:WP_Term}&array<string, WP_Term> $terms
	 */
	public static function get_from_terms( array $terms ) {
		$languages = self::get_languages();
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
	 * @param array $data Data to process. See `PLL_Language::__construct()` for information on accepted data.
	 * @return array Sanitized Data.
	 *
	 * @phpstan-return LanguageData
	 */
	private static function sanitize_data( array $data ) {
		foreach ( $data['term_props'] as $tax => $props ) {
			$data['term_props'][ $tax ] = array_map( 'absint', $props );
		}

		$data['is_rtl'] = ! empty( $data['is_rtl'] ) ? 1 : 0;

		$positive_fields = array( 'mo_id', 'term_group', 'page_on_front', 'page_for_posts' );

		foreach ( $positive_fields as $field ) {
			$data[ $field ] = ! empty( $data[ $field ] ) ? absint( $data[ $field ] ) : 0;
		}

		/**
		 * @var LanguageData
		 */
		return $data;
	}

	/**
	 * Returns predefined languages data.
	 *
	 * @since 3.4
	 *
	 * @return array[]
	 *
	 * @phpstan-return array<string, array<string, string>>
	 */
	private static function get_languages() {
		if ( empty( self::$languages ) ) {
			self::$languages = include POLYLANG_DIR . '/settings/languages.php';
		}

		return self::$languages;
	}
}
