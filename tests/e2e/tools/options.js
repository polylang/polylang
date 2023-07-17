/**
 * Returns all plugin options.
 *
 * @param {RequestUtils} requestUtils Gutenberg request utils object.
 * @returns {Promise} Request promise with the options values when fullfiled.
 */
export function getOptions ( requestUtils ) {
	return requestUtils.rest(
		{
			path: '/pll-test/v1/options',
			method: 'GET'
		}
	);
}

/**
 * Updates a plugin option.
 *
 * @param {RequestUtils} requestUtils Gutenberg request utils object.
 * @param {string} optionKey Option key.
 * @param {string} optionValue Option value.
 * @returns {Promise} Request promise.
 */
export function setOption ( requestUtils, optionKey, optionValue ) {
	return requestUtils.rest(
		{
			path: '/pll-test/v1/options',
			method: 'PUT',
			params: {
				key: optionKey,
				value: optionValue,
			}
		}
	);
}

/**
 * Deletes all plugin options and roll back to default values.
 *
 * @param {RequestUtils} requestUtils Gutenberg request utils object.
 * @returns {Promise} Request promise.
 */
export function deleteOptions ( requestUtils ) {
	return requestUtils.rest(
		{
			path: '/pll-test/v1/options',
			method: 'DELETE'
		}
	);
}
