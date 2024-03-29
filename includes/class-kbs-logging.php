<?php
/**
 * Class for logging events and errors.
 *
 * @package     KBS
 * @subpackage  Logging
 * @copyright   Copyright (c) 2017, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * KBS_Logging Class
 *
 * A general use class for logging events and errors.
 *
 * @since	1.0
 */
class KBS_Logging {

	/**
	 * Set up the KBS Logging Class
	 *
	 * @since	1.0
	 */
	public function __construct() {
		// Create the log post type
		add_action( 'init', array( $this, 'register_post_type' ), 1 );

		// Create types taxonomy and default types
		add_action( 'init', array( $this, 'register_taxonomy' ), 1 );

	} // 

	/**
	 * Registers the kbs_log Post Type
	 *
	 * @access	public
	 * @since	1.0
	 * @return	void
	 */
	public function register_post_type() {
		/* Logs post type */
		$log_args = array(
			'labels'              => array( 'name' => esc_html__( 'Logs', 'kb-support' ) ),
			'public'              => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'show_ui'             => false,
			'query_var'           => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'supports'            => array( 'title', 'editor' ),
			'can_export'          => true,
		);

		register_post_type( 'kbs_log', $log_args );
	} // register_post_type

	/**
	 * Registers the Type Taxonomy
	 *
	 * The "Type" taxonomy is used to determine the type of log entry
	 *
	 * @access	public
	 * @since	1.0
	 * @return	void
	*/
	public function register_taxonomy() {
		register_taxonomy( 'kbs_log_type', 'kbs_log', array( 'public' => false ) );
	} // register_taxonomy

	/**
	 * Log types
	 *
	 * Sets up the default log types and allows for new ones to be created
	 *
	 * @access	public
	 * @since	1.0
	 * @return 	arr		$terms
	 */
	public function log_types() {
		$terms = array(
			'submit', 'reply', 'status', 'assign', 'note'
		);

		return apply_filters( 'kbs_log_types', $terms );
	} // log_types

	/**
	 * Check if a log type is valid
	 *
	 * Checks to see if the specified type is in the registered list of types
	 *
	 * @access	public
	 * @since	1.0
	 * @uses	KBS_Logging::log_types()
	 * @param	str		$type	Log type
	 * @return	bool	Whether log type is valid
	 */
	function valid_type( $type ) {
		return in_array( $type, $this->log_types() );
	} // valid_type

	/**
	 * Create new log entry
	 *
	 * This is just a simple and fast way to log something. Use $this->insert_log()
	 * if you need to store custom meta data
	 *
	 * @access	public
	 * @since	1.0
	 * @uses	KBS_Logging::insert_log()
	 * @param	str		$title		Log entry title
	 * @param	str		$message	Log entry message
	 * @param	int		$parent		Log entry parent
	 * @param	str		$type		Log type (default: null)
	 * @return	int		Log ID
	 */
	public function add( $title = '', $message = '', $parent = 0, $type = null ) {
		$log_data = array(
			'post_title'   => $title,
			'post_content' => $message,
			'post_parent'  => $parent,
			'log_type'     => $type,
		);

		return $this->insert_log( $log_data );
	} // add

	/**
	 * Easily retrieves log items for a particular object ID
	 *
	 * @access	public
	 * @since	1.0
	 * @uses	KBS_Logging::get_connected_logs()
	 * @param	int		$object_id	(default: 0)
	 * @param	str		$type		Log type (default: null)
	 * @param	int		$paged		Page number (default: null)
	 * @return	arr		Array of the connected logs
	*/
	public function get_logs( $object_id = 0, $type = null, $paged = null ) {
		return $this->get_connected_logs( array( 'post_parent' => $object_id, 'paged' => $paged, 'log_type' => $type ) );
	} // get_logs

	/**
	 * Stores a log entry
	 *
	 * @access	public
	 * @since	1.0
	 * @uses	KBS_Logging::valid_type()
	 * @param	srr		$log_data	Log entry data
	 * @param	srr		$log_meta	Log entry meta
	 * @return	int		The ID of the newly created log item
	 */
	function insert_log( $log_data = array(), $log_meta = array() ) {
		$defaults = array(
			'post_type'    => 'kbs_log',
			'post_status'  => 'publish',
			'post_parent'  => 0,
			'post_content' => '',
			'log_type'     => false,
		);

		$args = wp_parse_args( $log_data, $defaults );

		do_action( 'kbs_pre_insert_log', $log_data, $log_meta );

		// Store the log entry
		$log_id = wp_insert_post( $args );

		// Set the log type, if any
		if ( $log_data['log_type'] && $this->valid_type( $log_data['log_type'] ) ) {
			wp_set_object_terms( $log_id, $log_data['log_type'], 'kbs_log_type', false );
		}

		// Set log meta, if any
		if ( $log_id && ! empty( $log_meta ) ) {
			foreach ( (array) $log_meta as $key => $meta ) {
				update_post_meta( $log_id, '_kbs_log_' . sanitize_key( $key ), $meta );
			}
		}

		do_action( 'kbs_post_insert_log', $log_id, $log_data, $log_meta );

		return $log_id;
	} // insert_log

	/**
	 * Update and existing log item
	 *
	 * @access	public
	 * @since	1.0
	 * @param	arr		$log_data	Log entry data
	 * @param	arr		$log_meta	Log entry meta
	 * @return	bool	True if successful, false otherwise
	 */
	public function update_log( $log_data = array(), $log_meta = array() ) {

		do_action( 'kbs_pre_update_log', $log_data, $log_meta );

		$defaults = array(
			'post_type'   => 'kbs_log',
			'post_status' => 'publish',
			'post_parent' => 0,
		);

		$args = wp_parse_args( $log_data, $defaults );

		// Store the log entry
		$log_id = wp_update_post( $args );

		if ( $log_id && ! empty( $log_meta ) ) {
			foreach ( (array) $log_meta as $key => $meta ) {
				if ( ! empty( $meta ) )
					update_post_meta( $log_id, '_kbs_log_' . sanitize_key( $key ), $meta );
			}
		}

		do_action( 'kbs_post_update_log', $log_id, $log_data, $log_meta );
	} // update_log

	/**
	 * Retrieve all connected logs
	 *
	 * Used for retrieving logs related to particular items, such as a specific ticket.
	 *
	 * @access	private
	 * @since	1.0
	 * @param	arr		$args	Query arguments
	 * @return	mixed	Array if logs were found, false otherwise
	 */
	public function get_connected_logs( $args = array() ) {
		$defaults = array(
			'post_type'      => 'kbs_log',
			'posts_per_page' => 20,
			'post_status'    => 'publish',
			'paged'          => get_query_var( 'paged' ),
			'log_type'       => false,
		);

		$query_args = wp_parse_args( $args, $defaults );

		if ( $query_args['log_type'] && $this->valid_type( $query_args['log_type'] ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy'  => 'kbs_log_type',
					'field'     => 'slug',
					'terms'     => $query_args['log_type'],
				)
			);
		}

		$logs = get_posts( $query_args );

		if ( $logs )
			return $logs;

		// No logs found
		return false;
	} // get_connected_logs

	/**
	 * Retrieves number of log entries connected to particular object ID
	 *
	 * @access	public
	 * @since	1.0
	 * @param	int		$object_id (default: 0)
	 * @param	str		$type		Log type (default: null)
	 * @param	arr		$meta_query	Log meta query (default: null)
	 * @param	arr		$date_query	Log data query (default: null)
	 * @return	int		Log count
	 */
	public function get_log_count( $object_id = 0, $type = null, $meta_query = null, $date_query = null ) {

		$query_args = array(
			'post_parent'      => $object_id,
			'post_type'        => 'kbs_log',
			'posts_per_page'   => -1,
			'post_status'      => 'publish',
			'fields'           => 'ids',
		);

		if ( ! empty( $type ) && $this->valid_type( $type ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy'  => 'kbs_log_type',
					'field'     => 'slug',
					'terms'     => $type,
				)
			);
		}

		if ( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query;
		}

		if ( ! empty( $date_query ) ) {
			$query_args['date_query'] = $date_query;
		}

		$logs = new WP_Query( $query_args );

		return (int) $logs->post_count;
	} // get_log_count

	/**
	 * Delete a log
	 *
	 * @access public
	 * @since	1.0
	 * @uses	KBS_Logging::valid_type
	 * @param	int		$object_id (default: 0)
	 * @param	str		$type		Log type (default: null)
	 * @param	arr		$meta_query	Log meta query (default: null)
	 * @return	void
	 */
	public function delete_logs( $object_id = 0, $type = null, $meta_query = null  ) {
		$query_args = array(
			'post_parent'    => $object_id,
			'post_type'      => 'kbs_log',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		);

		if ( ! empty( $type ) && $this->valid_type( $type ) ) {
			$query_args['tax_query'] = array(
				array(
					'taxonomy'  => 'kbs_log_type',
					'field'     => 'slug',
					'terms'     => $type,
				)
			);
		}

		if ( ! empty( $meta_query ) ) {
			$query_args['meta_query'] = $meta_query;
		}

		$logs = get_posts( $query_args );

		if ( $logs ) {
			foreach ( $logs as $log ) {
				wp_delete_post( $log, true );
			}
		}
	} // delete_logs

} // KBS_Logging

// Initiate the logging system
$GLOBALS['kbs_logs'] = new KBS_Logging();

/**
 * Record a log entry
 *
 * This is just a simple wrapper function for the log class add() function
 *
 * @since	1.0
 *
 * @param	str		$title
 * @param	str		$message
 * @param	int		$parent
 * @param	null	$type
 *
 * @global	$kbs_logs	KBS Logs Object
 *
 * @uses	KBS_Logging::add()
 *
 * @return	mixed	ID of the new log entry
 */
function kbs_record_log( $title = '', $message = '', $parent = 0, $type = null ) {
	global $kbs_logs;
	$log = $kbs_logs->add( $title, $message, $parent, $type );
	return $log;
} // kbs_record_log
