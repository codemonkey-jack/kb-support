<?php
/**
 * KBS Form Class
 *
 * @package		KBS
 * @subpackage	Posts/Forms
 * @since		1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * KBS_Form Class
 *
 * @since	1.0
 */
class KBS_Form {

	/**
	 * The form ID
	 *
	 * @since	1.0
	 */
	public $ID = 0;

	/**
	 * The form fields
	 *
	 * @since	1.0
	 */
	private $fields;

	/**
	 * The mapped form fields.
	 *
	 * @since	1.0
	 */
	public $mapped_fields;

    /**
	 * The redirect target.
	 *
	 * @since	1.0
	 */
	public $redirect_to;

	/**
	 * Get things going
	 *
	 * @since	1.0
	 */
	public function __construct( $_id = false, $_args = array() ) {
		$form = WP_Post::get_instance( $_id );

		return $this->setup_form( $form, $_args );

	} // __construct

	/**
	 * Given the form data, let's set the variables
	 *
	 * @since	1.0
	 * @param 	obj		$form	The Form post object
	 * @param	arr		$args	Arguments passed to the class on instantiation
	 * @return	bool			If the setup was successful or not
	 */
	private function setup_form( $form, $args ) {

		if( ! is_object( $form ) ) {
			return false;
		}

		if( ! is_a( $form, 'WP_Post' ) ) {
			return false;
		}

		if( 'kbs_form' !== $form->post_type ) {
			return false;
		}

		foreach ( $form as $key => $value ) {
			switch ( $key ) {
				default:
					$this->$key = $value;
					break;
			}
		}

		$this->get_fields();
		$this->mapped_fields();

		return true;

	} // setup_form

	/**
	 * Magic __get function to dispatch a call to retrieve a private property
	 *
	 * @since	1.0
	 */
	public function __get( $key ) {

		if( method_exists( $this, 'get_' . $key ) ) {
			return call_user_func( array( $this, 'get_' . $key ) );
		} else {
			return new WP_Error( 'kbs-form-invalid-property', sprintf( __( "Can't get property %s", 'kb-support' ), $key ) );
		}
	} // __get

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
	 * Retrieve the form shortcode.
	 *
	 * @since	1.0
	 * @return	str
	 */
	public function get_shortcode() {
		return kbs_get_form_shortcode( $this->ID );
	} // get_shortcode

	/**
	 * Adds a form field.
	 *
	 * @since	1.0
	 * @param	arr		$data	Field data.
	 * @return	mixed.
	 */
	public function add_field( $data )	{

		if ( ! isset( $data['form_id'], $data['label'], $data['type'] ) )	{
			return false;
		}

		do_action( 'kbs_pre_add_form_field', $data );

		$select_options = array();

		if ( ! empty( $data['select_options'] ) )	{
			$options = explode( "\n", $data['select_options'] );

			if ( ! empty( $options ) )	{
				foreach( $options as $option )	{
					$select_options[ $option ] = $option;
				}
			}
		}

		$settings = array(
            'blank'           => ! empty( $data['blank'] )           ? true                                          : false,
			'chosen'          => ! empty( $data['chosen'] )          ? true                                          : false,
            'chosen_search'   => ! empty( $data['chosen_search'] )   ? sanitize_text_field( $data['chosen_search'] ) : '',
			'description'     => ! empty( $data['description'] )     ? sanitize_text_field( $data['description'] )   : '',
			'description_pos' => ! empty( $data['description_pos'] ) ? $data['description_pos']                      : 'label',
			'hide_label'      => ! empty( $data['hide_label'] )      ? true                                          : false,
            'front_hidden'    => ! empty( $data['front_hidden'] )    ? true                                          : false,
			'input_class'     => ! empty( $data['input_class'] )     ? sanitize_text_field( $data['input_class'] )   : '',
			'label_class'     => ! empty( $data['label_class'] )     ? sanitize_text_field( $data['label_class'] )   : '',
			'mapping'         => ! empty( $data['mapping'] )         ? $data['mapping']                              : '',
			'kb_search'       => ! empty( $data['kb_search'] )       ? true                                          : false,
			'placeholder'     => ! empty( $data['placeholder'] )     ? sanitize_text_field( $data['placeholder'] )   : '',
			'required'        => ! empty( $data['required'] )        ? true                                          : false,
			'selected'        => ! empty( $data['selected'] )        ? true                                          : false,
			'select_multiple' => ! empty( $data['select_multiple'] ) ? true                                          : false,
			'select_options'  => $select_options,
			'show_logged_in'  => ! empty( $data['show_logged_in'] )  ? $data['show_logged_in']                       : true,
			'type'            => $data['type'],
			'value'           => ! empty( $data['value'] )           ? sanitize_text_field( $data['value'] )         : ''
		);

		if ( ! empty( $settings['chosen_search'] ) && empty( $settings['chosen'] ) )	{
			$settings['chosen_search'] = '';
		}

		// Auto mappings
		$auto_mappings = array(
			'department'               => 'department',
			'ticket_category_dropdown' => 'post_category'
		);
		$auto_mappings = apply_filters( 'kbs_form_auto_mappings', $auto_mappings, $data, $settings );

		foreach( $auto_mappings as $mapping_field => $mapping )	{
			if ( $mapping_field == $settings['type'] )	{
				$settings['mapping'] = $mapping;
			}
		}

		$settings = apply_filters( 'kbs_new_form_field_settings', $settings, $data );

		$args = array(
			'post_type'    => 'kbs_form_field',
			'post_title'   => $data['label'],
			'post_name'    => 'kbs-' . sanitize_title( $data['label'] ),
			'post_content' => '',
			'post_status'  => 'publish',
			'post_parent'  => $data['form_id'],
			'menu_order'   => isset( $data['menu_order'] ) ? $data['menu_order'] : $this->get_next_order(),
			'meta_input'   => array( '_kbs_field_settings' => $settings )
		);

		$args = apply_filters( 'kbs_post_add_form_field_args', $args );

		$field_id = wp_insert_post( $args );

		do_action( 'kbs_post_add_form_field', $data );

		return $field_id;

	} // add_field

	/**
	 * Saves a form field.
	 *
	 * @since	1.0
	 * @param	arr		$data	Field data.
	 * @return	mixed.
	 */
	public function save_field( $data )	{

		if ( ! isset( $data['field_id'], $data['label'], $data['type'] ) )	{
			return false;
		}

		do_action( 'kbs_pre_save_form_field', $data );

        $select_options = array();

		if ( ! empty( $data['select_options'] ) )	{
			$options = explode( "\n", $data['select_options'] );

			if ( ! empty( $options ) )	{
				foreach( $options as $option )	{
					$select_options[ $option ] = $option;
				}
			}
		}

		$settings = array(
            'blank'           => ! empty( $data['blank'] )           ? true                                          : false,
			'chosen'          => ! empty( $data['chosen'] )          ? true                                          : false,
            'chosen_search'   => ! empty( $data['chosen_search'] )   ? sanitize_text_field( $data['chosen_search'] ) : '',
			'description'     => ! empty( $data['description'] )     ? sanitize_text_field( $data['description'] )   : '',
			'description_pos' => ! empty( $data['description_pos'] ) ? $data['description_pos']                      : 'label',
			'hide_label'      => ! empty( $data['hide_label'] )      ? true                                          : false,
            'front_hidden'    => ! empty( $data['front_hidden'] )    ? true                                          : false,
			'input_class'     => ! empty( $data['input_class'] )     ? sanitize_text_field( $data['input_class'] )   : '',
			'label_class'     => ! empty( $data['label_class'] )     ? sanitize_text_field( $data['label_class'] )   : '',
			'mapping'         => ! empty( $data['mapping'] )         ? $data['mapping']                              : '',
			'kb_search'       => ! empty( $data['kb_search'] )       ? true                                          : false,
			'placeholder'     => ! empty( $data['placeholder'] )     ? sanitize_text_field( $data['placeholder'] )   : '',
			'required'        => ! empty( $data['required'] )        ? true                                          : false,
			'selected'        => ! empty( $data['selected'] )        ? true                                          : false,
			'select_multiple' => ! empty( $data['select_multiple'] ) ? true                                          : false,
			'select_options'  => $select_options,
			'show_logged_in'  => ! empty( $data['show_logged_in'] )  ? $data['show_logged_in']                       : true,
			'type'            => $data['type'],
			'value'           => ! empty( $data['value'] )           ? sanitize_text_field( $data['value'] )         : ''
		);

		// Auto mappings
		$auto_mappings = array(
			'department'               => 'department',
			'ticket_category_dropdown' => 'post_category'
		);
		$auto_mappings = apply_filters( 'kbs_form_auto_mappings', $auto_mappings, $data, $settings );

		foreach( $auto_mappings as $mapping_field => $mapping )	{
			if ( $mapping_field == $settings['type'] )	{
				$settings['mapping'] = $mapping;
			}
		}

		$settings = apply_filters( 'kbs_save_form_field_settings', $settings, $data );

		$args = array(
			'ID'           => $data['field_id'],
			'post_title'   => $data['label'],
			'post_name'    => 'kbs-' . sanitize_title( $data['label'] ),
			'post_content' => '',
			'meta_input'   => array( '_kbs_field_settings' => $settings )
		);

		$args = apply_filters( 'kbs_post_save_form_field_args', $args );

		$field_id = wp_update_post( $args );

		do_action( 'kbs_post_save_form_field', $data );

		return $field_id;

	} // save_field

	/**
	 * Determine if a mapping is in use.
	 *
	 * @since	1.0
	 * @param	str		The mapping to check.
	 * @return	str
	 */
	public function has_mapping( $mapping )	{
		foreach( $this->fields as $field )	{
			$settings = $this->get_field_settings( $field->ID );

			if ( ! empty( $settings ) && ! empty( $settings['mapping'] ) && $settings['mapping'] == $mapping )	{
				return true;
			}
		}

		return false;
	} // has_mapping

	/**
	 * Retrieve the form fields.
	 *
	 * @since	1.0
	 * @return	obj
	 */
	public function get_fields() {

		if ( ! isset( $this->fields ) )	{

			$args = array(
				'posts_per_page' => -1,
				'post_type'      => 'kbs_form_field',
				'post_parent'    => $this->ID,
				'post_status'    => 'publish',
				'orderby'        => 'menu_order',
				'order'          => 'ASC'
			);

			$args = apply_filters( 'kbs_get_fields', $args );

			$this->fields = get_posts( $args );

		}

		return $this->fields;
	} // get_fields

	/**
	 * Sets the built-in fields.
	 *
	 * @since	1.0
	 * @return	void
	 */
	function mapped_fields()	{
		if ( ! isset( $this->fields ) )	{
			return;
		}

		$this->mapped_fields = array();

		$mappings = kbs_get_mappings();

		foreach( $this->fields as $field )	{
			$settings = $this->get_field_settings( $field->ID );
			if ( array_key_exists( $settings['mapping'], $mappings ) )	{
				$this->mapped_fields[ $settings['mapping'] ] = $field->post_name;
			}
		}

	} // mapped_fields

    /**
	 * Get the redirect target.
	 *
	 * @since	1.0
	 * @return	int
	 */
	function get_redirect_target()	{
		if ( empty( $this->redirect_to ) )	{
            $this->redirect_to = kbs_get_form_redirect_target( $this->ID );
        }

		return $this->redirect_to;
	} // get_redirect_target

	/*
	 * Get the next positional order for the field.
	 *
	 * @param
	 * @return	int		Field position.
	 */
	public function get_next_order()	{

		$args = array(
			'posts_per_page' => 1,
			'post_type'      => 'kbs_form_field',
			'post_parent'    => $this->ID,
			'post_status'    => 'publish',
			'orderby'        => 'menu_order',
			'order'          => 'DESC'
		);

		$order = 1;

		$fields = get_posts( $args );

		if ( $fields )	{
			if ( ! empty( $fields[0]->menu_order ) )	{
				$order = $fields[0]->menu_order;
				$order++;
			}
		}

		return (int) $order;

	} // get_next_order

	/*
	 * Get the field settings
	 *
	 * @param	int		$field_id		The post ID of the field
	 * @return	arr		$field_settings	Array of field meta data
	 */
	public function get_field_settings( $field_id )	{

		$field_settings = get_post_meta( $field_id, '_kbs_field_settings', true );

		return apply_filters( 'kbs_field_settings', $field_settings, $field_id );

	} // get_field_settings

	/**
	 * Deletes a form.
	 *
	 * @since	1.0
	 * @param	int		$form_id	The form post ID.
	 * @return	mixed	Field post object if successful, otherwise false.
	 */
	public function delete_form( $form_id )	{

		if ( 'kbs_form' != get_post_type( $form_id ) )	{
			return false;
		}

		do_action( 'kbs_pre_delete_form', $form_id );

		foreach ( $this->fields as $field )	{
			$this->delete_field( $field->ID, true );
		}

		$result = wp_delete_post( $form_id, true );

		do_action( 'kbs_post_delete_form', $form_id, $result );

		return $result;
	} // delete_form

	/**
	 * Deletes a field.
	 *
	 * @since	1.0
	 * @param	int		$field_id	The field post ID.
	 * @param	bool	$force		Whether or not to force deletion of a default field.
	 * @return	mixed	Field post object if successful, otherwise false.
	 */
	public function delete_field( $field_id, $force = false )	{
		if ( 'kbs_form_field' != get_post_type( $field_id ) )	{
			return false;
		}

		if ( ! $force && ! kbs_can_delete_field( $field_id ) )	{
			return false;
		}

		do_action( 'kbs_pre_delete_field', $field_id );

		$result = wp_delete_post( $field_id, true );

		do_action( 'kbs_post_delete_field', $field_id );

		return $result;
	} // delete_field

	/**
	 * Displays a field.
	 *
	 * @since	1.0
	 * @param	obj		$field		The field post object.
	 * @param	arr		$settings	The field settings.
	 * @return	str		The field.
	 */
	public function display_field( $field, $settings )	{
		do_action( 'kbs_before_form_field', $field, $settings );
		do_action( 'kbs_before_form_' . $settings['type'] . '_field', $field, $settings );

		/*
		 * Output the field
		 * @since	1.0
		 */
		do_action( 'kbs_form_display_' . $settings['type'] . '_field', $field, $settings );

		do_action( 'kbs_after_form_field', $field, $settings );
		do_action( 'kbs_after_form_' . $settings['type'] . '_field', $field, $settings );
	} // display_field

	/**
	 * Get the submission count.
	 *
	 * @since	1.0
	 * @return	int		Submission count.
	 */
	public function get_submission_count()	{
		$submissions = get_post_meta( $this->ID, '_submission_count', true );

		if ( ! $submissions )	{
			$submissions = 0;
		}

		return $submissions;
	} // get_submission_count

	/**
	 * Increment the submission count.
	 *
	 * @since	1.0
	 * @return	void.
	 */
	public function increment_submissions()	{
		$submissions = $this->get_submission_count();
		$submissions++;

		update_post_meta( $this->ID, '_submission_count', $submissions );
	} // get_submission_count

} // KBS_Form
