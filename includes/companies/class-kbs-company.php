<?php
/**
 * Company Object
 *
 * @package     KBS
 * @subpackage  Classes/Company
 * @copyright   Copyright (c) 2017, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * KBS_Company Class
 *
 * @since	1.0
 */
class KBS_Company {

	/**
	 * The company ID
	 *
	 * @since 1.0
	 */
	public $ID = 0;

	/**
	 * The company name
	 *
	 * @since 1.0
	 */
	public $name;

	/**
	 * The customer iD
	 *
	 * @since 1.2
	 */
	public $customer = 0;

	/**
	 * The company contact
	 *
	 * @since 1.0
	 */
	public $contact;

	/**
	 * The primary company email
	 *
	 * @since 1.0
	 */
	public $email;

	/**
	 * The company phone
	 *
	 * @since 1.0
	 */
	public $phone;

	/**
	 * The company website
	 *
	 * @since 1.0
	 */
	public $website;

	/**
	 * The company logo
	 *
	 * @since 1.0
	 */
	public $logo;

	/**
	 * The company creation date
	 *
	 * @since 1.0
	 */
	public $date_created;

	/**
	 * The company modified date
	 *
	 * @since 1.0
	 */
	public $modified_date;

	/**
	 * Get things going
	 *
	 * @since 1.0
	 */
	public function __construct( $company_id = false ) {
		if ( empty( $company_id ) ) {
			return false;
		}

		$this->setup_company( $company_id );
	} // __construct

	/**
	 * Given the company data, let's set the variables
	 *
	 * @since	1.0
	 * @param	int		$company_id		The Company ID
	 * @return 	bool	If the setup was successful or not
	 */
	private function setup_company( $company_id ) {

		if ( empty( $company_id ) ) {
			return false;
		}

		$company = get_post( $company_id );

		if ( ! $company || is_wp_error( $company ) ) {
			return false;
		}

		if ( 'kbs_company' !== $company->post_type ) {
			return false;
		}

		// Extensions can hook here perform actions before the company data is loaded
		do_action( 'kbs_pre_setup_company', $this, $company );

		// Primary Identifier
		$this->ID              = absint( $company->ID );

		// Dates
		$this->date_created    = $company->post_date;
		$this->modified_date   = $company->post_modified;

		// Company information
		$this->name     = $company->post_title;
		$this->customer = $this->get_meta( '_kbs_company_customer' );

		if ( ! empty( $this->customer ) )	{
			$customer = new KBS_Customer( $this->customer );
		}

		$this->contact  = ! empty( $customer->id ) ? $customer->name  : $this->get_meta( '_kbs_company_contact' );
		$this->email    = ! empty( $customer->id ) ? $customer->email : $this->get_meta( '_kbs_company_email' );
		$this->phone    = $this->get_meta( '_kbs_company_phone' );
		$this->logo     = get_the_post_thumbnail_url( $this->ID );
		$this->website  = $this->get_meta( '_kbs_company_website' );

		// Extensions can hook here to add items to this object
		do_action( 'kbs_setup_company', $this, $company_id );

		return true;

	} // setup_company

	/**
	 * Magic __get function to dispatch a call to retrieve a private property
	 *
	 * @since 1.0
	 */
	public function __get( $key ) {

		if ( method_exists( $this, 'get_' . $key ) ) {

			return call_user_func( array( $this, 'get_' . $key ) );

		} else {

			return new WP_Error( 'kbs-company-invalid-property', sprintf( __( "Can't get property %s", 'kb-support' ), $key ) );

		}

	} // __get

	/**
	 * Retrieve company meta field for a company.
	 *
	 * @param	str		$meta_key	The meta key to retrieve.
	 * @param	bool	$single		Whether to return a single value.
	 * @return	mixed	Will be an array if $single is false. Will be value of meta data field if $single is true.
	 *
	 * @access	public
	 * @since	1.0
	 */
	public function get_meta( $meta_key = '', $single = true ) {
		return get_post_meta( $this->ID, $meta_key, $single );
	} // get_meta

	/**
	 * Add meta data field to a company.
	 *
	 * @param	str		$meta_key		Metadata name.
	 * @param	mixed	$meta_value		Metadata value.
	 * @param	bool	$unique			Optional, default is false. Whether the same key should not be added.
	 * @return	bool	False for failure. True for success.
	 *
	 * @access	public
	 * @since	1.0
	 */
	public function add_meta( $meta_key, $meta_value, $unique = false ) {

		if ( empty( $meta_key ) || empty( $meta_value ) ) {
			return false;
		}
		return add_post_meta( $this->ID, $meta_key, $meta_value, $unique );
	} // add_meta

	/**
	 * Update company meta field based on company ID.
	 *
	 * @param	str		$meta_key		Metadata key.
	 * @param	mixed	$meta_value		Metadata value.
	 * @param	mixed	$prev_value		Optional. Previous value to check before removing.
	 * @return	bool	False on failure, true if success.
	 *
	 * @access	public
	 * @since	1.0
	 */
	public function update_meta( $meta_key, $meta_value, $prev_value = '' ) {

		if ( empty( $meta_key ) || empty( $meta_value ) ) {
			return false;
		}
		return update_post_meta( $this->ID, $meta_key, $meta_value, $prev_value );
	} // update_meta

	/**
	 * Remove metadata matching criteria from a company.
	 *
	 * @param	str		$meta_key		Metadata name.
	 * @param	mixed	$meta_value		Optional. Metadata value.
	 * @return	bool	False for failure. True for success.
	 *
	 * @access	public
	 * @since	1.0
	 */
	public function delete_meta( $meta_key = '', $meta_value = '' ) {
		return delete_post_meta( $this->ID, $meta_key, $meta_value );
	} // delete_meta

} // KBS_Company
