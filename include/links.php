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
	/**
	 * Stores the plugin options.
	 *
	 * @var \WP_Syntex\Polylang\Options\Options
	 */
	public $options;

	/**
	 * @var PLL_Model
	 */
	public $model;

	/**
	 * Instance of a child class of PLL_Links_Model.
	 *
	 * @var PLL_Links_Model
	 */
	public $links_model;

	/**
	 * Current language (used to filter the content).
	 *
	 * @var PLL_Language|null
	 */
	public $curlang;

	/**
	 * Constructor
	 *
	 * @since 1.2
	 *
	 * @param PLL_Base $polylang The Polylang object.
	 */
	public function __construct( PLL_Base &$polylang ) {
		$this->links_model = &$polylang->links_model;
		$this->model       = &$polylang->model;
		$this->options     = $polylang->options;
	}

	/**
	 * Returns the home url in the requested language.
	 *
	 * @since 1.3
	 *
	 * @param PLL_Language|string $language  The language.
	 * @param bool                $is_search Optional, whether we need the home url for a search form, defaults to false.
	 * @return string
	 */
	public function get_home_url( $language, $is_search = false ) {
		if ( ! $language instanceof PLL_Language ) {
			$language = $this->model->get_language( $language );
		}

		if ( empty( $language ) ) {
			return home_url( '/' );
		}

		return $is_search ? $language->get_search_url() : $language->get_home_url();
	}
}
