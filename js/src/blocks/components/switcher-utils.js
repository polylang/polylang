/**
 * Returns the switcher wrapper class names.
 *
 * @param {string}  layout          The switcher layout.
 * @param {boolean} showFlags       Whether flags are displayed.
 * @param {string}  flagAspectRatio The flag aspect ratio.
 * @return {string} The switcher class names.
 */
export const getSwitcherClassName = ( layout, showFlags, flagAspectRatio ) => {
	const classes = [ 'pll-switcher', `pll-layout-${ layout }` ];

	if ( showFlags ) {
		classes.push(
			`pll-aspect-ratio-${ flagAspectRatio.replace( ':', '' ) }`
		);
	}

	return classes.join( ' ' );
};

/**
 * Gets the label for a language.
 *
 * @param {Object} language   The language object.
 * @param {string} showLabels Whether to show the names. Can be 'names', 'codes' or ''.
 * @return {string} The label for the language.
 */
export const getLabel = ( language, showLabels ) => {
	let label = '';
	if ( showLabels === 'names' ) {
		label = language.name;
	} else if ( showLabels === 'codes' ) {
		label = language.slug.toUpperCase();
	}

	return label;
};

/**
 * Returns the CSS length for the space between flag and label.
 *
 * @param {string} showLabels       Whether to show labels. Can be 'names', 'codes' or ''.
 * @param {string} flagLabelSpacing CSS length for the space between flag and label.
 * @return {string|undefined} The label spacing, or undefined when labels are hidden.
 */
export const getLabelSpacing = ( showLabels, flagLabelSpacing ) => {
	if ( '' === showLabels ) {
		return undefined;
	}

	return flagLabelSpacing;
};
