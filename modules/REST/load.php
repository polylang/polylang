<?php
/**
 * @package Polylang
 */

namespace WP_Syntex\Polylang\REST;

defined( 'ABSPATH' ) || exit;

add_action(
	'pll_init',
	function ( $polylang ) {
		$polylang->rest = new API( $polylang->model );
	}
);
