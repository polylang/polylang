/**
 * Synchronization icon - controls-repeat Dashicon.
 */

/**
 * WordPress dependencies
 */
import { SVG, Path } from '@wordpress/primitives';

const isPrimitivesComponents = 'undefined' !== typeof wp.primitives;

const synchronization = isPrimitivesComponents ? (
	<SVG
		width="20"
		height="20"
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 20 20"
	>
		<Path d="M5 7v3l-2 1.5v-6.5h11v-2l4 3.010-4 2.99v-2h-9zM15 13v-3l2-1.5v6.5h-11v2l-4-3.010 4-2.99v2h9z"></Path>
	</SVG>
) : (
	'controls-repeat'
);

export default synchronization;
