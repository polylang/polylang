<?php
/**
 * Loads the site health.
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
};

if ( $polylang instanceof PLL_Admin && $polylang->model->get_languages_list() ) {
	add_action(
		'pll_init',
		function ( $polylang ) {
			$polylang->container->addShared( 'site_health', new PLL_Admin_Site_Health( $polylang->model, $polylang->static_pages ) );
			$polylang->container->get( 'site_health' );
		}
	);
}
