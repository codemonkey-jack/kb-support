<?php
/**
 * Functions for forms
 *
 * @package     KBS
 * @subpackage  Functions/Forms
 * @copyright   Copyright (c) 2017, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Whether or not a user can submit a form.
 *
 * @since	1.0
 * @param	int|obj	$form		The form ID or object.
 * @return	bool	True if the user can submit, otherwise false
 */
function kbs_user_can_submit( $form = 0 )	{
	if ( is_int( $form ) )	{
		$form = get_post( $form );
	}

	$can_submit = true;

	if ( kbs_user_must_be_logged_in() && ! is_user_logged_in() )	{
		$can_submit = false;
	}

	/**
	 * Allow plugins to filter the response.
	 *
	 * @since	1.0
	 */
	return apply_filters( 'kbs_user_can_submit', $can_submit, $form );
} // kbs_user_can_submit

/**
 * Determine if the current page is a ticket submission page.
 *
 * @since	1.0
 * @return	bool	True if submission page, or false.
 */
function kbs_is_submission_form()	{
	$is_submission = is_page( kbs_get_option( 'submission_page' ) );

	if ( ! $is_submission )	{
		global $post;

		if ( ! empty( $post ) )	{
			$is_submission = has_shortcode( $post->post_content, 'kbs_submit' );
		}
	}

	return apply_filters( 'kbs_is_submission_form', $is_submission );
} // kbs_is_submission_form

/**
 * Retrieve the ticket submission page.
 *
 * @since	1.0
 * @return	str		The URL to the submission page.
 */
function kbs_get_submission_page()	{
	$page = kbs_get_option( 'submission_page' );

	return apply_filters( 'kbs_submission_page', get_permalink( $page ) );
} // kbs_is_submission_form

/**
 * Determines if secure ticket submission pages are enforced.
 *
 * @since	1.0
 * @return	bool	True if enforce SSL is enabled, false otherwise
 */
function kbs_is_ssl_enforced() {
	$ssl_enforced = kbs_get_option( 'enforce_ssl', false );
	return (bool) apply_filters( 'kbs_is_ssl_enforced', $ssl_enforced );
} // kbs_is_ssl_enforced

/**
 * Handle redirections for SSL enforced ticket submissions.
 *
 * @since	1.0
 * @return	void
 */
function kbs_enforced_form_ssl_redirect_handler() {
	$submission_form = kbs_is_submission_form();

	if ( ! kbs_is_ssl_enforced() || ! $submission_form || is_admin() || is_ssl() ) {
		return;
	}

	if ( $submission_form && false !== strpos( kbs_get_current_page_url(), 'https://' ) ) {
		return;
	}

	$uri = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

	wp_safe_redirect( $uri );
	exit;
} // kbs_enforced_form_ssl_redirect_handler
add_action( 'template_redirect', 'kbs_enforced_form_ssl_redirect_handler' );

/**
 * Retrieve all forms.
 *
 * @since	1.0
 * @param	arr		$args	Arguments. See $defaults / WP_Query.
 * @return	obj		WP_Query Object
 */
function kbs_get_forms( $args = array() )	{

	$defaults = array(
		'post_type'         => 'kbs_form',
		'post_status'       => 'any',
		'posts_per_page'	=> -1
	);

	$args  = wp_parse_args( $args, $defaults );
	$forms = get_posts( $args );

	return $forms;
} // kbs_get_forms

/**
 * Returns an array of forms that has form ID as index and form title as value
 * Primarily used for selects
 *
 * @param array $args
 *
 * @return array
 * @since 1.5.6
 *
 */
function kbs_get_array_forms( $args = array() ){

	$defaults = array(
		'post_type'      => 'kbs_form',
		'post_status'    => 'any',
		'posts_per_page' => -1
	);

	$args  = wp_parse_args( $args, $defaults );
	$forms = get_posts( $args );

	$return = array(
		'none' => esc_html__( 'Select form', 'kb-support' )
	);

	foreach ( $forms as $form ){
		$return[ $form->ID ] = $form->post_title;
	}

	return $return;

}

/**
 * Retrieve a form.
 *
 * @since	1.0
 * @param	int		$form_id	Post ID.
 * @return	obj		WP_Post
 */
function kbs_get_form( $form_id )	{

	$form = new KBS_Form( $form_id );

	return apply_filters( 'kbs_get_form', $form, $form_id );

} // kbs_get_form

/**
 * Retrieve a forms redirection page.
 *
 * @since	1.0
 * @param	int		$form_id	Post ID.
 * @return	int		The page ID to which the form should redirect
 */
function kbs_get_form_redirect_target( $form_id )	{

	$redirect = get_post_meta( $form_id, '_redirect_page', true );

    if ( ! $redirect )  {
        $redirect = kbs_get_option( 'tickets_page' );
    }

	return apply_filters( 'kbs_form_redirect_target', $redirect, $form_id );

} // kbs_get_form_redirect_target

/**
 * Retrieve a forms redirection page.
 *
 * @param int $form_id Post ID.
 *
 * @return    string        The after form submission action
 * @since    1.6.0
 */
function kbs_get_form_submission_options( $form_id ){

	$submission = get_post_meta( $form_id, '_submission_action', true );

	if ( !$submission ){
		$submission = 'redirect';
	}

	return apply_filters( 'kbs_form_submission_action', $submission, $form_id );

} // kbs_get_form_submission_options



/**
 * Retrieve the form shortcode.
 *
 * @since	1.0
 * @param	int		$form_id	The form ID
 * @return	str
 */
function kbs_get_form_shortcode( $form_id ) {
	$shortcode = '[kbs_submit form="' . $form_id . '"]';
	$shortcode = apply_filters( 'kbs_form_shortcode', $shortcode, $form_id );

	return $shortcode;
} // kbs_get_form_shortcode

/**
 * Whether or not a form has the mandatory email field.
 *
 * @since	1.0
 * @param	int		$form_id	Form ID.
 * @param	str		$field		The type of field to check.
 * @return	bool	True if the email field exists for the form.
 */
function kbs_form_has_default_field( $form_id, $field )	{
	$fields = get_posts( array(
		'status'      => 'publish',
		'post_type'   => 'kbs_form_field',
		'post_parent' => $form_id,
		'numberposts' => 1,
		'fields'      => 'ids',
		'meta_key'    => '_default_field',
		'meta_value'  => $field
	) );

	if ( $fields )	{
		return true;
	}

	return false;
} // kbs_form_has_default_field

/**
 * Default form fields
 *
 * @since	1.0
 * @return	arr
 */
function kbs_form_default_fields()	{
	$default_fields = array(
		'first_name'  => array(
			'type'            => 'text',
			'mapping'         => 'customer_first',
			'required'        => true,
			'label'           => __( 'First Name', 'kb-support' ),
			'show_logged_in'  => false,
			'kb_search'       => false,
			'menu_order'      => '0'
		),
		'last_name'   => array(
			'type'            => 'text',
			'mapping'         => 'customer_last',
			'required'        => true,
			'label'           => __( 'Last Name', 'kb-support' ),
			'show_logged_in'  => false,
			'kb_search'       => false,
			'menu_order'      => '1'
		),
		'email'       => array(
			'type'            => 'email',
			'mapping'         => 'customer_email',
			'required'        => true,
			'label'           => __( 'Email Address', 'kb-support' ),
			'show_logged_in'  => false,
			'kb_search'       => false,
			'menu_order'      => '2'
		),
		'subject'     => array(
			'type'            => 'text',
			'mapping'         => 'post_title',
			'required'        => true,
			'label'           => __( 'Subject', 'kb-support' ),
			'show_logged_in'  => true,
			'kb_search'       => true,
			'menu_order'      => '3'
		),
		'rich_editor' => array(
			'type'            => 'rich_editor',
			'mapping'         => 'post_content',
			'required'        => true,
			'label'           => __( 'Description', 'kb-support' ),
			'show_logged_in'  => true,
			'kb_search'       => false,
			'menu_order'      => '4'
		)
	);

	$default_fields = apply_filters( 'kbs_form_default_fields', $default_fields );

	return $default_fields;
} // kbs_form_default_fields

/**
 * Adds the default fields to a form if needed.
 *
 * @since	1.0
 * @param	int		$field_id	The form ID
 */
function kbs_add_default_fields_to_form( $form_id )	{

	$default_fields = kbs_form_default_fields();

	foreach( $default_fields as $field => $field_data )	{

		if ( ! kbs_form_has_default_field( $form_id, $field ) )	{

			$form = new KBS_Form( $form_id );

			$data = array(
				'form_id'         => $form_id,
				'type'            => $field_data['type'],
				'mapping'         => $field_data['mapping'],
				'required'        => $field_data['required'],
				'label'           => $field_data['label'],
				'label_class'     => '',
				'input_class'     => '',
				'select_options'  => '',
				'select_multiple' => false,
				'selected'        => false,
				'chosen'          => false,
                'chosen_search'   => '',
				'placeholder'     => '',
				'description'     => '',
				'hide_label'      => false,
				'kb_search'       => $field_data['kb_search'],
				'show_logged_in'  => $field_data['show_logged_in'],
				'menu_order'      => $field_data['menu_order']
			);

			$field_id = $form->add_field( $data );

			if ( $field_id )	{
				add_post_meta( $field_id, '_default_field', $field );
			}

		}

	}

} // kbs_add_default_fields_to_form
add_action( 'kbs_form_before_save', 'kbs_add_default_fields_to_form', 5 );

/**
 * Retrieve all form fields.
 *
 * @since	1.0
 * @param	int		$form_id	Post ID.
 * @param	arr		$args		Arguments. See $defaults / WP_Query.
 * @return	obj		WP_Query Object.
 */
function kbs_get_fields( $form_id )	{

	$form = new KBS_Form( $form_id );

	return $form->get_fields();

} // kbs_get_fields

/**
 * Retrieve a single form field.
 *
 * Also retrieves settings into $field->settings.
 *
 * @since	1.0
 * @param	int		$field_id	Post ID.
 * @return	obj		WP_Query Object.
 */
function kbs_get_field( $field_id )	{

	$field = get_post( $field_id );

	if ( ! $field )	{
		return false;
	}

	$field->settings = kbs_get_field_settings( $field->ID );

	return apply_filters( 'kbs_get_field', $field );

} // kbs_get_field

/**
 * Retrieve a form field by a given field.
 *
 * @since	1.0
 * @param	str		$field	The field to retrieve the form field with
 * @param	mixed	$value	The value for field
 * @return	mixed
 */
function kbs_get_field_by( $field = '', $value = '' ) {

	if( empty( $field ) || empty( $value ) ) {
		return false;
	}

	switch( strtolower( $field ) ) {

		case 'id':
			$form_field = kbs_get_field( $value );

			if ( 'kbs_form_field' != get_post_type( $form_field ) ) {
				return false;
			}
			break;

		case 'slug':
		case 'name':
			$form_field = get_posts( array(
				'post_type'      => 'kbs_form_field',
				'name'           => $value,
				'posts_per_page' => 1,
				'post_status'    => 'any'
			) );

			if ( $form_field ) {
				$form_field = $form_field[0];
			}

			break;

		default:
			return false;
	}

	if ( $form_field ) {
		return $form_field;
	}

	return false;
} // kbs_get_field_by

/**
 * Whether or not a field can be deleted from a form.
 *
 * @since	1.0
 * @param	int		$field_id	The ID of the field to delete.
 * @return	bool	True if a field can be deleted, otherwise false.
 */
function kbs_can_delete_field( $field_id )	{
	$no_delete = get_post_meta( $field_id, '_default_field', true );

	if ( $no_delete )	{
		return false;
	}

	return true;
} // kbs_can_delete_field

/**
 * Delete a form field.
 *
 * @since	1.0
 * @param	int		$field_id	Post ID.
 * @param	bool	$force		Whether or not to force deletion of a default field.
 * @return	obj		WP_Query Object.
 */
function kbs_delete_field( $field_id, $force = false )	{

	if ( ! $force && ! kbs_can_delete_field( $field_id ) )	{
		return false;
	}

	do_action( 'kbs_pre_delete_field', $field_id );

	$result = wp_delete_post( $field_id );

	do_action( 'kbs_post_delete_field', $field_id, $result );

	return $result;

} // kbs_delete_field

/**
 * Returns the field type in readable format.
 *
 * @since	1.0
 * @param	str		$type	The type to return
 * @return	str		The field type in readable format.
 */
function kbs_get_field_type( $type )	{

	$field_types = kbs_get_field_types();

	return $field_types[ $type ];

} // kbs_get_field_type

/**
 * Returns the field settings.
 *
 * @since	1.0
 * @param	str		$field_id	The post ID.
 * @return	arr		The field settings.
 */
function kbs_get_field_settings( $field_id )	{

	$field_settings = get_post_meta( $field_id, '_kbs_field_settings', true );

	return apply_filters( 'kbs_field_settings', $field_settings, $field_id );

} // kbs_get_field_settings

/**
 * Returns all possible form fields types.
 *
 * @since	1.0
 * @param
 * @return	arr
 */
function kbs_get_field_types()	{

	$field_types = array(
		'checkbox'                  => __( 'Checkbox', 'kb-support' ),
		'checkbox_list'             => __( 'Checkbox List', 'kb-support' ),
		'date_field'                => __( 'Date Field', 'kb-support' ),
		'department'                => __( 'Departments List', 'kb-support' ),
		'email'                     => __( 'Email Field', 'kb-support' ),
		'file_upload'               => __( 'File Upload', 'kb-support' ),
		'hidden'                    => __( 'Hidden Field', 'kb-support' ),
		'number'                    => __( 'Number Field', 'kb-support' ),
		'radio'                     => __( 'Radio Buttons', 'kb-support' ),
		'recaptcha'                 => __( 'Google reCAPTCHA', 'kb-support' ),
		'rich_editor'               => __( 'Rich Text Editor', 'kb-support' ),
		'select'                    => __( 'Select List', 'kb-support' ),
		'text'                      => __( 'Text Field', 'kb-support' ),
		'textarea'                  => __( 'Textarea', 'kb-support' ),
		'ticket_category_dropdown'  => sprintf( __( '%s Categories', 'kb-support' ), kbs_get_ticket_label_singular() ),
		'url'                       => __( 'URL Field', 'kb-support' ),
	);

	if ( ! kbs_departments_enabled() )	{
		unset( $field_types['department'] );
	}

	if ( ! kbs_file_uploads_are_enabled() )	{
		unset( $field_types['file_uploads'] );
	}

	if ( ! kbs_get_option( 'recaptcha_site_key' ) || ! kbs_get_option( 'recaptcha_secret' ) )	{
		unset( $field_types['recaptcha'] );
	}

	/**
	 * Filter the field types to allow for custom fields to be added.
	 *
	 * @since	1.0
	 * @param	$field_types
	 */
	$field_types = apply_filters( 'kbs_field_types', $field_types );

	asort( $field_types );

	return $field_types;

} // kbs_get_field_types

/**
 * Returns all possible form field mappings.
 *
 * @since	1.0
 * @param	str		$mapping	The mapping to retrieve.
 * @return	arr
 */
function kbs_get_mappings( $mapping = null )	{

	$mappings = array(
		'customer_first'   => __( 'Customer First Name', 'kb-support' ),
		'customer_last'    => __( 'Customer Last Name', 'kb-support' ),
		'customer_email'   => __( 'Customer Email', 'kb-support' ),
		'customer_phone1'  => __( 'Customer Primary Phone', 'kb-support' ),
		'customer_phone2'  => __( 'Customer Additional Phone', 'kb-support' ),
		'customer_website' => __( 'Customer Website', 'kb-support' ),
		'department'       => __( 'Department', 'kb-support' ),
		'post_content'     => __( 'Ticket Content', 'kb-support' ),
		'post_title'       => __( 'Ticket Title', 'kb-support' )
	);

	if ( ! kbs_departments_enabled() )	{
		unset( $mappings['department'] );
	}

	/**
	 * Filter the field mappings to allow for custom mappings to be added.
	 *
	 * @since	1.0
	 * @param	$field_types
	 */
	$mappings = apply_filters( 'kbs_mappings', $mappings );

	asort( $mappings );

	$mappings = array( '' => __( 'None', 'kb-support' ) ) + $mappings;

	if ( isset( $mapping ) && array_key_exists( $mapping, $mappings ) )	{
		return $mappings[ $mapping ];
	}

	return $mappings;

} // kbs_get_mappings

/**
 * Returns all available mappings.
 *
 * @since	1.0
 * @param	int	form_id		Form post ID.
 * @return	arr
 */
function kbs_get_available_mappings( $form_id )	{

	$kbs_form = new KBS_Form( $form_id );

	$mappings = kbs_get_mappings();

	foreach( $mappings as $key => $value )	{
		if ( $kbs_form->has_mapping( $key ) )	{
			unset( $mappings[ $key ] );
		}
	}

	return $mappings;

} // kbs_get_available_mappings

/**
 * Fields that are ignored during form submission.
 *
 * @since	1.0
 * @return	arr		Array of fields that should be ignored.
 */
function kbs_form_ignore_fields()	{
	$ignore = array(
		'kbs_form_id',
		'kbs_action',
		'kbs_redirect',
		'kbs_honeypot',
		'kbs_ticket_submit',
		'g-recaptcha-response'
	);

	return apply_filters( 'kbs_ignore_ticket_fields', $ignore );
} // kbs_form_ignore_fields

/**
 * Output the icons for the field settings.
 *
 * @since	1.0
 * @param	int		$field_id	The field ID.
 * @return	str
 */
function kbs_display_field_setting_icons( $field_id )	{

	$settings = kbs_get_field_settings( $field_id );
	$mappings = kbs_get_mappings();
	$output   = array();

	if ( $settings )	{
		if ( ! empty( $settings['hide_label'] ) )	{
			$output[] = '<i title="' . __( 'Label Hidden', 'kb-support' ) . '" class="fas fa-tag" aria-hidden="true"></i>';
		} else	{
			$output[] = '&nbsp;&nbsp;&nbsp;';
		}

		if ( ! empty( $settings['required'] ) )	{
			$output[] = '<i title="' . __( 'Required Field', 'kb-support' ) . '" class="fas fa-asterisk" aria-hidden="true"></i>';
		} else	{
			$output[] = '&nbsp;&nbsp;&nbsp;';
		}

		if ( ! empty( $settings['placeholder'] ) )	{
			$output[] = '<i title="' . sprintf( __( 'Placeholder: %s', 'kb-support' ), stripslashes( $settings['placeholder'] ) ) . '" class="fas fa-info-circle" aria-hidden="true"></i>';
		} else	{
			$output[] = '&nbsp;&nbsp;&nbsp;';
		}

		if ( ! empty( $settings['mapping'] ) && 'post_category' != $settings['mapping'] )	{
			$output[] = '<i title="' . sprintf( __( 'Maps to %s', 'kb-support' ), stripslashes( $mappings[ $settings['mapping'] ] ) ) . '" class="fas fa-map-marker-alt" aria-hidden="true"></i>';
		} else	{
			$output[] = '&nbsp;&nbsp;&nbsp;';
		}

        if ( ! empty( $settings['chosen'] ) )	{
			$output[] = '<i title="' . __( 'Searchable', 'kb-support' ) . '" class="fas fa-search" aria-hidden="true"></i>';
		} else	{
			$output[] = '&nbsp;&nbsp;&nbsp;';
		}

		if ( 'hidden' == $settings['type'] )	{
			$output[] = '<i title="' . __( 'Hidden', 'kb-support' ) . '" class="far fa-eye-slash" aria-hidden="true"></i>';
		} else	{
			$output[] = '&nbsp;&nbsp;&nbsp;';
		}

	}

	$output = apply_filters( 'kbs_field_setting_icons', $output, $field_id, $settings );

	return implode( "\t", $output );

} // kbs_display_field_setting_icons

/**
 * Display Form
 *
 * @since	1.0
 * @global	$kbs_form
 * @param	str			$form_id	Form post ID
 * @return	str			Form
 */
function kbs_display_form( $form_id = 0 ) {
	global $kbs_form;

	if ( empty( $form_id ) ) {
		return __( 'Submission form not found', 'kb-support' );
	}

	if ( has_action( 'kbs_display_form' ) )	{
		do_action( 'kbs_display_form', $form_id );
	} else	{

		$kbs_form = new KBS_Form( $form_id );

		if ( ! $kbs_form ) {
			return __( 'Submission form not found', 'kb-support' );
		}

		ob_start();

		kbs_get_template_part( 'shortcode', apply_filters( 'kbs_form_template', 'form', $kbs_form, $form_id ) );

		return apply_filters( 'kbs_submit_form', ob_get_clean(), $kbs_form, $form_id );

	}
} // kbs_display_form

/**
 * Form submission error messages.
 *
 * @since	1.0
 * @param	int		$field_id	The field ID.
 * @param	str		$error		The type of error.
 * @return	void
 */
function kbs_form_submission_errors( $field_id, $error )	{

	$errors = array(
		'process_error'    => __( 'An internal error has occurred, please try again or contact support.', 'kb-support' ),
		'required'         => get_the_title( $field_id ) . __( ' is a required field.', 'kb-support' ),
		'invalid_email'    => get_the_title( $field_id ) . __( ' requires a valid email address.', 'kb-support' ),
		'agree_to_policy'  => __( 'You must acknowledge and accept our privacy policy', 'kb-support' ),
		'agree_to_terms'   => __( 'You must agree to the terms and conditions', 'kb-support' ),
		'google_recaptcha' => get_the_title( $field_id ) . __( ' validation failed', 'kb-support' )
	);

	$errors = apply_filters( 'kbs_form_submission_errors', $errors, $field_id );

	if ( ! array_key_exists( $error, $errors ) )	{
		return get_the_title( $field_id ) . __( ' contains an error.', 'kb-support' );
	}

	return $errors[ $error ];

} // kbs_form_submission_errors

/**
 * Display a form text input field.
 *
 * This function is also the callback for email and URL fields.
 *
 * @since	1.0
 * @param	bool		$email		The email address to check
 * @return	bool		True if the email is banned, or false
 */
function kbs_check_email_from_submission( $email )	{

	$is_banned = false;
	$banned    = kbs_get_banned_emails();

	if ( ! empty( $banned ) )	{
		if ( is_user_logged_in() )	{

			// The user is logged in, check that their account email is not banned
			$user_data = get_userdata( get_current_user_id() );
			if ( kbs_is_email_banned( $user_data->user_email ) )	{
				$is_banned = true;
			}
		}
		// Check that the email used to submit ticket is not banned
		if ( kbs_is_email_banned( $email ) )	{
			$is_banned = true;
		}

	}

	return $is_banned;

} // kbs_validate_email_from_submission

/**
 * Display a form text input field.
 *
 * This function is also the callback for email and URL fields.
 *
 * @since	1.0
 * @param	obj			$field		Field post object
 * @param	arr			$settings	Field settings
 * @return	str			$type input field
 */
function kbs_display_form_text_field( $field, $settings )	{

	$type        = ! empty( $settings['type'] ) ? $settings['type'] : 'text';
	$placeholder = ! empty( $settings['placeholder'] ) ? ' placeholder="' . esc_attr( $settings['placeholder'] ) . '"' : '';
	$class       = ! empty( $settings['input_class'] ) ? esc_attr( $settings['input_class'] ) : '';
	$value       = '';

	if ( $type == 'date_field' )	{
		if( empty( $class ) ) {
			$class = 'kbs_datepicker';
		} elseif( ! strpos( $class, 'kbs_datepicker' ) ) {
			$class .= ' kbs_datepicker';
		}
		$type = 'text';

		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_register_style( 'jquery-ui-css', KBS_PLUGIN_URL . 'assets/css/jquery-ui-fresh.min.css' );
		wp_enqueue_style( 'jquery-ui-css' );
	}

	$class = implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $class ) ) );
	$class = 'kbs-article-search ' . $class;
	$type  = 'search';

	if ( ! empty( $settings['kb_search'] ) )	{
		$class = 'kbs-article-search ' . $class;
		$type  = 'search';
		wp_enqueue_script( 'kbs-live-search' );
	}

	if ( ! empty( $settings['mapping'] ) && is_user_logged_in() && ! kbs_is_agent() )	{
		$user_id = get_current_user_id();

		if ( 'customer_first' == $settings['mapping'] )	{
			$value = ' value="' . get_userdata( $user_id )->first_name . '"';
		}

		if ( 'customer_last' == $settings['mapping'] )	{
			$value = ' value="' . get_userdata( $user_id )->last_name . '"';
		}

		if ( 'customer_email' == $settings['mapping'] )	{
			$value = ' value="' . get_userdata( $user_id )->user_email . '"';
		}

		if ( 'customer_website' == $settings['mapping'] )	{
			$value = ' value="' . get_userdata( $user_id )->user_url . '"';
		}

		// Allow plugins to filter values for mapped fields
		$value = apply_filters( 'kbs_mapped_form_field_value', $value, $settings, $field );

	} elseif ( ! empty( $settings['value'] ) )	{
		$value = ' value="' . esc_attr( $settings['value'] ) . '"';
	}

	$output = sprintf( '<input type="%1$s" name="%2$s" id="%2$s" class="kbs-input %3$s"%4$s%5$s />',
		esc_attr( $type ),
		esc_attr( $field->post_name ),
		! empty( $class ) ? $class : '',
		$value,
		$placeholder
	);

	$output = apply_filters( 'kbs_display_form_' . $settings['type'] . '_field', $output, $field, $settings );

	echo $output;

} // kbs_display_form_text_field
add_action( 'kbs_form_display_text_field', 'kbs_display_form_text_field', 10, 2 );
add_action( 'kbs_form_display_date_field_field', 'kbs_display_form_text_field', 10, 2 );
add_action( 'kbs_form_display_hidden_field', 'kbs_display_form_text_field', 10, 2 );
add_action( 'kbs_form_display_email_field', 'kbs_display_form_text_field', 10, 2 );
add_action( 'kbs_form_display_number_field', 'kbs_display_form_text_field', 10, 2 );
add_action( 'kbs_form_display_url_field', 'kbs_display_form_text_field', 10, 2 );

/**
 * Display a form textrea field
 *
 * @since	1.0
 * @param	obj			$field		Field post object
 * @param	arr			$settings	Field settings
 * @return	str			Field
 */
function kbs_display_form_textarea_field( $field, $settings )	{

	$placeholder = ! empty( $settings['placeholder'] ) ? ' placeholder="' . esc_attr( $settings['placeholder'] ) . '"' : '';
	$class       = ! empty( $settings['input_class'] ) ? esc_attr( $settings['input_class'] ) : '';

	if ( $settings['type'] == 'rich_editor' )	{
		$wp_settings  = apply_filters( 'kbs_rich_editor_settings', array(
			'wpautop'       => true,
			'media_buttons' => false,
			'textarea_name' => esc_attr( $field->post_name ),
			'textarea_rows' => get_option( 'default_post_edit_rows', 10 ),
			'tabindex'      => '',
			'editor_css'    => '',
			'editor_class'  => $settings['input_class'],
			'teeny'         => true,
			'dfw'           => false,
			'tinymce'       => true,
			'quicktags'     => false
		) );

		$output = wp_editor( '', esc_attr( $field->post_name ), $wp_settings );

	} else	{

		$output = sprintf( '<textarea name="%1$s" id="%1$s"%2$s%3$s></textarea>',
			esc_attr( $field->post_name ),
			! empty( $class ) ? ' class="' . $class . '"' : '',
			$placeholder
		);

	}

	$output = apply_filters( 'kbs_display_form_textarea_field', $output, $field, $settings );

	echo $output;

} // kbs_display_form_textarea_field
add_action( 'kbs_form_display_textarea_field', 'kbs_display_form_textarea_field', 10, 2 );
add_action( 'kbs_form_display_rich_editor_field', 'kbs_display_form_textarea_field', 10, 2 );

/**
 * Display a form select field
 *
 * @since	1.0
 * @param	obj			$field		Field post object
 * @param	arr			$settings	Field settings
 * @return	str			Field
 */
function kbs_display_form_select_field( $field, $settings )	{

	$class         = ! empty( $settings['input_class'] )     ? esc_attr( $settings['input_class'] )   : '';
	$multiple      = ! empty( $settings['select_multiple'] ) ? ' ' . ' multiple'                      : false;
    $blank_first   = ! empty( $settings['blank'] )           ? true                                   : false;
    $chosen        = ! empty( $settings['chosen'] )          ? true                                   : false;
    $chosen_search = ! empty( $settings['chosen_search'] )   ? esc_html( $settings['chosen_search'] ) : false;
    $data_array    = ! empty( $settings['data'] )            ? $settings['data']                      : array();
    $data_elements = '';
	$options       = array();

	if ( $chosen )	{
		$class .= 'kbs-select-chosen';

        if ( $chosen_search && ! isset( $data_array['search-placeholder'] ) )    {
            $data_array['search-type']        = 'general';
            $data_array['search-placeholder'] = $chosen_search;

			if ( ! empty( $settings['placeholder'] ) )	{
				$data_array['placeholder'] = esc_html( $settings['placeholder'] );
			}
        }
	}

	$class   = implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $class ) ) );
	$options = apply_filters( 'kbs_form_select_field_options', $settings['select_options'], $settings );

    foreach ( $data_array as $key => $value ) {
        $data_elements .= ' data-' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
    }

	$output = sprintf( '<select name="%1$s" id="%1$s"%2$s%3$s%4$s>',
		esc_attr( $field->post_name ),
		' class="' . $class . ' kbs-input"',
		$multiple,
        $data_elements
	);

    if ( $blank_first )	{
		$output .= '<option value="">';
        $output .= '';
        $output .= '</option>';
	}

    if ( ! empty( $settings['placeholder'] ) )	{
		$output .= '<option value="0">';
        $output .= esc_html( $settings['placeholder'] );
        $output .= '</option>';
	}

	if ( ! empty( $options ) )	{
		foreach( $options as $key => $value )	{
			$output .= '<option value="' . esc_attr( $key ) . '"';
			$output .= selected( $settings['selected'], $key, false );
			$output .= '>' . esc_html( $value ) . '</option>';
		}
	}

	$output .= '</select>';

	$output = apply_filters( 'kbs_display_form_select_field', $output, $field, $settings );

	echo $output;

} // kbs_display_form_select_field
add_action( 'kbs_form_display_select_field', 'kbs_display_form_select_field', 10, 2 );

/**
 * Display a ticket category select field.
 *
 * @since	1.0
 * @param	obj			$field		Field post object
 * @param	arr			$settings	Field settings
 * @return	str			Field
 */
function kbs_display_form_ticket_category_field( $field, $settings )	{

	add_filter( 'kbs_form_select_field_options', 'kbs_get_ticket_category_options' );
	kbs_display_form_select_field( $field, $settings );
	remove_filter('kbs_form_select_field_options', 'kbs_get_ticket_category_options' );

} // kbs_display_form_ticket_category_field
add_action( 'kbs_form_display_ticket_category_dropdown_field', 'kbs_display_form_ticket_category_field', 10, 2 );

/**
 * Display a departments select field.
 *
 * @since	1.2
 * @param	obj			$field		Field post object
 * @param	arr			$settings	Field settings
 * @return	str			Field
 */
function kbs_display_form_department_field( $field, $settings )	{

	add_filter( 'kbs_form_select_field_options', 'kbs_get_department_options' );
	if ( ! empty( $_GET['department'] ) )	{
		$settings['selected'] = $_GET['department'];
	}
	kbs_display_form_select_field( $field, $settings );
	remove_filter('kbs_form_select_field_options', 'kbs_get_department_options' );

} // kbs_display_form_department_field
add_action( 'kbs_form_display_department_field', 'kbs_display_form_department_field', 10, 2 );

/**
 * Display a form checkbox field
 *
 * @since	1.0
 * @param	obj			$field		Field post object
 * @param	arr			$settings	Field settings
 * @return	str			Field
 */
function kbs_display_form_checkbox_field( $field, $settings )	{

	$class       = ! empty( $settings['input_class'] ) ? ' class="' . esc_attr( $settings['input_class'] ) . '"' : '';
	$checked     = ! empty( $settings['selected'] )    ? ' ' . ' checked'                                        : '';

	$output = sprintf( '<input type="checkbox" name="%1$s" id="%1$s"%2$s%3$s />',
		esc_attr( $field->post_name ),
		$class,
		$checked
	);

	$output = apply_filters( 'kbs_display_form_checkbox_field', $output, $field, $settings );

	echo $output;

} // kbs_display_form_textarea_field
add_action( 'kbs_form_display_checkbox_field', 'kbs_display_form_checkbox_field', 10, 2 );

/**
 * Render the agree to privacy policy checkbox.
 *
 * @since	1.5
 * @return	string
 */
function kbs_render_agree_to_privacy_policy_field()	{
	$agree_to_policy = kbs_get_option( 'show_agree_to_privacy_policy', false );
	$privacy_page    = kbs_get_privacy_page();
	$label           = kbs_get_option( 'agree_privacy_label', false );
    $description     = kbs_get_option( 'agree_privacy_descripton', false );

	if ( empty( $agree_to_policy ) || empty( $privacy_page ) || empty( $label ) )	{
    	return;
	}

	$privacy_text = get_post_field( 'post_content', $privacy_page );

	if ( empty( $privacy_text ) )	{
		return;
	}

	$label_class = '';
	$input_class = '';

	$args = apply_filters( 'kbs_agree_to_privacy_policy_args', array(
		'label_class' => '',
		'input_class' => ''
	) );

	if ( ! empty( $args['label_class'] ) )	{
		$label_class = ' ' . sanitize_html_class( $args['label_class'] );
	}

	if ( ! empty( $args['input_class'] ) )	{
		$input_class = ' class="' . sanitize_html_class( $args['input_class'] ) . '"';
	}

	ob_start(); ?>

	<p><input type="checkbox" name="kbs_agree_privacy_policy" id="kbs-agree-privacy-policy"<?php echo $input_class; ?> value="1" /> <a href="#TB_inline?width=600&height=550&inlineId=kbs-ticket-privacy-policy" title="<?php echo esc_html( get_the_title( $privacy_page ) ); ?>" class="thickbox"<?php echo $label_class; ?>><?php esc_attr_e( $label, 'kb-support' ); ?></a></p>

	<div id="kbs-ticket-privacy-policy" class="kbs_hidden">
		<?php do_action( 'kbs_before_privacy_policy' ); ?>

        <?php if ( function_exists( 'apply_shortcodes' ) ) : ?>
            <?php echo wpautop( apply_shortcodes( stripslashes( $privacy_text ) ) ); ?>
        <?php else : ?>
            <?php echo wpautop( do_shortcode( stripslashes( $privacy_text ) ) ); ?>
        <?php endif; ?>

		<?php do_action( 'kbs_after_privacy_policy' ); ?>
    </div>

    <?php if ( ! empty( $description ) ) : ?>
        <span class="kbs-description"><?php echo esc_html( $description ); ?></span>
    <?php endif; ?>

	<?php echo ob_get_clean();

} // kbs_render_agree_to_privacy_policy_field
add_action( 'kbs_ticket_form_after_fields', 'kbs_render_agree_to_privacy_policy_field', 950 );

/**
 * Render the agree to terms checkbox.
 *
 * @since	1.0
 * @return	str
 */
function kbs_render_agree_to_terms_field()	{
	$agree_to_terms = kbs_get_option( 'show_agree_to_terms', false );
	$agree_text     = kbs_get_option( 'agree_terms_text', false );
	$label          = kbs_get_option( 'agree_terms_label', false );
    $description    = kbs_get_option( 'agree_terms_description', false );
	$terms_heading  = kbs_get_option( 'agree_terms_heading', sprintf(
		__( 'Terms and Conditions for Support %s', 'kb-support' ), kbs_get_ticket_label_plural()
	) );

	if ( ! $agree_to_terms || ! $agree_text || ! $label )	{
    	return;
	}

	$label_class = '';
	$input_class = '';

	$args = apply_filters( 'kbs_agree_to_terms_args', array(
		'label_class' => '',
		'input_class' => ''
	) );

	if ( ! empty( $args['label_class'] ) )	{
		$label_class = ' ' . sanitize_html_class( $args['label_class'] );
	}

	if ( ! empty( $args['input_class'] ) )	{
		$input_class = ' class="' . sanitize_html_class( $args['input_class'] ) . '"';
	}

	ob_start(); ?>

	<p><input type="checkbox" name="kbs_agree_terms" id="kbs-agree-terms"<?php echo $input_class; ?> value="1" /> <a href="#TB_inline?width=600&height=550&inlineId=kbs-ticket-terms-conditions" title="<?php esc_attr_e( $terms_heading, 'kb-support' ); ?>" class="thickbox"<?php echo $label_class; ?>><?php esc_attr_e( $label, 'kb-support' ); ?></a></p>

	<div id="kbs-ticket-terms-conditions" class="kbs_hidden">
		<?php do_action( 'kbs_before_terms' ); ?>
		<?php echo wpautop( stripslashes( $agree_text ) ); ?>
		<?php do_action( 'kbs_after_terms' ); ?>
    </div>

    <?php if ( ! empty( $description ) ) : ?>
        <span class="kbs-description"><?php echo esc_html( $description ); ?></span>
    <?php endif; ?>

	<?php echo ob_get_clean();

} // kbs_render_agree_to_terms_field
add_action( 'kbs_ticket_form_after_fields', 'kbs_render_agree_to_terms_field', 999 );

/**
 * Display a form checkbox list field
 *
 * @since	1.0
 * @param	obj			$field		Field post object
 * @param	arr			$settings	Field settings
 * @return	str			Field
 */
function kbs_display_form_checkbox_list_field( $field, $settings )	{

	$class   = ! empty( $settings['input_class'] ) ? ' class="' . esc_attr( $settings['input_class'] ) . '"' : '';
	$options = $settings['select_options'];

	if ( empty ( $options ) )	{
		return;
	}

	foreach ( $options as $option )	{
		$output[] = sprintf( '<input type="checkbox" name="%1$s[]" id="%2$s"%3$s value="%4$s" /> %5$s',
			esc_attr( $field->post_name ),
			esc_attr( kbs_sanitize_key( $option ) ),
			$class,
			esc_attr( $option ),
			esc_attr( $option )
		);

	}

	$output = apply_filters( 'kbs_display_form_checkbox_field', $output, $field, $settings );

	echo implode( '<br />', $output );

} // kbs_display_form_checkbox_list_field
add_action( 'kbs_form_display_checkbox_list_field', 'kbs_display_form_checkbox_list_field', 10, 2 );

/**
 * Display a form radio group field
 *
 * @since	1.0
 * @param	obj			$field		Field post object
 * @param	arr			$settings	Field settings
 * @return	str			Field
 */
function kbs_display_form_radio_field( $field, $settings )	{

	$class   = ! empty( $settings['input_class'] ) ? ' class="' . esc_attr( $settings['input_class'] ) . '"' : '';
	$options = $settings['select_options'];

	if ( empty ( $options ) )	{
		return;
	}

	foreach ( $options as $option )	{
		$output[] = sprintf( '<input type="radio" name="%1$s" id="%2$s"%3$s value="%4$s" /> %5$s',
			esc_attr( $field->post_name ),
			esc_attr( kbs_sanitize_key( $option ) ),
			$class,
			esc_attr( $option ),
			esc_attr( $option )
		);

	}

	$output = apply_filters( 'kbs_display_form_radio_field', $output, $field, $settings );

	echo implode( '<br />', $output );

} // kbs_display_form_radio_field
add_action( 'kbs_form_display_radio_field', 'kbs_display_form_radio_field', 10, 2 );

/**
 * Display a form recaptcha field
 *
 * @since	1.0
 * @param	object		$field		Field post object
 * @param	array		$settings	Field settings
 * @return	string		Field
 */
function kbs_display_form_recaptcha_field( $field, $settings )	{
	$site_key = kbs_get_option( 'recaptcha_site_key' );
	$secret   = kbs_get_option( 'recaptcha_secret' );
    $version  = kbs_get_recaptcha_version();

	if ( ! $site_key || ! $secret )	{
		return;
	}

    $script = 'https://www.google.com/recaptcha/api.js';

    if ( 'v3' === $version )    {
        $script = add_query_arg( 'render', $site_key, $script );

        $output = '<input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response" value="" />' .  "\n";
        $output .= '<input type="hidden" name="recaptcha_action" id="recaptcha-action" value="" />' .  "\n";
    }

    wp_register_script( 'google-recaptcha', $script, '', KBS_VERSION, true );
    wp_enqueue_script( 'google-recaptcha' );

    if ( 'v2' === $version )    {
        $output = sprintf(
            '<div class="g-recaptcha" data-sitekey="%1$s" data-theme="%2$s" data-type="%3$s" data-size="%4$s"></div>',
            $site_key,
            kbs_get_option( 'recaptcha_theme' ),
            kbs_get_option( 'recaptcha_type' ),
            kbs_get_option( 'recaptcha_size' )
        ) . "\n";
    }

    $output .= sprintf(
        '<input type="hidden" name="%1$s" id="%1$s" value="" />',
        esc_attr( $field->post_name )
    ) . "\n";

    $output = apply_filters( 'kbs_display_form_recaptcha_field', $output, $field, $settings );

    echo $output;
} // kbs_display_form_recaptcha_field
add_action( 'kbs_form_display_recaptcha_field', 'kbs_display_form_recaptcha_field', 10, 2 );

/**
 * Display a form file upload field.
 *
 * @since	1.0
 * @param	obj			$field		Field post object
 * @param	arr			$settings	Field settings
 * @return	str			$type input field
 */
function kbs_display_form_file_upload_field( $field, $settings )	{

	if ( ! kbs_file_uploads_are_enabled() )	{
		return;
	}

	$output      = '';
	$placeholder = ! empty( $settings['placeholder'] ) ? ' placeholder="' . esc_attr( $settings['placeholder'] ) . '"' : '';
	$class       = ! empty( $settings['input_class'] ) ? esc_attr( $settings['input_class'] ) : '';
	$class       = implode( ' ', array_map( 'sanitize_html_class', explode( ' ', $class ) ) );

	for ( $i = 1; $i <= kbs_get_max_file_uploads(); $i++ )	{
        $output .= sprintf( '<input type="file" name="%1$s[]"%2$s%3$s accept="%4$s" />',
			esc_attr( $field->post_name ),
			! empty( $class ) ? ' class="' . $class . ' kbs-input"' : '',
			$placeholder,
			kbs_get_allowed_file_types()
		);
	}

	$output = apply_filters( 'kbs_display_form_file_upload_field', $output, $field, $settings );

	echo $output;

} // kbs_display_form_file_upload_field
add_action( 'kbs_form_display_file_upload_field', 'kbs_display_form_file_upload_field', 10, 2 );

/**
 * Validate reCAPTCHA.
 *
 * @since	1.1.12
 * @param	string		$response	reCAPTCHA response.
 * @return	bool    True if verified, otherwise false
 */
function kbs_validate_recaptcha( $response )	{
    $version   = kbs_get_recaptcha_version();
	$post_data = http_build_query( array(
        'secret'   => kbs_get_option( 'recaptcha_secret' ),
        'response' => $response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ) );

    $options = array( 'http' => array(
        'method'  => 'POST',
        'header'  => 'Content-type: application/x-www-form-urlencoded',
        'content' => $post_data
    ) );

    $context  = stream_context_create( $options );
    $response = file_get_contents( 'https://www.google.com/recaptcha/api/siteverify', false, $context );
    $result   = json_decode( $response );

    if ( ! empty( $result ) && true == $result->success )	{
        $return = $result->success;

        if ( 'v3' === $version )    {
            $return = $result->action == 'submit_kbs_form' && $result->score >= 0.5;
        }

		return $return;
    }

    return false;
} // kbs_validate_recaptcha

/**
 * Output a fields description.
 *
 * @since	1.0
 * @param	obj		$field		Field post object
 * @param	arr		$settings	Field settings
 * @return	str		The field description.
 */
function kbs_display_form_field_description( $field, $settings )	{
	if ( ! empty( $settings['description'] ) ) : ?>
    	<span class="kbs-description"><?php echo esc_html( $settings['description'] ); ?></span>
    <?php endif;
} // kbs_display_form_field_description

/**
 * Retrieve an array of banned_emails
 *
 * @since	1.0
 * @return	arr		Array of banned emails
 */
function kbs_get_banned_emails() {
	$emails = array_map( 'trim', kbs_get_option( 'banned_emails', array() ) );

	return apply_filters( 'kbs_banned_emails', $emails );
} // kbs_get_banned_emails

/**
 * Determines if an email is banned
 *
 * @since	1.0
 * @param	str		$email	Email address to check
 * @return	bool	true if the email address is banned, or false
 */
function kbs_is_email_banned( $email = '' ) {

	if ( empty( $email ) ) {
		return false;
	}

	$banned_emails = kbs_get_banned_emails();

	if ( ! is_array( $banned_emails ) || empty( $banned_emails ) )	{
		return false;
	}

	foreach( $banned_emails as $banned_email )	{
		if ( is_email( $banned_email ) )	{
			$return = ( $banned_email == trim( $email )          ? true : false );
		} else {
			$return = ( stristr( trim( $email ), $banned_email ) ? true : false );
		}

		if ( true === $return ) {
			break;
		}
	}

	return apply_filters( 'kbs_is_email_banned', $return, $email );

} // kbs_is_email_banned
