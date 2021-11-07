<?php
/**
 * KB Support REST API
 *
 * @package     KBS
 * @subpackage  Classes/REST API
 * @copyright   Copyright (c) 2020, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.5
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * KBS_API Class
 *
 * @since	1.0.8
 */
class KBS_API extends WP_REST_Controller {
	/**
	 * Version
	 *
	 * @since	1.5
	 * @var		int
	 */
	protected $version = '1';
    /**
     * Namespace
     *
     * @since   1.5
     * @var     string
     */
    protected $namespace = 'kbs/v';

    /**
     * ID of user performing the API request.
     *
     * @since   1.5
     * @var     int
     */
    public $user_id = 0;

    /**
	 * Get things going
	 *
	 * @since	1.5
	 */
	public function __construct()	{} // __construct

	/**
	 * Magic GET function.
	 *
	 * @since	1.5
	 * @param	string	$key	The property to retrieve
	 * @return	mixed	The value retrieved
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
     * Checks if a given request has access to read an object.
     *
     * @since   1.5
     * @param	WP_REST_Request	$request	Full details about the request
	 * @return	bool|WP_Error	True if the request has read access for the item, WP_Error object otherwise
     */
    public function get_item_permissions_check( $request ) {
		if ( ! $this->is_authenticated() )	{
			return new WP_Error(
				'rest_forbidden_context',
				$this->errors( 'no_auth' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
    } // get_item_permissions_check

	/**
	 * Get the post, if the ID is valid.
	 *
	 * @since	1.5
	 * @param	int					$id	Supplied ID.
	 * @return	WP_Post|WP_Error	Post object if ID is valid, WP_Error otherwise.
	 */
	protected function get_post( $id ) {
		$error = new WP_Error(
			'rest_post_invalid_id',
			__( 'Invalid post ID.', 'kb-support' ),
			array( 'status' => 404 )
		);

		if ( (int) $id <= 0 ) {
			return $error;
		}

		$post = get_post( (int) $id );
		if ( empty( $post ) || empty( $post->ID ) || $this->post_type !== $post->post_type ) {
			return $error;
		}

		return $post;
	} // get_post

	/**
	 * Checks the post_date_gmt or modified_gmt and prepare any post or
	 * modified date for single post output.
	 *
	 * @since	1.5
	 *
	 * @param	string		$date_gmt	GMT publication time.
	 * @param	string|null	$date		Optional. Local publication time. Default null.
	 * @return	string|null	ISO8601/RFC3339 formatted datetime.
	 */
	protected function prepare_date_response( $date_gmt, $date = null ) {
		// Use the date if passed.
		if ( isset( $date ) ) {
			return mysql_to_rfc3339( $date );
		}

		// Return null if $date_gmt is empty/zeros.
		if ( '0000-00-00 00:00:00' === $date_gmt ) {
			return null;
		}

		// Return the formatted datetime.
		return mysql_to_rfc3339( $date_gmt );
	} // prepare_date_response

	/**
	 * Whether or not the user is authenticated.
	 *
	 * This method also sets the request parameters.
	 *
	 * @since	1.5
	 * @return	bool
	 */
	public function is_authenticated()	{

		$this->user_id = apply_filters( 'determine_current_user', false );
		wp_set_current_user( $this->user_id );
        if ( is_user_logged_in() )  {
            $this->user_id = get_current_user_id();

            return true;
        }

		return false;
	} // is_authenticated

	/**
	 * Respond with error.
	 *
	 * @since	1.5
	 * @param	string	$error	Error key
	 * @return	string	Error message
	 */
	public function errors( $error )	{
		$errors = array(
			'no_auth'            => __( 'Authentication failed.', 'kb-support' ),
			'no_permission'      => __( 'Access denied.', 'kb-support' ),
			'required_fields'    => __( 'Required fields missing.', 'kb-support' ),
			'invalid_email'      => __( 'Invalid email address.', 'kb-support' ),
			'create_failed'      => sprintf(
				__( 'Unable to create %s.', 'kb-support' ),
				kbs_get_article_label_singular( true )
			),
            'ticket_not_found'   => sprintf(
				__( '% not found.', 'kb-support' ),
				kbs_get_article_label_singular()
			),
			'restricted_article' => sprintf(
				__( 'Criteria not met to access restricted %s.', 'kb-support' ),
				kbs_get_article_label_singular( true )
			)
		);

		$errors = apply_filters( 'kbs_api_errors', $errors );

		$errors['no_data'] = __( 'No data.', 'kb-support' );

		if ( array_key_exists( $error, $errors ) )	{
			return $errors[ $error ];
		}

		return $errors['no_data'];
	} // errors

} // KBS_API
