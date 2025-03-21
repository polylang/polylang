<?php
/**
 * Loads the integration with Jetpack.
 * Works for Twenty Fourteen featured content too.
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

PLL_Integrations::instance()->jetpack = new PLL_Jetpack(); // Must be loaded before the plugin is active.
add_action( 'pll_init', array( PLL_Integrations::instance()->featured_content = new PLL_Featured_Content(), 'init' ) );
