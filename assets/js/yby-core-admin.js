/*
 * YBY Core admin JS
 */
( function ( $ ) {
	$( document ).on( 'click', '.yby-media-button', function ( event ) {
		event.preventDefault();

		var targetId = $( this ).data( 'yby-media-target' );
		var frame = wp.media( {
			title: 'Select Media',
			button: {
				text: 'Use this file',
			},
			multiple: false,
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			$( '#' + targetId ).val( attachment.url ).trigger( 'change' );
		} );

		frame.open();
	} );
}( jQuery ) );
