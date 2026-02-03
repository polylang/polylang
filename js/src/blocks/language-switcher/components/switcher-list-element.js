/**
 * Internal dependencies
 */
import { useMemoizedSwitcherLabel } from '../../hooks/use-memoized-switcher-label';

/**
 * Switcher list element component.
 *
 * @param {Object}  props           Component props.
 * @param {Object}  props.language  Language object.
 * @param {boolean} props.showFlags Whether to show the flags.
 * @param {boolean} props.showNames Whether to show the names.
 * @return {ReactElement}            The Switcher element component.
 */
export const SwitcherListElement = ( { language, showFlags, showNames } ) => {
	const { text, flag } = useMemoizedSwitcherLabel(
		language,
		showFlags,
		showNames
	);

	return (
		<li>
			{ /* eslint-disable-next-line prettier/prettier */ }
			<span dangerouslySetInnerHTML={ { __html: flag } } /> { text } { /* phpcs:ignore WordPressVIPMinimum.JS.DangerouslySetInnerHTML.Found */ }
		</li>
	);
};
