<?php
/**
 * @package Polylang-Pro
 */

namespace WP_Syntex\Polylang\REST;

defined( 'ABSPATH' ) || exit;

add_action(
	'pll_init',
	function ( $polylang ) {
		$polylang->rest_api = new API( $polylang->model );
	}
);
