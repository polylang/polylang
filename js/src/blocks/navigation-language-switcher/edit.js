/**
 * Edit callback for navigation language switcher block.
 */

/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { SwitcherControls } from '../components/switcher-controls';
import { LanguagesContext } from '../languages-context';
import { RenderedSwitcher } from './rendered-switcher';
import { useLanguagesList } from '@wpsyntex/polylang-react-library';

/**
 * Edit callback for navigation language switcher block.
 *
 * @param {Object}   props               Block properties.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Object}   props.context       Block context.
 * @param {Function} props.setAttributes Function to set block attributes.
 * @return {ReactElement} The block content and controls.
 */
export const Edit = ( { attributes, context, setAttributes } ) => {
	const languages = useLanguagesList();
	const { show_flags, flag_aspect_ratio } = attributes;
	const aspectRatioClass =
		show_flags && flag_aspect_ratio
			? `pll-aspect-ratio-${ flag_aspect_ratio.replace( ':', '' ) }`
			: '';

	return (
		<div
			{ ...useBlockProps( {
				className: aspectRatioClass || undefined,
			} ) }
		>
			<SwitcherControls
				attributes={ attributes }
				setAttributes={ setAttributes }
				layoutOptions={ [ 'horizontal', 'dropdown' ] }
				showToolbar={ false }
				hideCurrentInDropdown={ true }
			/>
			<Disabled>
				<LanguagesContext.Provider value={ { languages } }>
					<RenderedSwitcher
						attributes={ attributes }
						context={ context }
					/>
				</LanguagesContext.Provider>
			</Disabled>
		</div>
	);
};
