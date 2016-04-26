<?php

/**
 * manages the static front page and the page for posts on frontend
 *
 * @since 1.8
 */
class PLL_Frontend_Static_Pages extends PLL_Static_Pages {

	/**
	 * constructor: setups filters and actions
	 *
	 * @since 1.8
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		parent::__construct( $polylang );

		$this->links_model = &$polylang->links_model;
		$this->links = &$polylang->links;
		$this->curlang = &$polylang->curlang;

		add_action( 'pll_language_defined', array( &$this, 'pll_language_defined' ) );
		add_action( 'pll_home_requested', array( &$this, 'pll_home_requested' ) );

		// modifies the page link in case the front page is not in the default language
		add_filter( 'page_link', array( &$this, 'page_link' ), 20, 2 );

		// manages the redirection of the homepage
		add_filter( 'redirect_canonical', array( &$this, 'redirect_canonical' ), 10, 2 );

		add_filter( 'pll_pre_translation_url', array( &$this, 'pll_pre_translation_url' ), 10, 3 );
		add_filter( 'pll_check_canonical_url', array( &$this, 'pll_check_canonical_url' ) );

		add_filter( 'pll_set_language_from_query', array( &$this, 'page_on_front_query' ), 10, 2 );
		add_filter( 'pll_set_language_from_query', array( &$this, 'page_for_posts_query' ), 10, 2 );
	}

	/**
	 * init the filters
	 *
	 * @since 1.8
	 */
	public function pll_language_defined() {
		// translates our page on front and page for posts properties
		$this->init();

		// translates page for posts and page on front
		add_filter( 'option_page_on_front', array( &$this, 'translate_page_on_front' ) );
		add_filter( 'option_page_for_posts', array( &$this, 'translate_page_for_posts' ) );

		// support theme customizer
		if ( isset( $_POST['wp_customize'], $_POST['customized'] ) ) {
			add_filter( 'pre_option_page_on_front', 'pll_get_post', 20 );
			add_filter( 'pre_option_page_for_post', 'pll_get_post', 20 );
		}
	}

	/**
	 * translates the page_id query var when the site root page is requested
	 *
	 * @since 1.8
	 */
	public function pll_home_requested() {
		set_query_var( 'page_id', $this->curlang->page_on_front );
	}

	/**
	 * translates page on front
	 *
	 * @since 1.8
	 *
	 * @param int $v page on front page id
	 * @return int
	 */
	public function translate_page_on_front( $v ) {
		// returns the current page if there is no translation to avoid ugly notices
		return isset( $this->curlang->page_on_front ) ? $this->curlang->page_on_front : $v;
	}

	/**
	 * translates page for posts
	 *
	 * @since 1.8
	 *
	 * @param int $v page for posts page id
	 * @return int
	 */
	public function translate_page_for_posts( $v ) {
		// returns the current page if there is no translation to avoid ugly notices
		return isset( $this->curlang->page_for_posts ) ? $this->curlang->page_for_posts : $v;
	}

	/**
	 * modifies the page link in case the front page is not in the default language
	 *
	 * @since 0.7.2
	 *
	 * @param string $link
	 * @param int $id
	 * @return string modified link
	 */
	public function page_link( $link, $id ) {
		if ( ( $lang = $this->model->post->get_language( $id ) ) && $id == $lang->page_on_front ) {
			return $lang->home_url;
		}
		return $link;
	}

	/**
	 * manages canonical redirection of the homepage when using page on front
	 *
	 * @since 0.1
	 *
	 * @param string $redirect_url
	 * @param string $requested_url
	 * @return bool|string modified url, false if redirection is canceled
	 */
	public function redirect_canonical( $redirect_url, $requested_url ) {
		global $wp_query;
		if ( is_page() && ! is_feed() && isset( $wp_query->queried_object ) && $wp_query->queried_object->ID == $this->curlang->page_on_front ) {
			$url = is_paged() ? $this->links_model->add_paged_to_link( $this->links->get_home_url(), $wp_query->query_vars['page'] ) : $this->links->get_home_url();

			// don't forget additional query vars
			$query = parse_url( $redirect_url, PHP_URL_QUERY );
			if ( ! empty( $query ) ) {
				parse_str( $query, $query_vars );
				$query_vars = rawurlencode_deep( $query_vars ); // WP encodes query vars values
				$url = add_query_arg( $query_vars, $url );
			}

			return $url;
		}

		return $redirect_url;
	}

	/**
	 * translates the url of the page on front and page for posts
	 *
	 * @since 1.8
	 *
	 * @param string $url not used
	 * @param object $language
	 * @param int $queried_object_id
	 * @return string
	 */
	public function pll_pre_translation_url( $url, $language, $queried_object_id ) {
		if ( ! empty( $queried_object_id ) ) {
			// page for posts
			if ( $GLOBALS['wp_query']->is_posts_page && ( $id = $this->model->post->get( $queried_object_id, $language ) ) ) {
				$url = get_permalink( $id );
			}

			// page on front
			elseif ( is_front_page() && $language->page_on_front && ( $language->page_on_front == $this->model->post->get( $queried_object_id, $language ) ) ) {
				$url = $language->home_url;
			}
		}

		return $url;
	}

	/**
	 * prevents canonical redirection if we are on a static front page
	 *
	 * @since 1.8
	 *
	 * @param string $redirect_url
	 * @return bool|string
	 */
	public function pll_check_canonical_url( $redirect_url ) {
		return $this->options['redirect_lang'] && isset( $this->curlang->page_on_front ) && is_page( $this->curlang->page_on_front ) ? false : $redirect_url;
	}

	/**
	 * setups query vars when requesting a static front page
	 *
	 * @since 1.8
	 *
	 * @param bool|object $lang
	 * @param object $query
	 * @return bool|object
	 */
	public function page_on_front_query( $lang, $query ) {
		if ( ! empty( $lang ) || ! $this->page_on_front ) {
			return $lang;
		}

		// the home page is requested
		if ( did_action( 'home_requested' ) ) {
			$query->set( 'page_id', $lang->page_on_front );
		}

		// redirect the language page to the homepage when using a static front page
		elseif ( ( $this->options['redirect_lang'] || $this->options['hide_default'] ) && ( count( $query->query ) == 1 || ( is_paged() && count( $query->query ) == 2 ) ) && is_tax( 'language' ) ) {
			$lang = $this->model->get_language( get_query_var( 'lang' ) );
			$query->set( 'page_id', $lang->page_on_front );
			$query->is_singular = $query->is_page = true;
			$query->is_archive = $query->is_tax = false;
			unset( $query->query_vars['lang'], $query->queried_object ); // reset queried object
		}

		// set the language when requesting a static front page
		else {
			$page_id = $this->get_page_id( $query );
			$languages = $this->model->get_languages_list();
			$pages = wp_list_pluck( $languages, 'page_on_front' );

			if ( ! empty( $page_id ) && false !== $n = array_search( $page_id, $pages ) ) {
				$lang = $languages[ $n ];
			}
		}

		// correct <!--nextpage--> for page_on_front
		if ( ! empty( $lang ) ) {
			$query->set( 'page', $query->query_vars['paged'] );
			unset( $query->query_vars['paged'] );
		}

		return $lang;
	}

	/**
	 * setups query vars when requesting a posts page
	 *
	 * @since 1.8
	 *
	 * @param bool|object $lang
	 * @param object $query
	 * @return bool|object
	 */
	public function page_for_posts_query( $lang, $query ) {
		if ( empty( $lang ) && $this->page_for_posts ) {
			$page_id = $this->get_page_id( $query );

			if ( ! empty( $page_id ) && in_array( $page_id, $pages = $this->model->get_languages_list( array( 'fields' => 'page_for_posts' ) ) ) ) {
				// fill the cache with all pages for posts to avoid one query per page later
				// the posts_per_page limit is a trick to avoid splitting the query
				get_posts( array( 'posts_per_page' => 999, 'post_type' => 'page', 'post__in' => $pages, 'lang' => '' ) );

				$lang = $this->model->post->get_language( $page_id );
				$query->is_singular = $query->is_page = false;
				$query->is_home = $query->is_posts_page = true;
			}
		}
		return $lang;
	}

	/**
	 * get queried page_id ( if exists )
	 * If permalinks are used, WordPress does set and use $query->queried_object_id and sets $query->query_vars['page_id'] to 0
	 * and does set and use $query->query_vars['page_id'] if permalinks are not used :(
	 *
	 * @since 1.5
	 *
	 * @param object $query instance of WP_Query
	 * @return int page_id
	 */
	protected function get_page_id( $query ) {
		if ( ! empty( $query->query_vars['pagename'] ) && isset( $query->queried_object_id ) ) {
			return $query->queried_object_id;
		}

		if ( isset( $query->query_vars['page_id'] ) ) {
			return $query->query_vars['page_id'];
		}

		return 0; // no page queried
	}
}
