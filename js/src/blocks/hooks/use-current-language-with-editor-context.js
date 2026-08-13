/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';

/**
 * Finds a language by slug.
 *
 * @param {Array}  languages The languages list.
 * @param {string} slug      The language slug.
 * @return {Object|null} The language, `null` if not found.
 */
const findLanguageBySlug = ( languages, slug ) => {
	if ( ! languages || ! slug ) {
		return null;
	}

	const currentLanguage = languages.find( ( language ) => {
		return language.slug === slug;
	} );

	return currentLanguage ? currentLanguage : null;
};

/**
 * Resolves the current language in block editor contexts.
 *
 * The widgets editor does not load `@wordpress/editor`, so the `core/editor`
 * store may be unavailable. In that context, fall back to the current language
 * slug injected by PHP (default language when none is set).
 *
 * @param {Array} languages The languages list.
 * @return {Object|null} The current language, `null` if not found.
 */
export const useCurrentLanguageWithEditorContext = ( languages ) => {
	return useSelect(
		( select ) => {
			if ( ! languages ) {
				return null;
			}

			const editorStore = select( 'core/editor' );

			if ( ! editorStore ) {
				return findLanguageBySlug(
					languages,
					pllEditorCurrentLanguageSlug // eslint-disable-line no-undef
				);
			}

			const currentPost = editorStore.getCurrentPost();

			if ( ! currentPost ) {
				return null;
			}

			const currentLanguageSlug =
				currentPost.lang ?? pllEditorCurrentLanguageSlug; // eslint-disable-line no-undef

			return findLanguageBySlug( languages, currentLanguageSlug );
		},
		[ languages ]
	);
};
