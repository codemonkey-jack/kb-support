<?php
/**
 * Ticket Functions
 *
 * @package     KBS
 * @subpackage  Tickets/Functions
 * @copyright   Copyright (c) 2017, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Get Tickets
 *
 * Retrieve tickets from the database.
 *
 * This is a simple wrapper for KBS_Tickets_Query.
 *
 * @since	1.0
 * @param	arr		$args		Arguments passed to get tickets
 * @return	obj		$tickets	Tickets retrieved from the database
 */
function kbs_get_tickets( $args = array() ) {

	// Fallback to post objects to ensure backwards compatibility
	if( ! isset( $args['output'] ) ) {
		$args['output'] = 'posts';
	}

	$args    = apply_filters( 'kbs_get_tickets_args', $args );
	$tickets = new KBS_Tickets_Query( $args );
	return $tickets->get_tickets();
} // kbs_get_tickets

/**
 * Retrieve a ticket.
 *
 * @since	1.0
 * @param	int	$ticket_id	The ticket ID.
 * @return	@see get_post()
 */
function kbs_get_ticket( $ticket_id )	{
	$ticket = new KBS_Ticket( $ticket_id );

	return apply_filters( 'kbs_get_ticket', $ticket, $ticket_id );
} // kbs_get_ticket

/**
 * Retrieve a ticket by a field.
 *
 * @since	1.0
 * @param	str			$field	The field by which to retrieve.
 * @param	mixed		$value	The value of the field.
 * @return	obj|false	The post object if found, otherwise false 
 */
function kbs_get_ticket_by( $field, $value )	{

	if ( 'id' == $field )	{
		$field = 'ID';
	}

	switch( $field )	{
		case 'ID':
		case 'id':
			return kbs_get_ticket( $value );
		break;

		case 'key':
			$args = array(
				'key'    => $value,
				'number' => 1
			);

			$tickets = kbs_get_tickets( $args );

			if ( ! empty( $tickets ) )	{
				return $tickets[0];
			}
			break;
	}

	return false;
} // kbs_get_ticket_by

/**
 * Retrieve ticket categories.
 *
 * @since	1.0
 * @param	arr		$args	See $defaults.
 * @return	obj		All ticket categories.
 */
function kbs_get_ticket_categories( $args = array() )	{
	$defaults = array(
		'taxonomy'      => 'ticket_category',
		'hide_empty'    => false,
		'orderby'       => 'name',
		'order'         => 'ASC'
	);
	
	$args = wp_parse_args( $args, $defaults );
	
	$ticket_categories = get_categories( $args );
	
	return apply_filters( 'kbs_get_ticket_categories', $ticket_categories, $args );
} // kbs_get_ticket_categories

/**
 * Ticket category options.
 *
 * @since	1.0
 * @return	arr		Array of ticket category options.
 */
function kbs_get_ticket_category_options()	{
	$options    = array();
	$categories = kbs_get_ticket_categories();

	if ( $categories )	{
		foreach( $categories as $category )	{
			$options[ absint( $category->term_id ) ] = $category->name;
		}
	}

	$options = apply_filters( 'kbs_ticket_category_options', $options );

	return $options;
} // kbs_get_ticket_category_options

/**
 * Count Tickets
 *
 * Returns the total number of tickets.
 *
 * @since	1.0
 * @param	arr	$args	List of arguments to base the ticket count on
 * @return	arr	$count	Number of tickets sorted by ticket date
 */
function kbs_count_tickets( $args = array() ) {

	global $wpdb;

	$defaults = array(
		'agent'      => null,
		'user'       => null,
		'customer'   => null,
		'company'    => null,
		's'          => null,
		'start-date' => null,
		'end-date'   => null
	);

	$args = wp_parse_args( $args, $defaults );

	$select = "SELECT p.post_status,count( * ) AS num_posts";
	$join = '';
	$where = "WHERE p.post_type = 'kbs_ticket'";

	// Count tickets for a search
	if ( ! empty( $args['s'] ) ) {

		if ( is_email( $args['s'] ) || strlen( $args['s'] ) == 32 ) {

			if( is_email( $args['s'] ) )	{
				$field = '_kbs_ticket_client';
			}

			$join = "LEFT JOIN $wpdb->postmeta m ON (p.ID = m.post_id)";
			$where .= $wpdb->prepare( "
				AND m.meta_key = %s
				AND m.meta_value = %s",
				$field,
				$args['s']
			);

		} elseif ( is_numeric( $args['s'] ) ) {

			$join = "LEFT JOIN $wpdb->postmeta m ON (p.ID = m.post_id)";
			$where .= $wpdb->prepare( "
				AND m.meta_key = %s
				AND m.meta_value = %d",
				'_kbs_ticket_client',
				$args['s']
			);

		} else {
			$search = $wpdb->esc_like( $args['s'] );
			$search = '%' . $search . '%';

			$where .= $wpdb->prepare( "AND ((p.post_title LIKE %s) OR (p.post_content LIKE %s))", $search, $search );
		}

	}

	// Limit ticket count by received date
	if ( ! empty( $args['start-date'] ) && false !== strpos( $args['start-date'], '-' ) ) {

		$date_parts = explode( '-', $args['start-date'] );
		$year       = ! empty( $date_parts[0] ) && is_numeric( $date_parts[0] ) ? $date_parts[0] : 0;
		$month      = ! empty( $date_parts[1] ) && is_numeric( $date_parts[1] ) ? $date_parts[1] : 0;
		$day        = ! empty( $date_parts[2] ) && is_numeric( $date_parts[2] ) ? $date_parts[2] : 0;

		$is_date    = checkdate( $month, $day, $year );
		if ( false !== $is_date ) {

			$date   = new DateTime( $args['start-date'] );
			$where .= $wpdb->prepare( " AND p.post_date >= '%s'", $date->format( 'Y-m-d' ) );

		}

		// Fixes an issue with the tickets list table counts when no end date is specified (partly with stats class)
		if ( empty( $args['end-date'] ) ) {
			$args['end-date'] = $args['start-date'];
		}

	}

	if ( ! empty ( $args['end-date'] ) && false !== strpos( $args['end-date'], '-' ) ) {

		$date_parts = explode( '-', $args['end-date'] );
		$year       = ! empty( $date_parts[0] ) && is_numeric( $date_parts[0] ) ? $date_parts[0] : 0;
		$month      = ! empty( $date_parts[1] ) && is_numeric( $date_parts[1] ) ? $date_parts[1] : 0;
		$day        = ! empty( $date_parts[2] ) && is_numeric( $date_parts[2] ) ? $date_parts[2] : 0;

		$is_date    = checkdate( $month, $day, $year );
		if ( false !== $is_date ) {

			$date   = new DateTime( $args['end-date'] );
			$where .= $wpdb->prepare( " AND p.post_date <= '%s'", $date->format( 'Y-m-d' ) );

		}

	}

	if ( ! empty( $args['customer'] ) )	{
		$join = "LEFT JOIN $wpdb->postmeta m ON (p.ID = m.post_id)";
		$where .= $wpdb->prepare( "
			AND m.meta_key = %s
			AND m.meta_value = %s",
			'_kbs_ticket_customer_id',
			$args['customer']
		);
	}

	if ( ! empty( $args['company'] ) )	{
		$join = "LEFT JOIN $wpdb->postmeta m ON (p.ID = m.post_id)";
		$where .= $wpdb->prepare( "
			AND m.meta_key = %s
			AND m.meta_value = %s",
			'_kbs_ticket_company_id',
			$args['company']
		);
	}

	if ( ! empty( $args['agent'] ) )	{
		$join = "LEFT JOIN $wpdb->postmeta m ON (p.ID = m.post_id)";
		$where .= $wpdb->prepare( "
			AND m.meta_key = %s
			AND m.meta_value = %d",
			'_kbs_ticket_agent_id',
			$args['agent']
		);
	}

	$where = apply_filters( 'kbs_count_tickets_where', $where );
	$join  = apply_filters( 'kbs_count_tickets_join', $join );

	$query = "$select
		FROM $wpdb->posts p
		$join
		$where
		GROUP BY p.post_status
	";

	$cache_key = md5( $query );

	$count = wp_cache_get( $cache_key, 'counts' );

	if ( false !== $count ) {
		return $count;
	}

	$count = $wpdb->get_results( $query, ARRAY_A );
	$stats    = array();
	$total    = 0;
	$statuses = kbs_get_ticket_status_keys( false );

	foreach ( $statuses as $state ) {
		$stats[ $state ] = 0;
	}

	foreach ( (array) $count as $row ) {
		if ( ! in_array( $row['post_status'], $statuses ) )	{
			continue;
		}
		$stats[ $row['post_status'] ] = $row['num_posts'];
	}

	$stats = (object) $stats;
	wp_cache_set( $cache_key, $stats, 'counts' );

	return $stats;
} // kbs_count_tickets

/**
 * Retrieve Open Ticket Count
 *
 * @since	1.0
 *
 * @return	int		Total number of currently open tickets
 */
function kbs_get_open_ticket_count()	{

	$tickets    = kbs_count_tickets();
	$open_count = 0;

	if ( ! empty( $tickets ) )	{
		$active_statuses   = kbs_get_active_ticket_status_keys();
		$inactive_statuses = kbs_get_inactive_ticket_statuses();

		foreach( $tickets as $status => $count )	{
			if ( ! empty( $tickets->$status ) && in_array( $status, $active_statuses ) && ! in_array( $status, $inactive_statuses ) )	{
				$open_count += $count;
			}
		}
	}

	return (int) $open_count;

} // kbs_get_open_ticket_count

/**
 * Get Ticket Status
 *
 * @since	1.0
 *
 * @param	mixed	WP_Post|KBS_Ticket|TicketID		$ticket		Ticket post object, KB_Ticket object, or ticket ID
 * @param	bool	$return_label					Whether to return the ticket status or not
 *
 * @return	bool|mixed	If ticket status exists, false otherwise
 */
function kbs_get_ticket_status( $ticket, $return_label = false ) {

	if( is_numeric( $ticket ) ) {

		$ticket = new KBS_Ticket( $ticket );

		if( ! $ticket->ID > 0 ) {
			return false;
		}

	}

	if ( ! is_object( $ticket ) || ! isset( $ticket->post_status ) ) {
		return false;
	}

	$statuses = kbs_get_ticket_statuses( false );

	if ( ! is_array( $statuses ) || empty( $statuses ) ) {
		return false;
	}

	$ticket = new KBS_Ticket( $ticket->ID );

	if ( array_key_exists( $ticket->status, $statuses ) ) {
		if ( true === $return_label ) {
			return $statuses[ $ticket->status ];
		} else {
			// Make sure we're matching cases, since they matter
			return array_search( strtolower( $post_status ), array_map( 'strtolower', $statuses ) );
		}
	}

	return false;
} // kbs_get_ticket_status

/**
 * Retrieve all ticket statuses.
 *
 * @since	1.0
 * @param	bool	$can_select		True to only return selectable status. False for all.
 * @return	arr
 */
function kbs_get_ticket_statuses( $can_select = true )	{
	$ticket_statuses = kbs_get_post_statuses( 'labels', $can_select );
	$statuses        = array();
	
	foreach ( $ticket_statuses as $ticket_status ) {
		$statuses[ $ticket_status->name ] = esc_html( $ticket_status->label );
	}

	$statuses = apply_filters( 'kbs_ticket_statuses', $statuses );

	return $statuses;
} // kbs_get_ticket_statuses

/**
 * Retrieves keys for all available ticket statuses.
 *
 * @since	1.0
 * @param	bool	$can_select		True to only return selectable status. False for all.
 * @return	arr		$ticket_status	All available ticket statuses
 */
function kbs_get_ticket_status_keys( $can_select = true ) {
	$statuses = array_keys( kbs_get_ticket_statuses( $can_select ) );
	asort( $statuses );

	return array_values( $statuses );
} // kbs_get_ticket_status_keys

/**
 * Retrieves keys for active ticket statuses.
 *
 * @since	1.0
 * @return	arr		Active ticket statuses
 */
function kbs_get_active_ticket_status_keys()	{
	$statuses = kbs_get_ticket_status_keys();
	$inactive = kbs_get_inactive_ticket_statuses();

	foreach( $inactive as $status )	{
		if ( in_array( $status, $statuses ) )	{
			unset( $statuses[ $status ] );
		}
	}

	return $statuses;
} // kbs_get_active_ticket_status_keys

/**
 * Retrieve inactive ticket statuses.
 *
 * @since	1.0
 * @return	arr		Array of inactive ticket statuses
 */
function kbs_get_inactive_ticket_statuses()	{
	$inactive = array( 'closed' );

	return apply_filters( 'kbs_inactive_ticket_statuses', $inactive );
} // kbs_get_inactive_ticket_statuses

/**
 * Retrieve the possible sources for logging a ticket.
 *
 * Custom sources can be added by hooking the `kbs_ticket_log_sources` filter.
 *
 * @since	1.0
 * @return	arr	Array of $key => value sources for logging a ticket.
 */
function kbs_get_ticket_log_sources()	{

	$sources = array(
		1  => __( 'Website', 'kb-support' ),
		2  => __( 'Email', 'kb-support' ),
		3  => __( 'Telephone', 'kb-support' ),
		99 => __( 'Other', 'kb-support' )
	);
	
	$sources = apply_filters( 'kbs_ticket_log_sources', $sources );
	
	if ( isset( $key ) )	{
		return $sources[ $key ];
	}
	
	return $sources;

} // kbs_get_ticket_log_sources

/**
 * Adds a new ticket.
 *
 * @since	1.0
 * @param	arr		$ticket_data	Ticket data.
 * @return	mixed	Ticket ID on success, false on failure.
 */
function kbs_add_ticket( $ticket_data )	{

	if ( ! empty( $ticket_data['attachments'] ) && ! is_array( $ticket_data['attachments'] ) )	{
		$ticket_data['attachments'] = array( $ticket_data['attachments'] );
	}

	$ticket_data = apply_filters( 'kbs_add_ticket_data', $ticket_data );
	$attachments = apply_filters( 'kbs_add_ticket_attachments', $ticket_data['attachments'] );
	$category    = array();

	$ticket = new KBS_Ticket();

	if ( ! empty( $ticket_data['post_category'] ) )	{
		if ( ! is_array( $ticket_data['post_category'] ) )	{
			$ticket_data['post_category'] = array( $ticket_data['post_category'] );
		}

		$category = array_map( 'intval', $ticket_data['post_category'] );
	}

	$ticket->status           = ! empty( $ticket_data['status'] )          ? $ticket_data['status']                : 'new';
	$ticket->ticket_title     = $ticket_data['post_title'];
	$ticket->ticket_content   = $ticket_data['post_content'];
	$ticket->ticket_category  = $category;
	$ticket->agent_id         = ! empty( $ticket_data['agent_id'] )        ? (int) $ticket_data['agent_id']        : '';
	$ticket->user_info        = $ticket_data['user_info'];
	$ticket->user_id          = ! empty( $ticket_data['user_info']['id'] ) ? (int) $ticket_data['user_info']['id'] : '';
	$ticket->email            = strtolower( sanitize_email( $ticket_data['user_email'] ) );
	$ticket->first_name       = ucfirst( sanitize_text_field( $ticket_data['user_info']['first_name'] ) );
    $ticket->last_name        = '';
	$ticket->email            = strtolower( sanitize_email( $ticket_data['user_info']['email'] ) );
	$ticket->ip               = kbs_get_ip();
	$ticket->sla_respond      = kbs_calculate_sla_target_response();
	$ticket->sla_resolve      = kbs_calculate_sla_target_resolution();
	$ticket->source           = '';
	$ticket->new_files        = $ticket_data['attachments'];
	$ticket->form_data        = ! empty( $ticket_data['form_data'] ) ? $ticket_data['form_data'] : '';

    if ( ! empty( $ticket_data['user_info']['last_name'] ) )  {
        $ticket->last_name = ucfirst( sanitize_text_field( $ticket_data['user_info']['last_name'] ) );
    }

	if ( isset( $ticket_data['post_date'] ) ) {
		$ticket->date = $ticket_data['post_date'];
	}

	do_action( 'kbs_before_add_ticket', $ticket->ID, $ticket_data );

	$ticket->save();

	if ( ! empty( $ticket->ID ) ) {
		do_action( 'kbs_add_ticket', $ticket->ID, $ticket_data );

		return $ticket->ID;
	}

	// Return false if no ticket was inserted
	return false;

} // kbs_add_ticket

/**
 * Adds a new ticket from a form submission.
 *
 * @since	1.0
 * @param	int		$form_id	Form ID
 * @param	arr		$form_data	Array of ticket data.
 * @return	mixed	Ticket ID on success, false on failure.
 */
function kbs_add_ticket_from_form( $form_id, $form_data )	{

	$kbs_form    = new KBS_Form( $form_id );
	$fields      = $kbs_form->fields;
	$data        = array();

	$ticket_data = array(
		'user_info'   => array(),
		'attachments' => array(),
		'form_data'   => array(
			'id'   => (int)$form_id,
			'data' => $form_data
		)
	);

	foreach( $fields as $field )	{

		$settings = $kbs_form->get_field_settings( $field->ID );

		if ( 'file_upload' == $settings['type'] && ! empty( $_FILES[ $field->post_name ] ) )	{
			$ticket_data['attachments'] = $_FILES[ $field->post_name ];
			continue;
		}

		if ( empty( $form_data[ $field->post_name ] ) )	{
			continue;
		}

		if ( ! empty( $settings['mapping'] ) )	{

			switch( $settings['mapping'] )	{
				case 'customer_first':
					$ticket_data['user_info']['first_name']       = ucfirst( $form_data[ $field->post_name ] );
					break;

				case 'customer_last':
					$ticket_data['user_info']['last_name']        = ucfirst( $form_data[ $field->post_name ] );
					break;

				case 'customer_email':
					$ticket_data['user_info']['email']            = strtolower( $form_data[ $field->post_name ] );
					$ticket_data['user_email']                    = $ticket_data['user_info']['email'];
					break;

				case 'customer_phone1':
					$ticket_data['user_info']['primary_phone']    = $form_data[ $field->post_name ];
					break;

				case 'customer_phone2':
					$ticket_data['user_info']['additional_phone'] = $form_data[ $field->post_name ];
					break;

				case 'customer_website':
					$ticket_data['user_info']['website']          = $form_data[ $field->post_name ];
					break;

				default:
					$ticket_data[ $settings['mapping'] ]          = $form_data[ $field->post_name ];
					break;
			}

		} else	{

			$ticket_data[ $field->post_name ] = array( $field->post_title, strip_tags( addslashes( $form_data[ $field->post_name ] ) ) );
		
			$data[] = '<strong>' . $field->post_title . '</strong><br />' . $form_data[ $field->post_name ];

		}
	}

	$ticket_data = apply_filters( 'kbs_add_ticket_from_form_data', $ticket_data, $form_id, $form_data );

	$ticket_id = kbs_add_ticket( $ticket_data );

	if ( $ticket_id )	{
		$kbs_form->increment_submissions();
		return $ticket_id;
	}

	return false;

} // kbs_add_ticket_from_form

/**
 * Record Submission In Log
 *
 * Stores log information for a ticket submission.
 *
 * @since	1.0
 * @global	$kbs_logs
 * @param	int			$ticket_id		Ticket ID
 * @param	int			$form_id		Form ID from which the ticket was submitted
 * @param	str|null	$submit_date	The date of the submission
 * @return	void
*/
function kbs_record_submission_in_log( $ticket_id = 0, $form_id = 0, $submit_date = null ) {
	global $kbs_logs;

	$log_data = array(
		'post_parent'   => $ticket_id,
		'log_type'      => 'submit',
		'post_date'     => ! empty( $submit_date ) ? $submit_date : null,
		'post_date_gmt' => ! empty( $submit_date ) ? get_gmt_from_date( $submit_date ) : null
	);

	$log_meta = array(
		'form_id' => $form_id
	);

	$kbs_logs->insert_log( $log_data, $log_meta );
} // kbs_record_submission_in_log

/**
 * Record Ticket Note Action In Log
 *
 * Stores log information for a ticket notes.
 *
 * @since	1.0
 * @global	$kbs_logs
 * @param	int			$ticket_id		Ticket ID
 * @param	int			$note_id		Note ID
 * @param	arr			$reply_data		Note data
 * @param	obj			$ticket			KBS_Ticket object
 * @return	void
*/
function kbs_record_note_in_log( $note_id = 0, $ticket_id = 0 ) {
	global $kbs_logs;

	$note = get_comment( $note_id );

	if ( $note )	{

		$log_data = array(
			'post_parent'   => $ticket_id,
			'log_type'      => 'note',
			'post_date'     => $note->comment_date,
			'post_date_gmt' => $note->comment_date_gmt
		);

		$log_meta = array(
			'note_id'  => $note_id,
			'note_by'  => $note->user_id,
			'reassign' => ! empty( $note_data['reassign'] )  ? $note_data['reassign']    : false
		);

		$kbs_logs->insert_log( $log_data, $log_meta );

	}
} // kbs_record_note_in_log
add_action( 'kbs_insert_ticket_note', 'kbs_record_note_in_log', 10, 2 );

/**
 * Update the status of a ticket.
 *
 * @since	1.0
 * @param	$ticket_id	The ticket ID
 * @param	$status		The status to be set for the ticket.
 * @return	mixed.
 */
function kbs_set_ticket_status( $ticket_id, $status = 'open' )	{

	if ( 'kbs_ticket' != get_post_type( $ticket_id ) )	{
		return false;
	}

	$ticket = new KBS_Ticket( $ticket_id );

	if ( $ticket->ID == 0 )	{
		return;
	}

	return $ticket->update_status( 'open' );

} // kbs_set_ticket_status

/**
 * Record Status Change In Log
 *
 * Stores log information for a ticket status change.
 *
 * @since	1.0
 * @global	$kbs_logs
 * @param	int			$ticket_id		Ticket ID
 * @param	str			$new_status		The new ticket status
 * @param	str			$old_status		The old ticket status
 * @param	str|null	$initiated_by	The email address of the user changing status
 * @return	void
*/
function kbs_record_status_change_in_log( $ticket_id = 0, $new_status, $old_status = 'new', $initiated_by = null ) {
	global $kbs_logs;

	$log_data = array(
		'post_parent'   => $ticket_id,
		'log_type'      => 'status'
	);

	if ( empty( $initiated_by ) )	{
		if ( is_user_logged_in() )	{
			$initiated_by = get_userdata( get_current_user_id() )->user_email;
		} else	{
			$initiated_by = kbs_get_ticket_user_email( $ticket_id );
		}
	}

	$initiated_by = apply_filters( 'kbs_ticket_status_change_initiated_by', $initiated_by, $ticket_id, $new_status, $old_status );

	$log_meta = array(
		'previous_status' => $old_status,
		'current_status'  => $new_status,
		'changed_by'      => $initiated_by
	);

	$kbs_logs->insert_log( $log_data, $log_meta );
} // kbs_record_status_change_in_log
add_action( 'kbs_update_ticket_status', 'kbs_record_status_change_in_log', 10, 3 );

/**
 * Record Agent Change In Log
 *
 * Stores log information for a ticket agent assignment change.
 *
 * @since	1.0
 * @global	$kbs_logs
 * @param	int			$ticket_id		Ticket ID
 * @param	str			$new_agent		The new ticket status
 * @param	str			$old_agent		The old ticket status
 * @param	str|null	$changed_by		The email address of the user changing agent
 * @return	void
*/
function kbs_record_agent_change_in_log( $ticket_id = 0, $new_agent = 0, $old_agent = 0, $changed_by = null ) {
	global $kbs_logs;

	$log_data = array(
		'post_parent'   => $ticket_id,
		'log_type'      => 'assign'
	);

	if ( empty( $changed_by ) )	{
		if ( is_user_logged_in() )	{
			$changed_by = get_userdata( get_current_user_id() )->user_email;
		}
	}

	$log_meta = array(
		'previous_agent'  => (int) $old_agent,
		'new_agent'       => (int) $new_agent,
		'changed_by'      => $changed_by
	);

	$kbs_logs->insert_log( $log_data, $log_meta );
} // kbs_record_agent_change_in_log

/**
 * Record Additional Agents Change In Log
 *
 * Stores log information for a ticket agents assignment change.
 *
 * @since	1.2
 * @global	$kbs_logs
 * @param	int			$ticket_id		Ticket ID
 * @param	str			$new_agent		The new ticket status
 * @param	str			$old_agent		The old ticket status
 * @param	str|null	$changed_by		The email address of the user changing agent
 * @return	void
*/
function kbs_record_additional_agents_change_in_log( $ticket_id = 0, $new_agent = 0, $old_agent = 0, $changed_by = null ) {
	global $kbs_logs;

	$log_data = array(
		'post_parent'   => $ticket_id,
		'log_type'      => 'assign'
	);

	if ( empty( $changed_by ) )	{
		if ( is_user_logged_in() )	{
			$changed_by = get_userdata( get_current_user_id() )->user_email;
		}
	}

	$log_meta = array(
		'previous_agents'  => (int) $old_agent,
		'new_agents'       => (int) $new_agent,
		'changed_by'       => $changed_by
	);

	$kbs_logs->insert_log( $log_data, $log_meta );
} // kbs_record_additional_agents_change_in_log

/**
 * Retrieve the ticket meta.
 *
 * @since	1.0
 * @param	int		$ticket_id	The ticket ID
 * @param	str		$meta_key	The individual key to retrieve
 * @param	bool	$single		Return single item or array
 * @return	arr	The ticket meta.
 */
function kbs_get_ticket_meta( $ticket_id, $meta_key = '', $single = true )	{
	$ticket = new KBS_Ticket( $ticket_id );

	return $ticket->get_meta( $meta_key, $single );
} // kbs_get_ticket_meta

/**
 * Update the meta for a ticket
 *
 * @param	int		$ticket_id 		Ticket ID
 * @param	str		$meta_key		Meta key to update
 * @param	str		$meta_value		Value to update to
 * @param	str		$prev_value		Previous value
 * @return	mixed	Meta ID if successful, false if unsuccessful
 */
function kbs_update_ticket_meta( $ticket_id = 0, $meta_key = '', $meta_value = '', $prev_value = '' ) {
	$ticket = new KBS_Ticket( $ticket_id );

	return $ticket->update_meta( $meta_key, $meta_value, $prev_value );
} // kbs_update_ticket_meta

/**
 * Get the user email associated with a ticket.
 *
 * @since	1.0
 * @param	int		$ticket_id	Ticket ID
 * @return	str		$email		User Email
 */
function kbs_get_ticket_user_email( $ticket_id ) {
	$ticket = new KBS_Ticket( $ticket_id );

	return $ticket->email;
} // kbs_get_ticket_user_email

/**
 * Retrieve the URL for a ticket.
 *
 * @since	1.0
 * @param	int		$ticket_id	The ticket ID.
 * @param	bool	$admin		True to retrieve the admin URL, false for front end.
 * @param	bool	$key		Whether to use the ticket key (for non logged in users) Front end only.
 * @return	str		The ticket URL
 */
function kbs_get_ticket_url( $ticket_id, $admin = false, $key = false )	{
	$scheme = null;
	
	if ( $admin )	{

		$scheme = defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ? 'https' : 'admin';
		$url    = add_query_arg( array(
			'post'   => $ticket_id,
			'action' => 'edit'
		), admin_url( 'post.php', $scheme ) );

	} else	{

		if ( $key )	{
			$args = array( 'key' => kbs_get_ticket_key( $ticket_id ) );
		} else	{
			$args = array( 'ticket' => $ticket_id );
		}

		$args['kbs_action'] = 'view_ticket';

		$url = add_query_arg( $args, site_url( '' ) );

		$url = apply_filters( 'kbs_ticket_url', $url, $ticket_id );

	}

	return $url;
} // kbs_get_ticket_url

/**
 * Retrieve the assigned agent.
 *
 * @since	1.0
 * @param	int	$ticket_id		The ticket ID
 * @return	int	The assigned agent ID.
 */
function kbs_get_agent( $ticket_id )	{
	$kbs_ticket = new KBS_Ticket( $ticket_id );
	
	return $kbs_ticket->agent_id;
} // kbs_get_agent

/**
 * Retrieve the unique ticket key.
 *
 * @since	1.0
 * @param	int		$ticket_id	The ticket ID
 * @return	str		The ticket key
 */
function kbs_get_ticket_key( $ticket_id )	{
	$ticket = new KBS_Ticket( $ticket_id );

	return $ticket->key;
} // kbs_get_ticket_key

/**
 * Get the ticket number
 *
 * This will return the ticket ID if sequential numbers are not enabled or the ticket number does not exist
 *
 * @since	1.1
 * @param	int		$ticket_id		Ticket ID
 * @return	str		Ticket number
 */
function kbs_get_ticket_number( $ticket_id = 0 ) {
	$ticket = new KBS_Ticket( $ticket_id );

	return $ticket->number;
} // kbs_get_ticket_number

/**
 * Formats the ticket number with the prefix and suffix
 *
 * @since	1.1
 * @param 	int		$number		The ticket number to format
 * @return	str		The formatted ticket number
 */
function kbs_format_ticket_number( $number )	{
	if ( ! is_numeric( $number ) )	{
		return $number;
	}

	$prefix  = kbs_get_option( 'ticket_prefix' );
	$number  = absint( $number );
	$postfix = kbs_get_option( 'ticket_suffix' );

	$formatted_number = $prefix . $number . $postfix;

	return apply_filters( 'kbs_format_ticket_number', $formatted_number, $prefix, $number, $postfix );
} // kbs_format_ticket_number

/**
 * Gets the next available ticket number
 *
 * This is used when inserting a new ticket
 *
 * @since	1.1
 * @return	str		$number		The next available ticket number
 */
function kbs_get_next_ticket_number()	{

	if ( ! kbs_get_option( 'enable_sequential' ) )	{
		return false;
	}

	$number           = get_option( 'kbs_last_ticket_number' );
	$start            = kbs_get_option( 'sequential_start', 1 );
	$increment_number = true;

	if ( $number )	{

		if ( empty( $number ) )	{
			$number = $start;
			$increment_number = false;
		}

	} else	{

		// This case handles the first addition of the new option, as well as if it get's deleted for any reason
		$tickets     = new KBS_Tickets_Query( array(
			'number'  => 1,
			'order'   => 'DESC',
			'orderby' => 'ID',
			'output'  => 'posts',
			'fields'  => 'ids'
		) );
		$last_ticket = $tickets->get_tickets();

		if ( ! empty( $last_ticket ) ) {
			$number = kbs_get_ticket_number( $last_ticket[0] );
		}

		if ( ! empty( $number ) && $number !== (int) $last_ticket[0] )	{
			$number = kbs_remove_ticket_prefix_postfix( $number );
		} else	{
			$number = $start;
			$increment_number = false;
		}

	}

	$increment_number = apply_filters( 'kbs_increment_ticket_number', $increment_number, $number );

	if ( $increment_number )	{
		$number++;
	}

	return apply_filters( 'kbs_get_next_ticket_number', $number );
} // kbs_get_next_ticket_number

/**
 * Given a given a number, remove the pre/suffix
 *
 * @since	1.1
 * @param 	str		$number  The formatted Current Number to increment
 * @return	str		The new Ticket number without prefix and suffix
 */
function kbs_remove_ticket_prefix_postfix( $number )	{
	$prefix = kbs_get_option( 'ticket_prefix' );
	$suffix = kbs_get_option( 'ticket_suffix' );

	// Remove prefix
	$number = preg_replace( '/' . $prefix . '/', '', $number, 1 );

	// Remove the suffix
	$length     = strlen( $number );
	$suffix_pos = strrpos( $number, $suffix );
	if ( false !== $suffix_pos )	{
		$number = substr_replace( $number, '', $suffix_pos, $length );
	}

	// Ensure it's a whole number
	$number = intval( $number );

	return apply_filters( 'kbs_remove_ticket_prefix_postfix', $number, $prefix, $suffix );
} // kbs_remove_ticket_prefix_postfix

/**
 * Assigns an agent to the ticket.
 *
 * @since	1.0
 * @param	int		$ticket_id	The ticket ID to update.
 * @param	int		$agent_id	The agent user ID. If not set, use current user.
 * @return	bool	True on success, false on failure
 */
function kbs_assign_agent( $ticket_id, $agent_id = 0 )	{

	if ( empty( $agent_id ) )	{
		$agent_id = get_current_user_id();
	}

	if ( ! kbs_is_agent( $agent_id ) )	{
		return false;
	}

	$ticket = new KBS_Ticket( $ticket_id );

	if ( empty( $ticket->ID ) )	{
		return false;
	}

	/**
	 * Fires immediately before assigning an agent
	 *
	 * @since	1.0
	 * @param	int	$ticket_id		The ticket ID
	 * @param	int	$agent_id		The agent user ID
	 */
	do_action( 'kbs_pre_assign_agent', $ticket_id, $agent_id );

	$ticket->__set( 'agent_id', $agent_id );
	$return = $ticket->save();

	/**
	 * Fires immediately after assigning an agent
	 *
	 * @since	1.0
	 * @param	int	$ticket_id		The ticket ID
	 * @param	int	$agent_id		The agent user ID
	 */
	do_action( 'kbs_post_assign_agent', $ticket_id, $agent_id );

	return $return;

} // kbs_assign_agent

/**
 * Retrieve the source used for logging the ticket.
 *
 * @since	1.0
 * @param	int	$ticket_id		The ticket ID
 * @return	str	The source.
 */
function kbs_get_ticket_source( $ticket_id )	{
	$kbs_ticket = new KBS_Ticket( $ticket_id );
	
	return $kbs_ticket->get_source();
} // kbs_get_ticket_source

/**
 * Re-open a closed ticket.
 *
 * @since	1.0
 * @param	arr	$data		$_GET super global.
 * @return	void.
 */
function kbs_reopen_ticket()	{

	if ( ! isset( $_GET['kbs-action'] ) || 're-open-ticket' != $_GET['kbs-action'] )	{
		return;
	}

	if( ! isset( $_GET['kbs-ticket-nonce'] ) || ! wp_verify_nonce( $_GET[ 'kbs-ticket-nonce' ], 'kbs-reopen-ticket' ) )	{
		$message = 'nonce_fail';
	} else	{
		remove_action( 'save_post_kbs_ticket', 'kbs_ticket_post_save', 10, 3 );
	
		if ( 'closed' == get_post_status( $_GET['post'] ) )	{
			$update = wp_update_post( array(
				'ID'          => $_GET['post'],
				'post_status' => 'open'
			) );
			
			if ( $update )	{
				$message = 'ticket_reopened';
				kbs_insert_note( $_GET['post'], sprintf( __( '%s re-opened.', 'kb-support' ), kbs_get_ticket_label_singular() ) ); 
			}
		}
		
		if ( ! isset( $message ) )	{
			$message = 'ticket_not_closed';
		}
		
	}
	
	$url = remove_query_arg( array( 'kbs-action', 'kbs-message', 'kbs-ticket-nonce' ) );
	
	wp_redirect( add_query_arg( 'kbs-message', $message, $url ) );

	die();

} // kbs_reopen_ticket
add_action( 'admin_init', 'kbs_reopen_ticket' );

/**
 * Update the ticket status to open if the status is currently new.
 *
 * This function is called from the `kbs_post_assign_agent` hook which is fired
 * after an agent is assigned to a ticket.
 *
 * @since	1.0
 * @param	int		$ticket_id	The Ticket ID
 * @return	void.
 */
function kbs_ticket_status_from_new_to_open( $ticket_id )	{
	if ( 'new' == get_post_status( $ticket_id ) )	{
		kbs_set_ticket_status( $ticket_id, 'open' );
	}
}
add_action( 'kbs_post_assign_agent', 'kbs_ticket_status_from_new_to_open' );

/**
 * Retrieve all ticket replies for the ticket.
 *
 * @since	1.0
 * @param	int		$ticket_id		The Ticket ID.
 * @param	arr		$args			See @get_posts
 * @return	obj|false
 */
function kbs_get_replies( $ticket_id = 0, $args = array() )	{
	if ( empty( $ticket_id ) )	{
		return false;
	}

	$ticket = new KBS_Ticket( $ticket_id );

	return $ticket->get_replies( $args );
} // kbs_get_replies

/**
 * Retrieve ticket reply count.
 *
 * @since	1.0
 * @param	int		$ticket_id		The Ticket ID.
 * @return	int
 */
function kbs_get_reply_count( $ticket_id )	{
	$ticket = new KBS_Ticket( $ticket_id );

	return $ticket->get_reply_count();
} // kbs_get_reply_count

/**
 * Whether or not an agent has replied to a ticket.
 *
 * @since	1.0
 * @param	int			$ticket_id		The Ticket ID.
 * @return	obj|false	
 */
function kbs_ticket_has_agent_reply( $ticket_id )	{
	$reply_args = array(
		'posts_per_page' => 1,
		'meta_query'     => array(
			'relation'    => 'AND',
			array(
				'key'     => '_kbs_reply_agent_id',
				'compare' => 'EXISTS'
			),
			array(
				'key'     => '_kbs_reply_agent_id',
				'value'   => '0',
				'compare' => '!='
			)
		)
	);

	return kbs_get_replies( $ticket_id, $reply_args );
} // kbs_ticket_has_agent_reply

/**
 * Retrieve the last reply for the ticket.
 *
 * @since	1.0
 * @uses	kbs_get_replies()
 * @param	int		$ticket_id		The Ticket ID.
 * @param	arr		$args			See @get_posts
 * @return	obj|false
 */
function kbs_get_last_reply( $ticket_id, $args = array() )	{
	$args['posts_per_page'] = 1;

	$reply = kbs_get_replies( $ticket_id, $args );

	if ( $reply )	{
		return $reply[0];
	}

	return $reply;
} // kbs_get_last_reply

/**
 * Gets the ticket reply HTML.
 *
 * @since	1.0
 * @param	obj|int	$reply		The reply object or ID
 * @param	int		$ticket_id	The ticket ID the reply is connected to
 * @return	str
 */
function kbs_get_reply_html( $reply, $ticket_id = 0 ) {

	if ( is_numeric( $reply ) ) {
		$reply = get_post( $reply );
	}

	$author      = kbs_get_reply_author_name( $reply, true );
	$date_format = get_option( 'date_format' ) . ', ' . get_option( 'time_format' );
	$files       = kbs_ticket_has_files( $reply->ID );
	$file_count  = ( $files ? count( $files ) : false );

	$create_article_link = add_query_arg( array(
		'kbs-action' => 'create_article',
		'ticket_id'  => $ticket_id,
		'reply_id'   => $reply->ID
	), admin_url() );

	$create_article_link = apply_filters( 'kbs_create_article_link', $create_article_link, $ticket_id, $reply );

	$reply_html  ='<h3>';
		$reply_html .= $author . '&nbsp;&ndash;&nbsp;' . date_i18n( $date_format, strtotime( $reply->post_date ) );
		if ( $file_count )	{
			$reply_html .= ' (' . $file_count . ' ' . _n( 'attached file', 'attached files', $file_count, 'kb-support' ) . ')';
		}
	$reply_html .= '</h3>';

	$reply_html .= '<div>';
		$reply_html .= '<p class="right">';
			$reply_html .= '<a class="create_article" href="' . $create_article_link . '">' . sprintf( __( 'Create %s', 'kb-support' ), kbs_get_article_label_singular() ) . '</a>';
		$reply_html .= '</p>';
		$reply_html .= wpautop( $reply->post_content );

		if ( $files )	{

			$reply_html .= '<ul>';

			foreach( $files as $file )	{
				$reply_html .= '<li>';
					$reply_html .= '<a href="' . wp_get_attachment_url( $file->ID ) . '" target="_blank">' . basename( get_attached_file( $file->ID ) ) . '</a>';
				$reply_html .= '</li>';
			}

			$reply_html .= '</ul>';
		}

	$reply_html .= '</div>';

	return $reply_html;

} // kbs_get_reply_html

/**
 * Retrieve the name of the person who replied to the ticket.
 *
 * @since	1.0
 * @param	obj|int	$reply		The reply object or ID
 * @param	bool	$role		Whether or not to include the role in the response
 * @return	str		The name of the person who authored the reply. If $role is true, their role in brackets
 */
function kbs_get_reply_author_name( $reply, $role = false )	{
	if ( is_numeric( $reply ) ) {
		$reply = get_post( $reply );
	}

	$author      = __( 'Unknown', 'kb-support' );
	$author_role = '';

	if ( ! empty( $reply->post_author ) ) {
		$author      = get_userdata( $reply->post_author );
		$author      = $author->display_name;

        if ( kbs_is_agent( $reply->post_author ))   {
            $author_role = __( 'Agent', 'kb-support' );
        } else  {
            $author_role = __( 'Customer', 'kb-support' );
        }
	} else {
		$customer_id = get_post_meta( $reply->ID, '_kbs_reply_customer_id', true );
		if ( $customer_id )	{
			$customer = new KBS_Customer( $customer_id );
			if ( $customer )	{
				$author      = $customer->name;
				$author_role = __( 'Customer', 'kb-support' );
			}
		}
	}

	if ( $role && ! empty( $author_role ) )	{
		$author .= ' (' . $author_role . ')';
	}

	return apply_filters( 'kbs_reply_author_name', $author, $reply, $role, $author_role );

} // kbs_get_reply_author_name

/**
 * Retrieve all notes attached to a ticket.
 *
 * @since	1.0
 * @param	int		$ticket_id	The ticket ID to retrieve notes for
 * @param	str		$search		Search for notes that contain a search term
 * @return	arr		$notes		Ticket Notes
 */
function kbs_get_notes( $ticket_id = 0, $search = '' ) {

	if ( empty( $ticket_id ) && empty( $search ) ) {
		return false;
	}

	remove_action( 'pre_get_comments', 'kbs_hide_notes', 10 );

	$notes = get_comments( array( 'post_id' => $ticket_id, 'search' => $search ) );

	add_action( 'pre_get_comments', 'kbs_hide_notes', 10 );

	return $notes;

} // kbs_get_notes

/**
 * Add a note to a ticket.
 *
 * @since	1.0
 * @param	int		$ticket_id	The ticket ID to store a note for
 * @param	str		$note		The note to store
 * @return	int		The new note ID
 */
function kbs_insert_note( $ticket_id = 0, $note = '' ) {

	if ( empty( $ticket_id ) )	{
		return false;
	}

	do_action( 'kbs_pre_insert_ticket_note', $ticket_id, $note );

	$note_id = wp_insert_comment( wp_filter_comment( array(
		'comment_post_ID'      => $ticket_id,
		'comment_content'      => $note,
		'user_id'              => is_admin() ? get_current_user_id() : 0,
		'comment_date'         => current_time( 'mysql' ),
		'comment_date_gmt'     => current_time( 'mysql', 1 ),
		'comment_approved'     => 1,
		'comment_parent'       => 0,
		'comment_author'       => '',
		'comment_author_IP'    => '',
		'comment_author_url'   => '',
		'comment_author_email' => '',
		'comment_type'         => 'kbs_ticket_note'

	) ) );

	do_action( 'kbs_insert_ticket_note', $note_id, $ticket_id, $note );

	if ( $note_id )	{
		wp_update_post( array( 'ID' => $ticket_id ) );
	}

	return $note_id;
} // kbs_insert_note

/**
 * Deletes a ticket note.
 *
 * @since	1.0
 * @param	int		$comment_id		The comment ID to delete
 * @param	int		$ticket_id		The ticket ID the note is connected to
 * @return	bool	True on success, false otherwise
 */
function kbs_delete_note( $comment_id = 0, $ticket_id = 0 ) {
	if( empty( $comment_id ) )
		return false;

	do_action( 'kbs_pre_delete_ticket_note', $comment_id, $ticket_id );
	$result = wp_delete_comment( $comment_id, true );
	do_action( 'kbs_post_delete_ticket_note', $comment_id, $ticket_id );

	return $result;
} // kbs_delete_note

/**
 * Gets the ticket note HTML.
 *
 * @since	1.0
 * @param	obj|int	$note		The comment object or ID
 * @param	int		$ticket_id	The ticket ID the note is connected to
 * @return	str
 */
function kbs_get_note_html( $note, $ticket_id = 0 ) {

	if ( is_numeric( $note ) ) {
		$note = get_comment( $note );
	}

	if ( ! empty( $note->user_id ) ) {
		$user = get_userdata( $note->user_id );
		$user = $user->display_name;
	} else {
		$user = __( 'KBS Bot', 'kb-support' );
	}

	$date_format = get_option( 'date_format' ) . ', ' . get_option( 'time_format' );

	$delete_note_url = wp_nonce_url( add_query_arg( array(
		'kbs-action' => 'delete_ticket_note',
		'note_id'    => $note->comment_ID,
		'ticket_id'  => $ticket_id
	), admin_url() ), 'kbs_delete_ticket_note_' . $note->comment_ID, 'kbs_note_nonce' );

	$note_html  ='<h3>';
		$note_html .= date_i18n( $date_format, strtotime( $note->comment_date ) ) . '&nbsp;&ndash;&nbsp;' . $user;
	$note_html .= '</h3>';

	$note_html .= '<div>';
		$note_html .= '<p class="kbs-delete"><a href="' . esc_url( $delete_note_url ) . '" class="kbs-delete">' . __( 'Delete', 'kb-support' ) . '</a></p>';
		$note_html .= wpautop( $note->comment_content );
	$note_html .= '</div>';

	return $note_html;

} // kbs_get_note_html

/**
 * Exclude notes (comments) on kbs_ticket post type from showing in Recent
 * Comments widgets.
 *
 * @since	1.0
 * @param	obj		$query	WordPress Comment Query Object
 * @return	void
 */
function kbs_hide_notes( $query ) {
	global $wp_version;

	if ( version_compare( floatval( $wp_version ), '4.1', '>=' ) ) {

		$types = isset( $query->query_vars['type__not_in'] ) ? $query->query_vars['type__not_in'] : array();

		if( ! is_array( $types ) ) {
			$types = array( $types );
		}

		$types[] = 'kbs_ticket_note';
		$query->query_vars['type__not_in'] = $types;

	}
} // kbs_hide_notes
add_action( 'pre_get_comments', 'kbs_hide_notes', 10 );

/**
 * Exclude notes (comments) on kbs_ticket post type from showing in comment feeds.
 *
 * @since	1.0
 * @param	arr		$where
 * @param	obj		$wp_comment_query	WordPress Comment Query Object
 * @return	arr		$where
 */
function kbs_hide_notes_from_feeds( $where, $wp_comment_query ) {
    global $wpdb;

	$where .= $wpdb->prepare( " AND comment_type != %s", 'kbs_ticket_note' );
	return $where;
} // kbs_hide_notes_from_feeds
add_filter( 'comment_feed_where', 'kbs_hide_notes_from_feeds', 10, 2 );


/**
 * Remove KBS Comments from the wp_count_comments function
 *
 * @since	1.0
 * @param	arr 	$stats		(empty from core filter)
 * @param	int		$post_id	Post ID
 * @return	arr		Array of comment counts
*/
function kbs_remove_notes_from_comment_counts( $stats, $post_id ) {
	global $wpdb, $pagenow;

	if( 'index.php' != $pagenow ) {
		return $stats;
	}

	$post_id = (int) $post_id;

	if ( apply_filters( 'kbs_count_ticket_notes_in_comments', false ) )	{
		return $stats;
	}

	$stats = wp_cache_get( "comments-{$post_id}", 'counts' );

	if ( false !== $stats )	{
		return $stats;
	}

	$where = 'WHERE comment_type != "kbs_ticket_note"';

	if ( $post_id > 0 )	{
		$where .= $wpdb->prepare( " AND comment_post_ID = %d", $post_id );
	}

	$count = $wpdb->get_results( "SELECT comment_approved, COUNT( * ) AS num_comments FROM {$wpdb->comments} {$where} GROUP BY comment_approved", ARRAY_A );

	$total = 0;
	$approved = array( '0' => 'moderated', '1' => 'approved', 'spam' => 'spam', 'trash' => 'trash', 'post-trashed' => 'post-trashed' );

	foreach ( (array) $count as $row ) {
		// Don't count post-trashed toward totals
		if ( 'post-trashed' != $row['comment_approved'] && 'trash' != $row['comment_approved'] )	{
			$total += $row['num_comments'];
		}

		if ( isset( $approved[ $row['comment_approved'] ] ) )	{
			$stats[ $approved[ $row['comment_approved'] ] ] = $row['num_comments'];
		}
	}

	$stats['total_comments'] = $total;

	foreach ( $approved as $key ) {
		if ( empty($stats[ $key ] ) )	{
			$stats[ $key ] = 0;
		}
	}

	$stats = (object) $stats;
	wp_cache_set( "comments-{$post_id}", $stats, 'counts' );

	return $stats;
} // kbs_remove_notes_from_comment_counts
add_filter( 'wp_count_comments', 'kbs_remove_notes_from_comment_counts', 10, 2 );

/**
 * The post types to be deleted when a ticket is deleted.
 *
 * @since	1.0
 * @return	arr		Array of post types to delete when a ticket is being deleted.
 */
function kbs_ticket_deleted_item_post_types()	{
	$post_types = array( 'kbs_ticket_reply', 'kbs_log' );
	return apply_filters( 'kbs_ticket_deleted_item_post_types', $post_types );
} // kbs_ticket_deleted_item_post_types
