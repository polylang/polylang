<?php
/**
 * @package Polylang
 */

/**
 * Manages the compatibility with Aqua Resizer when used in themes.
 *
 * @since 2.8
 */
class PLL_Aqua_Resizer {
	/**
	 * Setups filters.
	 *
	 * @since 2.8
	 */
	public function init() {
		add_filter( 'pll_home_url_black_list', array( $this, 'home_url_black_list' ) );
	}

	/**
	 * Avoids filtering the home url for the function aq_resize().
	 *
	 * @since 1.1.5
	 *
	 * @param array $arr Home url filter black list.
	 * @return array
	 */
	public function home_url_black_list( $arr ) {
		return array_merge( $arr, array( array( 'function' => 'aq_resize' ) ) );
	}
}
