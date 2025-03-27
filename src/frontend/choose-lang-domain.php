<?php
/**
 * @package Polylang
 */

/**
 * Choose the language when the language is managed by different domains
 *
 * @since 1.5
 */
class PLL_Choose_Lang_Domain extends PLL_Choose_Lang_Url {

	/**
	 * Don't set any language cookie
	 *
	 * @since 1.5
	 *
	 * @return void
	 */
	public function maybe_setcookie() {}

	/**
	 * Don't redirect according to browser preferences
	 *
	 * @since 1.5
	 *
	 * @return PLL_Language
	 */
	public function get_preferred_language() {
		return $this->model->get_language( $this->links_model->get_language_from_url() );
	}

	/**
	 * Adds query vars to query for home pages in all languages
	 *
	 * @since 1.5
	 *
	 * @return void
	 */
	public function home_requested() {
		$this->set_curlang_in_query( $GLOBALS['wp_query'] );
		/** This action is documented in include/choose-lang.php */
		do_action( 'pll_home_requested' );
	}
}
