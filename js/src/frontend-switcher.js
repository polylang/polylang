/**
 * Allows to open/close the language switcher's submenus on click when the "dropdown" layout is used.
 */

const pllSwitcher = {
	/**
	 * The buttons allowing to open/close the submenus.
	 *
	 * @member {HTMLElement|null}
	 */
	buttons: null,

	/**
	 * The `select` tags.
	 *
	 * @member {HTMLElement|null}
	 */
	selects: null,

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
		pllSwitcher.buttons =
			document.getElementsByClassName( 'pll-submenu-toggle' );
		const lenButtons = pllSwitcher.buttons.length;

		for ( let i = 0; i < lenButtons; i++ ) {
			pllSwitcher.buttons[ i ].addEventListener(
				'click',
				pllSwitcher.openCloseSubmenu
			);
		}

		pllSwitcher.selects = document.getElementsByClassName(
			'pll-switcher-select'
		);
		const lenSelects = pllSwitcher.selects.length;

		for ( let j = 0; j < lenSelects; j++ ) {
			pllSwitcher.selects[ j ].addEventListener(
				'change',
				pllSwitcher.changeLocationSelect
			);
		}
	},

	/**
	 * Handles the opening and closing of a submenu for a "dropdown" layout.
	 */
	openCloseSubmenu: {
		/**
		 * Event callback that opens or closes a submenu.
		 *
		 * @param {Event} event The event.
		 */
		handleEvent: ( event ) => {
			const expanded =
				event.currentTarget.getAttribute( 'aria-expanded' );
			event.currentTarget.setAttribute(
				'aria-expanded',
				'true' === expanded ? 'false' : 'true'
			);
		},
	},

	/**
	 * Handles the redirection for a "select" layout.
	 */
	changeLocationSelect: {
		/**
		 * Event callback that changes the location when a value is selected in the `select` switcher.
		 *
		 * @param {Event} event The event.
		 */
		handleEvent: ( event ) => {
			if ( event.currentTarget.value ) {
				window.location.href = event.currentTarget.value; // PHPCS:ignore WordPressVIPMinimum.JS.Window.location, Already escaped in php.
			}
		},
	},
};

pllSwitcher.init();
