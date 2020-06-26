<?php
/**
 * Loads the integration with Aqua Resizer.
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
};

PLL_Integrations::instance()->aq_resizer = new PLL_Aqua_Resizer();
PLL_Integrations::instance()->aq_resizer->init();
