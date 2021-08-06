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
	 * @var PLL_Language|false|null A `PLL_Language` when defined, `false` otherwise. `null` until the language
	 *                              definition process runs.
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

		if ( ! empty( $_REQUEST['lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			if ( is_string( $_REQUEST['lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$this->curlang = $this->model->get_language( sanitize_key( $_REQUEST['lang'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			}

			if ( empty( $this->curlang ) && ! empty( $this->options['default_lang'] ) && is_string( $this->options['default_lang'] ) ) {
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
}
