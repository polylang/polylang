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
 * @phpstan-type LanguagePropData array{
 *     term_id: positive-int,
 *     term_taxonomy_id: positive-int,
 *     count: int<0, max>
 * }
 * @phpstan-type LanguageData array{
 *     term_props: array{
 *         language: LanguagePropData,
 *     }&array<non-empty-string, LanguagePropData>,
 *     name: non-empty-string,
 *     slug: non-empty-string,
 *     locale: non-empty-string,
 *     w3c: non-empty-string,
 *     flag_code: non-empty-string,
 *     term_group: int,
 *     is_rtl: int<0, 1>,
 *     facebook?: string,
 *     home_url: non-empty-string,
 *     search_url: non-empty-string,
 *     host: non-empty-string,
 *     flag_url: non-empty-string,
 *     flag: non-empty-string,
 *     custom_flag_url?: string,
 *     custom_flag?: string,
 *     page_on_front: int<0, max>,
 *     page_for_posts: int<0, max>,
 *     active: bool,
 *     fallbacks?: array<non-empty-string>,
 *     is_default: bool
 * }
 */
class PLL_Language extends PLL_Language_Deprecated {

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
	private $home_url;

	/**
	 * Home URL to use in search forms.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	private $search_url;

	/**
	 * Host corresponding to this language.
	 *
	 * @var string
	 *
	 * @phpstan-var non-empty-string
	 */
	public $host;

	/**
	 * ID of the page on front in this language (set from pll_additional_language_data filter).
	 *
	 * @var int
	 *
	 * @phpstan-var int<0, max>
	 */
	public $page_on_front = 0;

	/**
	 * ID of the page for posts in this language (set from pll_additional_language_data filter).
	 *
	 * @var int
	 *
	 * @phpstan-var int<0, max>
	 */
	public $page_for_posts = 0;

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
	 * Whether or not the language is active. Default `true`.
	 *
	 * @var bool
	 */
	public $active = true;

	/**
	 * List of WordPress language locales. Ex: array( 'en_GB' ).
	 *
	 * @var string[]
	 *
	 * @phpstan-var list<non-empty-string>
	 */
	public $fallbacks = array();

	/**
	 * Whether the language is the default one.
	 *
	 * @var bool
	 */
	public $is_default;

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
	 * @phpstan-var array{
	 *         language: LanguagePropData,
	 *     }
	 *     &array<non-empty-string, LanguagePropData>
	 */
	protected $term_props;

	/**
	 * Constructor: builds a language object given the corresponding data.
	 *
	 * @since 1.2
	 * @since 3.4 Only accepts one argument.
	 *
	 * @param array $language_data {
	 *     Language object properties stored as an array.
	 *
	 *     @type array[]  $term_props      An array of language term properties. Array keys are language taxonomy names
	 *                                     (`language` and `term_language` are mandatory), array values are arrays of
	 *                                     language term properties (`term_id`, `term_taxonomy_id`, and `count`).
	 *     @type string   $name            Language name. Ex: English.
	 *     @type string   $slug            Language code used in URL. Ex: en.
	 *     @type string   $locale          WordPress language locale. Ex: en_US.
	 *     @type string   $w3c             W3C locale.
	 *     @type string   $flag_code       Code of the flag.
	 *     @type int      $term_group      Order of the language when displayed in a list of languages.
	 *     @type int      $is_rtl          `1` if the language is rtl, `0` otherwise.
	 *     @type string   $facebook        Optional. Facebook locale.
	 *     @type string   $home_url        Home URL in this language.
	 *     @type string   $search_url      Home URL to use in search forms.
	 *     @type string   $host            Host corresponding to this language.
	 *     @type string   $flag_url        URL of the flag.
	 *     @type string   $flag            HTML markup of the flag.
	 *     @type string   $custom_flag_url Optional. URL of the custom flag if it exists.
	 *     @type string   $custom_flag     Optional. HTML markup of the custom flag if it exists.
	 *     @type int      $page_on_front   Optional. ID of the page on front in this language.
	 *     @type int      $page_for_posts  Optional. ID of the page for posts in this language.
	 *     @type bool     $active          Whether or not the language is active. Default `true`.
	 *     @type string[] $fallbacks       List of WordPress language locales. Ex: array( 'en_GB' ).
	 *     @type bool     $is_default      Whether or not the language is the default one.
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
	 * @param string $property Name of the field to return. An empty string to return them all.
	 * @return (int[]|int)[] Array keys are taxonomy names, array values depend of `$property`.
	 *
	 * @phpstan-param 'term_taxonomy_id'|'term_id'|'count'|'' $property
	 * @phpstan-return array<non-empty-string, (
	 *     $property is non-empty-string ?
	 *     (
	 *         $property is 'count' ?
	 *         int<0, max> :
	 *         positive-int
	 *     ) :
	 *     LanguagePropData
	 * )>
	 */
	public function get_tax_props( $property = '' ) {
		if ( empty( $property ) ) {
			return $this->term_props;
		}

		$term_props = array();

		foreach ( $this->term_props as $taxonomy_name => $props ) {
			$term_props[ $taxonomy_name ] = $props[ $property ];
		}

		return $term_props;
	}

	/**
	 * Returns the flag informations.
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
	 *
	 * @phpstan-return array{
	 *     url: string,
	 *     src: string,
	 *     width?: positive-int,
	 *     height?: positive-int
	 * }
	 */
	public static function get_flag_informations( $code ) {
		$flag = array( 'url' => '' );

		// Polylang builtin flags.
		if ( ! empty( $code ) && is_readable( POLYLANG_DIR . ( $file = '/flags/' . $code . '.png' ) ) ) {
			$flag['url'] = plugins_url( $file, POLYLANG_FILE );

			// If base64 encoded flags are preferred.
			if ( ! defined( 'PLL_ENCODED_FLAGS' ) || PLL_ENCODED_FLAGS ) {
				$imagesize = getimagesize( POLYLANG_DIR . $file );
				if ( is_array( $imagesize ) ) {
					list( $flag['width'], $flag['height'] ) = $imagesize;
				}
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
	 * Returns HTML code for flag.
	 *
	 * @since 2.7
	 *
	 * @param array  $flag  Flag properties: src, width and height.
	 * @param string $title Optional title attribute.
	 * @param string $alt   Optional alt attribute.
	 * @return string
	 *
	 * @phpstan-param array{
	 *     src: string,
	 *     width?: int|numeric-string,
	 *     height?: int|numeric-string
	 * } $flag
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
		$flag_url = empty( $this->custom_flag_url ) ? $this->flag_url : $this->custom_flag_url;

		/**
		 * Filters `flag_url` property.
		 *
		 * @since 3.4.4
		 *
		 * @param string       $flag_url Flag URL.
		 * @param PLL_Language $language Current `PLL_language` instance.
		 */
		return apply_filters( 'pll_language_flag_url', $flag_url, $this );
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
	 * Returns the language locale.
	 * Converts WP locales to W3C valid locales for display.
	 *
	 * @since 1.8
	 *
	 * @param string $filter Either 'display' or 'raw', defaults to raw.
	 * @return string
	 *
	 * @phpstan-param 'display'|'raw' $filter
	 * @phpstan-return non-empty-string
	 */
	public function get_locale( $filter = 'raw' ) {
		return 'display' === $filter ? $this->w3c : $this->locale;
	}

	/**
	 * Returns the values of this instance's properties, which can be filtered if required.
	 *
	 * @since 3.4
	 *
	 * @param string $context Whether or not properties should be filtered. Accepts `db` or `display`.
	 *                        Default to `display` which filters some properties.
	 *
	 * @return array Array of language object properties.
	 *
	 * @phpstan-return LanguageData
	 */
	public function to_array( $context = 'display' ) {
		$language = get_object_vars( $this );

		if ( 'db' !== $context ) {
			$language['home_url']   = $this->get_home_url();
			$language['search_url'] = $this->get_search_url();
		}

		/** @phpstan-var LanguageData $language */
		return $language;
	}

	/**
	 * Converts current `PLL_language` into a `stdClass` object. Mostly used to allow dynamic properties.
	 *
	 * @since 3.4
	 *
	 * @return stdClass Converted `PLL_Language` object.
	 */
	public function to_std_class() {
		return (object) $this->to_array();
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

	/**
	 * Returns language's home URL. Takes care to render it dynamically if no cache is allowed.
	 *
	 * @since 3.4
	 *
	 * @return string Language home URL.
	 */
	public function get_home_url() {
		if ( ( defined( 'PLL_CACHE_LANGUAGES' ) && ! PLL_CACHE_LANGUAGES ) || ( defined( 'PLL_CACHE_HOME_URL' ) && ! PLL_CACHE_HOME_URL ) ) {
			/**
			 * Filters current `PLL_Language` instance `home_url` property.
			 *
			 * @since 3.4.4
			 *
			 * @param string $home_url         The `home_url` prop.
			 * @param array  $language Current Array of `PLL_Language` properties.
			 */
			return apply_filters( 'pll_language_home_url', $this->home_url, $this->to_array( 'db' ) );
		}

		return $this->home_url;
	}

	/**
	 * Returns language's search URL. Takes care to render it dynamically if no cache is allowed.
	 *
	 * @since 3.4
	 *
	 * @return string Language search URL.
	 */
	public function get_search_url() {
		if ( ( defined( 'PLL_CACHE_LANGUAGES' ) && ! PLL_CACHE_LANGUAGES ) || ( defined( 'PLL_CACHE_HOME_URL' ) && ! PLL_CACHE_HOME_URL ) ) {
			/**
			 * Filters current `PLL_Language` instance `search_url` property.
			 *
			 * @since 3.4.4
			 *
			 * @param string $search_url        The `search_url` prop.
			 * @param array  $language Current Array of `PLL_Language` properties.
			 */
			return apply_filters( 'pll_language_search_url', $this->search_url, $this->to_array( 'db' ) );
		}

		return $this->search_url;
	}

	/**
	 * Returns the value of a language property.
	 * This is handy to get a property's value without worrying about triggering a deprecation warning or anything.
	 *
	 * @since 3.4
	 *
	 * @param string $property A property name. A composite value can be used for language term property values, in the
	 *                         form of `{language_taxonomy_name}:{property_name}` (see {@see PLL_Language::get_tax_prop()}
	 *                         for the possible values). Ex: `term_language:term_taxonomy_id`.
	 * @return string|int|bool|string[] The requested property for the language, `false` if the property doesn't exist.
	 *
	 * @phpstan-return (
	 *     $property is 'slug' ? non-empty-string : string|int|bool|list<non-empty-string>
	 * )
	 */
	public function get_prop( $property ) {
		// Deprecated property.
		if ( $this->is_deprecated_term_property( $property ) ) {
			return $this->get_deprecated_term_property( $property );
		}

		if ( $this->is_deprecated_url_property( $property ) ) {
			return $this->get_deprecated_url_property( $property );
		}

		// Composite property like 'term_language:term_taxonomy_id'.
		if ( preg_match( '/^(?<tax>.{1,32}):(?<field>term_id|term_taxonomy_id|count)$/', $property, $matches ) ) {
			/** @var array{tax:non-empty-string, field:'term_id'|'term_taxonomy_id'|'count'} $matches */
			return $this->get_tax_prop( $matches['tax'], $matches['field'] );
		}

		// Any other public property.
		if ( isset( $this->$property ) ) {
			return $this->$property;
		}

		return false;
	}
}
