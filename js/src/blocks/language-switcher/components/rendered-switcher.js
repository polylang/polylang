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
import { LanguagesContext } from '../../languages-context';
import { SwitcherLinkElement } from './switcher-link-element';

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
	} = attributes;
	const { languages } = useContext( LanguagesContext );
	const currentLanguage = useCurrentLanguage( languages );
	const curatedLanguages = useCuratedLanguages( languages, currentLanguage );
	const topLevelLanguages = useCuratedLanguages(
		languages,
		currentLanguage,
		true
	);
	const switcherClassName = getSwitcherClassName(
		layout,
		show_flags,
		flag_aspect_ratio
	);
	const labelSpacing = '' !== show_labels ? flag_label_spacing : undefined;

	if ( layout === 'dropdown' ) {
		const currentLanguageItem = topLevelLanguages[ 0 ];

		return (
			<nav className={ switcherClassName }>
				{ currentLanguageItem && (
					<SwitcherLinkElement
						label={ getLabel( currentLanguageItem, show_labels ) }
						flag={ getFlag(
							currentLanguageItem,
							show_flags,
							flag_border_radius,
							flag_width
						) }
						labelSpacing={ labelSpacing }
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
			<select className={ switcherClassName }>
				{ curatedLanguages.map( ( language ) => {
					return (
						<option key={ language.slug } value={ language.slug }>
							{ getLabel( language, show_labels ) }
						</option>
					);
				} ) }
			</select>
		);
	}

	return (
		<nav className={ switcherClassName }>
			<ul>
				{ curatedLanguages.map( ( language ) => {
					return (
						<li key={ language.slug }>
							<SwitcherLinkElement
								label={ getLabel( language, show_labels ) }
								flag={ getFlag(
									language,
									show_flags,
									flag_border_radius,
									flag_width
								) }
								labelSpacing={ labelSpacing }
							/>
						</li>
					);
				} ) }
			</ul>
		</nav>
	);
};

/**
 * Returns the switcher wrapper class names.
 *
 * @param {string}  layout          The switcher layout.
 * @param {boolean} showFlags       Whether flags are displayed.
 * @param {string}  flagAspectRatio The flag aspect ratio.
 * @return {string} The switcher class names.
 */
const getSwitcherClassName = ( layout, showFlags, flagAspectRatio ) => {
	const classes = [ 'pll-switcher', `pll-layout-${ layout }` ];

	if ( showFlags ) {
		classes.push(
			`pll-aspect-ratio-${ flagAspectRatio.replace( ':', '' ) }`
		);
	}

	return classes.join( ' ' );
};

/**
 * Get the label for a language.
 *
 * @param {Object} language   The language object.
 * @param {string} showLabels Whether to show the names. Can be 'names', 'codes' or ''.
 * @return {string} The label for the language.
 */
const getLabel = ( language, showLabels ) => {
	let label = '';
	if ( showLabels === 'names' ) {
		label = language.name;
	} else if ( showLabels === 'codes' ) {
		label = language.slug.toUpperCase();
	}

	return label;
};

/**
 * Strip fixed dimensions from flag HTML markup.
 *
 * @param {string} html The flag HTML markup.
 * @return {string} The flag HTML without fixed dimensions.
 */
const stripFlagDimensions = ( html ) => {
	return html
		.replace( /\s(?:width|height)=["'][^"']*["']/gi, '' )
		.replace( /\sstyle=["'][^"']*["']/gi, '' );
};

/**
 * Get the flag for a language.
 *
 * @param {Object}  language         The language object.
 * @param {boolean} showFlags        Whether to show the flags.
 * @param {number}  flagBorderRadius The border radius of the flags.
 * @param {string}  flagWidth        The width of the flags as a CSS length.
 * @return {ReactElement|null} The flag for the language.
 */
const getFlag = ( language, showFlags, flagBorderRadius, flagWidth ) => {
	if ( ! showFlags || ! language.flag ) {
		return null;
	}

	return (
		<span
			className="pll-switcher-flag"
			style={ {
				'--pll-flag-border-radius': flagBorderRadius,
				'--pll-flag-width': flagWidth,
			} }
			/* eslint-disable-next-line prettier/prettier */
			dangerouslySetInnerHTML={ { // phpcs:ignore WordPressVIPMinimum.JS.DangerouslySetInnerHTML.Found
				__html: stripFlagDimensions( language.flag ),
			} }
		/>
	);
};
