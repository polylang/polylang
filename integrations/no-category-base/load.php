<?php
/**
 * Loads the integration with No Category Base.
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
};

PLL_Integrations::instance()->no_category_base = new PLL_No_Category_Base();
PLL_Integrations::instance()->no_category_base->init();
