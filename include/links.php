<?php
/**
 * @package Polylang
 */

/**
 * Manages links related functions
 *
 * @since 1.2
 */
class PLL_Links {
	public $links_model, $model, $options;

	/**
	 * Constructor
	 *
	 * @since 1.2
	 *
	 * @param object $polylang
	 */
	public function __construct( &$polylang ) {
		$this->links_model = &$polylang->links_model;
		$this->model = &$polylang->model;
		$this->options = &$polylang->options;
	}

	/**
	 * Returns the home url in the requested language
	 *
	 * @since 1.3
	 *
	 * @param object|string $language
	 * @param bool          $is_search optional whether we need the home url for a search form, defaults to false
	 */
	public function get_home_url( $language, $is_search = false ) {
		$language = is_object( $language ) ? $language : $this->model->get_language( $language );
		return $is_search ? $language->search_url : $language->home_url;
	}
}
