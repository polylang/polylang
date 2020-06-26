<?php
/**
 * Loads the integration with WP Sweep.
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
};

add_action(
	'plugins_loaded',
	function() {
		if ( defined( 'WP_SWEEP_VERSION' ) ) {
			PLL_Integrations::instance()->wp_sweep = new PLL_WP_Sweep();
			PLL_Integrations::instance()->wp_sweep->init();
		}
	},
	0
);
