<?php
/**
 * Loads the integration with cache plugins.
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

add_action(
	'plugins_loaded',
	function () {
		if ( pll_is_cache_active() ) {
			add_action( 'pll_init', array( PLL_Integrations::instance()->cache_compat = new PLL_Cache_Compat(), 'init' ) );
		}
	},
	0
);
