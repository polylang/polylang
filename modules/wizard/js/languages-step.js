/**
 * @package Polylang
 */

jQuery( document ).ready(
	function( $ ) {
		var addLanguageForm = $( '.languages-step' ); // Form element.
		var languageFields = $( '#language-fields' ); // Element where to append hidden fields for creating language.
		var languagesTable = $( '#languages' ); // Table element contains languages list to create.
		var languagesListTable = $( '#languages tbody' ); // Table rows with languages list to create.
		var definedLanguagesListTable = $( '#defined-languages tbody' ); // Table rows with already defined languages list.
		var languagesList = $( '#lang_list' ); // Select form element with predefined languages without already created languages.
		var nextStepButton = $( '[name="save_step"]' ); // The button for continuing to the next step.
		var messagesContainer = $( '#messages' ); // Element where to display error messages.
		var languagesMap = new Map(); // Languages map object for managing the languages to create.
		var dialog = $( '#dialog' ); // Dialog box for alerting the language selected has not been added to the list.

		/**
		 * Add a language in the list to create it in Polylang settings
		 *
		 * @param {object} language The language object
		 */
		function addLanguage( language ) {
			// language properties come from the select dropdown which is built server side and well escaped.
			// see template view-wizard-step-languages.php.
			var languageValueHtml = $( '<td />' ).text( language.text ).prepend( language.flagUrl ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.prepend
			var languageTrashIconHtml = $( '<td />' )
				.append( // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.append
					$( '<span />' )
					.addClass( 'dashicons dashicons-trash' )
					.attr( 'data-language', language.locale )
					.append( // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.append
						$( '<span />' )
						.addClass( 'screen-reader-text' )
						.text( pll_wizard_params.i18n_remove_language_icon )
					)
				);
			// see the comment and the harcoded code above. languageTrashIconHtml and languageValueHtml are safe.
			var languageLineHtml = $( '<tr />' ).prepend( languageTrashIconHtml ).prepend( languageValueHtml ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.prepend
			var languageFieldHtml = $( '<input />' ).attr(
				{
					type: 'hidden',
					name: 'languages[]'
				}
			).val( language.locale );

			languagesList.val( '' );
			languagesList.selectmenu( 'refresh' ); // Refresh jQuery selectmenu widget after changing the value.

			languagesMap.set( language.locale, language );

			// see above how languageLineHtml is built.
			languagesListTable.append( languageLineHtml ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.append
			// Bind click event on trash icon.
			languagesListTable.on(
				'click',
				'span[data-language=' + language.locale + ']',
				function( event ) {
					event.preventDefault();
					// Remove line in languages table.
					$( this ).parents( 'tr' ).remove();
					// Remove input field.
					languageField = languageFields.children( 'input[value=' + $( this ).data( 'language' ) + ']' ).remove();
					// If there is no more languages hide languages table.
					if ( languagesListTable.children().length <= 0 ) {
						languagesTable.hide();
					}
					// Remove language from the Map.
					languagesMap.delete( $( this ).data( 'language' ) );
					// Hide error message.
					hideError();
				}
			);
			// see above how languageFieldHtml is built.
			// Add hidden input field for posting the form.
			languageFields.append( languageFieldHtml ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.append

		}

		/**
		 * Display an error message
		 *
		 * @param {string} message The message to display
		 */
		function showError( message ) {
			messagesContainer.empty();
			// html is harcoded and use of jQuery text method which is safe to add message value.
			// In addition message is i18n value which is initialized server side in PLL_Wizard::add_step_languages and correctly escaped.
			messagesContainer.prepend( $( '<p/>' ).addClass( 'error' ).text( message ) ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.prepend
		}

		/**
		 * Hide all error messages and fields in error
		 */
		function hideError() {
			messagesContainer.empty();
			addLanguageForm.find( '.error' ).removeClass( 'error field-in-error' );
		}

		/**
		 * Style the field to indicate where the error is
		 *
		 * @param {object} field The jQuery element which is in error
		 */
		function showFieldInError( field ) {
			field.addClass( 'error field-in-error' );
		}

		/**
		 * Focus on a specific element
		 *
		 * @param {object} field The jQuery element which will be focused
		 */
		function focusOnField( field ) {
			field.focus();
		}

		/**
		 * Disable a specific button
		 *
		 * @param {object} button
		 */
		function disableButton( button ){
			button.prop( 'disabled', true );
			// Because the button is disabled we need to add the value of the button to ensure it will pass in the request.
			addLanguageForm.append( // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.append
				$( '<input />' ).prop(
					{
						type: 'hidden',
						name: button.prop( 'name' ),
						value: button.prop( 'value' )
					}
				)
			);
		}

		/**
		 * Remove error when a new selection is done in languages list.
		 */
		languagesList.on(
			'selectmenuchange',
			function() {
				hideError();;
			}
		);
		/**
		 * Bind click event on "Add language" button
		 */
		$( '#add-language' ).on(
			'click',
			function( event ) {
				hideError();
				var selectedOption = event.currentTarget.form.lang_list.options[event.currentTarget.form.lang_list.selectedIndex];
				if ( '' !== selectedOption.value && ! languagesMap.has( selectedOption.value ) ) {
					addLanguage(
						{
							locale: selectedOption.value,
							text: selectedOption.innerText,
							name: $( selectedOption ).data( 'language-name' ),
							flagUrl: $( selectedOption ).data( 'flag-html' )
						}
					);
					// Show table of languages.
					languagesTable.show();
					// Put back the focus on the select language field after clicking on "Add language button".
					focusOnField( $( '#lang_list-button' ) );
				} else {
					var message = pll_wizard_params.i18n_no_language_selected;
					if ( languagesMap.has( selectedOption.value ) ) {
						message = pll_wizard_params.i18n_language_already_added;
					}
					showError( message );
					showFieldInError( languagesList.next( 'span.ui-selectmenu-button' ) );
					focusOnField( $( '#lang_list-button' ) );

				}
			}
		);

		/**
		 * Bind submit event on "add_lang" form
		 */
		addLanguageForm.on(
			'submit',
			function( event ) {
				// Verify if there is at least one language.
				var isLanguagesAlreadyDefined = definedLanguagesListTable.children().length > 0;
				var selectedLanguage = $( '#lang_list' ).val();
				if ( languagesMap.size <= 0 && ! isLanguagesAlreadyDefined ) {
					if ( '' === selectedLanguage ) {
						showError( pll_wizard_params.i18n_no_language_added );
						showFieldInError( languagesList.next( 'span.ui-selectmenu-button' ) );
						focusOnField( $( '#lang_list-button' ) );
					} else {
						showError( pll_wizard_params.i18n_add_language_needed );
						showFieldInError( languagesList.next( 'span.ui-selectmenu-button' ) );
						focusOnField( $( '#add-language' ) ); // Put the focus on the "Add language" button.
					}
					return false;
				}
				// Verify if the language has been added in the list otherwise display a dialog box to confirm what to do.
				if ( '' !== selectedLanguage ) {
					// Verify we don't add a duplicate language before opening the dialog box otherwise display an error message.
					if ( ! languagesMap.has( selectedLanguage ) ) {
						dialog.dialog( 'open' );
					} else {
						showError( pll_wizard_params.i18n_language_already_added );
						showFieldInError( languagesList.next( 'span.ui-selectmenu-button' ) );
						focusOnField( $( '#lang_list-button' ) );
					}
					return false;
				}
				disableButton( nextStepButton );
			}
		);

		// Is there an error return by PHP ?
		var searchParams = new URLSearchParams( document.location.search );
		if ( searchParams.has( 'activate_error' ) ) {
			// If the error code exists, display it.
			if ( undefined !== pll_wizard_params[ searchParams.get( 'activate_error' ) ] ) {
				showError( pll_wizard_params[ searchParams.get( 'activate_error' ) ] );
			}
		}

		function confirmDialog( what ) {
			switch ( what ) {
				case 'yes':
					var selectedOption = $( '#lang_list' ).children( ':selected' );
					addLanguage(
						{
							locale: selectedOption[0].value,
							text: selectedOption[0].innerText,
							name: $( selectedOption ).data( 'language-name' ),
							flagUrl: $( selectedOption ).data( 'flag-html' )
						}
					);
					break;
				case 'no':
					// Empty select form field and submit again the form.
					languagesList.val( '' );
					break;
				case 'ignore':
			}
			dialog.dialog( 'close' );
			if ( 'ignore' === what ) {
				focusOnField( $( '#lang_list-button' ) );
			} else {
				addLanguageForm.submit();
			}
		}

		// Initialize dialog box in the case a language is selected but not added in the list.
		dialog.dialog(
			{
				autoOpen: false,
				modal: true,
				draggable: false,
				resizable: false,
				title: pll_wizard_params.i18n_dialog_title,
				minWidth: 600,
				maxWidth: '100%',
				open: function( event, ui ) {
					// Change dialog box position for rtl language
					if ( $( 'body' ).hasClass( 'rtl' ) ) {
						$( this ).parent().css(
							{
								right: $( this ).parent().css( 'left' ),
								left: 'auto'
							}
						);
					}
					// Display language name and flag information in dialog box.
					$( this ).find( '#dialog-language' ).text( $( '#lang_list' ).children( ':selected' )[0].innerText );
					// language properties come from the select dropdown #lang_list which is built server side and well escaped.
					// see template view-wizard-step-languages.php.
					$( this ).find( '#dialog-language-flag' ).empty().prepend( $( '#lang_list' ).children( ':selected' ).data( 'flag-html' ) ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.prepend
				},
				buttons: [
				{
					text: pll_wizard_params.i18n_dialog_yes_button,
					click: function( event ) {
						confirmDialog( 'yes' );
					}
				},
				{
					text: pll_wizard_params.i18n_dialog_no_button,
					click: function( event ) {
						confirmDialog( 'no' );
					}
				},
				{
					text: pll_wizard_params.i18n_dialog_ignore_button,
					click: function( event ) {
						confirmDialog( 'ignore' );
					}
				}
				]
			}
		)
	}
);
