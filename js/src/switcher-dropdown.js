/**
 * Handle the language switcher in dropdown mode.
 *
 * @package Polylang
 */

 document.querySelectorAll( ".pll-switcher-select" ).forEach(
	select => {
		select.addEventListener( "change",
			event => {
				location.href = event.currentTarget.value
			}
		)
	}
);
