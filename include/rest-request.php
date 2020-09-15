<?php
/**
 * @package Polylang
 */

/**
 * REST API controller
 * accessible as $polylang global object
 *
 * Properties:
 * options              => inherited, reference to Polylang options array
 * model                => inherited, reference to PLL_Model object
 * links_model          => inherited, reference to PLL_Links_Model object
 * links                => reference to PLL_Admin_Links object
 * static_pages         => reference to PLL_Static_Pages object
 * filters              => reference to PLL_Frontend_Filters object
 * filters_links        => reference to PLL_Filters_Links object
 * filters_sanitization => reference to PLL_Filters_Sanitization object
 * posts                => reference to PLL_CRUD_Posts object
 * terms                => reference to PLL_CRUD_Terms object
 *
 * @since 2.6
 */
class PLL_REST_Request extends PLL_Base {
	/**
	 * Instance of PLL_Admin_Links
	 *
	 * @var PLL_Admin_Links
	 */
	public $links;

	/**
	 * Instance of PLL_Static_Pages
	 *
	 * @var PLL_Static_Pages
	 */
	public $static_pages;

	/**
	 * Instance of PLL_CRUD_Posts
	 *
	 * @var PLL_CRUD_Posts
	 */
	public $posts;

	/**
	 * Instance of PLL_CRUD_Terms
	 *
	 * @var PLL_CRUD_Terms
	 */
	public $terms;

	/**
	 * Instance of PLL_Frontend_Filters
	 *
	 * @var PLL_Frontend_Filters
	 */
	public $filters;

	/**
	 * Instance of PLL_Filters_Links
	 *
	 * @var PLL_Filters_Links
	 */
	public $filters_links;

	/**
	 * Setup filters
	 *
	 * @since 2.6
	 */
	public function init() {
		parent::init();

		if ( $this->model->get_languages_list() ) {

			/** This action is documented in include/class-polylang.php */
			do_action( 'pll_no_language_defined' ); // To load overridden textdomains.

			$this->filters_links        = new PLL_Filters_Links( $this );
			$this->filters              = new PLL_Filters( $this );

			// Static front page and page for posts
			if ( 'page' === get_option( 'show_on_front' ) ) {
				$this->static_pages = new PLL_Static_Pages( $this );
			}

			$this->links = new PLL_Admin_Links( $this );

			$this->nav_menu = new PLL_Nav_Menu( $this ); // For auto added pages to menu
		}
	}
}
