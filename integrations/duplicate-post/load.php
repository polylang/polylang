<?php
/**
 * Loads the integration with Duplicate Post.
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

add_action(
	'plugins_loaded',
	function () {
		if ( defined( 'DUPLICATE_POST_CURRENT_VERSION' ) ) {
			PLL_Integrations::instance()->duplicate_post = new PLL_Duplicate_Post();
			PLL_Integrations::instance()->duplicate_post->init();
		}
	},
	0
);
