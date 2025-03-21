<?php
/**
 * Loads the settings module for translated slugs.
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly
}

if ( $polylang->model->has_languages() ) {
	add_filter(
		'pll_settings_modules',
		function ( $modules ) {
			$modules[] = 'PLL_Settings_Preview_Translate_Slugs';
			return $modules;
		}
	);
}
