/**
 * @package Polylang
 */

jQuery( document ).ready(
	function( $ ) {
		// Add a boolean variable to be able to check jQuery UI >= 1.12 which is introduced in WP 5.6.
		// Backward compatibility WP < 5.6
		var isJqueryUImin112 = $.ui.version >= '1.12.0';
		// Allow to check if a flag list dropdown is present. Not present in the Wizard steps or other settings page.
		var flagListExist = $( "#flag_list" ).length;
		// Allow to check if a language list dropdown is present. Not present in other settings page.
		var langListExist = $( "#lang_list" ).length;

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

		// Overrides the flag dropdown list with our customized jquery ui selectmenu.
		// Common functions for overriding language and flag dropdown list.

		// Inject flag image when jQuery UI selectmenu is created or an item is selected.
		// jQuery UI 1.12 introduce a wrapper inside de li tag which is necessary to selectmenu widget to work correctly.
		// Mainly copy from the orginal jQuery UI 1.12 selectmenu widget _renderItem method.
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

		// Selectmenu widget parameters.
		// Callbacks when Selectmenu widget create or select event is triggered.
		var createSelectCallback = function( event, ui ) {
			selectmenuRefreshButtonText( event.target );
		}
		// Callbacks when Selectmenu widget open event is triggered.
		// Needed to corectly refresh the selected element in the list.
		var openCallback = function( event, ui ){
			if ( isJqueryUImin112 ) {
				// Just a refresh of the menu is needed with jQuery UI 1.12 because _renderButtonItem is triggered and then inject correctly the flag.
				$( event.target ).selectmenu( 'refresh' );
			} else {
				selectmenuRefreshButtonText( $( event.target ).selectmenu( 'refresh' ) );
			}
		}

		// Selectmenu widget options
		// jQuery UI selectmenu widget substract 2% and we need 95% for the width matches to the other fields width.
		var selectmenuOptions = { width: '97%'};
		var selectmenuFlagListCallbacks = {};

		// Create the jQuery UI selectmenu widget for flag list dropdown and return its instance.
		// There is no need of create and select callbacks with jQuery UI 1.12 because overrinding _renderButtonItem method do the job.
		if ( isJqueryUImin112 ) {
			selectmenuFlagListCallbacks =
				{
					open: openCallback,
				};
		} else {
			selectmenuFlagListCallbacks = {
				create: createSelectCallback,
				select: createSelectCallback,
				open: openCallback,
			};
		}

		// Create the selectmenu widget only if the field is present.
		if ( flagListExist ) {
			var selectmenuFlagList = $( '#flag_list' ).selectmenu( Object.assign( {}, selectmenuOptions, selectmenuFlagListCallbacks ) ).selectmenu( 'instance' );
			// Overrides each item in the jQuery UI selectmenu list by injecting flag image.
			selectmenuFlagList._renderItem = selectmenuRenderItem;
			// Override the selected item rendering for jQuery UI 1.12
			if ( isJqueryUImin112 ) {
				selectmenuFlagList._renderButtonItem = selectmenuRenderButtonItem;
				selectmenuFlagList.refresh(); // Need to refresh to take in account the button item rendering method after the selectmenu widget instanciaion.
			}
		}
		// Language choice in predefined languages in Polylang Languages settings page and wizard.
		// Overrides the predefined language dropdown list with our customized jQuery ui selectmenu widget.

		// Callback when selectmenu widget change event is triggered.
		var changeCallback = function( event, ui ) {
			var value = $( event.target ).val().split( ':' );
			var selected = $( "option:selected", event.target ).text().split( ' - ' );
			$( '#lang_slug' ).val( value[0] );
			$( '#lang_locale' ).val( value[1] );
			$( 'input[name="rtl"]' ).val( [value[2]] );
			$( '#lang_name' ).val( selected[0] );

			// Refresh the flag field only if it's present.
			if ( flagListExist ) {
				$( '#flag_list').val( value[3] );

				// Refresh the jQuery UI selectmenu flag list.
				if ( isJqueryUImin112 ) {
					// Just a refresh of the menu is needed with jQuery UI 1.12 because _renderButtonItem is triggered and then inject correctly the flag.
					selectmenuFlagList.refresh();
				} else {
					selectmenuRefreshButtonText( $( '#flag_list').selectmenu( 'refresh' ) );
				}
			}
		};

		// Create the jQuery UI selectmenu widget language list dropdown and return its instance.
		var selectmenuLangListCallbacks = {};
		// For the wizard we need a 100% width. So we override the previous defined value of selectmenuOptions. Remind that jQuery UI selectmenu widget substract 2% to this value.
		if( $( '#lang_list' ).closest( '.pll-wizard-content' ).length > 0 ) {
			selectmenuOptions = Object.assign( selectmenuOptions, { width: '102%' } );
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
			var selectmenuLangList = $( '#lang_list' ).selectmenu( Object.assign( {}, selectmenuOptions, selectmenuLangListCallbacks ) ).selectmenu( 'instance' );
			// Overrides each element in the jQuery UI selectmenu list by injecting flag image.
			selectmenuLangList._renderItem = selectmenuRenderItem;
			// Override the selected item rendering for jQuery UI 1.12
			if ( isJqueryUImin112 ) {
				selectmenuLangList._renderButtonItem = selectmenuRenderButtonItem;
				selectmenuLangList.refresh(); // Need to refresh to take in account the button item rendering method after the selectmenu widget instanciaion.
			}
		}

		// strings translations
		// save translations when pressing enter
		$( '.translation input' ).keypress(
			function( event ){
				if ( 13 === event.keyCode ) {
					event.preventDefault();
					$( '#submit' ).click();
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
						var res = wpAjax.parseAjaxResponse( response, 'ajax-response' );
						$.each(
							res.responses,
							function() {
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
													btnText = pll_dismiss_notice || '';

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
		$( '.pll-configure' ).keypress(
			function( event ){
				if ( 13 === event.keyCode ) {
					event.preventDefault();
					$( this ).find( '.save' ).click();
				}

				if ( 27 === event.keyCode ) {
					event.preventDefault();
					$( this ).find( '.cancel' ).click();
				}
			}
		);

		// settings URL modifications
		// manages visibility of fields
		$( "input[name='force_lang']" ).change(
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
