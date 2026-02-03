/**
 * WordPress dependencies
 */
import { useContext } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { LanguagesContext } from '../../languages-context';
import { SwitcherUI } from './switcher-ui';
import { useCurrentLanguage, useCuratedLanguages } from '@wpsyntex/polylang-react-library';

/**
 * Switcher container component.
 *
 * @param {Object} props            The component props.
 * @param {Object} props.attributes The block attributes.
 * @param {Object} props.context    The block context.
 * @return {ReactElement} The Switcher component.
 */
export const NavigationSwitcherContainer = ( { attributes, context } ) => {
	const { dropdown, show_flags, show_names } = attributes;
	const { showSubmenuIcon, openSubmenusOnClick } = context;
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
			showFlags={ Boolean( show_flags ) }
			showNames={ Boolean( show_names ) }
			withSubmenuIcon={ Boolean(
				( showSubmenuIcon || openSubmenusOnClick ) && dropdown
			) }
		/>
	);
};
