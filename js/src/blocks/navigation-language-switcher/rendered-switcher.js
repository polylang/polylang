/**
 * WordPress dependencies
 */
import { Fragment, useContext } from '@wordpress/element';

/**
 * External dependencies
 */
import {
	SubmenuIcon,
	useCurrentLanguage,
	useCuratedLanguages,
} from '@wpsyntex/polylang-react-library';

/**
 * Internal dependencies
 */
import { LanguagesContext } from '../languages-context';
import { SwitcherLanguageLink } from '../components/switcher-language-link';
import { getLabelSpacing } from '../components/switcher-utils';

/**
 * Rendered switcher component.
 *
 * @param {Object} props            The component props.
 * @param {Object} props.attributes The block attributes.
 * @param {Object} props.context    The block context.
 * @return {ReactElement} The rendered switcher.
 */
export const RenderedSwitcher = ( { attributes, context } ) => {
	const {
		layout,
		show_labels,
		show_flags,
		flag_border_radius,
		flag_width,
		flag_label_spacing,
	} = attributes;
	const { showSubmenuIcon, openSubmenusOnClick } = context;
	const { languages } = useContext( LanguagesContext );
	const currentLanguage = useCurrentLanguage( languages );
	const isDropdown = layout === 'dropdown';
	const languagesToRender = useCuratedLanguages(
		languages,
		currentLanguage,
		isDropdown
	);
	const labelSpacing = getLabelSpacing( show_labels, flag_label_spacing );
	const withSubmenuIcon =
		isDropdown && ( showSubmenuIcon || openSubmenusOnClick );
	const linkProps = {
		showLabels: show_labels,
		showFlags: show_flags,
		flagBorderRadius: flag_border_radius,
		flagWidth: flag_width,
		labelSpacing,
	};

	return (
		<>
			{ languagesToRender.map( ( language ) => {
				return (
					<Fragment key={ language.slug }>
						<SwitcherLanguageLink
							language={ language }
							{ ...linkProps }
						/>
						{ withSubmenuIcon && (
							<span className="wp-block-navigation__submenu-icon">
								<SubmenuIcon />
							</span>
						) }
					</Fragment>
				);
			} ) }
		</>
	);
};
