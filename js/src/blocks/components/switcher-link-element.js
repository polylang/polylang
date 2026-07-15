/**
 * Switcher link element component.
 *
 * @param {Object}       props              Component props.
 * @param {ReactElement} props.flag         Flag to display.
 * @param {string}       props.label        Label to display.
 * @param {string}       props.labelSpacing CSS length for the space between flag and label.
 * @return {ReactElement}                   The Switcher element component.
 */
export const SwitcherLinkElement = ( { flag, label, labelSpacing } ) => {
	const hasLabel = '' !== label;
	const labelStyle =
		hasLabel && labelSpacing
			? { '--pll-flag-label-spacing': labelSpacing }
			: undefined;

	return (
		// eslint-disable-next-line jsx-a11y/anchor-is-valid
		<a href={ '#' }>
			{ flag }
			{ hasLabel && (
				<span className="pll-switcher-label" style={ labelStyle }>
					{ label }
				</span>
			) }
		</a>
	);
};
