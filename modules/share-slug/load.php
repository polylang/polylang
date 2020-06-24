<?php
/**
 * Loads the settings module for shared slugs.
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly
};

if ( $polylang->model->get_languages_list() ) {
	add_filter(
		'pll_settings_modules',
		function( $modules ) {
			$modules[] = 'PLL_Settings_Share_Slug';
			return $modules;
		}
	);
}
