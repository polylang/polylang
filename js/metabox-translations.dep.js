/**
 * @package Polylang
 */

 /**
  * Refresh translations fields and correctly set auto-completion.
  * 
  * @param {jQuery} $ The jQuery library instance.
  */
function init_translations( $ ) {
	$( '.tr_lang' ).each(
		function(){
			var tr_lang = $( this ).attr( 'id' ).substring( 8 );
			var td = $( this ).parent().parent().siblings( '.pll-edit-column' );

			$( this ).autocomplete(
				{
					minLength: 0,
					source: ajaxurl + '?action=pll_posts_not_translated' +
						'&post_language=' + $( '.post_lang_choice' ).val() +
						'&translation_language=' + tr_lang +
						'&post_type=' + $( '#post_type' ).val() +
						'&_pll_nonce=' + $( '#_pll_nonce' ).val(),
					select: function( event, ui ) {
						$( '#htr_lang_' + tr_lang ).val( ui.item.id );
						// ui.item.link is built and come from server side and is well escaped when necessary
						td.html( ui.item.link ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
					},
				}
			);

			// when the input box is emptied
			$( this ).on(
				'blur',
				function() {
					if ( ! $( this ).val() ) {
						$( '#htr_lang_' + tr_lang ).val( 0 );
						// Value is retrieved from HTML already generated server side
						td.html( td.siblings( '.hidden' ).children().clone() ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
					}
				}
			);
		}
	);
}

export { init_translations };
