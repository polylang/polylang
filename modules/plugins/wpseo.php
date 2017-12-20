<?php

/**
 * Manages the compatibility with Yoast SEO
 *
 * @since 2.3
 */
class PLL_WPSEO {
	/**
	 * Translate options and add specific filters and actions
	 *
	 * @since 1.6.4
	 */
	public function init() {
		if ( ! defined( 'WPSEO_VERSION' ) ) {
			return;
		}

		if ( ! PLL() instanceof PLL_Frontend ) {
			add_action( 'admin_init', array( $this, 'wpseo_register_strings' ) );
			return;
		}

		add_filter( 'option_wpseo_titles', array( $this, 'wpseo_translate_titles' ) );

		// Reloads options once the language has been defined to enable translations
		// Useful only when the language is set from content
		if ( did_action( 'wp_loaded' ) ) {
			$wpseo_front = WPSEO_Frontend::get_instance();
			$options = WPSEO_Options::get_option_names();
			foreach ( $options as $opt ) {
				$wpseo_front->options = array_merge( $wpseo_front->options, (array) get_option( $opt ) );
			}
		}

		// Filters sitemap queries to remove inactive language or to get
		// one sitemap per language when using multiple domains or subdomains
		// because WPSEO does not accept several domains or subdomains in one sitemap
		add_filter( 'wpseo_posts_join', array( $this, 'wpseo_posts_join' ), 10, 2 );
		add_filter( 'wpseo_posts_where', array( $this, 'wpseo_posts_where' ), 10, 2 );
		add_filter( 'wpseo_typecount_join', array( $this, 'wpseo_posts_join' ), 10, 2 );
		add_filter( 'wpseo_typecount_where', array( $this, 'wpseo_posts_where' ), 10, 2 );

		if ( PLL()->options['force_lang'] > 1 ) {
			add_filter( 'wpseo_enable_xml_sitemap_transient_caching', '__return_false' ); // Disable cache! otherwise WPSEO keeps only one domain (thanks to Junaid Bhura)
			add_filter( 'home_url', array( $this, 'wpseo_home_url' ), 10, 2 ); // Fix home_url
		} else {
			// Get all terms in all languages when the language is set from the content or directory name
			add_filter( 'get_terms_args', array( $this, 'wpseo_remove_terms_filter' ) );

			// Add the homepages for all languages to the sitemap when the front page displays posts
			if ( ! get_option( 'page_on_front' ) ) {
				add_filter( 'wpseo_sitemap_post_content', array( $this, 'add_language_home_urls' ) );
			}
		}

		add_filter( 'pll_home_url_white_list', array( $this, 'wpseo_home_url_white_list' ) );
		add_action( 'wpseo_opengraph', array( $this, 'wpseo_ogp' ), 2 );
		add_filter( 'wpseo_canonical', array( $this, 'wpseo_canonical' ) );
	}

	/**
	 * Helper function to register strings for custom post types and custom taxonomies titles and meta descriptions
	 *
	 * @since 2.1.6
	 *
	 * @param array $options
	 * @param array $titles
	 * @return array
	 */
	protected function _wpseo_register_strings( $options, $titles ) {
		foreach ( $titles as $title ) {
			if ( ! empty( $options[ $title ] ) ) {
				pll_register_string( $title, $options[ $title ], 'wordpress-seo' );
			}
		}
		return $options;
	}

	/**
	 * Registers strings for custom post types and custom taxonomies titles and meta descriptions
	 *
	 * @since 2.0
	 */
	function wpseo_register_strings() {
		$options = get_option( 'wpseo_titles' );
		foreach ( get_post_types( array( 'public' => true, '_builtin' => false ) ) as $t ) {
			if ( pll_is_translated_post_type( $t ) ) {
				$this->_wpseo_register_strings( $options, array( 'title-' . $t, 'metadesc-' . $t ) );
			}
		}
		foreach ( get_post_types( array( 'has_archive' => true, '_builtin' => false ) ) as $t ) {
			if ( pll_is_translated_post_type( $t ) ) {
				$this->_wpseo_register_strings( $options, array( 'title-ptarchive-' . $t, 'metadesc-ptarchive-' . $t, 'bctitle-ptarchive-' . $t ) );
			}
		}
		foreach ( get_taxonomies( array( 'public' => true, '_builtin' => false ) ) as $t ) {
			if ( pll_is_translated_taxonomy( $t ) ) {
				$this->_wpseo_register_strings( $options, array( 'title-tax-' . $t, 'metadesc-tax-' . $t ) );
			}
		}
	}

	/**
	 * Helper function to translate custom post types and custom taxonomies titles and meta descriptions
	 *
	 * @since 2.1.6
	 *
	 * @param array $options
	 * @param array $titles
	 * @return array
	 */
	protected function _wpseo_translate_titles( $options, $titles ) {
		foreach ( $titles as $title ) {
			if ( ! empty( $options[ $title ] ) ) {
				$options[ $title ] = pll__( $options[ $title ] );
			}
		}
		return $options;
	}

	/**
	 * Translates strings for custom post types and custom taxonomies titles and meta descriptions
	 *
	 * @since 2.0
	 *
	 * @param array $options
	 * @return array
	 */
	function wpseo_translate_titles( $options ) {
		if ( PLL() instanceof PLL_Frontend ) {
			foreach ( get_post_types( array( 'public' => true, '_builtin' => false ) ) as $t ) {
				if ( pll_is_translated_post_type( $t ) ) {
					$options = $this->_wpseo_translate_titles( $options, array( 'title-' . $t, 'metadesc-' . $t ) );
				}
			}
			foreach ( get_post_types( array( 'has_archive' => true, '_builtin' => false ) ) as $t ) {
				if ( pll_is_translated_post_type( $t ) ) {
					$options = $this->_wpseo_translate_titles( $options, array( 'title-ptarchive-' . $t, 'metadesc-ptarchive-' . $t, 'bctitle-ptarchive-' . $t ) );
				}
			}
			foreach ( get_taxonomies( array( 'public' => true, '_builtin' => false ) ) as $t ) {
				if ( pll_is_translated_taxonomy( $t ) ) {
					$options = $this->_wpseo_translate_titles( $options, array( 'title-tax-' . $t, 'metadesc-tax-' . $t ) );
				}
			}
		}
		return $options;
	}

	/**
	 * Fixes the home url as well as the stylesheet url
	 * Only when using multiple domains or subdomains
	 *
	 * @since 1.6.4
	 *
	 * @param string $url
	 * @param string $path
	 * @return $url
	 */
	public function wpseo_home_url( $url, $path ) {
		$uri = empty( $path ) ? ltrim( $_SERVER['REQUEST_URI'], '/' ) : $path;

		if ( 'sitemap_index.xml' === $uri || preg_match( '#([^/]+?)-sitemap([0-9]+)?\.xml|([a-z]+)?-?sitemap\.xsl#', $uri ) ) {
			$url = PLL()->links_model->switch_language_in_link( $url, PLL()->curlang );
		}

		return $url;
	}

	/**
	 * Get active languages for the sitemaps
	 *
	 * @since 2.0
	 *
	 * @return array list of active language slugs, empty if all languages are active
	 */
	protected function wpseo_get_active_languages() {
		$languages = PLL()->model->get_languages_list();
		if ( wp_list_filter( $languages, array( 'active' => false ) ) ) {
			return wp_list_pluck( wp_list_filter( $languages, array( 'active' => false ), 'NOT' ), 'slug' );
		}
		return array();
	}

	/**
	 * Modifies the sql request for posts sitemaps
	 * Only when using multiple domains or subdomains or if some languages are not active
	 *
	 * @since 1.6.4
	 *
	 * @param string $sql       JOIN clause
	 * @param string $post_type
	 * @return string
	 */
	public function wpseo_posts_join( $sql, $post_type ) {
		return pll_is_translated_post_type( $post_type ) && ( PLL()->options['force_lang'] > 1 || $this->wpseo_get_active_languages() ) ? $sql . PLL()->model->post->join_clause() : $sql;
	}

	/**
	 * Modifies the sql request for posts sitemaps
	 * Only when using multiple domains or subdomains or if some languages are not active
	 *
	 * @since 1.6.4
	 *
	 * @param string $sql       WHERE clause
	 * @param string $post_type
	 * @return string
	 */
	public function wpseo_posts_where( $sql, $post_type ) {
		if ( pll_is_translated_post_type( $post_type ) ) {
			if ( PLL()->options['force_lang'] > 1 ) {
				return $sql . PLL()->model->post->where_clause( PLL()->curlang );
			}

			if ( $languages = $this->wpseo_get_active_languages() ) {
				return $sql . PLL()->model->post->where_clause( $languages );
			}
		}
		return $sql;
	}

	/**
	 * Removes the language filter (and remove inactive languages) for the taxonomy sitemaps
	 * Only when the language is set from the content or directory name
	 *
	 * @since 1.0.3
	 *
	 * @param array $args get_terms arguments
	 * @return array modified list of arguments
	 */
	public function wpseo_remove_terms_filter( $args ) {
		if ( isset( $GLOBALS['wp_query']->query['sitemap'] ) ) {
			$args['lang'] = implode( ',', $this->wpseo_get_active_languages() );
		}
		return $args;
	}

	/**
	 * Adds the home urls for all (active) languages to the sitemap
	 *
	 * @since 1.9
	 *
	 * @param string $str additional urls to sitemap post
	 * @return string
	 */
	public function add_language_home_urls( $str ) {
		global $wpseo_sitemaps;
		$renderer = version_compare( WPSEO_VERSION, '3.2', '<' ) ? $wpseo_sitemaps : $wpseo_sitemaps->renderer;

		$languages = wp_list_pluck( wp_list_filter( PLL()->model->get_languages_list(), array( 'active' => false ), 'NOT' ), 'slug' );

		foreach ( $languages as $lang ) {
			if ( empty( PLL()->options['hide_default'] ) || pll_default_language() !== $lang ) {
				$str .= $renderer->sitemap_url( array(
					'loc' => pll_home_url( $lang ),
					'pri' => 1,
					'chf' => apply_filters( 'wpseo_sitemap_homepage_change_freq', 'daily', pll_home_url( $lang ) ),
				) );
			}
		}
		return $str;
	}

	/**
	 * Filters home url
	 *
	 * @since 1.1.2
	 *
	 * @param array $arr
	 * @return array
	 */
	public function wpseo_home_url_white_list( $arr ) {
		return array_merge( $arr, array( array( 'file' => 'wordpress-seo' ) ) );
	}

	/**
	 * Adds opengraph support for translations
	 *
	 * @since 1.6
	 */
	public function wpseo_ogp() {
		global $wpseo_og;

		// WPSEO already deals with the locale
		if ( did_action( 'pll_init' ) && method_exists( $wpseo_og, 'og_tag' ) ) {
			foreach ( PLL()->model->get_languages_list() as $language ) {
				if ( PLL()->curlang->slug !== $language->slug && PLL()->links->get_translation_url( $language ) && isset( $language->facebook ) ) {
					$wpseo_og->og_tag( 'og:locale:alternate', $language->facebook );
				}
			}
		}
	}

	/**
	 * Fixes the canonical front page url as unlike WP, WPSEO does not add a trailing slash to the canonical front page url
	 *
	 * @since 1.7.10
	 *
	 * @param string $url
	 * @return $url
	 */
	public function wpseo_canonical( $url ) {
		return is_front_page( $url ) && get_option( 'permalink_structure' ) ? trailingslashit( $url ) : $url;
	}
}
