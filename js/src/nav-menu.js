/**
 * Handles the options in the language switcher nav menu metabox.
 *
 * @package Polylang
 */

const pllNavMenu = {
	/**
	 * Init.
	 */
	init: () => {
		const handlers = [
			pllNavMenu.printMetabox.attachEvent,
			pllNavMenu.ensureContent.attachEvent,
			pllNavMenu.showHideRows.attachEvent
		];

		if ( document.readyState !== 'loading' ) {
			handlers.forEach( handler => handler() );
		} else {
			handlers.forEach( handler =>
				document.addEventListener( 'DOMContentLoaded', handler )
			);
		}
	},

	printMetabox: {
		/**
		 * Attaches an event to `#menu-to-edit` to print our checkboxes in the language switcher.
		 */
		attachEvent: () => {
			/*global pll_data*/
			const wrapper = document.getElementById( 'menu-to-edit' );

			if ( ! wrapper ) {
				return;
			}

			wrapper.addEventListener( 'click', ( event ) => {
				if ( ! event.target.classList.contains( 'item-edit' ) ) {
					// Not clicking on a Edit arrow button.
					return;
				}

				const metabox = event.target.closest( '.menu-item' ).querySelector( '.menu-item-settings' );

				if ( ! metabox?.id ) {
					// Should not happen.
					return;
				}

				if ( ! metabox.querySelectorAll( 'input[value="#pll_switcher"][type=text]' ).length ) {
					// Not our metabox, or already replaced.
					return;
				}

				// Remove default fields we don't need.
				for ( const el of metabox.querySelectorAll( 'p:not(.field-move)' ) ) {
					el.remove();
				}

				const t      = pllNavMenu.printMetabox;
				const itemId = Number( metabox.id.replace( 'menu-item-settings-', '' ) );

				metabox.append( t.createHiddenInput( 'title', itemId, pll_data.title ) ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.append
				metabox.append( t.createHiddenInput( 'url', itemId, '#pll_switcher' ) ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.append
				metabox.append( t.createHiddenInput( 'detect', itemId, 1 ) ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.append

				const ids          = Array( 'hide_if_no_translation', 'hide_current', 'force_home', 'show_flags', 'show_names', 'dropdown' ); // Reverse order.
				const isValDefined = typeof( pll_data.val[ itemId ] ) !== 'undefined';

				ids.forEach( ( optionName ) => {
					// Create the checkbox's wrapper.
					const inputWrapper = t.createElement( 'p', { class: 'description' } );

					if ( 'hide_current' === optionName && isValDefined && 1 === pll_data.val[ itemId ].dropdown ) {
						// Hide the `hide_current` checkbox if `dropdown` is checked.
						inputWrapper.classList.add( 'hidden' );
					}

					metabox.prepend( inputWrapper ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.prepend

					// Create the checkbox's label.
					const inputId = `edit-menu-item-${ optionName }-${ itemId }`;
					const label   = t.createElement( 'label', { 'for': inputId } );
					label.innerText = ` ${ pll_data.strings[ optionName ] }`;

					inputWrapper.append( label ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.append

					// Create the checkbox.
					const cb = t.createElement( 'input', {
						type:  'checkbox',
						id:    inputId,
						name:  `menu-item-${ optionName }[${ itemId }]`,
						value: 1
					} );

					if ( ( isValDefined && 1 === pll_data.val[ itemId ][ optionName ] ) || ( ! isValDefined && 'show_names' === optionName ) ) { // `show_names` as default value.
						cb.checked = true;
					}

					label.prepend( cb ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.prepend
				} );
			} );
		},

		/**
		 * Creates and returns a `<input type=hidden"/>` element.
		 *
		 * @param {String}         id     An identifier for this input. It will be part of the final `id` attribute.
		 * @param {Integerg}       itemId The ID of the menu element (post ID).
		 * @param {String|Integer} value  The input's value.
		 * @returns {HTMLElement}
		 */
		createHiddenInput: ( id, itemId, value ) => {
			return pllNavMenu.printMetabox.createElement( 'input', {
				type:  'hidden',
				id:    `edit-menu-item-pll-${ id }-${ itemId }`,
				name:  `menu-item-pll-${ id }[${ itemId }]`,
				value: value
			} );
		},

		/**
		 * Creates and returns an element.
		 *
		 * @param {String} type Element's type.
		 * @param {Object} atts Element's attributes.
		 * @returns {HTMLElement}
		 */
		createElement: ( type, atts ) => {
			const el = document.createElement( type );
			for ( const [ key, value ] of Object.entries( atts ) ) {
				el.setAttribute( key, value );
			}
			return el;
		}
	},

	ensureContent: {
		/**
		 * Attaches an event to `#menu-to-edit` to disallow unchecking both `show_names` and `show_flags`.
		 */
		attachEvent: () => {
			const wrapper = document.getElementById( 'menu-to-edit' );

			if ( ! wrapper ) {
				return;
			}

			const regExpr = new RegExp( /^edit-menu-item-show_(names|flags)-(\d+)$/ );

			wrapper.addEventListener( 'change', ( event ) => {
				if ( ! event.target.id || event.target.checked ) {
					// Now checked, nothing to do.
					return;
				}

				const matches = event.target.id.match( regExpr );

				if ( ! matches ) {
					// Not the checkbox we want.
					return;
				}

				// Check the other checkbox.
				const [ , type, id ] = matches;
				const otherType = 'names' === type ? 'flags' : 'names';
				document.getElementById( `edit-menu-item-show_${ otherType }-${ id }` ).checked = true;
			} );
		}
	},

	showHideRows: {
		/**
		 * Attaches an event to `#menu-to-edit` to show or hide the `hide_current` checkbox when `dropdown` is checked.
		 */
		attachEvent: () => {
			const wrapper = document.getElementById( 'menu-to-edit' );

			if ( ! wrapper ) {
				return;
			}

			const regExpr = new RegExp( /^edit-menu-item-dropdown-(\d+)$/ );

			wrapper.addEventListener( 'change', ( event ) => {
				if ( ! event.target.id ) {
					// Not the checkbox we want.
					return;
				}

				const matches = event.target.id.match( regExpr );

				if ( ! matches ) {
					// Not the checkbox we want.
					return;
				}

				const hideCb = document.getElementById( `edit-menu-item-hide_current-${ matches[1] }` );

				if ( ! hideCb ) {
					// Should not happen.
					return;
				}

				const description = hideCb.closest( '.description' );

				// Hide or show.
				description.classList.toggle( 'hidden', event.target.checked );

				if ( event.target.checked ) {
					// Uncheck after hiding.
					hideCb.checked = false;
					hideCb.dispatchEvent( new Event( 'change' ) );
				}
			} );
		}
	}
};

pllNavMenu.init();
