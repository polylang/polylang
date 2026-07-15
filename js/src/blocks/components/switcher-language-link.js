/**
 * Internal dependencies
 */
import { useMemoizedFlag } from '../hooks/use-memoized-flag';
import { SwitcherLinkElement } from './switcher-link-element';
import { getLabel } from './switcher-utils';

/**
 * Renders a language link for the switcher preview.
 *
 * @param {Object}           props                  Component props.
 * @param {Object}           props.language         The language object.
 * @param {string}           props.showLabels       Whether to show labels. Can be 'names', 'codes' or ''.
 * @param {boolean}          props.showFlags        Whether to show the flags.
 * @param {number}           props.flagBorderRadius The border radius of the flags.
 * @param {string}           props.flagWidth        The width of the flags as a CSS length.
 * @param {string|undefined} props.labelSpacing     CSS length for the space between flag and label.
 * @return {ReactElement} The language link element.
 */
export const SwitcherLanguageLink = ( {
	language,
	showLabels,
	showFlags,
	flagBorderRadius,
	flagWidth,
	labelSpacing,
} ) => {
	const flag = useMemoizedFlag(
		language,
		showFlags,
		flagBorderRadius,
		flagWidth
	);

	return (
		<SwitcherLinkElement
			label={ getLabel( language, showLabels ) }
			flag={ flag }
			labelSpacing={ labelSpacing }
		/>
	);
};
