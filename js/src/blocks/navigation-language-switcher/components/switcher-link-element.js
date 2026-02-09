/**
 * Internal dependencies
 */
import { useMemoizedSwitcherLabel } from '../../hooks/use-memoized-switcher-label';

/**
 * Switcher link element component.
 *
 * @param {Object}  props            Component props.
 * @param {Object}  props.language   Language object.
 * @param {boolean} props.isTopLevel Whether the language is the top level language.
 * @param {boolean} props.showFlags  Whether to show the flags.
 * @param {boolean} props.showNames  Whether to show the names.
 * @return {ReactElement}            The Switcher element component.
 */
export const SwitcherLinkElement = ( {
	language,
	isTopLevel,
	showFlags,
	showNames,
} ) => {
	const { text, flag } = useMemoizedSwitcherLabel(
		language,
		showFlags,
		showNames
	);
	const prefix = isTopLevel ? '' : ' ';

	return (
		// eslint-disable-next-line jsx-a11y/anchor-is-valid
		<a href={ '#' }>
			{ prefix }
			{ /* eslint-disable-next-line prettier/prettier */ }
			<span dangerouslySetInnerHTML={ { __html: flag } } /> { text } { /* phpcs:ignore WordPressVIPMinimum.JS.DangerouslySetInnerHTML.Found */ }
		</a>
	);
};
