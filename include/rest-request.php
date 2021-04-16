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
	 * @var PLL_Filters
	 */
	public $filters;

	/**
	 * @var PLL_Filters_Links
	 */
	public $filters_links;

	/**
	 * @var PLL_Admin_Links
	 */
	public $links;

	/**
	 * @var PLL_Nav_Menu
	 */
	public $nav_menu;

	/**
	 * @var PLL_Static_Pages
	 */
	public $static_pages;

	/**
	 * @var PLL_Filters_Widgets_Options
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

		if ( $this->model->get_languages_list() ) {

			/** This action is documented in include/class-polylang.php */
			do_action( 'pll_no_language_defined' ); // To load overridden textdomains.

			$this->filters_links = new PLL_Filters_Links( $this );
			$this->filters = new PLL_Filters( $this );
			$this->filters_widgets_options = new PLL_Filters_Widgets_Options( $this );

			// Static front page and page for posts.
			if ( 'page' === get_option( 'show_on_front' ) ) {
				$this->static_pages = new PLL_Static_Pages( $this );
			}

			$this->links = new PLL_Admin_Links( $this );

			$this->nav_menu = new PLL_Nav_Menu( $this ); // For auto added pages to menu.
		}
	}
}
