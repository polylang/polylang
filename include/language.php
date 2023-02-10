<?php
/**
 * @package Polylang
 */

/**
 * A language object is made of two terms in 'language' and 'term_language' taxonomies.
 * Manipulating only one object per language instead of two terms should make things easier.
 *
 * @since 1.2
 */
#[AllowDynamicProperties]
class PLL_Language {
	/**
	 * Id of the term in 'language' taxonomy.
	 *
	 * @var int
	 */
	public $term_id;

	/**
	 * Language name. Ex: English.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Language code used in url. Ex: en.
	 *
	 * @var string
	 */
	public $slug;

	/**
	 * Order of the language when displayed in a list of languages.
	 *
	 * @var int
	 */
	public $term_group;

	/**
	 * Term taxonomy id in 'language' taxonomy.
	 *
	 * @var int
	 */
	public $term_taxonomy_id;

	/**
	 * Number of posts and pages in that language.
	 *
	 * @var int
	 */
	public $count;

	/**
	 * Id of the term in 'term_language' taxonomy.
	 *
	 * @var int
	 */
	public $tl_term_id;

	/**
	 * Term taxonomy id in 'term_language' taxonomy.
	 *
	 * @var int
	 */
	public $tl_term_taxonomy_id;

	/**
	 * Number of terms in that language.
	 *
	 * @var int
	 */
	public $tl_count;

	/**
	 * WordPress language locale. Ex: en_US.
	 *
	 * @var string
	 */
	public $locale;

	/**
	 * 1 if the language is rtl, 0 otherwise.
	 *
	 * @var int
	 */
	public $is_rtl;

	/**
	 * W3C locale.
	 *
	 * @var string
	 */
	public $w3c;

	/**
	 * Facebook locale.
	 *
	 * @var string|null
	 */
	public $facebook;

	/**
	 * Home url in this language.
	 *
	 * @var string|null
	 */
	public $home_url;

	/**
	 * Home url to use in search forms.
	 *
	 * @var string|null
	 */
	public $search_url;

	/**
	 * Host corresponding to this language.
	 *
	 * @var string|null
	 */
	public $host;

	/**
	 * Id of the post storing strings translations.
	 *
	 * @var int
	 */
	public $mo_id;

	/**
	 * Id of the page on front in this language ( set from pll_languages_list filter ).
	 *
	 * @var int|null
	 */
	public $page_on_front;

	/**
	 * Id of the page for posts in this language ( set from pll_languages_list filter ).
	 *
	 * @var int|null
	 */
	public $page_for_posts;

	/**
	 * Code of the flag.
	 *
	 * @var string
	 */
	public $flag_code;

	/**
	 * Url of the flag.
	 *
	 * @var string|null
	 */
	public $flag_url;

	/**
	 * Html markup of the flag.
	 *
	 * @var string|null
	 */
	public $flag;

	/**
	 * Url of the custom flag if it exists.
	 *
	 * @var string|null
	 */
	public $custom_flag_url;

	/**
	 * Html markup of the custom flag if it exists.
	 *
	 * @var string|null
	 */
	public $custom_flag;

	/**
	 * Constructor: builds a language object given its two corresponding terms in 'language' and 'term_language' taxonomies.
	 *
	 * @since 1.2
	 *
	 * @param WP_Term|array $language      Term in 'language' taxonomy or language object properties stored as an array.
	 * @param WP_Term       $term_language Corresponding 'term_language' term.
	 */
	public function __construct( $language, $term_language = null ) {
		if ( empty( $term_language ) ) {
			// Build the object from all properties stored as an array.
			foreach ( $language as $prop => $value ) {
				$this->$prop = $value;
			}
		} else {
			// Build the object from taxonomy terms.
			$this->term_id = (int) $language->term_id;
			$this->name = $language->name;
			$this->slug = $language->slug;
			$this->term_group = (int) $language->term_group;
			$this->term_taxonomy_id = (int) $language->term_taxonomy_id;
			$this->count = (int) $language->count;

			$this->tl_term_id = (int) $term_language->term_id;
			$this->tl_term_taxonomy_id = (int) $term_language->term_taxonomy_id;
			$this->tl_count = (int) $term_language->count;

			// The description field can contain any property.
			$description = maybe_unserialize( $language->description );
			foreach ( $description as $prop => $value ) {
				'rtl' == $prop ? $this->is_rtl = $value : $this->$prop = $value;
			}

			$this->mo_id = PLL_MO::get_id( $this );

			$languages = include POLYLANG_DIR . '/settings/languages.php';
			$this->w3c = isset( $languages[ $this->locale ]['w3c'] ) ? $languages[ $this->locale ]['w3c'] : str_replace( '_', '-', $this->locale );
			if ( isset( $languages[ $this->locale ]['facebook'] ) ) {
				$this->facebook = $languages[ $this->locale ]['facebook'];
			}
		}
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
		if ( ! empty( $code ) && is_readable( POLYLANG_DIR . ( $file = '/flags/' . $code . '.png' ) ) ) {
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
			if ( is_readable( $file = "{$dir}/{$this->locale}.png" ) || is_readable( $file = "{$dir}/{$this->locale}.jpg" ) || is_readable( $file = "{$dir}/{$this->locale}.svg" ) ) {
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
		wp_update_term_count( $this->term_taxonomy_id, 'language' ); // Posts count.
		wp_update_term_count( $this->tl_term_taxonomy_id, 'term_language' ); // Terms count.
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
		$this->search_url = $search_url;
		$this->home_url = $home_url;
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
		if ( ! empty( $this->home_url ) ) {
			$this->home_url = set_url_scheme( $this->home_url );
		}

		if ( ! empty( $this->search_url ) ) {
			$this->search_url = set_url_scheme( $this->search_url );
		}

		// Set url scheme, also for the flags.
		if ( ! empty( $this->flag_url ) ) {
			$this->flag_url = set_url_scheme( $this->flag_url );
		}

		if ( ! empty( $this->custom_flag_url ) ) {
			$this->custom_flag_url = set_url_scheme( $this->custom_flag_url );
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
}
