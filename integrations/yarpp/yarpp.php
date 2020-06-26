<?php
/**
 * @package Polylang
 */

/**
 * Manages the compatibility with Yet Another Related Posts Plugin.
 *
 * @since 2.8
 */
class PLL_Yarpp {
	/**
	 * Just makes YARPP aware of the language taxonomy ( after Polylang registered it ).
	 *
	 * @since 1.0
	 */
	public function init() {
		$GLOBALS['wp_taxonomies']['language']->yarpp_support = 1;
	}
}
