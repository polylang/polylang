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
		// Dropdowns.
		pllSwitcher.buttons =
			document.getElementsByClassName( 'pll-submenu-toggle' );

		for ( const button in pllSwitcher.buttons ) {
			button.setAttribute( 'aria-expanded', 'false' );
			button.addEventListener(
				'click',
				pllSwitcher.openCloseSubmenu.toggleOnButtonClick
			);
			button.parentNode.addEventListener(
				'mouseenter',
				pllSwitcher.openCloseSubmenu.openOnParentMouseEnter
			);
			button.parentNode.addEventListener(
				'mouseleave',
				pllSwitcher.openCloseSubmenu.closeOnParentMouseLeave
			);
			button.nextElementSibling.addEventListener(
				'keydown',
				pllSwitcher.openCloseSubmenu.closeOnListKeydown
			);
			button.nextElementSibling
				.querySelector( 'li:last-child > a' )
				.addEventListener(
					'blur',
					pllSwitcher.openCloseSubmenu.closeOnLastLinkBlur
				);
		}

		// Selects.
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
		 * Event callback that toggles a submenu when clicking the button.
		 *
		 * @param {Event} event The event.
		 */
		toggleOnButtonClick: ( event ) => {
			const expanded =
				event.currentTarget.getAttribute( 'aria-expanded' );
			event.currentTarget.setAttribute(
				'aria-expanded',
				'true' === expanded ? 'false' : 'true'
			);
		},

		/**
		 * Event callback that opens a submenu when hovering the parent.
		 *
		 * @param {Event} event The event.
		 */
		openOnParentMouseEnter: ( event ) => {
			event.currentTarget
				.querySelector( '.pll-submenu-toggle' )
				.setAttribute( 'aria-expanded', 'true' );
		},

		/**
		 * Event callback that closes a submenu when hover is leaving the parent.
		 *
		 * @param {Event} event The event.
		 */
		closeOnParentMouseLeave: ( event ) => {
			event.currentTarget
				.querySelector( '.pll-submenu-toggle' )
				.setAttribute( 'aria-expanded', 'false' );
		},

		/**
		 * Event callback that closes a submenu when pressing the Escape key while in the list.
		 *
		 * @param {Event} event The event.
		 */
		closeOnListKeydown: ( event ) => {
			if ( event.key !== 'Escape' ) {
				return;
			}
			event.currentTarget.previousElementSibling.focus();
			event.currentTarget.previousElementSibling.setAttribute(
				'aria-expanded',
				'false'
			);
		},

		/**
		 * Event callback that closes a submenu when the last link in the list looses focus (to a target outside the list).
		 *
		 * @param {Event} event The event.
		 */
		closeOnLastLinkBlur: ( event ) => {
			const list = event.currentTarget.closest( 'ul' );
			if ( ! list ) {
				return;
			}
			if ( list.parentNode.contains( event.relatedTarget ) ) {
				return;
			}
			list.previousElementSibling.setAttribute(
				'aria-expanded',
				'false'
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
