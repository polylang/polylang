jQuery( document ).ready(function( $ ) {
	var widgets_container = $( '#widgets-right' );

	/**
	 * Prepend widget titles with a flag once a language is selected.
	 * @param {object} widget The widget element.
	 * @return {void} Nothing
	 */
	function add_flag( widget ) {
		widget = $( widget );
		var title = $( '.widget-top .widget-title h3', widget ),
			icon = $( '.pll-lang-choice option:selected', widget ).attr( 'flag' );

		if ( icon && '0' !== icon ) {
			var current = $( '.pll-lang img', title );
			if ( current.length ) {
				current.attr( 'src', icon );
			} else {
				var img  = '<img src="' + icon + '">',
					flag = '<span class="pll-lang">' + img + ' &nbsp; </span>';
				title.prepend( flag );
			}
		} else {
			$( '.pll-lang', title ).remove();
		}
	}

	$( '.widgets-holder-wrap .widget', widgets_container ).each( function() {
		add_flag( this );
	} );

	widgets_container.on( 'change', '.pll-lang-choice', function() {
		add_flag( $( this ).parents( '.widget' ) );
	} );
} );
