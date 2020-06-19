<?php
/**
 * Loads the integration with WP Offload Media Lite.
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
};

add_action(
	'plugins_loaded',
	function() {
		if ( function_exists( 'as3cf_init' ) && class_exists( 'PLL_AS3CF' ) ) {
			add_action( 'pll_init', array( PLL_Integrations::instance()->as3cf = new PLL_AS3CF(), 'init' ) );
		}
	},
	0
);

