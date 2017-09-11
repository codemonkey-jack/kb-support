<?php
/**
 * KBS Ticket Class
 *
 * @package		KBS
 * @subpackage	Posts/Tickets
 * @since		1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * KBS_Ticket Class
 *
 * @since	1.0
 */
class KBS_Ticket {
	
	/**
	 * The ticket ID
	 *
	 * @since	1.0
	 * @var		int
	 */
	public $ID     = 0;
	protected $_ID = 0;

	/**
	 * Identify if the ticket is a new one or existing.
	 *
	 * @since	1.0
	 * @var		bool
	 */
	protected $new = false;

	/**
	 * The Ticket number (for use with sequential tickets)
	 *
	 * @since	1.0
	 * @var		str
	 */
	protected $number = '';

	/**
	 * The ticket title
	 *
	 * @since	1.0
	 * @var		int
	 */
	protected $ticket_title = '';

	/**
	 * The ticket content
	 *
	 * @since	1.0
	 * @var		int
	 */
	protected $ticket_content;

	/**
	 * The ticket categories
	 *
	 * @since	1.0
	 * @var		int
	 */
	protected $ticket_category;

	/**
	 * The ticket meta
	 *
	 * @since	1.0
	 * @var		arr
	 */
	private $ticket_meta = array();

	/**
	 * The Unique Ticket Key
	 *
	 * @since	1.0
	 * @var		str
	 */
	protected $key = '';

	/**
	 * The front end form data.
	 *
	 * @since	1.0
	 * @var		arr
	 */
	protected $form_data = array();

	/**
	 * Array of user information
	 *
	 * @since	1.0
	 * @var		arr
	 */
	private $user_info = array();

	/**
	 * The ID of the agent assigned to the ticket
	 *
	 * @since	1.0
	 * @var		int
	 */
	protected $agent_id = 0;

    /**
	 * Array of additional agent IDs assigned to the ticket
	 *
	 * @since	1.1
	 * @var		arr
	 */
	protected $agents = array();

	/**
	 * The ID of the agent who logged the ticket
	 *
	 * @since	1.0
	 * @var		int
	 */
	protected $logged_by = 0;

	/**
	 * The date the ticket was created
	 *
	 * @since	1.0
	 * @var		str
	 */
	protected $date = '';

	/**
	 * The date the ticket was last modified
	 *
	 * @since	1.0
	 * @var		str
	 */
	protected $modified_date = '';

	/**
	 * The date the ticket was marked as 'resolved'
	 *
	 * @since	1.0
	 * @var		str
	 */
	protected $resolved_date = '';

	/**
	 * The status of the ticket
	 *
	 * @since	1.0
	 * @var		str
	 */
	protected $status = 'new';

	/**
	 * When updating, the old status prior to the change
	 *
	 * @since	1.0
	 * @var		str
	 */
	protected $old_status = '';

	/**
	 * The display name of the current ticket status
	 *
	 * @since	1.0
	 * @var		str
	 */
	protected $status_nicename = '';

	/**
	 * The date the ticket was closed
	 *
	 * @since	1.0
	 * @var		str
	 */
	protected $closed_date = false;

	/**
	 * The customer ID associated with the ticket
	 *
	 * @since	1.0
	 * @var		int
	 */
	protected $customer_id = null;

	/**
	 * The company ID associated with the ticket
	 *
	 * @since	1.0
	 * @var		int
	 */
	protected $company_id = 0;

	/**
	 * The User ID (if logged in) that opened the ticket
	 *
	 * @since	1.0
	 * @var		int
	 */
	protected $user_id = 0;

	/**
	 * The first name of the requestor
	 *
	 * @since	1.0
	 * @var		str
	 */
	protected $first_name = '';

	/**
	 * The last name of the requestor
	 *
	 * @since	1.0
	 * @var		str
	 */
	protected $last_name = '';

	/**
	 * The email used to open the ticket
	 *
	 * @since	1.0
	 * @var		str
	 */
	protected $email = '';

	/**
	 * IP Address ticket was opened from
	 *
	 * @since	1.0
	 * @var		str
	 */
	protected $ip = '';

	/**
	 * The source by which the ticket was logged.
	 *
	 * @since	1.0
	 * @var		str
	 */
	protected $source = '';

	/**
	 * Array of items that have changed since the last save() was run.
	 * This is for internal use, to allow fewer update_ticket_meta calls to be run.
	 *
	 * @since	1.0
	 * @var		arr
	 */
	private $pending;

	/**
	 * Sla target response time for this ticket.
	 *
	 * @since	1.0
	 * @var		str
	 */
	protected $sla_respond = '';

	/**
	 * SLA target resolution date/time for this ticket.
	 *
	 * @since	1.0
	 * @var		str
	 */
	protected $sla_resolve = '';

	/**
	 * SLA first response date/time for this ticket.
	 *
	 * @since	1.0
	 * @var		str
	 */
	protected $first_response = '';

	/**
	 * Array of attached file IDs for this ticket.
	 *
	 * @since	1.0
	 * @var		arr
	 */
	protected $files = array();

	/**
	 * Array of new files for this ticket.
	 *
	 * @since	1.0
	 * @var		arr
	 */
	protected $new_files = array();

	/**
	 * Array of replies for this ticket.
	 *
	 * @since	1.0
	 * @var		arr
	 */
	protected $replies = array();

	/**
	 * Array of private notes for this ticket.
	 *
	 * @since	1.0
	 * @var		arr
	 */
	protected $notes = array();

	/**
	 * Setup the KBS_Ticket class
	 *
	 * @since	1.0
	 * @param	int		$ticket_id	A given ticket
	 * @return	mixed	void|false
	 */
	public function __construct( $ticket_id = false ) {
		if ( empty( $ticket_id ) ) {
			return false;
		}

		$this->setup_ticket( $ticket_id );
	} // __construct

	/**
	 * Magic GET function.
	 *
	 * @since	1.0
	 * @param	str		$key	The property
	 * @return	mixed	The value
	 */
	public function __get( $key ) {
		if ( method_exists( $this, 'get_' . $key ) ) {
			$value = call_user_func( array( $this, 'get_' . $key ) );
		} else {
			$value = $this->$key;
		}

		return $value;
	} // __get

	/**
	 * Magic SET function
	 *
	 * Sets up the pending array for the save method.
	 *
	 * @since	1.0
	 * @param	str		$key	The property name
	 * @param	mixed	$value	The value of the property
	 */
	public function __set( $key, $value ) {

		if ( 'status' === $key ) {
			$this->old_status = $this->status;
		}

		if ( 'customer_id' === $key )	{
			if ( empty( $this->company_id ) )	{
				$this->company_id = kbs_get_customer_company_id( $value );
				$this->pending['company_id'] = $this->company_id;
			}
		}

		$this->pending[ $key ] = $value;

		if ( '_ID' !== $key ) {
			$this->$key = $value;
		}
	} // __set

	/**
	 * Magic ISSET function, which allows empty checks on protected elements.
	 *
	 * @since	1.0
	 * @param	str		$name	The attribute to get
	 * @return	bool	If the item is set or not
	 */
	public function __isset( $name ) {
		if ( property_exists( $this, $name) ) {
			return false === empty( $this->$name );
		} else {
			return null;
		}
	} // __isset

	/**
	 * Setup the ticket properties.
	 *
	 * @since	1.0
	 * @param 	int		$ticket_id	The Ticket ID
	 * @return	bool	True if the setup was successful
	 */
	private function setup_ticket( $ticket_id ) {

		$this->pending = array();

		if ( empty( $ticket_id ) ) {
			return false;
		}

		$ticket = get_post( $ticket_id );

		if ( ! $ticket || is_wp_error( $ticket ) ) {
			return false;
		}

		if ( 'kbs_ticket' !== $ticket->post_type ) {
			return false;
		}

		// Extensions can hook here perform actions before the ticket data is loaded
		do_action( 'kbs_pre_setup_ticket', $this, $ticket_id );

		// Primary Identifier
		$this->ID              = absint( $ticket_id );

		// Protected ID that can never be changed
		$this->_ID             = absint( $ticket_id );

		// We have a ticket, get the generic ticket_meta item to reduce calls to it
		$this->ticket_meta     = $this->get_meta();

		// Status and Dates
		$this->date            = $ticket->post_date;
		$this->modified_date   = $ticket->post_modified;
		$this->closed_date     = $this->setup_closed_date();
		$this->status          = $ticket->post_status;
		$this->post_status     = $this->status;

		$all_ticket_statuses   = kbs_get_ticket_statuses();
		$this->status_nicename = array_key_exists( $this->post_status, $all_ticket_statuses ) ? $all_ticket_statuses[ $this->status ] : ucfirst( $this->status );

		// Content & Replies
		$this->ticket_title    = $ticket->post_title;
		$this->ticket_content  = $ticket->post_content;
		$this->replies         = $this->get_replies();
		$this->files           = $this->get_files();

		// User data
		$this->ip              = $this->setup_ip();
		$this->agent_id        = $this->setup_agent_id();
        $this->agents          = $this->setup_agents();
		$this->logged_by       = $this->setup_logged_by();
		$this->customer_id     = $this->setup_customer_id();
		$this->company_id      = $this->setup_company_id();
		$this->user_id         = $this->setup_user_id();
		$this->email           = $this->setup_email();
		$this->user_info       = $this->setup_user_info();
		$this->first_name      = $this->user_info['first_name'];
		$this->last_name       = $this->user_info['last_name'];

		// SLA
		$this->sla_respond     = $this->setup_sla_targets( 'respond' );
		$this->sla_resolve     = $this->setup_sla_targets( 'resolve' );
		$this->first_response  = $this->setup_first_response();

		$this->key             = $this->setup_ticket_key();
		$this->number          = $this->setup_ticket_number();
		$this->form_data       = $this->setup_form_data();

		// Extensions can hook here to add items to this object
		do_action( 'kbs_setup_ticket', $this, $ticket_id );
								
		return true;

	} // setup_ticket

	/**
	 * Create the base of a ticket.
	 *
	 * @since	1.0
	 * @return	int|bool	False on failure, the ticket ID on success.
	 */
	private function insert_ticket() {

		if ( empty( $this->ticket_title ) )	{
			$this->ticket_title = sprintf( __( 'New %s', 'kb-support' ), kbs_get_ticket_label_singular() );
		}

		if ( empty( $this->ip ) ) {
			$this->ip = kbs_get_ip();
			$this->pending['ip'] = $this->ip;
		}

		if ( empty( $this->key ) ) {
			$auth_key  = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
			$this->key = strtolower( md5( $this->email . date( 'Y-m-d H:i:s' ) . $auth_key . uniqid( 'kbs', true ) ) );  // Unique key
			$this->pending['key'] = $this->key;
		}

		if ( ! empty( $this->form_data ) )	{
			$this->pending['form_data'] = $this->form_data;
		}

		$ticket_data = array(
			'date'         => $this->date,
			'agent_id'     => $this->agent_id,
            'agents'       => $this->agents,
			'user_email'   => $this->email,
			'user_info'    => array(
				'id'               => $this->user_id,
				'email'            => $this->email,
				'first_name'       => $this->first_name,
				'last_name'        => isset( $this->last_name )                     ? $this->last_name                     : '',
				'primary_phone'    => isset( $this->user_info['primary_phone'] )    ? $this->user_info['primary_phone']    : '',
				'additional_phone' => isset( $this->user_info['additional_phone'] ) ? $this->user_info['additional_phone'] : '',
				'website'          => isset( $this->user_info['website'] )          ? $this->user_info['website']          : ''
			),
			'company_id'   => $this->company_id,
			'sla_respond'  => $this->sla_respond,
			'sla_resolve'  => $this->sla_resolve,
			'status'       => $this->status,
			'source'       => $this->source,
			'files'        => $this->new_files,
			'form_data'    => $this->form_data
		);

		$args = apply_filters( 'kbs_insert_ticket_args', array(
			'post_status'   => $this->status,
			'post_title'    => $this->ticket_title,
			'post_content'  => $this->ticket_content,
			'post_type'     => 'kbs_ticket',
			'post_date'     => ! empty( $this->date ) ? $this->date : null,
			'post_date_gmt' => ! empty( $this->date ) ? get_gmt_from_date( $this->date ) : null
		), $ticket_data );

		// Create a blank ticket
		$ticket_id = wp_insert_post( $args );

		if ( ! empty( $ticket_id ) ) {

			$this->ID  = $ticket_id;
			$this->_ID = $ticket_id;

			$customer = new stdClass;

			if ( did_action( 'kbs_pre_process_ticket' ) && is_user_logged_in() && ! kbs_is_agent() ) {

				$customer = new KBS_Customer( get_current_user_id(), true );

				// Customer is logged in but used a different email to log ticket with so assign to their customer record.
				if ( ! empty( $customer->id ) && $this->email != $customer->email ) {
					$customer->add_email( $this->email );
				}

			}

			if ( empty( $customer->id ) ) {
				$customer = new KBS_Customer( $this->email );
			}

			if ( empty( $customer->id ) ) {

				$customer_data = array(
					'name'             => $this->first_name . ' ' . $this->last_name,
					'email'            => $this->email,
					'user_id'          => $this->user_id,
					'company_id'       => $this->company_id,
					'primary_phone'    => isset( $this->user_info['primary_phone'] )    ? $this->user_info['primary_phone']    : '',
					'additional_phone' => isset( $this->user_info['additional_phone'] ) ? $this->user_info['additional_phone'] : '',
					'website'          => isset( $this->user_info['website'] )          ? $this->user_info['website']          : ''
				);

				if ( empty( $customer_data['user_id'] ) )	{
					$user = get_user_by( 'email', $this->email );

					if ( $user )	{
						$this->user_id            = $user->ID;
						$customer_data['user_id'] = $this->user_id;
						$this->pending['user_id'] = $this->user_id;
					}
				}

				$customer->create( $customer_data );

			}

			$this->customer_id            = $customer->id;
			$this->company_id             = $customer->company_id;
			$this->pending['customer_id'] = $this->customer_id;
			$this->pending['company_id']  = $this->company_id;
			$customer->attach_ticket( $this->ID );

			if ( ! empty( $this->agent_id ) )	{
				$this->pending['agent_id'] = $this->agent_id;
			}

            if ( ! empty( $this->agents ) )	{
				$this->pending['agents'] = $this->agents;
			}

			if ( ! empty( $this->ticket_category ) )	{
				$this->pending['ticket_category'] = $this->ticket_category;
			}

			if ( ! empty( $this->new_files ) )	{
				$this->pending['files'] = $this->new_files;
			}

			$this->pending['sla_respond'] = $this->sla_respond;
			$this->pending['sla_resolve'] = $this->sla_resolve;

			$this->ticket_meta = apply_filters( 'kbs_ticket_meta', $this->ticket_meta, $ticket_data );

			$this->update_meta( '_kbs_ticket_version_created', KBS_VERSION );
			$this->update_meta( '_ticket_data', $this->ticket_meta );

			if ( kbs_get_option( 'enable_sequential' ) )	{
				$number       = kbs_get_next_ticket_number();
				$this->number = kbs_format_ticket_number( $number );
				$this->update_meta( '_kbs_ticket_number', $this->number );
				update_option( 'kbs_last_ticket_number', $number );
			}

			$this->new = true;
		}

		return $this->ID;

	} // insert_ticket

	/**
	 * Once items have been set, an update is needed to save them to the database.
	 *
	 * @since	1.0
	 * @return	bool	True of the save occurred, false if it failed or wasn't needed
	 */
	public function save() {
		$saved = false;

		if ( empty( $this->ID ) ) {

			$ticket_id = $this->insert_ticket();

			if ( false === $ticket_id ) {
				$saved = false;
			} else {
				$this->ID = $ticket_id;
				$form_id  = 0;
				if ( ! empty( $this->form_data ) && ! empty( $this->form_data['id'] ) )	{
					$form_id = (int) $this->form_data['id'];
				}
				kbs_record_submission_in_log( $ticket_id, $form_id );
			}

		}

		if ( $this->ID !== $this->_ID ) {
			$this->ID = $this->_ID;
		}

		// If we have something pending, let's save it
		if ( ! empty( $this->pending ) ) {

			foreach( $this->pending as $key => $value ) {
				switch( $key ) {
					case 'status':
						$this->update_status( $this->status );
						break;

					case 'ip':
						$this->update_meta( '_kbs_ticket_user_ip', $this->ip );
						break;

					case 'customer_id':
						$this->update_meta( '_kbs_ticket_customer_id', $this->customer_id );
						add_post_meta( $this->ID, '_kbs_ticket_created_by', $this->customer_id, true );
						break;

					case 'company_id':
						$this->update_meta( '_kbs_ticket_company_id', $this->company_id );
						break;

					case 'agent_id':
                        if ( '-1' === $this->agent_id ) {
                            $this->agent_id = 0;
                        }
						$current_agent = $this->get_meta( '_kbs_ticket_agent_id' );
						$this->update_meta( '_kbs_ticket_agent_id', $this->agent_id );
						kbs_record_agent_change_in_log( $this->ID, $this->agent_id, $current_agent );
						break;

                    case 'agents':
                        if ( ! is_array( $this->agents ) )  {
                            $this->agents = array( $this->agents );
                        }

                        if ( in_array( $this->agent_id, $this->agents ) )   {
                            if ( ( $key = array_search( $this->agent_id, $this->agents ) ) !== false ) {
                                unset( $this->agents[ $key ] );
                            }
                        }

                        if ( kbs_multiple_agents() )    {
                            $current_agents = $this->get_meta( '_kbs_ticket_agents' );
                            $this->update_meta( '_kbs_ticket_agents', $this->agents );
                            kbs_record_additional_agents_change_in_log( $ticket_id = 0, $this->agents, $current_agents );
                        }
                        break;

					case 'user_id':
						$this->update_meta( '_kbs_ticket_user_id', $this->user_id );
						break;

					case 'first_name':
						$this->user_info['first_name'] = $this->first_name;
						break;

					case 'last_name':
						$this->user_info['last_name'] = $this->last_name;
						break;

					case 'email':
						$this->update_meta( '_kbs_ticket_user_email', $this->email );
						break;

					case 'key':
						$this->update_meta( '_kbs_ticket_key', $this->key );
						break;

					case 'form_data':
						foreach( $this->form_data as $form_key => $form_value )	{
							$this->update_meta( '_kbs_ticket_form_' . $form_key, $form_value );
						}
						break;

					case 'date':
						$args = array(
							'ID'        => $this->ID,
							'post_date' => $this->date,
							'edit_date' => true,
						);

						wp_update_post( $args );
						break;

					case 'ticket_title':
						$args = array(
							'ID'         => $this->ID,
							'post_title' => $this->ticket_title
						);

						wp_update_post( $args );
						break;

					case 'ticket_content':
						$args = array(
							'ID'           => $this->ID,
							'post_content' => $this->ticket_content
						);

						wp_update_post( $args );
						break;

					case 'ticket_category':
						if ( ! is_array( $this->ticket_category ) )	{
							$this->ticket_category = array( $this->ticket_category );
						}
						$terms = array_map( 'intval', $this->ticket_category );
						wp_set_object_terms( $this->ID, $terms, 'ticket_category' );
						break;

					case 'resolved_date':
						$this->update_meta( '_kbs_ticket_resolved_date', $this->resolved_date );
						break;

					case 'files':
						$this->files = $this->attach_files();
						break;

					case 'sla_respond':
						$this->update_meta( '_kbs_ticket_sla_target_respond', $this->sla_respond );
						break;

					case 'sla_resolve':
						$this->update_meta( '_kbs_ticket_sla_target_resolve', $this->sla_resolve );
						break;

					case 'source':
						$this->update_meta( '_kbs_ticket_source', $this->source );
						break;

					case 'number':
						$this->update_meta( '_kbs_ticket_number', $this->number );
						break;

					default:
						do_action( 'kbs_ticket_save', $this, $key );
						break;
				}
			}

			$customer = new KBS_Customer( $this->customer_id );

			// Increase the customer's ticket stats
			$customer->increase_ticket_count();

			$new_meta = array(
				'agent_id'      => $this->agent_id,
                'agents'        => $this->agents,
				'source'        => $this->source,
				'sla'           => array( 'respond' => $this->sla_respond, 'resolve' => $this->sla_resolve ),
				'user_info'     => is_array( $this->user_info ) ? $this->user_info : array(),
				'user_ip'       => $this->ip,
				'resolved'      => $this->resolved_date,
				'files'         => $this->files
			);

			// Do some merging of user_info before we merge it all
			if ( ! empty( $this->ticket_meta['user_info'] ) ) {
				$new_meta[ 'user_info' ] = array_replace_recursive( $new_meta[ 'user_info' ], $this->ticket_meta[ 'user_info' ] );
			}

			$meta = $this->get_meta();

			if ( empty( $meta ) )	{
				$meta = array();
			}

			$merged_meta = array_merge( $meta, $new_meta );

			// Only save the ticket meta if it's changed
			if ( md5( serialize( $meta ) ) !== md5( serialize( $merged_meta) ) ) {
				$updated     = $this->update_meta( '_ticket_data', $merged_meta );
				if ( false !== $updated ) {
					$saved = true;
				}
			}

			$this->pending = array();
			$saved         = true;
		}

		if ( true === $saved ) {
			$this->setup_ticket( $this->ID );
		}

		return $saved;
	} // save

	/**
	 * Set the ticket status and run any status specific changes necessary.
	 *
	 * @since	1.0
	 * @param	str		$status	The status to set the ticket to
	 * @return	bool	Returns if the status was successfully updated
	 */
	public function update_status( $status = false ) {

		$old_status = ! empty( $this->old_status ) ? $this->old_status : false;

		if ( $old_status === $status ) {
			return false; // Don't permit status changes that aren't changes
		}

		$do_change = apply_filters( 'kbs_should_update_ticket_status', true, $this->ID, $status, $old_status );

		$updated = false;

		if ( $do_change ) {

			do_action( 'kbs_before_ticket_status_change', $this->ID, $status, $old_status );

			$update_fields = array( 'ID' => $this->ID, 'post_status' => $status, 'edit_date' => current_time( 'mysql' ) );
			$update_fields = apply_filters( 'kbs_update_ticket_status_fields', $update_fields );

			$updated = wp_update_post( $update_fields );

			$all_ticket_statuses   = kbs_get_ticket_statuses();
			$this->status_nicename = array_key_exists( $status, $all_ticket_statuses ) ? $all_ticket_statuses[ $status ] : ucfirst( $status );

			// Process any specific status functions
			switch( $status ) {
				case 'open':
					$this->process_open();
					break;
				case 'hold':
					$this->process_on_hold();
					break;
				case 'closed':
					$this->process_closed();
					break;
				default:
					do_action( 'kbs_ticket_status_' . $status, $this->ID, $old_status );
			}

		}

		do_action( 'kbs_update_ticket_status', $this->ID, $status, $old_status );

		return $updated;

	} // update_status

	/**
	 * Retrieve the ID
	 *
	 * @since	1.0
	 * @return	int
	 */
	public function get_ID() {
		return $this->ID;
	} // get_ID
	
	/**
	 * Retrieve the ticket content
	 *
	 * @since	1.0
	 * @return	str
	 */
	public function get_content() {
		$content = apply_filters( 'the_content', $this->ticket_content );
		$content = str_replace( ']]>', ']]&gt;', $content );
		
		return apply_filters( 'kbs_ticket_content', $content );
	} // get_content

	/**
	 * Retrieve the ticket content excerpt.
	 *
	 * Used to display the ticket content on the [kbs_view_ticket] shortcode.
	 *
	 * @since	1.0
	 * @return	str
	 */
	public function get_the_excerpt() {
		if ( has_excerpt( $this->ID ) )	{
			$excerpt = get_post_field( 'post_excerpt', $this->ID );
		} else	{
			$excerpt = get_post_field( 'post_content', $this->ID );
		}

		$more = ' <a class="ticket-toggle-content more">';
			$more .= __( 'Show more&hellip;', 'kb-support' );
		$more .= '</a>';

		$excerpt = wp_trim_words( $excerpt, 15, $more );

		return apply_filters( 'kbs_ticket_excerpt', $excerpt );
	} // get_the_excerpt

	/**
	 * Get a post meta item for the ticket
	 *
	 * @since	1.0
	 * @param	str		$meta_key		The Meta Key
	 * @param	bool	$single			Return single item or array
	 * @return mixed	The value from the post meta
	 */
	public function get_meta( $meta_key = '_ticket_data', $single = true ) {

		$meta = get_post_meta( $this->ID, $meta_key, $single );

		$meta = apply_filters( 'kbs_get_ticket_meta_' . $meta_key, $meta, $this->ID );

		return apply_filters( 'kbs_get_ticket_meta', $meta, $this->ID, $meta_key );
	} // get_meta

	/**
	 * Update the post meta
	 *
	 * @since	1.0
	 * @param	str			$meta_key		The meta key to update
	 * @param	str			$meta_value		The meta value
	 * @param	str			$prev_value		Previous meta value
	 * @return	int|bool	Meta ID if the key didn't exist, true on successful update, false on failure
	 */
	public function update_meta( $meta_key = '', $meta_value = '', $prev_value = '' ) {
		if ( empty( $meta_key ) ) {
			return false;
		}

		if ( 'key' == $meta_key || 'date' == $meta_key ) {

			$current_meta = $this->get_meta();

			if ( empty( $current_meta ) )	{
				$current_meta = array();
			}

			$current_meta[ $meta_key ] = $meta_value;

			$meta_key     = '_ticket_data';
			$meta_value   = $current_meta;

		} elseif ( $meta_key == 'email' || $meta_key == '_kbs_ticket_user_email' ) {

			$meta_value = apply_filters( 'kbs_update_ticket_meta_' . $meta_key, $meta_value, $this->ID );
			update_post_meta( $this->ID, '_kbs_ticket_user_email', $meta_value );

			$current_meta = $this->get_meta();

			if ( empty( $current_meta ) )	{
				$current_meta = array( 'user_info' => array() );
			}

			$current_meta['user_info']['email'] = $meta_value;

			$meta_key     = '_ticket_data';
			$meta_value   = $current_meta;

		}

        if ( empty( $prev_value ) ) {
            $prev_value = $this->get_meta( $meta_key );
        }

		$meta_value = apply_filters( 'kbs_update_ticket_meta_' . $meta_key, $meta_value, $this->ID );

        do_action( 'kbs_update_ticket_meta', $meta_key, $meta_value, $prev_value, $this->ID );

		if ( ! empty( $meta_value ) )	{
			return update_post_meta( $this->ID, $meta_key, $meta_value, $prev_value );
		} else	{
			return delete_post_meta( $this->ID, $meta_key );
		}
	} // update_meta

	/**
	 * When a ticket is set to a status of 'open' process the necessary actions.
	 *
	 * @since	1.0
	 * @access	private
	 * @return	void
	 */
	private function process_open() {
		if ( 'open' == $this->old_status )	{
			return;
		}

		// Add the SLA data
		if ( empty( $this->sla_respond ) )	{
			$this->update_meta( '_kbs_ticket_sla_target_respond', kbs_calculate_sla_target_response() );
		}

		if ( empty( $this->sla_resolve ) )	{
			$this->update_meta( '_kbs_ticket_sla_target_resolve', kbs_calculate_sla_target_resolution() );
		}

		do_action( 'kbs_open_ticket', $this->ID, $this );
	} // process_open

	/**
	 * When a ticket is set to a status of 'hold' process the necessary actions.
	 *
	 * @since	1.0
	 * @access	private
	 * @return	void
	 */
	private function process_on_hold() {
		if ( 'hold' == $this->old_status )	{
			return;
		}
		do_action( 'kbs_hold_ticket', $this->ID, $this );
	} // process_on_hold

	/**
	 * When a ticket is set to a status of 'closed' process the necessary actions.
	 *
	 * @since	1.0
	 * @access	private
	 * @return	void
	 */
	private function process_closed() {
		if ( 'closed' == $this->old_status )	{
			return;
		}

		// Add SLA data
		add_post_meta( $this->ID, '_kbs_ticket_closed_date', current_time( 'mysql' ), true );

		if ( is_admin() )	{
			add_post_meta( $this->ID, '_kbs_ticket_closed_by', get_current_user_id(), true );
		}

		do_action( 'kbs_close_ticket', $this->ID, $this );
	} // process_closed

	/**
	 * Attach files to the ticket.
	 *
	 * @since	1.0
	 * @return	arr		Array of attachment IDs
	 */
	private function attach_files()	{
		if ( empty( $this->new_files ) )	{
			return false;
		}

		$file_ids = array();

		foreach( $this->new_files['name'] as $key => $value )	{
			$file_id = false;

			if ( $this->new_files['name'][ $key ] )	{

				$attachment = array(
					'name'     => $this->new_files['name'][ $key ],
					'type'     => $this->new_files['type'][ $key ],
					'tmp_name' => $this->new_files['tmp_name'][ $key ],
					'error'    => $this->new_files['error'][ $key ],
					'size'     => $this->new_files['size'][ $key ]
				);

				$_FILES = array( 'kbs_ticket_attachments' => $attachment );

				foreach( $_FILES as $attachment => $array )	{
					$file_id = kbs_attach_file_to_ticket( $attachment, $this->ID );

					if ( $file_id )	{
						$file_ids[] = $file_id;
					}
				}

			}
		}

		return $file_ids;
	} // attach_files

	/**
	 * Setup the ticket closed date
	 *
	 * @since	1.0
	 * @return	str		The date the ticket was closed
	 */
	private function setup_closed_date() {
		$ticket = get_post( $this->ID );

		if ( 'closed' != $ticket->post_status ) {
			return false; // This ticket was never closed
		}

		$date = ( $date = $this->get_meta( '_kbs_ticket_closed_date', true ) ) ? $date : $ticket->modified_date;

		return $date;
	} // setup_closed_date

	/**
	 * Retrieve the assigned agent ID.
	 *
	 * @since	1.0
	 * @return	int
	 */
	public function setup_agent_id()	{	
		return $this->get_meta( '_kbs_ticket_agent_id', true );
	} // setup_agent_id

    /**
	 * Retrieve the IDs of additional agents assigned to the ticket.
	 *
	 * @since	1.0
	 * @return	arr
	 */
	public function setup_agents()	{
        if ( ! kbs_multiple_agents() )   {
            return array();
        }

		$agents = $this->get_meta( '_kbs_ticket_agents', true );
        if ( ! $agents )    {
            return array();
        }

        return $agents;
	} // setup_agents

	/**
	 * Retrieve the agent who logged the ticket.
	 *
	 * @since	1.0
	 * @return	int
	 */
	public function setup_logged_by()	{	
		return $this->get_meta( '_kbs_ticket_logged_by', true );
	} // setup_logged_by

	/**
	 * Setup the customer ID
	 *
	 * @since	1.0
	 * @return	int		The Customer ID
	 */
	private function setup_customer_id() {
		$customer_id = $this->get_meta( '_kbs_ticket_customer_id', true );

		return $customer_id;
	} // setup_customer_id

	/**
	 * Setup the company ID
	 *
	 * @since	1.0
	 * @return	int		The Company ID
	 */
	private function setup_company_id() {
		$company_id = $this->get_meta( '_kbs_ticket_company_id', true );

		return $company_id;
	} // setup_company_id

	/**
	 * Setup the User ID associated with the ticket
	 *
	 * @since	1.0
	 * @return	int		The User ID
	 */
	private function setup_user_id() {
		$user_id  = $this->get_meta( '_kbs_ticket_user_id', true );
		$customer = new KBS_Customer( $this->customer_id );

		// Make sure it exists, and that it matches that of the associted customer record
		if ( empty( $user_id ) || ( ! empty( $customer->user_id ) && (int) $user_id !== (int) $customer->user_id ) ) {

			$user_id = $customer->user_id;

			// Backfill the user ID, or reset it to be correct in the event of data corruption
			$this->update_meta( '_kbs_ticket_user_id', $user_id );

		}

		return $user_id;
	} // setup_user_id

	/**
	 * Setup the email address for the ticket
	 *
	 * @since	1.0
	 * @return	str		The email address for the ticket
	 */
	private function setup_email() {
		$email = $this->get_meta( '_kbs_ticket_user_email', true );

		if ( empty( $email ) ) {
			$email = KBS()->customers->get_column( 'email', $this->customer_id );
		}

		return $email;
	} // setup_email

	/**
	 * Setup the user info
	 *
	 * @since	1.0
	 * @return	arr		The user info associated with the ticket
	 */
	private function setup_user_info() {
		$defaults = array(
			'first_name' => $this->first_name,
			'last_name'  => $this->last_name
		);

		$user_info    = isset( $this->ticket_meta['user_info'] ) ? maybe_unserialize( $this->ticket_meta['user_info'] ) : array();
		$user_info    = wp_parse_args( $user_info, $defaults );

		// Ensure email index is in the old user info array
		if ( empty( $user_info['email'] ) ) {
			$user_info['email'] = $this->email;
		}

		if ( empty( $user_info ) ) {
			// Get the customer, but only if it's been created
			$customer = new KBS_Customer( $this->customer_id );

			if ( $customer->id > 0 ) {
				$name = explode( ' ', $customer->name, 2 );
				$user_info = array(
					'first_name'       => $name[0],
					'last_name'        => $name[1],
					'email'            => $customer->email,
					'primary_phone'    => $customer->primary_phone,
					'additional_phone' => $customer->additional_phone,
					'website'          => $customer->website
				);
			}
		} else {
			// Get the customer, but only if it's been created
			$customer = new KBS_Customer( $this->customer_id );
			if ( $customer->id > 0 ) {
				foreach ( $user_info as $key => $value ) {
					if ( ! empty( $value ) ) {
						continue;
					}

					switch( $key ) {
						case 'first_name':
							$name = explode( ' ', $customer->name, 2 );

							$user_info[ $key ] = $name[0];
							break;

						case 'last_name':
							$name      = explode( ' ', $customer->name, 2 );
							$last_name = ! empty( $name[1] ) ? $name[1] : '';

							$user_info[ $key ] = $last_name;
							break;

						case 'email':
							$user_info[ $key ] = $customer->email;
							break;

						case 'primary_phone':
							$user_info[ $key ] = $customer->primary_phone;
							break;

						case 'additional_phone':
							$user_info[ $key ] = $customer->additional_phone;
							break;

						case 'website':
							$user_info[ $key ] = $customer->website;
							break;

					}
				}
			}
		}

		return $user_info;
	} // setup_user_info

	/**
	 * Setup the IP Address for the ticket.
	 *
	 * @since	1.0
	 * @return	str		The IP address for the ticket
	 */
	private function setup_ip() {
		$ip = $this->get_meta( '_kbs_ticket_user_ip', true );
		return $ip;
	} // setup_ip

	/**
	 * Setup the ticket key.
	 *
	 * @since	1.0
	 * @return	str		The Ticket Key
	 */
	private function setup_ticket_key() {
		$key = $this->get_meta( '_kbs_ticket_key', true );

		return $key;
	} // setup_ticket_key

	/**
	 * Setup the ticket number
	 *
	 * @since	1.1
	 * @return	int|str		Integer by default, or string if sequential order numbers is enabled
	 */
	private function setup_ticket_number()	{
		$number = $this->ID;

		if ( kbs_get_option( 'enable_sequential' ) )	{
			$number = $this->get_meta( '_kbs_ticket_number', true );

			if ( ! $number ) {
				$number = $this->ID;
			}
		}

		return $number;
	} // setup_ticket_number

	/**
	 * Setup the SLA target data for the ticket
	 *
	 * @since	1.0
	 * @param	str		$which	Which target to receive. i.e. 'respond' or 'resolve'
	 * @return	arr|bool	The sla data for the ticket
	 */
	private function setup_sla_targets( $which = 'respond' ) {
		$sla = $this->get_meta( '_kbs_ticket_sla_target_' . $which );

		return $sla;
	} // setup_sla_targets

	/**
	 * Setup the SLA first response for the ticket
	 *
	 * @since	1.0
	 * @return	arr|bool	The first response time for the ticket
	 */
	private function setup_first_response() {
		$response = $this->get_meta( '_kbs_ticket_sla_first_respond' );

		return $response;
	} // setup_first_response

	/**
	 * Setup the ticket form data.
	 *
	 * @since	1.0
	 * @return	str		The Ticket Form Data
	 */
	private function setup_form_data() {
		$form_data = array();
		$id        = $this->get_meta( '_kbs_ticket_form_id', true );
		$data      = $this->get_meta( '_kbs_ticket_form_data', true );

		if ( $id && $data )	{
			$form_data = array(
				'id'   => $this->get_meta( '_kbs_ticket_form_id', true ),
				'data' => $this->get_meta( '_kbs_ticket_form_data', true )
			);
		}

		return $form_data;
	} // setup_form_data

	/**
	 * Retrieve ticket number
	 *
	 * @since	1.1
	 * @return	int|str		Ticket number
	 */
	private function get_number() {
		return apply_filters( 'kbs_ticket_number', $this->number, $this->ID, $this );
	} // get_number

	/**
	 * Retrieve the ticket replies
	 *
	 * @since	1.0
	 * @param	arr		$args	Array of get_posts arguments.
	 * @return	obj|false
	 */
	public function get_replies( $args = array() ) {
		$defaults = array(
			'post_type'      => 'kbs_ticket_reply',
			'post_parent'    => $this->ID,
			'post_status'    => 'publish',
			'posts_per_page' => -1
		);

		$args = wp_parse_args( $args, $defaults );

		$replies = get_posts( $args );
		
		return apply_filters( 'kbs_ticket_replies', $replies );
	} // get_replies

	/**
	 * Retrieve ticket reply count.
	 *
	 * @since	1.0
	 * @return	int		Ticket reply count
	 */
	public function get_reply_count()	{
		global $wpdb;

		$reply_count = $wpdb->get_var( $wpdb->prepare(
		"
			SELECT count(*)
			FROM $wpdb->posts
			WHERE post_status = %s
			AND post_type = %s
			AND post_parent = %d
		",
		'publish',
		'kbs_ticket_reply',
		$this->ID
	) );

	return $reply_count;

	} // get_reply_count

	/**
	 * Retrieve the tickets attached files.
	 *
	 * @since	1.0
	 * @return	obj|bool
	 */
	public function get_files() {
		$files = kbs_ticket_has_files( $this->ID );

		if ( ! $files )	{
			return false;
		}
		
		return $files;
	} // get_files

	/**
	 * Retrieve the target response time.
	 *
	 * @since	1.0
	 * @return	int
	 */
	public function get_target_respond() {
		if ( empty( $this->sla_respond ) )	{
			return false;
		}

		$respond = date_i18n( get_option( 'time_format' ) . ' ' . get_option( 'date_format' ), strtotime( $this->sla_respond ) );

		return apply_filters( 'kbs_get_target_respond', $respond );
	} // get_target_respond

	/**
	 * Retrieve the actual first response time.
	 *
	 * @since	1.0
	 * @return	int
	 */
	public function get_first_response() {
		if ( empty( $this->first_response ) )	{
			return false;
		}

		$respond = date_i18n( get_option( 'time_format' ) . ' ' . get_option( 'date_format' ), strtotime( $this->first_response ) );

		return apply_filters( 'kbs_get_target_respond', $respond );
	} // get_target_respond

	/**
	 * Retrieve the target resolution time.
	 *
	 * @since	1.0
	 * @return	int
	 */
	public function get_target_resolve() {
		if ( empty( $this->sla_resolve ) )	{
			return false;
		}

		$resolve = date_i18n( get_option( 'time_format' ) . ' ' . get_option( 'date_format' ), strtotime( $this->sla_resolve ) );

		return apply_filters( 'kbs_get_target_resolve', $resolve );
	} // get_target_resolve

	/**
	 * Retrieve the target resolution time.
	 *
	 * @since	1.0
	 * @param	str	$target		'respond' or 'resolve'
	 * @return	int
	 */
	public function get_sla_remain( $target = 'respond' ) {
		$now = current_time( 'timestamp' );

		if ( $target == 'resolve' )	{
			$end = strtotime( $this->get_target_resolve() );
		} else	{
			$end = strtotime( $this->get_target_respond() );
		}

		$diff = human_time_diff( $end, $now );

		if ( $now > $end )	{
			$diff .= ' ' . __( 'ago', 'kb-support' );
		}

		return apply_filters( 'kbs_get_sla_remain', $diff );
	} // get_sla_remain

	/**
	 * Retrieve the source used for logging the ticket.
	 *
	 * @since	1.0
	 * @return	str
	 */
	public function get_source() {
		$sources = kbs_get_ticket_log_sources();

		$ticket_source = $this->ticket_meta['source'];

		if ( array_key_exists( $ticket_source, $sources ) )	{
			$return = $sources[ $ticket_source ];
		} else	{
			$return = __( 'Source could not be found', 'kb-support' );
		}

		return apply_filters( 'kbs_get_source', $return );
	} // get_source

	/**
	 * Add a note to a ticket.
	 *
	 * @since	1.0
	 * @param	str		$note	The note to add
	 * @return	int		The ID of the note added
	 */
	public function add_note( $note = false ) {
		// Return if no note specified
		if ( empty( $note ) ) {
			return false;
		}

		return kbs_insert_note( $this->ID, $note );
	} // add_note

	/**
	 * Delete a note from a ticket.
	 *
	 * @since	1.0
	 * @param	int		$note_id	The ID of the note to delete
	 * @return	bool	True if deleted, or false
	 */
	public function delete_note( $note_id = 0 ) {
		// Return if no note specified
		if ( empty( $note_id ) ) {
			return false;
		}

		return kbs_delete_note( $note_id, $this->ID );
	} // delete_note

	/**
	 * Add a reply to a ticket.
	 *
	 * @since	1.0
	 * @param	arr			$reply_data	The reply data
	 * @return	int|false	The reply ID on success, or false on failure
	 */
	public function add_reply( $reply_data = array() ) {

		// Return if no reply data
		if ( empty( $reply_data ) || empty( $reply_data['response'] ) || empty( $reply_data['ticket_id'] ) )	{
			return false;
		}

		$close = ! empty( $reply_data['close'] ) ? isset( $reply_data['close'] ) : false;

		do_action( 'kbs_pre_reply_to_ticket', $reply_data, $this );

		$args = array(
			'post_type'    => 'kbs_ticket_reply',
			'post_status'  => 'publish',
			'post_content' => $reply_data['response'],
			'post_parent'  => $reply_data['ticket_id'],
			'post_author'  => ! empty( $reply_data['author'] ) ? (int) $reply_data['author'] : get_current_user_id(),
			'meta_input'   => array()
		);

		if ( isset( $reply_data['customer_id'] ) )	{
			$args['meta_input']['_kbs_reply_customer_id'] = $reply_data['customer_id'];
		}

		if ( isset( $reply_data['agent_id'] ) )	{
			$args['meta_input']['_kbs_reply_agent_id'] = $reply_data['agent_id'];
		}

		if ( $close )	{
			$args['meta_input']['_kbs_reply_resolution'] = true;
		}

        /*
         * Allow developers to filter the reply args
         *
         * @since   1.1
         */
        $args = apply_filters( 'kbs_ticket_add_reply_args', $args, $reply_data );

		$reply_id = wp_insert_post( $args );

		if ( empty( $reply_id ) )	{
			return false;
		}

		if ( $close )	{
			$this->update_status( 'closed' );
			$this->update_meta( '_kbs_resolution_id', $reply_id );
		} else	{
			wp_update_post( array( 'ID' => $reply_data['ticket_id'] ) );
		}

		do_action( 'kbs_reply_to_ticket', $this->ID, $reply_id, $reply_data, $this );

		return $reply_id;

	} // add_reply

	public function show_form_data()	{
		if ( empty( $this->form_data ) )	{
			return;
		}

		$form = new KBS_Form( $this->form_data['id'] );

		$ignore = kbs_form_ignore_fields();

		$output = '<h2>' . sprintf( __( 'Form: %s', 'kb-support' ), get_the_title( $this->form_data['id'] ) ) . '</h2>';
		foreach( $this->form_data['data'] as $field => $value )	{

			$form_field = kbs_get_field_by( 'name', $field );

			if ( empty( $form_field ) )	{
				continue;
			}

			$settings = $form->get_field_settings( $form_field->ID );

			if ( 'post_category' == $settings['mapping'] )	{
				$value = is_array( $value ) ? $value : array( $value );
				$cats  = array();
				foreach( $value as $category )	{
					$term = get_term( $category );
					if ( $term )	{
						$cats[] = $term->name;
					} else	{
						$cats[] = sprintf( __( 'Term %s no longer exists', 'kb-support' ), $category );
					}
				}
				$value = $cats;
			}

			if ( is_array( $value ) )	{
				$value = implode( ', ', $value );
			}

			$value = apply_filters( 'kbs_show_form_data', $value, $form_field->ID, $settings );

			$output .= '<p><strong>' . get_the_title( $form_field->ID ) . '</strong>: ' . $value;
		}

		return apply_filters( 'kbs_show_form_data', $output );
	} // show_form_data

} // KBS_Ticket
