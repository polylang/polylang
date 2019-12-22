<?php

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
 * sync           => reference to PLL_Sync object
 *
 * @since 2.6
 */
class PLL_REST_Request extends PLL_Base {
	public $links, $static_pages, $posts, $terms, $filters, $filters_links, $sync;

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
			$this->posts = new PLL_CRUD_Posts( $this );
			$this->terms = new PLL_CRUD_Terms( $this );
			$this->sync  = new PLL_Sync( $this );

			$this->nav_menu = new PLL_Nav_Menu( $this ); // For auto added pages to menu

			// Share term slugs
			if ( get_option( 'permalink_structure' ) && $this->options['force_lang'] && class_exists( 'PLL_Share_Term_Slug' ) ) {
				$this->share_term_slug = version_compare( $GLOBALS['wp_version'], '4.8', '<' ) ?
					new PLL_Frontend_Share_Term_Slug( $this ) :
					new PLL_Share_Term_Slug( $this );
			}

			// Translate slugs, only for pretty permalinks
			if ( get_option( 'permalink_structure' ) && class_exists( 'PLL_Translate_Slugs' ) ) {
				$curlang = null;
				$slugs_model = new PLL_Translate_Slugs_Model( $this );
				$this->translate_slugs = new PLL_Translate_Slugs( $slugs_model, $curlang );
			}

			if ( class_exists( 'PLL_Sync_Post_REST' ) ) {
				$this->sync_post = new PLL_Sync_Post_REST( $this );
			}

			if ( class_exists( 'PLL_Duplicate_REST' ) ) {
				$this->duplicate_rest = new PLL_Duplicate_REST();
			}
		}
	}
}
