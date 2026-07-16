/**
 * WordPress dependencies
 */
import { useContext } from '@wordpress/element';

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
import {
	getLabel,
	getLabelSpacing,
	getSwitcherClassName,
} from '../components/switcher-utils';

/**
 * Rendered switcher component.
 *
 * @param {Object} props            The component props.
 * @param {Object} props.attributes The block attributes.
 * @return {ReactElement} The rendered switcher.
 */
export const RenderedSwitcher = ( { attributes } ) => {
	const {
		layout,
		show_labels,
		show_flags,
		flag_aspect_ratio,
		flag_border_radius,
		flag_width,
		flag_label_spacing,
		style,
	} = attributes;
	const { languages } = useContext( LanguagesContext );
	const currentLanguage = useCurrentLanguage( languages );
	const curatedLanguages = useCuratedLanguages(
		languages,
		currentLanguage,
		false
	);
	const alignment = style?.typography?.textAlign;
	const switcherClassName = getSwitcherClassName(
		layout,
		show_flags,
		flag_aspect_ratio,
		alignment
	);
	const labelSpacing = getLabelSpacing( show_labels, flag_label_spacing );
	const linkProps = {
		showLabels: show_labels,
		showFlags: show_flags,
		flagBorderRadius: flag_border_radius,
		flagWidth: flag_width,
		labelSpacing,
	};

	if ( layout === 'dropdown' ) {
		const currentLanguageItem = curatedLanguages[ 0 ];

		return (
			<nav className={ switcherClassName }>
				{ currentLanguageItem && (
					<SwitcherLanguageLink
						language={ currentLanguageItem }
						{ ...linkProps }
					/>
				) }
				<button className="pll-submenu-toggle">
					<SubmenuIcon />
				</button>
			</nav>
		);
	}

	if ( layout === 'select' ) {
		return (
			<div className={ switcherClassName }>
				<select className="pll-switcher-select">
					{ curatedLanguages.map( ( language ) => {
						return (
							<option key={ language.slug } value={ language.slug }>
								{ getLabel( language, show_labels ) }
							</option>
						);
					} ) }
				</select>
			</div>
		);
	}

	return (
		<nav className={ switcherClassName }>
			<ul>
				{ curatedLanguages.map( ( language ) => {
					return (
						<li key={ language.slug }>
							<SwitcherLanguageLink
								language={ language }
								{ ...linkProps }
							/>
						</li>
					);
				} ) }
			</ul>
		</nav>
	);
};
