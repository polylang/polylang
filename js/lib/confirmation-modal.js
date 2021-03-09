/**
 * @package Polylang
 */

const languagesList = jQuery( '.post_lang_choice' );

// Dialog box for alerting the user about a risky changing.
export const initializeConfimationModal = () => {
	// We can't use underscore or lodash in this common code because it depends of the context classic or block editor.
	// Classic editor underscore is loaded, Block editor lodash is loaded.
	const { __ } = wp.i18n;

	// Create dialog container.
	const dialogContainer = jQuery(
		'<div/>',
		{
			id: 'pll-dialog',
			style: 'display:none;'
		}
	).text( __( 'Are you sure you want to change the language of the current content?', 'polylang' ) );

	// Put it after languages list dropdown.
	// PHPCS ignore dialogContainer is a new safe HTML code generated above.
	languagesList.after( dialogContainer ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.after

	const dialogResult = new Promise(
		( confirm, cancel ) => {
			const confirmDialog = ( what ) => { // phpcs:ignore PEAR.Functions.FunctionCallSignature.Indent
				switch ( what ) { // phpcs:ignore PEAR.Functions.FunctionCallSignature.Indent
					case 'yes':
						// Confirm the new language.
						languagesList.data( 'old-value', languagesList.children( ':selected' )[0].value );
						confirm();
						break;
					case 'no':
						// Revert to the old language.
						languagesList.val( languagesList.data( 'old-value' ) );
						cancel( 'Cancel' );
						break;
				}
				dialogContainer.dialog( 'close' ); // phpcs:ignore PEAR.Functions.FunctionCallSignature.Indent
			} // phpcs:ignore PEAR.Functions.FunctionCallSignature.Indent

			// Initialize dialog box in the case a language is selected but not added in the list.
			dialogContainer.dialog(
				{
					autoOpen: false,
					modal: true,
					draggable: false,
					resizable: false,
					title: __( 'Change language', 'polylang' ),
					minWidth: 600,
					maxWidth: '100%',
					classes: {
						'ui-dialog': 'pll-confirmation-modal',
					},
					open: function( event, ui ) {
						// Change dialog box position for rtl language
						if ( jQuery( 'body' ).hasClass( 'rtl' ) ) {
							jQuery( this ).parent().css(
								{
									right: jQuery( this ).parent().css( 'left' ),
									left: 'auto'
								}
							);
						}
					},
					close: function( event, ui ) {
						// When we're closing the dialog box we need to cancel the language change as we click on Cancel button.
						confirmDialog( 'no' );
					},
					buttons: [
					{
						text: __( 'OK', 'polylang' ),
						click: function( event ) {
							confirmDialog( 'yes' );
						}
					},
					{
						text: __( 'Cancel', 'polylang' ),
						click: function( event ) {
							confirmDialog( 'no' );
						}
					}				]
				}
			);
		}
	);
	return { dialogContainer, dialogResult };
}

export const initializeLanguageOldValue = () => {
	// Keep the old language value to be able to compare to the new one and revert to it if necessary.
	languagesList.attr( 'data-old-value', languagesList.children( ':selected' )[0].value );
};
