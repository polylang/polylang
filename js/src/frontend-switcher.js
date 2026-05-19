/**
 * Allows to open/close the language switcher's submenus on click when the "dropdown" layout is used.
 */

import Dropdown from './frontend-dropdown';

const pllSwitcher = {
	/**
	 * Init.
	 */
	init: () => {
		if ( document.readyState !== 'loading' ) {
			pllSwitcher.ready();
		} else {
			document.addEventListener( 'DOMContentLoaded', pllSwitcher.ready );
		}
	},

	/**
	 * Called when the DOM is ready. Attaches the events to the buttons.
	 */
	ready: () => {
		// Dropdowns.
		const buttons = document.getElementsByClassName( 'pll-submenu-toggle' );

		for ( const button of buttons ) {
			new Dropdown( button );
		}

		// Selects.
		const selects = document.getElementsByClassName(
			'pll-switcher-select'
		);

		for ( const select of selects ) {
			select.addEventListener( 'change', ( event ) => {
				if ( event.currentTarget.value ) {
					window.location.href = event.currentTarget.value; // PHPCS:ignore WordPressVIPMinimum.JS.Window.location, Already escaped in php.
				}
			} );
		}
	},
};

pllSwitcher.init();
