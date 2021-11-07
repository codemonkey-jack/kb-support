<?php
/**
 * KB Support REST API
 *
 * @package     KBS
 * @subpackage  Classes/Replies REST API
 * @copyright   Copyright (c) 2020, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.5
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * KBS_Replies_API Class
 *
 * @since	1.5
 */
class KBS_Replies_API extends KBS_API {

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
	public function __construct( $post_type )	{
        $this->post_type = 'kbs_ticket_reply';
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
			'/' . $this->rest_base . '/ticket' . '/(?P<id>\d+)',
			array(
                'args'   => array(
					'id' => array(
						'type'        => 'integer',
						'description' => sprintf(
                            __( 'Unique identifier for the %s.', 'kb-support' ),
                            kbs_get_ticket_label_singular( true )
                        )
					)
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
                array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
			),
		);


		register_rest_route(
			$this->namespace . $this->version,
			'/' . $this->rest_base . '/(?P<id>\d+)',
			array(
				'args'   => array(
					'id' => array(
						'type'        => 'integer',
						'description' => __( 'Unique identifier for the reply.', 'kb-support' )
					)
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' )
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),

				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
			),
		);

		// Register routes for the notes.
		register_rest_route(
			$this->namespace . $this->version,
			'/' . $this->rest_base . '/ticket/notes' . '/(?P<id>\d+)',
			array(
                'args'   => array(
					'id' => array(
						'type'        => 'integer',
						'description' => sprintf(
                            __( 'Unique identifier for the %s.', 'kb-support' ),
                            kbs_get_ticket_label_singular( true )
                        )
					)
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_notes' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
                array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_note' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_note' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
			),
		);

    } // register_routes

	/**
     * Checks if a given request has access to read a reply.
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

        $reply_id = isset( $request['id'] ) ? $request['id'] : false;
        $post     = $this->get_post( $reply_id );

        if ( is_wp_error( $post ) ) {
			return $post;
		}

        if ( 'edit' === $request['context'] || ! $this->check_read_permission( $post ) ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to view this object.', 'kb-support' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

        return $this->check_read_permission( $post );
    } // get_item_permissions_check

    /**
     * Checks if a given request has access to read multiple replies.
     *
     * @since   1.5
     * @param	WP_REST_Request	$request	Full details about the request.
	 * @return	bool|WP_Error	True if the request has read access for the item, WP_Error object otherwise.
     */
    public function get_items_permissions_check( $request ) {
		return true;
    } // get_items_permissions_check

    /**
	 * Checks if a given request has access to create a ticket reply.
	 *
	 * @since  1.5
	 *
	 * @param  WP_REST_Request $request    Full details about the request.
	 * @return true|WP_Error   True if the request has access to create the item, WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ) {
		$create = $this->is_authenticated() && kbs_is_agent( $this->user_id );
        $create = apply_filters( "kbs_rest_{$this->post_type}_create", $create, $request, $this );
		if ( ! $create ) {
			return new WP_Error(
				'rest_forbidden_context',
				$this->errors( 'no_auth' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
    } // create_item_permissions_check

	/**
	 * Retrieves a single reply.
	 *
	 * @since	1.5
	 * @param	WP_REST_Request	$request	Full details about the request
	 * @return	WP_REST_Response|WP_Error	Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$reply_id = isset( $request['id'] ) ? $request['id'] : false;
		$post     = $this->get_post( absint( $reply_id ) );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$data     = $this->prepare_item_for_response( $post, $request );
		$response = rest_ensure_response( $data );

		return $response;
	} // get_item

	/**
	 * Retrieves a collection of replies.
	 *
	 * @since	1.5
	 * @param	WP_REST_Request		$request	Full details about the request
	 * @return	WP_REST_Response|WP_Error		Response object on success, or WP_Error object on failure
	 */
	function get_items( $request ) {
        // Ensure a search string is set in case the orderby is set to 'relevance'.
		if ( ! empty( $request['orderby'] ) && 'relevance' === $request['orderby'] && empty( $request['search'] ) ) {
			return new WP_Error(
				'rest_no_search_term_defined',
				__( 'You need to define a search term to order by relevance.', 'kb-support' ),
				array( 'status' => 400 )
			);
		}

		// Ensure an include parameter is set in case the orderby is set to 'include'.
		if ( ! empty( $request['orderby'] ) && 'include' === $request['orderby'] && empty( $request['include'] ) ) {
			return new WP_Error(
				'rest_orderby_include_missing_include',
				__( 'You need to define an include parameter to order by include.', 'kb-support' ),
				array( 'status' => 400 )
			);
		}

		// Retrieve the list of registered collection query parameters.
		$registered = $this->get_collection_params();
		$args       = array();
        $meta_query = array();

        // Set the ticket (parent) ID
        $args['post_parent'] = $request['id'];

        /*
		 * This array defines mappings between public API query parameters whose
		 * values are accepted as-passed, and their internal WP_Query parameter
		 * name equivalents (some are the same). Only values which are also
		 * present in $registered will be set.
		 */
		$parameter_mappings = array(
            'author'         => 'author__in',
			'author_exclude' => 'author__not_in',
			'exclude'        => 'post__not_in',
			'include'        => 'post__in',
			'offset'         => 'offset',
			'order'          => 'order',
			'orderby'        => 'orderby',
			'page'           => 'paged'
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

        // By ticket ID
        if ( isset( $registered['customer'], $request['customer'] ) ) {
            $meta_query[] = array(
                'key'   => '_kbs_reply_customer_id',
                'value' => (int) $request['customer'],
                'type'  => 'NUMERIC'
            );
        }

        // By an agent ID
        if ( isset( $registered['agent'], $request['agent'] ) ) {
            $meta_query[] = array(
                'key'   => '_kbs_reply_agent_id',
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

		$posts_query  = new WP_Query();
		$query_result = $posts_query->query( $query_args );
        $posts        = array();

		foreach ( $query_result as $post ) {
			if ( ! $this->check_read_permission( $post ) ) {
				continue;
			}
			$data    = $this->prepare_item_for_response( $post, $request );
			$data->data['author'] = get_the_author_meta( 'user_nicename', $post->post_author );
			$data->data['isAgent'] = kbs_is_agent( $post->post_author);
			$data->data['type'] = 'kbs_ticket_reply';
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
				__( 'The page number requested is larger than the number of pages available.', 'kb-support' ),
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

	public function get_notes( $request ) {
		$notes = kbs_get_notes( $request['id'] );
		$formatted_noted = [];

		foreach ( $notes as $key => $note ) {
			$formatted_noted[$key]['id'] = $note->comment_ID;
			$formatted_noted[$key]['date'] = $note->comment_date;
			$formatted_noted[$key]['postID'] = $note->comment_post_ID;
			$formatted_noted[$key]['type'] = $note->comment_type;
			$formatted_noted[$key]['author'] = get_the_author_meta( 'user_nicename', $note->user_id );
			$formatted_noted[$key]['content'] = $note->comment_content;

		}
		return rest_ensure_response( $formatted_noted );

	}

	public function create_note( $request ) {
		if ( ! isset( $request['id'] ) ) {
			return new WP_Error( 'rest_post_invalid_id', __( 'Invalid post ID.', 'kb-support' ), array( 'status' => 404 ) );
		}
		$id = $request['id'];
		$note = $request['note'];

		$note_id = kbs_insert_note( $id, $note );

		return rest_ensure_response( $note_id );
	}

    /**
	 * Creates a ticket reply.
	 *
	 * @since  1.5
	 *
	 * @param  WP_REST_Request             $request    Full details about the request.
	 * @return WP_REST_Response|WP_Error   Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
        foreach( $this->get_required_fields() as $received => $send )   {
            if ( empty( $request[ $received ] ) )   {
                return new WP_Error(
                    'required_fields',
                    $this->errors( 'required_fields' ),
                    array( 'status' => 400 )
                );
            }
        }

        $error = new WP_Error(
			'rest_post_invalid_id',
			__( 'Invalid post ID.', 'kb-support' ),
			array( 'status' => 404 )
		);

        $ticket_id = absint( $request['id'] );
        $post      = get_post( $ticket_id );

        if ( empty( $post ) || empty( $post->ID ) || 'kbs_ticket' !== $post->post_type ) {
			return $error;
		}

        $ticket = new KBS_Ticket( $ticket_id );

		$reply_data = array(
			'ticket_id'   => $ticket->ID,
            'response'    => $request['reply_content'],
            'close'       => ! empty( $request['close_ticket'] ) ? true : false,
            'customer_id' => (int) $ticket->customer_id,
            'author'      => 0,
            'status'      => $ticket->post_status,
            'author'      => ! empty( $request['reply_author'] ) ? $request['reply_author'] : get_current_user_id(),
            'source'      => 'kbs-rest'
        );

        if ( ! empty( $request['ticket_status'] ) && in_array( $request['ticket_status'], kbs_get_ticket_status_keys() ) )  {
            $reply_data['status'] = $request['ticket_status'];
        }

        if ( ! empty( $request['agent'] ) ) {
            $agent_id = 0;

            if ( is_email( $request['agent'] ) ) {
                $agent = get_user_by( 'email', $request['agent'] );

                if ( $agent )   {
                    $agent_id = $agent->ID;
                }
            } else  {
                $agent_id = absint( $request['agent'] );
            }

            if ( kbs_is_agent( $agent_id ) )    {
                $reply_data['agent_id'] = $agent_id;
            }
        }

		$reply_id = $ticket->add_reply( $reply_data );

		if ( ! $reply_id )	{
			return new WP_Error(
				'create_reply_failed',
				$this->errors( 'create_reply_failed' ),
				array( 'status' => 400 )
			);
		}

		$post     = get_post( $reply_id );
		$response = $this->prepare_item_for_response( $post, $request );

		return rest_ensure_response( $response );
	} // create_item

	public function delete_item( $request ) {

		if( ! isset($request['id']) )  {
			return new WP_Error(
				'required_fields',
				$this->errors( 'required_fields' ),
				array( 'status' => 400 )
			);
		}

		$post = get_post( $request['id']);

		wp_delete_post( $request['id'] );

		$response = $this->prepare_item_for_response( $post, $request );

		return rest_ensure_response( $response );

	}

    /**
	 * Prepares a single reply output for response.
	 *
	 * @since	1.5
	 * @param	WP_Post				$post		WP_Post post object
	 * @param	WP_REST_Request		$request	Request object
	 * @return	WP_REST_Response	Response object
	 */
	public function prepare_item_for_response( $post, $request )	{
        $GLOBALS['post'] = $post;

		setup_postdata( $post );

        $ticket = new KBS_Ticket( $post->post_parent );
        $data   = array();

        $data['id']   = $post->ID;
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

        $data['status']  = $post->post_status;
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

		if ( ! empty( $ticket->ID ) )	{
			$data['ticket_data']['ID'] = $ticket->ID;

            if ( ! empty( $ticket->number ) )	{
                $data['ticket_data']['number'] = $ticket->number;
            }

            if ( ! empty( $ticket->key ) )	{
                $data['ticket_data']['key'] = $ticket->key;
            }

            if ( ! empty( $ticket->status ) )	{
                $data['ticket_data']['status'] = get_post_status( $ticket->ID );
            }

            if ( ! empty( $ticket->post_title ) )	{
                $data['ticket_data']['title'] = get_post_title( $ticket->ID );
            }
        }

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );
		$links    = $this->prepare_links( $post );

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
		$query_params = parent::get_collection_params();

		$query_params['context']['default'] = 'view';

		$query_params['orderby'] = array(
			'description' => __( 'Sort collection by object attribute.', 'kb-support' ),
			'type'        => 'string',
			'default'     => 'id',
			'enum'        => array(
				'id',
				'date',
                'agent',
				'customer',
				'modified',
				'include'
			)
		);

		$post_type = get_post_type_object( $this->post_type );

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
		 *
		 * @param	array			$query_params	JSON Schema-formatted collection parameters.
		 * @param	WP_Post_Type	$post_type		Post type object.
		 */
		return apply_filters( "rest_{$this->post_type}_collection_params", $query_params, $post_type );
	} // get_collection_params

	/**
	 * Checks if a ticket can be read.
	 *
	 * @since	1.5
	 * @param	object   $post   WP_Post object
	 * @return	bool	Whether the post can be read.
	 */
	public function check_read_permission( $post )	{
		$can_access = false;

        if ( ! empty( $post ) && is_object( $post ) )    {
            switch ( get_post_type( $post ) )   {
                case 'kbs_ticket_reply':
                    $object_id = $post->post_parent;
                    break;
                case 'kbs_ticket':
                    $object_id = $post->ID;
                    break;
                default:
                    return new WP_Error(
                        'rest_post_invalid_type',
                        __( 'Invalid post type.', 'kb-support' ),
                        array( 'status' => 404 )
                    );
            }

            if ( kbs_is_agent( $this->user_id ) )	{
                $can_access = kbs_agent_can_access_ticket( $object_id );
            } else	{
                $can_access = kbs_customer_can_access_ticket( $object_id );
            }
        }

		return $can_access;
	} // check_read_permission

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
	 * @param	WP_Post  $post		WP_Post object
	 * @return	array    Links for the given post
	 */
	protected function prepare_links( $post ) {
        $base   = sprintf( '%s/%s', $this->namespace . $this->version, $this->rest_base );
        $ticket = new KBS_Ticket( $post->post_parent );

        // Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( trailingslashit( $base ) . $post->ID ),
			)
		);

		if ( ! empty( $ticket->ID ) )	{
            $links['collection'] = array(
				'href'       => rest_url( trailingslashit( $base . '/ticket/' ) . $ticket->ID ),
                'embeddable' => true
			);

			$links['ticket'] = array(
				'href'       => rest_url( 'wp/v2/tickets/' . $ticket->ID ),
				'embeddable' => true,
			);

            if ( ! empty( $ticket->agent ) )	{
                $links['agent'][] = array(
                    'href'       => rest_url( 'wp/v2/users/' . $ticket->agent ),
                    'embeddable' => true
                );
            }

            if ( ! empty( $ticket->customer_id ) )	{
                $links['customer'] = array(
                    'href'       => rest_url( 'kbs/v1/customers/' . $ticket->customer_id ),
                    'embeddable' => true,
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
        }

		return $links;
	} // prepare_links

    /**
	 * Retrieves an array of required parameters to create a ticket reply via REST.
     *
     * Formatted as $key = expected key received within $request, $value = key expected
     * when adding reply.
	 *
	 * @since	1.5
	 * @return	array	Array of required parameters
	 */
	public function get_required_fields()	{
		$fields = array(
			'id'            => 'ticket_id',
			'reply_content' => 'response'
		);

		$fields = apply_filters( 'kbs_rest_required_ticket_reply_params', $fields );

		return $fields;
	} // get_required_fields

} // KBS_Replies_API
