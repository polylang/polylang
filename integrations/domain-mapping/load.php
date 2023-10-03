<?php
/**
 * Loads the integration with WordPress MU Domain Mapping.
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

PLL_Integrations::instance()->dm = new PLL_Domain_Mapping();
