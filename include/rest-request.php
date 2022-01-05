<?php
/**
 * @package Polylang
 */

/**
 * Main Polylang class for REST API requrests, accessible from @see PLL().
 *
 * @since 2.6
 */
class PLL_REST_Request extends PLL_Base {

	/**
	 * A `PLL_Language` when defined, `false` otherwise. `null` until the language definition process runs.
	 *
	 * @var PLL_Language|false|null
	 */
	public $curlang;

	/**
	 * @var PLL_Filters|null
	 */
	public $filters;

	/**
	 * @var PLL_Filters_Links|null
	 */
	public $filters_links;

	/**
	 * @var PLL_Admin_Links|null
	 */
	public $links;

	/**
	 * @var PLL_Nav_Menu|null
	 */
	public $nav_menu;

	/**
	 * @var PLL_Static_Pages|null
	 */
	public $static_pages;

	/**
	 * @var PLL_Filters_Widgets_Options|null
	 */
	public $filters_widgets_options;

	/**
	 * Setup filters.
	 *
	 * @since 2.6
	 *
	 * @return void
	 */
	public function init() {
		parent::init();

		if ( ! $this->model->get_languages_list() ) {
			return;
		}

		$this->set_current_language();

		$this->filters_links           = new PLL_Filters_Links( $this );
		$this->filters                 = new PLL_Filters( $this );
		$this->filters_widgets_options = new PLL_Filters_Widgets_Options( $this );

		// Static front page and page for posts.
		if ( 'page' === get_option( 'show_on_front' ) ) {
			$this->static_pages = new PLL_Static_Pages( $this );
		}

		$this->links    = new PLL_Admin_Links( $this );
		$this->nav_menu = new PLL_Nav_Menu( $this ); // For auto added pages to menu.
	}

	/**
	 * Sets the current language in the REST context, used to filter the content.
	 *
	 * @since 3.2
	 *
	 * @return void
	 */
	public function set_current_language() { // phpcs:ignore Squiz.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
		$request = $this->get_rest_request();

		/**
		 * Fires before the language is defined by PLL in the REST context.
		 *
		 * @since 3.2
		 *
		 * @param PLL_REST_Request      $polylang Instance of the main PLL's object.
		 * @param WP_REST_Request|false $request  A `WP_REST_Request` request. False on failure to retrieve the current
		 *                                        REST route. Be aware that this object may bot be 100% accurate since
		 *                                        it is created before the real one and dos not run the REST-related
		 *                                        hooks.
		 */
		do_action( 'pll_before_rest_language_defined', $this, $request );

		$lang = ! empty( $request ) ? $request->get_param( 'lang' ) : null;

		if ( ! empty( $lang ) ) {
			if ( is_string( $lang ) ) {
				$this->curlang = $this->model->get_language( sanitize_key( $lang ) );
			}

			if ( empty( $this->curlang ) && ! empty( $this->options['default_lang'] ) && is_string( $this->options['default_lang'] ) ) {
				// A lang has been requested but it is invalid, let's fall back to the default one.
				$this->curlang = $this->model->get_language( sanitize_key( $this->options['default_lang'] ) );
			}
		}

		/**
		 * Fires after the language is (maybe) defined by PLL in the REST context.
		 *
		 * @since 3.2
		 *
		 * @param PLL_REST_Request      $polylang Instance of the main PLL's object.
		 * @param WP_REST_Request|false $request  A `WP_REST_Request` request. False on failure to retrieve the current
		 *                                        REST route. Be aware that this object may bot be 100% accurate since
		 *                                        it is created before the real one and dos not run the REST-related
		 *                                        hooks.
		 */
		do_action( 'pll_after_rest_language_defined', $this, $request );

		if ( ! empty( $this->curlang ) ) {
			/** This action is documented in frontend/choose-lang.php */
			do_action( 'pll_language_defined', $this->curlang->slug, $this->curlang );
		} else {
			/** This action is documented in include/class-polylang.php */
			do_action( 'pll_no_language_defined' ); // To load overridden textdomains.
		}
	}

	/**
	 * Returns the current REST route (the path after `/wp-json`).
	 *
	 * @since  3.2
	 * @global mixed[] $HTTP_SERVER_VARS
	 *
	 * @return string
	 */
	public function get_rest_route() {
		if ( empty( get_option( 'permalink_structure' ) ) ) {
			// Not using permalinks.
			$current_route = ! empty( $_GET['rest_route'] ) && is_string( $_GET['rest_route'] ) ? wp_unslash( $_GET['rest_route'] ) : false; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			return ! empty( $current_route ) ? $current_route : '';
		}

		// Using permalinks.
		if ( ! empty( $GLOBALS['HTTP_SERVER_VARS']['REQUEST_URI'] ) && is_string( $GLOBALS['HTTP_SERVER_VARS']['REQUEST_URI'] ) ) {
			$current_uri = wp_unslash( $GLOBALS['HTTP_SERVER_VARS']['REQUEST_URI'] );
		} elseif ( ! empty( $_SERVER['REQUEST_URI'] ) && is_string( $_SERVER['REQUEST_URI'] ) ) {
			$current_uri = wp_unslash( $_SERVER['REQUEST_URI'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}

		if ( empty( $current_uri ) ) {
			return '';
		}

		$rest_prefix   = wp_parse_url( $this->restUrl( '/' ), PHP_URL_PATH );
		$current_route = wp_parse_url( $current_uri, PHP_URL_PATH );

		if ( 0 !== strpos( trailingslashit( $current_route ), $rest_prefix ) ) {
			return '';
		}

		return substr( $current_route, strlen( untrailingslashit( $rest_prefix ) ) );
	}

	/**
	 * Retrieves the URL to a REST endpoint.
	 * This is a wrapper for `rest_url()` (without the `$scheme` parameter) that doesn't trigger an error if
	 * `$wp_rewrite` is not defined.
	 * Note: The returned URL is NOT escaped.
	 *
	 * @since  3.2
	 * @see    rest_url()
	 * @global WP_Rewrite $wp_rewrite
	 *
	 * @param  string $path Optional. REST route. Default empty.
	 * @return string       Full URL to the endpoint.
	 */
	public function restUrl( $path = '' ) {
		if ( empty( $GLOBALS['wp_rewrite'] ) ) {
			$reset_wp_rewrite = true;

			/**
			 * Otherwise `rest_url()` will explode.
			 * Instanciating `WP_Rewrite` early should not have any side effects: it only sets local properties and uses
			 * `get_option( 'permalink_structure' )`.
			 */
			$GLOBALS['wp_rewrite'] = new WP_Rewrite(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		$rest_url = rest_url( $path );

		if ( ! empty( $reset_wp_rewrite ) ) {
			// Reset `$wp_rewrite` to prevent side effects.
			unset( $GLOBALS['wp_rewrite'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		return $rest_url;
	}

	/**
	 * Returns a dummy `WP_REST_Request` object for the current REST route, which can be used to retrieve data.
	 *
	 * @since 3.2
	 * @see   WP_REST_Server->serve_request()
	 *
	 * @return WP_REST_Request|false
	 */
	private function get_rest_request() {
		$path = $this->get_rest_route();

		// phpcs:disable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( empty( $path ) || empty( $_SERVER['REQUEST_METHOD'] ) ) {
			return false;
		}

		$request = new WP_REST_Request( $_SERVER['REQUEST_METHOD'], $path );
		$server  = $this->get_rest_server();

		$request->set_query_params( wp_unslash( $_GET ) );
		$request->set_body_params( wp_unslash( $_POST ) );
		$request->set_file_params( $_FILES );
		$request->set_headers( $server->get_headers( wp_unslash( $_SERVER ) ) );
		$request->set_body( $server::get_raw_data() );

		if ( isset( $_GET['_method'] ) ) {
			$request->set_method( $_GET['_method'] );
		} elseif ( isset( $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ) ) {
			$request->set_method( $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] );
		}

		// phpcs:enable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return $request;
	}

	/**
	 * Returns a REST server instance without triggering `rest_api_init`.
	 *
	 * @since  3.2
	 * @see    rest_get_server()
	 * @global WP_REST_Server $wp_rest_server
	 *
	 * @return WP_REST_Server|object REST server instance.
	 */
	private function get_rest_server() {
		if ( ! empty( $GLOBALS['wp_rest_server'] ) ) {
			return $GLOBALS['wp_rest_server'];
		}

		/** This filter is documented in wp-includes/rest-api.php */
		$server_class = apply_filters( 'wp_rest_server_class', 'WP_REST_Server' );

		return new $server_class(); // phpcs:ignore NeutronStandard.Functions.VariableFunctions.VariableFunction
	}
}
