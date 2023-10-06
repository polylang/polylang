<?php
/**
 * Loads the integration with Yoast SEO.
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

add_action(
	'plugins_loaded',
	function () {
		if ( defined( 'WPSEO_VERSION' ) ) {
			add_action( 'pll_init', array( PLL_Integrations::instance()->wpseo = new PLL_WPSEO(), 'init' ) );
		}
	},
	0
);
