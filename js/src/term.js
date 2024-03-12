/**
 * @package Polylang
 */

/**
 * Quick edit
 */
jQuery(
	function ( $ ) {
		const handleQuickEditInsertion = ( mutationsList ) => {
			for ( const mutation of mutationsList ) {
				const addedNodes = Array.from( mutation.addedNodes ).filter( el => el.nodeType === Node.ELEMENT_NODE )
				const form = addedNodes[0];
				if ( 0 < mutation.addedNodes.length && form.classList.contains( 'inline-edit-row' ) ) {
					// WordPress has inserted the quick edit form.
					const term_id = Number( form.id.substring( 5 ) );

					if ( term_id > 0 ) {
						// Get the language dropdown.
						const select = form.querySelector( 'select[name="inline_lang_choice"]' );
						const lang = document.querySelector( '#lang_' + String( term_id ) ).innerHTML;
						select.value = lang; // Populates the dropdown with the post language.

						// Disable the language dropdown for default categories.
						const default_cat = document.querySelector( `#default_cat_${term_id}` )?.innerHTML;
						if ( term_id == default_cat ) {
							select.disabled = true;
						}
					}
				}
			}
		}
		const table = document.getElementById( 'the-list' );
		if ( null !== table ) {
			// Ensure the table is displayed before listening to any change.
			const config = { childList: true, subtree: true };
			const observer = new MutationObserver( handleQuickEditInsertion );

			observer.observe( table, config);
		}
	}
);

/**
 * Update rows of translated terms when adding / deleting a translation or when the language is modified in quick edit.
 * Acts on ajaxSuccess event.
 */
jQuery(
	function ( $ ) {
		$( document ).ajaxSuccess(
			function ( event, xhr, settings ) {
				function update_rows( term_id ) {
					// collect old translations
					var translations = new Array();
					$( '.translation_' + term_id ).each(
						function () {
							translations.push( $( this ).parent().parent().attr( 'id' ).substring( 4 ) );
						}
					);

					var data = {
						action:       'pll_update_term_rows',
						term_id:      term_id,
						translations: translations.join( ',' ),
						taxonomy:     $( "input[name='taxonomy']" ).val(),
						post_type:    $( "input[name='post_type']" ).val(),
						screen:       $( "input[name='screen']" ).val(),
						_pll_nonce:   $( '#_pll_nonce' ).val()
					};

					// get the modified rows in ajax and update them
					$.post(
						ajaxurl,
						data,
						function ( response ) {
							if ( response ) {
								// Target a non existing WP HTML id to avoid a conflict with WP ajax requests.
								var res = wpAjax.parseAjaxResponse( response, 'pll-ajax-response' );
								$.each(
									res.responses,
									function () {
										if ( 'row' == this.what ) {
											// data is built with a call to WP_Terms_List_Table::single_row method
											// which uses internally other WordPress methods which escape correctly values.
											// For Polylang language columns the HTML code is correctly escaped in PLL_Admin_Filters_Columns::term_column method.
											$( "#tag-" + this.supplemental.term_id ).replaceWith( this.data ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.replaceWith
										}
									}
								);
							}
						}
					);
				}

				var data = wpAjax.unserialize( settings.data ); // what were the data sent by the ajax request?
				if ( 'undefined' != typeof( data['action'] ) ) {
					switch ( data['action'] ) {
						// when adding a term, the new term_id is in the ajax response
						case 'add-tag':
							// Target a non existing WP HTML id to avoid a conflict with WP ajax requests.
							res = wpAjax.parseAjaxResponse( xhr.responseXML, 'pll-ajax-response' );
							$.each(
								res.responses,
								function () {
									if ( 'term' == this.what ) {
										update_rows( this.supplemental.term_id );
									}
								}
							);

							// and also reset translations hidden input fields
							$( '.htr_lang' ).val( 0 );
						break;

						// when deleting a term
						case 'delete-tag':
							update_rows( data['tag_ID'] );
						break;

						// in case the language is modified in quick edit and breaks translations
						case 'inline-save-tax':
							update_rows( data['tax_ID'] );
						break;
					}
				}
			}
		);
	}
);

jQuery(
	function ( $ ) {
		// translations autocomplete input box
		function init_translations() {
			$( '.tr_lang' ).each(
				function () {
					var tr_lang = $( this ).attr( 'id' ).substring( 8 );
					var td = $( this ).parent().parent().siblings( '.pll-edit-column' );

					$( this ).autocomplete(
						{
							minLength: 0,
							source: ajaxurl + '?action=pll_terms_not_translated' +
								'&term_language=' + $( '#term_lang_choice' ).val() +
								'&term_id=' + $( "input[name='tag_ID']" ).val() +
								'&taxonomy=' + $( "input[name='taxonomy']" ).val() +
								'&translation_language=' + tr_lang +
								'&post_type=' + typenow +
								'&_pll_nonce=' + $( '#_pll_nonce' ).val(),
							select: function ( event, ui ) {
								$( '#htr_lang_' + tr_lang ).val( ui.item.id );
								// ui.item.link is built and come from server side and is well escaped when necessary
								td.html( ui.item.link ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
							},
						}
					);

					// when the input box is emptied
					$( this ).on(
						'blur',
						function () {
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

		init_translations();

		// ajax for changing the term's language
		$( '#term_lang_choice' ).on(
			'change',
			function () {
				var value = $( this ).val();
				var lang  = $( this ).children( 'option[value="' + value + '"]' ).attr( 'lang' );
				var dir   = $( '.pll-translation-column > span[lang="' + lang + '"]' ).attr( 'dir' );

				var data = {
					action:     'term_lang_choice',
					lang:       value,
					from_tag:   $( "input[name='from_tag']" ).val(),
					term_id:    $( "input[name='tag_ID']" ).val(),
					taxonomy:   $( "input[name='taxonomy']" ).val(),
					post_type:  typenow,
					_pll_nonce: $( '#_pll_nonce' ).val()
				};

				$.post(
					ajaxurl,
					data,
					function ( response ) {
						// Target a non existing WP HTML id to avoid a conflict with WP ajax requests.
						var res = wpAjax.parseAjaxResponse( response, 'pll-ajax-response' );
						$.each(
							res.responses,
							function () {
								switch ( this.what ) {
									case 'translations': // translations fields
										// Data is built and come from server side and is well escaped when necessary
										$( "#term-translations" ).html( this.data ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
										init_translations();
									break;
									case 'parent': // parent dropdown list for hierarchical taxonomies
										// data correctly escaped in PLL_Admin_Filters_Term::term_lang_choice method which uses wp_dropdown_categories function.
										$( '#parent' ).replaceWith( this.data ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.replaceWith
									break;
									case 'tag_cloud': // popular items
										// data correctly escaped in PLL_Admin_Filters_Term::term_lang_choice method which uses wp_tag_cloud and wp_generate_tag_cloud functions.
										$( '.tagcloud' ).replaceWith( this.data ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.replaceWith
									break;
									case 'flag': // flag in front of the select dropdown
										// Data is built and come from server side and is well escaped when necessary
										$( '.pll-select-flag' ).html( this.data ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
									break;
								}
							}
						);

						// Modifies the text direction
						$( 'body' ).removeClass( 'pll-dir-rtl' ).removeClass( 'pll-dir-ltr' ).addClass( 'pll-dir-' + dir );
					}
				);
			}
		);
	}
);
