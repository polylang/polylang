<?php
/**
 * Loads the WPML compatibility mode.
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
};

if ( $polylang->model->get_languages_list() && ( ! defined( 'PLL_WPML_COMPAT' ) || PLL_WPML_COMPAT ) ) {
	require_once __DIR__ . '/wpml-legacy-api.php';

	$polylang->add_shared( 'wpml_api', PLL_WPML_API::class );

	if ( ! $polylang->has( 'wpml_compat' ) ) { // This test if for back-compat' with PLL_WPML_Compat::instance().
		$polylang->add_shared( 'wpml_compat', PLL_WPML_Compat::class );
	}

	if ( ! $polylang->has( 'wpml_config' ) ) { // This test if for back-compat' with PLL_WPML_Config::instance().
		$polylang->add_shared( 'wpml_config', PLL_WPML_Config::class );
	}

	$polylang->get( 'wpml_api' )->init();
	$polylang->get( 'wpml_compat' )->init();
	$polylang->get( 'wpml_config' )->init();
}
