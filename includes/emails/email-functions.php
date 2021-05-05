<?php
/**
 * Email Functions
 *
 * Taken from Easy Digital Downloads
 *
 * @package     KBS
 * @subpackage  Emails
 * @copyright   Copyright (c) 2017, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Retrieve an array of addresses that should not receive ticket logged notifications.
 *
 * @since	1.2.8
 * @return	array	Array of email address which should be excluded from email notifications
 */
function kbs_get_ticket_received_no_notification_emails()	{
    $emails = kbs_get_option( 'no_notify_received_emails', array() );

    if ( ! empty( $emails ) )   {
        $emails = array_map( 'trim', explode( "\n", $emails ) );
        $emails = array_unique( $emails );
        $emails = array_map( 'sanitize_text_field', $emails );

        if ( ! empty( $emails ) )   {
            foreach( $emails as $id => $email )	{
                if ( ! is_email( $email ) )	{
                    if ( $email[0] != '@' )	{
                        unset( $emails[ $id ] );
                    }
                }
            }
        }
    }

	return apply_filters( 'no_notify_received_emails', $emails );
} // kbs_get_ticket_received_no_notification_emails

/**
 * Determines if an email should be removed from the notification list.
 *
 * @since	1.2.7
 * @param	string	$email	Email address to check
 * @return	bool	true if the email address should be removed
 */
function kbs_maybe_remove_email_from_notification( $email = '' ) {

	if ( empty( $email ) ) {
		return false;
	}

	$no_notify   = kbs_get_ticket_received_no_notification_emails();
	$remove      = false;
	$check_email = strtolower( trim( $email ) );

	if ( empty( $no_notify ) )	{
		return false;
	}

	foreach( $no_notify as $no_notify_email )	{
		$no_notify_email = strtolower( trim( $no_notify_email ) );

		if ( is_email( $no_notify_email ) )	{
			$return = ( $no_notify_email == $check_email ? true : false );
		} else {
			$return = ( stristr( $check_email, $no_notify_email ) ? true : false );
		}

		if ( true === $return ) {
			break;
		}
	}

	return apply_filters( 'kbs_remove_email_from_notification', $return, $email );

} // kbs_maybe_remove_email_from_notification

/**
 * Email the ticket details to the customer.
 *
 * @since	1.0
 * @param	int		$ticket_id		Ticket ID
 * @param	bool	$admin_notice	Whether to send the admin email notification or not (default: true)
 * @param   bool    $resend         Whether or not we should be resending the email
 * @return	void
 */
function kbs_email_ticket_received( $ticket_id, $admin_notice = true, $resend = false ) {

	$disable = kbs_get_option( 'ticket_received_disable_email', false );
	$disable = apply_filters( 'kbs_ticket_received_disable_email', $disable, $ticket_id );

	if ( ! empty( $disable ) )	{
		return;
	}

    $pending = get_post_meta( $ticket_id, '_kbs_pending_ticket_created_email', true );

    if ( ! $pending && ! $resend )  {
        return;
    }

	$single       = kbs_get_ticket_label_singular();
	$ticket       = new KBS_Ticket( $ticket_id );
	$ticket_data  = $ticket->get_meta();

    $to_email     = $ticket->email;
    $to_email     = apply_filters( 'kbs_ticket_received_to_email', $to_email, $ticket );

    if ( is_email( $to_email ) )    {
        $from_name    = kbs_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
        $from_name    = apply_filters( 'kbs_ticket_from_name', $from_name, $ticket_id, $ticket_data );

        $from_email   = kbs_get_option( 'from_email', get_bloginfo( 'admin_email' ) );
        $from_email   = apply_filters( 'kbs_ticket_from_address', $from_email, $ticket_id, $ticket_data );

        $subject      = kbs_get_option( 'ticket_subject', sprintf( __( 'Support %s Details', 'kb-support' ), $single ) );
        $subject      = apply_filters( 'kbs_ticket_subject', wp_strip_all_tags( $subject ), $ticket_id );
        $subject      = kbs_do_email_tags( $subject, $ticket_id );

        $heading      = kbs_get_option( 'ticket_heading', sprintf( __( 'Support %s Details', 'kb-support' ), $single ) );
        $heading      = apply_filters( 'kbs_ticket_heading', $heading, $ticket_id, $ticket_data );
        $heading      = kbs_do_email_tags( $heading, $ticket_id );

        $message      = kbs_do_email_tags( kbs_get_ticket_logged_email_body_content( $ticket_id, $ticket_data ), $ticket_id );
        $attachments  = apply_filters( 'kbs_ticket_attachments', array(), $ticket_id, $ticket_data );

        $emails       = KBS()->emails;

        $emails->__set( 'from_name', $from_name );
        $emails->__set( 'from_email', $from_email );
        $emails->__set( 'heading', $heading );

        $headers = apply_filters( 'kbs_ticket_headers', $emails->get_headers(), $ticket_id, $ticket_data );
        $emails->__set( 'headers', $headers );

        $emails->send( $to_email, $subject, $message, $attachments );
    }

	if ( $admin_notice && ! kbs_admin_notices_disabled( $ticket_id ) ) {
		do_action( 'kbs_admin_ticket_notice', $ticket_id, $ticket_data );
	}

    delete_post_meta( $ticket_id, '_kbs_pending_ticket_created_email' );

} // kbs_email_ticket_received

/**
 * Email to customer when a reply is added to a ticket.
 *
 * @since	1.0
 * @param	int		$ticket_id		Ticket ID
 * @return	void
 */
function kbs_email_ticket_reply( $ticket_id, $reply_id ) {

	if ( ! is_admin() && ! wp_doing_cron() && ! defined( 'REST_REQUEST' ) )	{
		return;
	}

	$disable = kbs_get_option( 'ticket_reply_disable_email', false );
	$disable = apply_filters( 'kbs_ticket_reply_disable_email', $disable, $ticket_id );

	if ( ! empty( $disable ) )	{
		return;
	}

	$single = kbs_get_ticket_label_singular();
	$ticket = new KBS_Ticket( $ticket_id );

	// We do not send reply emails if a ticket is closed.
	if ( 'closed' == $ticket->post_status )	{
		return;
	}

	$ticket_data  = $ticket->get_meta();

    $to_email     = $ticket->email;
    $to_email     = apply_filters( 'kbs_ticket_reply_to_email', $to_email, $ticket );

    if ( is_email( $to_email ) )    {
        $from_name    = kbs_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
        $from_name    = apply_filters( 'kbs_ticket_reply_from_name', $from_name, $ticket_id, $ticket_data, $reply_id );

        $from_email   = kbs_get_option( 'from_email', get_bloginfo( 'admin_email' ) );
        $from_email   = apply_filters( 'kbs_ticket_reply_from_address', $from_email, $ticket_id, $ticket_data, $reply_id );

        $subject      = kbs_get_option( 'ticket_reply_subject', sprintf( __( 'Your Support %s Received a Reply - ##{ticket_id}##', 'kb-support' ), $single ) );
        $subject      = apply_filters( 'kbs_ticket_reply_subject', wp_strip_all_tags( $subject ), $ticket_id, $reply_id );
        $subject      = kbs_do_email_tags( $subject, $ticket_id );

        $heading      = kbs_get_option( 'ticket_reply_heading', sprintf( __( 'Support %s Update for #{ticket_id}', 'kb-support' ), $single ) );
        $heading      = apply_filters( 'kbs_ticket_reply_heading', $heading, $ticket_id, $ticket_data, $reply_id );
        $heading      = kbs_do_email_tags( $heading, $ticket_id );

        $message      = kbs_do_email_tags( kbs_get_ticket_reply_email_body_content( $ticket_id, $ticket_data ), $ticket_id );
        $attachments  = apply_filters( 'kbs_ticket_reply_attachments', array(), $ticket_id, $ticket_data, $reply_id );

        $emails       = KBS()->emails;

        $emails->__set( 'from_name', $from_name );
        $emails->__set( 'from_email', $from_email );
        $emails->__set( 'heading', $heading );

        $headers = apply_filters( 'kbs_ticket_reply_headers', $emails->get_headers(), $ticket_id, $ticket_data, $reply_id );
        $emails->__set( 'headers', $headers );

        $emails->send( $to_email, $subject, $message, $attachments );
    }

} // kbs_email_ticket_reply
add_action( 'kbs_reply_to_ticket', 'kbs_email_ticket_reply', 999, 2 );

/**
 * Email to customer when a ticket is closed.
 *
 * @since	1.0
 * @param	int		$ticket_id		Ticket ID
 * @return	void
 */
function kbs_email_ticket_closed( $ticket_id ) {

	$disable = kbs_get_option( 'ticket_closed_disable_email', false );
	$disable = apply_filters( 'kbs_ticket_close_disable_email', $disable, $ticket_id );

	if ( ! empty( $disable ) )	{
		return;
	}

	$single = kbs_get_ticket_label_singular();
	$ticket = new KBS_Ticket( $ticket_id );

	// We only send emails if a ticket is closed.
	if ( 'closed' != $ticket->post_status )	{
		return;
	}

	$ticket_data  = $ticket->get_meta();

    $to_email     = $ticket->email;
    $to_email     = apply_filters( 'kbs_ticket_closed_to_email', $to_email, $ticket );

    if ( is_email( $to_email ) )    {
        $from_name    = kbs_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
        $from_name    = apply_filters( 'kbs_ticket_closed_from_name', $from_name, $ticket_id, $ticket_data );

        $from_email   = kbs_get_option( 'from_email', get_bloginfo( 'admin_email' ) );
        $from_email   = apply_filters( 'kbs_ticket_closed_from_address', $from_email, $ticket_id, $ticket_data );

        $subject      = kbs_get_option( 'ticket_closed_subject', sprintf( __( 'Your Support %s is Closed ##{ticket_id}##', 'kb-support' ), $single ) );
        $subject      = apply_filters( 'kbs_ticket_closed_subject', wp_strip_all_tags( $subject ), $ticket_id );
        $subject      = kbs_do_email_tags( $subject, $ticket_id );

        $heading      = kbs_get_option( 'ticket_closed_heading', sprintf( __( 'Support %s #{ticket_id} Closed', 'kb-support' ), $single ) );
        $heading      = apply_filters( 'kbs_ticket_closed_heading', $heading, $ticket_id, $ticket_data );
        $heading      = kbs_do_email_tags( $heading, $ticket_id );

        $message      = kbs_do_email_tags( kbs_get_ticket_closed_email_body_content( $ticket_id, $ticket_data ), $ticket_id );
        $attachments  = apply_filters( 'kbs_ticket_closed_attachments', array(), $ticket_id, $ticket_data );

        $emails       = KBS()->emails;

        $emails->__set( 'from_name', $from_name );
        $emails->__set( 'from_email', $from_email );
        $emails->__set( 'heading', $heading );

        $headers = apply_filters( 'kbs_ticket_closed_headers', $emails->get_headers(), $ticket_id, $ticket_data );
        $emails->__set( 'headers', $headers );

        $emails->send( $to_email, $subject, $message, $attachments );
    }

} // kbs_email_ticket_closed
add_action( 'kbs_close_ticket', 'kbs_email_ticket_closed', 999 );

/**
 * Email the ticket received confirmation to the admin accounts for testing.
 *
 * @since	1.0
 * @return	void
 */
function kbs_email_test_ticket_received() {
	$single       = kbs_get_ticket_label_singular();
    $admin_emails = kbs_get_admin_notice_emails();

    if ( empty( $admin_emails ) )   {
        return;
    }

	$from_name   = kbs_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	$from_name   = apply_filters( 'kbs_test_ticket_from_name', $from_name, 0, array() );

	$from_email  = kbs_get_option( 'from_email', get_bloginfo( 'admin_email' ) );
	$from_email  = apply_filters( 'kbs_test_ticket_from_address', $from_email, 0, array() );

	$subject     = kbs_get_option( 'ticket_subject', sprintf( __( 'Support %s Submitted', 'kb-support' ), $single ) );
	$subject     = apply_filters( 'kbs_test_ticket_subject', wp_strip_all_tags( $subject ), 0 );
	$subject     = kbs_do_email_tags( $subject, 0 );

	$heading     = kbs_get_option( 'ticket_heading', sprintf( __( 'Support %s Details', 'kb-support' ), $single ) );
	$heading     = apply_filters( 'kbs_test_ticket_heading', $heading, 0, array() );
	$heading     = kbs_do_email_tags( $heading, 0 );

	$message     = kbs_do_email_tags( kbs_get_ticket_logged_email_body_content( 0, array() ), 0 );
    $attachments = apply_filters( 'kbs_test_ticket_attachments', array(), 0, array() );

	$emails = KBS()->emails;
	$emails->__set( 'from_name' , $from_name );
	$emails->__set( 'from_email', $from_email );
	$emails->__set( 'heading'   , $heading );

	$headers = apply_filters( 'kbs_test_ticket_headers', $emails->get_headers(), 0, array() );
	$emails->__set( 'headers', $headers );

	$emails->send( $admin_emails, $subject, $message, $attachments );
} // kbs_email_test_ticket_received

/**
 * Sends the Admin Ticket Notification Email
 *
 * @since	1.0
 * @param	int		$ticket_id		Ticket ID
 * @param	arr		$ticket_data	Ticket Meta and Data
 * @return	void
 */
function kbs_admin_email_ticket_notice( $ticket_id = 0, $ticket_data = array() ) {
	$single    = kbs_get_ticket_label_singular();
	$ticket_id = absint( $ticket_id );
    $headers   = array();

	if ( empty( $ticket_id ) ) {
		return;
	}

    $admin_emails = kbs_get_admin_notice_emails( $ticket_id );

    if ( empty( $admin_emails ) ) {
		return;
	}

	$from_name   = kbs_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	$from_name   = apply_filters( 'kbs_notification_ticket_from_name', $from_name, $ticket_id, $ticket_data );

	$from_email  = kbs_get_option( 'from_email', get_bloginfo( 'admin_email' ) );
	$from_email  = apply_filters( 'kbs_notification_ticket_from_address', $from_email, $ticket_id, $ticket_data );

	$subject     = kbs_get_option( 'ticket_notification_subject', sprintf( __( 'New %1$s logged - Case #%1$s', 'kb-support' ), $single, $ticket_id ) );
	$subject     = apply_filters( 'kbs_admin_ticket_notification_subject', wp_strip_all_tags( $subject ), $ticket_id );
	$subject     = kbs_do_email_tags( $subject, $ticket_id );

	$headers[]   = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>";
	$headers[]   = "Reply-To: ". $from_email;
	$headers[]   = "Content-Type: text/html; charset=utf-8";
	$headers     = apply_filters( 'kbs_admin_ticket_notification_headers', $headers, $ticket_id, $ticket_data );

	$message     = kbs_get_ticket_notification_email_body_content( $ticket_id, $ticket_data );
    $attachments = apply_filters( 'kbs_admin_ticket_notification_attachments', array(), $ticket_id, $ticket_data );

	$emails = KBS()->emails;
	$emails->__set( 'from_name', $from_name );
	$emails->__set( 'from_email', $from_email );
	$emails->__set( 'headers', $headers );
	$emails->__set( 'heading', sprintf( __( 'New %s Received', 'kb-support' ), $single ) );

	$emails->send( $admin_emails, $subject, $message, $attachments );
} // kbs_admin_email_ticket_notice
add_action( 'kbs_admin_ticket_notice', 'kbs_admin_email_ticket_notice', 10, 2 );

/**
 * Sends the Admin Reply Notification Email
 *
 * @since	1.0
 * @param	int		$reply_id		Reply ID
 * @param	arr		$data			Array of reply data from form
 * @return	void
 */
function kbs_admin_email_reply_notice( $reply_id = 0, $data = array() ) {
	if ( ( is_admin() && ! wp_doing_cron() ) || kbs_admin_notices_disabled( $reply_id ) )	{
		return;
	}

	$single    = kbs_get_ticket_label_singular();
	$ticket_id = absint( $reply_id );
    $headers   = array();

	if ( empty( $reply_id ) ) {
		return;
	}

	$ticket_id = get_post_field( 'post_parent', $reply_id );

	if ( empty( $ticket_id ) ) {
		return;
	}

    $admin_emails = kbs_get_admin_notice_emails( $ticket_id );

    if ( empty( $admin_emails ) ) {
		return;
	}

	$from_name   = kbs_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	$from_name   = apply_filters( 'kbs_notification_reply_from_name', $from_name, $ticket_id, $data );

	$from_email  = kbs_get_option( 'from_email', get_bloginfo( 'admin_email' ) );
	$from_email  = apply_filters( 'kbs_notification_reply_from_address', $from_email, $ticket_id, $data, $reply_id );

	$subject     = kbs_get_option( 'reply_notification_subject', sprintf( __( 'New %1$s Reply Received - %1$s #%1$s', 'kb-support' ), $single, $ticket_id ) );
	$subject     = apply_filters( 'kbs_admin_reply_notification_subject', wp_strip_all_tags( $subject ), $ticket_id, $reply_id );
	$subject     = kbs_do_email_tags( $subject, $ticket_id );

	$headers[]   = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>";
	$headers[]   = "Reply-To: ". $from_email;
	$headers[]   = "Content-Type: text/html; charset=utf-8";
	$headers     = apply_filters( 'kbs_admin_reply_notification_headers', $headers, $ticket_id, $data, $reply_id );

	$message     = kbs_get_reply_notification_email_body_content( $ticket_id, $data );
    $attachments = apply_filters( 'kbs_admin_reply_notification_attachments', array(), $ticket_id, $data, $reply_id );

	$emails = KBS()->emails;
	$emails->__set( 'from_name', $from_name );
	$emails->__set( 'from_email', $from_email );
	$emails->__set( 'headers', $headers );
	$emails->__set( 'heading', sprintf( __( 'New %s Reply Received', 'kb-support' ), $single ) );

	$emails->send( $admin_emails, $subject, $message, $attachments );
} // kbs_admin_email_reply_notice
add_action( 'kbs_ticket_customer_reply', 'kbs_admin_email_reply_notice', 10, 2 );

/**
 * Sends agents notification of ticket assignment.
 *
 * @since	1.1
 * @param	int		$ticket_id		Ticket ID
 * @param	int		$agent_id		The user ID of the agent
 * @param	int		$previous		Previously assigned agent
 * @return	void
 */
function kbs_email_agent_assigned_to_ticket( $ticket_id = 0, $agent_id = 0, $previous = 0 ) {
	if ( ! kbs_agent_assignment_notices_enabled( $ticket_id ) )	{
		return;
	}

	$single = kbs_get_ticket_label_singular();
	$ticket = new KBS_Ticket( $ticket_id );

	// Make sure we have an agent assigned.
	if ( empty( $ticket->agent_id ) && empty( $ticket->agents ) && empty( $agent_id ) )	{
		return;
	}

    $agent = get_userdata( $agent_id );

    if ( ! $agent ) {
        return;
    }

	$ticket_data  = $ticket->get_meta();

    // Place the previously assigned agent into the $ticket_data array so it can be referenced within hooks/filters
    if ( ! empty( $previous ) ) {
        $ticket_data['previous_agent'] = $previous;
    }

	$from_name    = kbs_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	$from_name    = apply_filters( 'kbs_agent_assigned_from_name', $from_name, $ticket_id, $ticket_data );

	$from_email   = kbs_get_option( 'from_email', get_bloginfo( 'admin_email' ) );
	$from_email   = apply_filters( 'kbs_agent_assigned_from_address', $from_email, $ticket_id, $ticket_data );

	$to_email     = $agent->user_email;

	$subject      = kbs_get_option( 'agent_assigned_subject', sprintf( __( 'Your Support %s is Closed ##{ticket_id}##', 'kb-support' ), $single ) );
	$subject      = apply_filters( 'kbs_agent_assigned_subject', wp_strip_all_tags( $subject ), $ticket_id );
	$subject      = kbs_do_email_tags( $subject, $ticket_id );

	$heading      = sprintf( __( 'Support %s #{ticket_id} Assigned', 'kb-support' ), $single );
	$heading      = apply_filters( 'kbs_agent_assigned_heading', $heading, $ticket_id, $ticket_data );
	$heading      = kbs_do_email_tags( $heading, $ticket_id );

	$message      = kbs_do_email_tags( kbs_get_agent_assigned_notification_email_body_content( $ticket_id, $ticket_data ), $ticket_id );
    $attachments  = apply_filters( 'kbs_agent_assigned_attachments', array(), $ticket_id, $ticket_data );

	$emails       = KBS()->emails;

	$emails->__set( 'from_name', $from_name );
	$emails->__set( 'from_email', $from_email );
	$emails->__set( 'heading', $heading );

	$headers = apply_filters( 'kbs_agent_assigned_headers', $emails->get_headers(), $ticket_id, $ticket_data );
	$emails->__set( 'headers', $headers );

	$emails->send( $to_email, $subject, $message, $attachments );
} // kbs_email_agent_assigned_to_ticket

/**
 * Retrieves the emails for which admin notifications are sent to (these can be
 * changed in the KBS Settings)
 *
 * @since	1.0
 * @param	int		$ticket_id	Ticket ID
 * @return	mixed
 */
function kbs_get_admin_notice_emails( $ticket_id = 0 )	{
	$emails  = kbs_get_option( 'admin_notice_emails', false );
	$emails  = strlen( trim( $emails ) ) > 0 ? $emails : get_bloginfo( 'admin_email' );
    $has_tag = strpos( $emails, '{agent}' );
	$emails  = array_map( 'trim', explode( "\n", $emails ) );

	if ( ! empty( $ticket_id ) && false !== $has_tag )	{
		$agent_id = kbs_get_agent( $ticket_id );

		if ( ! empty( $agent_id ) )	{
			$agent_data = get_userdata( $agent_id );

			if ( ! empty( $agent_data ) )	{
				$emails   = str_replace( '{agent}', $agent_data->user_email, $emails );
                $done_tag = true;
			}
		}

        if ( empty( $done_tag ) )   {
            str_replace( '{agent}', '', $emails );
        }
	}

	return apply_filters( 'kbs_admin_notice_emails', $emails );
} // kbs_get_admin_notice_emails

/**
 * Checks whether admin ticket notices are disabled
 *
 * @since	1.0
 *
 * @param	int		$ticket_id
 * @return	mixed
 */
function kbs_admin_notices_disabled( $ticket_id = 0 ) {
	$ret = kbs_get_option( 'disable_admin_notices', false );
	return (bool) apply_filters( 'kbs_admin_notices_disabled', $ret, $ticket_id );
} // kbs_admin_notices_disabled

/**
 * Checks whether agent assignment ticket notices are disabled
 *
 * @since	1.1
 *
 * @param	int		$ticket_id
 * @return	mixed
 */
function kbs_agent_assignment_notices_enabled( $ticket_id = 0 ) {
	$ret = kbs_get_option( 'agent_notices', false );
	return (bool) apply_filters( 'kbs_agent_assignment_notices_disabled', $ret, $ticket_id );
} // kbs_agent_assignment_notices_enabled

/**
 * Get various correctly formatted names used in emails
 *
 * @since	1.0
 * @param	$user_info
 *
 * @return	arr		$email_names
 */
function kbs_get_email_names( $user_info ) {
	$email_names = array();
	$user_info 	= maybe_unserialize( $user_info );

	$email_names['fullname'] = '';
	if ( isset( $user_info['id'] ) && $user_info['id'] > 0 && isset( $user_info['first_name'] ) ) {
		$user_data = get_userdata( $user_info['id'] );
		$email_names['name']      = $user_info['first_name'];
		$email_names['fullname']  = $user_info['first_name'] . ' ' . $user_info['last_name'];
		$email_names['username']  = $user_data->user_login;
	} elseif ( isset( $user_info['first_name'] ) ) {
		$email_names['name']     = $user_info['first_name'];
		$email_names['fullname'] = $user_info['first_name'] . ' ' . $user_info['last_name'];
		$email_names['username'] = $user_info['first_name'];
	} else {
		$email_names['name']     = $user_info['email'];
		$email_names['username'] = $user_info['email'];
	}

	return $email_names;
} // kbs_get_email_names
