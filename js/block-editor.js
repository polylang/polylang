/**
 * @package Polylang
 */

/**
 * Filter REST API requests to add the language in the request
 *
 * @since 2.5
 */
wp.apiFetch.use(
	function( options, next ) {
		// If options.url is defined, this is not a REST request but a direct call to post.php for legacy metaboxes.
		if ( 'undefined' === typeof options.url ) {
			if ( 'undefined' === typeof options.data ) {
				// GET
				options.path += ( ( options.path.indexOf( '?' ) >= 0 ) ? '&lang=' : '?lang=' ) + getCurrentLanguage();
			} else {
				// PUT, POST
				options.data.lang = getCurrentLanguage();
			}
		}
		return next( options );
	}
);

/**
 * Get the language from the HTML form
 *
 * @since 2.5
 *
 * @return {Element.value}
 */
function getCurrentLanguage() {
	return document.querySelector( '[name=post_lang_choice]' ).value;
}

/**
 * Save post after lang choice is done and redirect to the same page for refreshing all the data
 *
 * @since 2.5
 */
jQuery( document ).ready(
	function( $ ) {
		// savePost after changing the post's language and reload page for refreshing post translated data
		$( '.post_lang_choice' ).change(
			function() {
				const select = wp.data.select;
				const dispatch = wp.data.dispatch;
				const subscribe = wp.data.subscribe;

				let unsubscribe = null;

				// Listen if the savePost is done
				const savePostIsDone = new Promise(
					function( resolve, reject ) {
						unsubscribe = subscribe(
							function() {
								const isSavePostSucceeded = select( 'core/editor' ).didPostSaveRequestSucceed();
								const isSavePostFailed = select( 'core/editor' ).didPostSaveRequestFail();
								if ( isSavePostSucceeded || isSavePostFailed ) {
									if ( isSavePostFailed ) {
										reject();
									} else {
										resolve();
									}
								}
							}
						);
					}
				);

				// Specific case for empty posts
				if ( location.pathname.match( /post-new.php/gi ) ) {
					const title = select( 'core/editor' ).getEditedPostAttribute( 'title' );
					const content = select( 'core/editor' ).getEditedPostAttribute( 'content' );
					const excerpt = select( 'core/editor' ).getEditedPostAttribute( 'excerpt' );
					if ( '' === title && '' === content && '' === excerpt ) {
						// Change the new_lang parameter with the new language value for reloading the page
						// WPCS location.search is never written in the page, just used to relaoad page ( See line 94 ) with the right value of new_lang
						// new_lang input is controlled server side in PHP. The value come from the dropdown list of language returned and escaped server side
						if ( -1 != location.search.indexOf( 'new_lang' ) ) {
							// use regexp non capturing group to replace new_lang parameter no matter where it is and capture other parameters which can be behind it
							window.location.search = window.location.search.replace( /(?:new_lang=[^&]*)(&)?(.*)/, 'new_lang=' + this.value + '$1$2' ); // phpcs:ignore WordPressVIPMinimum.JS.Window.location, WordPressVIPMinimum.JS.Window.VarAssignment
						} else {
							window.location.search = window.location.search + ( ( -1 != window.location.search.indexOf( '?' ) ) ? '&' : '?' ) + 'new_lang=' + this.value; // phpcs:ignore WordPressVIPMinimum.JS.Window.location, WordPressVIPMinimum.JS.Window.VarAssignment
						}
					}
				}

				// For empty posts savePost does nothing
				dispatch( 'core/editor' ).savePost();

				savePostIsDone.then(
					function() {
						// If the post is well saved, we can reload the page
						unsubscribe();
						window.location.reload();
					},
					function() {
						// If the post save failed
						unsubscribe();
					}
				).catch(
					function() {
						// If an exception is thrown
						unsubscribe();
					}
				);
			}
		);
	}
);

/**
 * Handles internals of the metabox:
 * Language select, autocomplete input field.
 *
 * @since 1.5
 */
jQuery( document ).ready(
	function( $ ) {
		// Ajax for changing the post's language in the languages metabox
		$( '.post_lang_choice' ).change(
			function() {
				var data = {
					action:     'post_lang_choice',
					lang:       $( this ).val(),
					post_type:  $( '#post_type' ).val(),
					post_id:    $( '#post_ID' ).val(),
					_pll_nonce: $( '#_pll_nonce' ).val()
				}

				$.post(
					ajaxurl,
					data,
					function( response ) {
						var res = wpAjax.parseAjaxResponse( response, 'ajax-response' );
						$.each(
							res.responses,
							function() {
								switch ( this.what ) {
									case 'translations': // Translations fields
										// Data is built and come from server side and is well escaped when necessary
										$( '.translations' ).html( this.data ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
										init_translations();
									break;
									case 'flag': // Flag in front of the select dropdown
										// Data is built and come from server side and is well escaped when necessary
										$( '.pll-select-flag' ).html( this.data ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
									break;
								}
							}
						);
					}
				);
			}
		);

		// Translations autocomplete input box
		function init_translations() {
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

					// When the input box is emptied
					$( this ).blur(
						function() {
							if ( ! $( this ).val() ) {
								$( '#htr_lang_' + tr_lang ).val( 0 );
								// Value is retrieved from HTML already generated server side
								td.html( td.siblings( '.hidden' ).children().clone() );  // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
							}
						}
					);
				}
			);
		}

		init_translations();
	}
);
