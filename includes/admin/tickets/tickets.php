<?php
/**
 * Manage kbs-ticket posts.
 *
 * @since		1.0
 * @package		KBS
 * @subpackage	Posts
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Remove block editor for kbs_ticket post type.
 *
 * @since	1.5
 * @param	bool	$block_editor	Whether or not to use block editor
 * @param	string	$post_type		Post type
 * @return	bool	True to use block editor, or false
 */
function kbs_ticket_remove_block_editor( $block_editor, $post_type )	{
	if ( 'kbs_ticket' == $post_type )	{
		$block_editor = false;
	}

	return $block_editor;
} // kbs_ticket_remove_block_editor
add_filter( 'use_block_editor_for_post_type', 'kbs_ticket_remove_block_editor', 10, 2 );

/**
 * Remove the post lock for tickets.
 *
 * By default this function does not run unless the
 * `kbs_disable_ticket_post_lock` is filtered.
 *
 * @since	1.1
 * @return	void
 */
function kbs_disable_ticket_post_lock()	{
	/**
	 * Allow developers to override the default setting
	 * which is NOT to disable the post lock
	 *
	 */
	if ( ! apply_filters( 'kbs_disable_ticket_post_lock', false ) )	{
		return;
	}

    if ( 'kbs_ticket' != get_current_screen()->post_type ) {
		return;
	}

	add_filter( 'show_post_locked_dialog', '__return_false' );
	add_filter( 'wp_check_post_lock_window', '__return_false' );
	wp_deregister_script('heartbeat');
} // kbs_disable_ticket_post_lock
add_action( 'load-edit.php', 'kbs_disable_ticket_post_lock' );
add_action( 'load-post.php', 'kbs_disable_ticket_post_lock' );

/**
 * Define the columns that should be displayed for the KBS ticket post lists screen
 *
 * @since	1.0
 * @param	arr		$columns	An array of column name ⇒ label. The label is shown as the column header.
 * @return	arr		$columns	Filtered array of column name => label to be shown as the column header.
 */
function kbs_set_kbs_ticket_post_columns( $columns ) {
	$category_labels = kbs_get_taxonomy_labels( 'ticket_category' );
	$tag_labels      = kbs_get_taxonomy_labels( 'ticket_tag' );

	$columns = array(
		'cb'              => '<input type="checkbox" />',
		'id'              => '#',
		'title'           => __( 'Title', 'kb-support' ),
		'status'          => __( 'Status', 'kb-support' ),
		'dates'           => __( 'Updated', 'kb-support' ),
		'customer'        => __( 'Customer', 'kb-support' ),
		'ticket_category' => $category_labels['menu_name'],
		'ticket_tag'      => $tag_labels['menu_name'],
		'agent'           => __( 'Agent', 'kb-support' )
	);

	if ( kbs_track_sla() )	{
		$columns['sla'] = __( 'SLA Status', 'kb-support' );
	}

	return apply_filters( 'kbs_ticket_post_columns', $columns );
} // kbs_set_kbs_ticket_post_columns
add_filter( 'manage_kbs_ticket_posts_columns' , 'kbs_set_kbs_ticket_post_columns' );

/**
 * Set the primary column for the kbs_ticket post type.
 *
 * @since	1.2.8
 * @param	string	$primary_id	The primary column ID
 * @param	string	$screen_id	Current screen ID
 * @return	string	The primary column ID
 */
function kbs_filter_ticket_primary_column( $primary_id, $screen_id )	{
	if ( 'edit-kbs_ticket' == $screen_id )	{
		$primary_id = 'id';
	}

	return $primary_id;
} // kbs_filter_ticket_primary_column
add_filter( 'list_table_primary_column', 'kbs_filter_ticket_primary_column', 10, 2 );

/**
 * Define which columns are sortable for ticket posts
 *
 * @since	1.0.9
 * @param	arr		$sortable_columns	Array of sortable columns
 * @return	arr		Array of sortable columns
 */
function kbs_ticket_post_sortable_columns( $sortable_columns )	{
	$sortable_columns['id']    = 'id';
    $sortable_columns['dates'] = 'date';

	return $sortable_columns;
} // kbs_ticket_post_sortable_columns
add_filter( 'manage_edit-kbs_ticket_sortable_columns', 'kbs_ticket_post_sortable_columns' );

/**
 * Define the data to be displayed within the KBS ticket post custom columns.
 *
 * @since	1.0
 * @param	str		$column_name	The name of the current column for which data should be displayed.
 * @param	int		$post_id		The ID of the current post for which data is being displayed.
 * @return	str
 */
function kbs_set_kbs_ticket_column_data( $column_name, $post_id ) {
	$kbs_ticket = new KBS_Ticket( $post_id );

	switch ( $column_name ) {
		case 'id':
			echo kb_tickets_post_column_id( $post_id, $kbs_ticket );
			break;

		case 'dates':
			echo kb_tickets_post_column_date( $post_id, $kbs_ticket );
			break;
		case 'status':
			echo kb_tickets_post_column_status( $post_id, $kbs_ticket );
			break;
		case 'customer':
			echo kb_tickets_post_column_customer( $post_id, $kbs_ticket );
			$company = kbs_get_company_name( $kbs_ticket->company_id );
			if ( ! empty( $company ) )	{
                $company_url = get_edit_post_link( $kbs_ticket->company_id );
				echo '<br />';
                printf(
                    '<span class="description"><a href="%s">%s</a></span>',
                    $company_url,
                    $company
                );
			}
			break;

		case 'ticket_category':
            $terms = wp_get_post_terms( $post_id, 'ticket_category' );
			if ( $terms )	{
				$output = array();

                foreach( $terms as $term )  {
                    $output[] = sprintf(
                        '<a href="%s">' . $term->name . '</a>',
                        add_query_arg( 'ticket_category', $term->slug )
                    );
                }

				echo implode( ', ', $output );

			} else	{
				echo '&mdash;';
			}
			break;

		case 'ticket_tag':
			$terms = wp_get_post_terms( $post_id, 'ticket_tag' );
			if ( $terms )	{
				$output = array();

                foreach( $terms as $term )  {
                    $output[] = sprintf(
                        '<a href="%s">' . $term->name . '</a>',
                        add_query_arg( 'ticket_tag', $term->slug )
                    );

                }

				echo implode( ', ', $output );

			} else	{
				echo '&mdash;';
			}
			break;

		case 'agent':
			echo kb_tickets_post_column_agent( $post_id, $kbs_ticket );
			break;

		case 'sla':
			echo kb_tickets_post_column_sla( $post_id, $kbs_ticket );
			break;

		default:
			echo __( 'No callback found for post column', 'kb-support' );
			break;
	}
} // kbs_set_kbs_ticket_column_data
add_action( 'manage_kbs_ticket_posts_custom_column' , 'kbs_set_kbs_ticket_column_data', 10, 2 );

/**
 * Output the ID row.
 *
 * @since	1.0
 * @param	int	$ticket_id	The ticket ID
 * @param	obj	$kbs_ticket	The KBS Ticket object
 * @return	str
 */
function kb_tickets_post_column_id( $ticket_id, $kbs_ticket )	{
	do_action( 'kbs_tickets_pre_column_id', $kbs_ticket );
	$output = sprintf( '<a href="%s" title="%s">%s</a>',
		get_edit_post_link( $ticket_id ),
		kbs_get_post_status_label( $kbs_ticket->post_status ),
		kbs_format_ticket_number( kbs_get_ticket_number( $ticket_id ) )
	);

    if ( $kbs_ticket->flagged ) {
        $title = sprintf( __( 'This %s has been flagged.', 'kb-support' ), kbs_get_ticket_label_singular( true ) );
        $output .= sprintf(
            ' <span title="%s" class="dashicons dashicons-flag kbs-notice-alert"></span>',
            $title
        );
    }

	if ( $kbs_ticket->last_replier ){
		$output .= '<p>';
		$output .= sprintf(
			__( '<svg aria-hidden="true" focusable="false" width="20" height="14" data-prefix="fas" data-icon="level-up-alt" class="svg-inline--fa fa-level-up-alt fa-w-10 kbs-label kbs-reply-status %s-replied" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512"><path fill="currentColor" d="M313.553 119.669L209.587 7.666c-9.485-10.214-25.676-10.229-35.174 0L70.438 119.669C56.232 134.969 67.062 160 88.025 160H152v272H68.024a11.996 11.996 0 0 0-8.485 3.515l-56 56C-4.021 499.074 1.333 512 12.024 512H208c13.255 0 24-10.745 24-24V160h63.966c20.878 0 31.851-24.969 17.587-40.331z" style="fill: %s;"></path></svg><span>%s replied</span>', 'kb-support' ),
			esc_attr( strtolower( $kbs_ticket->last_replier ) ),
			kbs_get_ticket_reply_status_colour( $kbs_ticket->last_replier ),
			$kbs_ticket->last_replier
		);
		$output .= '</p>';
	}

	do_action( 'kbs_tickets_post_column_id', $kbs_ticket );

	return apply_filters( 'kb_tickets_post_column_id', $output, $ticket_id );
} // kb_tickets_post_column_id

function kb_tickets_post_column_status($ticket_id,$kbs_ticket){

	$output = sprintf( '<span class="kbs-status" style="background-color: %s;">%s</span>',
		kbs_get_ticket_status_colour( $kbs_ticket->post_status  ),
		kbs_get_post_status_label( $kbs_ticket->post_status )
	);

	do_action( 'kbs_tickets_post_column_status', $kbs_ticket );

	return apply_filters( 'kb_tickets_post_column_status', $output, $ticket_id );
}

/**
 * Output the Date row.
 *
 * @since	1.0
 * @param	int	$ticket_id	The ticket ID
 * @param	obj	$kbs_ticket	The KBS Ticket object
 * @return	str
 */
function kb_tickets_post_column_date( $ticket_id, $kbs_ticket ){
	do_action( 'kbs_tickets_pre_column_date', $kbs_ticket );

	$dif = absint( time() - $kbs_ticket->last_reply_date );

	$time_passed = 0;

	if ( ( $dif / ( 60 ) ) < 60 ){
		$time_passed = absint( $dif / ( 60 ) ) . ( ( absint( $dif / ( 60 ) ) <= 1 ) ? esc_html__( ' minute', 'kb-support' ) : esc_html__( ' minutes ago', 'kb-support' ) );
	} else if ( ( $dif / ( 60 * 60 ) ) <= 24 ){
		$time_passed = absint( $dif / ( 60 * 60 ) ) . ( ( absint( $dif / ( 60 * 60 ) ) <= 1 ) ? esc_html__( ' hour', 'kb-support' ) : esc_html__( ' hours ago', 'kb-support' ) );
	} else {
		$time_passed = absint( $dif / ( 60 * 60 * 24 ) ) . ( ( absint( $dif / ( 60 * 60 * 24 ) ) <= 1 ) ? esc_html__( ' day', 'kb-support' ) : esc_html__( ' days ago', 'kb-support' ) );
	}

	$tooltip = '<div class="wpchill-tooltip"><span>[?]</span>' .
			   '<div class="wpchill-tooltip-content">' .
			   esc_html__( 'Receive date:  ', 'kb-support' ) .
			   date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $kbs_ticket->date ) ) .
			   '</div></div>';

	$output = $time_passed . $tooltip;

	do_action( 'kbs_tickets_post_column_date', $kbs_ticket );

	return apply_filters( 'kb_tickets_post_column_date', $output, $ticket_id );
} // kb_tickets_post_column_date

/**
 * Output the Customer row.
 *
 * @since	1.0
 * @param	int	$ticket_id	The ticket ID
 * @param	obj	$kbs_ticket	The KBS Ticket object
 * @return	str
 */
function kb_tickets_post_column_customer( $ticket_id, $kbs_ticket )	{
	do_action( 'kbs_tickets_pre_column_customer', $kbs_ticket );

	if ( ! empty( $kbs_ticket->customer_id ) && kbs_customer_exists( $kbs_ticket->customer_id ) )	{

		$customer = new KBS_Customer( $kbs_ticket->customer_id );

		$customer_page = add_query_arg( array(
			'post_type' => 'kbs_ticket',
			'page'      => 'kbs-customers',
			'view'      => 'userdata',
			'id'        => $kbs_ticket->customer_id
		), admin_url( 'edit.php' ) );

		$output = '<a href="' . $customer_page . '">' . $customer->name . '</a>';

	} else	{
		$output = __( 'No Customer Assigned', 'kb-support' );
	}

	do_action( 'kbs_tickets_post_column_customer', $kbs_ticket );

	return apply_filters( 'kb_tickets_post_column_customer', $output, $ticket_id );
} // kb_tickets_post_column_customer

/**
 * Output the Agent row.
 *
 * @since	1.0
 * @param	int	$ticket_id	The ticket ID
 * @param	obj	$kbs_ticket	The KBS Ticket object
 * @return	str
 */
function kb_tickets_post_column_agent( $ticket_id, $kbs_ticket )	{
	do_action( 'kbs_tickets_pre_column_agent', $kbs_ticket );

    $output = '';

	if ( ! empty( $kbs_ticket->agent_id ) && $agent = new KBS_Agent( (int) $kbs_ticket->agent_id ) )	{
		$output .= sprintf( '<a href="%s">%s</a>',
			get_edit_user_link( $kbs_ticket->agent_id ),
			$agent->name
		);
	} else	{
		$output .= __( 'No Agent Assigned', 'kb-support' );
	}

    if ( kbs_departments_enabled() )    {
        $department = kbs_get_department_for_ticket( $ticket_id );
        if ( ! empty( $department ) )  {
            $output .= '<br>';
            $output .= sprintf(
                '<span class="description"><a href="%s">%s</a></span>',
                add_query_arg( array(
                    'post_status'    => 'all',
                    'post_type'      => 'kbs_ticket',
                    'action'         => -1,
                    'm'              => 0,
                    'department'     => 'support',
                    'filter_action'  => 'Filter',
                    'paged'          => 1,
                    'action2'        => -1
                ), 'edit.php' ),
                $department->name
            );
        }
    }

	do_action( 'kbs_tickets_post_column_agent', $kbs_ticket );

	return apply_filters( 'kb_tickets_post_column_agent', $output, $ticket_id );
} // kb_tickets_post_column_agent

/**
 * Output the SLA Status row.
 *
 * @since	1.0
 * @param	int	$ticket_id	The ticket ID
 * @param	obj	$kbs_ticket	The KBS Ticket object
 * @return	str
 */
function kb_tickets_post_column_sla( $ticket_id, $kbs_ticket )	{
	do_action( 'kbs_tickets_pre_column_sla', $kbs_ticket );

	$output = kbs_display_sla_status_icons( $kbs_ticket, '<br />', false );

	do_action( 'kbs_tickets_post_column_sla', $kbs_ticket );

	$output = apply_filters( 'kb_tickets_post_column_sla', $output, $ticket_id );

	return implode( '<br />', $output );

} // kb_tickets_post_column_sla

/**
 * Order tickets.
 *
 * @since	1.0.9
 * @param	object	$query	The WP_Query object
 * @return	void
 */
function kbs_order_admin_tickets( $query )	{
	if ( ! is_admin() || 'kbs_ticket' != $query->get( 'post_type' ) )	{
		return;
	}

	$orderby_setting = kbs_get_user_tickets_orderby_setting();
	$order_setting   = kbs_get_user_tickets_order_setting();

	$orderby = '' != $query->get( 'orderby' ) ? $query->get( 'orderby' ) : $orderby_setting;
	$order   = '' != $query->get( 'order' )   ? $query->get( 'order' )   : $order_setting;

	switch( $orderby )	{
        case 'ID':
			$query->set( 'orderby',  'ID' );
			$query->set( 'order',  $order );
			break;
		case 'date':
			$query->set( 'orderby', 'meta_value modified' );
			$query->set( 'meta_query', array(
					'relation' => 'OR',
					array(
						'key'     => '_kbs_ticket_last_reply_date',
						'compare' => 'EXISTS'
					),
					array(
						'key'     => '_kbs_ticket_last_reply_date',
						'compare' => 'NOT EXISTS'
					),
				)
			);
			$query->set( 'order', $order );
			break;
		case 'title':
			$query->set( 'orderby',  'title' );
			$query->set( 'order',  $order );
			break;
	}
} // kbs_order_admin_tickets
add_action( 'pre_get_posts', 'kbs_order_admin_tickets' );

/**
 * Filter tickets query so agents only see their own tickets and new tickets.
 *
 * @since	1.0
 * @return	void
 */
function kbs_restrict_agent_ticket_view( $query )	{
	if ( ! is_admin() || 'kbs_ticket' != $query->get( 'post_type' ) )	{
		return;
	}

	$admin_agents = kbs_get_option( 'admin_agents' );

	if ( empty( $admin_agents ) && current_user_can( 'administrator' ) )	{
		$query->set( 'p', '99999999' );
		return;
	}

	// If user is admin and admins are agents, they see all.
	if ( current_user_can( 'manage_ticket_settings' ) )	{
		return;
	}

	if ( kbs_get_option( 'restrict_agent_view', false ) )	{
		$agent_id = get_current_user_id();

		$meta_query = array(
			'relation' => 'OR',
			array(
				'key'     => '_kbs_ticket_agent_id',
				'value'   => $agent_id,
				'type'    => 'NUMERIC'
			)
		);

		if ( kbs_departments_enabled() )	{
			$departments  = kbs_get_agent_departments( $agent_id );
			foreach( $departments as $department )	{
				$meta_query[] = array(
					'key'   => '_kbs_ticket_department',
					'value' => (int) $department,
					'type'  => 'NUMERIC'
				);
			}
		} else	{
			$meta_query[] = array(
				'key'   => '_kbs_ticket_agent_id',
				'value' => ''
			);
			$meta_query[] = array(
				'key'     => '_kbs_ticket_agent_id',
				'value'   => 'anything',
				'compare' => 'NOT EXISTS'
			);
		}

        if ( kbs_multiple_agents() )    {
            $meta_query[] = array(
                'key'     => '_kbs_ticket_agents',
                'value'   => sprintf( ':%d;', $agent_id ),
                'compare' => 'LIKE'
            );
        }

        $query->set( 'meta_query', $meta_query );
  }
} // kbs_restrict_agent_ticket_view
add_action( 'pre_get_posts', 'kbs_restrict_agent_ticket_view' );

/**
 * Search by ticket ID.
 *
 * Allows user to search for ticket by either Post ID or Ticket number.
 * Searches should be prefixed with '#'
 *
 * @since   1.3
 * @param   object  $query  The WP Query object
 * @return  object  The WP Query object
 */
function kbs_search_ticket_list_by_id( $query ) {
    global $pagenow, $typenow;

    if ( 'edit.php' != $pagenow && 'kbs_ticket' != $typenow )   {
        return;
    }

    if ( ! isset( $query->query_vars['s'] ) )   {
        return;
    }

    if ( '#' != substr( $query->query_vars['s'], 0, 1 ) )  {
        return;
    }

    $id = substr( $query->query_vars['s'], 1 );

    if ( ! $id )    {
        return;
    }

    if ( is_numeric( $id ) && get_post( $id ) )  {

        $query->query_vars['p'] = $id;

    } elseif ( kbs_use_sequential_ticket_numbers() )	{

        $query->query_vars['meta_key']   = '_kbs_ticket_number';
        $query->query_vars['meta_value'] = $id;

    }

    unset( $query->query_vars['s'] );
    add_filter( 'get_search_query', function() { return $_GET['s']; } );
} // kbs_search_ticket_list_by_id
add_action( 'parse_request', 'kbs_search_ticket_list_by_id' );

/**
 * Add Ticket Filters
 *
 * Adds taxonomy drop down filters for tickets.
 *
 * @since	1.0
 * @return	void
 */
function kbs_add_ticket_filters() {
	global $typenow;

	if ( 'kbs_ticket' == $typenow ) {
		$terms = get_terms( 'ticket_category' );

		if ( is_array( $terms ) && count( $terms ) > 0 )	{
			$category_labels = kbs_get_taxonomy_labels( 'ticket_category' );

			echo "<select name='ticket_category' id='ticket_category' class='postform'>";
				echo "<option value=''>" . sprintf( __( 'Show all %s', 'kb-support' ), strtolower( $category_labels['name'] ) ) . "</option>";

				foreach ( $terms as $term )	{
					$selected = isset( $_GET['ticket_category'] ) && $_GET['ticket_category'] == $term->slug ? ' selected="selected"' : '';
					echo '<option value="' . esc_attr( $term->slug ) . '"' . $selected . '>' . esc_html( $term->name ) .' (' . $term->count .')</option>';
				}

			echo "</select>";
		}

		$terms = get_terms( 'ticket_tag' );
		if ( is_array( $terms ) && count( $terms ) > 0 )	{
			$tag_labels = kbs_get_taxonomy_labels( 'ticket_tag' );

			echo "<select name='ticket_tag' id='ticket_tag' class='postform'>";
				echo "<option value=''>" . sprintf( __( 'Show all %s', 'kb-support' ), strtolower( $tag_labels['name'] ) ) . "</option>";

				foreach ( $terms as $term ) {
					$selected = isset( $_GET['ticket_tag'] ) && $_GET['ticket_tag'] == $term->slug ? ' selected="selected"' : '';
					echo '<option value="' . esc_attr( $term->slug ) . '"' . $selected . '>' . esc_html( $term->name ) .' (' . $term->count .')</option>';
				}

			echo "</select>";
		}

        if ( ! kbs_get_option( 'restrict_agent_view' ) || current_user_can( 'manage_ticket_settings' ) )	{
            $agents = kbs_get_agents();

            if ( is_array( $agents ) && count( $agents ) > 0 )	{
                echo "<select name='agent' id='agent' class='postform'>";
                    echo "<option value='0'>" . __( 'All agents', 'kb-support' ) . "</option>";

                    foreach ( $agents as $agent ) {
                        $selected = isset( $_GET['agent'] ) && $_GET['agent'] == $agent->ID ? ' selected="selected"' : '';
                        printf(
                            '<option value="%d"' . $selected . '>%s (%d)</option>',
                            esc_attr( $agent->ID ),
                            esc_html( $agent->display_name ),
                            kbs_agent_ticket_count( $agent->ID )
                        );
                    }

                echo "</select>";
            }
        }

        if ( kbs_departments_enabled() )    {
            $terms = get_terms( 'department' );
            if ( is_array( $terms ) && count( $terms ) > 0 )	{
                $tag_labels = kbs_get_taxonomy_labels( 'department' );

                echo "<select name='department' id='department' class='postform'>";
                    echo "<option value=''>" . sprintf( __( 'All %s', 'kb-support' ), strtolower( $tag_labels['name'] ) ) . "</option>";

                    foreach ( $terms as $term ) {
                        $selected = isset( $_GET['department'] ) && $_GET['department'] == $term->slug ? ' selected="selected"' : '';
                        echo '<option value="' . esc_attr( $term->slug ) . '"' . $selected . '>' . esc_html( $term->name ) .' (' . $term->count .')</option>';
                    }

                echo "</select>";
            }
        }

		// Awaiting reply
        $awaiting_customer = (int)kbs_count_tickets_by_reply_status( 'customer' );
        $awaiting_customer = $awaiting_customer > 0 ? ' (' . $awaiting_customer . ')' : '';
        $awaiting_agent    = (int)kbs_count_tickets_by_reply_status( 'agent' );
        $awaiting_agent    = $awaiting_agent > 0    ? ' (' . $awaiting_agent . ')'    : '';

		if ( ! empty( $awaiting_customer ) || ! empty( $awaiting_agent ) )	{
			echo '<select name="reply_status" id="reply_status" class="postform">';
				$selected = '';
				if ( isset( $_GET ) && isset( $_GET['reply_status'] ) && '' != $_GET['reply_status'] )	{
					$selected = $_GET['reply_status'];
				}
				printf(
					'<option value=""%s>%s</option>',
					'' == $selected ? ' selected="selected"' : '',
					__( 'All reply statuses', 'kb-support' )
				);

			if ( ! empty( $awaiting_agent ) )	{
				printf(
					'<option value="awaiting_support"%s>%s</option>',
					'awaiting_support' == $selected ? ' selected="selected"' : '',
					__( 'Awaiting Support Reply', 'kb-support' ) . $awaiting_agent
				);
			}

			if ( ! empty( $awaiting_customer ) )	{
				printf(
					'<option value="awaiting_customer"%s>%s</option>',
					'awaiting_customer' == $selected ? ' selected="selected"' : '',
					__( 'Awaiting Customer Reply', 'kb-support' ) . $awaiting_customer
				);
			}

			echo "</select>";
		}

		if ( isset( $_REQUEST['all_posts'] ) && '1' === $_REQUEST['all_posts'] )	{
			echo '<input type="hidden" name="all_posts" value="1" />';
		} elseif ( ! current_user_can( 'view_ticket_reports' ) )	{
			$author_id = get_current_user_id();
			echo '<input type="hidden" name="author" value="' . esc_attr( $author_id ) . '" />';
		}
	}

} // kbs_add_ticket_filters
add_action( 'restrict_manage_posts', 'kbs_add_ticket_filters', 100 );

/**
 * Display additional actions/filters.
 *
 * @since   1.5.3
 * @param   string  $which  The location of the bulk actions: 'top' or 'bottom'
 * @return  string
 */
function kbs_add_additional_ticket_filters_actions( $which )    {
    global $wpdb, $post;

    if ( 'top' !== $which || 'kbs_ticket' != get_post_type() ) {
        return;
    }

    $actions = array();

    if ( empty( $_GET['flagged'] ) )    {
        $flagged = $wpdb->get_results(
            "
            SELECT *
            FROM $wpdb->postmeta
            WHERE meta_key = '_kbs_ticket_flagged'
            LIMIT 1
            "
        );

        if ( $flagged ) {
            $actions[] = sprintf(
                '<a class="button button-secondary" href="%s">%s</a>',
                add_query_arg( 'flagged', 1 ),
                sprintf( __( 'Filter Flagged %s', 'kb-support' ), kbs_get_ticket_label_plural() )
            );
        }
    }

    if ( ! empty( $actions ) )  {
        ob_start(); ?>
        <br class="clear">
        <div class="alignleft kbs-ticket-list-actions">
            <?php echo implode( '&nbsp;&#124;&nbsp;', $actions ); ?>
        </div>

        <?php echo ob_get_clean();
    }
} // kbs_add_additional_ticket_filters_actions
add_action( 'manage_posts_extra_tablenav', 'kbs_add_additional_ticket_filters_actions', PHP_INT_MAX );

/**
 * Filter tickets by flag.
 *
 * @since	1.5.3
 * @return	void
 */
function kbs_filter_flagged_tickets( $query )	{
	if ( ! is_admin() || 'kbs_ticket' != $query->get( 'post_type' ) || ! isset( $_GET['flagged'] ) )	{
		return;
	}

    if ( empty( $_GET['flagged'] ) )    {
        return;
    }

	$query->set( 'meta_key', '_kbs_ticket_flagged' );
	$query->set( 'meta_value', 1 );
	$query->set( 'meta_type', 'NUMERIC' );
} // kbs_filter_flagged_tickets
add_action( 'pre_get_posts', 'kbs_filter_flagged_tickets' );

/**
 * Filter tickets by customer.
 *
 * @since	1.0
 * @return	void
 */
function kbs_filter_customer_tickets( $query )	{
	if ( ! is_admin() || 'kbs_ticket' != $query->get( 'post_type' ) || ! isset( $_GET['customer'] ) )	{
		return;
	}

	$query->set( 'meta_key', '_kbs_ticket_customer_id' );
	$query->set( 'meta_value', $_GET['customer'] );
	$query->set( 'meta_type', 'NUMERIC' );
} // kbs_filter_customer_tickets
add_action( 'pre_get_posts', 'kbs_filter_customer_tickets' );

/**
 * Filter tickets by company.
 *
 * @since	1.0
 * @return	void
 */
function kbs_filter_company_tickets( $query )	{
	if ( ! is_admin() || 'kbs_ticket' != $query->get( 'post_type' ) || ! isset( $_GET['company_id'] ) )	{
		return;
	}

	$query->set( 'meta_key', '_kbs_ticket_company_id' );
	$query->set( 'meta_value', $_GET['company_id'] );
	$query->set( 'meta_type', 'NUMERIC' );
} // kbs_filter_customer_tickets
add_action( 'pre_get_posts', 'kbs_filter_company_tickets' );

/**
 * Filter tickets by company.
 *
 * @since	1.0
 * @return	void
 */
function kbs_filter_agent_tickets( $query )	{
	if ( ! is_admin() || 'kbs_ticket' != $query->get( 'post_type' ) || ! isset( $_GET['agent'] ) )	{
		return;
	}

	$query->set( 'meta_key', '_kbs_ticket_agent_id' );
	$query->set( 'meta_value', $_GET['agent'] );
	$query->set( 'meta_type', 'NUMERIC' );
} // kbs_filter_agent_tickets
add_action( 'pre_get_posts', 'kbs_filter_agent_tickets' );

/**
 * Filter tickets by reply status.
 *
 * @since	1.4
 * @return	void
 */
function kbs_filter_tickets_by_reply_status( $query )	{
	if ( ! is_admin() || 'kbs_ticket' != $query->get( 'post_type' ) || empty( $_GET['reply_status'] ) )	{
		return;
	}

	$selected = $_GET['reply_status'];

	if ( 'awaiting_support' == $selected )	{

		$query->set( 'meta_key', '_kbs_ticket_last_reply_by' );
		$query->set( 'meta_value', 3 );
		$query->set( 'meta_type', 'NUMERIC' );

	} elseif ( 'awaiting_customer' == $selected )	{

		$current_meta = $query->get('meta_query');
		$current_meta = empty( $current_meta ) ? array() : $current_meta;
		$custom_meta  = array(
			'relation' => 'OR',
			array(
				'key'   => '_kbs_ticket_last_reply_by',
				'type'  => 'NUMERIC',
				'value' => 1
			),
			array(
				'key'   => '_kbs_ticket_last_reply_by',
				'type'  => 'NUMERIC',
				'value' => 2
			)
		);

		$meta_query = $current_meta[] = $custom_meta;
		$query->set( 'meta_query', array( $meta_query ) );
	}
} // kbs_filter_tickets_by_reply_status
add_action( 'pre_get_posts', 'kbs_filter_tickets_by_reply_status' );

/**
 * Hide inactive tickets from the 'all' tickets list.
 *
 * @since	1.0
 * @param	obj		$query	The WP_Query.
 * @return	void
 */
function kbs_remove_inactive_tickets( $query )	{
	if ( ! is_admin() || ! $query->is_main_query() || 'kbs_ticket' != $query->get( 'post_type' ) )	{
		return;
	}

	if ( ! kbs_get_option( 'hide_closed', false ) )	{
		return;
	}

	if ( isset( $_GET['post_status'] ) && 'all' != $_GET['post_status'] )	{
		return;
	}

	$active_statuses = kbs_get_ticket_status_keys( false );

	if ( ( $key = array_search( 'closed', $active_statuses ) ) !== false )	{
		unset( $active_statuses[ $key ] );
	}

	$active_tickets = kbs_get_tickets( array(
		'status' => $active_statuses,
		'fields' => 'ids',
		'output' => 'fields',
		'number' => -1
	) );

	if ( $active_tickets )	{
		$query->set( 'post__in', $active_tickets );
	}

} // kbs_remove_inactive_tickets
add_action( 'pre_get_posts', 'kbs_remove_inactive_tickets' );

/**
 * Customize the view filter counts
 *
 * @since	1.0
 * @param	arr		$views		Array of views
 * @return	arr		$views		Filtered Array of views
 */
function kbs_ticket_filter_views( $views )	{
	$active_only = kbs_get_option( 'hide_closed' );
    $span_string = '<span class="kbs-label kbs-label-status" style="background-color: %s;"></span>';
    $all_colour  = kbs_get_ticket_status_colour( 'all', true );

	if ( isset( $views['mine'] ) )	{
		unset( $views['mine'] );
	}

	if ( 'kbs_ticket' != get_post_type() )	{
		return $views;
	}

    if ( ! $active_only )   {
        foreach( $views as $status => $label )  {
            $colour = kbs_get_ticket_status_colour( $status );

            if ( 'all' == $status ) {
                $search       = __( 'All', 'kb-support' );
                $replace      = sprintf( __( 'All %s', 'kb-support' ), kbs_get_ticket_label_plural() );
                $views['all'] = str_replace( $search, $replace, $views['all'] );
                $colour       = $all_colour;
            }

            $span = sprintf(
                $span_string,
                $colour
            );

            $views[ $status ] = $span . $views[ $status ] . '</span>';
        }
    } else  {
        $args = array();
        if ( kbs_get_option( 'restrict_agent_view' ) && ! current_user_can( 'manage_ticket_settings' ) )	{
            $args['agent'] = get_current_user_id();
        } elseif( ! empty( $_GET['agent'] ) )   {
            $args['agent'] = absint( $_GET['agent'] );
        }

        $all_statuses      = array_keys( kbs_get_ticket_statuses() );
        $inactive_statuses = kbs_get_inactive_ticket_statuses();
        $num_posts         = kbs_count_tickets( $args );
        $count             = 0;

        if ( ! empty( $num_posts ) )	{
            foreach( $num_posts as $status => $status_count )	{
                $colour = kbs_get_ticket_status_colour( $status );
                $span   = sprintf(
                    $span_string,
                    $colour
                );

                if ( ! empty( $num_posts->$status ) && in_array( $status, $all_statuses ) )	{
                    $views[ $status ] = preg_replace( '/\(.+\)/U', '(' . number_format_i18n( $num_posts->$status ) . ')', $views[ $status ] );

                    $views[ $status ] = $span . '<span>' . $views[ $status ] . '</span>';
                }
                if ( ! in_array( $status, $inactive_statuses ) )	{
                    $count += $status_count;
                }
            }
        }

        $views['all'] = preg_replace( '/\(.+\)/U', '(' . number_format_i18n( $count ) . ')', $views['all'] );

        if ( $active_only )	{
            $search       = __( 'All', 'kb-support' );
            $replace      = sprintf( __( 'Active %s', 'kb-support' ), kbs_get_ticket_label_plural() );
            $views['all'] = str_replace( $search, $replace, $views['all'] );
            $views['all'] = sprintf(
                $span_string . '<span>' . $views['all'] . '</span>',
                $all_colour
            );
        }

        foreach( $views as $status => $link )	{
            if ( 'all' != $status && 'trash' != $status && ! in_array( $status, $all_statuses ) )	{
                unset( $views[ $status ] );
            }
        }

        // Return the All view to the front
        $views = array( 'all' => $views['all'] ) + $views;
    }

	if ( isset( $views['closed'] ) )	{
		$closed = $views['closed'];
		unset( $views['closed'] );
		$views = $views + array( 'closed' => $closed );
	}

    // Force trash view to end
    if ( isset( $views['trash'] ) )	{
        $trashed = $views['trash'];
        unset( $views['trash'] );
        $views['trash'] = $trashed;
    }

	return apply_filters( 'kbs_ticket_views', $views );

} // kbs_ticket_filter_views
add_filter( 'views_edit-kbs_ticket' , 'kbs_ticket_filter_views' );

/**
 * Remove action items from the bulk item menu and post row action list.
 *
 * @since	1.0
 * @param	arr		$actions	The action items array
 * @return	arr		Filtered action items array
 */
function kbs_tickets_remove_ticket_post_actions( $actions )	{
	if ( 'kbs_ticket' == get_post_type() )	{
		global $post;

		$remove_actions = array( 'edit', 'trash', 'inline hide-if-no-js' );
		$singular       = kbs_get_ticket_label_singular();

		foreach( $remove_actions as $remove_action )	{

			if ( doing_filter( 'bulk_actions-edit-kbs_ticket' ) && current_user_can( 'manage_ticket_settings' ) && 'trash' == $remove_action )	{
				continue;
			}

			if ( isset( $actions[ $remove_action ] ) )	{
				unset( $actions[ $remove_action ] );
			}

		}

	}

	return $actions;
} // kbs_tickets_remove_ticket_post_actions
add_filter( 'bulk_actions-edit-kbs_ticket', 'kbs_tickets_remove_ticket_post_actions' );
add_filter( 'post_row_actions', 'kbs_tickets_remove_ticket_post_actions' );

/**
 * Save the KBS Ticket custom posts
 *
 * @since	1.0
 * @param	int		$post_id		The ID of the post being saved.
 * @param	obj		$post			The WP_Post object of the post being saved.
 * @param	bool	$update			Whether an existing post if being updated or not.
 *
 * @return	void
 */
function kbs_ticket_post_save( $post_id, $post, $update )	{

	if ( ! isset( $_POST['kbs_ticket_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['kbs_ticket_meta_box_nonce'], 'kbs_ticket_meta_save' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )	{
		return;
	}

	if ( isset( $post->post_type ) && 'revision' == $post->post_type ) {
		return;
	}

    remove_action( 'save_post_kbs_ticket', 'kbs_ticket_post_save', 10, 3 );

	if ( is_admin() )	{
		if ( isset( $_POST['_kbs_ticket_logged_by'] ) )	{
			add_post_meta( $post_id, '_kbs_ticket_logged_by', absint( $_POST['_kbs_ticket_logged_by'] ), true );
		}
	}
	// The default fields that get saved
	$fields = kbs_ticket_metabox_fields();

	$ticket = new KBS_Ticket( $post_id );

	foreach ( $fields as $key => $field )	{

		$posted_value = '';

		if ( ! empty( $_POST[ $field ] ) ) {

			if ( is_string( $_POST[ $field ] ) )	{
				$posted_value = sanitize_text_field( $_POST[ $field ] );
			} elseif ( is_int( $_POST[ $field ] ) )	{
				$posted_value = absint( $_POST[ $field ] );
			} elseif( is_array( $_POST[ $field ] ) )	{
				$posted_value = array_map( 'absint', $_POST[ $field ] );
			}
		}

		$new_value = apply_filters( 'kbs_ticket_metabox_save_' . $field, $posted_value );

		$ticket->__set( $key, $new_value );

	}

	if ( empty( $ticket->key ) )	{
		$auth_key  = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
		$key = strtolower( md5( $ticket->email . date( 'Y-m-d H:i:s' ) . $auth_key . uniqid( 'kbs', true ) ) );
		$ticket->__set( 'key', $key );
	}

    $ticket_number = get_post_meta( $post_id, '_kbs_ticket_number', true );
    if ( ! $ticket_number ) {
        $number = kbs_get_next_ticket_number();
        if ( $number ) {
            $number = kbs_format_ticket_number( $number );
            $ticket->__set( 'number', $number );
            update_option( 'kbs_last_ticket_number', $number );
        }
    }

	if ( ! empty( $_POST['ticket_status'] ) && $_POST['ticket_status'] != $post->post_status )	{
        if ( 'closed' == $_POST['ticket_status'] && ! isset( $_POST['kbs_closure_email'] ) )  {
            add_filter( 'kbs_ticket_close_disable_email', '__return_true' );
        }

		$ticket->__set( 'status', $_POST['ticket_status'] );
	}

    if ( isset( $_POST['kbs_ticket_source'] ) )  {
        $source = absint( $_POST['kbs_ticket_source'] );

        $ticket->__set( 'source', $source );
    }

	do_action( 'kbs_pre_save_ticket', $ticket, $post_id );

	$ticket->save();

	add_post_meta( $post_id, '_kbs_ticket_version_created', KBS_VERSION, true );

    if ( isset( $_POST['_kbs_pending_ticket_created_email'] ) )   {
        add_post_meta( $post_id, '_kbs_pending_ticket_created_email', 1 );
    }

	do_action( 'kbs_save_ticket', $post_id, $post );

} // kbs_ticket_post_save
add_action( 'save_post_kbs_ticket', 'kbs_ticket_post_save', 10, 3 );
