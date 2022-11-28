<?php
/**
 * @package Polylang
 */

/**
 * Base class to choose the language
 *
 * @since 1.2
 */
abstract class PLL_Choose_Lang {
	/**
	 * Stores the plugin options.
	 *
	 * @var array
	 */
	public $options;

	/**
	 * @var PLL_Model
	 */
	public $model;

	/**
	 * Instance of a child class of PLL_Links_Model.
	 *
	 * @var PLL_Links_Model
	 */
	public $links_model;

	/**
	 * Current language.
	 *
	 * @var PLL_Language|null
	 */
	public $curlang;

	/**
	 * Constructor
	 *
	 * @since 1.2
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		$this->links_model = &$polylang->links_model;
		$this->model = &$polylang->model;
		$this->options = &$polylang->options;

		$this->curlang = &$polylang->curlang;
	}

	/**
	 * Sets the language for ajax requests
	 * and setup actions
	 * Any child class must call this method if it overrides it
	 *
	 * @since 1.8
	 *
	 * @return void
	 */
	public function init() {
		if ( Polylang::is_ajax_on_front() || ! wp_using_themes() ) {
			$this->set_language( empty( $_REQUEST['lang'] ) ? $this->get_preferred_language() : $this->model->get_language( sanitize_key( $_REQUEST['lang'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		add_action( 'pre_comment_on_post', array( $this, 'pre_comment_on_post' ) ); // sets the language of comment
		add_action( 'parse_query', array( $this, 'parse_main_query' ), 2 ); // sets the language in special cases
		add_action( 'wp', array( $this, 'maybe_setcookie' ), 7 );
	}

	/**
	 * Sets the current language
	 * and fires the action 'pll_language_defined'.
	 *
	 * @since 1.2
	 *
	 * @param PLL_Language $curlang Current language.
	 * @return void
	 */
	protected function set_language( $curlang ) {
		// Don't set the language a second time
		if ( isset( $this->curlang ) ) {
			return;
		}

		// Final check in case $curlang has an unexpected value
		// See https://wordpress.org/support/topic/detect-browser-language-sometimes-setting-null-language
		$this->curlang = ( $curlang instanceof PLL_Language ) ? $curlang : $this->model->get_language( $this->options['default_lang'] );

		$GLOBALS['text_direction'] = $this->curlang->is_rtl ? 'rtl' : 'ltr';
		if ( did_action( 'wp_default_styles' ) ) {
			wp_styles()->text_direction = $GLOBALS['text_direction'];
		}

		/**
		 * Fires when the current language is defined.
		 *
		 * @since 0.9.5
		 *
		 * @param string       $slug    Current language code.
		 * @param PLL_Language $curlang Current language object.
		 */
		do_action( 'pll_language_defined', $this->curlang->slug, $this->curlang );
	}

	/**
	 * Set a cookie to remember the language.
	 * Setting PLL_COOKIE to false will disable cookie although it will break some functionalities
	 *
	 * @since 1.5
	 *
	 * @return void
	 */
	public function maybe_setcookie() {
		// Don't set cookie in javascript when a cache plugin is active.
		if ( ! pll_is_cache_active() && ! empty( $this->curlang ) && ! is_404() ) {
			$args = array(
				'domain'   => 2 === $this->options['force_lang'] ? wp_parse_url( $this->links_model->home, PHP_URL_HOST ) : COOKIE_DOMAIN,
				'samesite' => 3 === $this->options['force_lang'] ? 'None' : 'Lax',
			);
			PLL_Cookie::set( $this->curlang->slug, $args );
		}
	}

	/**
	 * Get the preferred language according to the browser preferences.
	 *
	 * @since 1.8
	 *
	 * @return string|bool The preferred language slug or false.
	 */
	public function get_preferred_browser_language() {
		if ( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
			$accept_langs = PLL_Accept_Languages_Collection::from_accept_language_header( sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) );

			$accept_langs->bubble_sort();

			$languages = $this->model->get_languages_list( array( 'hide_empty' => true ) ); // Hides languages with no post.

			/**
			 * Filters the list of languages to use to match the browser preferences.
			 *
			 * @since 1.9.3
			 *
			 * @param array $languages Array of PLL_Language objects.
			 */
			$languages = apply_filters( 'pll_languages_for_browser_preferences', $languages );

			return $accept_langs->find_best_match( $languages );
		}

		return false;
	}

	/**
	 * Returns the preferred language
	 * either from the cookie if it's a returning visit
	 * or according to browser preference
	 * or the default language
	 *
	 * @since 0.1
	 *
	 * @return object browser preferred language or default language
	 */
	public function get_preferred_language() {
		$language = false;
		$cookie   = false;

		if ( isset( $_COOKIE[ PLL_COOKIE ] ) ) {
			// Check first if the user was already browsing this site.
			$language = sanitize_key( $_COOKIE[ PLL_COOKIE ] );
			$cookie   = true;
		} elseif ( $this->options['browser'] ) {
			$language = $this->get_preferred_browser_language();
		}

		/**
		 * Filter the visitor's preferred language (normally set first by cookie
		 * if this is not the first visit, then by the browser preferences).
		 * If no preferred language has been found or set by this filter,
		 * Polylang fallbacks to the default language
		 *
		 * @since 1.0
		 * @since 2.7 Added $cookie parameter.
		 *
		 * @param string|bool $language Preferred language code, false if none has been found.
		 * @param bool        $cookie   Whether the preferred language has been defined by the cookie.
		 */
		$slug = apply_filters( 'pll_preferred_language', $language, $cookie );

		// Return default if there is no preferences in the browser or preferences does not match our languages or it is requested not to use the browser preference
		return ( $lang = $this->model->get_language( $slug ) ) ? $lang : $this->model->get_language( $this->options['default_lang'] );
	}

	/**
	 * Sets the language when home page is requested
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	protected function home_language() {
		// Test referer in case PLL_COOKIE is set to false. Since WP 3.6.1, wp_get_referer() validates the host which is exactly what we want
		// Thanks to Ov3rfly http://wordpress.org/support/topic/enhance-feature-when-front-page-is-visited-set-language-according-to-browser
		$language = $this->options['hide_default'] && ( wp_get_referer() || ! $this->options['browser'] ) ?
			$this->model->get_language( $this->options['default_lang'] ) :
			$this->get_preferred_language(); // Sets the language according to browser preference or default language
		$this->set_language( $language );
	}

	/**
	 * To call when the home page has been requested
	 * Make sure to call this after 'setup_theme' has been fired as we need $wp_query
	 * Performs a redirection to the home page in the current language if needed
	 *
	 * @since 0.9
	 *
	 * @return void
	 */
	public function home_requested() {
		// We are already on the right page
		if ( $this->options['default_lang'] == $this->curlang->slug && $this->options['hide_default'] ) {
			$this->set_curlang_in_query( $GLOBALS['wp_query'] );

			/**
			 * Fires when the site root page is requested
			 *
			 * @since 1.8
			 */
			do_action( 'pll_home_requested' );
		}
		// Redirect to the home page in the right language
		// Test to avoid crash if get_home_url returns something wrong
		// FIXME why this happens? http://wordpress.org/support/topic/polylang-crashes-1
		// Don't redirect if $_POST is not empty as it could break other plugins
		elseif ( is_string( $redirect = $this->curlang->home_url ) && empty( $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			// Don't forget the query string which may be added by plugins
			$query_string = wp_parse_url( pll_get_requested_url(), PHP_URL_QUERY );
			if ( ! empty( $query_string ) ) {
				$redirect .= ( $this->links_model->using_permalinks ? '?' : '&' ) . $query_string;
			}

			/**
			 * When a visitor reaches the site home, Polylang redirects to the home page in the correct language.
			 * This filter allows plugins to modify the redirected url or prevent this redirection
			 * /!\ this filter may be fired *before* the theme is loaded
			 *
			 * @since 1.1.1
			 *
			 * @param string $redirect the url the visitor will be redirected to
			 */
			$redirect = apply_filters( 'pll_redirect_home', $redirect );
			if ( $redirect && wp_validate_redirect( $redirect ) ) {
				$this->maybe_setcookie();
				header( 'Vary: Accept-Language' );
				wp_safe_redirect( $redirect, 302, POLYLANG );
				exit;
			}
		}
	}

	/**
	 * Set the language when posting a comment
	 *
	 * @since 0.8.4
	 *
	 * @param int $post_id the post being commented
	 * @return void
	 */
	public function pre_comment_on_post( $post_id ) {
		$this->set_language( $this->model->post->get_language( $post_id ) );
	}

	/**
	 * Modifies some main query vars for the home page and the page for posts
	 * to enable one home page (and one page for posts) per language.
	 *
	 * @since 1.2
	 *
	 * @param WP_Query $query Instance of WP_Query.
	 * @return void
	 */
	public function parse_main_query( $query ) {
		if ( ! $query->is_main_query() ) {
			return;
		}

		/**
		 * This filter allows to set the language based on information contained in the main query
		 *
		 * @since 1.8
		 *
		 * @param PLL_Language|false $lang  Language object or false.
		 * @param WP_Query           $query WP_Query object.
		 */
		if ( $lang = apply_filters( 'pll_set_language_from_query', false, $query ) ) {
			$this->set_language( $lang );
			$this->set_curlang_in_query( $query );
		} elseif ( ( count( $query->query ) == 1 || ( is_paged() && count( $query->query ) == 2 ) ) && $lang = get_query_var( 'lang' ) ) {
			$lang = $this->model->get_language( $lang );
			$this->set_language( $lang ); // Set the language now otherwise it will be too late to filter sticky posts!

			// Set is_home on translated home page when it displays posts. It must be true on page 2, 3... too.
			$query->is_home    = true;
			$query->is_tax     = false;
			$query->is_archive = false;

			// Filters is_front_page() in case a static front page is not translated in this language.
			add_filter( 'option_show_on_front', array( $this, 'filter_option_show_on_front' ) );
		}
	}

	/**
	 * Filters the option show_on_front when the current front page displays posts.
	 *
	 * This is useful when a static front page is not translated in all languages.
	 *
	 * @return string
	 */
	public function filter_option_show_on_front() {
		return 'posts';
	}

	/**
	 * Sets the current language in the query.
	 *
	 * @since 2.2
	 *
	 * @param WP_Query $query Instance of WP_Query.
	 * @return void
	 */
	protected function set_curlang_in_query( &$query ) {
		if ( ! empty( $this->curlang ) ) {
			$pll_query = new PLL_Query( $query, $this->model );
			$pll_query->set_language( $this->curlang );
		}
	}
}
