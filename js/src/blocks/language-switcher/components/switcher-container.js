/**
 * WordPress dependencies
 */
import { useContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { LanguagesContext } from '../../languages-context';
import { SwitcherUI } from './switcher-ui';
import { useCurrentLanguage } from '../../hooks/use-current-language';
import { useCuratedLanguages } from '../../hooks/use-curated-languages';

/**
 * Switcher container component.
 *
 * @param {Object} props            The component props.
 * @param {Object} props.attributes The block attributes.
 * @return {ReactElement} The Switcher component.
 */
export const SwitcherContainer = ( { attributes } ) => {
	const { dropdown, show_flags, show_names } = attributes;
	const { languages } = useContext( LanguagesContext );
	const currentLanguage = useCurrentLanguage( languages );
	const curatedLanguages = useCuratedLanguages(
		languages,
		currentLanguage,
		dropdown
	);

	return (
		<SwitcherUI
			languages={ curatedLanguages }
			showFlags={ show_flags }
			showNames={ show_names }
			isDropdown={ dropdown }
		/>
	);
};
