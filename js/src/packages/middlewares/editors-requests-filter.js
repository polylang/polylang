/**
 * WordPress dependencies.
 */
import apiFetch from '@wordpress/api-fetch';

/*
 * Internal dependencies.
 */
import filterPathMiddleware from './filter-path';

/**
 * Safely filters requests for translatable entities in block editor type screens.
 * Ensures that `pllFilteredRoutes` has been well defined on server side and
 * that the filtered request is a REST one.
 *
 * @param {function(APIFetchOptions):APIFetchOptions} filterCallback
 */
const editorsRequestsFilter = ( filterCallback ) => {
	apiFetch.use( ( options, next ) => {
		/*
		 * If options.url is defined, this is not a REST request but a direct call to post.php for legacy metaboxes.
		 * If `filteredRoutes` is not defined, return early.
		 */
		if (
			'undefined' !== typeof options.url ||
			'undefined' === typeof pllFilteredRoutes
		) {
			return next( options );
		}

		return next(
			filterPathMiddleware( options, pllFilteredRoutes, filterCallback )
		);
	} );
};

export default editorsRequestsFilter;
