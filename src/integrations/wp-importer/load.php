<?php
/**
 * Loads the integration with WordPress Importer.
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

PLL_Integrations::instance()->wp_importer = new PLL_WordPress_Importer();
