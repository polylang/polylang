/**
 * @package Polylang
 */

/**
 * Adds data to all ajax requests made with jQuery.
 *
 * @since 3.7
 *
 * @param {Object} data The data to add.
 * @returns {void}
 */
export function ajaxFilter( data ) {
	if ( 'undefined' === typeof jQuery || ! data ) {
		return;
	}

	const dataStr = jQuery.param( data );

	jQuery.ajaxPrefilter( function ( options ) {
		if ( -1 === options.url.indexOf( ajaxurl ) && -1 === ajaxurl.indexOf( options.url ) ) {
			return;
		}

		if (
			'undefined' === typeof options.data ||
			null === options.data ||
			'string' === typeof options.data && '' === options.data.trim()
		) {
			// An empty string or null/undefined.
			options.data = dataStr;
		} else if ( 'string' === typeof options.data ) {
			// A non-empty string: can be a JSON string or a query string.
			try {
				options.data = JSON.stringify( Object.assign( JSON.parse( options.data ), data ) );
			} catch ( exception ) {
				// A non-empty non-JSON string is considered a query string.
				options.data = `${ options.data }&${ dataStr }`;
			}
		} else if ( jQuery.isPlainObject( options.data ) ) {
			// An object.
			options.data = Object.assign( options.data, data );
		}
	} );
}
