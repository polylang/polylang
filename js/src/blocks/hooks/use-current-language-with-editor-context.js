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
 * Wraps `useCurrentLanguage` from `@wpsyntex/polylang-react-library`.
 *
 * The widgets editor does not load `@wordpress/editor`, so the library hook
 * cannot run there. In that context, fall back to the default language slug
 * injected by PHP (same as `useCurrentLanguage` when no post language exists).
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

			if ( select( 'core/edit-widgets' ) ) {
				return findLanguageBySlug(
					languages,
					pllEditorCurrentLanguageSlug // eslint-disable-line no-undef
				);
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
