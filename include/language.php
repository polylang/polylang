<?php

/**
 * A language object is made of two terms in 'language' and 'term_language' taxonomies
 * manipulating only one object per language instead of two terms should make things easier
 *
 * Properties:
 * term_id             => id of term in 'language' taxonomy
 * name                => language name. Ex: English
 * slug                => language code used in url. Ex: en
 * term_group          => order of the language when displayed in a list of languages
 * term_taxonomy_id    => term taxonomy id in 'language' taxonomy
 * taxonomy            => 'language'
 * description         => language locale for backward compatibility
 * parent              => 0 / not used
 * count               => number of posts and pages in that language
 * tl_term_id          => id of the term in 'term_language' taxonomy
 * tl_term_taxonomy_id => term taxonomy id in 'term_language' taxonomy
 * tl_count            => number of terms in that language ( not used by Polylang )
 * locale              => WordPress language locale. Ex: en_US
 * is_rtl              => 1 if the language is rtl
 * w3c                 => W3C locale
 * flag_code           => code of the flag
 * flag_url            => url of the flag
 * flag                => html img of the flag
 * custom_flag_url     => url of the custom flag if exists, internal use only, moves to flag_url on frontend
 * custom_flag         => html img of the custom flag if exists, internal use only, moves to flag on frontend
 * home_url            => home url in this language
 * search_url          => home url to use in search forms
 * host                => host of this language
 * mo_id               => id of the post storing strings translations
 * page_on_front       => id of the page on front in this language ( set from pll_languages_list filter )
 * page_for_posts      => id of the page for posts in this language ( set from pll_languages_list filter )
 *
 * @since 1.2
 */
class PLL_Language {
	public $term_id, $name, $slug, $term_group, $term_taxonomy_id, $taxonomy, $description, $parent, $count;
	public $tl_term_id, $tl_term_taxonomy_id, $tl_count;
	public $locale, $is_rtl;
	public $w3c, $facebook;
	public $flag_url, $flag;
	public $home_url, $search_url;
	public $host, $mo_id;
	public $page_on_front, $page_for_posts;

	/**
	 * Constructor: builds a language object given its two corresponding terms in language and term_language taxonomies
	 *
	 * @since 1.2
	 *
	 * @param object|array $language      'language' term or language object properties stored as an array
	 * @param object       $term_language Corresponding 'term_language' term
	 */
	public function __construct( $language, $term_language = null ) {
		// Build the object from all properties stored as an array
		if ( empty( $term_language ) ) {
			foreach ( $language as $prop => $value ) {
				$this->$prop = $value;
			}
		}

		// Build the object from taxonomies
		else {
			foreach ( $language as $prop => $value ) {
				$this->$prop = in_array( $prop, array( 'term_id', 'term_taxonomy_id', 'count' ) ) ? (int) $language->$prop : $language->$prop;
			}

			$this->tl_term_id = (int) $term_language->term_id;
			$this->tl_term_taxonomy_id = (int) $term_language->term_taxonomy_id;
			$this->tl_count = (int) $term_language->count;

			// The description field can contain any property
			// Backward compatibility for is_rtl
			$description = maybe_unserialize( $language->description );
			foreach ( $description as $prop => $value ) {
				'rtl' == $prop ? $this->is_rtl = $value : $this->$prop = $value;
			}

			$this->description = &$this->locale; // Backward compatibility with Polylang < 1.2

			$this->mo_id = PLL_MO::get_id( $this );

			include PLL_SETTINGS_INC . '/languages.php';
			$this->w3c = isset( $languages[ $this->locale ]['w3c'] ) ? $languages[ $this->locale ]['w3c'] : str_replace( '_', '-', $this->locale );
			if ( isset( $languages[ $this->locale ]['facebook'] ) ) {
				$this->facebook = $languages[ $this->locale ]['facebook'];
			}
		}
	}

	/**
	 * Sets flag_url and flag properties
	 *
	 * @since 1.2
	 */
	public function set_flag() {
		$flags['flag']['url'] = '';

		// Polylang builtin flags
		if ( ! empty( $this->flag_code ) && file_exists( POLYLANG_DIR . ( $file = '/flags/' . $this->flag_code . '.png' ) ) ) {
			$flags['flag']['url'] = esc_url_raw( plugins_url( $file, POLYLANG_FILE ) );

			// If base64 encoded flags are preferred
			if ( ! defined( 'PLL_ENCODED_FLAGS' ) || PLL_ENCODED_FLAGS ) {
				$flags['flag']['src'] = 'data:image/png;base64,' . base64_encode( file_get_contents( POLYLANG_DIR . $file ) );
			} else {
				$flags['flag']['src'] = esc_url( plugins_url( $file, POLYLANG_FILE ) );
			}
		}

		// Custom flags ?
		if ( file_exists( PLL_LOCAL_DIR . ( $file = '/' . $this->locale . '.png' ) ) || file_exists( PLL_LOCAL_DIR . ( $file = '/' . $this->locale . '.jpg' ) ) ) {
			$url = content_url( '/polylang' . $file );
			$flags['custom_flag']['url'] = esc_url_raw( $url );
			$flags['custom_flag']['src'] = esc_url( $url );
		}

		/**
		 * Filter the flag title attribute
		 * Defaults to the language name
		 *
		 * @since 0.7
		 *
		 * @param string $title  the flag title attribute
		 * @param string $slug   the language code
		 * @param string $locale the language locale
		 */
		$title = apply_filters( 'pll_flag_title', $this->name, $this->slug, $this->locale );

		foreach ( $flags as $key => $flag ) {
			$this->{$key . '_url'} = empty( $flag['url'] ) ? '' : $flag['url'];

			/**
			 * Filter the html markup of a flag
			 *
			 * @since 1.0.2
			 *
			 * @param string $flag html markup of the flag or empty string
			 * @param string $slug language code
			 */
			$this->{$key} = apply_filters( 'pll_get_flag', empty( $flag['src'] ) ? '' :
				sprintf(
					'<img src="%s" title="%s" alt="%s" />',
					$flag['src'],
					esc_attr( $title ),
					esc_attr( $this->name )
				),
				$this->slug
			);
		}
	}

	/**
	 * Replace flag by custom flag
	 * Takes care of url scheme
	 *
	 * @since 1.7
	 */
	public function set_custom_flag() {
		// Overwrite with custom flags on frontend only
		if ( ! empty( $this->custom_flag ) ) {
			$this->flag = $this->custom_flag;
			$this->flag_url = $this->custom_flag_url;
			unset( $this->custom_flag, $this->custom_flag_url ); // hide this
		}

		// Set url scheme, also for default flags
		if ( is_ssl() ) {
			$this->flag = str_replace( 'http://', 'https://', $this->flag );
			$this->flag_url = str_replace( 'http://', 'https://', $this->flag_url );
		} else {
			$this->flag = str_replace( 'https://', 'http://', $this->flag );
			$this->flag_url = str_replace( 'https://', 'http://', $this->flag_url );
		}
	}

	/**
	 * Updates post and term count
	 *
	 * @since 1.2
	 */
	public function update_count() {
		wp_update_term_count( $this->term_taxonomy_id, 'language' ); // posts count
		wp_update_term_count( $this->tl_term_taxonomy_id, 'term_language' ); // terms count
	}

	/**
	 * Set home_url and search_url properties
	 *
	 * @since 1.3
	 *
	 * @param string $search_url
	 * @param string $home_url
	 */
	public function set_home_url( $search_url, $home_url ) {
		$this->search_url = $search_url;
		$this->home_url = $home_url;
	}

	/**
	 * Set home_url scheme
	 * this can't be cached across pages
	 *
	 * @since 1.6.4
	 */
	public function set_home_url_scheme() {
		if ( is_ssl() ) {
			$this->home_url = str_replace( 'http://', 'https://', $this->home_url );
			$this->search_url = str_replace( 'http://', 'https://', $this->search_url );
		}

		else {
			$this->home_url = str_replace( 'https://', 'http://', $this->home_url );
			$this->search_url = str_replace( 'https://', 'http://', $this->search_url );
		}
	}

	/**
	 * Returns the language locale
	 * Converts WP locales to W3C valid locales for display
	 *
	 * @since 1.8
	 *
	 * @param string $filter either 'display' or 'raw', defaults to raw
	 * @return string
	 */
	public function get_locale( $filter = 'raw' ) {
		return 'display' === $filter ? $this->w3c : $this->locale;
	}
}
