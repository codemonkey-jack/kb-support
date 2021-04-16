<?php
/**
 * Customer Object
 *
 * Largely taken from Easy Digital Downloads.
 *
 * @package     KBS
 * @subpackage  Classes/Customer
 * @copyright   Copyright (c) 2017, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * KBS_Customer Class
 *
 * @since	1.0
 */
class KBS_Customer {

	/**
	 * The customer ID
	 *
	 * @since 1.0
	 */
	public $id = 0;

	/**
	 * The customer's ticket count
	 *
	 * @since 1.0
	 */
	public $ticket_count = 0;

	/**
	 * The customer's primary email
	 *
	 * @since 1.0
	 */
	public $email;

	/**
	 * The customer's emails
	 *
	 * @since 1.0
	 */
	public $emails;

	/**
	 * The company ID
	 *
	 * @since 1.0
	 */
	public $company_id = 0;

	/**
	 * The company name
	 *
	 * @since 1.0
	 */
	public $company;

	/**
	 * The customer's primary phone number
	 *
	 * @since 1.0
	 */
	public $primary_phone;

	/**
	 * The customer's additional phone number
	 *
	 * @since 1.0
	 */
	public $additional_phone;

	/**
	 * The customer's website address
	 *
	 * @since 1.0
	 */
	public $website;

	/**
	 * The customer's name
	 *
	 * @since 1.0
	 */
	public $name;

	/**
	 * The customer's creation date
	 *
	 * @since 1.0
	 */
	public $date_created;

	/**
	 * The ticket IDs associated with the customer
	 *
	 * @since  1.0
	 */
	public $ticket_ids;

	/**
	 * The user ID associated with the customer
	 *
	 * @since  1.0
	 */
	public $user_id;

	/**
	 * Customer Notes
	 *
	 * @since	1.0
	 */
	public $notes;

	/**
	 * The Database Abstraction
	 *
	 * @since  1.0
	 */
	protected $db;

	/**
	 * Get things going
	 *
	 * @since 1.0
	 */
	public function __construct( $_id_or_email = false, $by_user_id = false ) {

		$this->db = new KBS_DB_Customers;

		if ( false === $_id_or_email || ( is_numeric( $_id_or_email ) && (int) $_id_or_email !== absint( $_id_or_email ) ) ) {
			return false;
		}

		$by_user_id = is_bool( $by_user_id ) ? $by_user_id : false;

		if ( is_numeric( $_id_or_email ) ) {
			$field = $by_user_id ? 'user_id' : 'id';
		} else {
			$field = 'email';
		}

		$customer = $this->db->get_customer_by( $field, $_id_or_email );

		if ( empty( $customer ) || ! is_object( $customer ) ) {
			return false;
		}

		$this->setup_customer( $customer );

	} // __construct

	/**
	 * Given the customer data, let's set the variables
	 *
	 * @since	1.0
	 * @param	obj		$customer	The Customer Object
	 * @return 	bool	If the setup was successful or not
	 */
	private function setup_customer( $customer ) {

		if ( ! is_object( $customer ) ) {
			return false;
		}

		foreach ( $customer as $key => $value ) {

			switch ( $key ) {

				case 'notes':
					$this->$key = $this->get_notes();
					break;

				default:
					$this->$key = $value;
					break;

			}

		}

		$this->company          = $this->get_company();

		$this->emails           = (array) $this->get_meta( 'additional_email', false );
		$this->emails[]         = $this->email;

		$this->primary_phone    = $this->get_meta( 'primary_phone', true );
		$this->additional_phone = $this->get_meta( 'additional_phone', true );
		$this->website          = $this->get_meta( 'website', true );

		// Customer ID and email are the only things that are necessary, make sure they exist
		if ( ! empty( $this->id ) && ! empty( $this->email ) ) {
			return true;
		}

		return false;

	} // setup_customer

	/**
	 * Magic __get function to dispatch a call to retrieve a private property
	 *
	 * @since 1.0
	 */
	public function __get( $key ) {

		if ( method_exists( $this, 'get_' . $key ) ) {

			return call_user_func( array( $this, 'get_' . $key ) );

		} else {

			return new WP_Error( 'kbs-customer-invalid-property', sprintf( __( "Can't get property %s", 'kb-support' ), $key ) );

		}

	} // __get

	/**
	 * Creates a customer
	 *
	 * @since	1.0
	 * @param 	arr		$data	Array of attributes for a customer
	 * @return	mixed	False if not a valid creation, Customer ID if user is found or valid creation
	 */
	public function create( $data = array() ) {

		if ( $this->id != 0 || empty( $data ) ) {
			return false;
		}

		$defaults = array(
			'ticket_ids' => ''
		);

		$args = wp_parse_args( $data, $defaults );
		$args = $this->sanitize_columns( $args );

		if ( empty( $args['email'] ) || ! is_email( $args['email'] ) ) {
			return false;
		}

		if ( ! empty( $args['ticket_ids'] ) && is_array( $args['ticket_ids'] ) ) {
			$args['ticket_ids'] = implode( ',', array_unique( array_values( $args['ticket_ids'] ) ) );
		}

		/**
		 * Fires before a customer is created
		 *
		 * @param array $args Contains customer information such as ticket ID, name, and email.
		 */
		do_action( 'kbs_customer_pre_create', $args );

		$created = false;

		// The DB class 'add' implies an update if the customer being asked to be created already exists
		if ( $this->db->add( $data ) ) {

			// We've successfully added/updated the customer, reset the class vars with the new data
			$customer = $this->db->get_customer_by( 'email', $args['email'] );

			// Setup the customer data with the values from DB
			$this->setup_customer( $customer );

			$created = $this->id;
		}

		/**
		 * Fires after a customer is created
		 *
		 * @param	int		$created	If created successfully, the customer ID.  Defaults to false.
		 * @param	arr		$args		Contains customer information such as ticket ID, name, and email.
		 */
		do_action( 'kbs_customer_post_create', $created, $args );

		return $created;

	} // create

	/**
	 * Update a customer record
	 *
	 * @since	1.0
	 * @param	arr		$data	Array of data attributes for a customer (checked via whitelist)
	 * @return	bool	If the update was successful or not
	 */
	public function update( $data = array() ) {

		if ( empty( $data ) ) {
			return false;
		}

		$data = $this->sanitize_columns( $data );

		do_action( 'kbs_customer_pre_update', $this->id, $data );

		$updated = false;

		if ( $this->db->update( $this->id, $data ) ) {

			$customer = $this->db->get_customer_by( 'id', $this->id );
			$this->setup_customer( $customer );

			$updated = true;
		}

		do_action( 'kbs_customer_post_update', $updated, $this->id, $data );

		return $updated;
	} // update

    /**
     * Adds a customer to a company
     *
     * @since   1.2
     * @param   int     $company_id
     * @return  bool    True if successful, otherwise false
     */
    public function add_company( $company_id )  {
        return $this->update( array( 'company_id' => $company_id ) );
    } // add_company

	/**
	 * Retrieve the customers company
	 *
	 * @since	1.0
	 * @return	str		The company name
	 */
	public function get_company()	{
		if ( 0 != $this->company_id )	{
			return kbs_get_company_name( $this->company_id );
		}
	} // get_company

	/**
	 * Attach an email to the customer
	 *
	 * @since	1.0
	 * @param	str		$email		The email address to remove from the customer
	 * @param	bool	$primary	Allows setting the email added as the primary
	 * @return	bool	If the email was added successfully
	 */
	public function add_email( $email = '', $primary = false ) {

		if( ! is_email( $email ) ) {
			return false;
		}

		$existing = new KBS_Customer( $email );

		if( $existing->id > 0 ) {
			// Email address already belongs to a customer
			return false;
		}

		if ( email_exists( $email ) ) {
			$user = get_user_by( 'email', $email );
			if ( $user->ID != $this->user_id ) {
				return false;
			}
		}

		do_action( 'kbs_customer_pre_add_email', $email, $this->id, $this );

		// Update is used to ensure duplicate emails are not added
		$ret = (bool) $this->add_meta( 'additional_email', $email );

		do_action( 'kbs_customer_post_add_email', $email, $this->id, $this );

		if ( $ret && true === $primary ) {
			$this->set_primary_email( $email );
		}

		return $ret;

	} // add_email

	/**
	 * Remove an email from the customer
	 *
	 * @since	1.0
	 * @param	str		$email	The email address to remove from the customer
	 * @return	bool	If the email was removeed successfully
	 */
	public function remove_email( $email = '' ) {

		if( ! is_email( $email ) ) {
			return false;
		}

		do_action( 'kbs_customer_pre_remove_email', $email, $this->id, $this );

		$ret = (bool) $this->delete_meta( 'additional_email', $email );

		do_action( 'kbs_customer_post_remove_email', $email, $this->id, $this );

		return $ret;

	} // remove_email

	/**
	 * Set an email address as the customer's primary email
	 *
	 * This will move the customer's previous primary email to an additional email
	 *
	 * @since	1.0
	 * @param	str		$new_primary_email	The email address to remove from the customer
	 * @return	bool	If the email was set as primary successfully
	 */
	public function set_primary_email( $new_primary_email = '' ) {

		if( ! is_email( $new_primary_email ) ) {
			return false;
		}

		do_action( 'kbs_customer_pre_set_primary_email', $new_primary_email, $this->id, $this );

		$existing = new KBS_Customer( $new_primary_email );

		if( $existing->id > 0 && (int) $existing->id !== (int) $this->id ) {

			// This email belongs to another customer
			return false;
		}

		$old_email = $this->email;

		// Update customer record with new email
		$update = $this->update( array( 'email' => $new_primary_email ) );

		// Remove new primary from list of additional emails
		$remove = $this->remove_email( $new_primary_email );

		// Add old email to additional emails list
		$add = $this->add_email( $old_email );

		$ret = $update && $remove && $add;

		if( $ret ) {
			$this->email = $new_primary_email;
		}

		do_action( 'kbs_customer_post_set_primary_email', $new_primary_email, $this->id, $this );

		return $ret;

	} // set_primary_email

	/**
	 * Retrieve the number of tickets the customer wants to load per page
	 *
	 * @since	1.4
	 * @return	int		Number of tickets to load per page
	 */
	public function get_tickets_per_page()	{
        return kbs_get_customer_tickets_per_page( $this->user_id );
	} // get_tickets_per_page

	/**
	 * Retrieve the default orderby option for the customer.
	 *
	 * @since	1.4
	 * @return	string	Post field to order by
	 */
	public function get_tickets_orderby()	{
        return kbs_get_user_tickets_orderby_setting( $this->user_id );
	} // get_tickets_orderby

	/**
	 * Retrieve the default order option for the customer.
	 *
	 * @since	1.4
	 * @return	string	Post field to order by
	 */
	public function get_tickets_order()	{
        return kbs_get_user_tickets_order_setting( $this->user_id );
	} // get_tickets_order

    /**
	 * Retrieve the number of replies the customer wants to load
	 *
	 * @since	1.2.6
	 * @return	int		Number of replies to load
	 */
	public function get_replies_to_load()	{
        return kbs_get_customer_replies_to_load( $this->user_id );
	} // get_replies_to_load


	/*
	 * Get the ticket ids of the customer in an array.
	 *
	 * @since	1.0
	 * @return	arr		An array of ticket IDs for the customer, or an empty array if none exist.
	 */
	public function get_ticket_ids() {

		$ticket_ids = $this->ticket_ids;

		if ( ! empty( $ticket_ids ) ) {
			$ticket_ids = array_map( 'absint', explode( ',', $ticket_ids ) );
		} else {
			$ticket_ids = array();
		}

		return $ticket_ids;

	} // get_ticket_ids

	/*
	 * Get an array of KBS_Ticket objects from the ticket_ids attached to the customer
	 *
	 * @since	1.0
	 * @param	arr|str		$status		A single status as a string or an array of statuses
	 * @return	arr			An array of KBS_Ticket objects or an empty array
	 */
	public function get_tickets( $status = array() ) {

		$ticket_ids = $this->get_ticket_ids();

		$tickets = array();
		foreach ( $ticket_ids as $ticket_id ) {

			$ticket = new KBS_Ticket( $ticket_id );
			if ( empty( $status ) || ( is_array( $status ) && in_array( $ticket->status, $status ) ) || $status == $ticket->status ) {
				$tickets[] = new KBS_Ticket( $ticket_id );
			}

		}

		return $tickets;

	} // get_tickets

	/**
	 * Attach ticket to the customer then triggers increasing stats
	 *
	 * @since	1.0
	 * @param	int		$ticket_id		The ticket ID to attach to the customer
	 * @param	bool	$update_stats	Whether or not to update customer stats
	 * @return	bool	If the attachment was successfuly
	 */
	public function attach_ticket( $ticket_id = 0, $update_stats = true ) {

		if( empty( $ticket_id ) ) {
			return false;
		}

		$ticket = new KBS_Ticket( $ticket_id );

		if( empty( $this->ticket_ids ) ) {

			$new_ticket_ids = $ticket->ID;

		} else {

			$ticket_ids = array_map( 'absint', explode( ',', $this->ticket_ids ) );

			if ( in_array( $ticket->ID, $ticket_ids ) ) {
				$update_stats = false;
			}

			$ticket_ids[] = $ticket->ID;

			$new_ticket_ids = implode( ',', array_unique( array_values( $ticket_ids ) ) );

		}

		do_action( 'kbs_customer_pre_attach_ticket', $ticket->ID, $this->id );

		$ticket_added = $this->update( array( 'ticket_ids' => $new_ticket_ids ) );

		if ( $ticket_added ) {
			$this->ticket_ids = $new_ticket_ids;

			if ( $update_stats ) {
				$this->increase_ticket_count();
			}
		}

		do_action( 'kbs_customer_post_attach_ticket', $ticket_added, $ticket->ID, $this->id );

		return $ticket_added;
	} // attach_ticket


	/**
	 * Remove a ticket from this customer, then triggers reducing stats
	 *
	 * @since	1.0
	 * @param	int		$ticket_id	The Ticket ID to remove
	 * @return	bool	If the removal was successful
	 */
	public function remove_ticket( $ticket_id = 0 ) {

		if( empty( $ticket_id ) ) {
			return false;
		}

		$ticket = new KBS_Ticket( $ticket_id );

		$new_ticket_ids = '';

		if( ! empty( $this->ticket_ids ) ) {

			$ticket_ids = array_map( 'absint', explode( ',', $this->ticket_ids ) );

			$pos = array_search( $ticket->ID, $ticket_ids );
			if ( false === $pos ) {
				return false;
			}

			unset( $ticket_ids[ $pos ] );
			$ticket_ids = array_filter( $ticket_ids );

			$new_ticket_ids = implode( ',', array_unique( array_values( $ticket_ids ) ) );

		}

		do_action( 'kbs_customer_pre_remove_ticket', $ticket->ID, $this->id );

		$ticket_removed = $this->update( array( 'ticket_ids' => $new_ticket_ids ) );

		if ( $ticket_removed ) {

			$this->ticket_ids = $new_ticket_ids;
			$this->decrease_ticket_count();

		}

		do_action( 'kbs_customer_post_remove_ticket', $ticket_removed, $ticket->ID, $this->id );

		return $ticket_removed;

	} // remove_ticket

	/**
	 * Increase the ticket count of a customer
	 *
	 * @since	1.0
	 * @param	int	$count	The number to imcrement by
	 * @return	int	The ticket count
	 */
	public function increase_ticket_count( $count = 1 ) {

		// Make sure it's numeric and not negative
		if ( ! is_numeric( $count ) || $count != absint( $count ) ) {
			return false;
		}

		$new_total = (int) $this->ticket_count + (int) $count;

		do_action( 'kbs_customer_pre_increase_ticket_count', $count, $this->id );

		if ( $this->update( array( 'ticket_count' => $new_total ) ) ) {
			$this->ticket_count = $new_total;
		}

		do_action( 'kbs_customer_post_increase_ticket_count', $this->ticket_count, $count, $this->id );

		return $this->ticket_count;
	} // increase_ticket_count

	/**
	 * Decrease the customer ticket count
	 *
	 * @since	1.0
	 * @param	int		$count The amount to decrease by
	 * @return	mixed	If successful, the new count, otherwise false
	 */
	public function decrease_ticket_count( $count = 1 ) {

		// Make sure it's numeric and not negative
		if ( ! is_numeric( $count ) || $count != absint( $count ) ) {
			return false;
		}

		$new_total = (int) $this->ticket_count - (int) $count;

		if( $new_total < 0 ) {
			$new_total = 0;
		}

		do_action( 'kbs_customer_pre_decrease_ticket_count', $count, $this->id );

		if ( $this->update( array( 'ticket_count' => $new_total ) ) ) {
			$this->ticket_count = $new_total;
		}

		do_action( 'kbs_customer_post_decrease_ticket_count', $this->ticket_count, $count, $this->id );

		return $this->ticket_count;
	} // decrease_ticket_count

	/**
	 * Get the parsed notes for a customer as an array.
	 *
	 * @since	1.0
	 * @param	int		$length		The number of notes to get
	 * @param	int		$paged		What note to start at
	 * @return	arr		The notes requsted
	 */
	public function get_notes( $length = 20, $paged = 1 ) {

		$length = is_numeric( $length ) ? $length : 20;
		$offset = is_numeric( $paged ) && $paged != 1 ? ( ( absint( $paged ) - 1 ) * $length ) : 0;

		$all_notes   = $this->get_raw_notes();
		$notes_array = array_reverse( array_filter( explode( "\n\n", $all_notes ) ) );

		$desired_notes = array_slice( $notes_array, $offset, $length );

		return $desired_notes;

	} // get_notes

	/**
	 * Get the total number of notes we have after parsing.
	 *
	 * @since	1.0
	 * @return	int		The number of notes for the customer
	 */
	public function get_notes_count() {

		$all_notes   = $this->get_raw_notes();
		$notes_count = 0;

		if ( ! empty( $all_notes ) )	{
			$notes_array = array_reverse( array_filter( explode( "\n\n", $all_notes ) ) );
			$notes_count = count( $notes_array );
		}

		return $notes_count;

	} // get_notes_count

	/**
	 * Add a note for the customer.
	 *
	 * @since	1.0
	 * @param	str			$note	The note to add
	 * @return	str|bool	The new note if added succesfully, false otherwise
	 */
	public function add_note( $note = '' ) {

		$note = trim( $note );
		if ( empty( $note ) ) {
			return false;
		}

		$notes = $this->get_raw_notes();

		if( empty( $notes ) ) {
			$notes = '';
		}

		$note_string = date_i18n( 'F j, Y H:i:s', current_time( 'timestamp' ) ) . ' - ' . $note;
		$new_note    = apply_filters( 'kbs_customer_add_note_string', $note_string );
		$notes      .= "\n\n" . $new_note;

		do_action( 'kbs_customer_pre_add_note', $new_note, $this->id );

		$updated = $this->update( array( 'notes' => $notes ) );

		if ( $updated ) {
			$this->notes = $this->get_notes();
		}

		do_action( 'kbs_customer_post_add_note', $this->notes, $new_note, $this->id );

		// Return the formatted note, so we can test, as well as update any displays
		return $new_note;

	} // add_note

	/**
	 * Get the notes column for the customer.
	 *
	 * @since	1.0
	 * @return	str		The Notes for the customer, non-parsed
	 */
	private function get_raw_notes() {

		$all_notes = $this->db->get_column( 'notes', $this->id );

		return (string) $all_notes;

	} // get_raw_notes

	/**
	 * Retrieve customer meta field for a customer.
	 *
	 * @param	str		$meta_key	The meta key to retrieve.
	 * @param	bool	$single		Whether to return a single value.
	 * @return	mixed	Will be an array if $single is false. Will be value of meta data field if $single is true.
	 *
	 * @access	public
	 * @since	1.0
	 */
	public function get_meta( $meta_key = '', $single = true ) {
		return KBS()->customer_meta->get_meta( $this->id, $meta_key, $single );
	} // get_meta

	/**
	 * Add meta data field to a customer.
	 *
	 * @param	str		$meta_key		Metadata name.
	 * @param	mixed	$meta_value		Metadata value.
	 * @param	bool	$unique			Optional, default is false. Whether the same key should not be added.
	 * @return	bool	False for failure. True for success.
	 *
	 * @access	public
	 * @since	1.0
	 */
	public function add_meta( $meta_key = '', $meta_value, $unique = false ) {
		return KBS()->customer_meta->add_meta( $this->id, $meta_key, $meta_value, $unique );
	} // add_meta

	/**
	 * Update customer meta field based on customer ID.
	 *
	 * @param	str		$meta_key		Metadata key.
	 * @param	mixed	$meta_value		Metadata value.
	 * @param	mixed	$prev_value		Optional. Previous value to check before removing.
	 * @return	bool	False on failure, true if success.
	 *
	 * @access	public
	 * @since	1.0
	 */
	public function update_meta( $meta_key = '', $meta_value, $prev_value = '' ) {
		return KBS()->customer_meta->update_meta( $this->id, $meta_key, $meta_value, $prev_value );
	} // update_meta

	/**
	 * Remove metadata matching criteria from a customer.
	 *
	 * @param	str		$meta_key		Metadata name.
	 * @param	mixed	$meta_value		Optional. Metadata value.
	 * @return	bool	False for failure. True for success.
	 *
	 * @access	public
	 * @since	1.0
	 */
	public function delete_meta( $meta_key = '', $meta_value = '' ) {
		return KBS()->customer_meta->delete_meta( $this->id, $meta_key, $meta_value );
	} // delete_meta

	/**
	 * Sanitize the data for update/create
	 *
	 * @since	1.0
	 * @param	arr		$data	The data to sanitize
	 * @return	arr		The sanitized data, based off column defaults
	 */
	private function sanitize_columns( $data ) {

		$columns        = $this->db->get_columns();
		$default_values = $this->db->get_column_defaults();

		foreach ( $columns as $key => $type ) {

			// Only sanitize data that we were provided
			if ( ! array_key_exists( $key, $data ) ) {
				continue;
			}

			switch( $type ) {

				case '%s':
					if ( 'email' == $key ) {
						$data[ $key ] = sanitize_email( $data[ $key ] );
					} elseif ( 'notes' == $key ) {
						$data[ $key ] = strip_tags( $data[ $key ] );
					} else {
						$data[ $key ] = sanitize_text_field( $data[ $key ] );
					}
					break;

				case '%d':
					if ( ! is_numeric( $data[ $key ] ) || (int) $data[ $key ] !== absint( $data[ $key ] ) ) {
						$data[ $key ] = $default_values[ $key ];
					} else {
						$data[ $key ] = absint( $data[ $key ] );
					}
					break;

				case '%f':
					// Convert what was given to a float
					$value = floatval( $data[ $key ] );

					if ( ! is_float( $value ) ) {
						$data[$key] = $default_values[$key];
					} else {
						$data[$key] = $value;
					}
					break;

				default:
					$data[$key] = sanitize_text_field( $data[$key] );
					break;

			}

		}

		return $data;
	} // sanitize_columns

} // KBS_Customer
