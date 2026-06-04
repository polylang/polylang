/**
 * Switcher link element component.
 *
 * @param {Object}       props       Component props.
 * @param {ReactElement} props.flag  Flag to display.
 * @param {string}       props.label Label to display.
 * @return {ReactElement}            The Switcher element component.
 */
export const SwitcherLinkElement = ( { flag, label } ) => {
	return (
		// eslint-disable-next-line jsx-a11y/anchor-is-valid
		<a href={ '#' }>
			{ flag }
			{ label }
		</a>
	);
};
