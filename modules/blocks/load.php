<?php
/**
 * @package Polylang-Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

add_action(
	'pll_init',
	function ( $polylang ) {
		if ( $polylang->model->has_languages() && pll_use_block_editor_plugin() ) {
			$polylang->switcher_block   = ( new PLL_Language_Switcher_Block( $polylang ) )->init();
			$polylang->navigation_block = ( new PLL_Navigation_Language_Switcher_Block( $polylang ) )->init();
		}
	}
);
