<?php
/**
 * @package Polylang
 */

/**
 * Main Polylang class for REST API requests, accessible from @see PLL().
 *
 * @since 2.6
 */
class PLL_REST_Request extends PLL_Base {
	/**
	 * @var PLL_Language|false|null A `PLL_Language` when defined, `false` otherwise. `null` until the language
	 *                              definition process runs.
	 */
	public $curlang;

	/**
	 * @var PLL_Default_Term|null
	 */
	public $default_term;

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
	 * @var PLL_Filters_Sanitization|null
	 */
	public $filters_sanitization;

	/**
	 * Constructor.
	 *
	 * @since 3.4
	 *
	 * @param PLL_Links_Model $links_model Reference to the links model.
	 */
	public function __construct( &$links_model ) {
		parent::__construct( $links_model );

		// Static front page and page for posts.
		// Early instantiated to be able to correctly initialize language properties.
		if ( 'page' === get_option( 'show_on_front' ) ) {
			$this->static_pages = new PLL_Static_Pages( $this );
		}

		$this->model->set_languages_ready();
	}

	/**
	 * Setup filters.
	 *
	 * @since 2.6
	 *
	 * @return void
	 */
	public function init() {
		parent::init();

		$this->default_term = new PLL_Default_Term( $this );
		$this->default_term->add_hooks();

		if ( ! $this->model->has_languages() ) {
			return;
		}

		add_filter( 'rest_pre_dispatch', array( $this, 'set_language' ), 10, 3 );

		// Use rest_pre_dispatch_filter to get the right language locale and initialize correctly sanitization filters.
		add_filter( 'rest_pre_dispatch', array( $this, 'set_filters_sanitization' ), 10, 3 );

		$this->filters_links           = new PLL_Filters_Links( $this );
		$this->filters                 = new PLL_Filters( $this );
		$this->filters_widgets_options = new PLL_Filters_Widgets_Options( $this );

		$this->links    = new PLL_Admin_Links( $this );
		$this->nav_menu = new PLL_Frontend_Nav_Menu( $this ); // For auto added pages to menu.
	}

	/**
	 * Sets the current language during a REST request if sent.
	 *
	 * @since 3.3
	 *
	 * @param mixed           $result  Response to replace the requested version with. Remains untouched.
	 * @param WP_REST_Server  $server  Server instance.
	 * @param WP_REST_Request $request Request used to generate the response.
	 * @return mixed Untouched $result.
	 *
	 * @phpstan-param WP_REST_Request<array{lang?: string}> $request
	 */
	public function set_language( $result, $server, $request ) {
		$lang = $request->get_param( 'lang' );

		if ( ! empty( $lang ) && is_string( $lang ) ) {
			$this->curlang = $this->model->get_language( sanitize_key( $lang ) );

			if ( empty( $this->curlang ) && ! empty( $this->options['default_lang'] ) ) {
				// A lang has been requested but it is invalid, let's fall back to the default one.
				$this->curlang = $this->model->get_language( sanitize_key( $this->options['default_lang'] ) );
			}
		}

		if ( ! empty( $this->curlang ) ) {
			/** This action is documented in frontend/choose-lang.php */
			do_action( 'pll_language_defined', $this->curlang->slug, $this->curlang );
		} else {
			/** This action is documented in include/class-polylang.php */
			do_action( 'pll_no_language_defined' ); // To load overridden textdomains.
		}

		return $result;
	}

	/**
	 * Initialize sanitization filters with the correct language locale.
	 *
	 * @see WP_REST_Server::dispatch()
	 *
	 * @since 2.9
	 * @since 3.8 Moved from Polylang Pro.
	 *
	 * @param mixed           $result  Response to replace the requested version with. Can be anything
	 *                                 a normal endpoint can return, or null to not hijack the request.
	 * @param WP_REST_Server  $server  Server instance.
	 * @param WP_REST_Request $request Request used to generate the response.
	 * @return mixed
	 *
	 * @phpstan-template T of array
	 * @phpstan-param WP_REST_Request<T> $request
	 */
	public function set_filters_sanitization( $result, $server, $request ) {
		$id   = $request->get_param( 'id' );
		$lang = $request->get_param( 'lang' );

		if ( is_string( $lang ) && ! empty( $lang ) ) {
			$language = $this->model->get_language( sanitize_key( $lang ) );
		} elseif ( is_numeric( $id ) && ! empty( $id ) ) {
			// Otherwise we need to get the language from the post itself.
			$language = $this->model->post->get_language( (int) $id );
		}

		if ( ! empty( $language ) ) {
			$this->filters_sanitization = new PLL_Filters_Sanitization( $language->locale );
		}

		return $result;
	}
}
