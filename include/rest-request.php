<?php
/**
 * @package Polylang
 */

/**
 * REST API controller, accessible from PLL().
 *
 * @since 2.6
 */
class PLL_REST_Request extends PLL_Base {

	/**
	 * Instance of PLL_Filters.
	 *
	 * @var PLL_Filters
	 */
	public $filters;

	/**
	 * Instance of PLL_Filters_Links.
	 *
	 * @var PLL_Filters_Links
	 */
	public $filters_links;

	/**
	 * Instance of PLL_Admin_Links.
	 *
	 * @var PLL_Admin_Links
	 */
	public $links;

	/**
	 * Instance of PLL_Nav_Menu.
	 *
	 * @var PLL_Nav_Menu
	 */
	public $nav_menu;

	/**
	 * Instance of PLL_Static_Pages.
	 *
	 * @var PLL_Static_Pages
	 */
	public $static_pages;

	/**
	 * Setup filters.
	 *
	 * @since 2.6
	 */
	public function init() {
		parent::init();

		if ( $this->model->get_languages_list() ) {

			/** This action is documented in include/class-polylang.php */
			do_action( 'pll_no_language_defined' ); // To load overridden textdomains.

			$this->filters_links = new PLL_Filters_Links( $this );
			$this->filters = new PLL_Filters( $this );

			// Static front page and page for posts?
			if ( 'page' === get_option( 'show_on_front' ) ) {
				$this->static_pages = new PLL_Static_Pages( $this );
			}

			$this->links = new PLL_Admin_Links( $this );

			$this->nav_menu = new PLL_Nav_Menu( $this ); // For auto added pages to menu.
		}
	}
}
