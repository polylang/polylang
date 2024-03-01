/**
 * @package Polylang
 */

/**
 * Tag suggest in quick edit
 */
jQuery(
	function ( $ ) {
		$.ajaxPrefilter(
			function ( options, originalOptions, jqXHR ) {
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
	function ( $ ) {
		const handleQuickEditInsertion = ( mutationsList ) => {
			for ( const mutation of mutationsList ) {
				const addedNodes = Array.from( mutation.addedNodes ).filter( el => el.nodeType === Node.ELEMENT_NODE )
				const form = addedNodes[0];
				if ( 0 < mutation.addedNodes.length && form.classList.contains( 'inline-editor' ) ) {
					// WordPress has inserted the quick edit form.
					const post_id = Number( form.id.substring( 5 ) );

					if ( post_id > 0 ) {
						// Get the language dropdown.
						const select = form.querySelector( 'select[name="inline_lang_choice"]' );
						const lang = document.querySelector( '#lang_' + String( post_id ) ).innerHTML;
						select.value = lang; // Populates the dropdown with the post language.

						filter_terms( lang ); // Initial filter for category checklist.
						filter_pages( lang ); // Initial filter for parent dropdown.

						// Modify category checklist and parent dropdown on language change.
						select.addEventListener(
							'change',
							function ( event ) {
								const newLang = event.target.value;
								filter_terms( newLang );
								filter_pages( newLang );
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
								function ( lg, term_tax ) {
									$.each(
										term_tax,
										function ( tax, terms ) {
											$.each(
												terms,
												function ( i ) {
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
								function ( lg, pages ) {
									$.each(
										pages,
										function ( i ) {
											v = $( '#post_parent option[value="' + pll_page_languages[ lg ][ i ] + '"]' );
											lang == lg ? v.show() : v.hide();
										}
									);
								}
							);
						}
					}
				}
		}
		const table = document.getElementById( 'the-list' );
		const config = { childList: true, subtree: true };
		const observer = new MutationObserver( handleQuickEditInsertion );

		observer.observe( table, config);
	}
);

/**
 * Update rows of translated posts when the language is modified in quick edit
 * Acts on ajaxSuccess event
 */
jQuery(
	function ( $ ) {
		$( document ).ajaxSuccess(
			function ( event, xhr, settings ) {
				function update_rows( post_id ) {
					// collect old translations
					var translations = new Array();
					$( '.translation_' + post_id ).each(
						function () {
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
						function ( response ) {
							if ( response ) {
								// Since WP changeset #52710 parseAjaxResponse() return content to notice the user in a HTML tag with ajax-response id.
								// Not to disturb this behaviour by executing another ajax request in the ajaxSuccess event, we need to target another unexisting id.
								var res = wpAjax.parseAjaxResponse( response, 'pll-ajax-response' );
								$.each(
									res.responses,
									function () {
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
