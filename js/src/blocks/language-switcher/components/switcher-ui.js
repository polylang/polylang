/**
 * Internal dependencies
 */
import { SwitcherListElement } from './switcher-list-element';

/**
 * Switcher UI component.
 *
 * @param {Object}  props            The component props.
 * @param {Array}   props.languages  The languages to display.
 * @param {boolean} props.showFlags  Whether to show the flags.
 * @param {boolean} props.showNames  Whether to show the names.
 * @param {boolean} props.isDropdown Whether to show the dropdown.
 * @return {ReactElement} The Switcher UI component.
 */
export const SwitcherUI = ( {
	languages,
	showFlags,
	showNames,
	isDropdown,
} ) => {
	if ( isDropdown ) {
		return (
			<select>
				{ languages.map( ( language ) => {
					return (
						<option key={ language.slug } value={ language.slug }>
							{ language.name }
						</option>
					);
				} ) }
			</select>
		);
	}

	return (
		<ul>
			{ languages.map( ( language ) => {
				return (
					<SwitcherListElement
						key={ language.slug }
						language={ language }
						showFlags={ showFlags }
						showNames={ showNames }
					/>
				);
			} ) }
		</ul>
	);
};
