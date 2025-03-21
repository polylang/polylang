/**
 * @package Polylang
 */

/**
 * Filters requests for translatable entities.
 * This logic is shared across all Polylang plugins.
 *
 * @since 3.5
 *
 * @param {APIFetchOptions} options
 * @param {Array} filteredRoutes
 * @param {CallableFunction} filter
 * @returns {APIFetchOptions}
 */
const filterPathMiddleware = ( options, filteredRoutes, filter ) => {
	const cleanPath = options.path.split( '?' )[0].replace(/^\/+|\/+$/g, ''); // Get path without query parameters and trim '/'.

	return Object.values( filteredRoutes ).find( ( path ) => cleanPath === path ) ? filter( options ) : options;
}

export default filterPathMiddleware;
