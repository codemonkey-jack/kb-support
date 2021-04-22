<?php
/**
 * AJAX Functions
 *
 * Process the front-end AJAX actions.
 *
 * @package     KBS
 * @subpackage  Functions/AJAX
 * @copyright   Copyright (c) 2017, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Check if AJAX works as expected
 *
 * @since	1.0
 * @return	bool	True if AJAX works, false otherwise
 */
function kbs_test_ajax_works() {
	// Check if the Airplane Mode plugin is installed
	if ( class_exists( 'Airplane_Mode_Core' ) ) {
		$airplane = Airplane_Mode_Core::getInstance();

		if ( method_exists( $airplane, 'enabled' ) ) {
			if ( $airplane->enabled() ) {
				return true;
			}
		} else {
			if ( $airplane->check_status() == 'on' ) {
				return true;
			}
		}
	}

	add_filter( 'block_local_requests', '__return_false' );

	if ( get_transient( '_kbs_ajax_works' ) ) {
		return true;
	}

	$params = array(
		'sslverify'  => false,
		'timeout'    => 30,
		'body'       => array(
			'action' => 'kbs_test_ajax'
		)
	);

	$ajax  = wp_remote_post( kbs_get_ajax_url(), $params );
	$works = true;

	if ( is_wp_error( $ajax ) ) {
		$works = false;
	} else {
		if ( empty( $ajax['response'] ) ) {
			$works = false;
		}

		if ( empty( $ajax['response']['code'] ) || 200 !== (int) $ajax['response']['code'] ) {
			$works = false;
		}

		if ( empty( $ajax['response']['message'] ) || 'OK' !== $ajax['response']['message'] ) {
			$works = false;
		}

		if ( ! isset( $ajax['body'] ) || 0 !== (int) $ajax['body'] ) {
			$works = false;
		}
	}

	if ( $works ) {
		set_transient( '_kbs_ajax_works', '1', DAY_IN_SECONDS );
	}

	return $works;
} // kbs_test_ajax_works

/**
 * Checks whether AJAX is disabled.
 *
 * @since	1.0
 * @return	bool	True when KBS AJAX is disabled, false otherwise.
 */
function kbs_is_ajax_disabled() {
	$retval = ! kbs_get_option( 'enable_ajax_ticket' );

	return apply_filters( 'kbs_is_ajax_disabled', $retval );
} // kbs_is_ajax_disabled

/**
 * Get AJAX URL
 *
 * @since	1.0
 * @return	str		URL to the AJAX file to call during AJAX requests.
*/
function kbs_get_ajax_url() {
	$scheme      = defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ? 'https' : 'admin';
	$current_url = kbs_get_current_page_url();
	$ajax_url    = admin_url( 'admin-ajax.php', $scheme );

	if ( preg_match( '/^https/', $current_url ) && ! preg_match( '/^https/', $ajax_url ) ) {
		$ajax_url = preg_replace( '/^http/', 'https', $ajax_url );
	}

	return apply_filters( 'kbs_ajax_url', $ajax_url );
} // kbs_get_ajax_url

/**
 * Dismiss admin notices.
 *
 * @since	1.0
 * @return	void
 */
function kbs_ajax_dismiss_admin_notice()	{
	$notice = sanitize_text_field( $_POST['notice'] );
    kbs_dismiss_notice( $notice );

	wp_send_json_success();
} // kbs_ajax_dismiss_admin_notice
add_action( 'wp_ajax_kbs_dismiss_notice', 'kbs_ajax_dismiss_admin_notice' );

/**
 * Sets a tickets flagged status.
 *
 * @since   1.5.3
 * @return  void
 */
function kbs_set_ticket_flagged_status_ajax()   {
    $ticket_id   = absint( $_POST['ticket_id'] );
    $flag_status = (bool) $_POST['flagged'];
    $user_id     = isset( $_POST['flagged_by'] ) ? absint( $_POST['flagged_by'] ) : get_current_user_id();
    $ticket      = new KBS_Ticket( $ticket_id );

    $ticket->set_flagged_status( $flag_status, $user_id );

    $flagged = $ticket->flagged ? 'flagged' : 'unflagged';
    $note_id = kbs_insert_note(
        $ticket_id,
        sprintf(
            __( '%s %s', 'kb-support' ),
            kbs_get_ticket_label_singular(),
            $flagged
        ),
        array( 'user_id' => $user_id )
    );

    $data = array(
        'flagged' => $ticket->flagged ? 'flagged' : 'unflagged',
        'note_id' => $note_id
    );

    wp_send_json_success( $data );
} // kbs_set_ticket_flagged_status_ajax
add_action( 'wp_ajax_kbs_set_ticket_flagged_status', 'kbs_set_ticket_flagged_status_ajax' );

/**
 * Add a participant to a ticket.
 *
 * @since	1.2.4
 * @return	void
 */
function kbs_ajax_add_participant()	{
	$email     = false;
	$ticket_id = absint( $_POST['ticket_id'] );

	if ( '-1' != $_POST['participant'] )	{
		$customer = new KBS_Customer( $_POST['participant'] );

		if ( $customer )	{
			$email = $customer->email;
		}
	} else	{
		$posted_email = sanitize_email( $_POST['email'] );
		$email        = is_email( $posted_email );
	}

	if ( ! empty( $ticket_id ) && ! empty( $email ) )	{
		kbs_add_ticket_participants( $ticket_id, $email );
		$count = kbs_get_ticket_participant_count( $ticket_id );
		$list  = kbs_list_ticket_participants( $ticket_id, array(
			'email_only'  => false,
			'remove_link' => true
		) );

		wp_send_json_success( array(
			'list'  => $list,
			'count' => $count
		) );
	}

	wp_send_json_error();
} // kbs_ajax_add_participant
add_action( 'wp_ajax_kbs_add_participant', 'kbs_ajax_add_participant' );

/**
 * Remove a participant from a ticket.
 *
 * @since	1.2.4
 * @return	void
 */
function kbs_ajax_remove_participant()	{
	$ticket_id    = absint( $_POST['ticket_id'] );
	$posted_email = sanitize_email( $_POST['participant'] );
	$email        = is_email( $posted_email );

	if ( ! empty( $ticket_id ) && ! empty( $email ) )	{
		kbs_remove_ticket_participants( $ticket_id, $email );
		$count = kbs_get_ticket_participant_count( $ticket_id );
		$list  = kbs_list_ticket_participants( $ticket_id, array(
			'email_only'  => false,
			'remove_link' => true
		) );

		wp_send_json_success( array(
			'list'  => $list,
			'count' => $count
		) );
	}

	wp_send_json_error();
} // kbs_ajax_remove_participant
add_action( 'wp_ajax_kbs_remove_participant', 'kbs_ajax_remove_participant' );

/**
 * Reply to a ticket.
 *
 * @since	1.0
 * @return	void
 */
function kbs_ajax_insert_ticket_reply()	{

	$ticket = new KBS_Ticket( $_POST['ticket_id'] );

	$reply_data = array(
		'ticket_id'   => $_POST['ticket_id'],
		'response'    => $_POST['response'],
		'status'      => ! empty( $_POST['status'] ) ? $_POST['status'] : $ticket->post_status,
		'close'       => $_POST['close_ticket'],
		'customer_id' => $ticket->customer_id,
		'agent_id'    => $ticket->agent_id,
		'key'         => $ticket->key,
		'author'      => get_current_user_id()
	);

	$reply_id      = $ticket->add_reply( $reply_data );

	$status_option = ( isset( $reply_data['close'] ) && '1' == $reply_data['close'] ) ? 'closed' : kbs_agent_get_default_reply_status();

	if ( '0' != $status_option ) {
		$update_fields = array(
			'ID'          => absint( $reply_data['ticket_id'] ),
			'post_status' => sanitize_text_field( $status_option ),
			'edit_date'   => current_time( 'mysql' )
		);

		$updated = wp_update_post( $update_fields );

	}

	do_action( 'kbs_ticket_admin_reply', $ticket->ID, $reply_id );

	wp_send_json(
		apply_filters(
			'helptain_ticket_reply_json_response',
			array(
				'reply_id'      => $reply_id,
				'status_option' => kbs_get_post_status_label( $status_option ),
				'status_color'  => kbs_get_ticket_status_colour( $status_option ),
			)
		)
	);

} // kbs_ajax_reply_to_ticket
add_action( 'wp_ajax_kbs_insert_ticket_reply', 'kbs_ajax_insert_ticket_reply' );

/**
 * Display replies for ticket post metabox.
 *
 * @since	1.0
 * @return	str
 */
function kbs_ajax_display_ticket_replies()	{
	$output = '';

	if ( ! empty( $_POST['kbs_reply_id'] ) )	{
		$output .= kbs_get_reply_html( $_POST['kbs_reply_id'], $_POST['kbs_ticket_id'], true );
	} else	{
        $user_id       = get_current_user_id();
		$number        = get_user_meta( $user_id, '_kbs_load_replies', true );
		$number        = ! empty( $number ) ? (int)$number : 0;
        $count_expand  = 1;
        $expand        = ! empty( $expand ) && $expand > 0 ? (int)$expand : 0;

		$args = array(
			'ticket_id' => (int)$_POST['kbs_ticket_id'],
			'number'    => $number,
			'page'      => isset( $_POST['kbs_page'] ) ? (int)$_POST['kbs_page'] : null
		);

		$expand = isset( $args['page'] ) && (int)$_POST['kbs_page'] > 1 ? 0 : $expand;

		$replies_query = new KBS_Replies_Query( $args );
		$replies       = $replies_query->get_replies();
        $latest_reply  = false;
        $auto_expand   = true;
		$notes = kbs_get_notes( $_POST['kbs_ticket_id'] );
		$all = array_merge($replies,$notes);
		uasort( $all, 'helptain_sort_data_by_date' );
		$i = 0;
		if ( ! empty( $all ) )	{
			foreach( $all as $reply )	{

                if ( ! $latest_reply )  {
                    $output .= sprintf( '<input type="hidden" id="kbs-latest-reply" name="kbs_latest_reply" value="%s">', $reply->ID );
                    $latest_reply = true;
                }


				$output      .= '<div class="kbs_historic_replies_wrapper " expanded="true">';
				if ( $reply->comment_ID ) {
					$output .= kbs_get_note_html( $reply, $_POST['kbs_ticket_id'] );
				} else {
					$output .= kbs_get_reply_html( $reply, $_POST['kbs_ticket_id'], true );
				}

                $output .= '</div>';

                $count_expand++;
			}

			if ( isset( $args['page'] ) && $args['page'] < $replies_query->pages )	{
				$output .= sprintf(
					'<p class="kbs-replies-load-more"><a class="button button-secondary button-small" id="kbs-replies-next-page" data-ticket-id="%d" data-load-page="%d">%s</a></p>',
					(int)$_POST['kbs_ticket_id'],
					( $args['page'] + 1 ),
					__( 'Load More', 'kb-support' )
				);
			}

		} else    {
            $output .= '<input type="hidden" id="kbs-latest-reply" name="kbs_latest_reply" value="0">';
        }

	}

	echo $output;
	die();
} // kbs_ajax_display_ticket_replies
add_action( 'wp_ajax_kbs_display_ticket_replies', 'kbs_ajax_display_ticket_replies' );

/**
 * Display replies for ticket management page.
 *
 * @since	1.2.6
 * @return	string
 */
function kbs_ajax_load_front_end_replies()	{
	$output = '';

    $time_format = get_option( 'time_format' );
    $date_format = get_option( 'date_format' );
    $user_id     = get_current_user_id();
	$number      = get_user_meta( $user_id, '_kbs_load_replies', true );
    $number      = ! empty( $number ) ? (int)$number : 0;
    $ticket_id   = (int)$_POST['kbs_ticket_id'];

    $args = array(
        'ticket_id' => $ticket_id,
        'number'    => $number,
        'page'      => isset( $_POST['kbs_page'] ) ? (int)$_POST['kbs_page'] : null
    );

    $replies_query = new KBS_Replies_Query( $args );
    $replies       = $replies_query->get_replies();

    ob_start();

    if ( ! empty( $replies ) ) :
        foreach( $replies as $reply ) :
            $reply_content = apply_filters( 'the_content', $reply->post_content );
            $reply_content = str_replace( ']]>', ']]&gt;', $reply_content );
            $files         = kbs_ticket_has_files( $reply->ID );
            $file_count    = ( $files ? count( $files ) : false );
            $heading       = apply_filters( 'kbs_front_replies_title', sprintf(
                '%s by %s',
                date_i18n( $time_format . ' \o\n ' . $date_format, strtotime(  $reply->post_date ) ),
                kbs_get_reply_author_name( $reply->ID, true )
            ) );
            ?>
            <div id="kbs-reply-card" class="card kbs_replies_wrapper">
                <div class="card-header kbs-replies-row-header">
                    <span class="kbs-replies-row-title">
                        <?php echo $heading; ?>
                    </span>

                    <span class="kbs-replies-row-actions">
                        <a href="#" class="toggle-view-reply-option-section" data-toggle="collapse" data-target="#kbs_ticket_reply-<?php echo $reply->ID; ?>" aria-expanded="false" aria-controls="kbs_ticket_reply-<?php echo $reply->ID; ?>" data-key="<?php echo $reply->ID; ?>">
                            <?php _e( 'View Reply', 'kb-support' ); ?>
                        </a>
                    </span>
                </div>

                <div id="kbs_ticket_reply-<?php echo $reply->ID; ?>" class="collapse" aria-labelledby="kbs_ticket_reply-<?php echo $reply->ID; ?>-heading" data-parent="#kbs-ticket-replies">
                    <div class="card-body">
                        <?php echo $reply_content; ?>
                        <?php if ( $files ) : ?>
                        <div class="kbs_ticket_reply_files">
                            <strong><?php printf(
                                __( 'Attached Files (%d)', 'kb-support' ),
                                $file_count
                            ); ?></strong>
                            <ol>
                                <?php foreach( $files as $file ) : ?>
                                    <li>
                                        <a href="<?php echo wp_get_attachment_url( $file->ID ); ?>" target="_blank">
                                            <?php echo basename( get_attached_file( $file->ID ) ); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        </div>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
            <div id="kbs-loading-replies"></div>

        <?php endforeach; ?>

        <?php if ( isset( $args['page'] ) && (int)$args['page'] < $replies_query->pages ) :
            $next_page = (int)$args['page'] + 1;
        else :
            $next_page = 0;
        endif; ?>

    <?php endif;

	$output = ob_get_clean();
    $response = array(
        'replies'   => $output,
        'next_page' => $next_page
    );
	wp_send_json_success( $response );
} // kbs_ajax_load_front_end_replies
add_action( 'wp_ajax_kbs_load_front_end_replies', 'kbs_ajax_load_front_end_replies' );
add_action( 'wp_ajax_nopriv_kbs_load_front_end_replies', 'kbs_ajax_load_front_end_replies' );

/**
 * Mark a reply as read.
 *
 * @since   1.2
 * @return  void
 */
function kbs_ajax_mark_reply_as_read() {

    $reply_id = isset( $_POST['reply_id'] )    ? $_POST['reply_id']    : 0;

    if ( ! empty( $reply_id ) )   {
        kbs_mark_reply_as_read( $reply_id );
    }

    wp_send_json_success();
} // kbs_ajax_mark_reply_as_read
add_action( 'wp_ajax_kbs_read_ticket_reply', 'kbs_ajax_mark_reply_as_read' );
add_action( 'wp_ajax_nopriv_kbs_read_ticket_reply', 'kbs_ajax_mark_reply_as_read' );

/**
 * Validate a ticket reply form.
 *
 * @since	1.0
 * @return	void
 */
function kbs_ajax_validate_ticket_reply_form()	{

	$error  = false;
	$ticket = absint( $_POST['kbs_ticket_id'] );
	$email  = sanitize_email( $_POST['kbs_confirm_email'] );

	kbs_do_honeypot_check( $_POST );

	if ( empty( $_POST['kbs_reply'] ) )	{
		$error = kbs_get_notices( 'missing_reply', true );
		$field = 'kbs_reply';
	} elseif ( empty( $email ) || ! is_email( $email ) )	{
		$error = kbs_get_notices( 'email_invalid', true );
		$field = 'kbs_confirm_email';
	} elseif ( ! empty( $_FILES ) && ! empty( $_FILES['name'][ kbs_get_max_file_uploads() ] ) )	{
		$error = kbs_get_notices( 'max_files', true );
		$field = 'kbs_files';
	}

	if ( ! empty( $error ) )	{
		wp_send_json( array(
			'error' => $error,
			'field' => $field
		) );
	}

	$ticket   = new KBS_Ticket( $ticket );
	$customer = new KBS_Customer( $email );

	/**
	 * Allow plugin developers to filter the customer object in case users other than
	 * the original person logging the ticket can reply.
	 *
	 * @since	1.0
	 */
	$customer = apply_filters( 'kbs_reply_customer_validate', $customer );

	if ( empty( $customer->id ) || $customer->id != $ticket->customer_id )	{
		$email_valid = false;

		if ( ! $email_valid && kbs_participants_enabled() )	{
			$email_valid = kbs_is_ticket_participant( $ticket->ID, $email );
		}

		$email_valid = apply_filters( 'kbs_validate_customer_reply_email', $email_valid, $customer, $ticket );

		if ( ! $email_valid )	{
			wp_send_json( array(
				'error' => kbs_get_notices( 'email_invalid', true ),
				'field' => 'kbs_confirm_email'
			) );
		}
	}

	/**
	 * Allow plugins to perform additional validation.
	 *
	 * @since	1.0
	 */
	do_action( 'kbs_validate_ticket_reply_form', $ticket, $customer );

	wp_send_json_success( array( 'error' => $error ) );

} // kbs_ajax_validate_ticket_reply_form
add_action( 'wp_ajax_kbs_validate_ticket_reply_form', 'kbs_ajax_validate_ticket_reply_form' );
add_action( 'wp_ajax_nopriv_kbs_validate_ticket_reply_form', 'kbs_ajax_validate_ticket_reply_form' );

/**
 * Adds a note to a ticket.
 *
 * @since	1.0
 * @return	void
 */
function kbs_ajax_ticket_insert_note()	{
	$note_id = kbs_insert_note( $_POST['ticket_id'], $_POST['note_content'] );

	wp_send_json( array( 'note_id' => $note_id ) );
} // kbs_ajax_ticket_insert_note
add_action( 'wp_ajax_kbs_insert_ticket_note', 'kbs_ajax_ticket_insert_note' );

/**
 * Display notes for ticket post metabox.
 *
 * @since	1.0
 * @return	str
 */
function kbs_ajax_display_ticket_notes()	{
	$output = '';

	if ( ! empty( $_POST['kbs_note_id'] ) )	{
		$output .= kbs_get_note_html( $_POST['kbs_note_id'], $_POST['kbs_ticket_id'], isset($_POST['expand']) );
	} else	{

		$notes  = kbs_get_notes( $_POST['kbs_ticket_id'] );

		if ( ! empty( $notes ) )	{
			foreach( $notes as $note )	{
				$output .= '<div class="kbs_ticket_notes_wrapper">';
					$output .= kbs_get_note_html( $note, $_POST['kbs_ticket_id'] );
				$output .= '</div>';
			}
		}

	}

	echo $output;
	die();
} // kbs_ajax_display_ticket_notes
add_action( 'wp_ajax_kbs_display_ticket_notes', 'kbs_ajax_display_ticket_notes' );

/**
 * Adds a new field to a form.
 *
 * @since	1.0
 * @return	void
 */
function kbs_ajax_add_form_field()	{

	if ( ! empty( $_POST['form_id'] ) )	{
		$form     = new KBS_Form( $_POST['form_id'] );
		$field_id = $form->add_field( $_POST );
	}

	if ( ! empty( $field_id ) )	{
		$results['id']      = $field_id;
		$results['message'] = 'field_added';
	} else	{
		$results['message'] = 'field_add_fail';
	}

	wp_send_json( $results );

} // kbs_ajax_add_form_field
add_action( 'wp_ajax_kbs_add_form_field', 'kbs_ajax_add_form_field' );

/**
 * Updates a field.
 *
 * @since	1.0
 * @return	void
 */
function kbs_ajax_save_form_field()	{

	if ( ! empty( $_POST['field_id'] ) )	{
		$form     = new KBS_Form( $_POST['form_id'] );
		$field_id = $form->save_field( $_POST );
	}

	if ( ! empty( $field_id ) )	{
		$results['id']      = $field_id;
		$results['message'] = 'field_saved';
	} else	{
		$results['message'] = 'field_save_fail';
	}

	wp_send_json( $results );

} // kbs_ajax_save_form_field
add_action( 'wp_ajax_kbs_save_form_field', 'kbs_ajax_save_form_field' );

/**
 * Sets the order of the form fields.
 *
 * @since	1.0
 * @return	void
 */
function kbs_ajax_order_form_fields()	{

	foreach( $_POST['fields'] as $order => $id )	{
		wp_update_post( array(
			'ID'			=> $id,
			'menu_order'	=> $order++
		) );
	}
}
add_action( 'wp_ajax_kbs_order_form_fields', 'kbs_ajax_order_form_fields' );

/**
 * Validate a ticket submission form.
 *
 * @since	1.0
 * @return	void
 */
function kbs_ajax_validate_form_submission()	{

	$form            = new KBS_Form( $_POST['kbs_form_id'] );
	$error           = false;
	$agree_to_policy = kbs_get_option( 'show_agree_to_privacy_policy', false );
	$privacy_page    = kbs_get_privacy_page();
	$agree_to_terms  = kbs_get_option( 'show_agree_to_terms', false );
	$agree_text      = kbs_get_option( 'agree_terms_text', false );
	$field           = '';

	if ( ! kbs_user_can_submit() )	{
		wp_send_json( array(
			'error' => kbs_get_notices( 'need_login', true ),
			'field' => $field
		) );
	}

	$fields = $form->get_fields();

	foreach ( $fields as $field )	{

		$settings = $form->get_field_settings( $field->ID );

		if ( ! empty( $settings['required'] ) && empty( $_POST[ $field->post_name ] ) )	{

			$error = kbs_form_submission_errors( $field->ID, 'required' );
			$field = $field->post_name;

		} elseif ( 'file_upload' == $settings['type'] )	{

			if ( ! empty( $_FILES ) && ! empty( $_FILES['name'][ kbs_get_max_file_uploads() ] ) )	{

				$error = kbs_get_notices( 'max_files', true );
				$field = 'kbs_files';

			}

		} elseif ( 'email' == $settings['type'] || 'customer_email' == $settings['mapping'] )	{

			if ( ! is_email( $_POST[ $field->post_name ] ) )	{
				$error = kbs_form_submission_errors( $field->ID, 'invalid_email' );
				$field = $field->post_name;
			} elseif ( kbs_check_email_from_submission( $_POST[ $field->post_name ] ) )	{
				$error = kbs_form_submission_errors( $field->ID, 'process_error' );
				$field = $field->post_name;
			}

		} elseif ( 'recaptcha' == $settings['type'] && ! kbs_validate_recaptcha( $_POST['g-recaptcha-response'] ) )	{

			$error = kbs_form_submission_errors( $field->ID, 'google_recaptcha' );
			$field = $field->post_name;

		} else	{
			/**
			 * Allow plugins to perform additional validation on individual field types.
			 *
			 * @since	1.0
			 */
			$error = apply_filters( 'kbs_validate_form_field_' . $settings['type'], $error, $field, $settings, $_POST[ $field->post_name ], $fields );
		}

		if ( $error )	{
			wp_send_json( array(
				'error' => $error,
				'field' => $field
			) );
		}

	}

	if ( $agree_to_policy && $privacy_page && empty( $_POST['kbs_agree_privacy_policy'] ) )	{
		wp_send_json( array(
			'error' => kbs_form_submission_errors( 0, 'agree_to_policy' ),
			'field' => 'kbs-agree-privacy-policy'
		) );
	}

	if ( $agree_to_terms && $agree_text && empty( $_POST['kbs_agree_terms'] ) )	{
		wp_send_json( array(
			'error' => kbs_form_submission_errors( 0, 'agree_to_terms' ),
			'field' => 'kbs-agree-terms'
		) );
	}

	/**
	 * Allow plugins to perform additional form validation.
	 *
	 * @since	1.0
	 */
	$error = apply_filters( 'kbs_validate_form_submission', $error, $form, $_POST );

	if ( $error )	{
		if ( $error )	{
			wp_send_json( array(
				'error' => $error,
				'field' => $field
			) );
		}
	}

	wp_send_json_success( array( 'error' => $error ) );

} // kbs_ajax_validate_form_submission
add_action( 'wp_ajax_kbs_validate_ticket_form', 'kbs_ajax_validate_form_submission' );
add_action( 'wp_ajax_nopriv_kbs_validate_ticket_form', 'kbs_ajax_validate_form_submission' );

/**
 * Retrieves customer data.
 *
 * @since	1.2
 * @return	void
 */
function kbs_ajax_get_customer_data()	{

	$response = array(
		'name'  => '',
		'email' => '',
		'phone' => '',
		'url'   => ''
	);

	$customer = new KBS_Customer( $_POST['customer_id'] );

	if ( $customer )	{
		$response = array(
			'name'  => ! empty( $customer->name )          ? esc_attr( $customer->name )          : '',
			'email' => ! empty( $customer->email )         ? esc_attr( $customer->email )         : '',
			'phone' => ! empty( $customer->primary_phone ) ? esc_attr( $customer->primary_phone ) : '',
			'url'   => ! empty( $customer->website )       ? esc_url( $customer->website )        : ''
		);
	}

	wp_send_json( $response );

} // kbs_ajax_get_customer_data
add_action( 'wp_ajax_kbs_get_customer_data', 'kbs_ajax_get_customer_data' );

/**
 * Adds a new customer via the customer screen.
 *
 * @since	1.0
 * @return	void
 */
function kbs_ajax_add_customer()	{

	if ( empty( $_POST['customer_name'] ) )	{
		wp_send_json( array(
			'error'   => true,
			'message' => __( 'Please enter a customer name.', 'kb-support' )
		) );
	}

	if ( ! is_email( $_POST['customer_email'] ) )	{
		wp_send_json( array(
			'error'   => true,
			'message' => __( 'Invalid email address.', 'kb-support' )
		) );
	}

	// If a WP user exists with this email, link the customer account
	$user_id   = 0;
	$user_data = get_user_by( 'email', $_POST['customer_email'] );
	if ( ! empty( $user_data ) )	{
		$user_id = $user_data->ID;
	}

	$customer      = new stdClass;
    $company_id    = isset( $_POST['customer_company'] ) ? $_POST['customer_company'] : 0;
	$customer_data = array(
		'name'       => strip_tags( stripslashes( $_POST['customer_name'] ) ),
		'company_id' => kbs_sanitize_company_id( $company_id ),
		'email'      => $_POST['customer_email'],
		'user_id'    => $user_id
	);

	$customer_data = apply_filters( 'kbs_add_customer_info', $customer_data );
	$customer_data = array_map( 'sanitize_text_field', $customer_data );

	$customer = new KBS_Customer( $customer_data['email'] );

	if ( ! empty( $customer->id ) ) {
		wp_send_json( array(
			'error'   => true,
			'message' => sprintf(
				__( 'Customer email address already exists for customer #%s &ndash; %s.', 'kb-support' ), $customer->id, $customer->name )
		) );
	}

	$customer->create( $customer_data );

	if ( empty( $customer->id ) )	{
		wp_send_json( array(
			'error'    => true,
			'message'  => __( 'Could not create customer.', 'kb-support' )
		) );
	}

	wp_send_json( array(
		'error'       => false,
		'redirect' => admin_url( 'edit.php?post_type=kbs_ticket&page=kbs-customers&view=userdata&id=' . $customer->id . '&kbs-message=customer_created' )
	) );

} // kbs_ajax_add_customer
add_action( 'wp_ajax_kbs_add_customer', 'kbs_ajax_add_customer' );

/**
 * Adds a new customer via the ticket screen.
 *
 * @since	1.0
 * @return	void
 */
function kbs_ajax_new_customer_for_ticket()	{

	if ( ! is_email( $_POST['customer_email'] ) )	{
		wp_send_json_error( array(
			'message' => __( 'Invalid email address.', 'kb-support' )
		) );
	}

	// If a WP user exists with this email, link the customer account
	$user_id   = 0;
	$user_data = get_user_by( 'email', $_POST['customer_email'] );
	if ( ! empty( $user_data ) )	{
		$user_id = $user_data->ID;
	}

    $customer      = new stdClass;
    $company_id    = isset( $_POST['customer_company'] ) ? $_POST['customer_company'] : 0;

    if ( '-1' == $company_id )  {
        $company_id = 0;
    }

	$customer_data = array(
		'name'       => strip_tags( stripslashes( $_POST['customer_name'] ) ),
		'company_id' => kbs_sanitize_company_id( $company_id ),
		'email'      => $_POST['customer_email'],
		'user_id'    => $user_id
	);

	$customer_data = apply_filters( 'kbs_new_customer_for_ticket_info', $customer_data );
	$customer_data = array_map( 'sanitize_text_field', $customer_data );

	$customer = new KBS_Customer( $customer_data['email'] );

    if ( ! empty( $customer->id ) ) {
		wp_send_json_error( array(
			'message' => sprintf(
				__( 'Customer email address already exists for customer #%s - %s.', 'kb-support' ), $customer->id, $customer->name )
		) );
	}

	$customer->create( $customer_data );

	if ( empty( $customer->id ) )	{
		wp_send_json_error( array(
			'message' => __( 'Could not create customer.', 'kb-support' )
		) );
	}

    $customer_id = absint( $customer->id );
    $option = sprintf(
        '<option value="%d">%s</option>',
        $customer_id,
        esc_html( $customer->name )
    );
    wp_send_json_success( array(
        'id'     => $customer_id,
        'option' => $option
    ) );
} // kbs_ajax_new_customer_for_ticket
add_action( 'wp_ajax_kbs_new_customer_for_ticket', 'kbs_ajax_new_customer_for_ticket' );

/**
 * Searches for users via ajax and returns a list of results.
 *
 * @since	1.0
 * @return	void
 */
function kbs_ajax_search_users()	{

	if ( current_user_can( 'manage_ticket_settings' ) ) {

		$search_query = trim( $_POST['user_name'] );
		$exclude      = trim( $_POST['exclude'] );

		$get_users_args = array(
			'number' => 9999,
			'search' => $search_query . '*'
		);

		if ( ! empty( $exclude ) ) {
			$exclude_array = explode( ',', $exclude );
			$get_users_args['exclude'] = $exclude_array;
		}

		$get_users_args = apply_filters( 'kbs_search_users_args', $get_users_args );

		$found_users = apply_filters( 'kbs_ajax_found_users', get_users( $get_users_args ), $search_query );

		$user_list = '<ul>';
		if ( $found_users ) {
			foreach( $found_users as $user ) {
				$user_list .= '<li><a href="#" data-userid="' . esc_attr( $user->ID ) . '" data-login="' . esc_attr( $user->user_login ) . '">' . esc_html( $user->user_login ) . '</a></li>';
			}
		} else {
			$user_list .= '<li>' . __( 'No users found', 'kb-support' ) . '</li>';
		}

		$user_list .= '</ul>';

		echo json_encode( array( 'results' => $user_list ) );

	}
	die();
} // kbs_ajax_search_users
add_action( 'wp_ajax_kbs_search_users', 'kbs_ajax_search_users' );

/**
 * Perform article search.
 *
 * @since	1.0
 * @return	void
 */
function kbs_ajax_article_search()	{

	$output      = false;
	$results     = false;
	$search_term = sanitize_text_field( $_POST['term'] );

	$args = array(
		'number'  => kbs_get_option( 'article_num_posts_ajax', 5 ),
		's'       => $search_term,
		'orderby' => 'relevance'
	);

	if ( ! is_user_logged_in() && kbs_get_option( 'article_hide_restricted_ajax' ) )	{
		$args['post__not_in'] = kbs_get_restricted_articles();
	}

	$articles_query = new KBS_Articles_Query( $args );
	$articles       = $articles_query->get_articles();

	if ( ! empty( $articles ) )	{

		$output = '<ul>';

		foreach( $articles as $article )	{
			$output .= '<li>';
				$output .= '<a href="' . get_post_permalink( $article->ID ) . '" target="_blank">';
					$output .= esc_attr( $article->post_title );
				$output .= '</a>';

				if ( kbs_get_article_excerpt_length() > 0 )	{
					$output .= '<br />';
					$output .= kbs_get_article_excerpt( $article->ID );
				}

			$output .= '</li>';
		}

		$output .='</ul>';

		if ( $articles_query->total_articles > $args['number'] )	{

			$search_url = add_query_arg( array(
				'kbs_action' => 'search_articles',
				's_article'  => $search_term
			), site_url() );

			$output .= '<a href="' . $search_url . '" target="_blank">';
				$output .= sprintf( __( 'View all %d possible solutions.', 'kb-support' ), $articles_query->total_articles );
			$output .= '</a>';

		}

		$results = true;
	}

	wp_send_json( array(
		'articles' => $output
	) );

} // kbs_ajax_article_search
add_action( 'wp_ajax_kbs_ajax_article_search', 'kbs_ajax_article_search' );
add_action( 'wp_ajax_nopriv_kbs_ajax_article_search', 'kbs_ajax_article_search' );
