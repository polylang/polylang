/**
 * WordPress dependencies
 */
import { Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SwitcherLinkElement } from './switcher-link-element';
import { SubmenuIcon } from '@wpsyntex/polylang-pro-icons';

/**
 * Switcher UI component.
 *
 * @param {Object}  props                 The component props.
 * @param {Array}   props.languages       The languages to display.
 * @param {boolean} props.showFlags       Whether to show the flags.
 * @param {boolean} props.showNames       Whether to show the names.
 * @param {boolean} props.withSubmenuIcon Whether to show the submenu icon.
 * @return {ReactElement} The Switcher UI component.
 */
export const SwitcherUI = ( {
	languages,
	showFlags,
	showNames,
	withSubmenuIcon,
} ) => {
	return (
		<>
			{ languages &&
				languages.map( ( language ) => {
					return (
						<Fragment key={ language.slug }>
							<SwitcherLinkElement
								language={ language }
								isTopLevel={
									languages.indexOf( language ) === 0
								}
								showFlags={ showFlags }
								showNames={ showNames }
							/>
							{ withSubmenuIcon && (
								<span className="wp-block-navigation__submenu-icon">
									<SubmenuIcon />
								</span>
							) }
						</Fragment>
					);
				} ) }
		</>
	);
};
