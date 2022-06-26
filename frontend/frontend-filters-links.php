<?php
/**
 * @package Polylang
 */

/**
 * Manages links filters on frontend
 *
 * @since 1.8
 */
class PLL_Frontend_Filters_Links extends PLL_Filters_Links {

	/**
	 * @var PLL_Frontend_Links|null
	 */
	public $links;

	/**
	 * Our internal non persistent cache object
	 *
	 * @var PLL_Cache
	 */
	public $cache;

	/**
	 * Stores a list of files and functions that home_url() must not filter.
	 *
	 * @var array
	 */
	private $black_list = array();

	/**
	 * Stores a list of files and functions that home_url() must filter.
	 *
	 * @var array
	 */
	private $white_list = array();

	/**
	 * Constructor
	 * Adds filters once the language is defined
	 * Low priority on links filters to come after any other modification
	 *
	 * @since 1.8
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		parent::__construct( $polylang );

		$this->curlang = &$polylang->curlang;
		$this->cache = new PLL_Cache();

		// Rewrites author and date links to filter them by language
		foreach ( array( 'feed_link', 'author_link', 'search_link', 'year_link', 'month_link', 'day_link' ) as $filter ) {
			add_filter( $filter, array( $this, 'archive_link' ), 20 );
		}

		// Meta in the html head section
		add_action( 'wp_head', array( $this, 'wp_head' ), 1 );

		// Modifies the home url
		if ( ! defined( 'PLL_FILTER_HOME_URL' ) || PLL_FILTER_HOME_URL ) {
			add_filter( 'home_url', array( $this, 'home_url' ), 10, 2 );
		}

		if ( $this->options['force_lang'] > 1 ) {
			// Rewrites next and previous post links when not automatically done by WordPress
			add_filter( 'get_pagenum_link', array( $this, 'archive_link' ), 20 );

			add_filter( 'get_shortlink', array( $this, 'shortlink' ), 20, 2 );

			// Rewrites ajax url
			add_filter( 'admin_url', array( $this, 'admin_url' ), 10, 2 );
		}

		// Redirects to canonical url before WordPress redirect_canonical
		// but after Nextgen Gallery which hacks $_SERVER['REQUEST_URI'] !!! and restores it in 'template_redirect' with priority 1
		add_action( 'template_redirect', array( $this, 'check_canonical_url' ), 4 );
	}

	/**
	 * Modifies the author and date links to add the language parameter ( as well as feed link )
	 *
	 * @since 0.4
	 *
	 * @param string $link
	 * @return string modified link
	 */
	public function archive_link( $link ) {
		return $this->links_model->switch_language_in_link( $link, $this->curlang );
	}

	/**
	 * Modifies page links
	 * and caches the result
	 *
	 * @since 1.7
	 *
	 * @param string $link    post link
	 * @param int    $post_id post ID
	 * @return string modified post link
	 */
	public function _get_page_link( $link, $post_id ) {
		$cache_key = "post:{$post_id}:{$link}";
		if ( false === $_link = $this->cache->get( $cache_key ) ) {
			$_link = parent::_get_page_link( $link, $post_id );
			$this->cache->set( $cache_key, $_link );
		}
		return $_link;
	}

	/**
	 * Modifies attachment links
	 * and caches the result
	 *
	 * @since 1.6.2
	 *
	 * @param string $link    attachment link
	 * @param int    $post_id attachment link
	 * @return string modified attachment link
	 */
	public function attachment_link( $link, $post_id ) {
		$cache_key = "post:{$post_id}:{$link}";
		if ( false === $_link = $this->cache->get( $cache_key ) ) {
			$_link = parent::attachment_link( $link, $post_id );
			$this->cache->set( $cache_key, $_link );
		}
		return $_link;
	}

	/**
	 * Modifies custom posts links
	 * and caches the result.
	 *
	 * @since 1.6
	 *
	 * @param string  $link Post link.
	 * @param WP_Post $post Post object.
	 * @return string Modified post link.
	 */
	public function post_type_link( $link, $post ) {
		$cache_key = "post:{$post->ID}:{$link}";
		if ( false === $_link = $this->cache->get( $cache_key ) ) {
			$_link = parent::post_type_link( $link, $post );
			$this->cache->set( $cache_key, $_link );
		}
		return $_link;
	}

	/**
	 * Modifies filtered taxonomies ( post format like ) and translated taxonomies links
	 * and caches the result.
	 *
	 * @since 0.7
	 *
	 * @param string  $link Term link.
	 * @param WP_Term $term Term object.
	 * @param string  $tax  Taxonomy name.
	 * @return string Modified link.
	 */
	public function term_link( $link, $term, $tax ) {
		$cache_key = "term:{$term->term_id}:{$link}";
		if ( false === $_link = $this->cache->get( $cache_key ) ) {
			if ( in_array( $tax, $this->model->get_filtered_taxonomies() ) ) {
				$_link = $this->links_model->switch_language_in_link( $link, $this->curlang );

				/** This filter is documented in include/filters-links.php */
				$_link = apply_filters( 'pll_term_link', $_link, $this->curlang, $term );
			}

			else {
				$_link = parent::term_link( $link, $term, $tax );
			}
			$this->cache->set( $cache_key, $_link );
		}
		return $_link;
	}

	/**
	 * Modifies the post short link when using one domain or subdomain per language.
	 *
	 * @since 2.6.9
	 *
	 * @param string $link    Post permalink.
	 * @param int    $post_id Post id.
	 * @return string Post permalink with the correct domain.
	 */
	public function shortlink( $link, $post_id ) {
		$post_type = get_post_type( $post_id );
		return $this->model->is_translated_post_type( $post_type ) ? $this->links_model->switch_language_in_link( $link, $this->model->post->get_language( $post_id ) ) : $link;
	}

	/**
	 * Outputs references to translated pages ( if exists ) in the html head section
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function wp_head() {
		// Don't output anything on paged archives: see https://wordpress.org/support/topic/hreflang-on-page2
		// Don't output anything on paged pages and paged posts
		if ( is_paged() || ( is_singular() && ( $page = get_query_var( 'page' ) ) && $page > 1 ) ) {
			return;
		}

		$urls = array();

		// Google recommends to include self link https://support.google.com/webmasters/answer/189077?hl=en
		foreach ( $this->model->get_languages_list() as $language ) {
			if ( $url = $this->links->get_translation_url( $language ) ) {
				$urls[ $language->get_locale( 'display' ) ] = $url;
			}
		}

		// Outputs the section only if there are translations ( $urls always contains self link )
		if ( ! empty( $urls ) && count( $urls ) > 1 ) {
			$languages = array();
			$hreflangs = array();

			// Prepare the list of languages to remove the country code
			foreach ( array_keys( $urls ) as $locale ) {
				$split = explode( '-', $locale );
				$languages[ $locale ] = reset( $split );
			}

			$count = array_count_values( $languages );

			foreach ( $urls as $locale => $url ) {
				$lang = $count[ $languages[ $locale ] ] > 1 ? $locale : $languages[ $locale ]; // Output the country code only when necessary
				$hreflangs[ $lang ] = $url;
			}

			// Adds the site root url when the default language code is not hidden
			// See https://wordpress.org/support/topic/implementation-of-hreflangx-default
			if ( is_front_page() && ! $this->options['hide_default'] && $this->options['force_lang'] < 3 ) {
				$hreflangs['x-default'] = home_url( '/' );
			}

			/**
			 * Filters the list of rel hreflang attributes
			 *
			 * @since 2.1
			 *
			 * @param array $hreflangs Array of urls with language codes as keys
			 */
			$hreflangs = apply_filters( 'pll_rel_hreflang_attributes', $hreflangs );

			foreach ( $hreflangs as $lang => $url ) {
				printf( '<link rel="alternate" href="%s" hreflang="%s" />' . "\n", esc_url( $url ), esc_attr( $lang ) );
			}
		}
	}

	/**
	 * Filters the home url to get the right language
	 *
	 * @since 0.4
	 *
	 * @param string $url
	 * @param string $path
	 * @return string
	 */
	public function home_url( $url, $path ) {
		if ( ! ( did_action( 'template_redirect' ) || did_action( 'login_init' ) ) || rtrim( $url, '/' ) != $this->links_model->home ) {
			return $url;
		}

		// We *want* to filter the home url in these cases
		if ( empty( $this->white_list ) ) {
			// On Windows get_theme_root() mixes / and \
			// We want only \ for the comparison with debug_backtrace
			$theme_root = get_theme_root();
			$theme_root = ( false === strpos( $theme_root, '\\' ) ) ? $theme_root : str_replace( '/', '\\', $theme_root );

			/**
			 * Filter the white list of the Polylang 'home_url' filter
			 * The $args contains an array of arrays each of them having
			 * a 'file' key and/or a 'function' key to decide which functions in
			 * which files using home_url() calls must be filtered
			 *
			 * @since 1.1.2
			 *
			 * @param array $args
			 */
			$this->white_list = apply_filters(
				'pll_home_url_white_list',
				array(
					array( 'file' => $theme_root ),
					array( 'function' => 'wp_nav_menu' ),
					array( 'function' => 'login_footer' ),
					array( 'function' => 'get_custom_logo' ),
					array( 'function' => 'render_block_core_site_title' ),
					array( 'function' => 'redirect_canonical' ),
				)
			);
		}

		// We don't want to filter the home url in these cases
		if ( empty( $this->black_list ) ) {

			/**
			 * Filter the black list of the Polylang 'home_url' filter
			 * The $args contains an array of arrays each of them having
			 * a 'file' key and/or a 'function' key to decide which functions in
			 * which files using home_url() calls must be filtered
			 *
			 * @since 1.1.2
			 *
			 * @param array $args
			 */
			$this->black_list = apply_filters(
				'pll_home_url_black_list',
				array(
					array( 'file' => 'searchform.php' ), // Since WP 3.6 searchform.php is passed through get_search_form
					array( 'function' => 'get_search_form' ),
				)
			);
		}

		$traces = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		unset( $traces[0], $traces[1] ); // We don't need the last 2 calls: this function + call_user_func_array (or apply_filters on PHP7+)

		foreach ( $traces as $trace ) {
			// Black list first
			foreach ( $this->black_list as $v ) {
				if ( ( isset( $trace['file'], $v['file'] ) && false !== strpos( $trace['file'], $v['file'] ) ) || ( ! empty( $v['function'] ) && $trace['function'] === $v['function'] ) ) {
					return $url;
				}
			}

			foreach ( $this->white_list as $v ) {
				if ( ( ! empty( $v['function'] ) && $trace['function'] === $v['function'] ) ||
					( isset( $trace['file'], $v['file'] ) && false !== strpos( $trace['file'], $v['file'] ) && in_array( $trace['function'], array( 'home_url', 'get_home_url', 'bloginfo', 'get_bloginfo' ) ) ) ) {
					$ok = true;
				}
			}
		}

		return empty( $ok ) ? $url : ( empty( $path ) ? rtrim( $this->links->get_home_url( $this->curlang ), '/' ) : $this->links->get_home_url( $this->curlang ) );
	}

	/**
	 * Rewrites ajax url when using domains or subdomains
	 *
	 * @since 1.5
	 *
	 * @param string $url  admin url with path evaluated by WordPress
	 * @param string $path admin path
	 * @return string
	 */
	public function admin_url( $url, $path ) {
		return 'admin-ajax.php' === $path ? $this->links_model->switch_language_in_link( $url, $this->curlang ) : $url;
	}

	/**
	 * If the language code is not in agreement with the language of the content,
	 * redirects incoming links to the proper URL to avoid duplicate content.
	 *
	 * @since 0.9.6
	 *
	 * @param string $requested_url Optional, defaults to requested url.
	 * @param bool   $do_redirect   Optional, whether to perform the redirect or not.
	 * @return string|void Returns if redirect is not performed.
	 */
	public function check_canonical_url( $requested_url = '', $do_redirect = true ) {
		global $wp_query;

		// Don't redirect in same cases as WP.
		if ( is_trackback() || is_search() || is_admin() || is_preview() || is_robots() || ( $GLOBALS['is_IIS'] && ! iis7_supports_permalinks() ) ) {
			return;
		}

		// Don't redirect mysite.com/?attachment_id= to mysite.com/en/?attachment_id=.
		if ( 1 == $this->options['force_lang'] && is_attachment() && isset( $_GET['attachment_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		/*
		 * If the default language code is not hidden and the static front page url contains the page name,
		 * the customizer lands here and the code below would redirect to the list of posts.
		 */
		if ( is_customize_preview() ) {
			return;
		}

		if ( empty( $requested_url ) ) {
			$requested_url = pll_get_requested_url();
		}

		if ( ( is_single() || is_page() ) && ! is_front_page() ) {
			$post = get_post();
			if ( $post instanceof WP_Post && $this->model->is_translated_post_type( $post->post_type ) ) {
				$language = $this->model->post->get_language( (int) $post->ID );
			}
		}

		if ( ! empty( $wp_query->tax_query ) ) {
			if ( $this->model->is_translated_taxonomy( $this->get_queried_taxonomy( $wp_query->tax_query ) ) ) {
				$term_id = $this->get_queried_term_id( $wp_query->tax_query );
				if ( $term_id ) {
					$language = $this->model->term->get_language( $term_id );
				}
			}
		}

		if ( $wp_query->is_posts_page ) {
			$page_id = get_query_var( 'page_id' );
			if ( ! $page_id ) {
				$page_id = get_queried_object_id();
			}
			if ( $page_id ) {
				$language = $this->model->post->get_language( (int) $page_id );
			}
		}

		if ( 3 === $this->options['force_lang'] ) {
			$requested_host = wp_parse_url( $requested_url, PHP_URL_HOST );
			foreach ( $this->options['domains'] as $lang => $domain ) {
				$host = wp_parse_url( $domain, PHP_URL_HOST );
				if ( $requested_host && $host && ltrim( $requested_host, 'w.' ) === ltrim( $host, 'w.' ) ) {
					$language = $this->model->get_language( $lang );
				}
			}
		}

		if ( empty( $language ) ) {
			$language = $this->curlang;
			$redirect_url = $requested_url;
		} else {
			$redirect_url = $this->redirect_canonical( $requested_url, $language );
			$redirect_url = $this->options['force_lang'] ?
				$this->links_model->switch_language_in_link( $redirect_url, $language ) :
				$this->links_model->remove_language_from_link( $redirect_url ); // Works only for default permalinks
		}


		/**
		 * Filters the canonical url detected by Polylang.
		 *
		 * @since 1.6
		 *
		 * @param string|false $redirect_url False or the url to redirect to.
		 * @param PLL_Language $language The language detected.
		 */
		$redirect_url = apply_filters( 'pll_check_canonical_url', $redirect_url, $language );

		if ( ! $redirect_url || $requested_url === $redirect_url ) {
			return $requested_url;
		}

		if ( ! $do_redirect ) {
			return $redirect_url;
		}

		// Protect against chained redirects.
		if ( $redirect_url === $this->check_canonical_url( $redirect_url, false ) && wp_validate_redirect( $redirect_url ) ) {
			wp_safe_redirect( $redirect_url, 301, POLYLANG );
			exit;
		}
	}

	/**
	 * Returns the term_id of the requested term.
	 *
	 * @since 2.9
	 *
	 * @param WP_Tax_Query $tax_query An instance of WP_Tax_Query.
	 * @return int|false
	 */
	protected function get_queried_term_id( $tax_query ) {
		$queried_terms = $tax_query->queried_terms;
		$taxonomy = $this->get_queried_taxonomy( $tax_query );

		if ( ! is_array( $queried_terms[ $taxonomy ]['terms'] ) ) {
			return false;
		}
		$field = $queried_terms[ $taxonomy ]['field'];
		$term  = reset( $queried_terms[ $taxonomy ]['terms'] );
		$lang  = isset( $queried_terms['language']['terms'] ) ? reset( $queried_terms['language']['terms'] ) : '';

		// We can get a term_id when requesting a plain permalink, eg /?cat=1.
		if ( 'term_id' === $field ) {
			return $term;
		}

		// We get a slug when requesting a pretty permalink. Let's query all corresponding terms.
		$args = array(
			'lang' => '',
			'taxonomy' => $taxonomy,
			$field => $term,
			'hide_empty' => false,
			'fields' => 'ids',
		);
		$terms = get_terms( $args );

		$filtered_terms_by_lang = array_filter(
			$terms,
			function ( $term ) use ( $lang ) {
				$term_lang = $this->model->term->get_language( $term );

				return ! empty( $term_lang ) && $term_lang->slug === $lang;
			}
		);

		$tr_term = reset( $filtered_terms_by_lang );

		if ( ! empty( $tr_term ) ) {
			// The queried term exists in the desired language.
			return $tr_term;
		}

		// The queried term doesn't exist in the desired language, let's return the first one retrieved.
		return reset( $terms );
	}

	/**
	 * Find the taxonomy being queried.
	 *
	 * @since 2.9
	 *
	 * @param WP_Tax_Query $tax_query An instance of WP_Tax_Query.
	 * @return string A taxonomy slug
	 */
	protected function get_queried_taxonomy( $tax_query ) {
		$queried_terms = $tax_query->queried_terms;
		unset( $queried_terms['language'] );

		return key( $queried_terms );
	}

	/**
	 * Evaluates the canonical redirect url through the deidcated WP function.
	 *
	 * @since 3.3
	 *
	 * @param string       $url      Requested url.
	 * @param PLL_Language $language Language of the queried object.
	 * @return string
	 */
	protected function redirect_canonical( $url, $language ) {
		global $wp_query;

		$this->curlang = $language; // Hack to filter the `page_for_posts` option in the correct language.

		$backup_wp_query = $wp_query;

		if ( isset( $wp_query->tax_query ) ) {
			unset( $wp_query->tax_query->queried_terms['language'] );
			unset( $wp_query->query['lang'] );
		}

		$redirect_url = redirect_canonical( $url, false );

		$wp_query = $backup_wp_query;

		return $redirect_url ? $redirect_url : $url;
	}
}
