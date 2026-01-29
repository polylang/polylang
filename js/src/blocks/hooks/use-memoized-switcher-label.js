/**
 * WordPress dependencies
 */
import { useMemo } from '@wordpress/element';

/**
 * Hook to memoize the switcher label.
 *
 * @param {Object}  language  The language object.
 * @param {boolean} showFlags Whether to show the flags.
 * @param {boolean} showNames Whether to show the names.
 * @return {Object} The memoized switcher label containing the text and the flag.
 */
export const useMemoizedSwitcherLabel = ( language, showFlags, showNames ) => {
	const { text, flag } = useMemo( () => {
		let memoizedText = '';
		if ( showNames ) {
			if ( showFlags ) {
				memoizedText = ` ${ language.name }`;
			} else {
				memoizedText = language.name;
			}
		}

		const memoizedFlag = showFlags ? language.flag : '';

		return {
			text: memoizedText,
			flag: memoizedFlag,
		};
	}, [ language, showFlags, showNames ] );

	return {
		text,
		flag,
	};
};
