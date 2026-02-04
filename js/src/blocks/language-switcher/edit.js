/**
 * Edit callback for language switcher block.
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
import { SwitcherContainer } from './components/switcher-container';
import { LanguagesContext } from '../languages-context';
import { useLanguagesList } from '@wpsyntex/polylang-react-library';

/**
 * Edit callback for language switcher block.
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
					{ ! dropdown && <ToggleControlShowNames /> }
					{ ! dropdown && <ToggleControlShowFlags /> }
					<ToggleControlForceHome />
					{ ! dropdown && <ToggleControlHideCurrent /> }
					<ToggleControlHideIfNoTranslations />
				</PanelBody>
			</InspectorControls>
			<Disabled>
				<LanguagesContext.Provider value={ { languages } }>
					<SwitcherContainer attributes={ props.attributes } />
				</LanguagesContext.Provider>
			</Disabled>
		</div>
	);
};
