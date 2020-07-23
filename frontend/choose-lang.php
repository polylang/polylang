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
	public $links_model, $model, $options;
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
	 * Writes language cookie
	 * Loads user defined translations
	 * Fires the action 'pll_language_defined'
	 *
	 * @since 1.2
	 *
	 * @param object $curlang current language
	 */
	protected function set_language( $curlang ) {
		// Don't set the language a second time
		if ( isset( $this->curlang ) ) {
			return;
		}

		// Final check in case $curlang has an unexpected value
		// See https://wordpress.org/support/topic/detect-browser-language-sometimes-setting-null-language
		$this->curlang = ( $curlang instanceof PLL_Language ) ? $curlang : $this->model->get_language( $this->options['default_lang'] );

		$GLOBALS['text_direction']  = $this->curlang->is_rtl ? 'rtl' : 'ltr';
		wp_styles()->text_direction = $GLOBALS['text_direction'];

		/**
		 * Fires when the current language is defined
		 *
		 * @since 0.9.5
		 *
		 * @param string $slug    current language code
		 * @param object $curlang current language object
		 */
		do_action( 'pll_language_defined', $this->curlang->slug, $this->curlang );
	}

	/**
	 * Set a cookie to remember the language.
	 * Setting PLL_COOKIE to false will disable cookie although it will break some functionalities
	 *
	 * @since 1.5
	 */
	public function maybe_setcookie() {
		// Don't set cookie in javascript when a cache plugin is active
		// Check headers have not been sent to avoid ugly error
		// Cookie domain must be set to false for localhost ( default value for COOKIE_DOMAIN ) thanks to Stephen Harris.
		if ( ! pll_is_cache_active() && ! headers_sent() && PLL_COOKIE !== false && ! empty( $this->curlang ) && ( ! isset( $_COOKIE[ PLL_COOKIE ] ) || $_COOKIE[ PLL_COOKIE ] != $this->curlang->slug ) && ! is_404() ) {

			/**
			 * Filter the Polylang cookie duration
			 * /!\ this filter may be fired *before* the theme is loaded
			 *
			 * @since 1.8
			 *
			 * @param int $duration cookie duration in seconds
			 */
			$expiration = apply_filters( 'pll_cookie_expiration', YEAR_IN_SECONDS );

			setcookie(
				PLL_COOKIE,
				$this->curlang->slug,
				time() + $expiration,
				COOKIEPATH,
				2 == $this->options['force_lang'] ? wp_parse_url( $this->links_model->home, PHP_URL_HOST ) : COOKIE_DOMAIN,
				is_ssl()
			);
		}
	}

	/**
	 * Get the preferred language according to the browser preferences
	 * Code adapted from http://www.thefutureoftheweb.com/blog/use-accept-language-header
	 *
	 * @since 1.8
	 *
	 * @return string|bool the preferred language slug or false
	 */
	public function get_preferred_browser_language() {
		$accept_langs = array();

		if ( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
			// Break up string into pieces ( languages and q factors )
			preg_match_all(
				'/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*((?>1|0)(?>\.[0-9]+)?))?/i',
				sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ),
				$lang_parse
			);

			$k = $lang_parse[1];
			$v = $lang_parse[4];

			if ( $n = count( $k ) ) {
				// Set default to 1 for any without q factor
				foreach ( $v as $key => $val ) {
					if ( '' === $val || (float) $val > 1 ) {
						$v[ $key ] = 1;
					}
				}

				// Bubble sort ( need a stable sort for Android, so can't use a PHP sort function )
				if ( $n > 1 ) {
					for ( $i = 2; $i <= $n; $i++ ) {
						for ( $j = 0; $j <= $n - 2; $j++ ) {
							if ( $v[ $j ] < $v[ $j + 1 ] ) {
								// Swap values
								$temp = $v[ $j ];
								$v[ $j ] = $v[ $j + 1 ];
								$v[ $j + 1 ] = $temp;
								// Swap keys
								$temp = $k[ $j ];
								$k[ $j ] = $k[ $j + 1 ];
								$k[ $j + 1 ] = $temp;
							}
						}
					}
				}
				$accept_langs = array_combine( $k, $v );
			}
		}

		$accept_langs = array_filter( $accept_langs ); // Remove languages marked as unacceptable (q=0).

		$languages = $this->model->get_languages_list( array( 'hide_empty' => true ) ); // Hides languages with no post

		/**
		 * Filter the list of languages to use to match the browser preferences
		 *
		 * @since 1.9.3
		 *
		 * @param array $languages array of PLL_Language objects
		 */
		$languages = apply_filters( 'pll_languages_for_browser_preferences', $languages );

		// Looks through sorted list and use first one that matches our language list
		foreach ( array_keys( $accept_langs ) as $accept_lang ) {
			// First loop to match the exact locale
			foreach ( $languages as $language ) {
				if ( 0 === strcasecmp( $accept_lang, $language->get_locale( 'display' ) ) ) {
					return $language->slug;
				}
			}

			// Second loop to match the language set
			foreach ( $languages as $language ) {
				if ( 0 === stripos( $accept_lang, $language->slug ) || 0 === stripos( $language->get_locale( 'display' ), $accept_lang ) ) {
					return $language->slug;
				}
			}
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
			if ( $redirect = apply_filters( 'pll_redirect_home', $redirect ) ) {
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
	 */
	public function pre_comment_on_post( $post_id ) {
		$this->set_language( $this->model->post->get_language( $post_id ) );
	}

	/**
	 * Modifies some main query vars for home page and page for posts
	 * to enable one home page ( and one page for posts ) per language
	 *
	 * @since 1.2
	 *
	 * @param object $query instance of WP_Query
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
		 * @param bool|object $lang  false or language object
		 * @param object      $query WP_Query object
		 */
		if ( $lang = apply_filters( 'pll_set_language_from_query', false, $query ) ) {
			$this->set_language( $lang );
			$this->set_curlang_in_query( $query );
		}

		// sets is_home on translated home page when it displays posts
		// is_home must be true on page 2, 3... too
		// as well as when searching an empty string: http://wordpress.org/support/topic/plugin-polylang-polylang-breaks-search-in-spun-theme
		elseif ( ( count( $query->query ) == 1 || ( is_paged() && count( $query->query ) == 2 ) || ( isset( $query->query['s'] ) && ! $query->query['s'] ) ) && $lang = get_query_var( 'lang' ) ) {
			$lang = $this->model->get_language( $lang );
			$this->set_language( $lang ); // sets the language now otherwise it will be too late to filter sticky posts !
			$query->is_home = true;
			$query->is_archive = $query->is_tax = false;
		}
	}

	/**
	 * Sets the current language in the query
	 *
	 * @since 2.2
	 *
	 * @param object $query
	 */
	protected function set_curlang_in_query( &$query ) {
		$pll_query = new PLL_Query( $query, $this->model );
		$pll_query->set_language( $this->curlang );
	}
}
