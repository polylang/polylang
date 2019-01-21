<?php

/**
 * REST API controller
 * accessible as $polylang global object
 *
 * Properties:
 * options        => inherited, reference to Polylang options array
 * model          => inherited, reference to PLL_Model object
 * links_model    => inherited, reference to PLL_Links_Model object
 * filters_links  => reference to PLL_Frontend_Filters_Links object
 * posts          => reference to PLL_CRUD_Posts object
 * terms          => reference to PLL_CRUD_Terms object
 * sync           => reference to PLL_Sync object
 *
 * @since 2.6
 */
class PLL_REST_Request extends PLL_Base {
	public $posts, $terms, $filters_links, $sync;

	/**
	 * Setup filters
	 *
	 * @since 2.6
	 */
	public function init() {
		parent::init();

		if ( $this->model->get_languages_list() ) {
			$this->filters_links = new PLL_Filters_Links( $this );

			$this->posts = new PLL_CRUD_Posts( $this );
			$this->terms = new PLL_CRUD_Terms( $this );
			$this->sync  = new PLL_Sync( $this );

			// Share term slugs
			if ( get_option( 'permalink_structure' ) && $this->options['force_lang'] && class_exists( 'PLL_Share_Term_Slug' ) ) {
				$this->share_term_slug = version_compare( $GLOBALS['wp_version'], '4.8', '<' ) ?
					new PLL_Frontend_Share_Term_Slug( $this ) :
					new PLL_Share_Term_Slug( $this );
			}

			// Translate slugs, only for pretty permalinks
			if ( get_option( 'permalink_structure' ) && class_exists( 'PLL_Translate_Slugs' ) ) {
				$slugs_model = new PLL_Translate_Slugs_Model( $this );
				$this->translate_slugs = new PLL_Translate_Slugs( $slugs_model, null );
			}

			// FIXME Duplicate content needed for PLL_Sync_Post
			if ( class_exists( 'PLL_Duplicate' ) ) {
				$this->duplicate = new PLL_Duplicate( $this );
			}

			if ( class_exists( 'PLL_Sync_Post' ) ) {
				$this->sync_post = new PLL_Sync_Post( $this );
			}
		}
	}
}
