jQuery( document ).ready(
	function( $ ) {
		$( '#lang_list' ).on(
			'selectmenucreate selectmenuselect',
			function( event, item ){
				$( '#lang_list' ).selectmenu( 'instance' ).buttonText.prepend( $( this ).children( ':selected' ).data( 'flag-html' ) );
			}
		);
		$( '#lang_list' ).selectmenu( { width: '100%' } );
		$( '#lang_list' ).selectmenu( 'instance' )._renderItem = function( wrapper, item ){
			var el = $( item.element );
			var img = el.data( 'flag-html' );
			var li = $(
				'<li>',
				{
					text: item.label,
					value: item.value
				}
			);
			li.prepend( img );
			li.data( 'value', item.value );
			return li.appendTo( wrapper );
		};
	}
);
