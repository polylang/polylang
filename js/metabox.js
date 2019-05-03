/**
 * Handles internals of the metabox: autocomplete input field and buttons.
 *
 * @since 1.5
 */
jQuery( document ).ready(function( $ ) {
	// Translations autocomplete input box
	function init_translations() {
		$( '.tr_lang' ).each(function(){
			var tr_lang = $( this ).attr( 'id' ).substring( 8 );
			var td = $( this ).parent().parent().siblings( '.pll-edit-column' );

			$( this ).autocomplete({
				minLength: 0,

				source: ajaxurl + '?action=pll_posts_not_translated' +
					'&post_language=' + $( '.post_lang_choice' ).val() +
					'&translation_language=' + tr_lang +
					'&post_type=' + $( '#post_type' ).val() +
					'&_pll_nonce=' + $( '#_pll_nonce' ).val(),

				select: function( event, ui ) {
					$( '#htr_lang_' + tr_lang ).val( ui.item.id );
					td.html( ui.item.link );
				},
			});

			// when the input box is emptied
			$( this ).blur(function() {
				if ( ! $( this ).val() ) {
					$( '#htr_lang_' + tr_lang ).val( 0 );
					td.html( td.siblings( '.hidden' ).children().clone() );
				}
			});
		});
	}

	init_translations();

	// Handle the response to a click on a Languages metabox button
	$( '#ml_box' ).on( 'click', '.pll-button', function(){
		var value = $( this ).hasClass( 'wp-ui-text-highlight' );
		var id = $( this ).attr( 'id' );
		var post_id = $( '#htr_lang_' + id.replace( 'pll_sync_post[', '' ).replace( ']', '' ) ).val();

		if ( 'undefined' == typeof( post_id ) || 0 == post_id || value || confirm( confirm_text ) ) {
			var data = {
				action:     'toggle_' + id,
				value:      value,
				post_type:  $( '#post_type' ).val(),
				_pll_nonce: $( '#_pll_nonce' ).val()
			}

			$.post( ajaxurl, data , function( response ){
				var res = wpAjax.parseAjaxResponse( response, 'ajax-response' );
				$.each( res.responses, function() {
					id = id.replace( '[', '\\[' ).replace( ']', '\\]' );
					$( '#' + id ).toggleClass( 'wp-ui-text-highlight' ).attr( 'title', this.data ).children( 'span' ).html( this.data );
					$( 'input[name="' + id + '"]' ).val( ! data['value'] );
				});
			});
		}
	});
});
