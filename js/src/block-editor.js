/**
 * @package Polylang
 */

import {
	initializeLanguageOldValue,
	initializeConfirmationModal
} from './lib/confirmation-modal';

import {
	initMetaboxAutoComplete,
} from './lib/metabox-autocomplete';

import filterPathMiddleware from './lib/filter-path-middleware';

/**
 * Filter REST API requests to add the language in the request
 *
 * @since 2.5
 */
wp.apiFetch.use(
	function ( options, next ) {
		/*
		 * If options.url is defined, this is not a REST request but a direct call to post.php for legacy metaboxes.
		 * If `filteredRoutes` is not defined, return early.
		 */
		if ( 'undefined' !== typeof options.url || 'undefined' === typeof pllFilteredRoutes ) {
			return next( options );
		}

		return next( filterPathMiddleware( options, pllFilteredRoutes, addLanguageParameter ) );
	}
);

/**
 * Gets the language of the currently edited post, fallback to default language if none is found.
 *
 * @since 2.5
 *
 * @return {Element.value}
 */
function getCurrentLanguage() {
	const lang = document.querySelector( '[name=post_lang_choice]' );

	if ( null === lang ) {
		return pllDefaultLanguage;
	}

	return lang.value;
}

/**
 * Adds language parameter according to the current one (query string for GET, body for PUT and POST).
 *
 * @since 3.5
 *
 * @param {APIFetchOptions} options
 * @returns {APIFetchOptions}
 */
function addLanguageParameter( options ) {
	if ( 'undefined' === typeof options.data || null === options.data ) {
		// GET
		options.path += ( ( options.path.indexOf( '?' ) >= 0 ) ? '&lang=' : '?lang=' ) + getCurrentLanguage();
	} else {
		// PUT, POST
		options.data.lang = getCurrentLanguage();
	}

	return options;
}

/**
 * Handles internals of the metabox:
 * Language select, autocomplete input field.
 *
 * @since 1.5
 *
 * Save post after lang choice is done and redirect to the same page for refreshing all the data.
 *
 * @since 2.5
 *
 * Link post saving after refreshing the metabox.
 *
 * @since 3.0
 */
jQuery(
	function ( $ ) {
		// Initialize current language to be able to compare if it changes.
		initializeLanguageOldValue();


		// Ajax for changing the post's language in the languages metabox
		$( '.post_lang_choice' ).on(
			'change',
			function ( event ) {
				const { select, dispatch, subscribe } = wp.data;
				const emptyPost                       = isEmptyPost();
				const { addQueryArgs }                = wp.url;

				// Initialize the confirmation dialog box.
				const confirmationModal            = initializeConfirmationModal();
				const { dialogContainer : dialog } = confirmationModal;
				let { dialogResult }               = confirmationModal;
				const selectedOption               = event.target; // The selected option in the dropdown list.

				// Specific case for empty posts.
				// Place at the beginning because window.location change triggers automatically page reloading.
				if ( location.pathname.match( /post-new.php/gi ) && emptyPost ) {
					reloadPageForEmptyPost( selectedOption.value );
				}

				// Otherwise send an ajax request to refresh the legacy metabox and set the post language with the new language.
				// It needs a confirmation of the user before changing the language.
				// Need to wait the ajax response before triggering the block editor post save action.
				if ( $( this ).data( 'old-value' ) !== selectedOption.value && ! emptyPost ) {
					dialog.dialog( 'open' );
				} else {
					// Update the old language with the new one to be able to compare it in the next change.
					// Because the page isn't reloaded in this case.
					initializeLanguageOldValue();
					dialogResult = Promise.resolve();
				}

				dialogResult.then(
					() => {
						let data = { // phpcs:ignore PEAR.Functions.FunctionCallSignature.Indent
							action:     'post_lang_choice',
							lang:       selectedOption.value,
							post_type:  $( '#post_type' ).val(),
							post_id:    $( '#post_ID' ).val(),
							_pll_nonce: $( '#_pll_nonce' ).val()
						}

						// Update post language in database as soon as possible.
						// Because, in addition of the block editor save process, the legacy metabox uses a post.php process to update the language and is too late compared to the page reload.
						$.post(
							ajaxurl,
							data,
							function () {
								blockEditorSavePostAndReloadPage();
							}
						);
					},
					() => {} // Do nothing when promise is rejected by clicking the Cancel dialog button.
				);

				function isEmptyPost() {
					const editor = select( 'core/editor' );

					return ! editor.getEditedPostAttribute( 'title' )?.trim() && ! editor.getEditedPostContent() && ! editor.getEditedPostAttribute( 'excerpt' )?.trim();
				}

				/**
				 * Reload the block editor page for empty posts.
				 *
				 * @param {string} lang The target language code.
				 */
				function reloadPageForEmptyPost( lang ) {
					// Change the new_lang parameter with the new language value for reloading the page
					// WPCS location.search is never written in the page, just used to reload page with the right value of new_lang
					// new_lang input is controlled server side in PHP. The value come from the dropdown list of language returned and escaped server side.
					// Notice that window.location changing triggers automatically page reloading.
					if ( -1 != location.search.indexOf( 'new_lang' ) ) {
						// use regexp non capturing group to replace new_lang parameter no matter where it is and capture other parameters which can be behind it
						window.location.search = window.location.search.replace( /(?:new_lang=[^&]*)(&)?(.*)/, 'new_lang=' + lang + '$1$2' ); // phpcs:ignore WordPressVIPMinimum.JS.Window.location, WordPressVIPMinimum.JS.Window.VarAssignment
					} else {
						window.location.search = window.location.search + ( ( -1 != window.location.search.indexOf( '?' ) ) ? '&' : '?' ) + 'new_lang=' + lang; // phpcs:ignore WordPressVIPMinimum.JS.Window.location, WordPressVIPMinimum.JS.Window.VarAssignment
					}
				};

				/**
				 * Triggers block editor post save and reload the block editor page when everything is ok.
				 */
				function blockEditorSavePostAndReloadPage() {

					let unsubscribe    = null;
					const previousPost = select( 'core/editor').getCurrentPost();

					// Listen if the savePost is completely done by subscribing to its events.
					const savePostIsDone = new Promise(
						function ( resolve, reject ) {
							unsubscribe = subscribe(
								function () {
									const post                 = select( 'core/editor').getCurrentPost();
									const { id, status, type } = post;
									const error                = select( 'core' )
										.getLastEntitySaveError(
											'postType',
											type,
											id
										);

									if ( error ) {
										reject();
									}

									if ( previousPost.modified !== post.modified ) {

										if ( location.pathname.match( /post-new.php/gi ) && status !== 'auto-draft' && id ) {
											window.history.replaceState(
												{ id },
												'Post ' + id,
												addQueryArgs( 'post.php', { post: id, action: 'edit' } )
											);
										}
										resolve();
									}
								}
							);
						}
					);

					// Triggers the post save.
					dispatch( 'core/editor' ).savePost();

					// Process
					savePostIsDone.then(
						function () {
							// If the post is well saved, we can reload the page
							window.location.reload();
						},
						function () {
							// If the post save failed
							unsubscribe();
						}
					).catch(
						function () {
							// If an exception is thrown
							unsubscribe();
						}
					);
				};
			}
		);

		initMetaboxAutoComplete();
	}
);
