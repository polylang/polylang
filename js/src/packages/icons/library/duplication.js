/**
 * Duplication icon - admin-page Dashicon.
 */

/**
 * WordPress dependencies
 */
import { SVG, Path } from '@wordpress/primitives';

const isPrimitivesComponents = 'undefined' !== typeof wp.primitives;

const duplication = isPrimitivesComponents ? (
	<SVG
		width="20"
		height="20"
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 20 20"
	>
		<Path d="M6 15v-13h10v13h-10zM5 16h8v2h-10v-13h2v11z"></Path>
	</SVG>
) : (
	'admin-page'
);

export default duplication;
