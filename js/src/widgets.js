/**
 * Handles the options in the language switcher widget.
 */
const pllWidget = {
	/**
	 * Init.
	 */
	init: () => {
		if ( document.readyState !== 'loading' ) {
			pllWidget.ready();
		} else {
			document.addEventListener( 'DOMContentLoaded', pllWidget.ready );
		}
	},

	/**
	 * Called when the DOM is ready. Attaches the events to the wrapper.
	 */
	ready: () => {
		if ( window.pll_widgets?.flags ) {
			pllWidget.displayFlags.flags = window.pll_widgets.flags;
		}
		document
			.querySelectorAll(
				// Widgets page without Classic Widgets, Widgets page with Classic Widgets, Customizer page.
				'#widgets-editor, .widget-liquid-right #widgets-right, #customize-theme-controls'
			)
			.forEach( ( wrapper ) => {
				wrapper.addEventListener( 'change', ( event ) => {
					if (
						event.target.closest(
							'.polylang-language-switcher-widget-content select, .polylang-language-switcher-widget-content input'
						)
					) {
						pllWidget.manageRowsAndValues.handleEvent( event );
						return;
					}

					if (
						! pllWidget.displayFlags.flags ||
						'customize-theme-controls' === wrapper.id
					) {
						// No flags for the customizer.
						return;
					}

					if ( event.target.closest( '.widget .pll-lang-choice' ) ) {
						// With Classic Widgets.
						pllWidget.displayFlags.handleEvent(
							event,
							'.widget',
							'.widget-top .widget-title h3'
						);
					} else if (
						event.target.closest(
							'.wp-block-legacy-widget__edit-form .pll-lang-choice'
						)
					) {
						// Without Classic Widgets.
						pllWidget.displayFlags.handleEvent(
							event,
							'.wp-block-legacy-widget__edit-form',
							'.wp-block-legacy-widget__edit-form-title'
						);
					}
				} );

				if ( 'customize-theme-controls' !== wrapper.id ) {
					// No flags for the customizer.
					wrapper
						.querySelectorAll( ':scope .pll-lang-choice' )
						.forEach( ( select ) => {
							select.dispatchEvent(
								new Event( 'change', { bubbles: true } )
							);
						} );
				}
			} );
	},

	/**
	 * Display or hide rows, depending on the value of other settings.
	 */
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

			const wrapper = event.target.closest(
				'.polylang-language-switcher-widget-content'
			);

			if ( ! wrapper ) {
				return;
			}

			const key = event.target.getAttribute( 'data-key' );

			// Show/Hide rows.
			wrapper
				.querySelectorAll(
					`:scope [class*="pll-hidden-if-${ key }-"]:not(.pll-hidden-if-${ key }-${ value })` // phpcs:ignore Squiz.ControlStructures.ControlSignature.SpaceAfterKeyword, Generic.ControlStructures.InlineControlStructure.NotAllowed, PHPCS detects `-if-` like a `if(`.
				)
				.forEach( ( input ) => {
					input.classList.remove( `pll-hidden-by-${ key }` );
				} );
			wrapper
				.querySelectorAll( `:scope .pll-hidden-if-${ key }-${ value }` ) // phpcs:ignore Squiz.ControlStructures.ControlSignature.SpaceAfterKeyword, Generic.ControlStructures.InlineControlStructure.NotAllowed, PHPCS detects `-if-` like a `if(`.
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

	/**
	 * Display a flag in front of a widget title, depending on the language the widget should be displayed for.
	 */
	displayFlags: {
		/**
		 * Flags (markup), with language slug as property names.
		 *
		 * @member {Object}
		 */
		flags: {},

		/**
		 * Event callback that adds or removes the flag.
		 *
		 * @param {Event}  event          The event.
		 * @param {string} widgetSelector Selector for the widget wrapper, relative to the event target.
		 * @param {string} titleSelector  Selector for the title, relative to the wrapper.
		 */
		handleEvent: ( event, widgetSelector, titleSelector ) => {
			const wrapper = event.target.closest( widgetSelector );

			if ( ! wrapper ) {
				return;
			}

			const title = wrapper.querySelector( titleSelector );

			if ( ! title ) {
				return;
			}

			const langSlug = event.target.value;
			const icon =
				langSlug &&
				pllWidget.displayFlags.flags.hasOwnProperty( langSlug )
					? pllWidget.displayFlags.flags[ langSlug ]
					: null;
			const currentFlag = title.querySelector( '.pll-lang' );

			if ( icon ) {
				if ( currentFlag ) {
					currentFlag.innerHTML = `${ icon } &nbsp; `; // phpcs:ignore WordPressVIPMinimum.JS.InnerHTML.Found, `icon` comes from `PLL_Admin_Base::add_inline_scripts()`.
				} else {
					const newFlag = document.createElement( 'span' );
					newFlag.classList.add( 'pll-lang' );
					newFlag.innerHTML = `${ icon } &nbsp; `; // phpcs:ignore WordPressVIPMinimum.JS.InnerHTML.Found, `icon` comes from `PLL_Admin_Base::add_inline_scripts()`.
					title.prepend( newFlag ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.prepend, `newFlag` is a new element we just created with safe data.
				}
			} else if ( currentFlag ) {
				currentFlag.remove();
			}
		},
	},
};

pllWidget.init();
