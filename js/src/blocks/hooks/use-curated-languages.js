/**
 * WordPress dependencies
 */
import { useMemo } from '@wordpress/element';

/**
 * Hook to curate the languages.
 * Returns an array of languages ensuring that the current language is always the first one.
 *
 * @param {Object[]} languages       The languages.
 * @param {Object}   currentLanguage The current language.
 * @param {boolean}  reduceToOneItem Whether to reduce the languages to one item.
 * @return {Object[]} The curated languages.
 */
export const useCuratedLanguages = (
	languages,
	currentLanguage,
	reduceToOneItem
) => {
	const curatedLanguages = useMemo( () => {
		if ( ! currentLanguage ) {
			return [];
		}

		if ( reduceToOneItem ) {
			return [ currentLanguage ];
		}

		return [
			currentLanguage,
			...languages.filter( ( language ) => {
				return language.slug !== currentLanguage.slug;
			} ),
		];
	}, [ languages, currentLanguage, reduceToOneItem ] );

	return curatedLanguages;
};
