<?php
/**
 * User Functions
 *
 * Functions related to users
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
 * Auto create WP User when a KBS customer is created.
 *
 * @since   1.5.2
 * @param   int     $customer_id    KBS Customer ID
 * @param   array   $args           Array of args passed to the create customer function
 * @return  void
 */
function kbs_auto_create_user_from_customer( $customer_id )  {
    if ( ! kbs_get_option( 'auto_add_user' ) || empty( $customer_id ) )    {
        return;
    }

    $customer = new KBS_Customer( $customer_id );

    if ( ! $customer || ! empty( $customer->user_id ) )    {
        return;
    }

    $user_id     = false;
    $update_data = array();

    // Check if a user exists and link to customer if it does
    foreach( $customer->emails as $email )  {
        $user = get_user_by( 'email', $email );

        if ( $user )    {
            $user_id = $user->ID;
            break;
        }
    }

    // No user exists, create one
    if ( ! $user_id )   {
        $name       = explode( ' ', $customer->name );
        $first_name = ! empty( $name[0] ) ? $name[0] : '';
        $last_name  = ! empty( $name[1] ) ? $name[1] : '';

        $user_data  = array(
            'user_login'   => $customer->email,
            'user_email'   => $customer->email,
            'user_pass'    => wp_generate_password(),
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'role'         => kbs_get_option( 'default_role' )
        );

        $user_id = wp_insert_user( $user_data );
    }

    // Add User ID to customer
    if ( $user_id && ! is_wp_error( $user_id ) )    {
        $update_data['user_id'] = (int) $user_id;

        if ( $customer->update( $update_data ) )    {
            wp_new_user_notification( $user_id, null, 'user' );
        }
    }
} // kbs_auto_create_user_from_customer
add_filter( 'kbs_customer_post_create', 'kbs_auto_create_user_from_customer' );

/**
 * Registers user profile fields.
 *
 * Fields should be registered as field_name => bool (true for agent only, otherwise false for all users)
 *
 * @since	1.0
 * @param	object	$user	WP_User object
 * @return	array	Array of user profile field ids
 */
function kbs_register_user_profile_fields( $user )	{
	if ( kbs_is_agent( $user->ID ) )	{
		$type   = 'agent';
		$fields = array(
            0 => 'replies_location',
			1 => 'replies_to_load',
			2 => 'redirect_reply',
			3 => 'redirect_closed',
            4 => 'reply_alerts'
		);

		if ( kbs_departments_enabled() )    {
			$fields[10] = 'kbs_departments';
		}
	} else	{
		$type   = 'customer';
		$fields = array(
			0 => 'replies_to_load',
            1 => 'closed_tickets'
		);
	}

	// For backwards compatibility
	if ( 'agent' == $type )	{
		$fields = apply_filters( 'kbs_user_profile_fields', $fields );
	}

	return apply_filters( "kbs_{$type}_user_profile_fields", $fields );
} // kbs_register_user_profile_fields

/**
 * Output user profile fields.
 *
 * @since	1.0
 * @param	object	$user	The WP_User object
 * @return	array	Array of user profile fields
 */
function kbs_output_user_profile_fields( $user )	{
    if ( get_current_user_id() != $user->ID && ! current_user_can( 'manage_ticket_settings' ) )  {
        return;
    }

	$fields = kbs_register_user_profile_fields( $user );

	if ( ! empty( $fields ) )	{
		$type = kbs_is_agent( $user->ID ) ? 'agent' : 'customer';
		ob_start(); ?>

		<h2><?php _e( 'KB Support Settings', 'kb-support' ); ?></h2>
		<table class="form-table">
			<?php do_action( "kbs_display_{$type}_user_profile_fields", $user, $fields ); ?>

			<?php // For backwards compatibility ?>
			<?php if ( 'agent' == $type )	{
				do_action( 'kbs_display_user_profile_fields', $user, $fields );
			} ?>
		</table>

		<?php echo ob_get_clean();
	}
} // kbs_output_user_profile_fields
add_action( 'show_user_profile', 'kbs_output_user_profile_fields', 11 );
add_action( 'edit_user_profile', 'kbs_output_user_profile_fields', 11 );

/**
 * Adds the Hide Closed Tickets option field to the user profile for non-agents.
 *
 * @since	1.2.6
 * @param   object	$user	The WP_User object
 */
function kbs_render_user_profile_hide_closed_tickets_field( $user )  {
	$hide_closed = kbs_customer_maybe_hide_closed_tickets( $user->ID );
	ob_start(); ?>

    <tr>
        <th><label for="kbs-agent-hide-closed"><?php printf( __( 'Hide Closed %s', 'kb-support' ), kbs_get_ticket_label_plural() ); ?></label></th>
        <td>
            <input type="checkbox" name="kbs_hide_closed" id="kbs-hide-closed" value="1"<?php checked( 1, $hide_closed ); ?> />
            <p class="description"><?php printf( __( 'Enable to hide closed %s from the %s Manager screen.', 'kb-support' ), kbs_get_ticket_label_plural( true ), kbs_get_ticket_label_singular() ); ?></p>
        </td>
    </tr>

	<?php echo ob_get_clean();
} // kbs_render_user_profile_hide_closed_tickets_field
add_action( 'kbs_display_customer_user_profile_fields', 'kbs_render_user_profile_hide_closed_tickets_field', 5 );

/**
 * Adds the tickets per page option field to the user profile for non-agents.
 *
 * @since	1.4
 * @param   object	$user	The WP_User object
 */
function kbs_render_user_profile_tickets_per_page_field( $user )  {
	$tickets_per_page = kbs_get_customer_tickets_per_page( $user->ID );
	ob_start(); ?>

    <tr>
        <th scope="row">
            <label for="kbs-customer-tickets-per-page">
				<?php printf( __( '%s per Page', 'kb-support' ), kbs_get_ticket_label_plural() ); ?>
			</label>
        </th>
        <td>
            <input class="small-text" type="number" name="kbs_tickets_per_page" id="kbs-customer-tickets-per-page" value="<?php echo (int)$tickets_per_page; ?>" step="1" min="1" />
            <p class="description"><?php printf( __( 'Choose the number of %s to display per page.', 'kb-support' ), kbs_get_ticket_label_plural( true ) ); ?></p>
        </td>
    </tr>

	<?php echo ob_get_clean();
} // kbs_render_user_profile_tickets_per_page_field
add_action( 'kbs_display_customer_user_profile_fields', 'kbs_render_user_profile_tickets_per_page_field', 5 );

/**
 * Adds the ticket orderby option field to the user profile.
 *
 * @since	1.4
 * @param   object	$user	The WP_User object
 */
function kbs_render_user_profile_tickets_orderby_field( $user )  {
	$orderby = get_user_meta( $user->ID, '_kbs_tickets_orderby', true );
	$orderby = '' != $orderby ? esc_attr( $orderby ) : 'date';
	$options = kbs_get_ticket_orderby_options();

	ob_start(); ?>

    <tr>
        <th scope="row">
            <label for="kbs-customer-tickets-orderby">
				<?php printf( __( 'Default %s Orderby', 'kb-support' ), kbs_get_ticket_label_plural() ); ?>
			</label>
        </th>
        <td>
            <select name="kbs_tickets_orderby" id="kbs-customer-tickets-orderby">
				<?php foreach( $options as $value => $label ) : ?>
					<?php $selected = selected( $orderby, $value, false ); ?>
					<?php printf(
						'<option value="%s"%s>%s</option>',
						$value,
						$selected,
						$label
					); ?>
				<?php endforeach; ?>
			</select>
            <p class="description"><?php printf( __( 'Select how you would like %s to be ordered by default.', 'kb-support' ), kbs_get_ticket_label_plural( true ) ); ?></p>
        </td>
    </tr>

	<?php echo ob_get_clean();

} // kbs_render_user_profile_tickets_orderby_field
add_action( 'kbs_display_agent_user_profile_fields', 'kbs_render_user_profile_tickets_orderby_field', 5 );
add_action( 'kbs_display_customer_user_profile_fields', 'kbs_render_user_profile_tickets_orderby_field', 5 );

/**
 * Adds the ticket order option field to the user profile.
 *
 * @since	1.4
 * @param   object	$user	The WP_User object
 */
function kbs_render_user_profile_tickets_order_field( $user )  {
	$order = get_user_meta( $user->ID, '_kbs_tickets_order', true );
	$order = '' != $order ? esc_attr( $order ) : 'DESC';
	$options = array(
		'DESC' => __( 'Descending Order', 'kb-support' ),
		'ASC'  => __( 'Ascending Order', 'kb-support' )
	);

	ob_start(); ?>

    <tr>
        <th scope="row">
            <label for="kbs-customer-tickets-order">
				<?php printf( __( 'Default %s Order', 'kb-support' ), kbs_get_ticket_label_plural() ); ?>
			</label>
        </th>
        <td>
            <select name="kbs_tickets_order" id="kbs-customer-tickets-order">
				<?php foreach( $options as $value => $label ) : ?>
					<?php $selected = selected( $order, $value, false ); ?>
					<?php printf(
						'<option value="%s"%s>%s</option>',
						$value,
						$selected,
						$label
					); ?>
				<?php endforeach; ?>
			</select>
            <p class="description"><?php printf( __( 'Select whether to order %s in ascending or descending order.', 'kb-support' ), kbs_get_ticket_label_plural( true ) ); ?></p>
        </td>
    </tr>

	<?php echo ob_get_clean();
} // kbs_render_user_profile_tickets_order_field
add_action( 'kbs_display_agent_user_profile_fields', 'kbs_render_user_profile_tickets_order_field', 5 );
add_action( 'kbs_display_customer_user_profile_fields', 'kbs_render_user_profile_tickets_order_field', 5 );


/**
 * Adds the Replies to Load option field to the user profile for agents.
 *
 * @since	1.2
 * @param   obj		$user	The WP_User object
 */
function kbs_render_user_profile_replies_to_load_field( $user )  {
	$replies_to_load = get_user_meta( $user->ID, '_kbs_load_replies', true );

	if ( '' == $replies_to_load )	{
		$replies_to_load = kbs_is_agent( $user->ID ) ? 0 : kbs_get_option( 'replies_to_load' );
	}

	ob_start(); ?>

    <tr>
        <th scope="row">
            <label for="kbs-agent-load-replies"><?php _e( 'Replies to Load', 'kb-support' ); ?></label>
        </th>
        <td>
            <input class="small-text" type="number" name="kbs_load_replies" id="kbs-load-replies" value="<?php echo (int)$replies_to_load; ?>" step="1" min="0" />
            <p class="description"><?php printf( __( 'Choose the number of replies to initially load when accessing the %s page. <code>0</code> loads all.', 'kb-support' ), kbs_get_ticket_label_plural( true ) ); ?></p>
        </td>
    </tr>

	<?php echo ob_get_clean();
} // kbs_render_user_profile_replies_to_load_field
add_action( 'kbs_display_agent_user_profile_fields', 'kbs_render_user_profile_replies_to_load_field', 5 );
add_action( 'kbs_display_customer_user_profile_fields', 'kbs_render_user_profile_replies_to_load_field', 5 );

/**
 * Adds the department options field to the user profile for agents.
 *
 * @since	1.3.4
 * @param   object	$user	The WP_User object
 */
function kbs_render_agent_user_profile_reply_alerts_field( $user )  {
    if ( ! kbs_departments_enabled() || ! kbs_is_agent( $user->ID ) || ( get_current_user_id() != $user->ID && ! current_user_can( 'manage_ticket_settings' ) ) )  {
        return;
    }

    $alert   = kbs_alert_agent_ticket_reply( $user->ID );
    $checked = checked( $alert, true, false );
    $label   = sprintf(
        __( 'When enabled, agents will be alerted if a new reply is added whilst they are editing a %s', 'kb-support' ),
        kbs_get_ticket_label_singular( true )
    );

    ob_start(); ?>

    <tr>
        <th scope="row">
            <?php printf( __( '%s Reply Alerts', 'kb-support' ), kbs_get_ticket_label_singular() ); ?>
        </th>
        <td>
            <?php printf(
                '<input type="checkbox" name="kbs_agent_reply_alerts" id="kbs-agent-reply-alerts" value="1"%s />',
                $checked
            ); ?>
            <label for="kbs-agent-reply-alerts"><?php echo $label; ?></label>
        </td>
    </tr>

    <?php echo ob_get_clean();

} // kbs_render_agent_user_profile_reply_alerts_field
add_action( 'kbs_display_agent_user_profile_fields', 'kbs_render_agent_user_profile_reply_alerts_field', 5 );

/**
 * Adds the department options field to the user profile for agents.
 *
 * @since	1.2
 * @param   object	$user	The WP_User object
 */
function kbs_render_agent_user_profile_department_field( $user )  {
    if ( ! kbs_departments_enabled() || ! kbs_is_agent( $user->ID ) || ( get_current_user_id() != $user->ID && ! current_user_can( 'manage_ticket_settings' ) ) )  {
        return;
    }

    $departments = kbs_get_departments();

    if ( $departments ) {
        $read_only = ! current_user_can( 'manage_ticket_settings' ) ? ' disabled' : '';
		$output    = array();
        ob_start(); ?>

        <tr>
            <th scope="row"><?php _e( 'Departments', 'kb-support' ); ?></th>
            <td>
                <?php foreach( $departments as $department ) : ?>
                    <?php $output[] = sprintf(
						'<input type="checkbox" name="kbs_departments[]" id="%1$s" value="%2$s"%3$s%4$s /> <label for="%1$s">%5$s</label>',
						$department->slug,
						$department->term_id,
						kbs_agent_is_in_department( $department->term_id, $user->ID ) ? ' checked="checked"' : '',
                        $read_only,
						$department->name
					); ?>
                <?php endforeach; ?>
                <?php echo implode( '<br />', $output ); ?>
            </td>
        </tr>

        <?php echo ob_get_clean();

    }
} // kbs_render_agent_user_profile_department_field
add_action( 'kbs_display_agent_user_profile_fields', 'kbs_render_agent_user_profile_department_field', 10 );

/**
 * Saves the tickets per page field.
 *
 * @since	1.4
 * @param	int		$user_id	WP User ID
 */
function kbs_save_user_tickets_per_page( $user_id ) {

	if ( kbs_is_agent( $user_id ) || ! current_user_can( 'edit_user', $user_id ) )	{
		return;
	}

	$default  = get_option( 'posts_per_page', 10 );
	$per_page = isset( $_POST['kbs_tickets_per_page'] ) ? $_POST['kbs_tickets_per_page'] : $default;

	update_user_meta( $user_id, '_kbs_tickets_per_page', $per_page );

} // kbs_save_user_tickets_per_page
add_action( 'personal_options_update', 'kbs_save_user_tickets_per_page' );
add_action( 'edit_user_profile_update', 'kbs_save_user_tickets_per_page' );

/**
 * Saves the hide closed tickets field.
 *
 * @since	1.2.6
 * @param	int		$user_id	WP User ID
 */
function kbs_save_user_hide_closed_tickets( $user_id ) {

	if ( kbs_is_agent( $user_id ) || ! current_user_can( 'edit_user', $user_id ) )	{
		return;
	}

	$hide = isset( $_POST['kbs_hide_closed'] ) ? absint( $_POST['kbs_hide_closed'] ) : 0;

	update_user_meta( $user_id, '_kbs_hide_closed', $hide );

} // kbs_save_user_hide_closed_tickets
add_action( 'personal_options_update', 'kbs_save_user_hide_closed_tickets' );
add_action( 'edit_user_profile_update', 'kbs_save_user_hide_closed_tickets' );

/**
 * Saves the tickets orderby field.
 *
 * @since	1.4
 * @param	int		$user_id	WP User ID
 */
function kbs_save_user_tickets_orderby( $user_id ) {

	if ( ! current_user_can( 'edit_user', $user_id ) )	{
		return;
	}

	$orderby = sanitize_text_field( $_POST['kbs_tickets_orderby'] );

	update_user_meta( $user_id, '_kbs_tickets_orderby', $orderby );

} // kbs_save_user_tickets_orderby
add_action( 'personal_options_update', 'kbs_save_user_tickets_orderby' );
add_action( 'edit_user_profile_update', 'kbs_save_user_tickets_orderby' );

/**
 * Saves the tickets order field.
 *
 * @since	1.4
 * @param	int		$user_id	WP User ID
 */
function kbs_save_user_tickets_order( $user_id ) {

	if ( ! current_user_can( 'edit_user', $user_id ) )	{
		return;
	}

	$order = sanitize_text_field( $_POST['kbs_tickets_order'] );

	update_user_meta( $user_id, '_kbs_tickets_order', $order );

} // kbs_save_user_tickets_order
add_action( 'personal_options_update', 'kbs_save_user_tickets_order' );
add_action( 'edit_user_profile_update', 'kbs_save_user_tickets_order' );

/**
 * Saves the load replies field.
 *
 * @since	1.2
 * @param	int		$user_id	WP User ID
 */
function kbs_save_user_load_replies( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) )	{
		return;
	}

	$number = absint( $_POST['kbs_load_replies'] );

	update_user_meta( $user_id, '_kbs_load_replies', $number );
} // kbs_save_user_load_replies
add_action( 'personal_options_update', 'kbs_save_user_load_replies' );
add_action( 'edit_user_profile_update', 'kbs_save_user_load_replies' );

/**
 * Saves the ticket reply alerts option.
 *
 * @since	1.3.4
 * @param	int		$user_id	WP User ID
 */
function kbs_save_user_reply_alerts( $user_id ) {
	if ( ! kbs_is_agent( $user_id ) || ! current_user_can( 'edit_user', $user_id ) )	{
		return;
	}

	$alert = ! empty( $_POST['kbs_agent_reply_alerts'] ) ? absint( $_POST['kbs_agent_reply_alerts'] ) : 0;

	update_user_meta( $user_id, '_kbs_reply_alerts', $alert );
} // kbs_save_user_reply_alerts
add_action( 'personal_options_update', 'kbs_save_user_reply_alerts' );
add_action( 'edit_user_profile_update', 'kbs_save_user_reply_alerts' );

/**
 * Saves the departments fields.
 *
 * @since	1.2
 * @param	int		$user_id	WP User ID
 */
function kbs_save_user_departments( $user_id ) {
	if ( ! kbs_departments_enabled() || ! kbs_is_agent( $user_id ) || ! current_user_can( 'manage_ticket_settings' ) )	{
		return;
	}

	$departments = kbs_get_departments();
	$add_departments  = ! empty( $_POST['kbs_departments'] ) ? $_POST['kbs_departments'] : array();

    if ( $departments ) {
        foreach( $departments as $department )	{
            if ( in_array( $department->term_id, $add_departments ) )	{
                kbs_add_agent_to_department( $department->term_id, $user_id );
            } else	{
                kbs_remove_agent_from_department( $department->term_id, $user_id );
            }
        }
    }
} // kbs_save_user_departments
add_action( 'personal_options_update', 'kbs_save_user_departments' );
add_action( 'edit_user_profile_update', 'kbs_save_user_departments' );

/**
 * Retrieve users by role.
 *
 * @since	1.0
 * @param	str		$role	Name of the role to retrieve.
 * @param	bool	$ids	True to return array of IDs, false for array of user objects
 * @return	mixed
 */
function kbs_get_users_by_role( $role = array( 'support_agent', 'support_manager' ), $ids = false )	{
	global $wpdb;

	$args = array(
		'orderby'    => 'display_name',
		'role__in' => $role
	);

	if ( ! empty( $ids ) )	{
		$args['fields'] = 'ID';
	}

	$args = apply_filters( 'kbs_users_by_role', $args );

	$user_query = new WP_User_Query( $args );

	$users = $user_query->get_results();

	return $users;
} // kbs_get_users_by_role

/**
 * Validate a potential username
 *
 * @access      public
 * @since       1.0
 * @param       str		$username	The username to validate
 * @return      bool
 */
function kbs_validate_username( $username ) {
	$sanitized = sanitize_user( $username, false );
	$valid     = ( $sanitized == $username );

	return (bool) apply_filters( 'kbs_validate_username', $valid, $username );
} // kbs_validate_username

/**
 * Attach the newly created user_id to a customer, if one exists
 *
 * @since	1.0
 * @param 	int		$user_id	The User ID that was created
 * @return	void
 */
function kbs_connect_existing_customer_to_new_user( $user_id ) {
	$email = get_the_author_meta( 'user_email', $user_id );

	// Update the user ID on the customer
	$customer = new KBS_Customer( $email );

	if( $customer->id > 0 ) {
		$customer->update( array( 'user_id' => $user_id ) );
	}
} // kbs_connect_existing_customer_to_new_user
add_action( 'user_register', 'kbs_connect_existing_customer_to_new_user', 10, 1 );

/**
 * Process Profile Updates from the Editor Form
 *
 * @since	1.0
 * @param	arr		$data	Data sent from the profile editor
 * @return void
 */
function kbs_process_profile_editor_updates( $data ) {
	if ( ! isset( $_POST['kbs_action'] ) || 'edit_user_profile' != $_POST['kbs_action'] )	{
		return;
	}

	if ( empty( $_POST['kbs_profile_editor_submit'] ) && ! is_user_logged_in() ) {
		return false;
	}

	// Nonce security
	if ( ! wp_verify_nonce( $_POST['kbs_profile_editor_nonce'], 'kbs-profile-editor-nonce' ) ) {
		return false;
	}

	$user_id       = get_current_user_id();
	$old_user_data = get_userdata( $user_id );

	$display_name = isset( $_POST['kbs_display_name'] )    ? sanitize_text_field( $_POST['kbs_display_name'] )    : $old_user_data->display_name;
	$first_name   = isset( $_POST['kbs_first_name'] )      ? sanitize_text_field( $_POST['kbs_first_name'] )      : $old_user_data->first_name;
	$last_name    = isset( $_POST['kbs_last_name'] )       ? sanitize_text_field( $_POST['kbs_last_name'] )       : $old_user_data->last_name;
	$email        = isset( $_POST['kbs_email'] )           ? sanitize_email( $_POST['kbs_email'] )                : $old_user_data->user_email;
	$line1        = isset( $_POST['kbs_address_line1'] )   ? sanitize_text_field( $_POST['kbs_address_line1'] )   : '';
	$line2        = isset( $_POST['kbs_address_line2'] )   ? sanitize_text_field( $_POST['kbs_address_line2'] )   : '';
	$city         = isset( $_POST['kbs_address_city'] )    ? sanitize_text_field( $_POST['kbs_address_city'] )    : '';
	$state        = isset( $_POST['kbs_address_state'] )   ? sanitize_text_field( $_POST['kbs_address_state'] )   : '';
	$zip          = isset( $_POST['kbs_address_zip'] )     ? sanitize_text_field( $_POST['kbs_address_zip'] )     : '';
	$country      = isset( $_POST['kbs_address_country'] ) ? sanitize_text_field( $_POST['kbs_address_country'] ) : '';

	$error    = false;
	$userdata = array(
		'ID'           => $user_id,
		'first_name'   => $first_name,
		'last_name'    => $last_name,
		'display_name' => $display_name,
		'user_email'   => $email
	);

	$address = array(
		'line1'    => $line1,
		'line2'    => $line2,
		'city'     => $city,
		'state'    => $state,
		'zip'      => $zip,
		'country'  => $country
	);

	do_action( 'kbs_pre_update_user_profile', $user_id, $userdata );

	if ( ! empty( $_POST['kbs_new_user_pass1'] ) ) {
		if ( $_POST['kbs_new_user_pass1'] !== $_POST['kbs_new_user_pass2'] ) {
			$error = 'password_mismatch';
		} else {
			$userdata['user_pass'] = $_POST['kbs_new_user_pass1'];
		}
	}

	// Force email to lower case to ensure we're checking like for like
	if ( ! $error && strtolower( $email ) != strtolower( $old_user_data->user_email ) ) {

		if ( ! is_email( $email ) ) {
			$error = 'email_invalid';
		}

		if ( email_exists( $email ) ) {
			$error = 'email_unavailable';
		}

	}

	$url = remove_query_arg( 'kbs_notice', $_POST['kbs_redirect'] );

	if ( $error ) {
		$url = add_query_arg( 'kbs_notice', $error, $url );
		wp_safe_redirect( $url );
		die();
	}

	// Process updates
	$updated = wp_update_user( $userdata );

	$customer = new KBS_Customer( $user_id, true );

	if ( ! empty( $address ) )	{
		$meta     = update_user_meta( $user_id, '_kbs_user_address', $address );
		$customer->update_meta( 'address', $address );
	}

	$old_per_page     = kbs_get_customer_tickets_per_page( $user_id );
	$default_per_page = get_option( 'posts_per_page', 10 );
	$new_per_page     = ! empty( $_POST['kbs_tickets_per_page'] ) ? absint( $_POST['kbs_tickets_per_page'] ) : 0;
	$new_per_page     = ! empty( $new_per_page ) ? $new_per_page : $default_per_page;

	if ( $new_per_page != $old_per_page )	{
		update_user_meta( $user_id, '_kbs_tickets_per_page', $new_per_page );
	}

	$old_orderby      = get_user_meta( $user_id, '_kbs_tickets_orderby', true );
	$new_orderby      = ! empty( $_POST['kbs_tickets_orderby'] ) ? $_POST['kbs_tickets_orderby'] : false;

	if ( $new_orderby != $old_orderby )	{
		update_user_meta( $user_id, '_kbs_tickets_orderby', $new_orderby );
	}

	$old_order        = get_user_meta( $user_id, '_kbs_tickets_order', true );
	$new_order        = ! empty( $_POST['kbs_tickets_order'] ) ? $_POST['kbs_tickets_order'] : false;

	if ( $new_order != $old_order )	{
		update_user_meta( $user_id, '_kbs_tickets_order', $new_order );
	}

	$old_hide_closed = get_user_meta( $user_id, '_kbs_hide_closed', true );
    $new_hide_closed = ! empty( $_POST['kbs_hide_closed'] ) ? $_POST['kbs_hide_closed'] : false;

    if ( $new_hide_closed != $old_hide_closed  )    {
	   update_user_meta( $user_id, '_kbs_hide_closed', $new_hide_closed );
    }

	$old_load_replies = kbs_get_customer_replies_to_load( $user_id );
	$new_load_replies = empty( $_POST['kbs_number_replies'] ) ? 0 : absint( $_POST['kbs_number_replies'] );

	if ( $new_load_replies != $old_load_replies )	{
		update_user_meta( $user_id, '_kbs_load_replies', $new_load_replies );
	}

	if ( $customer->email === $email || ( is_array( $customer->emails ) && in_array( $email, $customer->emails ) ) ) {
		$customer->set_primary_email( $email );
	};

	if ( $customer->id > 0 ) {
		$update_args = array(
			'name'  => $first_name . ' ' . $last_name,
		);

		$customer->update( $update_args );
	}

	if ( $updated ) {
		do_action( 'kbs_user_profile_updated', $user_id, $userdata );
		wp_safe_redirect( add_query_arg( 'kbs_notice', 'profile_updated', $url ) );
		die();
	}
} // kbs_process_profile_editor_updates
add_action( 'init', 'kbs_process_profile_editor_updates' );

/**
 * Retrieve the orderby option for tickets.
 *
 * @since   1.4
 * @param   int     $user_id    The User ID of the current user
 * @return  string	Post field to order by
 */
function kbs_get_user_tickets_orderby_setting( $user_id = 0 )   {
    $default = 'date';

    if ( empty( $user_id ) )    {
        $user_id = get_current_user_id();
    }

    if ( ! empty( $user_id ) )    {
        $orderby = get_user_meta( $user_id, '_kbs_tickets_orderby', true );

        if ( '' == $orderby )   {
            $orderby = $default;
        }

    } else  {
        $orderby = $default;
    }

    $orderby = ! empty( $orderby ) ? $orderby : $default;

    $orderby = apply_filters( 'kbs_user_tickets_orderby', $orderby, $user_id );

    return $orderby;
} // kbs_get_user_tickets_orderby_setting

/**
 * Retrieve the users defined ticket order setting.
 *
 * @since	1.4
 * @param	int		$user_id	WP User ID
 * @return	string	ASC|DESC
 */
function kbs_get_user_tickets_order_setting( $user_id = 0 )	{
	if ( empty( $user_id ) )	{
		$user_id = get_current_user_id();
	}

	$order = 'DESC';

	if ( $user_id )	{
		$setting = get_user_meta( $user_id, '_kbs_tickets_order', true );

		if ( '' != $setting )	{
			$order = $setting;
		}
	}

	return $order;
} // kbs_get_user_tickets_order_setting

/**
 * Process the 'remove' email address action on the profile editor form.
 *
 * @since	1.0
 * @return	void
 */
function kbs_process_profile_editor_remove_email() {
	if ( ! isset( $_GET['kbs_action'] ) || 'profile-remove-email' != $_GET['kbs_action'] )	{
		return;
	}

	if ( ! is_user_logged_in() ) {
		return false;
	}

	// Nonce security
	if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'kbs-remove-customer-email' ) ) {
		return false;
	}

	if ( empty( $_GET['email'] ) || ! is_email( $_GET['email'] ) ) {
		return false;
	}

	$customer = new KBS_Customer( get_current_user_id(), true );
	$url      = remove_query_arg( 'kbs_notice', $_GET['redirect'] );

	if ( $customer->remove_email( $_GET['email'] ) ) {

		$url = add_query_arg( 'kbs_notice', 'profile_updated', $_GET['redirect'] );

		$user          = wp_get_current_user();
		$user_login    = ! empty( $user->user_login ) ? $user->user_login : 'KBSBot';
		$customer_note = __( sprintf( 'Email address %s removed by %s', $_GET['email'], $user_login ), 'kb-support' );
		$customer->add_note( $customer_note );

		$url = add_query_arg( 'kbs_notice', 'email_removed', $url );

	} else {
		$url = add_query_arg( 'kbs_notice', 'email_remove_failed', $url );
	}

	wp_safe_redirect( $url );
	exit;
} // kbs_process_profile_editor_remove_email
add_action( 'init', 'kbs_process_profile_editor_remove_email' );

/**
 * Generate a username for a new support customer
 *
 * @since   1.2.6
 * @param   array   $user_data  Array of user data
 * @return  string  Username
 */
function kbs_create_user_name( $user_data ) {
    $format    = kbs_get_option( 'reg_name_format', 'email' );
    $user_name = '';

    switch( $format )   {
        case 'email_prefix':
            $email_prefix = explode( '@', $user_data['user_email'] );
            $user_name    = strtolower( $email_prefix[0] );
            break;

        case 'email':
            $user_name = strtolower( $user_data['user_email'] );
            break;

        case 'full_name':
			$user_name = strtolower( $user_data['user_first'] . $user_data['user_last'] );
			break ;
    }

    /**
     * WP has a maximum 60 characters for usernames.
     * We max at 57 to allow for suffixes to be added to duplicates
     */
    $user_name = substr( $user_name, 0, 57 );

    return kbs_check_duplicate_user_name( $user_name );
} // kbs_create_user_name

/**
 * Check to see if a username is a duplicate.
 * If it is, append a postfix and return it.
 *
 * @since 1.2.6
 *
 * @param   string  $user_name  Username to check
 * @return  string  username    Validated username
 */
function kbs_check_duplicate_user_name( $user_name ) {
	$user_check = get_user_by( 'login', $user_name );

	if ( is_a( $user_check, 'WP_User' ) ) {
		$suffix = 1;
		do {
			$alt_username = sanitize_user( $user_name . $suffix );
			$user_check   = get_user_by( 'login', $alt_username );
			$suffix ++;
		} while ( is_a( $user_check, 'WP_User' ) );
		$user_name = $alt_username;
	}

	return $user_name ;
} // kbs_check_duplicate_user_name
