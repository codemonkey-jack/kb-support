<?php
/**
 *
 * This class is for registering our meta
 *
 * @package     KBS
 * @subpackage  Classes/Register Meta
 * @copyright   Copyright (c) 2020, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.5
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
    exit;

/**
 * KBS_Register_Meta Class
 *
 * @since	1.5
 */
class KBS_Register_Meta {

	private static $instance;

    /**
     * Meta fields.
     *
     * @since   1.5.2
     * @var     array
     */
    public $meta_fields = array();

    /**
     * Post types.
     *
     * @since   1.5.2
     * @var     array
     */
    public $post_types = array();

	/**
	 * Setup the post meta registration
	 *
	 * @since	1.5
	 */
	private function __construct() {
        $this->post_types = $this->get_post_types();

        $this->set_meta_fields();

		$this->hooks();
	} // __construct

	/**
	 * Get the one true instance of KBS_Register_Meta.
	 *
	 * @since	1.5
	 * @return	$instance
	 */
	static public function instance() {
		if ( !self::$instance ) {
			self::$instance = new KBS_Register_Meta();
		}

		return self::$instance;
	} // instance

	/**
	 * Register the hooks to kick off meta registration.
	 *
	 * @since  2.5
	 * @return void
	 */
	private function hooks() {
        add_action( 'init', array( $this, 'register_post_meta' ), 11 );
    } // hooks

    /**
     * Retrieve post types.
     *
     * @since   1.5.2
     * @return  array
     */
    public function get_post_types()    {
        $post_types = array(
            'kbs_ticket',
            'kbs_ticket_reply',
            'kbs_form',
            'kbs_form_field',
            'kbs_company'
        );

        $post_types = apply_filters( 'kbs_register_meta_post_types', $post_types );

        return $post_types;
    } // get_post_types

    /**
     * Register meta fields.
     *
     * @since   1.5
     * @param   string  $post_type      Post type to which the meta belongs
     * @param   array   $meta_fields    Array of fields to register
     * @return  void
     */
    public function register_post_meta()  {
        foreach( $this->meta_fields as $post_type => $fields )	{
            foreach( $fields as $key => $args ) {
                register_post_meta( $post_type, $key, $args );
            }
		}
    } // register_post_meta

    /**
     * Set all meta fields.
     *
     * @since   1.5.2
     * @return  array
     */
    public function set_meta_fields()   {
        foreach( $this->post_types as $type )   {
            $method = "get_{$type}_meta_fields";
            if ( method_exists( $this, $method ) )  {
                $this->meta_fields[ $type ] = $this->$method();
            }
        }

        $this->meta_fields = apply_filters( 'kbs_post_meta_fields', $this->meta_fields );
    } // set_meta_fields

    /**
     * Retrieve the meta keys for this post type.
     *
     * Array format: key = meta_name, value = $args (see register_meta)
     *
     * @since	1.5
     * @return	array	Array of meta key parameters
     */
    public function get_kbs_ticket_reply_meta_fields()	{
        $object = get_post_type_object( 'kbs_ticket_reply' );

        $meta_fields = array(
            '_kbs_reply_customer_id' => array(
                'type'              => 'integer',
                'description'       => __( 'ID of the ticket customer.', 'kb-support' ),
                'single'            => true,
                'sanitize_callback' => 'absint',
                'auth_callback'     => function() {
                    return kbs_can_view_customers();
                },
                'show_in_rest'      => array(
                    'schema' => array(
                        'type'  => 'integer'
                    )
                )
            ),
            '_kbs_reply_agent_id' => array(
                'type'              => 'integer',
                'description'       => __( 'ID of the ticket agent.', 'kb-support' ),
                'single'            => true,
                'auth_callback'     => function() {
                    return kbs_can_view_customers();
                },
                'show_in_rest'      => array(
                    'schema' => array(
                        'type'  => 'integer'
                    )
                )
            ),
            '_kbs_reply_participant' => array(
                'type'              => 'string',
                'description'       => __( 'Participant email address.', 'kb-support' ),
                'single'            => true,
                'auth_callback'     => function() {
                    return kbs_can_view_customers();
                },
                'show_in_rest'      => array(
                    'schema' => array(
                        'type'  => 'string'
                    )
                )
            ),
            '_kbs_reply_resolution' => array(
                'type'              => 'boolean',
                'description'       => sprintf(
                    __( 'Whether or not this reply resolved the %s.', 'kb-support' ),
                    kbs_get_ticket_label_singular( true )
                ),
                'single'            => true,
                'auth_callback'     => function() {
                    return kbs_can_view_customers();
                },
                'show_in_rest'      => array(
                    'schema' => array(
                        'type'  => 'boolean'
                    )
                )
            )
        );

        $meta_fields = apply_filters( "kbs_ticket_reply_meta_fields", $meta_fields );

        return $meta_fields;
    } // get_kbs_ticket_reply_meta_fields

    /**
     * Retrieve the meta keys for forms.
     *
     * Array format: key = meta_name, value = $args (see register_meta)
     *
     * @since	1.5
     * @return	array	Array of meta key parameters
     */
    public function get_kbs_form_meta_fields()	{
        $object = get_post_type_object( 'kbs_form' );

        $meta_fields = array(

            '_redirect_page' => array(
                'type'         => 'integer',
                'description'  => __( 'Redirect page ID.', 'kb-support' ),
                'single'       => true,
                'default'      => 0,
                'show_in_rest' => array(
                    'schema' => array(
                        'type'    => 'integer',
                        'default' => 0
                    )
                )
            ),
            '_submission_action' => array(
	            'type'         => 'string',
	            'description'  => __( 'After submission action', 'kb-support' ),
	            'single'       => true,
	            'default'      => 'redirect',
	            'show_in_rest' => array(
		            'schema' => array(
			            'type'    => 'string',
			            'default' => 'redirect'
		            )
	            )
            ),
            '_submission_text' => array(
	            'type'         => 'string',
	            'description'  => __( 'After submission text', 'kb-support' ),
	            'single'       => true,
	            'default'      => esc_html__( 'Ticket has been created. You can check your tickets [link] here [/link]', 'kb-support' ),
	            'show_in_rest' => array(
		            'schema' => array(
			            'type'    => 'string',
			            'default' => esc_html__( 'Ticket has been created. You can check your tickets [link] here [/link]', 'kb-support' ),
		            )
	            )
            ),
            '_submission_count' => array(
                'type'         => 'integer',
                'description'  => __( 'Submission count.', 'kb-support' ),
                'single'       => true,
                'default'      => 0,
                'show_in_rest' => array(
                    'schema' => array(
                        'items' => array(
                            'type'    => 'integer',
                            'default' => 0
                        )
                    )
                )
            )
        );

        $meta_fields = apply_filters( "kbs_form_meta_fields", $meta_fields );

        return $meta_fields;
    } // get_kbs_form_meta_fields

    /**
     * Retrieve the meta keys for forms fields
     *
     * Array format: key = meta_name, value = $args (see register_meta)
     *
     * @since	1.5
     * @return	array	Array of meta key parameters
     */
    public function get_kbs_form_field_meta_fields()	{
        $object = get_post_type_object( 'kbs_form_field' );

        $meta_fields = array(
            '_default_field' => array(
                'type'         => 'string',
                'description'  => __( 'Form field settings.', 'kb-support' ),
                'single'       => true,
                'default'      => '',
                'show_in_rest' => array(
                    'schema' => array(
                        'type'    => 'string',
                        'default' => ''
                    )
                )
            ),
            '_kbs_field_settings' => array(
                'type'         => 'object',
                'description'  => __( 'Form field settings.', 'kb-support' ),
                'single'       => true,
                'default'      => array(),
                'show_in_rest' => array(
                    'schema' => array(
                        'type'       => 'object',
                        'properties' => array(
                            'blank' => array(
                                'type' => 'boolean'
                            ),
                            'chosen' => array(
                                'type' => 'boolean'
                            ),
                            'chosen_search' => array(
                                'type' => 'string'
                            ),
                            'description' => array(
                                'type' => 'string'
                            ),
                            'description_pos' => array(
                                'type' => 'string'
                            ),
                            'hide_label' => array(
                                'type' => 'boolean'
                            ),
                            'input_class' => array(
                                'type' => 'string'
                            ),
                            'label_class' => array(
                                'type' => 'string'
                            ),
                            'mapping' => array(
                                'type' => 'string'
                            ),
                            'kb_search' => array(
                                'type' => 'boolean'
                            ),
                            'placeholder' => array(
                                'type' => 'string'
                            ),
                            'required' => array(
                                'type' => 'boolean'
                            ),
                            'selected' => array(
                                'type' => 'boolean'
                            ),
                            'select_multiple' => array(
                                'type' => 'boolean'
                            ),
                            'select_options' => array(
                                'type' => 'array'
                            ),
                            'show_logged_in' => array(
                                'type' => 'boolean'
                            ),
                            'type' => array(
                                'type' => 'string'
                            ),
                            'value' => array(
                                'type' => 'string'
                            )
                        )
                    )
                )
            )
        );

        $meta_fields = apply_filters( "kbs_form_field_meta_fields", $meta_fields );

        return $meta_fields;
    } // get_kbs_form_field_meta_fields

    /**
     * Register kbs_company meta fields.
     *
     * @since   1.5
     * @return  void
     */
    public function get_kbs_company_meta_fields()   {
        $object = get_post_type_object( 'kbs_company' );

		$meta_fields = array(
			'_kbs_company_customer' => array(
				'type'              => 'integer',
				'description'       => __( 'KBS ID of Customer who is the primary company contact.', 'kb-support' ),
				'single'            => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => function() {
					return current_user_can( "edit_{$object->name}" );
				},
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'integer'
					)
				)
			),
            '_kbs_company_contact' => array(
				'type'              => 'string',
				'description'       => __( 'Name of customer who is the primary company contact.', 'kb-support' ),
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => function() {
					return current_user_can( "edit_{$object->name}" );
				},
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'string'
					)
				)
			),
            '_kbs_company_email' => array(
				'type'              => 'string',
				'description'       => __( 'Email of customer who is the primary company contact.', 'kb-support' ),
				'single'            => true,
				'sanitize_callback' => 'sanitize_email',
				'auth_callback'     => function() {
					return current_user_can( "edit_{$object->name}" );
				},
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'string'
					)
				)
			),
            '_kbs_company_phone' => array(
				'type'              => 'string',
				'description'       => __( 'Phone number for company.', 'kb-support' ),
				'single'            => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => function() {
					return current_user_can( "edit_{$object->name}" );
				},
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'string'
					)
				)
			),
            '_kbs_company_website' => array(
				'type'              => 'string',
				'description'       => __( 'Web address for company.', 'kb-support' ),
				'single'            => true,
				'sanitize_callback' => 'esc_url_raw',
				'auth_callback'     => function() {
					return current_user_can( "edit_{$object->name}" );
				},
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'string'
					)
				)
			)
		);

		$meta_fields = apply_filters( "kbs_company_meta_fields", $meta_fields );

        return $meta_fields;
    } // get_kbs_company_meta_fields

	/**
	 * Sanitize an int array.
	 *
	 * @since	1.5
	 * @param	array	$value	The value passed into the meta
	 * @return	array	The sanitized value
	 */
	public function sanitize_int_array( $value )	{
		$value = $this->sanitize_array( $value );
		$value = array_map( 'absint', $value );

		return $value;
	} // sanitize_int_array

	/**
	 * Sanitize a general array.
	 *
	 * @since	1.5
	 * @param	array	$value	The value passed into the meta
	 * @return	array	The sanitized value
	 */
	public function sanitize_array( $value )	{
		if ( ! is_array( $value ) ) {
			if ( is_object( $value ) ) {
				$value = (array) $value;
			}

			if ( is_serialized( $value ) ) {
				preg_match( '/[oO]\s*:\s*\d+\s*:\s*"\s*(?!(?i)(stdClass))/', $value, $matches );
				if ( ! empty( $matches ) ) {
					return false;
				}

				$value = (array) maybe_unserialize( $value );
			}
		}

		return $value;
	} // sanitize_array
} // KBS_Register_Meta

KBS_Register_Meta::instance();
