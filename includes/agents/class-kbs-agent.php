<?php
/**
 * Agent Object
 *
 * @package     KBS
 * @subpackage  Classes/Agent
 * @copyright   Copyright (c) 2017, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.2.5
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * KBS_Agent Class
 *
 * @since       1.2.5
 */
class KBS_Agent {

	/**
	 * The agent ID
	 *
	 * @since       1.2.5
	 */
	public $id = 0;

    /**
     * Whether or not the user is a KBS Agent
     *
     * @since   1.2.5
     * @var     bool
     */
    public $is_agent = false;

	/**
	 * The agent's open ticket count
	 *
	 * @since       1.2.5
	 */
	public $open_tickets = 0;

	/**
	 * The agent's username
	 *
	 * @since       1.5
	 */
	public $username;

	/**
	 * The agent's first name
	 *
	 * @since       1.5
	 */
	public $first_name;

	/**
	 * The agent's last name
	 *
	 * @since       1.5
	 */
	public $last_name;

	/**
	 * The agent's email
	 *
	 * @since       1.2.5
	 */
	public $email;

	/**
	 * The agent's name
	 *
	 * @since       1.2.5
	 */
	public $name;

    /**
     * Agent data
     *
     * @since   1.2.5
     * @var     Array
     */
    public $data;

    /**
     * Agent status
     *
     * @since   1.2.5
     * @var     string
     */
    public $status = 'offline';

    /**
     * Whether or not an agent is currently online
     *
     * @since   1.2.5
     * @var     string
     */
    public $is_online = false;

	/**
	 * Get things going
	 *
	 * @since   1.2.5
     * @param   mixed   $_id_email_or_object    WP user ID, email address or WP_User object
	 */
	public function __construct( $_id_email_or_object = false ) {

		if ( false === $_id_email_or_object ) {
			return false;
		}

        if ( is_numeric( $_id_email_or_object ) && (int) $_id_email_or_object !== absint( $_id_email_or_object ) )   {
            return false;
        }

        $agent = false;
        $field = 'id';

		if ( is_email( $_id_email_or_object ) ) {
			$field = 'email';
		} elseif ( is_object( $_id_email_or_object ) )    {
            $agent = $_id_email_or_object;
        }

        if ( ! $agent ) {
            $agent = get_user_by( $field, $_id_email_or_object );
        }

		if ( ! empty( $agent ) && is_object( $agent ) ) {
			return $this->setup_agent( $agent );
		}

		return false;

	} // __construct

	/**
	 * Given the agent data, let's set the variables
	 *
	 * @since       1.2.5
	 * @param	obj		$agent	The Agent Object
	 * @return 	bool	If the setup was successful or not
	 */
	private function setup_agent( $agent ) {

		$defaults = $this->data_defaults();

		foreach ( $defaults as $field => $value ) {

			if ( isset ( $agent->{$field} ) ) {
				$defaults[ $field ] = $agent->{$field};
			}

		}

        $this->id = $defaults['ID'];

		if ( ! empty( $this->id ) ) {
            $this->is_agent = kbs_is_agent( $this->id );

            if ( $this->is_agent )  {
				$this->username     = $defaults['user_login'];
                $this->email        = $defaults['user_email'];
				$this->first_name   = $defaults['first_name'];
				$this->last_name    = $defaults['last_name'];
                $this->name         = $defaults['display_name'];
                $this->open_tickets = $this->count_open_tickets();
                $this->data         = $defaults;

                $this->setup_status();
            }

			return $this->is_agent;
		}

		return false;

	} // setup_agent

    /**
	 * Get the default profile data fields
	 *
	 * @since  1.2.5
	 * @return array   Array of default user data fields
	 */
	protected function data_defaults() {

		$defaults = apply_filters( 'kbs_agent_data_defaults', array(
			'ID'              => '',
			'user_login'      => '',
			'first_name'      => '',
			'last_name'       => '',
			'user_nicename'   => '',
			'user_email'      => '',
			'user_url'        => '',
			'user_registered' => '',
			'display_name'    => '',
		) );

        $defaults = apply_filters( 'kbs_agent_data_defaults', $defaults );

        return $defaults;
	} // data_defaults

	/**
	 * Magic __get function to dispatch a call to retrieve a private property
	 *
	 * @since       1.2.5
	 */
	public function __get( $key ) {

		if ( method_exists( $this, 'get_' . $key ) ) {
			return call_user_func( array( $this, 'get_' . $key ) );
		} else {
			return new WP_Error( 'kbs-agent-invalid-property', sprintf( __( "Can't get property %s", 'kb-support' ), $key ) );
		}

	} // __get

    /**
     * Setup the agent status
     *
     * @since   1.2.5
     * @return  void
     */
    private function setup_status() {
        $this->is_online = kbs_agent_is_online( $this->id );
        $this->status    = $this->is_online ? 'online' : 'offline';
    } // setup_status

    /**
     * Retrieve the statuses to treat as open tickets.
     *
     * @since   1.2.5
     * @return  array   Array of ticket statuses
     */
    public function get_open_statuses()    {
        $open_statuses = array( 'open' );
        $open_statuses = apply_filters( 'kbs_agent_ticket_open_statuses', $open_statuses );

        return $open_statuses;
    } // get_open_statuses

    /**
     * Retrieve open tickets for the agent.
     *
     * @since   1.2.5
     * @return  array   Array of ticket objects
     */
    public function get_open_tickets()   {
        $args = array(
            'status' => $this->get_open_statuses(),
            'agent'  => $this->id
        );

        $args = apply_filters( 'kbs_get_agent_open_tickets', $args, $this->id, $this );

        return kbs_get_tickets( $args );
    } // get_open_tickets

    /**
     * Retrieve the open ticket count for the agent.
     *
     * @since   1.2.5
     * @return  bool    Open ticket count
     */
    public function count_open_tickets()    {
        $count = get_user_option( 'kbs_open_tickets', $this->id );

		if ( false === $count ) {
			$count = count( $this->get_open_tickets() );
			update_user_option( $this->id, 'kbs_open_tickets', $count );
		}

		return $count;
    } // count_open_tickets

	/**
	 * Increase the ticket count of a agent
	 *
	 * @since       1.2.5
	 * @param	int	$count	The number to imcrement by
	 * @return	int	The ticket count
	 */
	public function increase_open_tickets( $count = 1 ) {

        $count     = absint( $count );
		$new_total = (int) $this->open_tickets + (int) $count;

		do_action( 'kbs_agent_pre_increase_open_tickets', $count, $this->id );

		if ( update_user_option( $this->id, 'kbs_open_tickets', $new_total ) ) {
			$this->open_tickets = $new_total;
		}

		do_action( 'kbs_agent_post_increase_open_tickets', $this->open_tickets, $count, $this->id );

		return $this->open_tickets;
	} // increase_open_tickets

	/**
	 * Decrease the agent ticket count
	 *
	 * @since       1.2.5
	 * @param	int		$count The amount to decrease by
	 * @return	mixed	If successful, the new count, otherwise false
	 */
	public function decrease_open_tickets( $count = 1 ) {

		$count     = absint( $count );
		$new_total = (int) $this->open_tickets - (int) $count;

		if( $new_total < 0 ) {
			$new_total = 0;
		}

		do_action( 'kbs_agent_pre_decrease_open_tickets', $count, $this->id );

		if ( update_user_option( $this->id, 'kbs_open_tickets', $new_total ) ) {
			$this->open_tickets = $new_total;
		}

		do_action( 'kbs_agent_post_decrease_open_tickets', $this->open_tickets, $count, $this->id );

		return $this->open_tickets;
	} // decrease_open_tickets

	/**
	 * Retrieve agent meta field for a agent.
	 *
	 * @param	str		$meta_key	The meta key to retrieve.
	 * @param	bool	$single		Whether to return a single value.
	 * @return	mixed	Will be an array if $single is false. Will be value of meta data field if $single is true.
	 *
	 * @access	public
	 * @since       1.2.5
	 */
	public function get_meta( $meta_key = '', $single = true ) {
		return get_user_meta( $this->id, $meta_key, $single );
	} // get_meta

	/**
	 * Add meta data field to a agent.
	 *
	 * @param	str		$meta_key		Metadata name.
	 * @param	mixed	$meta_value		Metadata value.
	 * @param	bool	$unique			Optional, default is false. Whether the same key should not be added.
	 * @return	bool	False for failure. True for success.
	 *
	 * @access	public
	 * @since       1.2.5
	 */
	public function add_meta( $meta_key, $meta_value, $unique = false ) {

		if ( empty( $meta_key ) || empty( $meta_value ) ) {
			return false;
		}
		return add_user_meta( $this->id, $meta_key, $meta_value, $unique );
	} // add_meta

	/**
	 * Update agent meta field based on agent ID.
	 *
	 * @param	str		$meta_key		Metadata key.
	 * @param	mixed	$meta_value		Metadata value.
	 * @param	mixed	$prev_value		Optional. Previous value to check before removing.
	 * @return	bool	False on failure, true if success.
	 *
	 * @access	public
	 * @since       1.2.5
	 */
	public function update_meta( $meta_key, $meta_value, $prev_value = '' ) {

		if ( empty( $meta_key ) || empty( $meta_value ) ) {
			return false;
		}
		return update_user_meta( $this->id, $meta_key, $meta_value, $prev_value );
	} // update_meta

	/**
	 * Remove metadata matching criteria from a agent.
	 *
	 * @param	str		$meta_key		Metadata name.
	 * @param	mixed	$meta_value		Optional. Metadata value.
	 * @return	bool	False for failure. True for success.
	 *
	 * @access	public
	 * @since       1.2.5
	 */
	public function delete_meta( $meta_key = '', $meta_value = '' ) {
		return delete_user_meta( $this->id, $meta_key, $meta_value );
	} // delete_meta

} // KBS_Agent
