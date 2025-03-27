<?php
/**
 * Loads the integration with Yet Another Related Posts Plugin.
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

add_action(
	'plugins_loaded',
	function () {
		if ( defined( 'YARPP_VERSION' ) ) {
			add_action( 'init', array( PLL_Integrations::instance()->yarpp = new PLL_Yarpp(), 'init' ) );
		}
	},
	0
);
