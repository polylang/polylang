/**
 * WordPress dependencies.
 */
import { SelectControl } from '@wordpress/components';
import { useMemo } from '@wordpress/element';

/**
 * Internal dependencies.
 */
import LanguageFlag from './language-flag';

/**
 * Displays a dropdown to select a language.
 *
 * @since 3.1
 *
 * @param {Object}   props                  LanguageDropdown props.
 * @param {Function} props.handleChange     Callback to be executed when language changes.
 * @param {Object}   props.languages        An iterable object containing languages objects.
 * @param {Object}   props.selectedLanguage An object representing a Polylang Language. Default to null.
 * @param {string}   props.defaultValue     Value to be selected if the selected language is not provided. Default to an empty string.
 *
 * @return {Object} A dropdown selector for languages.
 */
function LanguageDropdown( {
	handleChange,
	languages,
	selectedLanguage = null,
	defaultValue = '',
} ) {
	const selectedLanguageSlug = selectedLanguage?.slug
		? selectedLanguage.slug
		: defaultValue;

	const normalizedLanguagesForSelectControl = useMemo( () => {
		return Array.from( languages.values() ).map( ( { slug, name } ) => ( {
			value: slug,
			label: name,
		} ) );
	}, [ languages ] );

	return (
		<div id="pll-language-select-control">
			<LanguageFlag language={ selectedLanguage } />
			<SelectControl
				value={ selectedLanguageSlug }
				onChange={ ( newLangSlug ) => handleChange( newLangSlug ) }
				options={ normalizedLanguagesForSelectControl }
				id="pll_post_lang_choice"
				name="pll_post_lang_choice"
				className="post_lang_choice"
				__nextHasNoMarginBottom
				__next40pxDefaultSize
			/>
		</div>
	);
}

export { LanguageDropdown };
