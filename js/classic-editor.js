// tag suggest in metabox
(function( $ ){
	$.ajaxPrefilter(function( options, originalOptions, jqXHR ) {
		if ( 'string' === typeof options.data && -1 !== options.url.indexOf( 'action=ajax-tag-search' ) && ( lang = $( '.post_lang_choice' ).val() ) ) {
			options.data = 'lang=' + lang + '&' + options.data;
		}
	});
})( jQuery );

// overrides tagBox.get
(function( $ ){
	// overrides function to add the language
	tagBox.get = function( id ) {
		var tax = id.substr( id.indexOf( '-' ) + 1 );

		// add the language in the $_POST variable
		var data = {
			action: 'get-tagcloud',
			lang:   $( '.post_lang_choice' ).val(),
			tax:    tax
		}

		$.post( ajaxurl, data, function( r, stat ) {
			if ( 0 == r || 'success' != stat ) {
				r = wpAjax.broken;
			}

			r = $( '<div id="tagcloud-' + tax + '" class="the-tagcloud">' + r + '</div>' );
			$( 'a', r ).click(function(){
				tagBox.flushTags( $( this ).closest( '.inside' ).children( '.tagsdiv' ), this );
				return false;
			});

			// add an if else condition to allow modifying the tags outputed when switching the language
			if ( v = $( '.the-tagcloud' ).css( 'display' ) ) {
				$( '.the-tagcloud' ).replaceWith( r );
				$( '.the-tagcloud' ).css( 'display', v );
			}
			else {
				$( '#' + id ).after( r );
			}
		});
	}
})( jQuery );

jQuery( document ).ready(function( $ ) {
	// collect taxonomies - code partly copied from WordPress
	var taxonomies = new Array();
	$( '.categorydiv' ).each(function(){
		var this_id = $( this ).attr( 'id' ), taxonomyParts, taxonomy;

		taxonomyParts = this_id.split( '-' );
		taxonomyParts.shift();
		taxonomy = taxonomyParts.join( '-' );
		taxonomies.push( taxonomy ); // store the taxonomy for future use

		// add our hidden field in the new category form - for each hierarchical taxonomy
		// to set the language when creating a new category
		$( '#' + taxonomy + '-add-submit' ).before( $( '<input />' )
			.attr( 'type', 'hidden' )
			.attr( 'id', taxonomy + '-lang' )
			.attr( 'name', 'term_lang_choice' )
			.attr( 'value', $( '.post_lang_choice' ).val() )
		);
	});

	// ajax for changing the post's language in the languages metabox
	$( '.post_lang_choice' ).change(function() {
		var value = $( this ).val();
		var lang  = $( this ).children( 'option[value="' + value + '"]' ).attr( 'lang' );
		var dir   = $( '.pll-translation-column > span[lang="' + lang + '"]' ).attr( 'dir' );

		var data = {
			action:     'post_lang_choice',
			lang:       value,
			post_type:  $( '#post_type' ).val(),
			taxonomies: taxonomies,
			post_id:    $( '#post_ID' ).val(),
			_pll_nonce: $( '#_pll_nonce' ).val()
		}

		$.post( ajaxurl, data , function( response ) {
			var res = wpAjax.parseAjaxResponse( response, 'ajax-response' );
			$.each( res.responses, function() {
				switch ( this.what ) {
					case 'translations': // translations fields
						$( '.translations' ).html( this.data );
						init_translations();
					break;
					case 'taxonomy': // categories metabox for posts
						var tax = this.data;
						$( '#' + tax + 'checklist' ).html( this.supplemental.all );
						$( '#' + tax + 'checklist-pop' ).html( this.supplemental.populars );
						$( '#new' + tax + '_parent' ).replaceWith( this.supplemental.dropdown );
						$( '#' + tax + '-lang' ).val( $( '.post_lang_choice' ).val() ); // hidden field
					break;
					case 'pages': // parent dropdown list for pages
						$( '#parent_id' ).html( this.data );
					break;
					case 'flag': // flag in front of the select dropdown
						$( '.pll-select-flag' ).html( this.data );
					break;
					case 'permalink': // Sample permalink
						var div = $( '#edit-slug-box' );
						if ( '-1' != this.data && div.children().length ) {
							div.html( this.data );
						}
					break;
				}
			});

			// modifies the language in the tag cloud
			$( '.tagcloud-link' ).each(function() {
				var id = $( this ).attr( 'id' );
				tagBox.get( id );
			});

			// Modifies the text direction
			$( 'body' ).removeClass( 'pll-dir-rtl' ).removeClass( 'pll-dir-ltr' ).addClass( 'pll-dir-' + dir );
			$( '#content_ifr' ).contents().find( 'html' ).attr( 'lang', lang ).attr( 'dir', dir );
			$( '#content_ifr' ).contents().find( 'body' ).attr( 'dir', dir );
		});
	});

	// translations autocomplete input box
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
});
