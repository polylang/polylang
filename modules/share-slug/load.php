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
	$polylang->add_shared( 'share_slug', PLL_Share_Slug::class );
	add_action( 'wp_loaded', array( $polylang->get( 'share_slug' ), 'init' ) );
}
