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
	 * Polylang's options.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 * @since 3.4
	 *
	 * @param array $options Array of Poylang's options passed by reference.
	 * @return void
	 */
	public function __construct( &$options ) {
		$this->options = &$options;
	}

	/**
	 * Returns a language object matching the given data, looking up in cached transient.
	 *
	 * @since 3.4
	 *
	 * @param array $language_data Language object properties stored as an array. See `PLL_Language::__construct()`
	 *                             for information on accepted properties.
	 *
	 * @return PLL_Language A language object if given data pass sanitization.
	 *
	 * @phpstan-param LanguageData $language_data
	 */
	public function get( $language_data ) {
		return new PLL_Language( $this->sanitize_data( $language_data ) );
	}

	/**
	 * Returns a language object based on terms.
	 *
	 * @since 3.4
	 *
	 * @param WP_Term[] $terms List of language terms, with the language taxonomy names as array keys.
	 *                         `language` is a mandatory key for the object to be created,
	 *                         `term_language` should be too in a fully operational environment.
	 * @return PLL_Language|null Language object on success, `null` on failure.
	 *
	 * @phpstan-param array{language?:WP_Term}&array<string, WP_Term> $terms
	 */
	public function get_from_terms( array $terms ) {
		if ( ! isset( $terms['language'] ) ) {
			return null;
		}

		$languages = $this->get_languages();
		$data      = array(
			'name'       => $terms['language']->name,
			'slug'       => $terms['language']->slug,
			'term_group' => $terms['language']->term_group,
			'term_props' => array(),
			'is_default' => $this->options['default_lang'] === $terms['language']->slug,
		);

		foreach ( $terms as $term ) {
			$data['term_props'][ $term->taxonomy ] = array(
				'term_id'          => $term->term_id,
				'term_taxonomy_id' => $term->term_taxonomy_id,
				'count'            => $term->count,
			);
		}

		// The description fields can contain any property.
		$description = maybe_unserialize( $terms['language']->description );

		if ( is_array( $description ) ) {
			$description = array_intersect_key(
				$description,
				array( 'locale' => null, 'rtl' => null, 'flag_code' => null, 'active' => null, 'fallbacks' => null )
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

		$flag_props = $this->get_flag( $data['flag_code'], $data['name'], $data['slug'], $data['locale'] );
		$data       = array_merge( $data, $flag_props );

		$additional_data = array();
		/**
		 * Filters additional data to add to the language before it is created.
		 *
		 * `home_url`, `search_url`, `page_on_front` and `page_for_posts` are only allowed.
		 *
		 * @since 3.4
		 *
		 * @param array $additional_data.
		 * @param array $data Language data.
		 *
		 * @phpstan-param array<non-empty-string, mixed> $additional_data
		 * @phpstan-param non-empty-array<non-empty-string, mixed> $data
		 */
		$additional_data = apply_filters( 'pll_additional_language_data', $additional_data, $data );

		$allowed_additional_data = array(
			'home_url'       => '',
			'search_url'     => '',
			'page_on_front'  => 0,
			'page_for_posts' => 0,
		);

		$data = array_merge( $data, array_intersect_key( $additional_data, $allowed_additional_data ) );

		return new PLL_Language( $this->sanitize_data( $data ) );
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
	private function sanitize_data( array $data ) {
		foreach ( $data['term_props'] as $tax => $props ) {
			$data['term_props'][ $tax ] = array_map( 'absint', $props );
		}

		$data['is_rtl'] = ! empty( $data['is_rtl'] ) ? 1 : 0;

		$positive_fields = array( 'term_group', 'page_on_front', 'page_for_posts' );

		foreach ( $positive_fields as $field ) {
			$data[ $field ] = ! empty( $data[ $field ] ) ? absint( $data[ $field ] ) : 0;
		}

		$data['active'] = isset( $data['active'] ) ? (bool) $data['active'] : true;

		if ( array_key_exists( 'fallbacks', $data ) && ! is_array( $data['fallbacks'] ) ) {
			unset( $data['fallbacks'] );
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
	private function get_languages() {
		if ( empty( self::$languages ) ) {
			self::$languages = include POLYLANG_DIR . '/settings/languages.php';
		}

		return self::$languages;
	}


	/**
	 * Creates flag_url and flag language properties. Also takes care of custom flag.
	 *
	 * @since 1.2
	 * @since 3.4 Moved from `PLL_Language`to `PLL_Language_Factory` and renamed
	 *            in favor of `get_flag()` (formerly `set_flag()`).
	 *
	 * @param string $flag_code Flag code.
	 * @param string $name      Language name.
	 * @param string $slug      Language slug.
	 * @param string $locale    Language locale.
	 * @return array {
	 *     Array of the flag properties.
	 *     @type string  $flag_url        URL of the flag.
	 *     @type string  $flag            HTML markup of the flag.
	 *     @type string  $custom_flag_url Optional. URL of the custom flag if it exists.
	 *     @type string  $custom_flag     Optional. HTML markup of the custom flag if it exists.
	 * }
	 *
	 * @phpstan-return array{
	 *     flag_url: string,
	 *     flag: string,
	 *     custom_flag_url?: non-empty-string,
	 *     custom_flag?: non-empty-string
	 * }
	 */
	private function get_flag( $flag_code, $name, $slug, $locale ) {
		$flags = array(
			'flag' => PLL_Language::get_flag_informations( $flag_code ),
		);

		// Custom flags?
		$directories = array(
			PLL_LOCAL_DIR,
			get_stylesheet_directory() . '/polylang',
			get_template_directory() . '/polylang',
		);

		foreach ( $directories as $dir ) {
			if ( is_readable( $file = "{$dir}/{$locale}.png" ) || is_readable( $file = "{$dir}/{$locale}.jpg" ) || is_readable( $file = "{$dir}/{$locale}.jpeg" ) || is_readable( $file = "{$dir}/{$locale}.svg" ) ) {
				$flags['custom_flag'] = array(
					'url' => content_url( '/' . str_replace( WP_CONTENT_DIR, '', $file ) ),
				);
				break;
			}
		}

		/**
		 * Filters the custom flag information.
		 *
		 * @since 2.4
		 *
		 * @param array|null $flag {
		 *   Information about the custom flag.
		 *
		 *   @type string $url    Flag url.
		 *   @type string $src    Optional, src attribute value if different of the url, for example if base64 encoded.
		 *   @type int    $width  Optional, flag width in pixels.
		 *   @type int    $height Optional, flag height in pixels.
		 * }
		 * @param string     $code Flag code.
		 */
		$flags['custom_flag'] = apply_filters( 'pll_custom_flag', empty( $flags['custom_flag'] ) ? null : $flags['custom_flag'], $flag_code );

		if ( ! empty( $flags['custom_flag']['url'] ) ) {
			if ( empty( $flags['custom_flag']['src'] ) ) {
				$flags['custom_flag']['src'] = esc_url( set_url_scheme( $flags['custom_flag']['url'], 'relative' ) );
			}

			$flags['custom_flag']['url'] = esc_url_raw( $flags['custom_flag']['url'] );
		} else {
			unset( $flags['custom_flag'] );
		}

		/**
		 * Filters the flag title attribute.
		 * Defaults to the language name.
		 *
		 * @since 0.7
		 *
		 * @param string $title  The flag title attribute.
		 * @param string $slug   The language code.
		 * @param string $locale The language locale.
		 */
		$title  = apply_filters( 'pll_flag_title', $name, $slug, $locale );
		$return = array();

		/**
		 * @var array{
		 *     flag: array{
		 *         url: string,
		 *         src: string,
		 *         width?: positive-int,
		 *         height?: positive-int
		 *     },
		 *     custom_flag?: array{
		 *         url: non-empty-string,
		 *         src: non-empty-string,
		 *         width?: positive-int,
		 *         height?: positive-int
		 *     }
		 * } $flags
		 */
		foreach ( $flags as $key => $flag ) {
			$return[ "{$key}_url" ] = $flag['url'];

			/**
			 * Filters the html markup of a flag.
			 *
			 * @since 1.0.2
			 *
			 * @param string $flag Html markup of the flag or empty string.
			 * @param string $slug Language code.
			 */
			$return[ $key ] = apply_filters(
				'pll_get_flag',
				PLL_Language::get_flag_html( $flag, $title, $name ),
				$slug
			);
		}

		/**
		 * @var array{
		 *     flag_url: string,
		 *     flag: string,
		 *     custom_flag_url?: non-empty-string,
		 *     custom_flag?: non-empty-string
		 * } $return
		 */
		return $return;
	}
}
