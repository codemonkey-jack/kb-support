<?php
/**
 * KB Support REST API
 *
 * @package     KBS
 * @subpackage  Classes/Tickets REST API
 * @copyright   Copyright (c) 2020, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.5
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * KBS_Tickets_API Class
 *
 * @since	1.5
 */
class KBS_Tickets_API extends KBS_API {

	/**
	 * Post type.
	 *
	 * @since	1.5
	 * @var		string
	 */
	protected $post_type;

	/**
	 * Instance of a post meta fields object.
	 *
	 * @since	1.5
	 * @var		WP_REST_Post_Meta_Fields
	 */
	protected $meta;

	/**
	 * Get things going
	 *
	 * @since	1.5
	 */
	public function __construct()	{
        $this->post_type = 'kbs_ticket';
		$obj             = get_post_type_object( $this->post_type );
		$this->rest_base = ! empty( $obj->rest_base ) ? $obj->rest_base : $obj->name;

		$this->meta = new WP_REST_Post_Meta_Fields( $this->post_type );
	} // __construct

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since	4.5
	 * @see		register_rest_route()
	 */
    public function register_routes()    {
        register_rest_route(
			$this->namespace . $this->version,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				)
			)
		);

		register_rest_route(
			$this->namespace . $this->version,
			'/' . $this->rest_base . '/(?P<id>\d+)',
			array(
				'args'   => array(
					'id' => array(
						'type'        => 'integer',
						'description' => sprintf(
							esc_html__( 'Unique identifier for the %s.', 'kb-support' ),
							kbs_get_ticket_label_singular( true )
						)
					)
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' )
				),
                array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				)
			)
		);

        register_rest_route(
			$this->namespace . $this->version,
			'/' . $this->rest_base . '/(?P<number>([a-zA-Z0-9]+))',
			array(
				'args'   => array(
					'number' => array(
						'type'        => 'string',
						'description' => sprintf(
							esc_html__( 'Unique identifier for the %s.', 'kb-support' ),
							kbs_get_ticket_label_singular( true )
						)
					)
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' )
				)
			)
		);

    } // register_routes

	/**
     * Checks if a given request has access to read a ticket.
     *
     * @since   1.5
     * @param	WP_REST_Request	$request	Full details about the request.
	 * @return	bool|WP_Error	True if the request has read access for the item, WP_Error object otherwise.
     */
    public function get_item_permissions_check( $request ) {
		if ( ! $this->is_authenticated() )	{
			return new WP_Error(
				'rest_forbidden_context',
				$this->errors( 'no_auth' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		$ticket_id = isset( $request['id'] ) ? $request['id'] : $this->get_ticket_id_by_number( $request );

        $post = $this->get_post( $ticket_id );

        if ( is_wp_error( $post ) ) {
			return $post;
		}

        if ( 'edit' === $request['context'] && $post && ! $this->check_update_permission( $post ) ) {
			return new WP_Error(
				'rest_forbidden_context',
				sprintf(
                    esc_html__( 'Sorry, you are not allowed to edit this %s.', 'kb-support' ),
                    kbs_get_ticket_label_singular( true )
                ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( $post ) {
			return $this->check_read_permission( $post );
		}

		return true;
    } // get_item_permissions_check

    /**
     * Checks if a given request has access to read multiple tickets.
     *
     * @since   1.5
     * @param	WP_REST_Request	$request	Full details about the request.
	 * @return	bool|WP_Error	True if the request has read access for the item, WP_Error object otherwise.
     */
    public function get_items_permissions_check( $request ) {
        if ( ! $this->is_authenticated() )	{
			return new WP_Error(
				'rest_forbidden_context',
				$this->errors( 'no_auth' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

        $post_type = get_post_type_object( $this->post_type );

		if ( 'edit' === $request['context'] && ! current_user_can( $post_type->cap->edit_posts ) ) {
			return new WP_Error(
				'rest_forbidden_context',
				esc_html__( 'Sorry, you are not allowed to edit posts in this post type.', 'kb-support' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
    } // get_items_permissions_check

	/**
	 * Checks if a given request has access to create a ticket.
	 *
	 * @since  1.5
	 *
	 * @param  WP_REST_Request $request    Full details about the request.
	 * @return true|WP_Error   True if the request has access to create the item, WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ) {
		$create = $this->is_authenticated();
        $create = apply_filters( "kbs_rest_{$this->post_type}_create", $create, $request, $this );

		if ( ! $create )	{
			return new WP_Error(
				'rest_forbidden_context',
				$this->errors( 'no_auth' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
    } // create_item_permissions_check

    /**
	 * Checks if a given request has access to update a ticket.
	 *
	 * @since  1.5
	 *
	 * @param  WP_REST_Request $request    Full details about the request.
	 * @return true|WP_Error   True if the request has access to update the item, WP_Error object otherwise.
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! $this->is_authenticated() )	{
			return new WP_Error(
				'rest_forbidden_context',
				$this->errors( 'no_auth' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

        $post = $this->get_post( $request['id'] );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$post_type = get_post_type_object( $this->post_type );

		if ( $post && ! $this->check_update_permission( $post ) ) {
			return new WP_Error(
				'rest_cannot_edit',
				sprintf(
                    esc_html__( 'Sorry, you are not allowed to edit this %s.', 'kb-support' ),
                    kbs_get_ticket_label_singular( true )
                ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

        return true;
    } // update_item_permissions_check

	/**
	 * Retrieves a single ticket.
	 *
	 * @since	1.5
	 * @param	WP_REST_Request	$request	Full details about the request
	 * @return	WP_REST_Response|WP_Error	Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$ticket_id = isset( $request['id'] ) ? $request['id'] : $this->get_ticket_id_by_number( $request );

		$post = $this->get_post( absint( $ticket_id ) );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$data     = $this->prepare_item_for_response( $post, $request );
		$response = rest_ensure_response( $data );

		return $response;
	} // get_item

	/**
	 * Get ticket ID by number.
	 *
	 * @since	1.5
	 * @param	WP_REST_Request	$request	Full details about the request
	 * @return	object	KBS_Ticket object or false
	 */
	public function get_ticket_id_by_number( $request )	{
		global $wpdb;

		$ticket_id = $wpdb->get_var( $wpdb->prepare(
			"
			SELECT post_id
			FROM $wpdb->postmeta
			WHERE meta_key = %s
			AND meta_value = %s
			LIMIT 1
			",
			'_kbs_ticket_number',
			trim( $request['number'] )
		) );

		return $ticket_id;
	} // get_ticket_id_by_number

	/**
	 * Retrieves a collection of tickets.
	 *
	 * @since	1.5
	 * @param	WP_REST_Request		$request	Full details about the request
	 * @return	WP_REST_Response|WP_Error		Response object on success, or WP_Error object on failure
	 */
	function get_items( $request )	{
        // Ensure a search string is set in case the orderby is set to 'relevance'.
		if ( ! empty( $request['orderby'] ) && 'relevance' === $request['orderby'] && empty( $request['search'] ) ) {
			return new WP_Error(
				'rest_no_search_term_defined',
				esc_html__( 'You need to define a search term to order by relevance.', 'kb-support' ),
				array( 'status' => 400 )
			);
		}

		// Ensure an include parameter is set in case the orderby is set to 'include'.
		if ( ! empty( $request['orderby'] ) && 'include' === $request['orderby'] && empty( $request['include'] ) ) {
			return new WP_Error(
				'rest_orderby_include_missing_include',
				esc_html__( 'You need to define an include parameter to order by include.', 'kb-support' ),
				array( 'status' => 400 )
			);
		}

		// Retrieve the list of registered collection query parameters.
		$registered = $this->get_collection_params();
		$args       = array();
        $meta_query = array();

        /*
		 * This array defines mappings between public API query parameters whose
		 * values are accepted as-passed, and their internal WP_Query parameter
		 * name equivalents (some are the same). Only values which are also
		 * present in $registered will be set.
		 */
		$parameter_mappings = array(
			'exclude'        => 'post__not_in',
			'include'        => 'post__in',
			'offset'         => 'offset',
			'order'          => 'order',
			'orderby'        => 'orderby',
			'page'           => 'paged',
			'search'         => 's',
			'slug'           => 'post_name__in',
			'status'         => 'post_status'
		);

		/*
		 * For each known parameter which is both registered and present in the request,
		 * set the parameter's value on the query $args.
		 */
		foreach ( $parameter_mappings as $api_param => $wp_param ) {
			if ( isset( $registered[ $api_param ], $request[ $api_param ] ) ) {
				$args[ $wp_param ] = $request[ $api_param ];
			}
		}

        // Check for & assign any parameters which require special handling or setting.
		$args['date_query'] = array();

		// Set before into date query. Date query must be specified as an array of an array.
		if ( isset( $registered['before'], $request['before'] ) ) {
			$args['date_query'][0]['before'] = $request['before'];
		}

		// Set after into date query. Date query must be specified as an array of an array.
		if ( isset( $registered['after'], $request['after'] ) ) {
			$args['date_query'][0]['after'] = $request['after'];
		}

        // Ensure our per_page parameter overrides any provided posts_per_page filter.
		if ( isset( $registered['per_page'] ) ) {
			$args['posts_per_page'] = $request['per_page'];
		}

        // By a customer's WordPress user ID
        if ( isset( $registered['user'], $request['user'] ) ) {
            $meta_query[] = array(
                'key'   => '_kbs_ticket_user_id',
                'value' => (int) $request['user'],
                'type'  => 'NUMERIC'
            );
        }

        // By a customer's ID
        if ( isset( $registered['customer'], $request['customer'] ) ) {
            $meta_query[] = array(
                'key'   => '_kbs_ticket_customer_id',
                'value' => (int) $request['customer'],
                'type'  => 'NUMERIC'
            );
        }

        // By a company ID
        if ( isset( $registered['company'], $request['company'] ) ) {
            $meta_query[] = array(
                'key'   => '_kbs_ticket_company_id',
                'value' => (int) $request['company'],
                'type'  => 'NUMERIC'
            );
        }

        // By an agent ID
        if ( isset( $registered['agent'], $request['agent'] ) ) {
            $meta_query[] = array(
                'key'   => '_kbs_ticket_agent_id',
                'value' => (int) $request['agent'],
                'type'  => 'NUMERIC'
            );
        }

		// Force the post_type argument, since it's not a user input variable.
		$args['post_type'] = $this->post_type;

        if ( ! empty( $meta_query ) )   {
            $args['meta_query'] = $meta_query;
        }

		/**
		 * Filters the query arguments for a request.
		 *
		 * Enables adding extra arguments or setting defaults for a ticket collection request.
		 *
		 * @since	1.5
		 * @param	array			$args		Key value array of query var to query value
		 * @param	WP_REST_Request	$request	The request used
		 */
		$args          = apply_filters( "kbs_rest_{$this->post_type}_query", $args, $request );
		$query_args    = $this->prepare_items_query( $args, $request );

        $taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );

		if ( ! empty( $request['tax_relation'] ) ) {
			$query_args['tax_query'] = array( 'relation' => $request['tax_relation'] );
		}

		foreach ( $taxonomies as $taxonomy ) {
			$base        = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;
			$tax_exclude = $base . '_exclude';

			if ( ! empty( $request[ $base ] ) ) {
				$query_args['tax_query'][] = array(
					'taxonomy'         => $taxonomy->name,
					'field'            => 'term_id',
					'terms'            => $request[ $base ],
					'include_children' => false,
				);
			}

			if ( ! empty( $request[ $tax_exclude ] ) ) {
				$query_args['tax_query'][] = array(
					'taxonomy'         => $taxonomy->name,
					'field'            => 'term_id',
					'terms'            => $request[ $tax_exclude ],
					'include_children' => false,
					'operator'         => 'NOT IN',
				);
			}
		}

		$posts_query  = new WP_Query();
		$query_result = $posts_query->query( $query_args );
        $posts        = array();

		foreach ( $query_result as $post ) {
			if ( ! $this->check_read_permission( $post ) ) {
				continue;
			}

			$data    = $this->prepare_item_for_response( $post, $request );
			$posts[] = $this->prepare_response_for_collection( $data );
		}

        $page        = (int) $query_args['paged'];
		$total_posts = $posts_query->found_posts;

		if ( $total_posts < 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			unset( $query_args['paged'] );

			$count_query = new WP_Query();
			$count_query->query( $query_args );
			$total_posts = $count_query->found_posts;
		}

		$max_pages = ceil( $total_posts / (int) $posts_query->query_vars['posts_per_page'] );

		if ( $page > $max_pages && $total_posts > 0 ) {
			return new WP_Error(
				'rest_post_invalid_page_number',
				esc_html__( 'The page number requested is larger than the number of pages available.', 'kb-support' ),
				array( 'status' => 400 )
			);
		}

		$response = rest_ensure_response( $posts );

		$response->header( 'X-WP-Total', (int) $total_posts );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$request_params = $request->get_query_params();
		$base           = add_query_arg( urlencode_deep( $request_params ), rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ) );

		if ( $page > 1 ) {
			$prev_page = $page - 1;

			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}

			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}
		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );

			$response->link_header( 'next', $next_link );
		}

		return $response;
	} // get_items

	/**
	 * Creates a ticket.
	 *
	 * @since  1.5
	 *
	 * @param  WP_REST_Request             $request    Full details about the request.
	 * @return WP_REST_Response|WP_Error   Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		$ticket_data = array(
			'user_info'        => array(),
			'attachments'      => array(),
			'source'           => 'kbs-rest',
			'privacy_accepted' => false,
			'terms_agreed'     => false
		);

		foreach( $this->get_required_fields() as $required_field )	{
			if ( empty( $request[ $required_field ] ) )	{
				return new WP_Error(
                    'required_fields',
                    $this->errors( 'required_fields' ),
                    array( 'status' => 500 )
                );
			}

			if ( 'customer_email' == $required_field )	{
				$email = $request[ $required_field ];

				if ( ! is_email( $email ) || kbs_check_email_from_submission( $email ) )	{
					return new WP_Error(
						'invalid_email',
						$this->errors( 'invalid_email' ),
						array( 'status' => 500 )
					);
				}
			}
		}

		$ticket_data['user_info']['email']      = strtolower( $email );
		$ticket_data['user_email']              = $ticket_data['user_info']['email'];
		$ticket_data['user_info']['first_name'] = ucfirst( $request[ 'customer_first' ] );
		$ticket_data['user_info']['last_name']  = ucfirst( $request[ 'customer_last' ] );
		$ticket_data['post_title']              = ucfirst( $request[ 'ticket_title' ] );
		$ticket_data['post_content']            = ucfirst( $request[ 'ticket_content' ] );

		if ( ! empty( $request['customer_phone1'] ) )	{
			$ticket_data['user_info']['primary_phone'] = $request['customer_phone1'];
		}

		if ( ! empty( $request['customer_phone1'] ) )	{
			$ticket_data['user_info']['additional_phone'] = $request['customer_phone2'];
		}

		if ( ! empty( $request['customer_website'] ) )	{
			$ticket_data['user_info']['website'] = $request['customer_website'];
		}

        if ( ! empty( $request['agent_id'] ) )	{
            $agent_id = 0;

            if ( is_email( $request['agent_id'] ) ) {
                $agent = get_user_by( 'email', $request['agent_id'] );

                if ( $agent )   {
                    $agent_id = $agent->ID;
                }
            } else  {
                $agent_id = $request['agent_id'];
            }

            if ( kbs_is_agent( $agent_id ) )    {
                $ticket_data['agent_id'] = $agent_id;
            }
		}

        if ( ! empty( $request['post_category'] ) ) {
            $ticket_data['post_category'] = $request['post_category'];
        }

        if ( ! empty( $request['department'] ) ) {
            $ticket_data['department'] = $request['department'];
        }

		$ticket_id = kbs_add_ticket( $ticket_data );

		if ( ! $ticket_id )	{
			return new WP_Error(
				'create_failed',
				$this->errors( 'create_failed' ),
				array( 'status' => 500 )
			);
		}

		$post     = get_post( $ticket_id );
		$response = $this->prepare_item_for_response( $post, $request );

		return rest_ensure_response( $response );
	} // create_item

    /**
	 * Updates a single ticket.
	 *
	 * @since  1.5
	 *
	 * @param  WP_REST_Request             $request    Full details about the request.
	 * @return WP_REST_Response|WP_Error   Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		$valid_check = $this->get_post( $request['id'] );
		if ( is_wp_error( $valid_check ) ) {
			return $valid_check;
		}

        $ticket = new KBS_Ticket( $request['id'] );
        if ( empty( $ticket->ID ) ) {
			return $ticket;
		}

        /** This action is documented in wp-includes/rest-api/endpoints/class-wp-rest-posts-controller.php */
		do_action( "kbs_rest_update_{$this->post_type}", $ticket, $request, false );

        if ( ! empty( $request['agent'] ) )    {
            if ( is_email( $request['agent'] ) ) {
                $agent = get_user_by( 'email', $request['agent'] );

                if ( $agent )   {
                    $agent_id = $agent->ID;
                }
            } else  {
                $agent_id = $request['agent'];
            }

            if ( kbs_is_agent( absint( $request['agent'] ) ) )    {
                $ticket->__set( 'agent_id', absint( $request['agent'] ) );
            }
        }

        if ( kbs_multiple_agents() )    {
            if ( ! empty( $request['agents'] ) )    {
                $agents = explode( ',', $request['agents'] );
                $agents = array_map( 'absint', $agents );

                foreach( $agents as $key => $value )    {
                    if ( ! kbs_is_agent( $value ) || $value === $ticket->agent_id ) {
                        unset( $agents[ $key ] );
                    }
                }

                $ticket->__set( 'agents', $agents );
            }
        }

        if ( ! empty( $request['company'] ) && ! empty( get_post( absint( $request['company'] ) ) ) )    {
            $ticket->__set( 'company_id', absint( $request['company'] ) );
        }

        if ( ! empty( $request['customer'] ) )    {
            $customer = new KBS_Customer( $request['customer'] );

            if ( ! empty( $customer->id ) )  {
                $ticket->__set( 'customer_id', $customer->id );
            }
        }

        if ( ! empty( $request['status'] ) && in_array( $request['status'], kbs_get_ticket_status_keys() ) )    {
            $ticket->__set( 'status', $request['status'] );
        }

        if ( ! empty( $request['post_category'] ) )  {
			$terms = wp_get_post_terms( $ticket->ID, 'ticket_category', array( 'fields' => 'ids' ) );

			$terms[] = $request['post_category'];

            $ticket->__set( 'ticket_category', $terms );
        }

		if ( ! empty( $request['delete_category'] ) )  {
			$terms = wp_get_post_terms( $ticket->ID, 'ticket_category', array( 'fields' => 'ids' ) );
			$cats  = array();

			foreach( $terms as $term )	{
				if ( (int) $term->term_id == (int) $request['delete_category'] )	{
					continue;
				}
				$cats[] = $term->term_id;
			}

			if ( $cats != $terms )	{
				$ticket->__set( 'ticket_category', $cats );
			}
        }

		if ( ! empty( absint( $request['department'] ) ) )  {
            $ticket->__set( 'department', $request['department'] );
        }

        $ticket->save();

        $post     = get_post( $request['id'] );
        $response = $this->prepare_item_for_response( $post, $request );

		return rest_ensure_response( $response );
    } // update_item

	/**
	 * Prepares a single ticket output for response.
	 *
	 * @since	1.5
	 * @param	WP_Post				$post		WP_Post post object
	 * @param	WP_REST_Request		$request	Request object
	 * @return	WP_REST_Response	Response object
	 */
	public function prepare_item_for_response( $post, $request )	{
        $GLOBALS['post'] = $post;

		setup_postdata( $post );

        $ticket     = new KBS_Ticket( $post->ID );
		$agent      = new KBS_Agent( $ticket->agent_id );
		$company    = new KBS_Company( $ticket->company_id );
        $data       = array();

        $data['id']    = $post->ID;
        $data['date'] = $this->prepare_date_response( $post->post_date_gmt, $post->post_date );

        if ( '0000-00-00 00:00:00' === $post->post_date_gmt ) {
            $post_date_gmt = get_gmt_from_date( $post->post_date );
        } else {
            $post_date_gmt = $post->post_date_gmt;
        }

        $data['date_gmt'] = $this->prepare_date_response( $post_date_gmt );
        $data['modified'] = $this->prepare_date_response( $post->post_modified_gmt, $post->post_modified );

        if ( '0000-00-00 00:00:00' === $post->post_modified_gmt ) {
            $post_modified_gmt = gmdate( 'Y-m-d H:i:s', strtotime( $post->post_modified ) - ( get_option( 'gmt_offset' ) * 3600 ) );
        } else {
            $post_modified_gmt = $post->post_modified_gmt;
        }
        $data['modified_gmt'] = $this->prepare_date_response( $post_modified_gmt );

        $data['status'] = $post->post_status;

        $data['title'] = array();

        $data['title']['raw'] = $post->post_title;

        add_filter( 'protected_title_format', array( $this, 'protected_title_format' ) );

        $data['title']['rendered'] = get_the_title( $post->ID );

        remove_filter( 'protected_title_format', array( $this, 'protected_title_format' ) );

        $data['content'] = array();
        $data['content']['raw'] = $post->post_content;
        /** This filter is documented in wp-includes/post-template.php */
        $data['content']['rendered'] = apply_filters( 'the_content', $post->post_content );

		$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );

		foreach ( $taxonomies as $taxonomy ) {
			$base          = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;
            $terms         = get_the_terms( $post, $taxonomy->name );
            $data[ $base ] = $terms ? array_values( wp_list_pluck( $terms, 'term_id' ) ) : array();
		}

		$post_type_obj = get_post_type_object( $post->post_type );

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

        $data['ticket_data'] = array();

		if ( ! empty( $ticket->resolved_date ) )	{
			$data['ticket_data']['resolved_date'] = $ticket->resolved_date;
		}

		if ( ! empty( $ticket->number ) )	{
			$data['ticket_data']['number'] = $ticket->number;
		}

		if ( ! empty( $ticket->key ) )	{
			$data['ticket_data']['key'] = $ticket->key;
		}

		if ( ! empty( $ticket->files ) )	{
			$files = array();

			foreach( $ticket->files as $file )	{
				$files[] = array(
					'filename' => get_the_title( $file->ID ),
					'url'      => $file->guid
				);
			}

			$data['ticket_data']['attachments'] = $files;
		}

		$data['ticket_data']['agent'] = $ticket->agent_id;

		if ( ! empty( $ticket->agents ) )	{
			$data['ticket_data']['additional_agents'] = $ticket->agents;
		}

		if ( ! empty( $ticket->customer_id ) )	{
			$data['ticket_data']['customer'] = $ticket->customer_id;
		}

		if ( ! empty( $ticket->email ) )	{
			$data['ticket_data']['email'] = $ticket->email;
		}

		if ( ! empty( $ticket->user_id ) )	{
			$data['ticket_data']['user_id'] = $ticket->user_id;
		}

		if ( ! empty( $ticket->user_info ) )	{
			$data['ticket_data']['user_info'] = $ticket->user_info;
		}

		if ( ! empty( $this->company_id ) )	{
			$data['ticket_data']['company'] = $ticket->company_id;
		}

		if ( ! empty( $ticket->participants ) )	{
			$data['ticket_data']['participants'] = $ticket->participants;
		}

		if ( ! empty( $ticket->participants ) )	{
			$data['ticket_data']['source'] = $ticket->get_source( 'name' );
		}

		if ( ! empty( $ticket->form_data ) )	{
			$data['form_data'] = $ticket->form_data;
		}

        $replies = $ticket->get_replies();
        if ( ! empty( $ticket->replies ) )  {
            $data['replies'] = count( $replies );
        }

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );
		$links    = $this->prepare_links( $ticket );

		$response->add_links( $links );

		/**
		 * Filters the ticket data for a response.
		 *
		 * @since	1.5
		 *
		 * @param WP_REST_Response	$response	The response object
		 * @param KBS_Ticket		$ticket		Ticket object
		 * @param WP_REST_Request	$request	Request object
		 */
		return apply_filters( "rest_prepare_{$this->post_type}", $response, $ticket, $request );
	} // prepare_item_for_response

	/**
	 * Retrieves the query params for the tickets collection.
	 *
	 * @since	1.5
	 * @return	array	Collection parameters
	 */
	public function get_collection_params() {
		$singular     = kbs_get_ticket_label_singular();
		$plural       = kbs_get_ticket_label_plural();
		$query_params = parent::get_collection_params();

		$query_params['context']['default'] = 'view';

		$query_params['after'] = array(
			'description' => esc_html__( 'Limit response to posts published after a given ISO8601 compliant date.' , 'kb-support' ),
			'type'        => 'string',
			'format'      => 'date-time',
		);

		$query_params['before'] = array(
			'description' => esc_html__( 'Limit response to posts published before a given ISO8601 compliant date.', 'kb-support' ),
			'type'        => 'string',
			'format'      => 'date-time',
		);

		$query_params['exclude'] = array(
			'description' => esc_html__( 'Ensure result set excludes specific IDs.', 'kb-support' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
			'default'     => array(),
		);

		$query_params['include'] = array(
			'description' => esc_html__( 'Limit result set to specific IDs.', 'kb-support' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
			'default'     => array(),
		);

		$query_params['offset'] = array(
			'description' => esc_html__( 'Offset the result set by a specific number of items.', 'kb-support' ),
			'type'        => 'integer',
		);

		$query_params['order'] = array(
			'description' => esc_html__( 'Order sort attribute ascending or descending.', 'kb-support' ),
			'type'        => 'string',
			'default'     => 'desc',
			'enum'        => array( 'asc', 'desc' ),
		);

		$query_params['orderby'] = array(
			'description' => esc_html__( 'Sort collection by object attribute.', 'kb-support' ),
			'type'        => 'string',
			'default'     => 'id',
			'enum'        => array(
				'id',
				'date',
                'agent',
				'customer',
				'agent',
				'modified',
				'include',
				'title'
			)
		);

		$query_params['user'] = array(
			'description' => sprintf(
				esc_html__( 'Limit result set to %s from a specific customer WP user account.', 'kb-support' ),
				strtolower( $plural )
			),
			'type'        => 'integer',
			'default'     => null
		);

		$query_params['customer'] = array(
			'description' => sprintf(
				esc_html__( 'Limit result set to %s from a specific customer.', 'kb-support' ),
				strtolower( $plural )
			),
			'type'        => 'integer',
			'default'     => null
		);

		$query_params['company'] = array(
			'description' => sprintf(
				esc_html__( 'Limit result set to %s from a specific company.', 'kb-support' ),
				strtolower( $plural )
			),
			'type'        => 'integer',
			'default'     => null
		);

		$query_params['agent'] = array(
			'description' => sprintf(
				esc_html__( 'Limit result set to %s assigned to a specific agent.', 'kb-support' ),
				strtolower( $plural )
			),
			'type'        => 'integer',
			'default'     => null
		);

		$query_params['status'] = array(
			'default'     => kbs_get_ticket_status_keys( false ),
			'description' => sprintf(
				esc_html__( 'Limit result set to %s assigned one or more statuses.', 'kb-support' ),
				strtolower( $singular )
			),
			'type'        => 'array',
			'items'       => array(
				'enum' => array_keys( kbs_get_post_statuses() ),
				'type' => 'string'
			)
		);

		$post_type = get_post_type_object( $this->post_type );

		$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );

		if ( ! empty( $taxonomies ) ) {
			$query_params['tax_relation'] = array(
				'description' => esc_html__( 'Limit result set based on relationship between multiple taxonomies.' , 'kb-support' ),
				'type'        => 'string',
				'enum'        => array( 'AND', 'OR' ),
			);
		}

		foreach ( $taxonomies as $taxonomy ) {
			$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

			$query_params[ $base ] = array(
				/* translators: %s: Taxonomy name. */
				'description' => sprintf( esc_html__( 'Limit result set to all items that have the specified term assigned in the %s taxonomy.', 'kb-support' ), $base ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'default'     => array(),
			);

			$query_params[ $base . '_exclude' ] = array(
				/* translators: %s: Taxonomy name. */
				'description' => sprintf( esc_html__( 'Limit result set to all items except those that have the specified term assigned in the %s taxonomy.', 'kb-support' ), $base ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'default'     => array(),
			);
		}

		/**
		 * Filter collection parameters for the tickets controller.
		 *
		 * The dynamic part of the filter `$this->post_type` refers to the post
		 * type slug for the controller.
		 *
		 * This filter registers the collection parameter, but does not map the
		 * collection parameter to an internal WP_Query parameter. Use the
		 * `rest_{$this->post_type}_query` filter to set WP_Query parameters.
		 *
		 * @since	1.5
		 * @param	array			$query_params	JSON Schema-formatted collection parameters.
		 * @param	WP_Post_Type	$post_type		Post type object.
		 */
		return apply_filters( "kbs_rest_{$this->post_type}_collection_params", $query_params, $post_type );
	} // get_collection_params

	/**
	 * Checks if a ticket can be read.
	 *
	 * @since	1.5
	 * @param	object	KBS_Ticket object
	 * @return	bool	Whether the post can be read.
	 */
	public function check_read_permission( $ticket )	{
		$can_access = false;

		if ( kbs_is_agent( $this->user_id ) )	{
			$can_access = kbs_agent_can_access_ticket( $ticket->ID, $this->user_id );
		} else	{
			$can_access = kbs_customer_can_access_ticket( $ticket->ID );
		}

		return $can_access;
	} // check_read_permission

    /**
	 * Checks if a ticket can be updated.
	 *
	 * @since	1.5
	 * @param	object	WP_Post object
	 * @return	bool	Whether the post can be read.
	 */
	public function check_update_permission( $post )	{
		$can_access = false;

		if ( kbs_is_agent( $this->user_id ) )	{
			$can_access = kbs_agent_can_access_ticket( $post->ID, $this->user_id );
		}

		return $can_access;
	} // check_update_permission

    /**
	 * Determines the allowed query_vars for a get_items() response and prepares
	 * them for WP_Query.
	 *
	 * @since  1.5
	 * @param  array           $prepared_args  Optional. Prepared WP_Query arguments. Default empty array.
	 * @param  WP_REST_Request $request        Optional. Full details about the request.
	 * @return array           Items query arguments.
	 */
	protected function prepare_items_query( $prepared_args = array(), $request = null ) {
		$query_args = array();

		foreach ( $prepared_args as $key => $value ) {
			/**
			 * Filters the query_vars used in get_items() for the constructed query.
			 *
			 * The dynamic portion of the hook name, `$key`, refers to the query_var key.
			 *
			 * @since    1.5
			 *
			 * @param    string  $value  The query_var value.
			 */
			$query_args[ $key ] = apply_filters( "rest_query_var-{$key}", $value ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		}

		// Map to proper WP_Query orderby param.
		if ( isset( $query_args['orderby'] ) && isset( $request['orderby'] ) ) {
			$orderby_mappings = array(
				'id'            => 'ID',
				'include'       => 'post__in'
			);

			if ( isset( $orderby_mappings[ $request['orderby'] ] ) ) {
				$query_args['orderby'] = $orderby_mappings[ $request['orderby'] ];
			}
		}

		return $query_args;
	} // prepare_items_query

	/**
	 * Prepares links for the request.
	 *
	 * @since	1.5
	 * @param	KBS_Ticket	$ticket		KBS Ticket object
	 * @return	array		Links for the given post
	 */
	protected function prepare_links( $ticket ) {
		$base = sprintf( '%s/%s', $this->namespace . $this->version, $this->rest_base );

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( trailingslashit( $base ) . $ticket->ID )
			),
			'collection' => array(
				'href' => rest_url( $base )
			)
		);

		if ( ! empty( $ticket->agent_id ) )	{
			$links['agent'] = array(
				'href'       => rest_url( 'wp/v2/users/' . $ticket->agent_id ),
				'embeddable' => true
			);
		}

		if ( ! empty( $ticket->customer_id ) && $ticket->customer_id > 0 )	{
			$links['customer'] = array(
				'href'       => rest_url( 'kbs/v1/customers/' . $ticket->customer_id ),
				'embeddable' => true
			);
		}

		if ( ! empty( $ticket->user_id ) )	{
			$links['user'] = array(
				'href'       => rest_url( 'wp/v2/users/' . $ticket->user_id ),
				'embeddable' => true
			);
		}

		if ( ! empty( $ticket->company_id ) )	{
			$links['company'] = array(
				'href'       => rest_url( 'kbs/v1/companies/' . $ticket->company_id ),
				'embeddable' => true
			);
		}

        if ( ! empty( $ticket->replies ) )  {
            $links['replies'] = array();

			$links['replies']['collection'] = array(
				'href'       => rest_url( 'kbs/v1/replies/ticket/' . $ticket->ID ),
				'embeddable' => true
			);
        }

		return $links;
	} // prepare_links

	/**
	 * Retrieves an array of required parameters to create a ticket via REST.
	 *
	 * @since	1.5
	 * @return	array	Array of required parameters
	 */
	public function get_required_fields()	{
		$fields = array(
			'customer_first',
			'customer_last',
			'customer_email',
			'ticket_title',
			'ticket_content'
		);

		$fields = apply_filters( 'kbs_rest_required_ticket_params', $fields );

		return $fields;
	} // get_required_fields

} // KBS_Tickets_API
