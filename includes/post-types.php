<?php
/**
 * Post Type Functions
 *
 * @package     KBS
 * @subpackage  Functions
 * @copyright   Copyright (c) 2016, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registers and sets up the Tickets and Articles custom post types
 *
 * @since	0.1
 * @return	void
 */
function kbs_setup_kbs_post_types() {

	$ticket_archives = defined( 'KBS_TICKET_DISABLE_ARCHIVE' ) && KBS_DISABLE_ARCHIVE ? false : true;
	$ticket_slug     = defined( 'KBS_TICKET_SLUG' ) ? KBS_SLUG : 'tickets';
	$ticket_rewrite  = defined( 'KBS_TICKET_DISABLE_REWRITE' ) && KBS_DISABLE_REWRITE ? false : array( 'slug' => $ticket_slug, 'with_front' => false );

	$ticket_labels =  apply_filters( 'kbs_ticket_labels', array(
		'name'                  => _x( '%2$s', 'ticket post type name', 'kb-support' ),
		'singular_name'         => _x( '%1$s', 'singular ticket post type name', 'kb-support' ),
		'add_new'               => __( 'Add New', 'kb-support' ),
		'add_new_item'          => __( 'Add New %1$s', 'kb-support' ),
		'edit_item'             => __( 'Edit %1$s', 'kb-support' ),
		'new_item'              => __( 'New %1$s', 'kb-support' ),
		'all_items'             => __( 'All %2$s', 'kb-support' ),
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
		'labels'             => $ticket_labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => $ticket_rewrite,
		'capability_type'    => 'case',
		'map_meta_cap'       => true,
		'has_archive'        => $ticket_archives,
		'hierarchical'       => false,
		'supports'           => apply_filters( 'kbs_ticket_supports', array( 'title', 'editor', 'revisions', 'author' ) )
	);
	register_post_type( 'kbs_ticket', apply_filters( 'kbs_ticket_post_type_args', $ticket_args ) );


	/** Articles Post Type */
	$article_labels = array(
		'name'               => _x( 'KB Articles', 'post type general name', 'kb-support' ),
		'singular_name'      => _x( 'KB Article', 'post type singular name', 'kb-support' ),
		'add_new'            => __( 'Add New', 'kb-support' ),
		'add_new_item'       => __( 'Add New KB Article', 'kb-support' ),
		'edit_item'          => __( 'Edit KB Article', 'kb-support' ),
		'new_item'           => __( 'New KB Article', 'kb-support' ),
		'all_items'          => __( 'All KB Articles', 'kb-support' ),
		'view_item'          => __( 'View Article', 'kb-support' ),
		'search_items'       => __( 'Search Articles', 'kb-support' ),
		'not_found'          => __( 'No KB Articles found', 'kb-support' ),
		'not_found_in_trash' => __( 'No KB Articles found in Trash', 'kb-support' ),
		'parent_item_colon'  => '',
		'menu_name'          => __( 'KB Articles', 'kb-support' )
	);

	$article_args = array(
		'labels'          => apply_filters( 'kbs_article_labels', $article_labels ),
		'public'          => false,
		'query_var'       => false,
		'rewrite'         => false,
		'capability_type' => 'support_article',
		'map_meta_cap'    => true,
		'supports'        => apply_filters( 'kbs_article_supports', array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author', 'trackbacks', 'comments' ) ),
		'can_export'      => true
	);
	register_post_type( 'kbs_article', $article_args );

} // kbs_setup_kbs_post_types
add_action( 'init', 'kbs_setup_kbs_post_types', 1 );

/**
 * Get Default Labels
 *
 * @since	0.1
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
 * @since	0.1
 *
 * @param	bool	$lowercase
 * @return	str		$defaults['singular']	Singular Ticket label
 */
function kbs_get_ticket_label_singular( $lowercase = false ) {
	$defaults = kbs_get_default_ticket_labels();
	return ($lowercase) ? strtolower( $defaults['singular'] ) : $defaults['singular'];
} // kbs_get_ticket_label_singular

/**
 * Get Plural Ticket Label
 *
 * @since	0.1
 *
 * @param	bool	$lowercase
 * @return	str		$defaults['plural']		Plural Ticket label
 */
function kbs_get_ticket_label_plural( $lowercase = false ) {
	$defaults = kbs_get_default_ticket_labels();
	return ( $lowercase ) ? strtolower( $defaults['plural'] ) : $defaults['plural'];
} // kbs_get_ticket_label_plural

/**
 * Get Default Labels
 *
 * @since	0.1
 * @return	arr		$defaults	Default labels
 */
function kbs_get_default_article_labels() {
	$defaults = array(
	   'singular' => __( 'Article', 'kb-support' ),
	   'plural'   => __( 'Articles','kb-support' )
	);
	return apply_filters( 'kbs_default_articles_name', $defaults );
} // kbs_get_default_article_labels

/**
 * Get Singular Article Label
 *
 * @since	0.1
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
 * @since	0.1
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
 * @since	0.1
 * @param	str		$title	Default title placeholder text
 * @return	str		$title	New placeholder text
 */
function kbs_change_default_title( $title ) {

	 // If a frontend plugin uses this filter (check extensions before changing this function)
	 if ( ! is_admin() ) {
		$label = kbs_get_ticket_label_singular();
		$title = sprintf( __( 'Enter %s name here', 'kb-support' ), $label );
		return $title;
	 }

	 $screen = get_current_screen();

	 if ( 'kbs_ticket' == $screen->post_type ) {
		$label = kbs_get_ticket_label_singular();
		$title = sprintf( __( 'Enter %s name here', 'kb-support' ), $label );
	 }
	 
	 if ( 'kbs_article' == $screen->post_type ) {
		$label = kbs_get_article_label_singular();
		$title = sprintf( __( 'Enter %s name here', 'kb-support' ), $label );
	 }

	 return $title;

} // kbs_change_default_title
add_filter( 'enter_title_here', 'kbs_change_default_title' );

/**
 * Registers the custom taxonomies for the kbs_ticket and kbs_article custom post types.
 *
 * @since	0.1
 * @return	void
*/
function kbs_setup_ticket_taxonomies() {

	$ticket_slug     = defined( 'KBS_TICKET_SLUG' ) ? KBS_TICKET_SLUG : 'tickets';

	/** Categories */
	$category_labels = array(
		'name'              => sprintf( _x( '%s Categories', 'taxonomy general name', 'kb-support' ), kbs_get_ticket_label_singular() ),
		'singular_name'     => sprintf( _x( '%s Category', 'taxonomy singular name', 'kb-support' ), kbs_get_ticket_label_singular() ),
		'search_items'      => sprintf( __( 'Search %s Categories', 'kb-support' ), kbs_get_ticket_label_singular() ),
		'all_items'         => sprintf( __( 'All %s Categories', 'kb-support' ), kbs_get_ticket_label_singular() ),
		'parent_item'       => sprintf( __( 'Parent %s Category', 'kb-support' ), kbs_get_ticket_label_singular() ),
		'parent_item_colon' => sprintf( __( 'Parent %s Category:', 'kb-support' ), kbs_get_ticket_label_singular() ),
		'edit_item'         => sprintf( __( 'Edit %s Category', 'kb-support' ), kbs_get_ticket_label_singular() ),
		'update_item'       => sprintf( __( 'Update %s Category', 'kb-support' ), kbs_get_ticket_label_singular() ),
		'add_new_item'      => sprintf( __( 'Add New %s Category', 'kb-support' ), kbs_get_ticket_label_singular() ),
		'new_item_name'     => sprintf( __( 'New %s Category Name', 'kb-support' ), kbs_get_ticket_label_singular() ),
		'menu_name'         => __( 'Categories', 'kb-support' ),
	);

	$category_args = apply_filters( 'kbs_ticket_category_args', array(
			'hierarchical' => true,
			'labels'       => apply_filters('kbs_ticket_category_labels', $category_labels),
			'show_ui'      => true,
			'query_var'    => 'ticket_category',
			'rewrite'      => array( 'slug' => $ticket_slug . '/category', 'with_front' => false, 'hierarchical' => true ),
			'capabilities' => array( 'manage_terms' => 'manage_case_terms','edit_terms' => 'edit_case_terms','assign_terms' => 'assign_case_terms','delete_terms' => 'delete_case_terms' )
		)
	);
	register_taxonomy( 'ticket_category', array( 'kbs_ticket' ), $category_args );
	register_taxonomy_for_object_type( 'ticket_category', 'kbs_ticket' );

	/** Tags */
	$tag_labels = array(
		'name'                  => sprintf( _x( '%s Tags', 'taxonomy general name', 'kb-support' ), kbs_get_ticket_label_singular() ),
		'singular_name'         => sprintf( _x( '%s Tag', 'taxonomy singular name', 'kb-support' ), kbs_get_ticket_label_singular() ),
		'search_items'          => sprintf( __( 'Search %s Tags', 'kb-support' ), kbs_get_ticket_label_singular() ),
		'all_items'             => sprintf( __( 'All %s Tags', 'kb-support' ), kbs_get_ticket_label_singular() ),
		'parent_item'           => sprintf( __( 'Parent %s Tag', 'kb-support' ), kbs_get_ticket_label_singular() ),
		'parent_item_colon'     => sprintf( __( 'Parent %s Tag:', 'kb-support' ), kbs_get_ticket_label_singular() ),
		'edit_item'             => sprintf( __( 'Edit %s Tag', 'kb-support' ), kbs_get_ticket_label_singular() ),
		'update_item'           => sprintf( __( 'Update %s Tag', 'kb-support' ), kbs_get_ticket_label_singular() ),
		'add_new_item'          => sprintf( __( 'Add New %s Tag', 'kb-support' ), kbs_get_ticket_label_singular() ),
		'new_item_name'         => sprintf( __( 'New %s Tag Name', 'kb-support' ), kbs_get_ticket_label_singular() ),
		'menu_name'             => __( 'Tags', 'kb-support' ),
		'choose_from_most_used' => sprintf( __( 'Choose from most used %s tags', 'kb-support' ), kbs_get_ticket_label_singular() ),
	);

	$tag_args = apply_filters( 'kbs_ticket_tag_args', array(
			'hierarchical' => false,
			'labels'       => apply_filters( 'kbs_ticket_tag_labels', $tag_labels ),
			'show_ui'      => true,
			'query_var'    => 'ticket_tag',
			'rewrite'      => array( 'slug' => $slug . '/tag', 'with_front' => false, 'hierarchical' => true  ),
			'capabilities' => array( 'manage_terms' => 'manage_case_terms','edit_terms' => 'edit_case_terms','assign_terms' => 'assign_case_terms','delete_terms' => 'delete_case_terms' )
		)
	);
	register_taxonomy( 'ticket_tag', array( 'kbs_ticket' ), $tag_args );
	register_taxonomy_for_object_type( 'ticket_tag', 'kbs_ticket' );
} // kbs_setup_ticket_taxonomies
add_action( 'init', 'kbs_setup_ticket_taxonomies', 0 );

/**
 * Get the singular and plural labels for a ticket taxonomy.
 *
 * @since	0.1
 * @param	str		$taxonomy	The Taxonomy to get labels for
 * @return	arr		Associative array of labels (name = plural)
 */
function kbs_get_ticket_taxonomy_labels( $taxonomy = 'ticket_category' ) {

	$allowed_taxonomies = apply_filters( 'kbs_allowed_ticket_taxonomies', array( 'ticket_category', 'ticket_tag' ) );

	if ( ! in_array( $taxonomy, $allowed_taxonomies ) ) {
		return false;
	}

	$labels   = array();
	$taxonomy = get_taxonomy( $taxonomy );

	if ( false !== $taxonomy ) {
		$singular = $taxonomy->labels->singular_name;
		$name     = $taxonomy->labels->name;

		$labels = array(
			'name'          => $name,
			'singular_name' => $singular,
		);
	}

	return apply_filters( 'kbs_get_ticket_taxonomy_labels', $labels, $taxonomy );

} // kbs_get_ticket_taxonomy_labels

/**
 * Registers Custom Post Statuses which are used by the Tickets.
 *
 *
 * @since	0.1
 * @return	void
 */
function kbs_register_post_type_statuses() {

	// Ticket Statuses
	register_post_status( 'refunded', array(
		'label'                     => _x( 'Refunded', 'Refunded payment status', 'kb-support' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Refunded <span class="count">(%s)</span>', 'Refunded <span class="count">(%s)</span>', 'kb-support' )
	) );
	register_post_status( 'failed', array(
		'label'                     => _x( 'Failed', 'Failed payment status', 'kb-support' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Failed <span class="count">(%s)</span>', 'Failed <span class="count">(%s)</span>', 'kb-support' )
	)  );
	register_post_status( 'revoked', array(
		'label'                     => _x( 'Revoked', 'Revoked payment status', 'kb-support' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Revoked <span class="count">(%s)</span>', 'Revoked <span class="count">(%s)</span>', 'kb-support' )
	)  );
	register_post_status( 'abandoned', array(
		'label'                     => _x( 'Abandoned', 'Abandoned payment status', 'kb-support' ),
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Abandoned <span class="count">(%s)</span>', 'Abandoned <span class="count">(%s)</span>', 'kb-support' )
	)  );

} // kbs_register_post_type_statuses
add_action( 'init', 'kbs_register_post_type_statuses', 2 );

/**
 * Updated Messages
 *
 * Returns an array of with all updated messages.
 *
 * @since	0.1
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
		1 => sprintf( __( '%2$s updated. %1$sView %2$s%3$s.', 'kb-support'   ), $url1, $url2, $url4 ),
		4 => sprintf( __( '%2$s updated. %1$sView %2$s%3$s.', 'kb-support'   ), $url1, $url2, $url4 ),
		6 => sprintf( __( '%2$s opened. %1$sView %2$s%3$s.', 'kb-support'    ), $url1, $url2, $url4 ),
		7 => sprintf( __( '%2$s saved. %1$sView %2$s%3$s.', 'kb-support'     ), $url1, $url2, $url4 ),
		8 => sprintf( __( '%2$s submitted. %1$sView %2$s%3$s.', 'kb-support' ), $url1, $url2, $url4 )
	);
	
	$messages['kbs_article'] = array(
		1 => sprintf( __( '%2$s updated. %1$sView %2$s%3$s.', 'kb-support'   ), $url1, $url3, $url4 ),
		4 => sprintf( __( '%2$s updated. %1$sView %2$s%3$s.', 'kb-support'   ), $url1, $url3, $url4 ),
		6 => sprintf( __( '%2$s published. %1$sView %2$s%3$s.', 'kb-support' ), $url1, $url3, $url4 ),
		7 => sprintf( __( '%2$s saved. %1$sView %2$s%3$s.', 'kb-support'     ), $url1, $url3, $url4 ),
		8 => sprintf( __( '%2$s submitted. %1$sView %2$s%3$s.', 'kb-support' ), $url1, $url3, $url4 )
	);

	return $messages;

} // kbs_updated_messages
add_filter( 'post_updated_messages', 'kbs_updated_messages' );

/**
 * Updated bulk messages
 *
 * @since	0.1
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
