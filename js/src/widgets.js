/**
 * Adds a flag to the widgets filtered by a language.
 *
 * @package Polylang
 */

jQuery(
	function( $ ) {
		var widgets_container,
			widgets_selector,
			flags,
			isBlockEditor = 'undefined' !== typeof wp.blockEditor;

		if ( 'undefined' !== typeof pll_widgets && pll_widgets.hasOwnProperty( 'flags' ) ) {
			flags = pll_widgets.flags;
		}

		/**
		 * Prepend widget titles with a flag once a language is selected.
		 *
		 * @param {object} widget The widget element.
		 * @return {void} Nothing.
		 */
		function add_flag( widget ) {
			if ( ! flags ) {
				return;
			}
			widget = $( widget );
			var title  = isBlockEditor ? widget.prev('h3') : $( '.widget-top .widget-title h3', widget ),
				locale = $( '.pll-lang-choice option:selected', widget ).val(),
				// Icon is HTML built and come from server side and is well escaped when necessary
				icon = ( locale && flags.hasOwnProperty( locale ) ) ? flags[ locale ] : null;

			if ( icon ) {
				icon += ' &nbsp; ';
				var current = $( '.pll-lang', title );
				if ( current.length ) {
					current.html( icon ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
				} else {
					flag = $( '<span />' ).addClass( 'pll-lang' ).html( icon );  // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.html
					// See the comment above about the icon which is safe. So it is also safe to prepend flag which uses icon.
					title.prepend( flag ); // phpcs:ignore WordPressVIPMinimum.JS.HTMLExecutingFunctions.prepend
				}
			} else {
				$( '.pll-lang', title ).remove();
			}
		}

		if ( isBlockEditor ) {

			widgets_container = $( '.edit-widgets-main-block-list' );
			widgets_selector  = '.widget';

			// Update flags when we click on the legacy widget to display its form.
			widgets_container.on(
				'click',
				'.wp-block-legacy-widget',
				function() {
					add_flag( $( this ).find( '.widget' ) );
				}
			);

		} else {
			if ( 'undefined' !== typeof wp.customize ) {

				widgets_container = $( '#customize-controls' );
				widgets_selector  = '.customize-control .widget';

				/**
				 * WP Customizer add control listener.
				 *
				 * @link https://wordpress.stackexchange.com/questions/256536/callback-after-wordpress-customizer-complete-loading
				 *
				 * @param {object} control The control type.
				 * @return {void} Nothing.
				 */
				function customize_add_flag( control ) {
					if ( ! control.extended( wp.customize.Widgets.WidgetControl ) ) {
						return;
					}

					/*
					* Make sure the widget's contents are embedded; normally this is done
					* when the control is expanded, for DOM performance reasons.
					*/
					control.embedWidgetContent();

					// Now we know for sure the widget is fully embedded.
					add_flag( control.container.find( '.widget' ) );
				}
				wp.customize.control.each( customize_add_flag );
				wp.customize.control.bind( 'add', customize_add_flag );

			} else {

				widgets_container = $( '#widgets-right' );
				widgets_selector  = '.widget';

			}

			// Add flags on load.
			$( widgets_selector, widgets_container ).each(
				function() {
					add_flag( this );
				}
			);
		}

		// Update flags.
		widgets_container.on(
			'change',
			'.pll-lang-choice',
			function() {
				add_flag( $( this ).parents( '.widget' ) );
			}
		);

		function pll_toggle( a, test ) {
			test ? a.show() : a.hide();
		}

		// Remove all options if dropdown is checked.
		$( '.widgets-sortables,.control-section-sidebar,.edit-widgets-main-block-list' ).on(
			'change',
			'.pll-dropdown',
			function() {
				var this_id = $( this ).parent().parent().parent().children( '.widget-id' ).attr( 'value' );
				pll_toggle( $( '.no-dropdown-' + this_id ), true != $( this ).prop( 'checked' ) );
			}
		);

		// Disallow unchecking both show names and show flags.
		var options = ['-show_flags', '-show_names'];
		$.each(
			options,
			function( i, v ) {
				$( '.widgets-sortables,.control-section-sidebar,.edit-widgets-main-block-list' ).on(
					'change',
					'.pll' + v,
					function() {
						var this_id = $( this ).parent().parent().parent().children( '.widget-id' ).attr( 'value' );
						if ( true != $( this ).prop( 'checked' ) ) {
							$( '#widget-' + this_id + options[ 1 - i ] ).prop( 'checked', true );
						}
					}
				);
			}
		);

	}
);
