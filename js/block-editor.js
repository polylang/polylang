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
