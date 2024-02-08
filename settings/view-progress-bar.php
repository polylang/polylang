<?php
/**
 * @package Polylang-Pro
 *
 * @param int $count
 * @param int $limit
 */

defined( 'ABSPATH' ) || exit;

$percent  = round( $atts['count'] * 100 / $atts['limit'], 1 );
$percent  = (float) min( $percent, 100 );
$decimals = 1;

if ( floor( $percent ) === $percent ) {
	$decimals = 0;
}

printf(
	'<div class="pll-progress-bar-wrapper">%1$s<div style="width: %2$s;">%1$s</div></div>',
	esc_html( number_format_i18n( $percent, $decimals ) ) . '%',
	esc_attr( (string) $percent ) . '%'
);
