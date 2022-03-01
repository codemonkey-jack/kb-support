<?php
/**
 * Post Type Functions
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
 * Registers and sets up the Tickets and Articles custom post types
 *
 * @since	1.0
 * @return	void
 */
function kbs_setup_post_types() {

	$ticket_labels =  apply_filters( 'kbs_ticket_labels', array(
		'name'                  => _x( '%2$s', 'kbs_ticket post type name', 'kb-support' ),
		'singular_name'         => _x( '%1$s', 'singular kbs_ticket post type name', 'kb-support' ),
		'add_new'               => __( 'Open %1$s', 'kb-support' ),
		'add_new_item'          => __( 'Add New %1$s', 'kb-support' ),
		'edit_item'             => __( 'Edit %1$s', 'kb-support' ),
		'new_item'              => __( 'New %1$s', 'kb-support' ),
		'all_items'             => __( '%2$s', 'kb-support' ),
		'view_item'             => __( 'View %1$s', 'kb-support' ),
		'search_items'          => __( 'Search %2$s', 'kb-support' ),
		'not_found'             => __( 'No %2$s found', 'kb-support' ),
		'not_found_in_trash'    => __( 'No %2$s found in Trash', 'kb-support' ),
		'parent_item_colon'     => '',
		'menu_name'             => _x( '%2$s', 'ticket post type menu name', 'kb-support' ),
		'featured_image'        => __( '%1$s Image', 'kb-support' ),
		'set_featured_image'    => __( 'Set %1$s Image', 'kb-support' ),
		'remove_featured_image' => __( 'Remove %1$s Image', 'kb-support' ),
		'use_featured_image'    => __( 'Use as %1$s Image', 'kb-support' ),
		'filter_items_list'     => __( 'Filter %2$s list', 'kb-support' ),
		'items_list_navigation' => __( '%2$s list navigation', 'kb-support' ),
		'items_list'            => __( '%2$s list', 'kb-support' )
	) );

	foreach ( $ticket_labels as $key => $value ) {
		$ticket_labels[ $key ] = sprintf( $value, kbs_get_ticket_label_singular(), kbs_get_ticket_label_plural() );
	}

	$ticket_args = array(
		'labels'                => $ticket_labels,
		'public'                => false,
		'publicly_queryable'    => false,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_icon'             => 'dashicons-sos',
		'query_var'             => true,
		'rewrite'               => false,
		'capability_type'       => 'ticket',
		'map_meta_cap'          => true,
		'has_archive'           => false,
		'hierarchical'          => false,
		'supports'              => apply_filters( 'kbs_ticket_supports', array( 'title', 'editor' ) ),
		'show_in_rest'          => true,
		'rest_base'             => 'tickets',
		'rest_controller_class' => 'KBS_Tickets_API'
	);

	register_post_type( 'kbs_ticket', apply_filters( 'kbs_ticket_post_type_args', $ticket_args ) );

	/** kbs_ticket_reply Post Type */
	$ticket_reply_labels =  apply_filters( 'kbs_ticket_reply_labels', array(
		'name'                  => _x( '%1$s Reply', 'kbs_ticket post type name', 'kb-support' ),
		'singular_name'         => _x( 'Reply', 'singular kbs_ticket post type name', 'kb-support' ),
		'add_new'               => __( 'Add Reply', 'kb-support' ),
		'add_new_item'          => __( 'Add New Reply', 'kb-support' ),
		'edit_item'             => __( 'Edit Reply', 'kb-support' ),
		'new_item'              => __( 'New Reply', 'kb-support' ),
		'all_items'             => __( 'Replies', 'kb-support' ),
		'view_item'             => __( 'View Reply', 'kb-support' ),
		'search_items'          => __( 'Search Replies', 'kb-support' ),
		'not_found'             => __( 'No Replies found', 'kb-support' ),
		'not_found_in_trash'    => __( 'No Replies found in Trash', 'kb-support' )
	) );

	foreach ( $ticket_reply_labels as $key => $value ) {
		$ticket_reply_labels[ $key ] = sprintf( $value, kbs_get_ticket_label_singular(), kbs_get_ticket_label_plural() );
	}

	$ticket_reply_args = array(
		'labels'                => $ticket_reply_labels,
		'public'                => false,
		'show_ui'               => true,
		'show_in_menu'          => false,
		'rewrite'               => false,
		'capability_type'       => 'ticket',
		'map_meta_cap'          => true,
		'has_archive'           => false,
		'hierarchical'          => false,
		'supports'              => apply_filters( 'kbs_ticket_reply_supports', array() ),
		'can_export'            => true,
        'show_in_rest'          => true,
		'rest_base'             => 'replies',
		'rest_controller_class' => 'KBS_Replies_API'
	);

	register_post_type( 'kbs_ticket_reply', apply_filters( 'kbs_ticket_reply_post_type_args', $ticket_reply_args ) );
	
	/** KB Form Type */
	$form_labels = array(
		'name'               => _x( 'Forms', 'kbs_form type general name', 'kb-support' ),
		'singular_name'      => _x( 'Form', 'kbs_form type singular name', 'kb-support' ),
		'add_new'            => __( 'New Form', 'kb-support' ),
		'add_new_item'       => __( 'New Form', 'kb-support' ),
		'edit_item'          => __( 'Edit Form', 'kb-support' ),
		'new_item'           => __( 'New Form', 'kb-support' ),
		'all_items'          => __( 'Submission Forms', 'kb-support' ),
		'view_item'          => __( 'View Form', 'kb-support' ),
		'search_items'       => __( 'Search Forms', 'kb-support' ),
		'not_found'          => __( 'No Forms found', 'kb-support' ),
		'not_found_in_trash' => __( 'No Forms found in Trash', 'kb-support' ),
		'parent_item_colon'  => '',
		'menu_name'          => __( 'Submission Forms', 'kb-support' )
	);

	$form_args = array(
		'labels'                => $form_labels,
		'public'                => false,
		'show_ui'               => true,
		'show_in_menu'          => 'edit.php?post_type=kbs_ticket',
		'menu_icon'             => 'dashicons-book-alt',
		'rewrite'               => false,
		'capability_type'       => 'submission_form',
		'map_meta_cap'          => true,
		'has_archive'           => false,
		'hierarchical'          => false,
		'supports'              => apply_filters( 'kbs_form_supports', array( 'title', 'custom-fields' ) ),
		'can_export'            => true,
        'show_in_rest'          => true,
		'rest_base'             => 'forms',
		'rest_controller_class' => 'KBS_Forms_API'
	);
	
	register_post_type( 'kbs_form', $form_args );
	
	/** KB Form Field Type */
	$field_labels = array(
		'name'               => _x( 'Fields', 'kbs_form type general name', 'kb-support' ),
		'singular_name'      => _x( 'Field', 'kbs_form type singular name', 'kb-support' ),
		'add_new'            => __( 'New Field', 'kb-support' ),
		'add_new_item'       => __( 'New Field', 'kb-support' ),
		'edit_item'          => __( 'Edit Field', 'kb-support' ),
		'new_item'           => __( 'New Field', 'kb-support' ),
		'all_items'          => __( 'Fields', 'kb-support' ),
		'view_item'          => __( 'View Field', 'kb-support' ),
		'search_items'       => __( 'Search Fields', 'kb-support' ),
		'not_found'          => __( 'No Fields found', 'kb-support' ),
		'not_found_in_trash' => __( 'No Fields found in Trash', 'kb-support' ),
		'parent_item_colon'  => '',
		'menu_name'          => __( 'Fields', 'kb-support' )
	);

	$field_args = array(
		'labels'                => $field_labels,
		'public'                => false,
		'rewrite'               => false,
		'capability_type'       => 'submission_form',
		'has_archive'           => false,
		'hierarchical'          => false,
		'supports'              => array( 'custom-fields' ),
		'can_export'            => true,
        'show_in_rest'          => true,
		'rest_base'             => 'fields',
		'rest_controller_class' => 'KBS_Form_Fields_API'
	);

	register_post_type( 'kbs_form_field', $field_args );

	/** KB Company Type */
	$company_labels = array(
		'name'                  => _x( 'Companies', 'kbs_company type general name', 'kb-support' ),
		'singular_name'         => _x( 'Company', 'kbs_company type singular name', 'kb-support' ),
		'add_new'               => __( 'New Company', 'kb-support' ),
		'add_new_item'          => __( 'New Company', 'kb-support' ),
		'edit_item'             => __( 'Edit Company', 'kb-support' ),
		'new_item'              => __( 'New Company', 'kb-support' ),
		'all_items'             => __( 'Companies', 'kb-support' ),
		'view_item'             => __( 'View Company', 'kb-support' ),
		'search_items'          => __( 'Search Forms', 'kb-support' ),
		'not_found'             => __( 'No Companies found', 'kb-support' ),
		'not_found_in_trash'    => __( 'No Companies found in Trash', 'kb-support' ),
		'parent_item_colon'     => '',
		'featured_image'        => __( 'Company Logo', 'kb-support' ),
		'set_featured_image'    => __( 'Set company logo', 'kb-support' ),
		'remove_featured_image' => __( 'Remove company logo', 'kb-support' ),
		'use_featured_image'    => __( 'Use as company logo', 'kb-support' )
	);

	$company_args = array(
		'labels'                => $company_labels,
		'public'                => false,
		'show_ui'               => true,
		'rewrite'               => false,
		'capability_type'       => 'customer',
		'show_in_menu'          => false,
		'map_meta_cap'          => true,
		'has_archive'           => false,
		'hierarchical'          => false,
		'supports'              => apply_filters( 'kbs_company_supports', array( 'title', 'thumbnail', 'custom-fields' ) ),
		'can_export'            => true,
		'show_in_rest'          => true,
		'rest_base'             => 'companies',
		'rest_controller_class' => 'KBS_Companies_API'
	);

	register_post_type( 'kbs_company', apply_filters( 'kbs_ticket_company_post_type_args', $company_args ) );

} // kbs_setup_post_types
add_action( 'init', 'kbs_setup_post_types', 1 );

/**
 * Get Default Ticket Labels
 *
 * @since	1.0
 * @return	arr		$defaults	Default labels
 */
function kbs_get_default_ticket_labels() {
	$defaults = array(
	   'singular' => __( 'Ticket', 'kb-support' ),
	   'plural'   => __( 'Tickets','kb-support' )
	);
	return apply_filters( 'kbs_default_tickets_name', $defaults );
} // kbs_get_default_ticket_labels

/**
 * Get Singular Ticket Label
 *
 * @since	1.0
 *
 * @param	bool	$lowercase
 * @return	str		$defaults['singular']	Singular Ticket label
 */
function kbs_get_ticket_label_singular( $lowercase = false ) {
	$defaults = kbs_get_default_ticket_labels();
	return ( $lowercase ) ? strtolower( $defaults['singular'] ) : $defaults['singular'];
} // kbs_get_ticket_label_singular

/**
 * Get Plural Ticket Label
 *
 * @since	1.0
 *
 * @param	bool	$lowercase
 * @return	str		$defaults['plural']		Plural Ticket label
 */
function kbs_get_ticket_label_plural( $lowercase = false ) {
	$defaults = kbs_get_default_ticket_labels();
	return ( $lowercase ) ? strtolower( $defaults['plural'] ) : $defaults['plural'];
} // kbs_get_ticket_label_plural

/**
 * Get Default Article Labels
 *
 * @since	1.0
 * @return	arr		$defaults	Default labels
 */
function kbs_get_default_article_labels() {
	$defaults = array(
	   'singular' => __( 'KB Article', 'kb-support' ),
	   'plural'   => __( 'KB Articles','kb-support' )
	);
	return apply_filters( 'kbs_default_articles_name', $defaults );
} // kbs_get_default_article_labels

/**
 * Get Singular Article Label
 *
 * @since	1.0
 *
 * @param	bool	$lowercase
 * @return	str		$defaults['singular']	Singular Ticket label
 */
function kbs_get_article_label_singular( $lowercase = false ) {
	$defaults = kbs_get_default_article_labels();
	return ($lowercase) ? strtolower( $defaults['singular'] ) : $defaults['singular'];
} // kbs_get_article_label_singular

/**
 * Get Plural Article Label
 *
 * @since	1.0
 *
 * @param	bool	$lowercase
 * @return	str		$defaults['plural']		Plural Ticket label
 */
function kbs_get_article_label_plural( $lowercase = false ) {
	$defaults = kbs_get_default_article_labels();
	return ( $lowercase ) ? strtolower( $defaults['plural'] ) : $defaults['plural'];
} // kbs_get_article_label_plural

/**
 * Change default "Enter title here" input
 *
 * @since	1.0
 * @param	str		$title	Default title placeholder text
 * @return	str		$title	New placeholder text
 */
function kbs_change_default_title( $title ) {

	 // If a frontend plugin uses this filter (check extensions before changing this function)
	 if ( ! is_admin() ) {
		$label = kbs_get_ticket_label_singular();
		$title = sprintf( __( 'Enter %s title here', 'kb-support' ), $label );
		return $title;
	 }

	 $screen = get_current_screen();

	 if ( 'kbs_ticket' == $screen->post_type ) {
		$label = kbs_get_ticket_label_singular();
		$title = sprintf( __( 'Enter %s title here', 'kb-support' ), $label );
	 } elseif ( 'kbs_article' == $screen->post_type ) {
		$label = kbs_get_article_label_singular();
		$title = sprintf( __( 'Enter %s title here', 'kb-support' ), $label );
	 } elseif ( 'kbs_form' == $screen->post_type )	{
		$title = __( 'Enter form name here', 'kb-support' ); 
	 } elseif ( 'kbs_company' == $screen->post_type )	{
		$title = __( 'Enter company name here', 'kb-support' ); 
	 }

	 return $title;

} // kbs_change_default_title
add_filter( 'enter_title_here', 'kbs_change_default_title' );

/**
 * Registers Custom Post Statuses which are used by the Tickets.
 *
 *
 * @since	1.0
 * @return	void
 */
function kbs_register_post_type_statuses() {

	// Ticket Statuses
	register_post_status( 'new', apply_filters( 'kbs_register_post_status_new', array(
		'label'                     => sprintf( _x( 'New', 'New %s', 'kb-support' ), kbs_get_ticket_label_plural() ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'New <span class="count">(%s)</span>', 'New <span class="count">(%s)</span>', 'kb-support' ),
		'kb-support'                => true,
		'kbs_select_allowed'        => false
	) ) );
	register_post_status( 'open', apply_filters( 'kbs_register_post_status_open', array(
		'label'                     => sprintf( _x( 'Open', 'Open %s', 'kb-support' ), kbs_get_ticket_label_plural() ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Open <span class="count">(%s)</span>', 'Open <span class="count">(%s)</span>', 'kb-support' ),
		'kb-support'                => true,
		'kbs_select_allowed'        => true
	) ) );
	register_post_status( 'hold', apply_filters( 'kbs_register_post_status_hold', array(
		'label'                     =>  sprintf( _x( 'On Hold', '%s on Hold', 'kb-support' ), kbs_get_ticket_label_plural() ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'On Hold <span class="count">(%s)</span>', 'On Hold <span class="count">(%s)</span>', 'kb-support' ),
		'kb-support'                => true,
		'kbs_select_allowed'        => true
	) ) );
	register_post_status( 'closed', apply_filters( 'kbs_register_post_status_closed', array(
		'label'                     => sprintf( _x( 'Closed', 'Closed %s', 'kb-support' ), kbs_get_ticket_label_plural() ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Closed <span class="count">(%s)</span>', 'Closed <span class="count">(%s)</span>', 'kb-support' ),
		'kb-support'                => true,
		'kbs_select_allowed'        => true
	) ) );

} // kbs_register_post_type_statuses
add_action( 'init', 'kbs_register_post_type_statuses', 2 );

/**
 * Retrieve all KB Support Custom Ticket post statuses.
 *
 * @since	1.0
 * @uses	get_post_stati()
 * @param	str		$output			The type of output to return, either 'names' or 'objects'. Default 'names'.
 * @param	bool	$allowed_only	If true, only statuses with the kbs_select_allowed true value are returned. False for all.
 * @return	arr|obj		
 */
function kbs_get_post_statuses( $output = 'names', $allowed_only = false )	{
	$args['kb-support'] = true;
	
	if ( ! empty( $allowed_only ) )	{
		$args['kbs_select_allowed'] = true;
	}
	
	$kbs_post_statuses = get_post_stati( $args, $output );
	
	return $kbs_post_statuses;
} // kbs_get_post_statuses

/**
 * Retrieve the label for the given post status.
 *
 * @since	1.2.3
 * @param	string	$post_status	The post status to retrieve the label for
 * @return	string	The post status label
 */
function kbs_get_post_status_label( $post_status )	{
	$status_object = get_post_status_object( $post_status );
	$label         = '';

	if ( ! empty( $status_object ) )	{
		$label = $status_object->label;
	}

	return $label;
} // kbs_get_post_status_label

/**
 * Updated Messages
 *
 * Returns an array of with all updated messages.
 *
 * @since	1.0
 * @param	arr		$messages	Post updated message
 * @return	arr		$messages	New post updated messages
 */
function kbs_updated_messages( $messages ) {

	global $post, $post_ID;

	$url1 = '<a href="' . get_permalink( $post_ID ) . '">';
	$url2 = kbs_get_ticket_label_singular();
	$url3 = kbs_get_article_label_singular();
	$url4 = '</a>';

	$messages['kbs_ticket'] = array(
		1 => sprintf( __( '%1$s updated.', 'kb-support'   ), $url2 ),
		4 => sprintf( __( '%1$s updated.', 'kb-support'   ), $url2 ),
		6 => sprintf( __( '%1$s opened.', 'kb-support'    ), $url2 ),
		7 => sprintf( __( '%1$s saved.', 'kb-support'     ), $url2 ),
		8 => sprintf( __( '%1$s submitted.', 'kb-support' ), $url2 )
	);

	if ( KBS()->KB->default_kb )	{
		$messages['kbs_article'] = array(
			1  => sprintf( __( '%2$s updated. %1$sView %2$s%3$s.', 'kb-support'   ), $url1, $url3, $url4 ),
			4  => sprintf( __( '%2$s updated. %1$sView %2$s%3$s.', 'kb-support'   ), $url1, $url3, $url4 ),
			6  => sprintf( __( '%2$s published. %1$sView %2$s%3$s.', 'kb-support' ), $url1, $url3, $url4 ),
			7  => sprintf( __( '%2$s saved. %1$sView %2$s%3$s.', 'kb-support'     ), $url1, $url3, $url4 ),
			8  => sprintf( __( '%2$s submitted. %1$sView %2$s%3$s.', 'kb-support' ), $url1, $url3, $url4 ),
			10 => sprintf( __( '%1$s draft updated. <a target="_blank" href="%2$s">Preview %1$s</a>', 'kb-support' ), $url3, esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) )
		);
	}
	
	$messages['kbs_form'] = array(
		1  => __( 'Form updated.', 'kb-support'   ),
		4  => __( 'Form updated.', 'kb-support'   ),
		6  => __( 'Form published.', 'kb-support' ),
		7  => __( 'Form saved.', 'kb-support'     ),
		8  => __( 'Form submitted.', 'kb-support' ),
		10 => __( 'Form draft updated.', 'kb-support' )
	);

	$messages['kbs_company'] = array(
		1  => __( 'Company updated.', 'kb-support'   ),
		4  => __( 'Company updated.', 'kb-support'   ),
		6  => __( 'Company published.', 'kb-support' ),
		7  => __( 'Company saved.', 'kb-support'     ),
		8  => __( 'Company submitted.', 'kb-support' ),
		10 => __( 'Company draft updated.', 'kb-support' )
	);

	return $messages;

} // kbs_updated_messages
add_filter( 'post_updated_messages', 'kbs_updated_messages' );

/**
 * Updated bulk messages
 *
 * @since	1.0
 * @param	arr		$bulk_messages	Post updated messages
 * @param	arr		$bulk_counts	Post counts
 * @return	arr		$bulk_messages	New post updated messages
 */
function kbs_bulk_updated_messages( $bulk_messages, $bulk_counts ) {

	$ticket_singular  = kbs_get_ticket_label_singular();
	$ticket_plural    = kbs_get_ticket_label_plural();
	$article_singular = kbs_get_article_label_singular();
	$article_plural   = kbs_get_article_label_plural();

	$bulk_messages['kbs_ticket'] = array(
		'updated'   => sprintf( _n( '%1$s %2$s updated.', '%1$s %3$s updated.', $bulk_counts['updated'], 'kb-support' ), $bulk_counts['updated'], $ticket_singular, $ticket_plural ),
		'locked'    => sprintf( _n( '%1$s %2$s not updated, somebody is editing it.', '%1$s %3$s not updated, somebody is editing them.', $bulk_counts['locked'], 'kb-support' ), $bulk_counts['locked'], $ticket_singular, $ticket_plural ),
		'deleted'   => sprintf( _n( '%1$s %2$s permanently deleted.', '%1$s %3$s permanently deleted.', $bulk_counts['deleted'], 'kb-support' ), $bulk_counts['deleted'], $ticket_singular, $ticket_plural ),
		'trashed'   => sprintf( _n( '%1$s %2$s moved to the Trash.', '%1$s %3$s moved to the Trash.', $bulk_counts['trashed'], 'kb-support' ), $bulk_counts['trashed'], $ticket_singular, $ticket_plural ),
		'untrashed' => sprintf( _n( '%1$s %2$s restored from the Trash.', '%1$s %3$s restored from the Trash.', $bulk_counts['untrashed'], 'kb-support' ), $bulk_counts['untrashed'], $ticket_singular, $ticket_plural )
	);
	
	$bulk_messages['kbs_article'] = array(
		'updated'   => sprintf( _n( '%1$s %2$s updated.', '%1$s %3$s updated.', $bulk_counts['updated'], 'kb-support' ), $bulk_counts['updated'], $article_singular, $article_plural ),
		'locked'    => sprintf( _n( '%1$s %2$s not updated, somebody is editing it.', '%1$s %3$s not updated, somebody is editing them.', $bulk_counts['locked'], 'kb-support' ), $bulk_counts['locked'], $article_singular, $article_plural ),
		'deleted'   => sprintf( _n( '%1$s %2$s permanently deleted.', '%1$s %3$s permanently deleted.', $bulk_counts['deleted'], 'kb-support' ), $bulk_counts['deleted'], $article_singular, $article_plural ),
		'trashed'   => sprintf( _n( '%1$s %2$s moved to the Trash.', '%1$s %3$s moved to the Trash.', $bulk_counts['trashed'], 'kb-support' ), $bulk_counts['trashed'], $article_singular, $article_plural ),
		'untrashed' => sprintf( _n( '%1$s %2$s restored from the Trash.', '%1$s %3$s restored from the Trash.', $bulk_counts['untrashed'], 'kb-support' ), $bulk_counts['untrashed'], $article_singular, $article_plural )
	);

	return $bulk_messages;

}
add_filter( 'bulk_post_updated_messages', 'kbs_bulk_updated_messages', 10, 2 );
