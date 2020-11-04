/**
 * @package Polylang
 */

/**
 * Tag suggest in quick edit
 */
jQuery(
	function( $ ) {
		$.ajaxPrefilter(
			function( options, originalOptions, jqXHR ) {
				if ( 'string' === typeof options.data && -1 !== options.data.indexOf( 'action=ajax-tag-search' ) && ( lang = $( ':input[name="inline_lang_choice"]' ).val() ) ) {
					options.data = 'lang=' + lang + '&' + options.data;
				}
			}
		);
	}
);

/**
 * Quick edit
 */
jQuery(
	function( $ ) {
		$( document ).on(
			'DOMNodeInserted',
			function( e ) {
				var t = $( e.target );

				// WP inserts the quick edit from
				if ( 'inline-edit' == t.attr( 'id' ) ) {
					var post_id = t.prev().attr( 'id' ).replace( "post-", "" );

					if ( post_id > 0 ) {
						// language dropdown
						var select = t.find( ':input[name="inline_lang_choice"]' );
						var lang = $( '#lang_' + post_id ).html();
						select.val( lang ); // populates the dropdown

						filter_terms( lang ); // initial filter for category checklist
						filter_pages( lang ); // initial filter for parent dropdown

						// modify category checklist an parent dropdown on language change
						select.change(
							function() {
								filter_terms( $( this ).val() );
								filter_pages( $( this ).val() );
							}
						);
					}
				}

				/**
				 * Filters the category checklist.
				 */
				function filter_terms( lang ) {
					if ( "undefined" != typeof( pll_term_languages ) ) {
						$.each(
							pll_term_languages,
							function( lg, term_tax ) {
								$.each(
									term_tax,
									function( tax, terms ) {
										$.each(
											terms,
											function( i ) {
												id = '#' + tax + '-' + pll_term_languages[ lg ][ tax ][ i ];
												lang == lg ? $( id ).show() : $( id ).hide();
											}
										);
									}
								);
							}
						);
					}
				}

				/**
				 * Filters the parent page dropdown list.
				 */
				function filter_pages( lang ) {
					if ( "undefined" != typeof( pll_page_languages ) ) {
						$.each(
							pll_page_languages,
							function( lg, pages ) {
								$.each(
									pages,
									function( i ) {
										v = $( '#post_parent option[value="' + pll_page_languages[ lg ][ i ] + '"]' );
										lang == lg ? v.show() : v.hide();
									}
								);
							}
						);
					}
				}
			}
		);
	}
);

/**
 * Update rows of translated posts when the language is modified in quick edit
 * Acts on ajaxSuccess event
 */
jQuery(
	function( $ ) {
		$( document ).ajaxSuccess(
			function( event, xhr, settings ) {
				function update_rows( post_id ) {
					// collect old translations
					var translations = new Array();
					$( '.translation_' + post_id ).each(
						function() {
							translations.push( $( this ).parent().parent().attr( 'id' ).substring( 5 ) );
						}
					);

					var data = {
						action:       'pll_update_post_rows',
						post_id:      post_id,
						translations: translations.join( ',' ),
						post_type:    $( "input[name='post_type']" ).val(),
						screen:       $( "input[name='screen']" ).val(),
						_pll_nonce:   $( "input[name='_inline_edit']" ).val() // reuse quick edit nonce
					};

					// get the modified rows in ajax and update them
					$.post(
						ajaxurl,
						data,
						function( response ) {
							if ( response ) {
								var res = wpAjax.parseAjaxResponse( response, 'ajax-response' );
								$.each(
									res.responses,
									function() {
										if ( 'row' == this.what ) {
											// data is built with a call to WP_Posts_List_Table::single_row method
											// which uses internally other WordPress methods which escape correctly values.
											// For Polylang language columns the HTML code is correctly escaped in PLL_Admin_Filters_Columns::post_column method.
											$( "#post-" + this.supplemental.post_id ).replaceWith( this.data ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.replaceWith
										}
									}
								);
							}
						}
					);
				}

				if ( 'string' == typeof( settings.data ) ) { // Need to check the type due to block editor sometime sending FormData objects
					var data = wpAjax.unserialize( settings.data ); // what were the data sent by the ajax request?
					if ( 'undefined' != typeof( data['action'] ) && 'inline-save' == data['action'] ) {
						update_rows( data['post_ID'] );
					}
				}
			}
		);
	}
);

/**
 * Media list table
 * When clicking on attach link, filters find post list per media language
 */
jQuery(
	function( $ ) {
		$.ajaxPrefilter(
			function ( options, originalOptions, jqXHR ) {
				if ( 'string' === typeof options.data && -1 !== options.data.indexOf( 'action=find_posts' ) ) {
					options.data = 'pll_post_id=' + $( '#affected' ).val() + '&' + options.data;
				}
			}
		);
	}
);
