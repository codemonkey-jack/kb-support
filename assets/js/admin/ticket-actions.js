jQuery( document ).ready( function ( $ ) {

	var action_bar = $( '#helptain-action-bar' );

	/**
	 * Admin ticket action bar action clicks
	 */
	action_bar.on( 'click', 'li.helptain-action-button a', function ( event ) {

		event.preventDefault();

		var action               = $( this ).data( 'action' ),
		    reply_wrapper        = $( '#kbs-ticket-reply-wrap' ),
		    note_wrapper         = $( '#kbs-ticket-add-note-container' ),
		    status_select        = $( '#helptain_status_select' ),
		    agent_select         = $( '#helptain_agent_select' ),
		    other_action_buttons = $( this ).parents( '#helptain-action-bar' ).find( 'li.helptain-action-button a' ).not( $( this ) );

		$( this ).toggleClass( 'active' );
		other_action_buttons.removeClass( 'active' );

		switch ( action ) {
			case 'show_reply_editor':
				reply_wrapper.toggleClass( 'helptain-hide' );
				note_wrapper.addClass( 'helptain-hide' );
				status_select.addClass( 'helptain-hide' );
				agent_select.addClass( 'helptain-hide' );

				$( 'html,body' ).animate( {
					scrollTop: (reply_wrapper.offset().top - 80)
				}, 600 );

				break;
			case 'show_note_editor':
				note_wrapper.toggleClass( 'helptain-hide' );
				reply_wrapper.addClass( 'helptain-hide' );
				status_select.addClass( 'helptain-hide' );
				agent_select.addClass( 'helptain-hide' );

				$( 'html,body' ).animate( {
					scrollTop: (note_wrapper.offset().top - 80)
				}, 600 );

				break;
			case 'set_status':
				status_select.toggleClass( 'helptain-hide' );
				agent_select.addClass( 'helptain-hide' );
				break;
			case 'assign_ticket':
				agent_select.toggleClass( 'helptain-hide' );
				status_select.addClass( 'helptain-hide' );
				break;
			default:
				jQuery( document ).trigger( 'helptain_action_bar_action_' + action, $( this ) );
				break;
		}
	} );

	/**
	 * Set ticket status
	 */
	action_bar.on( 'click', 'ul#helptain_status_select li', function ( e ) {
		e.preventDefault;

		var $status = $( this ).attr( 'status' ),
		    $action = 'kbs_ajax_update_ticket_status',
		    $nonce  = $( this ).parent().attr( 'nonce' ),
		    $id     = $( 'input#post_ID' ).val(),
		    $list   = $( this );


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
				if ( 'undefined' == typeof response || !response || response.error ) {
					console.log( 'php function returned false' );
				} else {
					$list.parents( 'ul.helptain-action-buttons' ).find( 'li.ticket-status' ).html( 'Status: ' + response.status ).css( 'background-color', response.status_color );
				}
			}
		} ).fail( function ( data ) {
			if ( window.console && window.console.log ) {
				console.log( data );
			}
		} );
	} );

	/**
	 * Set ticket agent
	 */
	action_bar.on( 'click', 'ul#helptain_agent_select li', function ( e ) {
		e.preventDefault;

		var $agent  = $( this ).attr( 'agent_id' ),
		    $action = 'kbs_ajax_update_ticket_agent',
		    $nonce  = $( this ).parent().attr( 'nonce' ),
		    $id     = $( 'input#post_ID' ).val(),
		    $list   = $( this );


		$.ajax( {
			type    : 'POST',
			dataType: 'json',
			data    : {
				action   : $action,
				agent    : $agent,
				nonce    : $nonce,
				ticket_id: $id
			},
			url     : kbs_vars.ajax_url,
			success : function ( response ) {

				if ( 'undefined' == typeof response || !response || response.error ) {
					console.log( 'php function returned false' );
				} else {
					$list.parent().find( 'li' ).removeClass( 'active' );
					$list.addClass( 'active' );

				}
			}
		} ).fail( function ( data ) {
			if ( window.console && window.console.log ) {
				console.log( data );
			}
		} );
	} );

	/**
	 * Toggle the reply/note row actions
	 */
	$( 'html body' ).on( 'click', '.kbs_historic_replies_wrapper a.helptain-admin-row-actions-toggle', function ( e ) {
		e.preventDefault();

		var toggle          = $( this ),
		    actions_wrapper = toggle.parent().find( '.helptain-admin-row-actions' );

		actions_wrapper.toggleClass( 'helptain-hide' );
	} );
} );
