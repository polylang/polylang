<?php
/**
 * Loads the module for general synchronization such as metas and taxonomies.
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly
};

if ( $polylang->model->get_languages_list() ) {
	if ( $polylang instanceof PLL_Admin_Base ) {
		$polylang->sync = new PLL_Admin_Sync( $polylang );
	} else {
		$polylang->sync = new PLL_Sync( $polylang );
	}

	add_filter(
		'pll_settings_modules',
		function( $modules ) {
			$modules[] = 'PLL_Settings_Sync';
			return $modules;
		}
	);
}
