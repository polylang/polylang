/**
 * @package Polylang
 */

import {
	initializeLanguageOldValue,
	initializeConfimationModal
} from './lib/confirmation-modal';

import {
	initMetaboxAutoComplete,
} from './lib/metabox-autocomplete';

/**
 * Filter REST API requests to add the language in the request
 *
 * @since 2.5
 */
wp.apiFetch.use(
	function( options, next ) {
		// If options.url is defined, this is not a REST request but a direct call to post.php for legacy metaboxes.
		if ( 'undefined' === typeof options.url ) {
			if ( 'undefined' === typeof options.data || null === options.data ) {
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
	function( $ ) {
		// Initialize current language to be able to compare if it changes.
		initializeLanguageOldValue();


		// Ajax for changing the post's language in the languages metabox
		$( '.post_lang_choice' ).on(
			'change',
			function( event ) {
				const select = wp.data.select;
				const dispatch = wp.data.dispatch;
				const subscribe = wp.data.subscribe;
				const emptyPost = isEmptyPost();

				// Initialize the confirmation dialog box.
				const confirmationModal = initializeConfimationModal();
				const { dialogContainer : dialog } = confirmationModal;
				let { dialogResult } = confirmationModal;
				// The selected option in the dropdown list.
				const selectedOption = event.target;

				// Specific case for empty posts.
				// Place at the beginning because window.location changing triggers automatically page reloading.
				if ( location.pathname.match( /post-new.php/gi ) && emptyPost ) {
					reloadPageForEmptyPost( selectedOption.value );
				}

				// Otherwise send an ajax request to refresh the legacy metabox and set the post language with the new language.
				// It needs a confirmation of the user before changing the language.
				// Need to wait the ajax response before triggering the block editor post save action.
				if ( $( this ).data( 'old-value' ) !== selectedOption.value && ! emptyPost ) {
					dialog.dialog( 'open' );
				} else {
					// Update the old language with the new one to be able to compare it in the next changing.
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
							function() {
								blockEditorSavePostAndReloadPage();
							}
						);
					},
					() => {} // Do nothing when promise is rejected by clicking the Cancel dialog button.
				);

				function isEmptyPost() {
					const editor = wp.data.select( 'core/editor' );
					const title = editor.getEditedPostAttribute( 'title' ).trim();
					const content = editor.getEditedPostAttribute( 'content' ).trim();
					const excerpt = editor.getEditedPostAttribute( 'excerpt' ).trim();

					return ! title && ! content && ! excerpt;
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

					let unsubscribe = null;

					// Listen if the savePost is completely done by subscribing to its events.
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

					// Triggers the post save.
					dispatch( 'core/editor' ).savePost();

					// Process
					savePostIsDone.then(
						function() {
							// If the post is well saved, we can reload the page
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
				};
			}
		);

		initMetaboxAutoComplete();
	}
);
