/**
 * WordPress dependencies
 */
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect } from '@wordpress/element';

/**
 * Custom hook to get the languages list.
 *
 * @return {Array|null} The languages list, `null` if not loaded yet.
 */
export const useLanguagesList = () => {
	const [ languages, setLanguages ] = useState( null );
	useEffect( () => {
		apiFetch( {
			path: '/pll/v1/languages',
			method: 'GET',
		} ).then( ( response ) => setLanguages( response ) );
	}, [] );

	return languages;
};
