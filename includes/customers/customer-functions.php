<?php
/**
 * Customer Functions
 *
 * Functions related to customers
 *
 * @package     KBS
 * @subpackage  Functions
 * @copyright   Copyright (c) 2017, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Retrieve the required capability for viewing customers.
 *
 * @since	1.5
 * @return	string	The permission to check for viewing customers
 */
function kbs_get_view_customers_required_capability()	{
	$capability = apply_filters( 'kbs_view_customers_role', 'view_ticket_reports' ); // Backwards compat
	$capability = apply_filters( 'kbs_view_customers_capability', $capability );

	return $capability;
} // kbs_get_view_customers_required_capability

/**
 * Whether or not a user can view customers.
 *
 * @since   1.5
 * @param   int     $user_id    The user ID to check
 * @return  bool
 */
function kbs_can_view_customers( $user_id = 0 ) {
    $can_view           = false;
    $user_id            = ! empty( $user_id ) ? absint( $user_id ) : get_current_user_id();

    if ( ! empty( $user_id ) )    {
        $can_view = user_can( $user_id, kbs_get_view_customers_required_capability() );
    }

    $can_view = apply_filters( 'kbs_can_view_customers', $can_view, $user_id );

    return $can_view;
} // kbs_can_view_customers

/**
 * Whether or not a user can edit customers.
 *
 * @since   1.2.9
 * @param   int     $user_id    The user ID to check
 * @return  bool
 */
function kbs_can_edit_customers( $user_id = 0 ) {
    $customer_edit_role = apply_filters( 'kbs_edit_customers_role', 'manage_ticket_settings' );
    $can_edit           = false;
    $user_id            = ! empty( $user_id ) ? absint( $user_id ) : get_current_user_id();

    if ( ! empty( $user_id ) )    {
        $can_edit = user_can( $user_id, $customer_edit_role );
    }

    $can_edit = apply_filters( 'kbs_can_edit_customers', $can_edit, $user_id );

    return $can_edit;
} // kbs_can_edit_customers

/**
 * Retrieve the customer ID from a ticket.
 *
 * @since	1.0
 * @param	int		$ticket_id	The ticket ID
 * @return	int		The customer ID
 */
function kbs_get_customer_id_from_ticket( $ticket_id )	{
	return get_post_meta( $ticket_id, '_kbs_ticket_customer_id', true );
} // kbs_get_customer_id_from_ticket

/**
 * Whether or not a customer exists.
 *
 * @since	1.0
 * @param	mixed	$id_or_email	The customer ID or email address
 * @return	bool	True or false
 */
function kbs_customer_exists( $id_or_email )	{
	$customer = new KBS_Customer( $id_or_email );

    if ( empty( $customer->id ) )  {
        return false;
    }

    return true;
} // kbs_customer_exists

/**
 * Adds a customer to a company.
 *
 * @since   1.2
 * @param   int|object  $customer   Customer ID or KBS_Customer object
 * @param   int         $company_id Company ID
 * @return  bool
 */
function kbs_add_customer_to_company( $customer, $company_id )  {
    if ( ! is_object( $customer ) ) {
        $customer = new KBS_Customer( $customer );
    }

    if ( empty( $customer->id ) )   {
        return false;
    }

    return $customer->add_company( $company_id );
} // kbs_add_customer_to_company

/**
 * Retrieve the customer company ID.
 *
 * @since	1.0
 * @param	int		$customer_id	The customer ID
 * @return	int		The customer company ID
 */
function kbs_get_customer_company_id( $customer_id )	{
	global $wpdb;

	$company_id = 0;

	$results = $wpdb->get_col( $wpdb->prepare(
		"SELECT company_id
		FROM " . $wpdb->prefix . "kbs_customers
		WHERE id = %d",
		$customer_id
	) );

	if ( $results )	{
		$company_id = $results[0];
	}

	return $company_id;
} // kbs_get_customer_company_id

/**
 * Whether or not a customer can access the ticket.
 *
 * @since	1.2
 * @param	int|object	$ticket		The ticket ID or a KBS_Ticket class object
 * @param	int|object	$customer	The customer ID or a KBS_Customer class object.
 * @return	bool		True if customer can view, otherwise false
 */
function kbs_customer_can_access_ticket( $ticket = '', $customer = '' )	{

	if ( empty( $ticket ) )	{
		return false;
	}

	if ( is_numeric( $ticket ) )	{
		$ticket = new KBS_Ticket( $ticket );
		if ( empty( $ticket->ID ) )	{
			return false;
		}
	}

    $by_user_id = false;

    if ( empty( $customer ) && is_user_logged_in() )	{
        $by_user_id = true;
        $user_id    = get_current_user_id();
    }

	if ( is_numeric( $customer ) )	{
		$customer = new KBS_Customer( $customer, $by_user_id );

		if ( empty( $customer->id ) )	{
			return false;
		}
	}

	$can_access = false;

	if ( $customer->id == $ticket->customer_id )	{
		$can_access = true;
	}

	if ( ! $can_access )	{
		if ( ! empty( $customer->company_id ) && ! empty( $ticket->company_id ) && $customer->company_id  == $ticket->company_id )	{
			$can_access = true;
		}
	}

	if ( ! $can_access && kbs_participants_enabled() && ! empty ( $customer->id ) )	{
        $can_access = kbs_is_ticket_participant( $ticket, $customer );
	}

	/**
	 * Enable extensions to overide the $can_access result
	 *
	 * @since	1.2
	 * @param	bool	$can_access		True if customer can view, otherwise false
	 * @param	object	$ticket			KBS_Ticket class object
	 * @param	object	$customer		KBS_Customer class object
	 */
	$can_access = apply_filters( 'kbs_customer_can_access_ticket', $can_access, $ticket, $customer );

	return (bool)$can_access;
} // kbs_customer_can_access_ticket

/**
 * Retrieve customer tickets.
 *
 * @since	1.0
 * @param	int|obj		$customer	The customer ID or a KBS_Customer object.
 * @param	arr			$args		Args that can be passed to kbs_get_tickets()
 * @param	bool		$can_select	True to only return selectable status. False for all.
 * @return	obj			Array of customer ticket objects.
 */
function kbs_get_customer_tickets( $customer, $args = array(), $can_select = true, $pagination = false )	{

	$customer_id = $customer;

	if ( is_object( $customer ) )	{
		$customer_id = $customer->id;
	}

	if ( empty( $customer_id ) )	{
		return false;
	}

	$ticket_statuses = kbs_get_ticket_statuses( $can_select );

	if ( $pagination )	{
		if ( get_query_var( 'paged' ) )	{
			$paged = get_query_var('paged');
		} else if ( get_query_var( 'page' ) )	{
			$paged = get_query_var( 'page' );
		} else	{
			$paged = 1;
		}
	}

	$defaults = array(
		'customer' => $customer_id,
		'number'   => 10,
		'status'   => apply_filters(
			'kbs_get_customer_tickets_statuses',
			array_keys( $ticket_statuses ),
			$can_select
		)
	);

	$args = wp_parse_args( $args, $defaults );

	if ( $pagination )	{
		$args['page'] = $paged;
	} else	{
		$args['nopaging'] = true;
	}

	return kbs_get_tickets( $args );

} // kbs_get_customer_tickets

/**
 * Whether or not a customer has closed tickets.
 *
 * @since   1.2.6
 * @param	int|object	$customer	The customer ID or a KBS_Customer object.
 * @return  bool        True if the customer has tickets in the closed status
 */
function kbs_customer_has_closed_tickets( $customer )   {
	$customer_id = $customer;

	if ( is_object( $customer ) )	{
		$customer_id = $customer->id;
	}

	if ( empty( $customer_id ) )	{
		return false;
	}

    $args = array(
		'customer' => $customer_id,
		'number'   => 1,
		'status'   => 'closed'
	);

    $tickets = kbs_get_tickets( $args );

    return ! empty( $tickets );
} // kbs_customer_has_closed_tickets

/**
 * Retrieve customer ticket count.
 *
 * @since	1.0
 * @param	int|obj		$customer	The customer ID or a KBS_Customer object.
 * @return	int			Array of customer ticket objects.
 */
function kbs_get_customer_ticket_count( $customer )	{

	$customer_id = $customer;

	if ( is_object( $customer ) )	{
		$customer_id = $customer->id;
	}

	if ( empty( $customer_id ) )	{
		return false;
	}

	$tickets = kbs_count_tickets( array( 'customer' => $customer_id ) );
	$count   = 0;

	if ( ! empty( $tickets ) )	{
		foreach( $tickets as $status )	{
			if ( ! empty( $status ) )	{
				$count += $status;
			}
		}
	}

	return $count;
} // kbs_get_customer_ticket_count

/**
 * Retrieve the number of tickets to load per page.
 *
 * @since   1.4
 * @param   int     $user_id    The User ID of the current user
 * @return  int     The number of replies to load
 */
function kbs_get_customer_tickets_per_page( $user_id = 0 )   {
    $default = get_option( 'posts_per_page', 10 );

    if ( empty( $user_id ) )    {
        $user_id = get_current_user_id();
    }

    if ( ! empty( $user_id ) )    {
        $tickets = get_user_meta( $user_id, '_kbs_tickets_per_page', true );

        if ( '' == $tickets )   {
            $tickets = $default;
        }

    } else  {
        $tickets = $default;
    }

    $tickets = ! empty( $tickets ) ? $tickets : $default;

    $tickets = apply_filters( 'kbs_customer_tickets_per_page', $tickets, $user_id );

    return (int)$tickets;
} // kbs_get_customer_tickets_per_page

/**
 * Retrieve the number of replies to load (front end).
 *
 * @since   1.2.6
 * @param   int     $user_id    The User ID of the current user
 * @return  int     The number of replies to load
 */
function kbs_get_customer_replies_to_load( $user_id = 0 )   {
    $default = kbs_get_option( 'replies_to_load' );

    if ( empty( $user_id ) )    {
        $user_id = get_current_user_id();
    }

    if ( ! empty( $user_id ) )    {
        $replies = get_user_meta( $user_id, '_kbs_load_replies', true );

        if ( '' == $replies )   {
            $replies = $default;
        }

    } else  {
        $replies = $default;
    }

    $replies = ! empty( $replies ) ? $replies : 0;

    $replies = apply_filters( 'kbs_replies_to_load', $replies, $user_id );

    return (int)$replies;
} // kbs_get_customer_replies_to_load


/**
 * Whether or not a customer wishes to hide closed tickets.
 *
 * @since   1.2.6
 * @param   int     $user_id    The User ID of the current user
 * @return  bool    Whether or not closed tickets should be hidden
 */
function kbs_customer_maybe_hide_closed_tickets( $user_id = 0 )   {
	if ( empty( $user_id ) )	{
		$user_id = get_current_user_id();
	}
    if ( metadata_exists( 'user', $user_id, '_kbs_hide_closed' ) )	{
		$hide_closed = get_user_meta( $user_id, '_kbs_hide_closed', true );
	} else	{
    	$hide_closed = kbs_get_option( 'hide_closed_front' );
	}

    return $hide_closed;
} // kbs_customer_maybe_hide_closed_tickets

/**
 * Retrieve all customers.
 *
 * @since	1.0
 * @param	bool	$ids	True to return array of IDs, false for array of user objects
 * @return	mixed
 */
function kbs_get_customers( $ids = false )	{
	$role = array( 'support_customer' );
	$role = apply_filters( 'kbs_customer_roles', $role );

	$customers = kbs_get_users_by_role( $role, $ids );

	return apply_filters( 'kbs_customers', $customers );
} // kbs_get_customers

/**
 * Counts the total number of customers.
 *
 * @since	1.0
 * @return	int		The total number of customers.
 */
function kbs_count_total_customers( $args = array() ) {
	return KBS()->customers->count( $args );
} // kbs_count_total_customers

/**
 * Whether or not customers can reopen a ticket.
 *
 * @since	1.0
 * @return	bool	True if they can re-open tickets, otherwise false
 */
function kbs_customers_can_repoen_tickets() {
    $can_reopen = kbs_get_option( 'customer_can_repoen' );
    $can_reopen = apply_filters( 'kbs_customers_can_repoen_tickets', $can_reopen );

    return (bool) $can_reopen;
} // kbs_customers_can_repoen_tickets

/**
 * Whether or not a specific customer can reopen a ticket.
 *
 * This function allows devs to perform further functions before retuning an answer.
 *
 * @since	1.0
 * @param   int|str The customer ID or email address
 * @param   int     The ticket ID
 * @return	bool	True if they can re-open the ticket, otherwise false
 */
function kbs_customer_can_repoen_ticket( $id_or_email, $ticket_id ) {
    $customer   = new KBS_Customer( $id_or_email );
    $can_reopen = kbs_customers_can_repoen_tickets();
    $can_reopen = apply_filters( 'kbs_customer_can_repoen_ticket', $can_reopen, $customer, $ticket_id );

    return $can_reopen;
} // kbs_customer_can_repoen_ticket

