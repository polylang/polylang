/**
 * Edit callback for language switcher block.
 */

/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { RenderedSwitcher } from './rendered-switcher';
import { LanguagesContext } from '../languages-context';
import { useLanguagesList } from '@wpsyntex/polylang-react-library';
import { SwitcherControls } from '../components/switcher-controls';

/**
 * Edit callback for language switcher block.
 *
 * @param {Object}   props               Block properties.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to set block attributes.
 * @return {ReactElement} The block content and controls.
 */
export const Edit = ( { attributes, setAttributes } ) => {
	const languages = useLanguagesList();

	return (
		<div { ...useBlockProps() }>
			<SwitcherControls
				attributes={ attributes }
				setAttributes={ setAttributes }
			/>
			<Disabled>
				<LanguagesContext.Provider value={ { languages } }>
					<RenderedSwitcher attributes={ attributes } />
				</LanguagesContext.Provider>
			</Disabled>
		</div>
	);
};
