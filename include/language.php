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
 *
 * @phpstan-type LanguageData array{
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
 *     mo_id: int,
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
	 * Duplicated from `$this->term_props['language']['term_id'],
	 * but kept to facilitate the use of it.
	 *
	 * @var int
	 *
	 * @phpstan-var int<1, max>
	 */
	public $term_id;

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
	 * @var string
	 */
	public $facebook = '';

	/**
	 * Home URL in this language.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	public $home_url;

	/**
	 * Home URL to use in search forms.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	public $search_url;

	/**
	 * Host corresponding to this language.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	public $host;

	/**
	 * ID of the post storing strings translations.
	 *
	 * @var int
	 */
	public $mo_id;

	/**
	 * ID of the page on front in this language (set from pll_languages_list filter).
	 *
	 * @var int
	 */
	public $page_on_front;

	/**
	 * ID of the page for posts in this language (set from pll_languages_list filter).
	 *
	 * @var int
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
	 * URL of the flag. Always set to the main domain.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	public $flag_url;

	/**
	 * HTML markup of the flag.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	public $flag;

	/**
	 * URL of the custom flag if it exists. Always set to the main domain.
	 *
	 * @var string
	 */
	public $custom_flag_url = '';

	/**
	 * HTML markup of the custom flag if it exists.
	 *
	 * @var string
	 */
	public $custom_flag = '';

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
	 * @phpstan-param LanguageData $language_data
	 */
	public function __construct( array $language_data ) {
		foreach ( $language_data as $prop => $value ) {
			$this->$prop = $value;
		}

		$this->term_id = $this->term_props['language']['term_id'];
	}

	/**
	 * Throws a depreciation notice if someone tries to get one of the following properties:
	 * `term_taxonomy_id`, `count`, `tl_term_id`, `tl_term_taxonomy_id` or `tl_count`.
	 *
	 * Backward compatibility with Polylang < 3.4.
	 *
	 * @since 3.4
	 *
	 * @param string $property Property to get.
	 * @return mixed Required property value.
	 */
	public function __get( $property ) {
		$deprecated_properties = array(
			'term_taxonomy_id'    => array( 'language', 'term_taxonomy_id' ),
			'count'               => array( 'language', 'count' ),
			'tl_term_id'          => array( 'term_language', 'term_id' ),
			'tl_term_taxonomy_id' => array( 'term_language', 'term_taxonomy_id' ),
			'tl_count'            => array( 'term_language', 'count' ),
		);

		// Deprecated property.
		if ( array_key_exists( $property, $deprecated_properties ) ) {
			$term_prop_type = $deprecated_properties[ $property ][0];
			$term_prop      = $deprecated_properties[ $property ][1];

			/** This filter is documented in wordpress/wp-includes/functions.php */
			if ( WP_DEBUG && apply_filters( 'deprecated_function_trigger_error', true ) ) {
				trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
					sprintf(
						"Class property %1\$s::\$%2\$s is deprecated, use %1\$s::get_tax_prop( '%3\$s', '%4\$s' ) instead.\nError handler",
						esc_html( get_class( $this ) ),
						esc_html( $property ),
						esc_html( $term_prop_type ),
						esc_html( $term_prop )
					),
					E_USER_DEPRECATED
				);
			}

			return $this->term_props[ $term_prop_type ][ $term_prop ];
		}

		// Undefined property.
		if ( ! property_exists( $this, $property ) ) {
			return null;
		}

		// The property is defined.
		$ref = new ReflectionProperty( $this, $property );

		// Public property.
		if ( $ref->isPublic() ) {
			return $this->{$property};
		}

		// Protected or private property.
		$visibility = $ref->isPrivate() ? 'private' : 'protected';
		$trace      = debug_backtrace(); // phpcs:ignore PHPCompatibility.FunctionUse.ArgumentFunctionsReportCurrentValue.NeedsInspection, WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
		$file       = isset( $trace[0]['file'] ) ? $trace[0]['file'] : '';
		$line       = isset( $trace[0]['line'] ) ? $trace[0]['line'] : 0;
		trigger_error( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
			esc_html(
				sprintf(
					"Cannot access %s property %s::$%s in %s on line %d.\nError handler",
					$visibility,
					get_class( $this ),
					$property,
					$file,
					$line
				)
			),
			E_USER_ERROR
		);
	}

	/**
	 * Checks for a deprecated property.
	 * Is triggered by calling `isset()` or `empty()` on inaccessible (protected or private) or non-existing properties.
	 *
	 * Backward compatibility with Polylang < 3.4.
	 *
	 * @since 3.4
	 *
	 * @param string $property A property name.
	 * @return bool
	 */
	public function __isset( $property ) {
		$deprecated_properties = array( 'term_taxonomy_id', 'count', 'tl_term_id', 'tl_term_taxonomy_id', 'tl_count' );
		return in_array( $property, $deprecated_properties, true );
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
	public function get_tax_props( $field = '' ) {
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
		// Add filter with site_url().
		$flag_url = empty( $this->custom_flag_url ) ? $this->flag_url : $this->custom_flag_url;


		return apply_filters( 'pll_get_display_flag_url', $flag_url, $this );
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
	 *
	 * @phpstan-param non-empty-string $search_url
	 * @phpstan-param non-empty-string $home_url
	 */
	public function set_home_url( $search_url, $home_url ) {
		$this->search_url = $search_url;
		$this->home_url   = $home_url;
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
			if ( ! empty( $this->$prop ) ) {
				$this->$prop = set_url_scheme( $this->$prop );
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
	 * Returns a predefined HTML flag.
	 *
	 * @since 3.4
	 *
	 * @param string $flag_code Flag code to render.
	 * @return string HTML code for the flag.
	 */
	public static function get_predefined_flag( $flag_code ) {
		$flag = self::get_flag_informations( $flag_code );

		return self::get_flag_html( $flag );
	}
}
