<?php
/**
 * Customer Meta DB class
 *
 * This class is for interacting with the customer meta database table
 *
 * @package		KBS
 * @subpackage	Classes/DB_Customer_Meta
 * @copyright   Copyright (c) 2017, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

class KBS_DB_Customer_Meta extends KBS_DB {

	/**
	 * Get things started
	 *
	 * @access	public
	 * @since	1.0
	*/
	public function __construct() {
		global $wpdb;

		$this->table_name  = $wpdb->prefix . 'kbs_customermeta';
		$this->primary_key = 'meta_id';
		$this->version     = '1.0';

		add_action( 'plugins_loaded', array( $this, 'register_table' ), 11 );

	} // __construct

	/**
	 * Get table columns and data types
	 *
	 * @access	public
	 * @since	1.0
	*/
	public function get_columns() {
		return array(
			'meta_id'         => '%d',
			'kbs_customer_id' => '%d',
			'meta_key'        => '%s',
			'meta_value'      => '%s',
		);
	} // get_columns

	/**
	 * Register the table with $wpdb so the metadata api can find it
	 *
	 * @access	public
	 * @since	1.0
	*/
	public function register_table() {
		global $wpdb;
		$wpdb->kbs_customermeta = $this->table_name;
	} // register_table

	/**
	 * Retrieve customer meta field for a customer.
	 *
	 * For internal use only. Use KBS_Customer->get_meta() for public usage.
	 *
	 * @param	int		$customer_id	Customer ID.
	 * @param	str		$meta_key		The meta key to retrieve.
	 * @param	bool	$single			Whether to return a single value.
	 * @return	mixed	Will be an array if $single is false. Will be value of meta data field if $single is true.
	 *
	 * @access	public
	 * @since	1.0
	 */
	public function get_meta( $customer_id = 0, $meta_key = '', $single = false ) {
		$customer_id = $this->sanitize_customer_id( $customer_id );
		if ( false === $customer_id ) {
			return false;
		}

		return get_metadata( 'kbs_customer', $customer_id, $meta_key, $single );
	} // get_meta

	/**
	 * Add meta data field to a customer.
	 *
	 * For internal use only. Use KBS_Customer->add_meta() for public usage.
	 *
	 * @param	int		$customer_id	Customer ID.
	 * @param	str		$meta_key		Metadata name.
	 * @param	mixed	$meta_value		Metadata value.
	 * @param	bool	$unique			Optional, default is false. Whether the same key should not be added.
	 * @return	bool	False for failure. True for success.
	 *
	 * @access	private
	 * @since	1.0
	 */
	public function add_meta( $customer_id, $meta_key = '', $meta_value, $unique = false ) {

		if ( empty( $customer_id ) || empty( $meta_key ) || empty( $meta_value ) ) {
			return false;
		}

		$customer_id = $this->sanitize_customer_id( $customer_id );
		if ( false === $customer_id ) {
			return false;
		}

		return add_metadata( 'kbs_customer', $customer_id, $meta_key, $meta_value, $unique );
	} // add_meta

	/**
	 * Update customer meta field based on Customer ID.
	 *
	 * For internal use only. Use KBS_Customer->update_meta() for public usage.
	 *
	 * Use the $prev_value parameter to differentiate between meta fields with the
	 * same key and Customer ID.
	 *
	 * If the meta field for the customer does not exist, it will be added.
	 *
	 * @param	int		$customer_id	Customer ID.
	 * @param	str		$meta_key		Metadata key.
	 * @param	mixed	$meta_value		Metadata value.
	 * @param	mixed	$prev_value		Optional. Previous value to check before removing.
	 * @return	bool	False on failure, true if success.
	 *
	 * @access	private
	 * @since	1.0
	 */
	public function update_meta( $customer_id, $meta_key = '', $meta_value, $prev_value = '' ) {

		if ( empty( $customer_id ) || empty( $meta_key ) || empty( $meta_value ) ) {
			return false;
		}

		$customer_id = $this->sanitize_customer_id( $customer_id );
		if ( false === $customer_id ) {
			return false;
		}

		return update_metadata( 'kbs_customer', $customer_id, $meta_key, $meta_value, $prev_value );
	} // update_meta

	/**
	 * Remove metadata matching criteria from a customer.
	 *
	 * For internal use only. Use KBS_Customer->delete_meta() for public usage.
	 *
	 * You can match based on the key, or key and value. Removing based on key and
	 * value, will keep from removing duplicate metadata with the same key. It also
	 * allows removing all metadata matching key, if needed.
	 *
	 * @param	int		$customer_id	Customer ID.
	 * @param	str		$meta_key		Metadata name.
	 * @param	mixed	$meta_value		Optional. Metadata value.
	 * @return	bool	False for failure. True for success.
	 *
	 * @access	private
	 * @since	1.0
	 */
	public function delete_meta( $customer_id = 0, $meta_key = '', $meta_value = '' ) {
		return delete_metadata( 'kbs_customer', $customer_id, $meta_key, $meta_value );
	} // delete_meta

	/**
	 * Create the table
	 *
	 * @access	public
	 * @since	1.0
	*/
	public function create_table() {

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE {$this->table_name} (
			meta_id bigint(20) NOT NULL AUTO_INCREMENT,
			kbs_customer_id bigint(20) NOT NULL,
			meta_key varchar(255) DEFAULT NULL,
			meta_value longtext,
			PRIMARY KEY  (meta_id),
			KEY kbs_customer_id (kbs_customer_id),
			KEY meta_key (meta_key)
			) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	} // create_table

	/**
	 * Given a customer ID, make sure it's a positive number, greater than zero before inserting or adding.
	 *
	 * @since	1.0
	 * @param	int|str		$customer_id	A passed customer ID.
	 * @return	int|bool	The normalized customer ID or false if it's found to not be valid.
	 */
	private function sanitize_customer_id( $customer_id ) {
		if ( ! is_numeric( $customer_id ) ) {
			return false;
		}

		$customer_id = (int) $customer_id;

		// We were given a non positive number
		if ( absint( $customer_id ) !== $customer_id ) {
			return false;
		}

		if ( empty( $customer_id ) ) {
			return false;
		}

		return absint( $customer_id );

	} // sanitize_customer_id

} // KBS_DB_Customer_Meta
