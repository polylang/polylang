<?php
/**
 * Loads the settings module for Machine Translation.
 *
 * @package Polylang
 */

defined( 'ABSPATH' ) || exit;

if ( $polylang->model->has_languages() ) {
	add_filter(
		'pll_settings_modules',
		function ( $modules ) {
			$modules[] = 'PLL_Settings_Preview_Machine_Translation';
			return $modules;
		}
	);
}
