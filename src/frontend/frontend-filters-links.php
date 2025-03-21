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
	 * @var PLL_Cache<string>
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
	 * @param object $polylang The Polylang object.
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
		if ( pll_get_constant( 'PLL_FILTER_HOME_URL', true ) ) {
			add_filter( 'home_url', array( $this, 'home_url' ), 10, 2 );
		}

		if ( $this->options['force_lang'] > 1 ) {
			// Rewrites next and previous post links when not automatically done by WordPress
			add_filter( 'get_pagenum_link', array( $this, 'archive_link' ), 20 );

			add_filter( 'get_shortlink', array( $this, 'shortlink' ), 20, 2 );

			// Rewrites ajax url
			add_filter( 'admin_url', array( $this, 'admin_url' ), 10, 2 );
		}
	}

	/**
	 * Modifies the author and date links to add the language parameter (as well as feed link).
	 *
	 * @since 0.4
	 *
	 * @param string $link The permalink to the archive.
	 * @return string The modified link.
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
	 * @param string $link    The page link.
	 * @param int    $post_id The post ID.
	 * @return string The modified page link.
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
	 * @param string $link    The attachment link.
	 * @param int    $post_id The attachment post ID.
	 * @return string The modified attachment link.
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
	 * @param string  $link The post link.
	 * @param WP_Post $post The post object.
	 * @return string The modified post link.
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
	 * @param string  $link The term link.
	 * @param WP_Term $term The term object.
	 * @param string  $tax  The taxonomy name.
	 * @return string The modified link.
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
	 * Filters the home url to get the right language.
	 *
	 * @since 0.4
	 *
	 * @param string $url  The home URL including scheme and path.
	 * @param string $path Path relative to the home URL.
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

			$white_list = array(
				array( 'file' => $theme_root ),
				array( 'function' => 'wp_nav_menu' ),
				array( 'function' => 'login_footer' ),
				array( 'function' => 'get_custom_logo' ),
				array( 'function' => 'render_block_core_site_title' ),
			);

			if ( 3 === $this->options['force_lang'] ) {
				$white_list[] = array( 'function' => 'redirect_canonical' );
			}

			/**
			 * Filters the white list of the Polylang 'home_url' filter.
			 *
			 * @since 1.1.2
			 *
			 * @param string[][] $white_list An array of arrays each of them having a 'file' key
			 *                               and/or a 'function' key to decide which functions in
			 *                               which files using home_url() calls must be filtered.
			 */
			$this->white_list = apply_filters( 'pll_home_url_white_list', $white_list );
		}

		// We don't want to filter the home url in these cases.
		if ( empty( $this->black_list ) ) {
			$black_list = array(
				array( 'file' => 'searchform.php' ), // Since WP 3.6 searchform.php is passed through get_search_form.
				array( 'function' => 'get_search_form' ),
			);

			/**
			 * Filters the black list of the Polylang 'home_url' filter.
			 *
			 * @since 1.1.2
			 *
			 * @param string[][] $black_list An array of arrays each of them having a 'file' key
			 *                               and/or a 'function' key to decide which functions in
			 *                               which files using home_url() calls must be filtered.
			 */
			$this->black_list = apply_filters( 'pll_home_url_black_list', $black_list );
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
	 * Rewrites the ajax url when using domains or subdomains.
	 *
	 * @since 1.5
	 *
	 * @param string $url  The admin url with path evaluated by WordPress.
	 * @param string $path Path relative to the admin URL.
	 * @return string
	 */
	public function admin_url( $url, $path ) {
		return 'admin-ajax.php' === $path ? $this->links_model->switch_language_in_link( $url, $this->curlang ) : $url;
	}
}
