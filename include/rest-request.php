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
		if ( $this->model->get_languages_list() ) {
			$this->filters_links = new PLL_Filters_Links( $this );

			$this->posts = new PLL_CRUD_Posts( $this );
			$this->terms = new PLL_CRUD_Terms( $this );
			$this->sync  = new PLL_Sync( $this );
		}
	}
}
