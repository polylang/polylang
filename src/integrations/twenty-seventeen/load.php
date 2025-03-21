<?php
/**
 * Loads the integration with WordPress MU Domain Mapping.
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

add_action( 'init', array( PLL_Integrations::instance()->twenty_seventeen = new PLL_Twenty_Seventeen(), 'init' ) );
