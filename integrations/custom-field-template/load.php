<?php
/**
 * Loads the integration with Custom Field Template.
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

add_action(
	'plugins_loaded',
	function () {
		if ( class_exists( 'custom_field_template' ) ) {
			PLL_Integrations::instance()->cft = new PLL_Cft();
			PLL_Integrations::instance()->cft->init();
		}
	},
	0
);
