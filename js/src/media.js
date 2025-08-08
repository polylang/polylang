/**
 * @package Polylang
 */

/**
 * Media list table
 * When clicking on attach link, filters find post list per media language
 */
jQuery(
	function ( $ ) {
		$.ajaxPrefilter(
			function ( options, originalOptions, jqXHR ) {
				if ( 'string' === typeof options.data && -1 !== options.data.indexOf( 'action=find_posts' ) ) {
					options.data = 'pll_post_id=' + $( '#affected' ).val() + '&' + options.data;
				}
			}
		);
	}
);
