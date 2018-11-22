/**
 * Filter REST API requests to add the language in the request
 *
 * @since 2.4
 */
wp.apiFetch.use( ( options, next ) => {
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
 * @since 2.4
 *
 * @return {Element.value}
 */
function getCurrentLanguage() {
	return document.querySelector( '[name=post_lang_choice]' ).value;
}

/**
 * save post after lang choice is done and redirect to the same page for refreshing all the data
 *
 * @since 2.4
 */
jQuery( document ).ready(function( $ ) {
	// savePost after changing the post's language and reload page for refreshing post translated data
	$( '.post_lang_choice' ).change(function() {
		const select = wp.data.select;
		const dispatch = wp.data.dispatch;
		const subscribe = wp.data.subscribe;

		let unsubscribe = null;

		// listen if the savePost is done
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

		dispatch( 'core/editor' ).savePost();

		savePostIsDone
			.then( function() {
				// if the post is well saved, we can reload the page
				unsubscribe();
				window.location.reload();
			} )
			.catch( function() {
				unsubscribe();
			} );
	} );
} );
