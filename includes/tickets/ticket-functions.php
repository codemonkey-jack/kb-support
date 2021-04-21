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
 * Whether sequential ticket numbers are in use.
 *
 * @since	1.3
 * @return	bool
 */
function kbs_use_sequential_ticket_numbers()	{
	return kbs_get_option( 'enable_sequential', false );
} // kbs_use_sequential_ticket_numbers

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
 * @param	int|object	id_or_object	The ticket ID or a KBS_Ticket object.
 * @return	@see get_post()
 */
function kbs_get_ticket( $id_or_object )	{
	if ( is_numeric( $id_or_object ) )	{
		$ticket = new KBS_Ticket( $id_or_object );
	} else	{
		$ticket = $id_or_object;
	}

	return apply_filters( 'kbs_get_ticket', $ticket, $id_or_object );
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
 * Whether or not a ticket is flagged.
 *
 * @since   1.5.3
 * @param   int|object  $ticket Ticket ID or a KBS_Ticket object
 * @return  bool        True if flagged, or false
 */
function kbs_is_ticket_flagged( $ticket )   {
    if ( is_numeric( $ticket ) ) {
		$ticket = new KBS_Ticket( $ticket );

		if ( ! $ticket->ID > 0 ) {
			return false;
		}
	}

	if ( ! is_object( $ticket ) ) {
		return false;
	}

    return $ticket->flagged;
} // kbs_is_ticket_flagged

/**
 * Flag/unflag a ticket.
 *
 * @since   1.5.3
 * @param   int|object  $ticket     Ticket ID or a KBS_Ticket object
 * @param   int         $user_id    ID of user setting flag status
 * @param   bool        $flag       True to flag a ticket, or false to unflag
 * @return  bool        Ticket flag status
 */
function kbs_set_ticket_flag_status( $ticket, $user_id = 0, $flag = true )    {
    if ( is_numeric( $ticket ) ) {
		$ticket = new KBS_Ticket( $ticket );

		if ( ! $ticket->ID > 0 ) {
			return false;
		}
	}

	if ( ! is_object( $ticket ) ) {
		return false;
	}

    if ( $ticket->flagged !== $flag )   {
        $ticket->set_flagged_status( $flag, $user_id );
    }

    return $ticket->flagged;
} // kbs_set_ticket_flag_status

/*
 * Retrieve ticket orderby options.
 *
 * @since	1.4
 * @return	array	Array of ticket orderby options
 */
function kbs_get_ticket_orderby_options()	{
	$options = array(
		'ID'       => __( 'ID', 'kb-support' ),
		'title'    => __( 'Subject', 'kb-support' ),
		'date'     => __( 'Date Modified', 'kb-support' ),
	);

	$options = apply_filters( 'kbs_ticket_orderby_options', $options );

	return $options;
} // kbs_get_ticket_orderby_options

/**
 * Count Tickets
 *
 * Returns the total number of tickets.
 *
 * @param arr $args List of arguments to base the ticket count on
 *
 * @return    arr    $count    Number of tickets sorted by ticket date
 * @throws Exception
 * @since    1.0
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
 * @return	int		Total number of currently open tickets
 */
function kbs_get_open_ticket_count( $status = false )	{

	$tickets         = kbs_count_tickets();
	$open_count      = 0;
    $active_statuses = $status;

	if ( ! empty( $tickets ) )	{
        if ( ! $active_statuses )   {
            $active_statuses   = kbs_get_active_ticket_status_keys();
        } else  {
            if ( ! is_array( $active_statuses ) )   {
                $active_statuses = array( $active_statuses );
            }
        }
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
			return $ticket->status_nicename;
		} else {
			// Make sure we're matching cases, since they matter
			return array_search( strtolower( $post_status ), array_map( 'strtolower', $statuses ) );
		}
	}

	return false;
} // kbs_get_ticket_status

/**
 * Retrieve default ticket statuses.
 *
 * @since	1.4
 * @param	bool	$can_select		True to only return selectable status. False for all.
 * @return	arr
 */
function kbs_get_default_ticket_statuses( $can_select = true )	{
	$default_statuses = array( 'open', 'new', 'hold', 'closed' );

	$default_statuses = apply_filters( 'kbs_default_ticket_statuses', $default_statuses );

	return $default_statuses;
} // kbs_get_default_ticket_statuses

/**
 * Get the ticket status colours.
 *
 * @since	1.4
 * @param	string	$status		Ticket status name
 * @param	bool	$default	Whether or not to return the default colour
 * @return	string	Ticket status colour
 */
function kbs_get_ticket_status_colour( $status, $default = false )	{
	$defaults = apply_filters( 'kbs_default_ticket_status_colours', array(
        'all'    => '#868686',
        'new'    => '#827a93',
		'open'   => '#82b74b',
		'hold'   => '#dd9933',
		'closed' => '#dd3333'
	) );

	if ( $default )	{
		if ( ! array_key_exists( $status, $defaults ) )	{
			$status = 'open';
		}

		return $defaults[ $status ];
	}

	$default_colour = '';

	if ( array_key_exists( $status, $defaults ) )	{
		$default_colour = $defaults[ $status ];
	}

	$colour = kbs_get_option( 'colour_' . $status, $default_colour );
	$colour = apply_filters( 'kbs_ticket_status_colour_' . $status, $colour );

	return $colour;
} // kbs_get_ticket_status_colour

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
    $defaults        = kbs_get_default_ticket_statuses();

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
function kbs_get_active_ticket_status_keys( $can_select = true )	{
	$statuses = kbs_get_ticket_status_keys( $can_select );
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
 * Retrieve ticket source terms.
 *
 * A simple helper function for get_terms()
 *
 * @since   1.2.9
 * @param   array   $args   Array of args to parse
 * @return  array|int|WP_Error List of WP_Term instances
 */
function kbs_get_ticket_source_terms( $args = array() )  {
    $defaults = array(
        'taxonomy'   => 'ticket_source',
        'hide_empty' => false
    );

    $args = wp_parse_args( $args, $defaults );

    return get_terms( $args );
} // kbs_get_ticket_source_terms

/**
 * Retrieve protected ticket source terms.
 *
 * @since   1.2.9
 * @return  Array of term objects
 */
function kbs_get_protected_ticket_source_term_ids()    {
    $protected = array();
    $sources   = kbs_get_ticket_source_terms();

    if ( ! empty( $sources ) )  {
        foreach( $sources as $source )  {
            if ( false !== strpos( $source->slug, 'kbs-' ) )    {
                $protected[] = $source->term_id;
            }
        }
    }

    return $protected;
} // kbs_get_protected_ticket_source_term_ids

/**
 * Retrieve the possible sources for logging a ticket.
 *
 * Custom sources can be added by hooking the `kbs_ticket_log_sources` filter.
 *
 * @since	1.0
 * @return	arr	Array of $key => value sources for logging a ticket.
 */
function kbs_get_ticket_log_sources()	{
    $categories = kbs_get_ticket_source_terms();
	$sources    = array();

    foreach ( $categories as $category ) {
        $sources[ absint( $category->term_id ) ] = esc_html( $category->name );
    }

	$sources = apply_filters( 'kbs_ticket_log_sources', $sources );

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

    /**
     * Remove action that triggers agent assignment email for existing tickets.
     *
     */
    remove_action( 'kbs_update_ticket_meta_key', 'kbs_trigger_agent_assigned_email', 999, 4 );

	$ticket_data = apply_filters( 'kbs_add_ticket_data', $ticket_data );
	$attachments = apply_filters( 'kbs_add_ticket_attachments', $ticket_data['attachments'] );
	$category    = array();
	$department  = '';

	$ticket = new KBS_Ticket();

	if ( ! empty( $ticket_data['department'] ) )	{
		$department = intval( $ticket_data['department'] );
	}

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
	$ticket->department       = $department;
	$ticket->user_info        = $ticket_data['user_info'];
	$ticket->user_id          = ! empty( $ticket_data['user_info']['id'] ) ? (int) $ticket_data['user_info']['id'] : '';
	$ticket->email            = strtolower( sanitize_email( $ticket_data['user_email'] ) );
	$ticket->first_name       = ucfirst( sanitize_text_field( $ticket_data['user_info']['first_name'] ) );
	$ticket->last_name        = '';
	$ticket->email            = strtolower( sanitize_email( $ticket_data['user_info']['email'] ) );
	$ticket->participants     = ! empty( $ticket_data['participants'] ) ? $ticket_data['participants'] : array( $ticket->email );
	$ticket->ip               = kbs_get_ip();
	$ticket->sla_respond      = kbs_calculate_sla_target_response();
	$ticket->sla_resolve      = kbs_calculate_sla_target_resolution();
	$ticket->new_files        = $ticket_data['attachments'];
	$ticket->form_data        = ! empty( $ticket_data['form_data'] ) ? $ticket_data['form_data'] : array();
	$ticket->privacy_accepted = isset( $ticket_data['privacy_accepted'] ) ? $ticket_data['privacy_accepted'] : false;
	$ticket->terms_agreed     = isset( $ticket_data['terms_agreed'] ) ? $ticket_data['terms_agreed'] : false;

    if ( ! empty( $ticket_data['user_info']['last_name'] ) )  {
        $ticket->last_name = ucfirst( sanitize_text_field( $ticket_data['user_info']['last_name'] ) );
    }

	if ( isset( $ticket_data['post_date'] ) ) {
		$ticket->date = $ticket_data['post_date'];
	}

	if ( ! empty( $ticket_data['source'] ) )	{
		$ticket->source = sanitize_text_field( $ticket_data['source'] );
	}

    if ( ! empty( $ticket_data['submission_origin'] ) )    {
        $ticket->submission_origin = $ticket_data['submission_origin'];
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

	$kbs_form         = new KBS_Form( $form_id );
	$fields           = $kbs_form->fields;
	$data             = array();
	$privacy_accepted = false;
	$terms_agreed     = false;

    if ( ! empty( $form_data['submission_origin'] ) )  {
        $submission_origin = $form_data['submission_origin'];
        unset( $form_data['submission_origin'] );
    }

	if ( isset( $form_data['privacy_accepted'] ) )	{
		$privacy_accepted = $form_data['privacy_accepted'];
		unset( $form_data['privacy_accepted'] );
	}

	if ( isset( $form_data['terms_agreed'] ) )	{
		$terms_agreed = $form_data['terms_agreed'];
		unset( $form_data['terms_agreed'] );
	}

	$ticket_data = array(
		'user_info'         => array(),
		'attachments'       => array(),
        'submission_origin' => $submission_origin,
		'privacy_accepted'  => $privacy_accepted,
		'terms_agreed'      => $terms_agreed,
		'form_data'         => array(
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
				case 'customer_email':
					$ticket_data['user_info']['email']            = strtolower( $form_data[ $field->post_name ] );
					$ticket_data['user_email']                    = $ticket_data['user_info']['email'];
					break;

				case 'customer_first':
					$ticket_data['user_info']['first_name']       = ucfirst( $form_data[ $field->post_name ] );
					break;

				case 'customer_last':
					$ticket_data['user_info']['last_name']        = ucfirst( $form_data[ $field->post_name ] );
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
	$ticket_id   = kbs_add_ticket( $ticket_data );

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

	return $ticket->update_status( $status );
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

	}

	$url = apply_filters( 'kbs_ticket_url', $url, $ticket_id, $admin, $key );

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

	if ( ! kbs_use_sequential_ticket_numbers() )	{
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

	$ticket->__set( 'agent_id', $agent_id );
	$return = $ticket->save();

	return $return;

} // kbs_assign_agent

/**
 * Assigns additional agents to a ticket.
 *
 * @since	1.1.11
 * @param	int|obj  $ticket      The ticket ID or object to update.
 * @param	int|arr  $agent_ids   The agent user ID's to add to the ticket.
 * @return	bool     True on success, false on failure
 */
function kbs_add_agents_to_ticket( $ticket, $agent_ids )	{

    if ( ! is_array( $agent_ids ) )	{
		$agent_ids = array( $agent_ids );
	}

    $new_agents = array();

    foreach( $agent_ids as $agent_id )  {

        if ( ! kbs_is_agent( $agent_id ) )	{
            continue;
        }

        $new_agents[] = $agent_id;

    }

    if ( empty( $new_agents ) ) {
        return false;
    }

    if ( is_numeric( $ticket ) ) {
        $ticket = new KBS_Ticket( $ticket );
    }

	if ( empty( $ticket->ID ) )	{
		return false;
	}

    $current_agents = $ticket->agents;
    $new_agents     = array_merge( $new_agents, $current_agents );

    /**
	 * Fires immediately before adding agents
	 *
	 * @since	1.0
	 * @param	obj	$ticket  		The ticket object
	 * @param	arr	$new_agents		The agent user IDs
	 */
	do_action( 'kbs_pre_add_agents_to_ticket', $ticket, $new_agents );

    $ticket->__set( 'agents', $new_agents );
    $return = $ticket->save();

    /**
	 * Fires immediately after adding agents
	 *
	 * @since	1.0
	 * @param	obj      $ticket  		The ticket object
	 * @param	arr  	 $new_agents	The agent user IDs
     * @param   bool     $return        Whether or not the ticket was saved
	 */
	do_action( 'kbs_post_add_agents_to_ticket', $ticket, $new_agents, $return );

    return $return;

} // kbs_add_agents_to_ticket

/**
 * Removes additional agents from a ticket.
 *
 * @since	1.1.11
 * @param	int|obj  $ticket      The ticket ID or object to update.
 * @param	int|arr  $agent_ids   The agent user ID's to remove from the ticket.
 * @return	bool     True on success, false on failure
 */
function kbs_remove_agents_from_ticket( $ticket, $agent_ids )   {

    if ( empty( $agent_ids ) )  {
        return false;
    }

    if ( ! is_array( $agent_ids ) )	{
		$agent_ids = array( $agent_ids );
	}

    if ( is_numeric( $ticket ) ) {
        $ticket = new KBS_Ticket( $ticket );
    }

	if ( empty( $ticket->ID ) )	{
		return false;
	}

    $agents = $ticket->agents;

    foreach( $agents as $agent_id ) {
        if ( ( $key = array_search( $agent_id, $agents ) ) !== false )  {
            unset( $agents[ $key ] );
        }
    }

    /**
	 * Fires immediately before adding agents
	 *
	 * @since	1.0
	 * @param	obj	$ticket  		The ticket object
	 * @param	arr	$new_agents		The agent user IDs
	 */
	do_action( 'kbs_pre_remove_agents_from_ticket', $ticket, $agent_ids );

    $ticket->__set( 'agents', $agents );
    $return = $ticket->save();

    /**
	 * Fires immediately after adding agents
	 *
	 * @since	1.0
	 * @param	obj      $ticket  		The ticket object
	 * @param	arr  	 $new_agents	The agent user IDs
     * @param   bool     $return        Whether or not the ticket was saved
	 */
	do_action( 'kbs_post_remove_agents_from_ticket', $ticket, $agents, $return );

    return $return;

} // kbs_remove_agents_from_ticket

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
add_action( 'kbs_assigned_agent', 'kbs_ticket_status_from_new_to_open' );

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
 * @param	arr		$args		Array of arguments to pass the wp_insert_comment function
 * @return	int		The new note ID
 */
function kbs_insert_note( $ticket_id = 0, $note = '', $args = array() ) {

	if ( empty( $ticket_id ) )	{
		return false;
	}

	$defaults = array(
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
	);

	$args = wp_parse_args( $args, $defaults );

	do_action( 'kbs_pre_insert_ticket_note', $ticket_id, $note, $args );

	$note_id = wp_insert_comment( wp_filter_comment( $args ) );

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

	$delete_note_cap = apply_filters( 'kbs_delete_note_cap', 'manage_ticket_settings', $note, $user );
	$date_format     = get_option( 'date_format' ) . ', ' . get_option( 'time_format' );

	$delete_note_url = wp_nonce_url( add_query_arg( array(
		'kbs-action' => 'delete_ticket_note',
		'note_id'    => $note->comment_ID,
		'ticket_id'  => $ticket_id
	), admin_url() ), 'kbs_delete_ticket_note_' . $note->comment_ID, 'kbs_note_nonce' );

	$actions = array(
		'delete_note' => '<a href="' . $delete_note_url . '" class="kbs-remove-row kbs-delete" title="' . esc_attr__( 'Delete Note', 'kb-support' ) . '">' . esc_html__( 'Delete Note', 'kb-support' ) . '</a>',
	);

	if ( $note->user_id != get_current_user_id() && ! current_user_can( $delete_note_cap ) )	{
		unset( $actions['delete_note'] );
	}

	$actions = apply_filters( 'kbs_ticket_notes_actions', $actions, $note );

	ob_start(); ?>

    <div class="kbs-notes-row-header">
        <span class="kbs-notes-row-title">
			<?php echo '<strong>' . esc_html( $user ) . '</strong>' . esc_html__( ' created a note' ); ?>
        </span>

        <span class="kbs-notes-row-actions">
			<?php
			$dif         = absint( time() - strtotime( $note->comment_date ) );
			$time_passed = 0;

			if ( ( $dif / ( 60 ) ) < 60 ) {
				$time_passed = absint( $dif / ( 60 ) ) . ( ( absint( $dif / ( 60 ) ) <= 1 ) ? esc_html__( ' minute', 'kb-support' ) : esc_html__( ' minutes ago', 'kb-support' ) );
			} else if ( ( $dif / ( 60 * 60 ) ) <= 24 ) {
				$time_passed = absint( $dif / ( 60 * 60 ) ) . ( ( absint( $dif / ( 60 * 60 ) ) <= 1 ) ? esc_html__( ' hour', 'kb-support' ) : esc_html__( ' hours ago', 'kb-support' ) );
			} else {
				$time_passed = absint( $dif / ( 60 * 60 * 24 ) ) . ( ( absint( $dif / ( 60 * 60 * 24 ) ) <= 1 ) ? esc_html__( ' day', 'kb-support' ) : esc_html__( ' days ago', 'kb-support' ) );
			}
			echo $time_passed;
			?>
			<?php if ( $actions && ! empty( $actions ) ) {
				?>
				<a href="#" class="helptain-admin-row-actions-toggle dashicons dashicons-ellipsis"></a>
				<ul class="helptain-admin-row-actions helptain-actions-sub-menu kbs-hidden">
					<?php
					foreach ( $actions as $action ) {
						echo '<li>' . $action . '</li>';
					}
					?>
				</ul>
			<?php } ?>

        </span>
    </div>

    <div class="kbs-notes-content-wrap">
        <div class="kbs-notes-content-sections">
            <div class="kbs-notes-content-section">
                <?php do_action( 'kbs_ticket_notes_before_content', $note ); ?>
                <?php echo wpautop( $note->comment_content ); ?>
                <?php do_action( 'kbs_ticket_notes_content', $note ); ?>
            </div>
            <?php do_action( 'kbs_ticket_notes_note', $note ); ?>
        </div>
    </div>

    <?php

    return ob_get_clean();

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
