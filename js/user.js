jQuery( document ).ready(function( $ ) {
	// biography
	// FIXME there is probably a more efficient way to do this
	var td = $( '#description' ).parent();
	var d = $( '#description' ).clone();
	var span = td.children( '.description' ).clone();
	td.children().remove();

	$( '.biography' ).each(function(){
		lang = $( this ).attr( 'name' ).split( '___' );
		desc = d.clone();
		desc.attr( 'name', 'description_' + lang[0] );
		desc.html( $( this ).val() );
		var langInner = $( '<div></div>' );
		langInner.text(lang[1]);
		td.append( langInner );
		td.append( desc );
	});

	td.append( '<br />' );
	td.append( span );
});
