/**
 * Filter REST API requests to add the language in the request
 *
 * @since 2.5
 */
wp.apiFetch.use( function( options, next ) {
	// If options.url is defined, this is not a REST request but a direct call to post.php for legacy metaboxes.
	if ( 'undefined' === typeof options.url ) {
		if ( 'undefined' === typeof options.data ) {
			// GET
			options.path += ( ( options.path.indexOf ( '?' ) >= 0 ) ? '&lang=' : '?lang=' ) + getCurrentLanguage();
		} else {
			// PUT, POST
			options.data.lang = getCurrentLanguage();
		}
	}
	return next( options );
} );

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
 * save post after lang choice is done and redirect to the same page for refreshing all the data
 *
 * @since 2.5
 */
jQuery( document ).ready(function( $ ) {
	// savePost after changing the post's language and reload page for refreshing post translated data
	$( '.post_lang_choice' ).change(function() {
		const select = wp.data.select;
		const dispatch = wp.data.dispatch;
		const subscribe = wp.data.subscribe;

		let unsubscribe = null;

		// Listen if the savePost is done
		const savePostIsDone = new Promise( function( resolve, reject ) {
			unsubscribe = subscribe( function() {
				const isSavePostSucceeded = select('core/editor').didPostSaveRequestSucceed();
				const isSavePostFailed = select('core/editor').didPostSaveRequestFail();
				if ( isSavePostSucceeded || isSavePostFailed ) {
					if ( isSavePostFailed ) {
						reject();
					} else {
						resolve();
					}
				}
			} );
		});

		// Specific case for empty posts
		if ( location.pathname.match( /post-new.php/gi ) ) {
			const title = select('core/editor').getEditedPostAttribute('title');
			const content = select('core/editor').getEditedPostAttribute('content');
			const excerpt = select('core/editor').getEditedPostAttribute('excerpt');
			if ( '' === title && '' === content && '' === excerpt ) {
				// Change the new_lang parameter with the new language value for reloading the page
				if ( -1 != location.search.indexOf( 'new_lang' ) ) {
					window.location.search = window.location.search.replace( /(?:new_lang=[^&]*)(&)?(.*)/, 'new_lang=' + this.value + '$1$2' );;
				} else {
					window.location.search = window.location.search + ( ( -1 != window.location.search.indexOf( '?' ) ) ? '&' : '?' ) + 'new_lang=' + this.value;
				}
			}
		}

		// For empty posts savePost does nothing
		dispatch( 'core/editor' ).savePost();

		savePostIsDone
			.then( function() {
				// If the post is well saved, we can reload the page
				unsubscribe();
				window.location.reload();
			} )
			.catch( function() {
				unsubscribe();
			} );
	} );
} );
