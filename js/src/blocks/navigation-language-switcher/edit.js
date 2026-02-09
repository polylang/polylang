/**
 * Edit callback for navigation language switcher block.
 */

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Disabled, PanelBody } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { createLanguageSwitcherEdit } from '../language-switcher-edit';
import { LanguagesContext } from '../languages-context';
import { NavigationSwitcherContainer } from './components/navigation-switcher-container';
import { useLanguagesList } from '@wpsyntex/polylang-react-library';

/**
 * Edit callback for navigation language switcher block.
 *
 * @param {Object} props Block properties.
 * @return {ReactElement} The block content and controls.
 */
export const Edit = ( props ) => {
	const { dropdown } = props.attributes;
	const languages = useLanguagesList();
	const {
		ToggleControlDropdown,
		ToggleControlShowNames,
		ToggleControlShowFlags,
		ToggleControlForceHome,
		ToggleControlHideCurrent,
		ToggleControlHideIfNoTranslations,
	} = createLanguageSwitcherEdit( props );

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody
					title={ __( 'Language switcher settings', 'polylang' ) }
				>
					<ToggleControlDropdown />
					<ToggleControlShowNames />
					<ToggleControlShowFlags />
					<ToggleControlForceHome />
					{ ! dropdown && <ToggleControlHideCurrent /> }
					<ToggleControlHideIfNoTranslations />
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<LanguagesContext.Provider value={ { languages } }>
					<NavigationSwitcherContainer
						attributes={ props.attributes }
						context={ props.context }
					/>
				</LanguagesContext.Provider>
			</Disabled>
		</div>
	);
};
