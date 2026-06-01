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
		flag_margin_right,
	} = attributes;
	const { languages } = useContext( LanguagesContext );
	const currentLanguage = useCurrentLanguage( languages );
	const curatedLanguages = useCuratedLanguages( languages, currentLanguage );

	if ( layout === 'dropdown' ) {
		return (
			<nav className={ `pll-switcher pll-layout-${ layout }` }>
				<ul>
					{ curatedLanguages.map( ( language, index ) => {
						const isTopLevel = 0 === index;

						return (
							<li key={ language.slug }>
								<SwitcherLinkElement
									label={ getLabel( language, show_labels ) }
									flag={ getFlag(
										language,
										show_flags,
										flag_aspect_ratio,
										flag_border_radius,
										flag_width,
										flag_margin_right
									) }
								/>
								{ isTopLevel && (
									<span className="wp-block-navigation__submenu-icon">
										<SubmenuIcon />
									</span>
								) }
							</li>
						);
					} ) }
				</ul>
			</nav>
		);
	}

	if ( layout === 'select' ) {
		return (
			<select className={ `pll-switcher pll-layout-${ layout }` }>
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
		<div className={ `pll-switcher pll-layout-${ layout }` }>
			<ul>
				{ curatedLanguages.map( ( language ) => {
					return (
						<li key={ language.slug }>
							<SwitcherLinkElement
								label={ getLabel( language, show_labels ) }
								flag={ getFlag(
									language,
									show_flags,
									flag_aspect_ratio,
									flag_border_radius,
									flag_width,
									flag_margin_right
								) }
							/>
						</li>
					);
				} ) }
			</ul>
		</div>
	);
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
 * @param {string}  flagAspectRatio  The aspect ratio of the flags. Can be '3:2' or '1:1'.
 * @param {number}  flagBorderRadius The border radius of the flags.
 * @param {number}  flagWidth        The width of the flags in pixels.
 * @param {string}  flagMarginRight  The margin right of the flags.
 * @return {ReactElement|null} The flag for the language.
 */
const getFlag = (
	language,
	showFlags,
	flagAspectRatio,
	flagBorderRadius,
	flagWidth,
	flagMarginRight
) => {
	if ( ! showFlags || ! language.flag ) {
		return null;
	}

	return (
		<span
			className="pll-switcher-flag"
			data-aspect-ratio={ flagAspectRatio.replace( ':', '' ) }
			style={ {
				'--pll-flag-border-radius': flagBorderRadius,
				'--pll-flag-width': `${ flagWidth }px`,
				'--pll-flag-margin-right': flagMarginRight,
			} }
			/* eslint-disable-next-line prettier/prettier */
			dangerouslySetInnerHTML={ { // phpcs:ignore WordPressVIPMinimum.JS.DangerouslySetInnerHTML.Found
				__html: stripFlagDimensions( language.flag ),
			} }
		/>
	);
};
