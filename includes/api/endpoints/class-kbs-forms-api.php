<?php
/**
 * KB Support REST API
 *
 * @package     KBS
 * @subpackage  Classes/Forms REST API
 * @copyright   Copyright (c) 2020, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.5.1
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * KBS_Forms_API Class
 *
 * @since	1.5.1
 */
class KBS_Forms_API extends KBS_API {
	/**
	 * Post type.
	 *
	 * @since	1.5.1
	 * @var		string
	 */
	protected $post_type;

	/**
	 * Instance of a post meta fields object.
	 *
	 * @since	1.5.1
	 * @var		WP_REST_Post_Meta_Fields
	 */
	protected $meta;

	/**
	 * Get things going
	 *
	 * @since	1.5.1
	 */
	public function __construct()	{
        $this->post_type = 'kbs_form';
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
						'description' => esc_html__( 'Unique identifier for the form.', 'kb-support' ),
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
     * @since   1.5.1
     * @param	WP_REST_Request	$request	Full details about the request.
	 * @return	bool|WP_Error	True if the request has read access for the item, WP_Error object otherwise.
     */
    public function get_item_permissions_check( $request ) {
		$form_id = $request['id'];

        $post = $this->get_post( $form_id );

        if ( is_wp_error( $post ) ) {
			return $post;
		}

		if ( $post ) {
			return $this->check_read_permission( $post );
		}

		return true;
    } // get_item_permissions_check

    /**
     * Checks if a given request has access to read multiple forms.
     *
     * @since   1.5.1
     * @param	WP_REST_Request	$request	Full details about the request.
	 * @return	bool|WP_Error	True if the request has read access for the item, WP_Error object otherwise.
     */
    public function get_items_permissions_check( $request ) {
		return true;
    } // get_items_permissions_check

	/**
	 * Retrieves a single form.
	 *
	 * @since	1.5.1
	 * @param	WP_REST_Request	$request	Full details about the request
	 * @return	WP_REST_Response|WP_Error	Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$form_id = $request['id'];

		$post = $this->get_post( absint( $form_id ) );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$data     = $this->prepare_item_for_response( $post, $request );
		$response = rest_ensure_response( $data );

		return $response;
	} // get_item

	/**
	 * Retrieves a collection of forms.
	 *
	 * @since	1.5.1
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

        // Ensure our per_page parameter overrides any provided posts_per_page filter.
		if ( isset( $registered['per_page'] ) ) {
			$args['posts_per_page'] = $request['per_page'];
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
		 * @since	1.5.1
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
	 * Prepares a single form output for response.
	 *
	 * @since	1.5.1
	 * @param	WP_Post				$post		WP_Post post object
	 * @param	WP_REST_Request		$request	Request object
	 * @return	WP_REST_Response	Response object
	 */
	public function prepare_item_for_response( $post, $request )	{
        $GLOBALS['post'] = $post;

		setup_postdata( $post );

        $form = new KBS_Form( $post->ID );
        $data = array();

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

        $data['status'] = $post->post_status;
        $data['title']  = $post->post_title;
        $data['meta']   = $this->meta->get_value( $post->ID, $request );

        $data['fields'] = array();

        foreach( $form->get_fields() as $field ) {
            $default    = get_post_meta( $field->ID, '_default_field', true ) ? true : false;
            $field_data = array();
            $field_data['ID']       = $field->ID;
            $field_data['title']    = $field->post_content;
            $field_data['settings'] = $form->get_field_settings( $field->ID );
            $field_data['default']  = $default;

            $data['fields'][] = $field_data;
        }

		$post_type_obj = get_post_type_object( $post->post_type );

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );
		$links    = $this->prepare_links( $form );

		$response->add_links( $links );

		/**
		 * Filters the form data for a response.
		 *
		 * @since	1.5.1
		 *
		 * @param WP_REST_Response    $response	The response object
		 * @param KBS_Form            $form		Form object
		 * @param WP_REST_Request     $request	Request object
		 */
		return apply_filters( "rest_prepare_{$this->post_type}", $response, $form, $request );
	} // prepare_item_for_response

	/**
	 * Retrieves the query params for the tickets collection.
	 *
	 * @since	1.5.1
	 * @return	array	Collection parameters
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();

		$query_params['context']['default'] = 'view';

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
			'default'     => 'title',
			'enum'        => array(
				'id',
				'date',
				'modified',
				'include',
				'title'
			)
		);

        $post_type = get_post_type_object( $this->post_type );

		/**
		 * Filter collection parameters for the forms controller.
		 *
		 * The dynamic part of the filter `$this->post_type` refers to the post
		 * type slug for the controller.
		 *
		 * This filter registers the collection parameter, but does not map the
		 * collection parameter to an internal WP_Query parameter. Use the
		 * `rest_{$this->post_type}_query` filter to set WP_Query parameters.
		 *
		 * @since	1.5.1
		 * @param	array			$query_params	JSON Schema-formatted collection parameters.
		 * @param	WP_Post_Type	$post_type		Post type object.
		 */
		return apply_filters( "kbs_rest_{$this->post_type}_collection_params", $query_params, $post_type );
	} // get_collection_params

	/**
	 * Checks if a ticket can be read.
	 *
	 * @since	1.5.1
	 * @param	object	KBS_Ticket object
	 * @return	bool	Whether the post can be read.
	 */
	public function check_read_permission( $form )	{
		return true;
	} // check_read_permission

    /**
	 * Determines the allowed query_vars for a get_items() response and prepares
	 * them for WP_Query.
	 *
	 * @since  1.5.1
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
			 * @since    1.5.1
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
	 * @since	1.5.1
	 * @param	KBS_Form	$form		KBS_Form object
	 * @return	array		Links for the given post
	 */
	protected function prepare_links( $form ) {
		$base = sprintf( '%s/%s', $this->namespace . $this->version, $this->rest_base );

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( trailingslashit( $base ) . $form->ID )
			),
			'collection' => array(
				'href' => rest_url( $base )
			)
		);

        if ( ! empty( $form->fields ) )  {
            $links['fields'] = array();

			$links['fields']['collection'] = array(
				'href'       => rest_url( 'kbs/v1/forms/fields/' . $form->ID ),
				'embeddable' => true
			);
        }

		return $links;
	} // prepare_links
} // KBS_Forms_API
