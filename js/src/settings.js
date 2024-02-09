/**
 * @package Polylang
 */

/**
 * Settings: attaches an event to `.pll-ajax-button` buttons to trigger AJAX requests.
 */
const pllMachineTranslationAjaxButton = {
	init: () => {
		if ( document.readyState !== 'loading' ) {
			pllMachineTranslationAjaxButton.attachEvent();
		} else {
			document.addEventListener( 'DOMContentLoaded', pllMachineTranslationAjaxButton.attachEvent );
		}
	},
	attachEvent: () => {
		document.querySelectorAll( '.pll-ajax-button' ).forEach( ( el ) => {
			el.addEventListener( 'click', ( event ) => {
				const button    = event.target;
				const action    = button.getAttribute( 'data-action' );
				const nonce     = button.getAttribute( 'data-nonce' );
				const row       = button.closest( 'tr' );
				const errorElms = row.querySelectorAll( '.pll-error-message-text' );

				if ( ! action || ! nonce || ! row || ! errorElms.length ) {
					return;
				}

				const urlParams = { 'action': action, '_pll_nonce': nonce, 'pll_ajax_settings': 1 };
				row.querySelectorAll( '[data-name]' ).forEach( ( el ) => {
					urlParams[ el.getAttribute( 'data-name' ) ] = el.value;
				} );
				const url = wp.url.addQueryArgs( ajaxurl, urlParams );

				button.setAttribute( 'disabled', 'disabled' );
				row.classList.remove( 'notice-success', 'notice-error', 'notice-alt' );

				fetch( url ).then( ( response ) => {
					return response.json();
				} ).then( ( json ) => {
					button.removeAttribute( 'disabled' );

					if ( json.success ) {
						row.classList.add( 'notice-success', 'notice-alt' );
					} else {
						errorElms[0].textContent = json.data ? json.data : pll_admin.unknown_error;
						row.classList.add( 'notice-error', 'notice-alt' );
					}
				} ).catch( () => {
					button.removeAttribute( 'disabled' );
					errorElms[0].textContent = pll_admin.unknown_error;
					row.classList.add( 'notice-error', 'notice-alt' );
				} )
			} );
		} );
	}
};

pllMachineTranslationAjaxButton.init();
