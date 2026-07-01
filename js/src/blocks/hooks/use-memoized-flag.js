/**
 * WordPress dependencies
 */
import { useMemo } from '@wordpress/element';

/**
 * Strips fixed dimensions from flag HTML markup.
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
 * Returns a memoized flag element for a language.
 *
 * @param {Object}  language         The language object.
 * @param {boolean} showFlags        Whether to show the flags.
 * @param {number}  flagBorderRadius The border radius of the flags.
 * @param {string}  flagWidth        The width of the flags as a CSS length.
 * @return {ReactElement|null} The flag for the language.
 */
export const useMemoizedFlag = (
	language,
	showFlags,
	flagBorderRadius,
	flagWidth
) => {
	return useMemo( () => {
		if ( ! showFlags || ! language?.flag ) {
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
	}, [ language?.flag, showFlags, flagBorderRadius, flagWidth ] );
};
