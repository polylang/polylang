<?php
/**
 * @package Polylang
 */

/**
 * Manages the compatibility with Yoast SEO
 * Version tested: 15.9.2
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
		add_action( 'wp_loaded', array( $this, 'wpseo_translate_options' ) );
		add_filter( 'wpseo_dynamic_permalinks_enabled', '__return_true', 999 );

		if ( PLL() instanceof PLL_Frontend ) {
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
				add_action( 'setup_theme', array( $this, 'maybe_deactivate_sitemap' ) ); // Deactivate sitemaps for inactive languages.
			} else {
				// Get all terms in all languages when the language is set from the content or directory name
				add_filter( 'get_terms_args', array( $this, 'wpseo_remove_terms_filter' ) );
				add_action( 'pre_get_posts', array( $this, 'before_sitemap' ), 0 ); // Needs to be fired before WPSEO_Sitemaps::redirect()
			}

			add_filter( 'pll_home_url_white_list', array( $this, 'wpseo_home_url_white_list' ) );
			add_filter( 'wpseo_frontend_presenters', array( $this, 'wpseo_frontend_presenters' ) );
			add_filter( 'wpseo_canonical', array( $this, 'wpseo_canonical' ) );
			add_filter( 'wpseo_frontend_presentation', array( $this, 'frontend_presentation' ) );
			add_filter( 'wpseo_breadcrumb_indexables', array( $this, 'breadcrumb_indexables' ) );
		} else {
			add_filter( 'pll_copy_post_metas', array( $this, 'copy_post_metas' ), 10, 4 );
			add_filter( 'pll_translate_post_meta', array( $this, 'translate_post_meta' ), 10, 3 );

			// Yoast SEO adds the columns hooks only for the 'inline-save' action. We need them for 'pll_update_post_rows' too.
			if ( wp_doing_ajax() && isset( $_POST['action'] ) && 'pll_update_post_rows' === $_POST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$GLOBALS['wpseo_meta_columns'] = new WPSEO_Meta_Columns();
			}
		}
	}

	/**
	 * Registers options for translation.
	 *
	 * @since 2.9
	 */
	public function wpseo_translate_options() {
		if ( method_exists( 'WPSEO_Options', 'clear_cache' ) ) {
			WPSEO_Options::clear_cache();
		}

		$keys = array(
			'title-*',
			'metadesc-*',
			'bctitle-*',
			'breadcrumbs-sep',
			'breadcrumbs-home',
			'breadcrumbs-prefix',
			'breadcrumbs-archiveprefix',
			'breadcrumbs-searchprefix',
			'breadcrumbs-404crumb',
			'company_name',
			'rssbefore',
			'rssafter',
		);

		new PLL_Translate_Option( 'wpseo_titles', array_fill_keys( $keys, 1 ), array( 'context' => 'wordpress-seo' ) );

		$keys = array(
			'og_frontpage_title',
			'og_frontpage_desc',
		);

		new PLL_Translate_Option( 'wpseo_social', array_fill_keys( $keys, 1 ), array( 'context' => 'wordpress-seo' ) );
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
		if ( empty( $path ) ) {
			$path = ltrim( (string) wp_parse_url( pll_get_requested_url(), PHP_URL_PATH ), '/' );
		}

		if ( preg_match( '#sitemap(_index)?\.xml|([^\/]+?)-?sitemap([0-9]+)?\.xml|([a-z]+)?-?sitemap\.xsl#', $path ) ) {
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
	 * Deactivates the sitemap for inactive languages when using subdomains or multiple domains
	 *
	 * @since 2.6.1
	 */
	public function maybe_deactivate_sitemap() {
		global $wpseo_sitemaps;

		if ( isset( $wpseo_sitemaps ) ) {
			$active_languages = $this->wpseo_get_active_languages();
			if ( ! empty( $active_languages ) && ! in_array( pll_current_language(), $active_languages ) ) {
				remove_action( 'pre_get_posts', array( $wpseo_sitemaps, 'redirect' ), 1 );
			}
		}
	}

	/**
	 * Add filters before the sitemap is evaluated and outputed.
	 *
	 * @since 2.6
	 *
	 * @param WP_Query $query Instance of WP_Query being filtered.
	 */
	public function before_sitemap( $query ) {
		$type = $query->get( 'sitemap' );

		// Add the post post type archives in all languages to the sitemap
		// Add the homepages for all languages to the sitemap when the front page displays posts
		if ( $type && pll_is_translated_post_type( $type ) && ( 'post' !== $type || ! get_option( 'page_on_front' ) ) ) {
			add_filter( "wpseo_sitemap_{$type}_content", array( $this, 'add_post_type_archive' ) );
		}
	}

	/**
	 * Generates a post type archive sitemap url
	 *
	 * @since 2.6.1
	 *
	 * @param string $link      The url.
	 * @param string $post_type The post type name.
	 * @return string Formatted sitemap url.
	 */
	protected function format_sitemap_url( $link, $post_type ) {
		global $wpseo_sitemaps;

		return $wpseo_sitemaps->renderer->sitemap_url(
			array(
				'loc' => $link,
				'mod' => WPSEO_Sitemaps::get_last_modified_gmt( $post_type ),
				'pri' => 1,
				'chf' => 'daily',
			)
		);
	}

	/**
	 * Adds the home and post type archives urls for all (active) languages to the sitemap
	 *
	 * @since 2.6
	 *
	 * @param string $str additional urls to sitemap post
	 * @return string
	 */
	public function add_post_type_archive( $str ) {
		$post_type     = substr( substr( current_filter(), 14 ), 0, -8 );
		$post_type_obj = get_post_type_object( $post_type );
		$languages     = wp_list_filter( PLL()->model->get_languages_list(), array( 'active' => false ), 'NOT' );

		if ( 'post' === $post_type ) {
			if ( ! empty( PLL()->options['hide_default'] ) ) {
				// The home url is of course already added by WPSEO.
				$languages = wp_list_filter( $languages, array( 'slug' => pll_default_language() ), 'NOT' );
			}

			foreach ( $languages as $lang ) {
				$str .= $this->format_sitemap_url( pll_home_url( $lang->slug ), $post_type );
			}
		} elseif ( $post_type_obj->has_archive ) {
			// Exclude cases where a post type archive is attached to a page (ex: WooCommerce).
			$slug = ( true === $post_type_obj->has_archive ) ? $post_type_obj->rewrite['slug'] : $post_type_obj->has_archive;

			if ( ! wpcom_vip_get_page_by_path( $slug ) ) {
				// The post type archive in the current language is already added by WPSEO.
				$languages = wp_list_filter( $languages, array( 'slug' => pll_current_language() ), 'NOT' );

				foreach ( $languages as $lang ) {
					PLL()->curlang = $lang; // Switch the language to get the correct archive link.
					$link = get_post_type_archive_link( $post_type );
					$str .= $this->format_sitemap_url( $link, $post_type );
				}
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
	 * Get alternate language codes for Opengraph.
	 *
	 * @since 2.7.3
	 *
	 * @return string[]
	 */
	protected function get_ogp_alternate_languages() {
		$alternates = array();

		foreach ( PLL()->model->get_languages_list() as $language ) {
			if ( isset( PLL()->curlang ) && PLL()->curlang->slug !== $language->slug && PLL()->links->get_translation_url( $language ) && isset( $language->facebook ) ) {
				$alternates[] = $language->facebook;
			}
		}

		// There is a risk that 2 languages have the same Facebook locale. So let's make sure to output each locale only once.
		return array_unique( $alternates );
	}

	/**
	 * Adds opengraph support for translations
	 *
	 * @since 2.7.3
	 *
	 * @param array $presenters An array of objects implementing Abstract_Indexable_Presenter
	 * @return array
	 */
	public function wpseo_frontend_presenters( $presenters ) {
		$_presenters = array();

		foreach ( $presenters as $presenter ) {
			$_presenters[] = $presenter;
			if ( $presenter instanceof Yoast\WP\SEO\Presenters\Open_Graph\Locale_Presenter ) {
				foreach ( $this->get_ogp_alternate_languages() as $lang ) {
					$_presenters[] = new PLL_WPSEO_OGP( $lang );
				}
			}
		}
		return $_presenters;
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

	/**
	 * Fixes the links and strings stored in the indexable table since Yoast SEO 14.0
	 *
	 * @since 2.8.2
	 *
	 * @param object $presentation The indexable presentation.
	 * @return object
	 */
	public function frontend_presentation( $presentation ) {
		switch ( $presentation->model->object_type ) {
			case 'home-page':
				$presentation->model->title = WPSEO_Options::get( 'title-home-wpseo' );
				$presentation->model->description = WPSEO_Options::get( 'metadesc-home-wpseo' );
				$presentation->model->open_graph_title = WPSEO_Options::get( 'og_frontpage_title' );
				$presentation->model->open_graph_description = WPSEO_Options::get( 'og_frontpage_desc' );
				break;

			case 'post-type-archive':
				if ( pll_is_translated_post_type( $presentation->model->object_sub_type ) ) {
					$presentation->model->title = WPSEO_Options::get( 'title-ptarchive-' . $presentation->model->object_sub_type );
					$presentation->model->description = WPSEO_Options::get( 'metadesc-ptarchive-' . $presentation->model->object_sub_type );
				}
				break;

			case 'system-page':
				switch ( $presentation->model->object_sub_type ) {
					case '404':
						$presentation->model->title = WPSEO_Options::get( 'title-404-wpseo' );
						break;
					case 'search-result':
						$presentation->model->title = WPSEO_Options::get( 'title-search-wpseo' );
						break;
				}
				break;
		}

		return $presentation;
	}

	/**
	 * Fixes the breadcrumb links and strings stored in the indexable table since Yoast SEO 14.0.
	 *
	 * In version 17.0, the breadcrumb links do not honor the filter `wpseo_dynamic_permalinks_enabled`.
	 *
	 * @since 2.8.3
	 *
	 * @param array $indexables An array of Indexable objects.
	 * @return array
	 */
	public function breadcrumb_indexables( $indexables ) {
		foreach ( $indexables as &$indexable ) {
			if ( 'home-page' === $indexable->object_type || ( 'post' === $indexable->object_type && 'page' === $indexable->object_sub_type && get_option( 'page_on_front' ) === $indexable->object_id ) ) {
				// Handles both when the front page displays the list of posts or a static page.
				$indexable->permalink = pll_home_url();
				$indexable->breadcrumb_title = pll__( WPSEO_Options::get( 'breadcrumbs-home' ) );
			} elseif ( 'post' === $indexable->object_type && 'page' === $indexable->object_sub_type && get_option( 'page_for_posts' ) === $indexable->object_id ) {
				$indexable->permalink = get_permalink( $indexable->object_id );
			} elseif ( 'post-type-archive' === $indexable->object_type && pll_is_translated_post_type( $indexable->object_sub_type ) ) {
				$indexable->permalink = get_post_type_archive_link( $indexable->object_sub_type );
				$breadcrumb_title = WPSEO_Options::get( 'bctitle-ptarchive-' . $indexable->object_sub_type );
				$breadcrumb_title = $breadcrumb_title ? $breadcrumb_title : $indexable->breadcrumb_title; // The option may be empty.
				$indexable->breadcrumb_title = pll__( $breadcrumb_title );
			} elseif ( 'term' === $indexable->object_type && pll_is_translated_taxonomy( $indexable->object_sub_type ) ) {
				$indexable->permalink = get_term_link( $indexable->object_id );
			}
		}

		return $indexables;
	}

	/**
	 * Copies or synchronizes the metas.
	 *
	 * @since 2.3.3
	 *
	 * @param string[] $keys List of custom fields names.
	 * @param bool     $sync True if it is synchronization, false if it is a copy.
	 * @param int      $from Id of the post from which we copy informations.
	 * @param int      $to   Id of the post to which we paste informations.
	 * @return array
	 */
	public function copy_post_metas( $keys, $sync, $from, $to ) {
		if ( ! $sync ) {
			// Text requiring translation.
			$keys[] = '_yoast_wpseo_title';
			$keys[] = '_yoast_wpseo_metadesc';
			$keys[] = '_yoast_wpseo_bctitle';
			$keys[] = '_yoast_wpseo_focuskw';
			$keys[] = '_yoast_wpseo_opengraph-title';
			$keys[] = '_yoast_wpseo_opengraph-description';
			$keys[] = '_yoast_wpseo_twitter-title';
			$keys[] = '_yoast_wpseo_twitter-description';

			// Copy the image urls.
			$keys[] = '_yoast_wpseo_opengraph-image';
			$keys[] = '_yoast_wpseo_twitter-image';
		}

		$keys[] = '_yoast_wpseo_meta-robots-noindex';
		$keys[] = '_yoast_wpseo_meta-robots-nofollow';
		$keys[] = '_yoast_wpseo_meta-robots-adv';

		$taxonomies = get_taxonomies(
			array(
				'hierarchical' => true,
				'public'       => true,
			)
		);

		$sync_taxonomies = PLL()->sync->taxonomies->get_taxonomies_to_copy( $sync, $from, $to );

		$taxonomies = array_intersect( $taxonomies, $sync_taxonomies );

		foreach ( $taxonomies as $taxonomy ) {
			$keys[] = '_yoast_wpseo_primary_' . $taxonomy;
		}

		return $keys;
	}

	/**
	 * Translate the primary term during the synchronization process
	 *
	 * @since 2.3.3
	 *
	 * @param int    $value Meta value.
	 * @param string $key   Meta key.
	 * @param string $lang  Language of target.
	 * @return int
	 */
	public function translate_post_meta( $value, $key, $lang ) {
		if ( 0 !== strpos( $key, '_yoast_wpseo_primary_' ) ) {
			return $value;
		}

		$taxonomy = str_replace( '_yoast_wpseo_primary_', '', $key );
		if ( ! PLL()->model->is_translated_taxonomy( $taxonomy ) ) {
			return $value;
		}

		return pll_get_term( $value, $lang );
	}
}
