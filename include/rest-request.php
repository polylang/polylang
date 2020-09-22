<?php
/**
 * @package Polylang
 */

/**
 * REST API controller
 * accessible as $polylang global object
 *
 * Properties:
 * options        => inherited, reference to Polylang options array
 * model          => inherited, reference to PLL_Model object
 * links_model    => inherited, reference to PLL_Links_Model object
 * links          => reference to PLL_Admin_Links object
 * static_pages   => reference to PLL_Static_Pages object
 * filters        => reference to PLL_Frontend_Filters object
 * filters_links  => reference to PLL_Filters_Links object
 * posts          => reference to PLL_CRUD_Posts object
 * terms          => reference to PLL_CRUD_Terms object
 *
 * @since 2.6
 */
class PLL_REST_Request extends PLL_Base {
	public $links, $static_pages, $posts, $terms, $filters, $filters_links;

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

			$this->filters_links = new PLL_Filters_Links( $this );
			$this->filters = new PLL_Filters( $this );

			// Static front page and page for posts
			if ( 'page' === get_option( 'show_on_front' ) ) {
				$this->static_pages = new PLL_Static_Pages( $this );
			}

			$this->links = new PLL_Admin_Links( $this );

			$this->nav_menu = new PLL_Nav_Menu( $this ); // For auto added pages to menu
		}
	}
}
