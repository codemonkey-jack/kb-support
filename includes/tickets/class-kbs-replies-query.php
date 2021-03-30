<?php
/**
 * KBS Replies Query
 *
 * @package     KBS
 * @subpackage  Classes/Stats
 * @copyright   Copyright (c) 2017, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.2
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * KBS_Replies_Query Class
 *
 * This class is for retrieving ticket replies data
 *
 * Replies can be retrieved for date ranges and pre-defined periods
 *
 * @since	1.2
 */
class KBS_Replies_Query extends KBS_Stats {

	/**
	 * The args to pass to the kbs_get_replies() query
	 *
	 * @var		array
	 * @access	public
	 * @since	1.2
	 */
	public $args = array();

	/**
	 * The replies found based on the criteria set
	 *
	 * @var		array|false
	 * @access	public
	 * @since	1.2
	 */
	public $replies = 0;

	/**
	 * The count of replies found based on the criteria set
	 *
	 * @var		int
	 * @access	public
	 * @since	1.2
	 */
	public $total_replies = 0;

	/**
	 * The total count of pages
	 *
	 * @var		int
	 * @access	public
	 * @since	1.2
	 */
	public $pages = 0;

	/**
	 * The current page
	 *
	 * @var		int
	 * @access	public
	 * @since	1.2
	 */
	public $current_page = 0;

	/**
	 * Default query arguments.
	 *
	 * Not all of these are valid arguments that can be passed to WP_Query. The ones that are not, are modified before
	 * the query is run to convert them to the proper syntax.
	 *
	 * @access	public
	 * @since	1.2
	 * @param	arr		$args	The array of arguments that can be passed in and used for setting up this reply query.
	 */
	public function __construct( $args = array() ) {
		$defaults = array(
			'post_type'  => 'kbs_ticket_reply',
			'ticket_id'  => null,
			'start_date' => false,
			'end_date'   => false,
			'author'     => null,
			'number'     => -1,
			'page'       => null,
			'orderby'    => 'date',
			'order'      => 'DESC',
			'status'     => 'publish',
			'meta_key'   => null,
			'year'       => null,
			'month'      => null,
			'day'        => null,
			's'          => null,
			'fields'     => null
		);

		$this->args = wp_parse_args( $args, $defaults );
		$this->args = apply_filters( 'kbs_get_replies_args', $this->args );

		$this->init();
	} // __construct

	/**
	 * Set a query variable.
	 *
	 * @access	public
	 * @since	1.2
	 */
	public function __set( $query_var, $value ) {
		if ( in_array( $query_var, array( 'meta_query', 'tax_query' ) ) )	{
			$this->args[ $query_var ][] = $value;
		} else	{
			$this->args[ $query_var ] = $value;
		}
	} // __set

	/**
	 * Unset a query variable.
	 *
	 * @access	public
	 * @since	1.2
	 */
	public function __unset( $query_var ) {
		unset( $this->args[ $query_var ] );
	} // __unset

	/**
	 * Modify the query/query arguments before we retrieve replies.
	 *
	 * @access	public
	 * @since	1.2
	 * @return	void
	 */
	public function init() {
		add_action( 'kbs_pre_get_replies',  array( $this, 'date_filter_pre'  ) );
		add_action( 'kbs_post_get_replies', array( $this, 'date_filter_post' ) );

		add_action( 'kbs_pre_get_replies',  array( $this, 'post_parent' ) );
		add_action( 'kbs_pre_get_replies',  array( $this, 'orderby'     ) );
		add_action( 'kbs_pre_get_replies',  array( $this, 'status'      ) );
		add_action( 'kbs_pre_get_replies',  array( $this, 'month'       ) );
		add_action( 'kbs_pre_get_replies',  array( $this, 'per_page'    ) );
		add_action( 'kbs_pre_get_replies',  array( $this, 'page'        ) );
		add_action( 'kbs_pre_get_replies',  array( $this, 'replies'     ) );
		add_action( 'kbs_pre_get_replies',  array( $this, 'author'      ) );
		add_action( 'kbs_pre_get_replies',  array( $this, 'search'      ) );
	} // init

	/**
	 * Retrieve replies.
	 *
	 * The query can be modified in two ways; either the action before the
	 * query is run, or the filter on the arguments (existing mainly for backwards
	 * compatibility).
	 *
	 * @access	public
	 * @since	1.2
	 * @return	obj
	 */
	public function get_replies() {
		do_action( 'kbs_pre_get_replies', $this );

		$query = new WP_Query( $this->args );

		$this->total_replies = (int) $query->found_posts;
		$this->pages         = (int) $query->max_num_pages;
		$this->replies       = $query->posts;

		do_action( 'kbs_post_get_replies', $this );

		return $this->replies;
	} // get_replies

	/**
	 * If querying a specific date, add the proper filters.
	 *
	 * @access	public
	 * @since	1.2
	 * @return	void
	 */
	public function date_filter_pre() {
		if( ! ( $this->args['start_date'] || $this->args['end_date'] ) ) {
			return;
		}

		$this->setup_dates( $this->args['start_date'], $this->args['end_date'] );

		add_filter( 'posts_where', array( $this, 'posts_where' ) );
	} // date_filter_pre

	/**
	 * If querying a specific date, remove filters after the query has been run
	 * to avoid affecting future queries.
	 *
	 * @access	public
	 * @since	1.2
	 * @return	void
	 */
	public function date_filter_post() {
		if ( ! $this->args['start_date'] || ! $this->args['end_date'] ) {
			return;
		}

		remove_filter( 'posts_where', array( $this, 'posts_where' ) );
	} // date_filter_post

	/**
	 * Post Status
	 *
	 * @access	public
	 * @since	1.2
	 * @return	void
	 */
	public function status() {
		if ( ! isset ( $this->args['status'] ) ) {
			return;
		}

		$this->__set( 'post_status', $this->args['status'] );
		$this->__unset( 'status' );
	} // status

	/**
	 * Post Parent
	 *
	 * @access	public
	 * @since	1.2
	 * @return	void
	 */
	public function post_parent() {
		if ( ! isset ( $this->args['ticket_id'] ) ) {
			return;
		}

		$this->__set( 'post_parent', $this->args['ticket_id'] );
		$this->__unset( 'ticket_id' );
	} // post_parent

	/**
	 * Current Page
	 *
	 * @access	public
	 * @since	1.2
	 * @return	void
	 */
	public function page() {
		if ( ! isset ( $this->args['page'] ) ) {
			return;
		}

		$this->__set( 'paged', $this->args['page'] );
		$this->__unset( 'page' );
	} // page

	/**
	 * Posts Per Page
	 *
	 * @access	public
	 * @since	1.2
	 * @return	void
	 */
	public function per_page() {

		if ( ! isset( $this->args['number'] ) ){
			return;
		}

		if ( $this->args['number'] == -1 ) {
			$this->__set( 'nopaging', true );
		} else {
			$this->__set( 'posts_per_page', $this->args['number'] );
		}

		$this->__unset( 'number' );
	} // per_page

	/**
	 * Current Month
	 *
	 * @access	public
	 * @since	1.2
	 * @return	void
	 */
	public function month() {
		if ( ! isset ( $this->args['month'] ) ) {
			return;
		}

		$this->__set( 'monthnum', $this->args['month'] );
		$this->__unset( 'month' );
	} // month

	/**
	 * Order by
	 *
	 * @access	public
	 * @since	1.2
	 * @return	void
	 */
	public function orderby() {
		switch ( $this->args['orderby'] ) {
			case 'author':
			case 'date':
			case 'modified':
			case 'name':
			case 'rand':
			case 'relevance':
			case 'title':
			default:
				$this->__set( 'orderby', $this->args['orderby'] );
				break;
		}
	} // orderby

	/**
	 * Replies
	 *
	 * @access	public
	 * @since	1.2
	 * @return	void
	 */
	public function replies() {
		if ( ! isset ( $this->args['replies'] ) ) {
			return;
		}

		if ( ! is_array( $this->args['replies'] ) )	{
			$this->args['replies'] = explode( ',', $this->args['replies'] );
		}

		$this->__set( 'post__in', $this->args['replies'] );
		$this->__unset( 'replies' );
	} // replies

	/**
	 * Author
	 *
	 * @access	public
	 * @since	1.2
	 * @return	void
	 */
	public function author() {
		if ( ! isset ( $this->args['author'] ) ) {
			return;
		}

		$this->__unset( 'author' );
	} // author

	/**
	 * Search
	 *
	 * @access	public
	 * @since	1.2
	 * @return	void
	 */
	public function search() {

		if ( ! isset( $this->args['s'] ) ) {
			return;
		}

		$search = trim( $this->args['s'] );

		if ( empty( $search ) ) {
			return;
		}

		if ( is_numeric( $search ) ) {

			$post = get_post( $search );

			if ( is_object( $post ) && $post->post_type == 'kbs_ticket_reply' ) {

				$arr   = array();
				$arr[] = $search;
				$this->__set( 'post__in', $arr );
				$this->__unset( 's' );
			}

		} else {
			$this->__set( 's', $search );
		}

	} // search

	/**
	 * Order posts by relevance.
	 *
	 * @access	public
	 * @since	1.2
	 * @return	void
	 */
	public function orderby_relevance()	{
		if ( empty( $this->posts ) )	{

		}
	} // orderby_relevance

	/**
	 *  Get non-read replies
	 *
	 * @return array
	 * @since 1.6.0
	 */
	public static function get_non_read_replies(){

		$args = array(
			'post_type'  => 'kbs_ticket_reply',
			'number'     => -1,
			'orderby'    => 'date',
			'order'      => 'DESC',
			'status'     => 'publish',
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => '_kbs_reply_customer_read',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_kbs_reply_customer_id',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => '_kbs_reply_agent_id',
					'compare' => 'EXISTS',
				)
			)
		);

		$replies       = new WP_Query( $args );
		$empty_replies = array();

		if ( $replies ){
			while ( $replies->have_posts() ){
				$replies->the_post();
				$empty_replies[ 'ticket-' . get_post_parent()->ID ][] = get_the_ID();
			}
		}

		wp_reset_postdata();

		return $empty_replies;

	}

} // KBS_Replies_Query
