<?php
/**
 * @package Polylang
 */

/**
 * Common class for handling the core sitemaps.
 *
 * The child classes must called the init() method.
 *
 * @since 3.0
 */
abstract class PLL_Abstract_Sitemaps {
	/**
	 * Setups actions and filters.
	 *
	 * @since 2.8
	 *
	 * @return void
	 */
	public function init() {
		add_filter( 'pll_home_url_white_list', array( $this, 'home_url_white_list' ) );
	}

	/**
	 * Whitelists the home url filter for the sitemaps.
	 *
	 * @since 2.8
	 *
	 * @param array $whitelist White list.
	 * @return array;
	 */
	public function home_url_white_list( $whitelist ) {
		$whitelist[] = array( 'file' => 'class-wp-sitemaps-posts' );
		return $whitelist;
	}
}
