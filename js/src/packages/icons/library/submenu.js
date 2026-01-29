/**
 * Submenu icon
 */

/**
 * WordPress dependencies
 */
import { SVG, Path } from '@wordpress/primitives';

const isPrimitivesComponents = 'undefined' !== typeof wp.primitives;

const SubmenuIcon = () =>
	isPrimitivesComponents ? (
		<SVG
			xmlns="http://www.w3.org/2000/svg"
			width="12"
			height="12"
			viewBox="0 0 12 12"
			fill="none"
		>
			<Path d="M1.50002 4L6.00002 8L10.5 4" strokeWidth="1.5" />
		</SVG>
	) : (
		'submenu'
	);

export default SubmenuIcon;
