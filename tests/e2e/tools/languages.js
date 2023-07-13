/**
 * Creates a language.
 *
 * @param {RequestUtils} requestUtils Gutenberg request utils object.
 * @returns {Promise} Request promise.
 */
export function deleteAllLanguages ( requestUtils ) {
	return requestUtils.rest(
		{
			path: '/pll-test/v1/languages',
			method: 'DELETE',
		}
	);
}

/**
 * Deletes all languages.
 *
 * @param {RequestUtils} requestUtils Gutenberg request utils object.
 * @param {string} locale Language locale to create.
 * @returns {Promise} Request promise.
 */
export function createLanguage( requestUtils, locale ) {
	return requestUtils.rest(
		{
			path: '/pll-test/v1/languages',
			method: 'POST',
			params: {
				locale: locale,
			}
		}
	);
}

/**
 * Returns languages list.
 *
 * @param {RequestUtils} requestUtils Gutenberg request utils object.
 * @returns {Promise} Request promise.
 */
export function getAllLanguages( requestUtils ) {
	return requestUtils.rest(
		{
			path: '/pll-test/v1/languages',
			method: 'GET',
		}
	);
}
