/**
 * @package Polylang
 */

jQuery(
	function( $ ) {

		// languages list table
		// accessibility to row actions on focus
		// mainly copy paste of WP code from common.js
		var transitionTimeout;
		$( 'table.languages' ).on(
			{ // restricted to languages list table
				focusin: function() {
					clearTimeout( transitionTimeout );
					var focusedRowActions = $( this ).find( '.row-actions' );
					// transitionTimeout is necessary for Firefox, but Chrome won't remove the CSS class without a little help.
					$( '.row-actions' ).not( this ).removeClass( 'visible' );
					focusedRowActions.addClass( 'visible' );
				},
				focusout: function() {
					// Tabbing between post title and .row-actions links needs a brief pause, otherwise
					// the .row-actions div gets hidden in transit in some browsers ( ahem, Firefox ).
					transitionTimeout = setTimeout(
						function() {
							focusedRowActions.removeClass( 'visible' );
						},
						30
					);
				}
			},
			'tr'
		); // acts on the whole tr instead of single td as we have actions links in several columns

		/**
		 * Common functions and variables for overriding languages and flags dropdown list by a jQuery UI selectmenu widget.
		*/

		// Add a boolean variable to be able to check jQuery UI >= 1.12 which is introduced in WP 5.6.
		// Backward compatibility WP < 5.6
		var isJqueryUImin112 = $.ui.version >= '1.12.0';
		// Allow to check if a flag list dropdown is present. Not present in the Wizard steps or other settings page.
		var flagListExist = $( "#flag_list" ).length;
		// Allow to check if a language list dropdown is present. Not present in other settings page.
		var langListExist = $( "#lang_list" ).length;
		// jQuery UI selectmenu widget width option
		var defaultSelectmenuWidth = '95%';
		var wizardSelectmenuWidth = '100%';

		// Inject flag image when jQuery UI selectmenu is created or an item is selected.
		// jQuery UI 1.12 introduce a wrapper inside de li tag which is necessary to selectmenu widget to work correctly.
		// Mainly copy from the original jQuery UI 1.12 selectmenu widget _renderItem method.
		// Note this code works fine with jQuery UI 1.11.4 too.
		var selectmenuRenderItem = function( ul, item ) {
			var li = $( '<li>' );
			var wrapper = $( '<div>');

			if ( item.disabled ) {
				this._addClass( li, null, "ui-state-disabled" );
			}
			this._setText( wrapper, item.label );

			// Add the flag from the data attribute in the selected element.
			wrapper.prepend( $( item.element ).data( 'flag-html' ) );
			wrapper.children( 'img' ).addClass( 'ui-icon' );

			return li.append( wrapper ).appendTo( ul );
		};
		// Override selected item to inject flag for jQuery UI less than 1.12.
		var selectmenuRefreshButtonText = function( selectElement ) {
			var buttonText = $( selectElement ).selectmenu( 'instance' ).buttonText;
			buttonText.prepend( $( selectElement ).children( ':selected' ).data( 'flag-html' ) );
			buttonText.children( 'img' ).addClass( 'ui-icon' );
		};
		// Override selected item since jQuery UI 1.12 which introduces extension point method _renderButtonItem.
		// @see https://api.jqueryui.com/1.12/selectmenu/#method-_renderButtonItem _renderButtonItem documentation.
		var selectmenuRenderButtonItem = function ( selectElement ) {
			var buttonItem = $( '<span>' );
			this._setText( buttonItem, selectElement.label );
			this._addClass( buttonItem, "ui-selectmenu-text" );

			// Add the flag from the data attribute in the selected element.
			buttonItem.prepend( $( selectElement.element ).data( 'flag-html' ) );
			buttonItem.children( 'img' ).addClass( 'ui-icon' );

			return buttonItem;
		}

		/**
		 * Initialize a jQuery UI selectmenu widget on a DOM element
		 *
		 * @param {*} element - The jQuery object representing the DOM element to attach the widget with.
		 * @param {*} config  - All the parameters - options and callbacks - necessary to configure the jQuery UI selectmenu widget.
		 * @return {Object} - The jQuery UI selectmenu widget object instance.
		 */
		function initializeSelectmenuWidget( element, config ) {
			// Create the jQuery UI selectmenu widget for flags list dropdown and return its instance.
			var selectmenuWidgetInstance = element.selectmenu( config ).selectmenu( 'instance' );
			// Overrides each item in the jQuery UI selectmenu list by injecting flag image.
			selectmenuWidgetInstance._renderItem = selectmenuRenderItem;
			// Override the selected item rendering for jQuery UI 1.12
			if ( isJqueryUImin112 ) {
				selectmenuWidgetInstance._renderButtonItem = selectmenuRenderButtonItem;
				// Need to refresh to take in account the new button item rendering method after the selectmenu widget instanciaion.
				selectmenuWidgetInstance.refresh();
			}
			return selectmenuWidgetInstance
		}
		/**
		 *  Selectmenu widget common parameters for its configuration: options and callbacks.
		 */

		// Selectmenu widget options
		var selectmenuOptions = {
			width: defaultSelectmenuWidth,
			classes: {
				'ui-selectmenu-menu': 'pll-selectmenu-menu',
				'ui-selectmenu-button': 'pll-selectmenu-button',
			}
		};

		// Selectmenu widget callbacks
		var selectmenuFlagListCallbacks = {};
		// Callbacks when Selectmenu widget create or select event is triggered.
		var createSelectCallback = function( event, ui ) {
			selectmenuRefreshButtonText( event.target );
		}

		/**
		 *  Overrides the flag dropdown list with our customized jquery ui selectmenu.
		 */

		// Callbacks when Selectmenu widget change or open event is triggered.
		// Needed to correctly refresh the selected element in the list when editing an existing language or when the value change is triggered by the language choice.
		// jQuery UI 1.11 callback version.
		var changeOpenCallback = function( event, ui ){
			selectmenuRefreshButtonText( $( event.target ).selectmenu( 'refresh' ) );
		}
		// jQueryUI 1.12 callback version.
		var changeOpenCallbackjQueryUI112 = function( event, ui ){
			// Just a refresh of the menu is needed with jQuery UI 1.12 because _renderButtonItem is triggered and then inject correctly the flag.
			$( event.target ).selectmenu( 'refresh' );
		}
		// There is no need of create and select callbacks with jQuery UI 1.12 because overriding _renderButtonItem method do the job.
		if ( isJqueryUImin112 ) {
			selectmenuFlagListCallbacks =
				{
					change: changeOpenCallbackjQueryUI112,
					open: changeOpenCallbackjQueryUI112,
				};
		} else {
			selectmenuFlagListCallbacks = {
				create: createSelectCallback,
				select: createSelectCallback,
				change: changeOpenCallback,
				open: changeOpenCallback,
			};
		}

		// Create the selectmenu widget only if the field is present.
		if ( flagListExist ) {
			// Create the jQuery UI selectmenu widget for flags list dropdown and return its instance.
			var selectmenuFlagList = initializeSelectmenuWidget( $( '#flag_list' ), Object.assign( {}, selectmenuOptions, selectmenuFlagListCallbacks ) );
			$( '#lang_list' ).on(
				'languageChanged',
				function( event, flag ) {
					// Refresh the flag field
					selectmenuFlagList.element.val( flag );
					selectmenuFlagList._trigger( 'change' );
				}
			);
		}

		/**
		 * Language choice in predefined languages in Polylang Languages settings page and wizard.
		 * Overrides the predefined language dropdown list with our customized jQuery ui selectmenu widget.
		 */

		/**
		 * Fill the other language form fields from the language element selected in the language list dropdown.
		 *
		 * @param {Object} language - language object of the selected element in the language list dropdown.
		 */
		function fillLanguageFields( language ) {
			$( '#lang_slug' ).val( language.slug );
			$( '#lang_locale' ).val( language.locale );
			$( 'input[name="rtl"]' ).val( language.rtl );
			$( '#lang_name' ).val( language.name );
		}

		/**
		 * Parse selected language element in the language list dropdown.
		 *
		 * @param {object} event - jQuery triggered event.
		 * @return {object} The language object with its named properties.
		 */
		function parseSelectedLanguage( event ) {
			var selectedElement = $('option:selected', event.target);
			var values = selectedElement.val().split(':')
			return {
				slug: values[0],
				locale: values[1],
				rtl: [values[2]],
				flag: values[3],
				name: selectedElement.text().split(' - ')[0] // At the moment there is no need of the 2nd part because it corresponds on the locale which is already known by splitting the selected element value
			};
		}

		// Callback when selectmenu widget change event is triggered.
		var changeCallback = function( event, ui ) {
			var language = parseSelectedLanguage( event );

			fillLanguageFields( language );

			$( event.target ).trigger( 'languageChanged', language.flag );
		};

		// Create the jQuery UI selectmenu widget languages list dropdown and return its instance.
		var selectmenuLangListCallbacks = {};
		// For the wizard we need a 100% width. So we override the previous defined value of selectmenuOptions.
		if( $( '#lang_list' ).closest( '.pll-wizard-content' ).length > 0 ) {
			selectmenuOptions = Object.assign( selectmenuOptions, { width: wizardSelectmenuWidth } );
		}

		// There is no need of create and select callbacks with jQuery UI 1.12 because overrinding _renderButtonItem method do the job.
		if ( isJqueryUImin112 ) {
			selectmenuLangListCallbacks = {
				change: changeCallback,
			};
		} else {
			selectmenuLangListCallbacks = {
				create: createSelectCallback,
				select: createSelectCallback,
				change: changeCallback,
			};
		}
		if ( langListExist ) {
			initializeSelectmenuWidget( $( '#lang_list' ), Object.assign( {}, selectmenuOptions, selectmenuLangListCallbacks ) );
		}

		// strings translations
		// save translations when pressing enter
		$( '.translation input' ).on(
			'keydown',
			function( event ){
				if ( 'Enter' === event.key ) {
					event.preventDefault();
					$( '#submit' ).trigger( 'click' );
				}
			}
		);

		// settings page
		// click on configure link
		$( '#the-list' ).on(
			'click',
			'.configure>a',
			function(){
				$( '.pll-configure' ).hide().prev().show();
				$( this ).closest( 'tr' ).hide().next().show();
				return false;
			}
		);

		// cancel
		$( '#the-list' ).on(
			'click',
			'.cancel',
			function(){
				$( this ).closest( 'tr' ).hide().prev().show();
			}
		);

		// save settings
		$( '#the-list' ).on(
			'click',
			'.save',
			function(){
				var tr = $( this ).closest( 'tr' );
				var parts = tr.attr( 'id' ).split( '-' );

				var data = {
					action:            'pll_save_options',
					pll_ajax_settings: true,
					module:            parts[parts.length - 1],
					_pll_nonce:        $( '#_pll_nonce' ).val()
				};

				data = tr.find( ':input' ).serialize() + '&' + $.param( data );

				$.post(
					ajaxurl,
					data,
					function( response ) {
						// Target a non existing WP HTML id to avoid a conflict with WP ajax requests.
						var res = wpAjax.parseAjaxResponse( response, 'pll-ajax-response' );
						$.each(
							res.responses,
							function() {
								/**
								 * Fires after saving the settings, before applying changes to the DOM.
								 *
								 * @since 3.6.0
								 *
								 * @param {Object}      response The response from the AJAX call.
								 * @param {HTMLElement} tr       The HTML element containing the fields.
								 */
								wp.hooks.doAction( 'pll_settings_saved', this, tr.get( 0 ) );

								switch ( this.what ) {
									case 'license-update':
										$( '#pll-license-' + this.data ).replaceWith( this.supplemental.html );
									break;
									case 'success':
										tr.hide().prev().show(); // close only if there is no error
									case 'error':
										$( '.settings-error' ).remove(); // remove previous messages if any
										$( 'h1' ).after( this.data );

										// Make notices dismissible
										// copy paste of common.js from WP 4.2.2
										$( '.notice.is-dismissible' ).each(
											function() {
												var $this = $( this ),
													$button = $( '<button type="button" class="notice-dismiss"><span class="screen-reader-text"></span></button>' ),
													btnText = pll_admin.dismiss_notice || '';

												// Ensure plain text
												$button.find( '.screen-reader-text' ).text( btnText );

												// Whitelist because of how the button is built. See above
												$this.append( $button ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.append

												$button.on(
													'click.wp-dismiss-notice',
													function( event ) {
														event.preventDefault();
														$this.fadeTo(
															100,
															0,
															function() {
																$( this ).slideUp(
																	100,
																	function() {
																		$( this ).remove();
																	}
																);
															}
														);
													}
												);
											}
										);
									break;
								}
							}
						);
					}
				);
			}
		);

		// act when pressing enter or esc in configurations
		$( '.pll-configure' ).on(
			'keydown',
			function( event ){
				if ( 'Enter' === event.key ) {
					event.preventDefault();
					$( this ).find( '.save' ).trigger( 'click' );
				}

				if ( 'Escape' === event.key ) {
					event.preventDefault();
					$( this ).find( '.cancel' ).trigger( 'click' );
				}
			}
		);

		// settings URL modifications
		// manages visibility of fields
		$( "input[name='force_lang']" ).on(
			'change',
			function() {
				function pll_toggle( a, test ) {
					test ? a.show() : a.hide();
				}

				var value = $( this ).val();
				pll_toggle( $( '#pll-domains-table' ), 3 == value );
				pll_toggle( $( "#pll-hide-default" ), 3 > value );
				pll_toggle( $( "#pll-rewrite" ), 2 > value );
				pll_toggle( $( "#pll-redirect-lang" ), 2 > value );
			}
		);

		// settings license
		// deactivate button
		$( '.pll-deactivate-license' ).on(
			'click',
			function() {
				var data = {
					action:            'pll_deactivate_license',
					pll_ajax_settings: true,
					id:                $( this ).attr( 'id' ),
					_pll_nonce:        $( '#_pll_nonce' ).val()
				};
				$.post(
					ajaxurl,
					data,
					function( response ){
						$( '#pll-license-' + response.id ).replaceWith( response.html );
					}
				);
			}
		);

		// Manage closing the metabox.
		// close postboxes that should be closed
		$( '.if-js-closed' ).removeClass( 'if-js-closed' ).addClass( 'closed' );
		// postboxes setup
		if ( 'undefined' !== typeof postboxes ) {
			postboxes.add_postbox_toggles( pagenow );
		}
	}
);

