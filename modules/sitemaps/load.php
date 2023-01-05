<?php
/**
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
};

if ( $polylang->model->get_languages_list() ) {
	if ( $polylang->links_model instanceof PLL_Links_Abstract_Domain ) {
		$polylang->add_shared( 'sitemaps', new PLL_Sitemaps_Domain( $polylang->links_model ) );
	} else {
		$polylang->add_shared( 'sitemaps', new PLL_Sitemaps( $polylang->links_model, $polylang->model, $polylang->options ) );
	}

	$polylang->get( 'sitemaps' )->init();
}
