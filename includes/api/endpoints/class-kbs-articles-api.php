<?php
/**
 * KB Support REST API
 *
 * @package     KBS
 * @subpackage  Classes/Articles REST API
 * @copyright   Copyright (c) 2020, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.5
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * KBS_Articles_API Class
 *
 * @since	1.5
 */
class KBS_Articles_API extends KBS_API {

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
		$this->post_type = $post_type;
		$obj             = get_post_type_object( $this->post_type );
		$this->rest_base = ! empty( $obj->rest_base ) ? $obj->rest_base : $obj->name;

		$this->meta = new WP_REST_Post_Meta_Fields( $this->post_type );
	} // __construct

	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @since	1.5
	 * @see		register_rest_route()
	 */
	public function register_routes() {
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
				'schema' => array( $this, 'get_public_item_schema' )
			)
		);

		$schema        = $this->get_item_schema();
		$get_item_args = array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
		);
		if ( isset( $schema['properties']['password'] ) ) {
			$get_item_args['password'] = array(
				'description' => __( 'The password for the post if it is password protected.', 'kb-support' ),
				'type'        => 'string',
			);
		}

		register_rest_route(
			$this->namespace . $this->version,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the object.', 'kb-support' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => $get_item_args,
				),
				'schema' => array( $this, 'get_public_item_schema' )
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
		$post = $this->get_post( $request['id'] );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		if ( kbs_article_is_restricted( $post->ID ) && ! $this->is_authenticated() )    {
            return new WP_Error(
				'rest_forbidden_context',
				$this->errors( 'restricted_article' ),
				array( 'status' => rest_authorization_required_code() )
			);
        }

		if ( ! kbs_article_user_can_access( $post->ID ) )	{
			return new WP_Error(
				'rest_forbidden_context',
				KBS()->api->errors( 'restricted_article' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( 'edit' === $request['context'] && $post && ! $this->check_update_permission( $post ) ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to edit this post.', 'kb-support' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		if ( $post && ! empty( $request['password'] ) ) {
			// Check post password, and return error if invalid.
			if ( ! hash_equals( $post->post_password, $request['password'] ) ) {
				return new WP_Error(
					'rest_post_incorrect_password',
					__( 'Incorrect post password.', 'kb-support' ),
					array( 'status' => 403 )
				);
			}
		}

		// Allow access to all password protected posts if the context is edit.
		if ( 'edit' === $request['context'] ) {
			add_filter( 'post_password_required', '__return_false' );
		}

		if ( $post ) {
			return $this->check_read_permission( $post );
		}

		return true;
    } // get_item_permissions_check

	/**
	 * Checks if a given request has access to read posts.
	 *
	 * @since	1.5
	 *
	 * @param 	WP_REST_Request	$request	Full details about the request.
	 * @return	true|WP_Error	True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		$post_type = get_post_type_object( $this->post_type );

		if ( 'edit' === $request['context'] && ! current_user_can( $post_type->cap->edit_posts ) ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to edit posts in this post type.', 'kb-support' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	} // get_items_permissions_check

	/**
	 * Retrieves a single post.
	 *
	 * @since	1.5
	 * @param	WP_REST_Request				$request	Full details about the request.
	 * @return	WP_REST_Response|WP_Error	Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$post = $this->get_post( $request['id'] );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$data     = $this->prepare_item_for_response( $post, $request );
		$response = rest_ensure_response( $data );

		if ( is_post_type_viewable( get_post_type_object( $post->post_type ) ) ) {
			$response->link_header( 'alternate', get_permalink( $post->ID ), array( 'type' => 'text/html' ) );
		}

		return $response;
	} // get_item

	/**
	 * Retrieves a collection of posts.
	 *
	 * @since	1.5
	 * @param	WP_REST_Request				$request	Full details about the request.
	 * @return	WP_REST_Response|WP_Error	Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {

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
			'menu_order'     => 'menu_order',
			'offset'         => 'offset',
			'order'          => 'order',
			'orderby'        => 'orderby',
			'page'           => 'paged',
			'parent'         => 'post_parent__in',
			'parent_exclude' => 'post_parent__not_in',
			'search'         => 's',
			'slug'           => 'post_name__in',
			'status'         => 'post_status',
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

		if ( isset( $registered['sticky'], $request['sticky'] ) ) {
			$sticky_posts = get_option( 'sticky_posts', array() );
			if ( ! is_array( $sticky_posts ) ) {
				$sticky_posts = array();
			}
			if ( $request['sticky'] ) {
				/*
				 * As post__in will be used to only get sticky posts,
				 * we have to support the case where post__in was already
				 * specified.
				 */
				$args['post__in'] = $args['post__in'] ? array_intersect( $sticky_posts, $args['post__in'] ) : $sticky_posts;

				/*
				 * If we intersected, but there are no post ids in common,
				 * WP_Query won't return "no posts" for post__in = array()
				 * so we have to fake it a bit.
				 */
				if ( ! $args['post__in'] ) {
					$args['post__in'] = array( 0 );
				}
			} elseif ( $sticky_posts ) {
				/*
				 * As post___not_in will be used to only get posts that
				 * are not sticky, we have to support the case where post__not_in
				 * was already specified.
				 */
				$args['post__not_in'] = array_merge( $args['post__not_in'], $sticky_posts );
			}
		}

		// Force the post_type argument, since it's not a user input variable.
		$args['post_type'] = $this->post_type;

		/**
		 * Filters the query arguments for a request.
		 *
		 * Enables adding extra arguments or setting defaults for a post collection request.
		 *
		 * @since 4.7.0
		 *
		 * @link https://developer.wordpress.org/reference/classes/wp_query/
		 *
		 * @param array           $args    Key value array of query var to query value.
		 * @param WP_REST_Request $request The request used.
		 */
		$args       = apply_filters( "rest_{$this->post_type}_query", $args, $request );
		$query_args = $this->prepare_items_query( $args, $request );

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

		// Allow access to all password protected posts if the context is edit.
		if ( 'edit' === $request['context'] ) {
			add_filter( 'post_password_required', '__return_false' );
		}

		$posts = array();

		foreach ( $query_result as $post ) {
			if ( ! $this->check_read_permission( $post ) ) {
				continue;
			}

			$data    = $this->prepare_item_for_response( $post, $request );
			$posts[] = $this->prepare_response_for_collection( $data );
		}

		// Reset filter.
		if ( 'edit' === $request['context'] ) {
			remove_filter( 'post_password_required', '__return_false' );
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

	/**
	 * Checks if the user can access password-protected content.
	 *
	 * This method determines whether we need to override the regular password
	 * check in core with a filter.
	 *
	 * @since	1.5
	 *
	 * @param	WP_Post			$post		Post to check against.
	 * @param	WP_REST_Request	$request	Request data to check.
	 * @return	bool			True if the user can access password-protected content, otherwise false.
	 */
	public function can_access_password_content( $post, $request ) {
		if ( empty( $post->post_password ) ) {
			// No filter required.
			return false;
		}

		// Edit context always gets access to password-protected posts.
		if ( 'edit' === $request['context'] ) {
			return true;
		}

		// No password, no auth.
		if ( empty( $request['password'] ) ) {
			return false;
		}

		// Double-check the request password.
		return hash_equals( $post->post_password, $request['password'] );
	} // can_access_password_content

	/**
	 * Checks if a given post type can be viewed or managed.
	 *
	 * @since	1.5
	 * @param	WP_Post_Type|string	$post_type	Post type name or object.
	 * @return	bool				Whether the post type is allowed in REST.
	 */
	protected function check_is_post_type_allowed( $post_type ) {
		if ( ! is_object( $post_type ) ) {
			$post_type = get_post_type_object( $post_type );
		}

		if ( ! empty( $post_type ) && ! empty( $post_type->show_in_rest ) ) {
			return true;
		}

		return false;
	} // check_is_post_type_allowed

	/**
	 * Checks if a post can be read.
	 *
	 * Correctly handles posts with the inherit status.
	 *
	 * @since	1.5
	 * @param	WP_Post	$post Post object.
	 * @return	bool	Whether the post can be read.
	 */
	public function check_read_permission( $post ) {
		$post_type = get_post_type_object( $post->post_type );
		if ( ! $this->check_is_post_type_allowed( $post_type ) ) {
			return false;
		}

		// Is the post readable?
		if ( 'publish' === $post->post_status || current_user_can( $post_type->cap->read_post, $post->ID ) ) {
			return true;
		}

		$post_status_obj = get_post_status_object( $post->post_status );
		if ( $post_status_obj && $post_status_obj->public ) {
			return true;
		}

		// Can we read the parent if we're inheriting?
		if ( 'inherit' === $post->post_status && $post->post_parent > 0 ) {
			$parent = get_post( $post->post_parent );
			if ( $parent ) {
				return $this->check_read_permission( $parent );
			}
		}

		/*
		 * If there isn't a parent, but the status is set to inherit, assume
		 * it's published (as per get_post_status()).
		 */
		if ( 'inherit' === $post->post_status ) {
			return true;
		}

		return false;
	} // check_read_permission

	/**
	 * Prepares a single post output for response.
	 *
	 * @since	1.5
	 * @param	WP_Post				$post		Post object.
	 * @param	WP_REST_Request		$request	Request object.
	 * @return	WP_REST_Response	Response object.
	 */
	public function prepare_item_for_response( $post, $request ) {
		$GLOBALS['post'] = $post;

		setup_postdata( $post );

		$fields = $this->get_fields_for_response( $request );

		// Base fields for every post.
		$data = array();

		if ( rest_is_field_included( 'id', $fields ) ) {
			$data['id'] = $post->ID;
		}

		if ( rest_is_field_included( 'date', $fields ) ) {
			$data['date'] = $this->prepare_date_response( $post->post_date_gmt, $post->post_date );
		}

		if ( rest_is_field_included( 'date_gmt', $fields ) ) {
			/*
			 * For drafts, `post_date_gmt` may not be set, indicating that the date
			 * of the draft should be updated each time it is saved (see #38883).
			 * In this case, shim the value based on the `post_date` field
			 * with the site's timezone offset applied.
			 */
			if ( '0000-00-00 00:00:00' === $post->post_date_gmt ) {
				$post_date_gmt = get_gmt_from_date( $post->post_date );
			} else {
				$post_date_gmt = $post->post_date_gmt;
			}
			$data['date_gmt'] = $this->prepare_date_response( $post_date_gmt );
		}

		if ( rest_is_field_included( 'guid', $fields ) ) {
			$data['guid'] = array(
				/** This filter is documented in wp-includes/post-template.php */
				'rendered' => apply_filters( 'get_the_guid', $post->guid, $post->ID ),
				'raw'      => $post->guid,
			);
		}

		if ( rest_is_field_included( 'modified', $fields ) ) {
			$data['modified'] = $this->prepare_date_response( $post->post_modified_gmt, $post->post_modified );
		}

		if ( rest_is_field_included( 'modified_gmt', $fields ) ) {
			/*
			 * For drafts, `post_modified_gmt` may not be set (see `post_date_gmt` comments
			 * above). In this case, shim the value based on the `post_modified` field
			 * with the site's timezone offset applied.
			 */
			if ( '0000-00-00 00:00:00' === $post->post_modified_gmt ) {
				$post_modified_gmt = gmdate( 'Y-m-d H:i:s', strtotime( $post->post_modified ) - ( get_option( 'gmt_offset' ) * 3600 ) );
			} else {
				$post_modified_gmt = $post->post_modified_gmt;
			}
			$data['modified_gmt'] = $this->prepare_date_response( $post_modified_gmt );
		}

		if ( rest_is_field_included( 'password', $fields ) ) {
			$data['password'] = $post->post_password;
		}

		if ( rest_is_field_included( 'slug', $fields ) ) {
			$data['slug'] = $post->post_name;
		}

		if ( rest_is_field_included( 'status', $fields ) ) {
			$data['status'] = $post->post_status;
		}

		if ( rest_is_field_included( 'type', $fields ) ) {
			$data['type'] = $post->post_type;
		}

		if ( rest_is_field_included( 'link', $fields ) ) {
			$data['link'] = get_permalink( $post->ID );
		}

		if ( rest_is_field_included( 'title', $fields ) ) {
			$data['title'] = array();
		}
		if ( rest_is_field_included( 'title.raw', $fields ) ) {
			$data['title']['raw'] = $post->post_title;
		}
		if ( rest_is_field_included( 'title.rendered', $fields ) ) {
			add_filter( 'protected_title_format', array( $this, 'protected_title_format' ) );

			$data['title']['rendered'] = get_the_title( $post->ID );

			remove_filter( 'protected_title_format', array( $this, 'protected_title_format' ) );
		}

		$has_password_filter = false;

		if ( $this->can_access_password_content( $post, $request ) ) {
			// Allow access to the post, permissions already checked before.
			add_filter( 'post_password_required', '__return_false' );

			$has_password_filter = true;
		}

		if ( rest_is_field_included( 'content', $fields ) ) {
			$data['content'] = array();
		}
		if ( rest_is_field_included( 'content.raw', $fields ) ) {
			$data['content']['raw'] = $post->post_content;
		}
		if ( rest_is_field_included( 'content.rendered', $fields ) ) {
			/** This filter is documented in wp-includes/post-template.php */
			$data['content']['rendered'] = post_password_required( $post ) ? '' : apply_filters( 'the_content', $post->post_content );
		}
		if ( rest_is_field_included( 'content.protected', $fields ) ) {
			$data['content']['protected'] = (bool) $post->post_password;
		}
		if ( rest_is_field_included( 'content.block_version', $fields ) ) {
			$data['content']['block_version'] = block_version( $post->post_content );
		}

		if ( rest_is_field_included( 'excerpt', $fields ) ) {
			/** This filter is documented in wp-includes/post-template.php */
			$excerpt = apply_filters( 'get_the_excerpt', $post->post_excerpt, $post );

			/** This filter is documented in wp-includes/post-template.php */
			$excerpt = apply_filters( 'the_excerpt', $excerpt );

			$data['excerpt'] = array(
				'raw'       => $post->post_excerpt,
				'rendered'  => post_password_required( $post ) ? '' : $excerpt,
				'protected' => (bool) $post->post_password,
			);
		}

		if ( $has_password_filter ) {
			// Reset filter.
			remove_filter( 'post_password_required', '__return_false' );
		}

		if ( rest_is_field_included( 'author', $fields ) ) {
			$data['author'] = (int) $post->post_author;
		}

		if ( rest_is_field_included( 'featured_media', $fields ) ) {
			$data['featured_media'] = (int) get_post_thumbnail_id( $post->ID );
		}

		if ( rest_is_field_included( 'parent', $fields ) ) {
			$data['parent'] = (int) $post->post_parent;
		}

		if ( rest_is_field_included( 'menu_order', $fields ) ) {
			$data['menu_order'] = (int) $post->menu_order;
		}

		if ( rest_is_field_included( 'comment_status', $fields ) ) {
			$data['comment_status'] = $post->comment_status;
		}

		if ( rest_is_field_included( 'ping_status', $fields ) ) {
			$data['ping_status'] = $post->ping_status;
		}

		if ( rest_is_field_included( 'sticky', $fields ) ) {
			$data['sticky'] = is_sticky( $post->ID );
		}

		if ( rest_is_field_included( 'template', $fields ) ) {
			$template = get_page_template_slug( $post->ID );
			if ( $template ) {
				$data['template'] = $template;
			} else {
				$data['template'] = '';
			}
		}

		if ( rest_is_field_included( 'format', $fields ) ) {
			$data['format'] = get_post_format( $post->ID );

			// Fill in blank post format.
			if ( empty( $data['format'] ) ) {
				$data['format'] = 'standard';
			}
		}

		if ( rest_is_field_included( 'meta', $fields ) ) {
			$data['meta'] = $this->meta->get_value( $post->ID, $request );
		}

		$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );

		foreach ( $taxonomies as $taxonomy ) {
			$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

			if ( rest_is_field_included( $base, $fields ) ) {
				$terms         = get_the_terms( $post, $taxonomy->name );
				$data[ $base ] = $terms ? array_values( wp_list_pluck( $terms, 'term_id' ) ) : array();
			}
		}

		$post_type_obj = get_post_type_object( $post->post_type );
		if ( is_post_type_viewable( $post_type_obj ) && $post_type_obj->public ) {
			$permalink_template_requested = rest_is_field_included( 'permalink_template', $fields );
			$generated_slug_requested     = rest_is_field_included( 'generated_slug', $fields );

			if ( $permalink_template_requested || $generated_slug_requested ) {
				if ( ! function_exists( 'get_sample_permalink' ) ) {
					require_once ABSPATH . 'wp-admin/includes/post.php';
				}

				$sample_permalink = get_sample_permalink( $post->ID, $post->post_title, '' );

				if ( $permalink_template_requested ) {
					$data['permalink_template'] = $sample_permalink[0];
				}

				if ( $generated_slug_requested ) {
					$data['generated_slug'] = $sample_permalink[1];
				}
			}
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		// Wrap the data in a response object.
		$response = rest_ensure_response( $data );

		$links = $this->prepare_links( $post );
		$response->add_links( $links );

		if ( ! empty( $links['self']['href'] ) ) {
			$actions = $this->get_available_actions( $post, $request );

			$self = $links['self']['href'];

			foreach ( $actions as $rel ) {
				$response->add_link( $rel, $self );
			}
		}

		/**
		 * Filters the post data for a response.
		 *
		 * The dynamic portion of the hook name, `$this->post_type`, refers to the post type slug.
		 *
		 * @since 4.7.0
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param WP_Post          $post     Post object.
		 * @param WP_REST_Request  $request  Request object.
		 */
		return apply_filters( "kbs_rest_prepare_{$this->post_type}", $response, $post, $request );
	} // prepare_item_for_response

	/**
	 * Retrieve Link Description Objects that should be added to the Schema for the posts collection.
	 *
	 * @since	1.5
	 * @return	array
	 */
	protected function get_schema_links() {

		$href = rest_url( "{$this->namespace}/{$this->rest_base}/{id}" );

		$links = array();

		if ( 'attachment' !== $this->post_type ) {
			$links[] = array(
				'rel'          => 'https://api.w.org/action-publish',
				'title'        => __( 'The current user can publish this post.', 'kb-support' ),
				'href'         => $href,
				'targetSchema' => array(
					'type'       => 'object',
					'properties' => array(
						'status' => array(
							'type' => 'string',
							'enum' => array( 'publish', 'future' ),
						),
					),
				),
			);
		}

		$links[] = array(
			'rel'          => 'https://api.w.org/action-unfiltered-html',
			'title'        => __( 'The current user can post unfiltered HTML markup and JavaScript.', 'kb-support' ),
			'href'         => $href,
			'targetSchema' => array(
				'type'       => 'object',
				'properties' => array(
					'content' => array(
						'raw' => array(
							'type' => 'string',
						),
					),
				),
			),
		);

		if ( post_type_supports( $this->post_type, 'author' ) ) {
			$links[] = array(
				'rel'          => 'https://api.w.org/action-assign-author',
				'title'        => __( 'The current user can change the author on this post.', 'kb-support' ),
				'href'         => $href,
				'targetSchema' => array(
					'type'       => 'object',
					'properties' => array(
						'author' => array(
							'type' => 'integer',
						),
					),
				),
			);
		}

		$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );

		foreach ( $taxonomies as $tax ) {
			$tax_base = ! empty( $tax->rest_base ) ? $tax->rest_base : $tax->name;

			/* translators: %s: Taxonomy name. */
			$assign_title = sprintf( __( 'The current user can assign terms in the %s taxonomy.', 'kb-support' ), $tax->name );
			/* translators: %s: Taxonomy name. */
			$create_title = sprintf( __( 'The current user can create terms in the %s taxonomy.', 'kb-support' ), $tax->name );

			$links[] = array(
				'rel'          => 'https://api.w.org/action-assign-' . $tax_base,
				'title'        => $assign_title,
				'href'         => $href,
				'targetSchema' => array(
					'type'       => 'object',
					'properties' => array(
						$tax_base => array(
							'type'  => 'array',
							'items' => array(
								'type' => 'integer',
							),
						),
					),
				),
			);

			$links[] = array(
				'rel'          => 'https://api.w.org/action-create-' . $tax_base,
				'title'        => $create_title,
				'href'         => $href,
				'targetSchema' => array(
					'type'       => 'object',
					'properties' => array(
						$tax_base => array(
							'type'  => 'array',
							'items' => array(
								'type' => 'integer',
							),
						),
					),
				),
			);
		}

		return $links;
	}

	/**
	 * Retrieves the query params for the articles collection.
	 *
	 * @since	1.5
	 * @return	array	Collection parameters
	 */
	public function get_collection_params() {
		$singular     = kbs_get_article_label_singular();
		$plural       = kbs_get_article_label_plural();
		$query_params = parent::get_collection_params();

		$query_params['orderby'] = array(
			'description' => __( 'Sort collection by object attribute.', 'kb-support' ),
			'type'        => 'string',
			'default'     => 'id',
			'enum'        => array(
				'author',
				'date',
				'id',
				'include',
				'modified',
				'parent',
				'relevance',
				'slug',
				'include_slugs',
				'title',
				'views',
				'views_month'
			)
		);

		$query_params['restricted'] = array(
			'default'     => 'null',
			'description' => sprintf(
				__( 'Limit result set to restricted %s.', 'kb-support' ),
				strtolower( $plural )
			)
		);

		$post_type = get_post_type_object( $this->post_type );

		/**
		 * Filter collection parameters for the articles controller.
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
			 * @since	1.5
			 * @param	string	$value	The query_var value.
			 */
			$query_args[ $key ] = apply_filters( "kbs_rest_query_var-{$key}", $value ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		}

		$query_args['ignore_sticky_posts'] = true;

		// Map to proper WP_Query orderby param.
		if ( isset( $query_args['orderby'] ) && isset( $request['orderby'] ) ) {
			$orderby_mappings = array(
				'id'            => 'ID',
				'include'       => 'post__in',
				'slug'          => 'post_name',
				'include_slugs' => 'post_name__in'
			);

			if ( isset( $orderby_mappings[ $request['orderby'] ] ) ) {
				$query_args['orderby'] = $orderby_mappings[ $request['orderby'] ];
			}
		}

		if ( isset( $request['orderby'] ) )	{
			if ( 'views' == $request['orderby'] || 'views_month' == $request['orderby'] )	{
				if ( 'views' == $request['orderby'] )	{
					$views_key = kbs_get_article_view_count_meta_key_name();
				} else	{
					$views_key = kbs_get_article_view_count_meta_key_name( false );
				}

				$query_args['meta_key'] = $views_key;
				$query_args['orderby']  = 'meta_value_num';
			}
		}

		return $query_args;
	} // prepare_items_query

	/**
	 * Adds the schema from additional fields to a schema array.
	 *
	 * The type of object is inferred from the passed schema.
	 *
	 * @since	1.5
	 * @param	array	$schema	Schema array.
	 * @return	array	Modified Schema array.
	 */
	protected function add_additional_fields_schema( $schema ) {
		if ( empty( $schema['title'] ) ) {
			return $schema;
		}

		// Can't use $this->get_object_type otherwise we cause an inf loop.
		$object_type = $schema['title'];

		$additional_fields = $this->get_additional_fields( $object_type );

		foreach ( $additional_fields as $field_name => $field_options ) {
			if ( ! $field_options['schema'] ) {
				continue;
			}

			$schema['properties'][ $field_name ] = $field_options['schema'];
		}

		return $schema;
	} // add_additional_fields_schema

	/**
	 * Prepares links for the request.
	 *
	 * @since	1.5
	 * @param	WP_Post	$post	Post object.
	 * @return	array	Links for the given post.
	 */
	protected function prepare_links( $post ) {
		$base = sprintf( '%s/%s', $this->namespace . $this->version, $this->rest_base );

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( trailingslashit( $base ) . $post->ID ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
			'about'      => array(
				'href' => rest_url( 'wp/v2/types/' . $this->post_type ),
			),
		);

		if ( ( in_array( $post->post_type, array( 'post', 'page' ), true ) || post_type_supports( $post->post_type, 'author' ) )
			&& ! empty( $post->post_author ) ) {
			$links['author'] = array(
				'href'       => rest_url( 'wp/v2/users/' . $post->post_author ),
				'embeddable' => true,
			);
		}

		if ( in_array( $post->post_type, array( 'post', 'page' ), true ) || post_type_supports( $post->post_type, 'comments' ) ) {
			$replies_url = rest_url( 'wp/v2/comments' );
			$replies_url = add_query_arg( 'post', $post->ID, $replies_url );

			$links['replies'] = array(
				'href'       => $replies_url,
				'embeddable' => true,
			);
		}

		if ( in_array( $post->post_type, array( 'post', 'page' ), true ) || post_type_supports( $post->post_type, 'revisions' ) ) {
			$revisions       = wp_get_post_revisions( $post->ID, array( 'fields' => 'ids' ) );
			$revisions_count = count( $revisions );

			$links['version-history'] = array(
				'href'  => rest_url( trailingslashit( $base ) . $post->ID . '/revisions' ),
				'count' => $revisions_count,
			);

			if ( $revisions_count > 0 ) {
				$last_revision = array_shift( $revisions );

				$links['predecessor-version'] = array(
					'href' => rest_url( trailingslashit( $base ) . $post->ID . '/revisions/' . $last_revision ),
					'id'   => $last_revision,
				);
			}
		}

		$post_type_obj = get_post_type_object( $post->post_type );

		if ( $post_type_obj->hierarchical && ! empty( $post->post_parent ) ) {
			$links['up'] = array(
				'href'       => rest_url( trailingslashit( $base ) . (int) $post->post_parent ),
				'embeddable' => true,
			);
		}

		// If we have a featured media, add that.
		$featured_media = get_post_thumbnail_id( $post->ID );
		if ( $featured_media ) {
			$image_url = rest_url( 'wp/v2/media/' . $featured_media );

			$links['https://api.w.org/featuredmedia'] = array(
				'href'       => $image_url,
				'embeddable' => true,
			);
		}

		if ( ! in_array( $post->post_type, array( 'attachment', 'nav_menu_item', 'revision' ), true ) ) {
			$attachments_url = rest_url( 'wp/v2/media' );
			$attachments_url = add_query_arg( 'parent', $post->ID, $attachments_url );

			$links['https://api.w.org/attachment'] = array(
				'href' => $attachments_url,
			);
		}

		$taxonomies = get_object_taxonomies( $post->post_type );

		if ( ! empty( $taxonomies ) ) {
			$links['https://api.w.org/term'] = array();

			foreach ( $taxonomies as $tax ) {
				$taxonomy_obj = get_taxonomy( $tax );

				// Skip taxonomies that are not public.
				if ( empty( $taxonomy_obj->show_in_rest ) ) {
					continue;
				}

				$tax_base = ! empty( $taxonomy_obj->rest_base ) ? $taxonomy_obj->rest_base : $tax;

				$terms_url = add_query_arg(
					'post',
					$post->ID,
					rest_url( 'wp/v2/' . $tax_base )
				);

				$links['https://api.w.org/term'][] = array(
					'href'       => $terms_url,
					'taxonomy'   => $tax,
					'embeddable' => true,
				);
			}
		}

		return $links;
	} // prepare_links

	/**
	 * Get the link relations available for the post and current user.
	 *
	 * @since 4.9.8
	 *
	 * @param WP_Post         $post    Post object.
	 * @param WP_REST_Request $request Request object.
	 * @return array List of link relations.
	 */
	protected function get_available_actions( $post, $request ) {

		if ( 'edit' !== $request['context'] ) {
			return array();
		}

		$rels = array();

		$post_type = get_post_type_object( $post->post_type );

		if ( 'attachment' !== $this->post_type && current_user_can( $post_type->cap->publish_posts ) ) {
			$rels[] = 'https://api.w.org/action-publish';
		}

		if ( current_user_can( 'unfiltered_html' ) ) {
			$rels[] = 'https://api.w.org/action-unfiltered-html';
		}

		if ( 'post' === $post_type->name ) {
			if ( current_user_can( $post_type->cap->edit_others_posts ) && current_user_can( $post_type->cap->publish_posts ) ) {
				$rels[] = 'https://api.w.org/action-sticky';
			}
		}

		if ( post_type_supports( $post_type->name, 'author' ) ) {
			if ( current_user_can( $post_type->cap->edit_others_posts ) ) {
				$rels[] = 'https://api.w.org/action-assign-author';
			}
		}

		$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );

		foreach ( $taxonomies as $tax ) {
			$tax_base   = ! empty( $tax->rest_base ) ? $tax->rest_base : $tax->name;
			$create_cap = is_taxonomy_hierarchical( $tax->name ) ? $tax->cap->edit_terms : $tax->cap->assign_terms;

			if ( current_user_can( $create_cap ) ) {
				$rels[] = 'https://api.w.org/action-create-' . $tax_base;
			}

			if ( current_user_can( $tax->cap->assign_terms ) ) {
				$rels[] = 'https://api.w.org/action-assign-' . $tax_base;
			}
		}

		return $rels;
	}

	/**
	 * Retrieves the item's schema, conforming to JSON Schema.
	 *
	 * @since	1.5
	 * @return	array	Item schema data.
	 */
	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->add_additional_fields_schema( $this->schema );
		}

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->post_type,
			'type'       => 'object',
			// Base properties for every Post.
			'properties' => array(
				'date'         => array(
					'description' => __( "The date the object was published, in the site's timezone.", 'kb-support' ),
					'type'        => array( 'string', 'null' ),
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'date_gmt'     => array(
					'description' => __( 'The date the object was published, as GMT.', 'kb-support' ),
					'type'        => array( 'string', 'null' ),
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
				),
				'guid'         => array(
					'description' => __( 'The globally unique identifier for the object.', 'kb-support' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
					'properties'  => array(
						'raw'      => array(
							'description' => __( 'GUID for the object, as it exists in the database.', 'kb-support' ),
							'type'        => 'string',
							'context'     => array( 'edit' ),
							'readonly'    => true,
						),
						'rendered' => array(
							'description' => __( 'GUID for the object, transformed for display.', 'kb-support' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
							'readonly'    => true,
						),
					),
				),
				'id'           => array(
					'description' => __( 'Unique identifier for the object.', 'kb-support' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'link'         => array(
					'description' => __( 'URL to the object.', 'kb-support' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'modified'     => array(
					'description' => __( "The date the object was last modified, in the site's timezone.", 'kb-support' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'modified_gmt' => array(
					'description' => __( 'The date the object was last modified, as GMT.', 'kb-support' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'slug'         => array(
					'description' => __( 'An alphanumeric identifier for the object unique to its type.', 'kb-support' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'arg_options' => array(
						'sanitize_callback' => array( $this, 'sanitize_slug' ),
					),
				),
				'status'       => array(
					'description' => __( 'A named status for the object.', 'kb-support' ),
					'type'        => 'string',
					'enum'        => array_keys( get_post_stati( array( 'internal' => false ) ) ),
					'context'     => array( 'view', 'edit' ),
				),
				'type'         => array(
					'description' => __( 'Type of Post for the object.', 'kb-support' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'password'     => array(
					'description' => __( 'A password to protect access to the content and excerpt.', 'kb-support' ),
					'type'        => 'string',
					'context'     => array( 'edit' ),
				),
			),
		);

		$post_type_obj = get_post_type_object( $this->post_type );
		if ( is_post_type_viewable( $post_type_obj ) && $post_type_obj->public ) {
			$schema['properties']['permalink_template'] = array(
				'description' => __( 'Permalink template for the object.', 'kb-support' ),
				'type'        => 'string',
				'context'     => array( 'edit' ),
				'readonly'    => true,
			);

			$schema['properties']['generated_slug'] = array(
				'description' => __( 'Slug automatically generated from the object title.', 'kb-support' ),
				'type'        => 'string',
				'context'     => array( 'edit' ),
				'readonly'    => true,
			);
		}

		if ( $post_type_obj->hierarchical ) {
			$schema['properties']['parent'] = array(
				'description' => __( 'The ID for the parent of the object.', 'kb-support' ),
				'type'        => 'integer',
				'context'     => array( 'view', 'edit' ),
			);
		}

		$post_type_attributes = array(
			'title',
			'editor',
			'author',
			'excerpt',
			'thumbnail',
			'comments',
			'revisions',
			'page-attributes',
			'post-formats',
			'custom-fields',
		);
		$fixed_schemas        = array(
			'kbs_article' => array(
				'title',
				'editor',
				'author',
				'excerpt',
				'thumbnail',
				'comments',
				'revisions',
				'post-formats',
				'custom-fields'
			)
		);
		foreach ( $post_type_attributes as $attribute ) {
			if ( isset( $fixed_schemas[ $this->post_type ] ) && ! in_array( $attribute, $fixed_schemas[ $this->post_type ], true ) ) {
				continue;
			} elseif ( ! isset( $fixed_schemas[ $this->post_type ] ) && ! post_type_supports( $this->post_type, $attribute ) ) {
				continue;
			}

			switch ( $attribute ) {

				case 'title':
					$schema['properties']['title'] = array(
						'description' => __( 'The title for the object.', 'kb-support' ),
						'type'        => 'object',
						'context'     => array( 'view', 'edit', 'embed' ),
						'arg_options' => array(
							'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_item_for_database().
							'validate_callback' => null, // Note: validation implemented in self::prepare_item_for_database().
						),
						'properties'  => array(
							'raw'      => array(
								'description' => __( 'Title for the object, as it exists in the database.' , 'kb-support' ),
								'type'        => 'string',
								'context'     => array( 'edit' ),
							),
							'rendered' => array(
								'description' => __( 'HTML title for the object, transformed for display.' , 'kb-support' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit', 'embed' ),
								'readonly'    => true,
							),
						),
					);
					break;

				case 'editor':
					$schema['properties']['content'] = array(
						'description' => __( 'The content for the object.', 'kb-support' ),
						'type'        => 'object',
						'context'     => array( 'view', 'edit' ),
						'arg_options' => array(
							'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_item_for_database().
							'validate_callback' => null, // Note: validation implemented in self::prepare_item_for_database().
						),
						'properties'  => array(
							'raw'           => array(
								'description' => __( 'Content for the object, as it exists in the database.', 'kb-support' ),
								'type'        => 'string',
								'context'     => array( 'edit' ),
							),
							'rendered'      => array(
								'description' => __( 'HTML content for the object, transformed for display.', 'kb-support' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'block_version' => array(
								'description' => __( 'Version of the content block format used by the object.', 'kb-support' ),
								'type'        => 'integer',
								'context'     => array( 'edit' ),
								'readonly'    => true,
							),
							'protected'     => array(
								'description' => __( 'Whether the content is protected with a password.', 'kb-support' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit', 'embed' ),
								'readonly'    => true,
							),
						),
					);
					break;

				case 'author':
					$schema['properties']['author'] = array(
						'description' => __( 'The ID for the author of the object.', 'kb-support' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit', 'embed' ),
					);
					break;

				case 'excerpt':
					$schema['properties']['excerpt'] = array(
						'description' => __( 'The excerpt for the object.', 'kb-support' ),
						'type'        => 'object',
						'context'     => array( 'view', 'edit', 'embed' ),
						'arg_options' => array(
							'sanitize_callback' => null, // Note: sanitization implemented in self::prepare_item_for_database().
							'validate_callback' => null, // Note: validation implemented in self::prepare_item_for_database().
						),
						'properties'  => array(
							'raw'       => array(
								'description' => __( 'Excerpt for the object, as it exists in the database.', 'kb-support' ),
								'type'        => 'string',
								'context'     => array( 'edit' ),
							),
							'rendered'  => array(
								'description' => __( 'HTML excerpt for the object, transformed for display.', 'kb-support' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit', 'embed' ),
								'readonly'    => true,
							),
							'protected' => array(
								'description' => __( 'Whether the excerpt is protected with a password.', 'kb-support' ),
								'type'        => 'boolean',
								'context'     => array( 'view', 'edit', 'embed' ),
								'readonly'    => true,
							),
						),
					);
					break;

				case 'thumbnail':
					$schema['properties']['featured_media'] = array(
						'description' => __( 'The ID of the featured media for the object.', 'kb-support' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit', 'embed' ),
					);
					break;

				case 'comments':
					$schema['properties']['comment_status'] = array(
						'description' => __( 'Whether or not comments are open on the object.', 'kb-support' ),
						'type'        => 'string',
						'enum'        => array( 'open', 'closed' ),
						'context'     => array( 'view', 'edit' ),
					);
					$schema['properties']['ping_status']    = array(
						'description' => __( 'Whether or not the object can be pinged.', 'kb-support' ),
						'type'        => 'string',
						'enum'        => array( 'open', 'closed' ),
						'context'     => array( 'view', 'edit' ),
					);
					break;

				case 'page-attributes':
					$schema['properties']['menu_order'] = array(
						'description' => __( 'The order of the object in relation to other object of its type.', 'kb-support' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit' ),
					);
					break;

				case 'post-formats':
					// Get the native post formats and remove the array keys.
					$formats = array_values( get_post_format_slugs() );

					$schema['properties']['format'] = array(
						'description' => __( 'The format for the object.', 'kb-support' ),
						'type'        => 'string',
						'enum'        => $formats,
						'context'     => array( 'view', 'edit' ),
					);
					break;

				case 'custom-fields':
					$schema['properties']['meta'] = $this->meta->get_field_schema();
					break;

			}
		}

		$schema['properties']['template'] = array(
			'description' => __( 'The theme file to use to display the object.', 'kb-support' ),
			'type'        => 'string',
			'context'     => array( 'view', 'edit' ),
			'arg_options' => array(
				'validate_callback' => array( $this, 'check_template' ),
			),
		);

		$taxonomies = wp_list_filter( get_object_taxonomies( $this->post_type, 'objects' ), array( 'show_in_rest' => true ) );

		foreach ( $taxonomies as $taxonomy ) {
			$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

			if ( array_key_exists( $base, $schema['properties'] ) ) {
				$taxonomy_field_name_with_conflict = ! empty( $taxonomy->rest_base ) ? 'rest_base' : 'name';
				_doing_it_wrong(
					'register_taxonomy',
					sprintf(
						/* translators: 1. The taxonomy name, 2. The property name, either 'rest_base' or 'name', 3. The conflicting value. */
						__( 'The "%1$s" taxonomy "%2$s" property (%3$s) conflicts with an existing property on the REST API Posts Controller. Specify a custom "rest_base" when registering the taxonomy to avoid this error.', 'kb-support' ),
						$taxonomy->name,
						$taxonomy_field_name_with_conflict,
						$base
					),
					'5.4.0'
				);
			}

			$schema['properties'][ $base ] = array(
				/* translators: %s: Taxonomy name. */
				'description' => sprintf( __( 'The terms assigned to the object in the %s taxonomy.', 'kb-support' ),$taxonomy->name ),
				'type'        => 'array',
				'items'       => array(
					'type' => 'integer',
				),
				'context'     => array( 'view', 'edit' ),
			);
		}

		$schema_links = $this->get_schema_links();

		if ( $schema_links ) {
			$schema['links'] = $schema_links;
		}

		// Take a snapshot of which fields are in the schema pre-filtering.
		$schema_fields = array_keys( $schema['properties'] );

		/**
		 * Filter the post's schema.
		 *
		 * The dynamic portion of the filter, `$this->post_type`, refers to the
		 * post type slug for the controller.
		 *
		 * @since 5.4.0
		 *
		 * @param array $schema Item schema data.
		 */
		$schema = apply_filters( "kbs_rest_{$this->post_type}_item_schema", $schema );

		// Emit a _doing_it_wrong warning if user tries to add new properties using this filter.
		$new_fields = array_diff( array_keys( $schema['properties'] ), $schema_fields );
		if ( count( $new_fields ) > 0 ) {
			_doing_it_wrong( __METHOD__, __( 'Please use register_rest_field to add new schema properties.', 'kb-support' ), '5.4.0' );
		}

		$this->schema = $schema;

		return $this->add_additional_fields_schema( $this->schema );
	} // get_item_schema

	/**
	 * Retrieves the item's schema for display / public consumption purposes.
	 *
	 * @since	1.5
	 * @return	array	Public item schema data.
	 */
	public function get_public_item_schema() {

		$schema = $this->get_item_schema();

		if ( ! empty( $schema['properties'] ) ) {
			foreach ( $schema['properties'] as &$property ) {
				unset( $property['arg_options'] );
			}
		}

		return $schema;
	} // get_public_item_schema

} // KBS_Articles_API
