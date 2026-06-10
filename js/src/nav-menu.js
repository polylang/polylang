/**
 * Handles the options in the language switcher nav menu metabox.
 */

const pllNavMenu = {
	/**
	 * The element wrapping the menu elements.
	 *
	 * @member {HTMLElement|null}
	 */
	wrapper: null,

	/**
	 * Init.
	 */
	init: () => {
		if ( document.readyState !== 'loading' ) {
			pllNavMenu.ready();
		} else {
			document.addEventListener( 'DOMContentLoaded', pllNavMenu.ready );
		}
	},

	/**
	 * Called when the DOM is ready. Attaches the events to the wrapper.
	 */
	ready: () => {
		pllNavMenu.wrapper = document.getElementById( 'menu-to-edit' );

		if ( ! pllNavMenu.wrapper ) {
			return;
		}

		pllNavMenu.wrapper.addEventListener( 'click', pllNavMenu.printMetabox );
		pllNavMenu.wrapper.addEventListener(
			'change',
			pllNavMenu.manageRowsAndValues
		);
	},

	printMetabox: {
		/**
		 * Event callback that prints our checkboxes in the language switcher.
		 *
		 * @param {Event} event The event.
		 */
		handleEvent: ( event ) => {
			if ( ! event.target.classList.contains( 'item-edit' ) ) {
				// Not clicking on a Edit arrow button.
				return;
			}

			const metabox = event.target
				.closest( '.menu-item' )
				.querySelector( '.menu-item-settings' );

			if ( ! metabox?.id ) {
				// Should not happen.
				return;
			}

			if (
				! metabox.querySelectorAll(
					'input[value="#pll_switcher"][type=text]'
				).length
			) {
				// Not our metabox, or already replaced.
				return;
			}

			// Remove default fields we don't need.
			[ ...metabox.children ].forEach( ( el ) => {
				if (
					'P' === el.nodeName &&
					! el.classList.contains( 'field-move' )
				) {
					el.remove();
				}
			} );

			const t = pllNavMenu.printMetabox;
			const itemId = Number(
				metabox.id.replace( 'menu-item-settings-', '' )
			);

			// phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.append
			metabox.append(
				t.createHiddenInput( 'title', itemId, pll_data.title )
			);
			// phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.append
			metabox.append(
				t.createHiddenInput( 'url', itemId, '#pll_switcher' )
			);
			metabox.append( t.createHiddenInput( 'pll-detect', itemId, 1 ) ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.append

			const menuValues =
				typeof pll_data.val[ itemId ] !== 'undefined'
					? pll_data.val[ itemId ]
					: {};

			Object.keys( pll_data.data )
				.reverse()
				.forEach( ( optionName ) => {
					const optionData = pll_data.data[ optionName ];
					const optionValue =
						typeof menuValues[ optionName ] !== 'undefined'
							? menuValues[ optionName ]
							: optionData.default;
					// Create the wrapper.
					const wrapperAtts = { class: 'description' };

					if ( optionData.hide_if ) {
						Object.keys( optionData.hide_if ).forEach(
							( conditionName ) => {
								const conditionValue =
									optionData.hide_if[ conditionName ];
								wrapperAtts.class += ` pll-hidden-if-${ conditionName }-${ conditionValue }`; // phpcs:ignore Squiz.ControlStructures.ControlSignature.SpaceAfterKeyword, Squiz.ControlStructures.ControlSignature.NewlineAfterOpenBrace

								if (
									typeof menuValues[ conditionName ] !==
										'undefined' &&
									conditionValue ===
										menuValues[ conditionName ]
								) {
									wrapperAtts.class += ` pll-hidden-by-${ conditionName }`;
								}
							}
						);
					}

					const inputWrapper = t.createElement( 'p', wrapperAtts );

					metabox.prepend( inputWrapper ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.prepend

					// Create the label.
					const label = t.createElement( 'label', {
						for: `edit-menu-item-${ optionName }-${ itemId }`, // phpcs:ignore Squiz.ControlStructures.ControlSignature.SpaceAfterKeyword
					} );
					label.innerText = ` ${ optionData.label } `;

					inputWrapper.append( label ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.append

					// Create the input.
					if ( optionData.choices ) {
						const input = t.createSelectInput(
							optionName,
							itemId,
							optionValue,
							optionData.choices
						);
						label.append( input ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.append
					} else {
						const input = t.createCheckboxInput(
							optionName,
							itemId,
							optionValue
						);
						label.prepend( input ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.prepend
					}
				} );
		},

		/**
		 * Creates and returns a `<input type="hidden"/>` element.
		 *
		 * @param {string}        id     An identifier for this input. It will be part of the final `id` attribute.
		 * @param {number}        itemId The ID of the menu element (post ID).
		 * @param {string|number} value  The input's value.
		 * @return {HTMLElement} The input element.
		 */
		createHiddenInput: ( id, itemId, value ) => {
			return pllNavMenu.printMetabox.createInput(
				id,
				itemId,
				value,
				'hidden'
			);
		},

		/**
		 * Creates and returns a `<input type="checkbox"/>` element.
		 *
		 * @param {string}         id     An identifier for this input. It will be part of the final `id` attribute.
		 * @param {number}         itemId The ID of the menu element (post ID).
		 * @param {boolean|number} value  The option value.
		 * @return {HTMLElement} The input element.
		 */
		createCheckboxInput: ( id, itemId, value ) => {
			const input = pllNavMenu.printMetabox.createInput(
				id,
				itemId,
				1,
				'checkbox'
			);
			input.setAttribute( 'data-key', id );
			if ( value ) {
				input.checked = true;
			}
			return input;
		},

		/**
		 * Creates and returns a `<select/>` element.
		 *
		 * @param {string}        id      An identifier for this input. It will be part of the final `id` attribute.
		 * @param {number}        itemId  The ID of the menu element (post ID).
		 * @param {string|number} value   The option value.
		 * @param {Object}        choices Choices.
		 * @return {HTMLElement} The input element.
		 */
		createSelectInput: ( id, itemId, value, choices ) => {
			const input = pllNavMenu.printMetabox.createElement( 'select', {
				id: `edit-menu-item-${ id }-${ itemId }`,
				name: `menu-item-${ id }[${ itemId }]`,
				'data-key': id,
			} );
			Object.keys( choices ).forEach( ( optionValue ) => {
				const atts = { value: optionValue };
				if ( value === optionValue ) {
					atts.selected = 'selected';
				}
				const option = pllNavMenu.printMetabox.createElement(
					'option',
					atts
				);
				option.innerText = choices[ optionValue ];
				input.append( option ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.append
			} );
			return input;
		},

		/**
		 * Creates and returns a `<input/>` element.
		 *
		 * @param {string}        id     An identifier for this input. It will be part of the final `id` attribute.
		 * @param {number}        itemId The ID of the menu element (post ID).
		 * @param {string|number} value  The input's value.
		 * @param {string}        type   The type of input.
		 * @return {HTMLElement} The input element.
		 */
		createInput: ( id, itemId, value, type ) => {
			return pllNavMenu.printMetabox.createElement( 'input', {
				type,
				id: `edit-menu-item-${ id }-${ itemId }`,
				name: `menu-item-${ id }[${ itemId }]`,
				value,
			} );
		},

		/**
		 * Creates and returns an element.
		 *
		 * @param {string} type Element's type.
		 * @param {Object} atts Element's attributes.
		 * @return {HTMLElement} The element.
		 */
		createElement: ( type, atts ) => {
			const el = document.createElement( type );
			for ( const [ key, value ] of Object.entries( atts ) ) {
				el.setAttribute( key, value );
			}
			return el;
		},
	},

	manageRowsAndValues: {
		/**
		 * Event callback that hides rows and forbids disabling both flag and name/code.
		 *
		 * @param {Event} event The event.
		 */
		handleEvent: ( event ) => {
			let value = '';

			if ( 'SELECT' === event.target.nodeName ) {
				value = event.target.value;
			} else if (
				'INPUT' === event.target.nodeName &&
				'checkbox' === event.target.type
			) {
				value = event.target.checked;
			} else {
				return;
			}

			const wrapper = event.target.closest( '.menu-item-settings' );

			if ( ! wrapper ) {
				return;
			}

			// Hide rows.
			const key = event.target.getAttribute( 'data-key' );

			wrapper
				.querySelectorAll(
					`[class*="pll-hidden-if-${ key }-"]:not(.pll-hidden-if-${ key }-${ value })` // phpcs:ignore Squiz.ControlStructures.ControlSignature.SpaceAfterKeyword, Generic.ControlStructures.InlineControlStructure.NotAllowed
				)
				.forEach( ( input ) => {
					input.classList.remove( `pll-hidden-by-${ key }` );
				} );
			wrapper
				.querySelectorAll( `.pll-hidden-if-${ key }-${ value }` ) // phpcs:ignore Squiz.ControlStructures.ControlSignature.SpaceAfterKeyword, Generic.ControlStructures.InlineControlStructure.NotAllowed
				.forEach( ( input ) => {
					input.classList.add( `pll-hidden-by-${ key }` );
				} );

			// Forbid disabling both flag and name/code.
			if ( 'show_labels' === key && '' === value ) {
				const otherInput = wrapper.querySelector(
					'[data-key="show_flags"]'
				);

				if ( true !== otherInput.checked ) {
					otherInput.checked = true;
					otherInput.dispatchEvent(
						new Event( 'change', { bubbles: true } )
					);
				}
			} else if ( 'show_flags' === key && false === value ) {
				const otherInput = wrapper.querySelector(
					'[data-key="show_labels"]'
				);

				if ( '' === otherInput.value ) {
					otherInput.value = 'names';
					otherInput.dispatchEvent(
						new Event( 'change', { bubbles: true } )
					);
				}
			}
		},
	},
};

pllNavMenu.init();
