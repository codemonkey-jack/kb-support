jQuery( document ).ready( function ( $ ) {

	var action_bar = $( '#helptain-action-bar' );

	// Admin ticket action bar action clicks
	action_bar.on( 'click', 'li.helptain-action-button a', function ( event ) {

		event.preventDefault();

		var action               = $( this ).data( 'action' ),
		    reply_wrapper        = $( '#kbs-ticket-reply-wrap' ),
		    note_wrapper         = $( '#kbs-ticket-add-note-container' ),
		    status_select        = $( '#helptain_status_select' ),
		    other_action_buttons = $( this ).parents( '#helptain-action-bar' ).find( 'li.helptain-action-button a' ).not( $( this ) );

		$( this ).toggleClass( 'active' );
		other_action_buttons.removeClass( 'active' );

		switch ( action ) {
			case 'show_reply_editor':
				reply_wrapper.toggleClass( 'helptain-hide' );
				note_wrapper.addClass( 'helptain-hide' );
				status_select.addClass( 'helptain-hide' );
				break;
			case 'show_note_editor':
				note_wrapper.toggleClass( 'helptain-hide' );
				reply_wrapper.addClass( 'helptain-hide' );
				status_select.addClass( 'helptain-hide' );
				break;
			case 'set_status':
				status_select.toggleClass( 'helptain-hide' );
				break;
			default:
				jQuery( document ).trigger( 'helptain_action_bar_action_' + action, $( this ) );
				break;
		}
	} );

	// Set ticket status
	action_bar.on( 'click', 'ul#helptain_status_select li', function ( e ) {
		e.preventDefault;

		var $status = $( this ).attr( 'status' ),
		    $action = 'kbs_ajax_update_ticket_status',
		    $nonce  = $( this ).parent().attr( 'nonce' ),
		    $id     = $( 'input#post_ID' ).val(),
			$list = $(this);


		$.ajax( {
			type    : 'POST',
			dataType: 'json',
			data    : {
				action   : $action,
				status   : $status,
				nonce    : $nonce,
				ticket_id: $id
			},
			url     : kbs_vars.ajax_url,
			success : function ( response ) {
				console.log(response.error);
				if ( response.error ) {

				} else {
					$list.parent().after('<p class="test">test</p>');
					setTimeout(function(){
						$list.parent().find('p.test').remove();
					},1000);
				}
			}
		} ).fail( function ( data ) {
			if ( window.console && window.console.log ) {
				//console.log( data );
			}
		} );
	} );
} );
