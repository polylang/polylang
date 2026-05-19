/**
 * Allows to open/close the language switcher's submenus when the "dropdown" layout is used.
 */

export default class Dropdown {
	constructor( button ) {
		this.button = button;
		this.closeOnClickOutsideBinded = this.closeOnClickOutside.bind( this );

		button.setAttribute( 'aria-expanded', 'false' );

		button.parentNode.addEventListener(
			'mouseenter',
			this.open.bind( this )
		);
		button.parentNode.addEventListener(
			'mouseleave',
			this.close.bind( this )
		);
		button.addEventListener( 'click', this.toggle.bind( this ) );
		button.nextElementSibling.addEventListener(
			'keydown',
			this.closeOnListKeydown.bind( this )
		);
		button.nextElementSibling
			.querySelector( 'li:last-child > a' )
			.addEventListener( 'blur', this.closeOnLastLinkBlur.bind( this ) );
	}

	/**
	 * Opens the submenu.
	 */
	open() {
		this.button.setAttribute( 'aria-expanded', 'true' );
		document.addEventListener( 'click', this.closeOnClickOutsideBinded );
	}

	/**
	 * Closes the submenu.
	 */
	close() {
		this.button.setAttribute( 'aria-expanded', 'false' );
		document.removeEventListener( 'click', this.closeOnClickOutsideBinded );
	}

	/**
	 * Toggles the submenu.
	 */
	toggle() {
		const expanded = this.button.getAttribute( 'aria-expanded' );
		if ( 'true' === expanded ) {
			this.close();
		} else {
			this.open();
		}
	}

	/**
	 * Event callback that closes the submenu when pressing the Escape key while in the list.
	 *
	 * @param {Event} event The event.
	 */
	closeOnListKeydown( event ) {
		if ( event.key === 'Escape' ) {
			this.button.focus();
			this.close();
		}
	}

	/**
	 * Event callback that closes the submenu when the last link in the list looses focus (to a target outside the list).
	 *
	 * @param {Event} event The event.
	 */
	closeOnLastLinkBlur( event ) {
		if ( ! this.button.parentNode.contains( event.relatedTarget ) ) {
			this.close();
		}
	}

	/**
	 * Event callback that closes the submenu when the user clicks outside the dropdown.
	 *
	 * @param {Event} event The event.
	 */
	closeOnClickOutside( event ) {
		if ( ! this.button.parentNode.contains( event.target ) ) {
			this.close();
		}
	}
}
