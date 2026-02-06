<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\Blocks;

use PLL_Admin;
use WP_Syntex\Polylang\Blocks\Javascript_SSR;
use WP_Syntex\Polylang\Blocks\Language_Switcher\Standard\Block as Standard_Block;
use WP_Syntex\Polylang\Blocks\Language_Switcher\Navigation\Block as Navigation_Block;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

add_action(
	'pll_init',
	function ( $polylang ) {
		if ( $polylang->model->has_languages() ) {
			$polylang->switcher_block   = ( new Standard_Block( $polylang ) )->init();
			$polylang->navigation_block = ( new Navigation_Block( $polylang ) )->init();
			$polylang->javascript_ssr   = $polylang instanceof PLL_Admin
				? ( new Javascript_SSR( $polylang->model->get_default_language(), $polylang->curlang ) )->init()
				: null;
		}
	}
);
