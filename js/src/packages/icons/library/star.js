/**
 * Star icon - star-filled Dashicon.
 */

/**
 * WordPress dependencies
 */
import { SVG, Path } from '@wordpress/primitives';

const isPrimitivesComponents = 'undefined' !== typeof wp.primitives;

const star = isPrimitivesComponents ? (
	<SVG
		width="20"
		height="20"
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 20 20"
	>
		<Path d="m10 1 3 6 6 .75-4.12 4.62L16 19l-6-3-6 3 1.13-6.63L1 7.75 7 7z"></Path>
	</SVG>
) : (
	'star-filled'
);

export default star;
