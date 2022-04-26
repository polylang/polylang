<?php
/**
 * Loads the settings module for translated slugs.
 *
 * @package Polylang
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly
};

if ( $polylang->model->get_languages_list() ) {
	$polylang->add_shared( 'translate_slugs', PLL_Translate_Slugs::class );
	add_action( 'wp_loaded', array( $polylang->get( 'translate_slugs' ), 'init' ) );
}
