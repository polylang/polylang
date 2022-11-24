<?php
/**
 * @package Polylang
 */

/**
 * A language object is made of two terms in 'language' and 'term_language' taxonomies.
 * Manipulating only one object per language instead of two terms should make things easier.
 *
 * @since 1.2
 * @immutable
 */
#[AllowDynamicProperties]
class PLL_Language {
	/**
	 * Language name. Ex: English.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	public $name;

	/**
	 * Language code used in URL. Ex: en.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	public $slug;

	/**
	 * Order of the language when displayed in a list of languages.
	 *
	 * @var int
	 */
	public $term_group;

	/**
	 * ID of the term in 'language' taxonomy.
	 *
	 * @var int
	 * @since 3.4 Deprecated.
	 * @deprecated
	 *
	 * @phpstan-var int<0, max>
	 */
	public $term_id;

	/**
	 * Term taxonomy id in 'language' taxonomy.
	 *
	 * @var int
	 * @since 3.4 Deprecated.
	 * @deprecated
	 *
	 * @phpstan-var int<0, max>
	 */
	public $term_taxonomy_id;

	/**
	 * Number of posts and pages in that language.
	 *
	 * @var int
	 * @since 3.4 Deprecated.
	 * @deprecated
	 *
	 * @phpstan-var int<0, max>
	 */
	public $count;

	/**
	 * ID of the term in 'term_language' taxonomy.
	 *
	 * @var int
	 * @since 3.4 Deprecated.
	 * @deprecated
	 *
	 * @phpstan-var int<0, max>
	 */
	public $tl_term_id;

	/**
	 * Term taxonomy ID in 'term_language' taxonomy.
	 *
	 * @var int
	 * @since 3.4 Deprecated.
	 * @deprecated
	 *
	 * @phpstan-var int<0, max>
	 */
	public $tl_term_taxonomy_id;

	/**
	 * Number of terms in that language.
	 *
	 * @var int
	 * @since 3.4 Deprecated.
	 * @deprecated
	 *
	 * @phpstan-var int<0, max>
	 */
	public $tl_count;

	/**
	 * WordPress language locale. Ex: en_US.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	public $locale;

	/**
	 * 1 if the language is rtl, 0 otherwise.
	 *
	 * @var int
	 *
	 * @phpstan-var int<0, 1>
	 */
	public $is_rtl;

	/**
	 * W3C locale.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	public $w3c;

	/**
	 * Facebook locale.
	 *
	 * @var string|null
	 *
	 * @phpstan-var non-empty-string|null
	 */
	public $facebook;

	/**
	 * Home URL in this language.
	 *
	 * @var string|null
	 *
	 * @phpstan-var non-empty-string|null
	 */
	public $home_url;

	/**
	 * Home URL to use in search forms.
	 *
	 * @var string|null
	 *
	 * @phpstan-var non-empty-string|null
	 */
	public $search_url;

	/**
	 * Host corresponding to this language.
	 *
	 * @var string|null
	 *
	 * @phpstan-var non-empty-string|null
	 */
	public $host;

	/**
	 * ID of the post storing strings translations.
	 *
	 * @var int|null
	 *
	 * @phpstan-var positive-int|null
	 */
	public $mo_id;

	/**
	 * ID of the page on front in this language (set from pll_languages_list filter).
	 *
	 * @var int|null
	 */
	public $page_on_front;

	/**
	 * ID of the page for posts in this language (set from pll_languages_list filter).
	 *
	 * @var int|null
	 */
	public $page_for_posts;

	/**
	 * Code of the flag.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	public $flag_code;

	/**
	 * URL of the flag.
	 *
	 * @var string|null
	 *
	 * @phpstan-var non-empty-string|null
	 */
	public $flag_url;

	/**
	 * HTML markup of the flag.
	 *
	 * @var string|null
	 */
	public $flag;

	/**
	 * URL of the custom flag if it exists.
	 *
	 * @var string|null
	 *
	 * @phpstan-var non-empty-string|null
	 */
	public $custom_flag_url;

	/**
	 * HTML markup of the custom flag if it exists.
	 *
	 * @var string|null
	 */
	public $custom_flag;

	/**
	 * Stores language term properties (like term IDs and counts) for each language taxonomy (`language`,
	 * `term_language`, etc).
	 * This stores the values of the properties `$term_id` + `$term_taxonomy_id` + `$count` (`language`), `$tl_term_id`
	 * + `$tl_term_taxonomy_id` + `$tl_count` (`term_language`), and the `term_id` + `term_taxonomy_id` + `count` for
	 * other language taxonomies.
	 *
	 * @var array[] Array keys are language term names.
	 *
	 * @exemple array(
	 *     'language'       => array(
	 *         'term_id'          => 7,
	 *         'term_taxonomy_id' => 8,
	 *         'count'            => 11,
	 *     ),
	 *     'term_language' => array(
	 *         'term_id'          => 11,
	 *         'term_taxonomy_id' => 12,
	 *         'count'            => 6,
	 *     ),
	 *     'foo_language'  => array(
	 *         'term_id'          => 33,
	 *         'term_taxonomy_id' => 34,
	 *         'count'            => 0,
	 *     ),
	 * )
	 *
	 * @phpstan-var array<
	 *     non-empty-string,
	 *     array{
	 *         term_id: positive-int,
	 *         term_taxonomy_id: positive-int,
	 *         count: int<0, max>
	 *     }
	 * >
	 */
	protected $term_props = array();

	/**
	 * Constructor: builds a language object given the corresponding data.
	 *
	 * @since 1.2
	 * @since 3.4 Only accepts one argument.
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
	 * @phpstan-param array{
	 *     term_id?: positive-int,
	 *     term_taxonomy_id?: positive-int,
	 *     count?: int<0, max>,
	 *     tl_term_id?: positive-int,
	 *     tl_term_taxonomy_id?: positive-int,
	 *     tl_count?: int<0, max>,
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
	public function __construct( array $language_data ) {
		// Deal with duplicated data for backward compatibility: priority to the ones in `$this->term_props`.
		$term_props = array(
			'term_id'             => array( 'language', 'term_id' ),
			'term_taxonomy_id'    => array( 'language', 'term_taxonomy_id' ),
			'count'               => array( 'language', 'count' ),
			'tl_term_id'          => array( 'term_language', 'term_id' ),
			'tl_term_taxonomy_id' => array( 'term_language', 'term_taxonomy_id' ),
			'tl_count'            => array( 'term_language', 'count' ),
		);

		foreach ( $term_props as $prop => $value ) {
			if ( isset( $language_data['term_props'][ $value[0] ][ $value[1] ] ) ) {
				$this->$prop = $this->set_tax_prop( $value[0], $value[1], $language_data['term_props'][ $value[0] ][ $value[1] ] );
			} elseif ( isset( $language_data[ $prop ] ) ) {
				$this->$prop = $this->set_tax_prop( $value[0], $value[1], $language_data[ $prop ] );
			}

			unset( $language_data['term_props'][ $value[0] ][ $value[1] ], $language_data[ $prop ] );

			if ( empty( $language_data['term_props'][ $value[0] ] ) ) {
				unset( $language_data['term_props'][ $value[0] ] );
			}
		}

		// Other values in `term_props` that are not `language` nor `term_language`.
		if ( ! empty( $language_data['term_props'] ) ) {
			foreach ( $language_data['term_props'] as $taxonomy_name => $prop_values ) {
				foreach ( $prop_values as $prop_name => $prop_value ) {
					$this->set_tax_prop( $taxonomy_name, $prop_name, $prop_value );
				}
			}
		}

		unset( $language_data['term_props'] );

		// Make sure everything is fine in term props.
		foreach ( $this->term_props as $taxonomy_name => $prop_values ) {
			if ( ! isset( $prop_values['term_id'], $prop_values['term_taxonomy_id'] ) ) {
				// This must not happen for `language` and `term_language`.
				unset( $this->term_props[ $taxonomy_name ] );
				continue;
			}

			if ( ! isset( $prop_values['count'] ) ) {
				$this->term_props[ $taxonomy_name ]['count'] = 0;
			}
		}

		// Add all the other values.
		foreach ( $language_data as $prop => $value ) {
			$this->$prop = $value;
		}
	}

	/**
	 * Returns a language term property value (term ID, term taxonomy ID, or count).
	 *
	 * @since 3.4
	 *
	 * @param string $taxonomy_name Name of the taxonomy.
	 * @param string $prop_name     Name of the property: 'term_taxonomy_id', 'term_id', 'count'.
	 * @return int
	 *
	 * @phpstan-param non-empty-string $taxonomy_name
	 * @phpstan-param 'term_taxonomy_id'|'term_id'|'count' $prop_name
	 * @phpstan-return int<0, max>
	 */
	public function get_tax_prop( $taxonomy_name, $prop_name ) {
		return isset( $this->term_props[ $taxonomy_name ][ $prop_name ] ) ? $this->term_props[ $taxonomy_name ][ $prop_name ] : 0;
	}

	/**
	 * Stores a language term property value (term ID, term taxonomy ID, or count).
	 *
	 * @since 3.4
	 *
	 * @param string $taxonomy_name Name of the taxonomy.
	 * @param string $prop_name     Name of the property: 'term_taxonomy_id', 'term_id', 'count'.
	 * @param int    $prop_value    Property value.
	 * @return int
	 *
	 * @phpstan-param non-empty-string $taxonomy_name
	 * @phpstan-param 'term_taxonomy_id'|'term_id'|'count' $prop_name
	 * @phpstan-return int<0, max>
	 */
	public function set_tax_prop( $taxonomy_name, $prop_name, $prop_value ) {
		if ( ! is_numeric( $prop_value ) ) {
			return 0;
		}

		$prop_value = $prop_value >= 1 ? abs( (int) $prop_value ) : 0;

		if ( 'count' === $prop_name || $prop_value >= 1 ) {
			if ( ! isset( $this->term_props[ $taxonomy_name ] ) ) {
				$this->term_props[ $taxonomy_name ] = array(); // @phpstan-ignore-line
			}

			$this->term_props[ $taxonomy_name ][ $prop_name ] = $prop_value; // @phpstan-ignore-line
		} else {
			unset( $this->term_props[ $taxonomy_name ][ $prop_name ] );
		}

		return $prop_value;
	}

	/**
	 * Returns the language term props for all content types.
	 *
	 * @since 3.4
	 *
	 * @param string|null $field Name of the field to return. `null` to return them all.
	 * @return (int[]|int)[] Array keys are taxonomy names, array values depend of `$field`.
	 *
	 * @phpstan-param 'term_taxonomy_id'|'term_id'|'count'|null $field
	 * @phpstan-return array<non-empty-string, (
	 *     $field is non-empty-string ?
	 *     (
	 *         $field is 'count' ?
	 *         int<0, max> :
	 *         positive-int
	 *     ) :
	 *     array{
	 *         term_id: positive-int,
	 *         term_taxonomy_id: positive-int,
	 *         count: int<0, max>
	 *     }
	 * )>
	 */
	public function get_tax_props( $field = null ) {
		if ( empty( $field ) ) {
			return $this->term_props;
		}

		$term_props = array();

		foreach ( $this->term_props as $taxonomy_name => $props ) {
			$term_props[ $taxonomy_name ] = $props[ $field ];
		}

		return $term_props;
	}

	/**
	 * Get the flag informations:
	 *
	 * @since 2.6
	 *
	 * @param string $code Flag code.
	 * @return array {
	 *   Flag informations.
	 *
	 *   @type string $url    Flag url.
	 *   @type string $src    Optional, src attribute value if different of the url, for example if base64 encoded.
	 *   @type int    $width  Optional, flag width in pixels.
	 *   @type int    $height Optional, flag height in pixels.
	 * }
	 */
	public static function get_flag_informations( $code ) {
		$flag = array( 'url' => '' );

		// Polylang builtin flags.
		if ( ! empty( $code ) && file_exists( POLYLANG_DIR . ( $file = '/flags/' . $code . '.png' ) ) ) {
			$flag['url'] = plugins_url( $file, POLYLANG_FILE );

			// If base64 encoded flags are preferred.
			if ( ! defined( 'PLL_ENCODED_FLAGS' ) || PLL_ENCODED_FLAGS ) {
				list( $flag['width'], $flag['height'] ) = getimagesize( POLYLANG_DIR . $file );
				$file_contents = file_get_contents( POLYLANG_DIR . $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				$flag['src'] = 'data:image/png;base64,' . base64_encode( $file_contents ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			}
		}

		/**
		 * Filters flag informations:
		 *
		 * @since 2.4
		 *
		 * @param array  $flag {
		 *   Information about the flag.
		 *
		 *   @type string $url    Flag url.
		 *   @type string $src    Optional, src attribute value if different of the url, for example if base64 encoded.
		 *   @type int    $width  Optional, flag width in pixels.
		 *   @type int    $height Optional, flag height in pixels.
		 * }
		 * @param string $code Flag code.
		 */
		$flag = apply_filters( 'pll_flag', $flag, $code );

		$flag['url'] = esc_url_raw( $flag['url'] );

		if ( empty( $flag['src'] ) ) {
			$flag['src'] = esc_url( set_url_scheme( $flag['url'], 'relative' ) );
		}

		return $flag;
	}

	/**
	 * Sets flag_url and flag properties.
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function set_flag() {
		$flags = array( 'flag' => self::get_flag_informations( $this->flag_code ) );

		// Custom flags?
		$directories = array(
			PLL_LOCAL_DIR,
			get_stylesheet_directory() . '/polylang',
			get_template_directory() . '/polylang',
		);

		foreach ( $directories as $dir ) {
			if ( file_exists( $file = "{$dir}/{$this->locale}.png" ) || file_exists( $file = "{$dir}/{$this->locale}.jpg" ) || file_exists( $file = "{$dir}/{$this->locale}.svg" ) ) {
				$flags['custom_flag']['url'] = content_url( '/' . str_replace( WP_CONTENT_DIR, '', $file ) );
				break;
			}
		}

		/**
		 * Filters the custom flag informations.
		 *
		 * @param array  $flag {
		 *   Information about the custom flag.
		 *
		 *   @type string $url    Flag url.
		 *   @type string $src    Optional, src attribute value if different of the url, for example if base64 encoded.
		 *   @type int    $width  Optional, flag width in pixels.
		 *   @type int    $height Optional, flag height in pixels.
		 * }
		 * @param string $code Flag code.
		 *
		 * @since 2.4
		 */
		$flags['custom_flag'] = apply_filters( 'pll_custom_flag', empty( $flags['custom_flag'] ) ? null : $flags['custom_flag'], $this->flag_code );

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
		$title = apply_filters( 'pll_flag_title', $this->name, $this->slug, $this->locale );

		foreach ( $flags as $key => $flag ) {
			$this->{$key . '_url'} = empty( $flag['url'] ) ? '' : $flag['url'];

			/**
			 * Filters the html markup of a flag.
			 *
			 * @since 1.0.2
			 *
			 * @param string $flag Html markup of the flag or empty string.
			 * @param string $slug Language code.
			 */
			$this->{$key} = apply_filters(
				'pll_get_flag',
				self::get_flag_html( $flag, $title, $this->name ),
				$this->slug
			);
		}
	}

	/**
	 * Get HTML code for flag.
	 *
	 * @since 2.7
	 *
	 * @param array  $flag  Flag properties: src, width and height.
	 * @param string $title Optional title attribute.
	 * @param string $alt   Optional alt attribute.
	 * @return string
	 */
	public static function get_flag_html( $flag, $title = '', $alt = '' ) {
		if ( empty( $flag['src'] ) ) {
			return '';
		}

		$alt_attr    = empty( $alt ) ? '' : sprintf( ' alt="%s"', esc_attr( $alt ) );
		$width_attr  = empty( $flag['width'] ) ? '' : sprintf( ' width="%s"', (int) $flag['width'] );
		$height_attr = empty( $flag['height'] ) ? '' : sprintf( ' height="%s"', (int) $flag['height'] );

		$style = '';
		$sizes = array_intersect_key( $flag, array_flip( array( 'width', 'height' ) ) );

		if ( ! empty( $sizes ) ) {
			array_walk(
				$sizes,
				function ( &$value, $key ) {
					$value = sprintf( '%s: %dpx;', esc_attr( $key ), (int) $value );
				}
			);
			$style = sprintf( ' style="%s"', implode( ' ', $sizes ) );
		}

		return sprintf(
			'<img src="%s"%s%s%s%s />',
			$flag['src'],
			$alt_attr,
			$width_attr,
			$height_attr,
			$style
		);
	}

	/**
	 * Returns the html of the custom flag if any, or the default flag otherwise.
	 *
	 * @since 2.8
	 *
	 * @return string
	 */
	public function get_display_flag() {
		return empty( $this->custom_flag ) ? $this->flag : $this->custom_flag;
	}

	/**
	 * Returns the url of the custom flag if any, or the default flag otherwise.
	 *
	 * @since 2.8
	 *
	 * @return string
	 */
	public function get_display_flag_url() {
		return empty( $this->custom_flag_url ) ? $this->flag_url : $this->custom_flag_url;
	}

	/**
	 * Updates post and term count.
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function update_count() {
		foreach ( $this->term_props as $taxonomy => $props ) {
			wp_update_term_count( $props['term_taxonomy_id'], $taxonomy );
		}
	}

	/**
	 * Set home_url and search_url properties.
	 *
	 * @since 1.3
	 *
	 * @param string $search_url Home url to use in search forms.
	 * @param string $home_url   Home url.
	 * @return void
	 */
	public function set_home_url( $search_url, $home_url ) {
		if ( empty( $search_url ) ) {
			$this->search_url = null;
		} else {
			$this->search_url = $search_url;
		}
		if ( empty( $home_url ) ) {
			$this->home_url = null;
		} else {
			$this->home_url = $home_url;
		}
	}

	/**
	 * Sets the scheme of the home url and the flag urls.
	 *
	 * This can't be cached across pages.
	 *
	 * @since 2.8
	 *
	 * @return void
	 */
	public function set_url_scheme() {
		$props = array( 'home_url', 'search_url', 'flag_url', 'custom_flag_url' );

		foreach ( $props as $prop ) {
			if ( empty( $this->$prop ) ) {
				continue;
			}

			$url = set_url_scheme( $this->$prop );

			if ( ! empty( $url ) ) {
				$this->$prop = $url;
			}
		}
	}

	/**
	 * Returns the language locale.
	 * Converts WP locales to W3C valid locales for display.
	 *
	 * @since 1.8
	 *
	 * @param string $filter Either 'display' or 'raw', defaults to raw.
	 * @return string
	 */
	public function get_locale( $filter = 'raw' ) {
		return 'display' === $filter ? $this->w3c : $this->locale;
	}

	/**
	 * Returns the values of this instance's properties.
	 *
	 * @since 3.4
	 *
	 * @return array
	 */
	public function get_object_vars() {
		return get_object_vars( $this );
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
	public static function validate_data( array $data ) {
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
}
