<?php
/**
 * Loads the WPML compatibility mode.
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly
};

if ( $polylang->model->get_languages_list() ) {
	if ( ! defined( 'PLL_WPML_COMPAT' ) || PLL_WPML_COMPAT ) {
		PLL_WPML_Compat::instance(); // WPML API
		PLL_WPML_Config::instance(); // wpml-config.xml
	}

	add_filter(
		'pll_settings_modules',
		function( $modules ) {
			$modules[] = 'PLL_Settings_WPML';
			return $modules;
		}
	);
}
