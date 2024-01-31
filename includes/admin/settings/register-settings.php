<?php
/**
 * Register Settings.
 *
 * Taken from Easy Digital Downloads.
 *
 * @package     KBS
 * @subpackage  Admin/Settings
 * @copyright   Copyright (c) 2017, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Get an option
 *
 * Looks to see if the specified setting exists, returns default if not
 *
 * @since	1.0
 * @return	mixed
 */
function kbs_get_option( $key = '', $default = false ) {
	global $kbs_options;
	$value = ! empty( $kbs_options[ $key ] ) ? $kbs_options[ $key ] : $default;
	$value = apply_filters( 'kbs_get_option', $value, $key, $default );
	return apply_filters( 'kbs_get_option_' . $key, $value, $key, $default );
} // kbs_get_option

/**
 * Update an option
 *
 * Updates a kbs setting value in both the db and the global variable.
 * Warning: Passing in an empty, false or null string value will remove
 *          the key from the kbs_options array.
 *
 * @since	1.0
 * @param	str				$key	The Key to update
 * @param	str|bool|int	$value	The value to set the key to
 * @return	bool			True if updated, false if not.
 */
function kbs_update_option( $key = '', $value = false ) {

	// If no key, exit
	if ( empty( $key ) ){
		return false;
	}

	if ( empty( $value ) ) {
		$remove_option = kbs_delete_option( $key );
		return $remove_option;
	}

	// First let's grab the current settings
	$options = get_option( 'kbs_settings' );

	// Let's let devs alter that value coming in
	$value = apply_filters( 'kbs_update_option', $value, $key );

	// Next let's try to update the value
	$options[ $key ] = $value;
	$did_update = update_option( 'kbs_settings', $options );

	// If it updated, let's update the global variable
	if ( $did_update ){
		global $kbs_options;
		$kbs_options[ $key ] = $value;

	}

	return $did_update;
} // kbs_update_option

/**
 * Remove an option.
 *
 * Removes a kbs setting value in both the db and the global variable.
 *
 * @since	1.0
 * @param	str		$key	The Key to delete.
 * @return	bool	True if updated, false if not.
 */
function kbs_delete_option( $key = '' ) {

	// If no key, exit
	if ( empty( $key ) ){
		return false;
	}

	// First let's grab the current settings
	$options = get_option( 'kbs_settings' );

	// Next let's try to update the value
	if( isset( $options[ $key ] ) ) {

		unset( $options[ $key ] );

	}

	$did_update = update_option( 'kbs_settings', $options );

	// If it updated, let's update the global variable
	if ( $did_update ){
		global $kbs_options;
		$kbs_options = $options;
	}

	return $did_update;
} // kbs_delete_option

/**
 * Get Settings.
 *
 * Retrieves all plugin settings.
 *
 * @since	1.0
 * @return	arr		KBS settings.
 */
function kbs_get_settings() {
	$settings = get_option( 'kbs_settings' );

	if( empty( $settings ) ) {

		$settings = array();

		update_option( 'kbs_settings', $settings );

	}

	return apply_filters( 'kbs_get_settings', $settings );
} // kbs_get_settings

/**
 * Add all settings sections and fields.
 *
 * @since	1.0
 * @return	void
*/
function kbs_register_settings() {

	if ( false == get_option( 'kbs_settings' ) ) {
		add_option( 'kbs_settings' );
	}

	foreach ( kbs_get_registered_settings() as $tab => $sections ) {
		foreach ( $sections as $section => $settings) {

			// Check for backwards compatibility
			$section_tabs = kbs_get_settings_tab_sections( $tab );
			if ( ! is_array( $section_tabs ) || ! array_key_exists( $section, $section_tabs ) ) {
				$section = 'main';
				$settings = $sections;
			}

			add_settings_section(
				'kbs_settings_' . $tab . '_' . $section,
				__return_null(),
				'__return_false',
				'kbs_settings_' . $tab . '_' . $section
			);

			foreach ( $settings as $option ) {
				// For backwards compatibility
				if ( empty( $option['id'] ) ) {
					continue;
				}

				$args = wp_parse_args( $option, array(
				    'section'       => $section,
				    'id'            => null,
				    'desc'          => '',
				    'name'          => '',
				    'size'          => null,
				    'options'       => '',
				    'std'           => '',
				    'min'           => null,
				    'max'           => null,
				    'step'          => null,
				    'chosen'        => null,
				    'placeholder'   => null,
				    'allow_blank'   => true,
				    'readonly'      => false,
				    'faux'          => false,
				    'tooltip_title' => false,
				    'tooltip_desc'  => false,
				    'field_class'   => '',
                    'class'         => 'kbs_option_' . str_replace( '-', '_', $option['id'] )
				) );

				add_settings_field(
					'kbs_settings[' . $args['id'] . ']',
					$args['name'],
					function_exists( 'kbs_' . $args['type'] . '_callback' ) ? 'kbs_' . $args['type'] . '_callback' : 'kbs_missing_callback',
					'kbs_settings_' . $tab . '_' . $section,
					'kbs_settings_' . $tab . '_' . $section,
					$args
				);
			}
		}

	}

	// Creates our settings in the options table
	register_setting( 'kbs_settings', 'kbs_settings', 'kbs_settings_sanitize' );

} // kbs_register_settings
add_action( 'admin_init', 'kbs_register_settings' );

/**
 * Retrieve the array of plugin settings.
 *
 * @since	1.0
 * @return	arr
*/
function kbs_get_registered_settings() {

	$single = kbs_get_ticket_label_singular();
	$plural = kbs_get_ticket_label_plural();

	/**
	 * 'Whitelisted' KBS settings, filters are provided for each settings
	 * section to allow extensions and other plugins to add their own settings.
	 */
	$kbs_settings = array(
		/** General Settings */
		'general' => apply_filters( 'kbs_settings_general',
			array(
				'pages' => array(
					'page_settings' => array(
						'id'   => 'page_settings',
						'name' => '<h3>' . esc_html__( 'Page Settings', 'kb-support' ) . '</h3>',
						'desc' => '',
						'type' => 'header'
					),
					'submission_page'   => array(
						'id'      => 'submission_page',
						'name'    => esc_html__( 'Submission Page', 'kb-support' ),
						'desc'    => sprintf( wp_kses_post( __( 'This is the page where customers will submit their %s. Should contain the <code>[kbs_submit]</code> shortcode.', 'kb-support' ) ), strtolower( $plural ) ),
						'type'    => 'select',
						'chosen'  => true,
						'options' => kbs_get_pages(),
					),
					'tickets_page'   => array(
						'id'      => 'tickets_page',
						'name'    => sprintf( esc_html__( '%s Page', 'kb-support' ), $plural ),
						'desc'    => sprintf( wp_kses_post( __( 'This is the page where can view and reply to their %s. Should contain the <code>[kbs_tickets]</code> shortcode', 'kb-support' ) ), strtolower( $plural ) ),
						'type'    => 'select',
						'chosen'  => true,
						'options' => kbs_get_pages(),
					)
				),
                'customers' => array(
                    'customer_registration_settings_header' => array(
						'id'   => 'customer_registration_settings_header',
						'name' => '<h3>' . sprintf( esc_html__( 'Registration Settings', 'kb-support' ), $single ) . '</h3>',
						'type' => 'header'
					),
                    'show_name_fields' => array(
                        'id'      => 'show_name_fields',
                        'name'    => esc_html__( 'Name Fields', 'kb-support' ),
						'desc'    => esc_html__( 'Select whether to display both the First and Last name fields on the registration form, or just the First name.', 'kb-support' ),
                        'type'    => 'select',
                        'chosen'  => true,
                        'options' => array(
                            'both'  => esc_html__( 'Both First and Last Name', 'kb-support' ),
                            'first' => esc_html__( 'First Name Only', 'kb-support' ),
							'none'  => esc_html__( 'None', 'kb-support' )
                        ),
                        'std'     => 'both'
                    ),
                    'require_name_fields' => array(
                        'id'      => 'require_name_fields',
                        'name'    => esc_html__( 'Required Name Fields', 'kb-support' ),
						'desc'    => esc_html__( 'Select whether both the First and Last name fields are required fields on the registration form, or just the First name.', 'kb-support' ),
                        'type'    => 'select',
                        'chosen'  => true,
                        'options' => array(
                            'both'  => esc_html__( 'Both First and Last Name', 'kb-support' ),
                            'first' => esc_html__( 'First Name Only', 'kb-support' ),
							'none'  => esc_html__( 'None', 'kb-support' )
                        ),
                        'std'     => 'both'
                    ),
                    'reg_name_format' => array(
                        'id'      => 'reg_name_format',
                        'name'    => esc_html__( 'Username Format', 'kb-support' ),
						'desc'    => esc_html__( 'Choose which format you would like usernames to created in following successful registration.', 'kb-support' ),
                        'type'    => 'select',
                        'chosen'  => true,
                        'options' => array(
                            'email'        => esc_html__( 'Full Email Address', 'kb-support' ),
                            'email_prefix' => esc_html__( 'Email Address Prefix', 'kb-support' ),
                            'full_name'    => esc_html__( 'First and Last Name', 'kb-support' )
                        ),
                        'std'     => 'email'
                    ),
                    'default_role' => array(
                        'id'      => 'default_role',
                        'name'    => esc_html__( 'Default Role', 'kb-support' ),
						'desc'    => esc_html__( 'Select the role to assign to a newly registered user.', 'kb-support' ),
                        'type'    => 'select',
                        'chosen'  => true,
                        'options' => kbs_get_user_role_options(),
                        'std'     => 'support_customer'
                    ),
                    'ticket_manager_settings_header' => array(
						'id'   => 'ticket_manager_settings_header',
						'name' => '<h3>' . sprintf( esc_html__( '%s Manager Settings', 'kb-support' ), $single ) . '</h3>',
						'type' => 'header'
					),
                    'replies_to_load' => array(
						'id'      => 'replies_to_load',
						'name'    => esc_html__( 'Default Replies to Load', 'kb-support' ),
						'desc'    => sprintf( wp_kses_post( __( 'Enter the number of replies a customer should see by default on the %s Manager screen. Enter <code>0</code> to load all. Registered customers can change this setting on their profile page.', 'kb-support' ) ), strtolower( $single ) ),
						'type'    => 'number',
						'size'    => 'small',
                        'min'     => '0',
						'max'     => '50',
						'std'     => '5'
					),
					'replies_to_expand' => array(
						'id'      => 'replies_to_expand',
						'name'    => esc_html__( 'Default Replies to Expand', 'kb-support' ),
						'desc'    => sprintf( wp_kses_post( __( 'Enter the number of replies that should auto expand for a customer on the %s Manager screen. Enter <code>0</code> to expand none. Registered customers can change this setting on their profile page.', 'kb-support' ) ), strtolower( $single ) ),
						'type'    => 'number',
						'size'    => 'small',
                        'min'     => '0',
						'max'     => '50',
						'std'     => 0
					),
                    'hide_closed_front' => array(
						'id'      => 'hide_closed_front',
						'name'    => sprintf( esc_html__( 'Hide Closed %s?', 'kb-support' ), $plural ),
						'desc'    => sprintf( esc_html__( 'If enabled, closed %s will not be displayed by default for customers on the %s Manager screen. Registered customers can change this setting on their profile page', 'kb-support' ), strtolower( $plural ), $single ),
						'type'    => 'checkbox',
						'std'     => '0'
					)
                )
			)
		),
		/** Ticket Settings */
		'tickets' => apply_filters( 'kbs_ticket_settings',
			array(
				'main'   => array(
					'ticket_settings_header' => array(
						'id'   => 'ticket_settings_header',
						'name' => '<h3>' . sprintf( esc_html__( '%s Settings', 'kb-support' ), $single ) . '</h3>',
						'type' => 'header'
					),
					'disable_tickets' => array(
						'id'      => 'disable_tickets',
						'name'    => sprintf( esc_html__( 'Disable Tickets?', 'kb-support' ), $plural ),
						'desc'    => esc_html__( 'Check to disable the KB Ticketing functionality of the plugin.', 'kb-support' ),
						'type'    => 'checkbox'
					),
					'enable_sequential' => array(
						'id'      => 'enable_sequential',
						'name'    => sprintf( esc_html__( 'Sequential %s Numbers?', 'kb-support' ), $single ),
						'desc'    => sprintf( esc_html__( 'Check this box to enable sequential %s numbers', 'kb-support' ), strtolower( $single ) ),
						'type'    => 'checkbox'
					),
					'sequential_start' => array(
						'id'      => 'sequential_start',
						'name'    => esc_html__( 'Sequential Starting Number', 'kb-support' ),
						'desc'    => esc_html__( 'The number at which the sequence should begin', 'kb-support' ),
						'type'    => 'number',
						'size'    => 'small',
						'std'     => '1'
					),
					'ticket_prefix' => array(
						'id'      => 'ticket_prefix',
						'name'    => sprintf( esc_html__( "Prefix for %s ID's", 'kb-support' ), $single ),
						'desc'    => '',
						'type'    => 'text',
						'size'    => 'small'
					),
					'ticket_suffix' => array(
						'id'      => 'ticket_suffix',
						'name'    => sprintf( esc_html__( "Suffix for %s ID's", 'kb-support' ), $single ),
						'desc'    => '',
						'type'    => 'text',
						'size'    => 'small'
					),
					'show_count' => array(
						'id'      => 'show_count',
						'name'    => sprintf( esc_html__( 'Show %s Count?', 'kb-support' ), $single ),
						'desc'    => sprintf( esc_html__( 'Whether or not to display the open %s count next to the %s menu', 'kb-support' ), strtolower( $single ), $plural ),
						'type'    => 'checkbox'
					),
                    'show_count_menubar' => array(
                        'id'      => 'show_count_menubar',
						'name'    => sprintf( esc_html__( 'Show Count on Menu Bar?', 'kb-support' ), $single ),
						'desc'    => sprintf( esc_html__( 'Choose an option for displaying the open %s count on the WordPress menu bar', 'kb-support' ), strtolower( $single ), $plural ),
						'type'    => 'select',
                        'chosen'  => true,
                        'options' => array(
                            'none'        => esc_html__( 'Do not display', 'kb-support' ),
                            'admin_front' => esc_html__( 'Both Admin and Front End', 'kb-support' ),
                            'admin'       => esc_html__( 'Admin only', 'kb-support' ),
                            'front'       => esc_html__( 'Front End Only', 'kb-support' )
                        ),
                        'std'     => 'front'
                    ),
					'enable_participants' => array(
						'id'      => 'enable_participants',
						'name'    => esc_html__( 'Enable Participants?', 'kb-support' ),
						'desc'    => sprintf( esc_html__( 'If enabled, participants can be added to %s and each participant will be able to view and respond to the %s', 'kb-support' ), strtolower( $plural ), strtolower( $single ) ),
						'type'    => 'checkbox'
					),
					'hide_closed' => array(
						'id'      => 'hide_closed',
						'name'    => sprintf( esc_html__( 'Hide Closed %s?', 'kb-support' ), $plural ),
						'desc'    => sprintf( esc_html__( 'Enable this option to remove closed %1$s from the default view on the admin %1$s screen', 'kb-support' ), strtolower( $plural ) ),
						'type'    => 'checkbox'
					)
				),
				'submit' => array(
					'submit_settings_header' => array(
						'id'   => 'submit_settings_header',
						'name' => '<h3>' . esc_html__( 'Submission Settings', 'kb-support' ) . '</h3>',
						'type' => 'header'
					),
					'enforce_ssl' => array(
						'id'   => 'enforce_ssl',
						'name' => esc_html__( 'Enforce SSL for Submissions?', 'kb-support' ),
						'desc' => esc_html__( 'Check this to force users to be redirected to the secure ticket submission page. You must have an SSL certificate installed to use this option.', 'kb-support' ),
						'type' => 'checkbox',
					),
					'logged_in_only' => array(
						'id'          => 'logged_in_only',
						'name'        => esc_html__( 'Disable Guest Submissions?', 'kb-support' ),
						'desc'        => sprintf( esc_html__( 'Require that users be logged in to submit %s.', 'kb-support' ), strtolower( $plural ) ),
						'type'        => 'checkbox',
						'std'         => '1',
                        'field_class' => 'logged_in_only'
					),
                    'auto_add_user' => array(
						'id'      => 'auto_add_user',
						'name'    => esc_html__( 'Auto Create User?', 'kb-support' ),
						'desc'    => esc_html__( 'If enabled, a WP User account will automatically be created when a new support customer is added.', 'kb-support' ),
						'type'    => 'checkbox',
						'std'     => false
					),
					'show_register_form' => array(
						'id'      => 'show_register_form',
						'name'    => esc_html__( 'Show Register / Login Form?', 'kb-support' ),
						'desc'    => esc_html__( 'Display the registration and login forms on the submission page for non-logged-in users.', 'kb-support' ),
						'type'    => 'select',
                        'chosen'  => true,
						'std'     => 'none',
						'options' => array(
							'both'         => esc_html__( 'Registration and Login Forms', 'kb-support' ),
							'registration' => esc_html__( 'Registration Form Only', 'kb-support' ),
							'login'        => esc_html__( 'Login Form Only', 'kb-support' ),
							'none'         => esc_html__( 'None', 'kb-support' )
						),
					),
					'form_submit_label' => array(
						'id'   => 'form_submit_label',
						'name' => esc_html__( 'Submit Label', 'kb-support' ),
						'desc' => sprintf( esc_html__( 'The label for the %s form submit button.', 'kb-support' ), strtolower( $single ) ),
						'type' => 'text',
						'std'  => sprintf( esc_html__( 'Submit %s', 'kb-support' ), $single )
					),
					'ticket_reply_label' => array(
						'id'   => 'ticket_reply_label',
						'name' => esc_html__( 'Reply Label', 'kb-support' ),
						'desc' => sprintf( esc_html__( 'The label for the %s reply form submit button.', 'kb-support' ), strtolower( $single ) ),
						'type' => 'text',
						'std'  => esc_html__( 'Reply', 'kb-support' )
					),
					'file_uploads' => array(
						'id'      => 'file_uploads',
						'name'    => esc_html__( 'Allow File Uploads', 'kb-support' ),
						'desc'    => sprintf( esc_html__( 'Maximum number of files that can be attached during %s creation or reply.', 'kb-support' ), strtolower( $single ) ),
						'type'    => 'number',
						'size'    => 'small',
						'max'     => '10',
						'std'     => '0'
					),
					'file_extensions' => array(
						'id'      => 'file_extensions',
						'name'    => esc_html__( 'Allowed File Extensions', 'kb-support' ),
						'desc'    => sprintf( esc_html__( 'Enter a list of file extensions that a customer may upload during %s submission. Seperate each extension with a comma.', 'kb-support' ), strtolower( $single ) ),
						'type'    => 'textarea',
						'std'     => kbs_get_default_file_types()
					)
				),
                'replies' => array(
                    'customer_can_repoen' => array(
						'id'      => 'customer_can_repoen',
						'name'    => sprintf( esc_html__( 'Re-open %s?', 'kb-support' ), $plural ),
						'desc'    => sprintf( esc_html__( 'If enabled, by replying to a closed %1$s, customers can re-open the %1$s', 'kb-support' ), strtolower( $single ) ),
						'type'    => 'checkbox',
                        'std'     => '0'
					),
					'agent_update_status_reply' => array(
						'id'      => 'agent_update_status_reply',
						'name'    => esc_html__( 'Agents Set Reply Status?', 'kb-support' ),
						'desc'    => sprintf( esc_html__( 'If enabled, agents will be able to update a %s status whilst replying.', 'kb-support' ), strtolower( $plural ) ),
						'type'    => 'checkbox',
						'std'     => '1'
					)
                ),
				'agents' => array(
					'agent_settings_header' => array(
						'id'   => 'agent_settings_header',
						'name' => '<h3>' . esc_html__( 'Agents', 'kb-support' ) . '</h3>',
						'type' => 'header'
					),
					'admin_agents' => array(
						'id'      => 'admin_agents',
						'name'    => esc_html__( 'Administrators are Agents?', 'kb-support' ),
						'desc'    => wp_kses_post( __( 'If enabled, users with the <code>Administrator</code> role will also be Support Agents.', 'kb-support' ) ),
						'type'    => 'checkbox',
						'std'     => '1'
					),
					'restrict_agent_view' => array(
						'id'      => 'restrict_agent_view',
						'name'    => sprintf( esc_html__( 'Restrict Agent %s View?', 'kb-support' ), $single ),
						'desc'    => sprintf( esc_html__( 'If enabled, Support Agents will only be able to see %1$s that are assigned to them directly, assigned to a department to which they are a member, or %1$s that are not yet assigned. If the current user is a Support Manager or an Administrator, they will always see all %1$s.', 'kb-support' ), strtolower( $plural ) ),
						'type'    => 'checkbox'
					),
                    'multiple_agents' => array(
						'id'      => 'multiple_agents',
						'name'    => sprintf( esc_html__( 'Multiple Agents per %s?', 'kb-support' ), $single ),
						'desc'    => sprintf( esc_html__( 'If enabled, multiple agents can be assigned to a %s and work collaboratively towards resolution.', 'kb-support' ), strtolower( $single ) ),
						'type'    => 'checkbox'
					),
					'agent_status'  => array(
						'id'      => 'agent_status',
						'name'    => esc_html__( 'Display Agent Status?', 'kb-support' ),
						'desc'    => sprintf( esc_html__( 'If enabled, customers will see an indicator as to whether or not the assigned agent is online when reviewing their %s.', 'kb-support' ), strtolower( $single ) ),
						'type'    => 'checkbox',
						'std'     => '0'
					),
					'enable_departments'  => array(
						'id'      => 'enable_departments',
						'name'    => esc_html__( 'Enable Departments?', 'kb-support' ),
						'desc'    => sprintf( esc_html__( 'If enabled, agents can be added to departments and %s can be assigned to departments.', 'kb-support' ), strtolower( $plural ) ),
						'type'    => 'checkbox',
						'std'     => '0'
					),
					'assign_settings_header' => array(
						'id'   => 'assign_settings_header',
						'name' => '<h3>' . sprintf( esc_html__( '%s Assignment', 'kb-support' ), $single ) . '</h3>',
						'type' => 'header'
					),
					'assign_on_submit' => array(
						'id'      => 'assign_on_submit',
						'name'    => sprintf( esc_html__( 'Auto Assign new %s?', 'kb-support' ), $plural ),
						'desc'    => sprintf( esc_html__( 'Select an option to automatically assign a %s to an agent when it is received', 'kb-support' ), strtolower( $single ) ),
						'type'    => 'select',
                        'chosen'  => true,
						'options' => array(
							'0'      => esc_html__( 'Do not Auto Assign', 'kb-support' ),
							'least'  => sprintf( esc_html__( 'Least %s', 'kb-support' ), $plural ),
							'random' => esc_html__( 'Random', 'kb-support' ),
						),
						'std'     => '0'
					),
					'auto_assign_agent' => array(
						'id'      => 'auto_assign_agent',
						'name'    => esc_html__( 'Auto Assign on Access?', 'kb-support' ),
						'desc'    => sprintf( wp_kses_post( __( 'If enabled, unassigned %1$s will be auto assigned to an agent when they access the %2$s. The %2$s status will also update to <code>open</code> if currently <code>new</code>. Avoids agent "Cherry Picking"', 'kb-support'  ) ), strtolower( $plural ), strtolower( $single ) ),
						'type'        => 'checkbox'
					)
				),
				'sla' => array(
					'sla_settings_header' => array(
						'id'   => 'sla_settings_header',
						'name' => '<h3>' . esc_html__( 'SLA Settings', 'kb-support' ) . '</h3>',
						'type' => 'header'
					),
					'sla_tracking' => array(
						'id'      => 'sla_tracking',
						'name'    => esc_html__( 'Enable SLA Tracking', 'kb-support' ),
						'type'    => 'checkbox',
						'std'     => '0'
					),
					'sla_response_time' => array(
						'id'      => 'sla_response_time',
						'name'    => esc_html__( 'Target Response Time', 'kb-support' ),
						'type'    => 'select',
						'chosen'  => true,
						'options' => kbs_get_response_time_options(),
						'std'     => 4 * HOUR_IN_SECONDS,
						'desc'    => sprintf( esc_html__( 'Enter your targeted first response time for %s.', 'kb-support' ), strtolower( $plural ) )
					),
					'sla_response_time_warn' => array(
						'id'      => 'sla_response_time_warn',
						'name'    => esc_html__( 'Warn if within', 'kb-support' ),
						'type'    => 'number',
						'size'    => 'small',
						'std'     => '1',
						'desc'    => wp_kses_post( __( 'The number of hours before <code>Target Response Time</code> expires that the SLA status should be set to warn.', 'kb-support' ) )
					),
					'sla_resolve_time' => array(
						'id'      => 'sla_resolve_time',
						'name'    => esc_html__( 'Target Resolution Time', 'kb-support' ),
						'type'    => 'select',
						'chosen'  => true,
						'options' => kbs_get_resolve_time_options(),
						'std'     => 2 * DAY_IN_SECONDS,
						'desc'    => sprintf( esc_html__( 'Enter your targeted resolution time for %s.', 'kb-support' ), strtolower( $plural ) )
					),
					'sla_resolve_time_warn' => array(
						'id'      => 'sla_resolve_time_warn',
						'name'    => esc_html__( 'Warn if within', 'kb-support' ),
						'type'    => 'number',
						'size'    => 'small',
						'std'     => '12',
						'desc'    => wp_kses_post( __( 'The number of hours before <code>Target Resolution Time</code> expires that the SLA status should be set to warn.', 'kb-support' ) )
					),
					'support_times' => array(
						'id'   => 'support_times',
						'name' => '<h3>' . esc_html__( 'Support Hours', 'kb-support' ) . '</h3>',
						'desc' => '',
						'type' => 'header'
					),
					'define_support_hours'    => array(
						'id'      => 'define_support_hours',
						'name'    => esc_html__( 'Define Support Hours?', 'kb-support' ),
						'desc'    => sprintf( esc_html__( 'Enable to define your Support Hours', 'kb-support' ), strtolower( $plural ) ),
						'type'    => 'checkbox',
						'std'     => '0'
					),
					'support_hours'    => array(
						'id'      => 'support_hours',
						'name'    => esc_html__( 'Hours of Support', 'kb-support' ),
						'type'    => 'support_hours'
					),
				)
			)
		),
		/** KB Settings */
		'articles' => apply_filters( 'kbs_article_settings',
			array(
				'main'   => array(
					'kb_settings_header' => array(
						'id'   => 'kb_settings_header',
						'name' => '<h3>' . sprintf( esc_html__( '%s Settings', 'kb-support' ), kbs_get_article_label_singular() ) . '</h3>',
						'type' => 'header'
					),
					'disable_kb_articles' => array(
						'id'      => 'disable_kb_articles',
						'name'    => esc_html__( 'Disable KB Articles and Categories', 'kb-support' ),
						'desc'    => esc_html__( 'Check to disable all KB Article functionality of the plugin.', 'kb-support' ),
						'type'    => 'checkbox',
						'std'     => 0
					),
					'article_restricted' => array(
						'id'      => 'article_restricted',
						'name'    => sprintf( esc_html__( 'Restrict %s', 'kb-support' ), kbs_get_article_label_plural() ),
						'desc'    => sprintf( esc_html__( 'Select to make %s restricted by default. Can by changed per %s', 'kb-support' ), kbs_get_article_label_plural(), kbs_get_article_label_singular() ),
						'type'    => 'checkbox',
						'std'     => '0'
					),
					'restricted_login'    => array(
						'id'      => 'restricted_login',
						'name'    => esc_html__( 'Show Register / Login Form?', 'kb-support' ),
						'desc'    => sprintf( esc_html__( 'Display the registration and/or login forms when a non-logged-in user lands on a restricted %s.', 'kb-support' ), kbs_get_article_label_singular() ),
						'type'    => 'select',
                        'chosen'  => true,
						'std'     => 'login',
						'options' => array(
							'both'         => esc_html__( 'Registration and Login Forms', 'kb-support' ),
							'registration' => esc_html__( 'Registration Form Only', 'kb-support' ),
							'login'        => esc_html__( 'Login Form Only', 'kb-support' ),
							'none'         => esc_html__( 'None', 'kb-support' ),
						)
					),
					'article_hide_restricted' => array(
						'id'      => 'article_hide_restricted',
						'name'    => sprintf( esc_html__( 'Hide Restricted %s', 'kb-support' ), kbs_get_article_label_plural() ),
						'desc'    => sprintf( esc_html__( 'Restricted %s are always hidden from search results when a user is not logged in. Select to also hide from archives.', 'kb-support' ), kbs_get_article_label_plural() ),
						'type'    => 'checkbox'
					),
					'article_hide_restricted_ajax' => array(
						'id'      => 'article_hide_restricted_ajax',
						'name'    => esc_html__( 'Restricted Ajax Search', 'kb-support' ),
						'desc'    => sprintf( wp_kses_post( __( 'Same as <code>Hide Restricted %s</code> but this option manipulates Ajax search results.', 'kb-support' ) ), kbs_get_article_label_plural() ),
						'type'    => 'checkbox'
					),
					'article_num_posts_ajax' => array(
						'id'      => 'article_num_posts_ajax',
						'name'    => esc_html__( 'Number of Results from Ajax', 'kb-support' ),
						'desc'    => sprintf( esc_html__( 'Enter the number of suggested %s that should be returned from the submission form Ajax search.', 'kb-support' ), kbs_get_article_label_plural() ),
						'type'    => 'number',
						'step'    => '1',
						'size'    => 'small',
						'std'     => '5'
					),
					'article_excerpt_length' => array(
						'id'      => 'article_excerpt_length',
						'name'    => esc_html__( 'Search Excerpt Length', 'kb-support' ),
						'desc'    => wp_kses_post( __( 'Enter the number of words that should form the excerpt length during an ajax search. i.e. on the submission form. Enter <code>0</code> for no excerpt.', 'kb-support' ) ),
						'type'    => 'number',
						'step'    => '5',
						'size'    => 'small',
						'std'     => '0'
					),
					'count_agent_article_views' => array(
						'id'      => 'count_agent_article_views',
						'name'    => esc_html__( 'Count Agent Views?', 'kb-support' ),
						'desc'    => sprintf( esc_html__( 'Enable to increment %1$s counts when an agent is viewing the %1$s.', 'kb-support' ), kbs_get_article_label_singular() ),
						'type'    => 'checkbox',
						'std'     => 0
					),
					'article_views_dashboard' => array(
						'id'      => 'article_views_dashboard',
						'name'    => esc_html__( 'Show Views on Dashboard', 'kb-support' ),
						'desc'    => sprintf( esc_html__( 'Enable to display %s view counts within the KB Support dashboard widget.', 'kb-support' ), kbs_get_article_label_singular() ),
						'type'    => 'checkbox',
						'std'     => 1
					)
				),
				'restricted_notices' => array(
					'kb_settings_restricted_header' => array(
						'id'   => 'kb_settings_restricted_header',
						'name' => '<h3>' . sprintf( esc_html__( '%s Notices', 'kb-support' ), kbs_get_article_label_singular() ) . '</h3>',
						'type' => 'header'
					),
					'restricted_notice'   => array(
						'id'   => 'restricted_notice',
						'name' => sprintf( esc_html__( 'Single %s', 'kb-support' ), kbs_get_article_label_singular() ),
						'desc' => sprintf( esc_html__( 'The text that will be displayed after the excerpt when a user attempts to access a restricted %s', 'kb-support' ), kbs_get_article_label_singular() ),
						'type' => 'rich_editor',
						'std'  => '<h3>' . esc_html__( 'Restricted Content', 'kb-support' ) . '</h3>' .
							sprintf( esc_html__( 'The %s you are viewing is restricted. Please login below to access the full content.', 'kb-support' ),
							kbs_get_article_label_singular()
						)
					)
				)
			)
		),
		/** Emails Settings */
		'emails' => apply_filters( 'kbs_settings_emails',
			array(
				'main' => array(
					'email_settings_header' => array(
						'id'   => 'email_settings_header',
						'name' => '<h3>' . esc_html__( 'Email Settings', 'kb-support' ) . '</h3>',
						'type' => 'header'
					),
					'from_name' => array(
						'id'   => 'from_name',
						'name' => esc_html__( 'From Name', 'kb-support' ),
						'desc' => esc_html__( 'The name customer emails are said to come from. This should probably be your site name.', 'kb-support' ),
						'type' => 'text',
						'std'  => get_bloginfo( 'name' )
					),
					'from_email' => array(
						'id'   => 'from_email',
						'name' => esc_html__( 'From Email', 'kb-support' ),
						'desc' => esc_html__( 'Email address for sending customer emails. This will act as the "from" and "reply-to" address.', 'kb-support' ),
						'type' => 'text',
						'std'  => get_bloginfo( 'admin_email' )
					),
					'email_template' => array(
						'id'      => 'email_template',
						'name'    => esc_html__( 'Email Template', 'kb-support' ),
						'desc'    => sprintf( esc_html__( 'Choose a template. Click "Save Changes" then "Preview %s Received" to see the new template.', 'kb-support' ), $single ),
						'type'    => 'select',
                        'chosen'  => true,
						'options' => kbs_get_email_templates()
					),
					'email_logo' => array(
						'id'   => 'email_logo',
						'name' => esc_html__( 'Logo', 'kb-support' ),
						'desc' => sprintf( esc_html__( 'Upload or choose a logo to be displayed at the top of the %s received emails. Displayed on HTML emails only.', 'kb-support' ), strtolower( $single ) ),
						'type' => 'upload'
					),
                    'email_settings' => array(
						'id'   => 'email_settings',
						'name' => '',
						'desc' => '',
						'type' => 'hook'
					),
                    'attach_files' => array(
						'id'      => 'attach_files',
						'name'    => esc_html__( 'Attach Files?', 'kb-support' ),
						'desc'    => sprintf( wp_kses_post( __( 'Enable this option if you want %s files attached to emails when using the <code>{ticket_files}</code> or <code>{reply_files}</code> email tags. If not enabled, links to the files will be listed within the email content.', 'kb-support' ) ), strtolower( $single ) ),
						'type'    => 'checkbox',
                        'std'     => '1'
					),
                    'copy_company_contact' => array(
                        'id'      => 'copy_company_contact',
						'name'    => esc_html__( 'Copy Company Contact?', 'kb-support' ),
						'desc'    => sprintf( esc_html__( 'If enabled, the primary company contact will be copied into all customer emails for %s associated with the company.', 'kb-support' ), strtolower( $plural ) ),
						'type'    => 'checkbox'
                    ),
                    'copy_participants' => array(
                        'id'      => 'copy_participants',
						'name'    => esc_html__( 'Copy Participants?', 'kb-support' ),
						'desc'    => sprintf( esc_html__( 'If enabled, all participants will receive email notification for all %s activity.', 'kb-support' ), strtolower( $single ) ),
						'type'    => 'checkbox'
                    )
				),
				'ticket_logged' => array(
					'ticket_logged_settings' => array(
						'id'   => 'ticket_logged_settings',
						'name' => '<h3>' . sprintf( esc_html__( '%s Received', 'kb-support' ), $single ) . '</h3>',
						'type' => 'header'
					),
					'ticket_received_disable_email' => array(
						'id'   => 'ticket_received_disable_email',
						'name' => esc_html__( 'Disable this Email', 'kb-support' ),
						'desc' => sprintf( esc_html__( 'Select to stop emails being sent when a %s is logged.', 'kb-support' ), strtolower( $single ) ),
						'type' => 'checkbox'
					),
                    'no_notify_received_emails' => array(
                        'id'      => 'no_notify_received_emails',
						'name'    => esc_html__( 'No Notification Emails', 'kb-support' ),
						'desc'    => sprintf( wp_kses_post( __( 'Email addresses entered here will not receive the %s Received email notifications. Enter one address per line. To exclude an entire domain enter the domain starting with <code>@</code>.', 'kb-support' ) ), $single ),
						'type'    => 'textarea'
                    ),
					'ticket_subject' => array(
						'id'   => 'ticket_subject',
						'name' => esc_html__( 'Email Subject', 'kb-support' ),
						'desc' => sprintf( esc_html__( 'Enter the subject line for the %s logged email. Template tags accepted.', 'kb-support' ), strtolower( $single ) ),
						'type' => 'text',
						'std'  => sprintf( esc_html__( '%s Recieved ##{ticket_id}##', 'kb-support' ), $single )
					),
					'ticket_heading' => array(
						'id'   => 'ticket_heading',
						'name' => esc_html__( 'Email Heading', 'kb-support' ),
						'desc' => sprintf( esc_html__( 'Enter the heading for the %s logged email', 'kb-support' ), strtolower( $single ) ),
						'type' => 'text',
						'std'  => sprintf( esc_html__( 'Support %s Details', 'kb-support' ), $single )
					),
					'ticket_content' => array(
						'id'   => 'ticket_content',
						'name' => esc_html__( 'Content', 'kb-support' ),
						'desc' => sprintf( esc_html__( 'Enter the text that is sent as a %1$s received email to users after submission of a %1$s. HTML is accepted. Available template tags:', 'kb-support' ), strtolower( $single ) ) . '<br />' . kbs_get_emails_tags_list(),
						'type' => 'rich_editor',
						'std'  => kbs_get_ticket_logged_email_body_content()
					),
				),
				'ticket_reply' => array(
					'ticket_reply_settings' => array(
						'id'   => 'ticket_reply_settings',
						'name' => '<h3>' . esc_html__( 'Reply Added', 'kb-support' ) . '</h3>',
						'type' => 'header'
					),
					'ticket_reply_disable_email' => array(
						'id'   => 'ticket_reply_disable_email',
						'name' => esc_html__( 'Disable this Email', 'kb-support' ),
						'desc' => sprintf( esc_html__( 'Select to stop emails being sent when a %s reply is added.', 'kb-support' ), strtolower( $single ) ),
						'type' => 'checkbox'
					),
					'ticket_reply_subject' => array(
						'id'   => 'ticket_reply_subject',
						'name' => esc_html__( 'Email Subject', 'kb-support' ),
						'desc' => sprintf( esc_html__( 'Enter the subject line for the %s reply email. Template tags accepted.', 'kb-support' ), strtolower( $single ) ),
						'type' => 'text',
						'std'  => sprintf( esc_html__( 'Your Support %s Received a Reply', 'kb-support' ), $single ) . ' ##{ticket_id}##'
					),
					'ticket_reply_heading' => array(
						'id'   => 'ticket_reply_heading',
						'name' => esc_html__( 'Email Heading', 'kb-support' ),
						'desc' => sprintf( esc_html__( 'Enter the heading for the %s reply email', 'kb-support' ), strtolower( $single ) ),
						'type' => 'text',
						'std'  => sprintf( esc_html__( 'Support %s Update for', 'kb-support' ), $single ) . ' #{ticket_id}'
					),
					'ticket_reply_content' => array(
						'id'   => 'ticket_reply_content',
						'name' => esc_html__( 'Content', 'kb-support' ),
						'desc' => sprintf( esc_html__( 'Enter the content that is sent to customers when their %1$s receives a reply. HTML is accepted. Available template tags:', 'kb-support' ), strtolower( $single ) ) . '<br/>' . kbs_get_emails_tags_list(),
						'type' => 'rich_editor',
						'std'  => esc_html__( "Dear", "kb-support" ) . " {name},\n\n" .
								  sprintf( esc_html__( 'Your support %1$s # {ticket_id} has received a reply. Click the link below to access your %1$s and review the details.', 'kb-support' ), strtolower( $single ) ) . "\n\n" .
								  '<a href="{ticket_url_path}">' . sprintf( esc_html__( 'View %s', 'kb-support' ), kbs_get_ticket_label_singular() ) . '</a>' . "\n\n" .
								  esc_html__( 'Regards', 'kb-support' ) . "\n\n" .
								  '{sitename}'
					)
				),
				'ticket_closed' => array(
					'ticket_closed_settings' => array(
						'id'   => 'ticket_closed_settings',
						'name' => '<h3>' .sprintf(  esc_html__( 'Ticket %s', 'kb-support' ), $single ) . '</h3>',
						'type' => 'header'
					),
					'ticket_closed_disable_email' => array(
						'id'   => 'ticket_closed_disable_email',
						'name' => esc_html__( 'Disable this Email', 'kb-support' ),
						'desc' => sprintf( esc_html__( 'Select to stop emails being sent when a %s is closed.', 'kb-support' ), strtolower( $single ) ),
						'type' => 'checkbox'
					),
					'ticket_closed_subject' => array(
						'id'   => 'ticket_closed_subject',
						'name' => esc_html__( 'Email Subject', 'kb-support' ),
						'desc' => sprintf( esc_html__( 'Enter the subject line for the %s closed email. Template tags accepted.', 'kb-support' ), strtolower( $single ) ),
						'type' => 'text',
						'std'  => sprintf( esc_html__( 'Your Support %s is Closed', 'kb-support' ), $single ) . ' ##{ticket_id}##'
					),
					'ticket_closed_heading' => array(
						'id'   => 'ticket_closed_heading',
						'name' => esc_html__( 'Email Heading', 'kb-support' ),
						'desc' => sprintf( esc_html__( 'Enter the heading for the %s closed email', 'kb-support' ), strtolower( $single ) ),
						'type' => 'text',
						'std'  => sprintf( esc_html__( 'Support %s #{ticket_id} Closed', 'kb-support' ), $single )
					),
					'ticket_closed_content' => array(
						'id'   => 'ticket_closed_content',
						'name' => esc_html__( 'Content', 'kb-support' ),
						'desc' => sprintf( esc_html__( 'Enter the content that is sent to customers when their %1$s is closed. HTML is accepted. Available template tags:', 'kb-support' ), strtolower( $single ) ) . '<br/>' . kbs_get_emails_tags_list(),
						'type' => 'rich_editor',
						'std'  => esc_html__( "Dear", "kb-support" ) . " {name},\n\n" .
								  sprintf( esc_html__( 'Your support %1$s # {ticket_id} is now closed. You can review the details of your %1$s by clicking the URL below.', 'kb-support' ), strtolower( $single ) ) . "\n\n" .
								  '<a href="{ticket_url_path}">' . sprintf( esc_html__( 'View %s', 'kb-support' ), kbs_get_ticket_label_singular() ) . '</a>' . "\n\n" .
								  esc_html__( 'Regards', 'kb-support' ) . "\n\n" .
								  '{sitename}'
					)
				),
				'ticket_notifications' => array(
					'ticket_notification_settings' => array(
						'id'   => 'ticket_notification_settings',
						'name' => '<h3>' . sprintf( esc_html__( '%s Notifications', 'kb-support' ), $single ) . '</h3>',
						'type' => 'header'
					),
					'disable_admin_notices' => array(
						'id'   => 'disable_admin_notices',
						'name' => esc_html__( 'Disable Notifications', 'kb-support' ),
						'desc' => sprintf( esc_html__( 'Check this box to disable %s notification emails.', 'kb-support' ), strtolower( $single ) ),
						'type' => 'checkbox'
					),
					'ticket_notification_subject' => array(
						'id'   => 'ticket_notification_subject',
						'name' => sprintf( esc_html__( '%s Notification Subject', 'kb-support' ), $single ),
						'desc' => sprintf( esc_html__( 'Enter the subject line for the %s notification email. Template tags accepted.', 'kb-support' ), strtolower( $single ) ),
						'type' => 'text',
						'std'  => sprintf( esc_html__( 'New %s Received - ##{ticket_id}##', 'kb-support' ), $single )
					),
					'ticket_notification' => array(
						'id'   => 'ticket_notification',
						'name' => sprintf( esc_html__( '%s Notification', 'kb-support' ), $single ),
						'desc' => sprintf( esc_html__( 'Enter the text that is sent as %s received notification email after submission of a case. HTML is accepted. Available template tags:', 'kb-support' ), strtolower( $single ) ) . '<br />' . kbs_get_emails_tags_list(),
						'type' => 'rich_editor',
						'std'  => esc_html__( 'Hey there!', 'kb-support' ) . "\n\n" .
								  sprintf( esc_html__( 'A new %s has been logged at', 'kb-support' ), strtolower( $single ) ) . " {sitename}.\n\n" .
								  "<strong>{ticket_title} - #{ticket_id}</strong>\n\n" .
								  '<a href="{ticket_admin_url_path}">' . sprintf( esc_html__( 'View %s', 'kb-support' ), kbs_get_ticket_label_singular() ) . '</a>' . "\n\n" .
								  esc_html__( 'Regards', 'kb-support' ) . "\n\n" .
								  '{sitename}'
					),
					'reply_notification_subject' => array(
						'id'   => 'reply_notification_subject',
						'name' => esc_html__( 'Reply Notification Subject', 'kb-support' ),
						'desc' => sprintf( esc_html__( 'Enter the subject line of the notification email that is sent when a customer submits a %s reply. Template tags accepted.', 'kb-support' ), strtolower( $single ) ),
						'type' => 'text',
						'std'  => sprintf( esc_html__( 'New %s Reply Received - ##{ticket_id}##', 'kb-support' ), $single )
					),
					'reply_notification' => array(
						'id'   => 'reply_notification',
						'name' => sprintf( esc_html__( '%s Reply Notification', 'kb-support' ), $single ),
						'desc' => sprintf( esc_html__( 'Enter the text that is sent as a notification email when a customer submits a %s reply. HTML is accepted. Available template tags:', 'kb-support' ), strtolower( $single ) ) . '<br />' . kbs_get_emails_tags_list(),
						'type' => 'rich_editor',
						'std'  => esc_html__( 'Hey there!', 'kb-support' ) . "\n\n" .
								  sprintf( esc_html__( 'A new %s reply has been received at', 'kb-support' ), strtolower( $single ) ) . " {sitename}.\n\n" .
								  "<strong>{ticket_title} - #{ticket_id}</strong>\n\n" .
								  '<a href="{ticket_admin_url_path}">' . sprintf( esc_html__( 'View %s', 'kb-support' ), kbs_get_ticket_label_singular() ) . '</a>' . "\n\n" .
								  esc_html__( 'Regards', 'kb-support' ) . "\n\n" .
								  '{sitename}'
					),
					'admin_notice_emails' => array(
						'id'   => 'admin_notice_emails',
						'name' => sprintf( esc_html__( '%s Notification Emails', 'kb-support' ), $single ),
						'desc' => sprintf(
							esc_html__( 'Enter the email address(es) that should receive a notification anytime a %s is logged, one per line. Enter <code>{agent}</code> to insert the assigned agent\'s email address', 'kb-support' ), strtolower( $single ), '{agent}' ),
						'type' => 'textarea',
						'std'  => get_bloginfo( 'admin_email' )
					),
                    'agent_notices' => array(
						'id'   => 'agent_notices',
						'name' => esc_html__( 'Assignment Notices', 'kb-support' ),
						'desc' => sprintf( esc_html__( 'Check this box to enable notifications to agents when a %s is assigned to them.', 'kb-support' ), strtolower( $single ) ),
						'type' => 'checkbox'
					),
					'agent_assigned_subject' => array(
						'id'   => 'agent_assigned_subject',
						'name' => esc_html__( 'Agent Assignment Subject', 'kb-support' ),
						'desc' => esc_html__( 'Enter the subject line for the agent assignment notification email. Template tags accepted.', 'kb-support' ),
						'type' => 'text',
						'std'  => sprintf( esc_html__( 'A %s Has Been Assigned to You - ##{ticket_id}##', 'kb-support' ), $single )
					),
                    'agent_assign_notification' => array(
						'id'   => 'agent_assign_notification',
						'name' => esc_html__( 'Agent Assigned Notification', 'kb-support' ),
						'desc' => sprintf( esc_html__( 'Enter the text that is sent as a notification to an agent when a %s has been assigned to them. HTML is accepted. Available template tags:', 'kb-support' ), strtolower( $single ) ) . '<br />' . kbs_get_emails_tags_list(),
						'type' => 'rich_editor',
						'std'  => esc_html__( 'Hey there!', 'kb-support' ) . "\n\n" .
								  sprintf( esc_html__( 'A %s has been assigned to you at {sitename}.', 'kb-support' ), strtolower( $single ) ) . "\n\n" .
								  "<strong>{ticket_title} - #{ticket_id}</strong>\n\n" .
								  sprintf( esc_html__( 'Please login to view and update the %s.', 'kb-support' ), strtolower( $single ) ) . "\n\n" .
                                  '<a href="{ticket_admin_url_path}">' . sprintf( __( 'View %s', 'kb-support' ), kbs_get_ticket_label_singular() ) . '</a>' . "\n\n" .
								  esc_html__( 'Regards', 'kb-support' ) . "\n\n" .
								  '{sitename}'
					)
				)
			)
		),
		/** Compliance Settings */
		'terms_compliance' => apply_filters( 'kbs_settings_terms_compliance',
			array(
				'privacy'     => array(
					'privacy_settings' => array(
						'id'   => 'privacy_settings',
						'name' => '<h3>' . esc_html__( 'Agreement Settings', 'kb-support' ) . '</h3>',
						'type' => 'header',
					),
					'show_agree_to_privacy_policy' => array(
						'id'   => 'show_agree_to_privacy_policy',
						'name' => esc_html__( 'Agree to Privacy Policy?', 'kb-support' ),
						'desc' => sprintf( esc_html__( 'Check this to show an agree to terms on the submission page that users must agree to before submitting their %s.', 'kb-support' ), strtolower( $single ) ),
						'type' => 'checkbox',
						'std'  => false
					),
					'agree_privacy_label' => array(
						'id'   => 'agree_privacy_label',
						'name' => esc_html__( 'Agree to Privacy Policy Label', 'kb-support' ),
						'desc' => sprintf( wp_kses_post( __( 'Label shown next to the agree to privacy policy checkbox. This text will link to your defined <a href="%s">privacy policy</a>.', 'kb-support' ) ), esc_attr( admin_url( 'privacy.php' ) ) ),
						'type' => 'text',
						'size' => 'regular',
						'std'  => esc_html__( 'I have read and accept the privacy policy.', 'kb-support' )
					),
                    'agree_privacy_descripton' => array(
						'id'   => 'agree_privacy_descripton',
						'name' => esc_html__( 'Agree to Privacy Policy Description', 'kb-support' ),
						'desc' => esc_html__( 'Description shown under the Agree to Privacy Policy field. Leave blank for none', 'kb-support' ),
						'type' => 'text',
						'size' => 'regular'
					),
                    'privacy_export_erase_settings' => array(
						'id'   => 'privacy_export_erase_settings',
						'name' => '<h3>' . esc_html__( 'Export & Erase Settings', 'kb-support' ) . '</h3>',
						'type' => 'header',
                        'desc' => sprintf(
                            esc_html__( 'These are the actions that will be taken on associated %s when a user/customer requests to be removed from your site or anonymized', 'kb-support' ),
                            kbs_get_ticket_label_plural( true )
                        )
					),
                    'ticket_privacy_action' => array(
                        'id'      => 'ticket_privacy_action',
                        'name'    => sprintf( '%s', kbs_get_ticket_label_plural() ),
                        'desc'    => sprintf(
                            esc_html__( 'This is the action that will be taken on associated %s when a user/customer requests to be anonymized or removed from your site.', 'kb-support' ),
                            kbs_get_ticket_label_plural( true )
                        ),
                        'type'    => 'select',
                        'chosen'  => true,
                        'options' => array(
                            'none'      => esc_html__( 'None', 'kb-support' ),
                            'anonymize' => esc_html__( 'Anonymize', 'kb-support' ),
                            'delete'    => esc_html__( 'Delete', 'kb-support' ),
                        ),
                        'std'     => 'none'
                    )
				),
				'terms_conditions'     => array(
					'terms_settings' => array(
						'id'   => 'terms_settings',
						'name' => '<h3>' . esc_html__( 'Agreement Settings', 'kb-support' ) . '</h3>',
						'type' => 'header',
					),
					'show_agree_to_terms' => array(
						'id'   => 'show_agree_to_terms',
						'name' => esc_html__( 'Agree to Terms', 'kb-support' ),
						'desc' => sprintf( esc_html__( 'Check this to show an agree to terms on the submission page that users must agree to before submitting their %s.', 'kb-support' ), strtolower( $single ) ),
						'type' => 'checkbox',
						'std'  => false
					),
					'agree_terms_label' => array(
						'id'   => 'agree_terms_label',
						'name' => esc_html__( 'Agree to Terms Label', 'kb-support' ),
						'desc' => esc_html__( 'Label shown next to the agree to terms checkbox.', 'kb-support' ),
						'type' => 'text',
						'size' => 'regular',
						'std'  => esc_html__( 'I have read and agree to the terms and conditions', 'kb-support' )
					),
                    'agree_terms_description' => array(
						'id'   => 'agree_terms_description',
						'name' => esc_html__( 'Agree to Terms Description', 'kb-support' ),
						'desc' => esc_html__( 'Description shown under the Agree to Terms field. Leave blank for none', 'kb-support' ),
						'type' => 'text',
						'size' => 'regular'
					),
					'agree_terms_heading' => array(
						'id'   => 'agree_terms_heading',
						'name' => esc_html__( 'Terms Heading', 'kb-support' ),
						'desc' => esc_html__( 'Heading for the agree to terms thickbox.', 'kb-support' ),
						'type' => 'text',
						'size' => 'regular',
						'std'  => sprintf(
							esc_html__( 'Terms and Conditions for Support %s', 'kb-support' ), $plural
						)
					),
					'agree_terms_text' => array(
						'id'   => 'agree_terms_text',
						'name' => esc_html__( 'Agreement Text', 'kb-support' ),
						'desc' => esc_html__( 'If Agree to Terms is checked, enter the agreement terms here.', 'kb-support' ),
						'type' => 'rich_editor'
					)
				)
			)
		),
		/** Styles Settings */
		'styles' => apply_filters( 'kbs_settings_styles',
			array(
				'main' => array(
					'style_settings' => array(
						'id'    => 'style_settings',
						'name'  => '<h3>' . esc_html__( 'Style Settings', 'kb-support' ) . '</h3>',
						'type'  => 'header'
					),
					'disable_styles' => array(
						'id'    => 'disable_styles',
						'name'  => esc_html__( 'Disable Styles', 'kb-support' ),
						'desc'  => esc_html__( 'Check this to disable all KB Support default styling of buttons, fields, and all other elements.', 'kb-support' ),
						'type'  => 'checkbox'
					)
				)
			)
		),
		/** Extension Settings */
		'extensions' => apply_filters( 'kbs_settings_extensions',
			array()
		),
		/** License Settings */
		'licenses' => apply_filters( 'kbs_settings_licenses',
			array()
		),
		/** Misc Settings */
		'misc' => apply_filters( 'kbs_settings_misc',
			array(
				'main' => array(
					'misc_settings_header' => array(
						'id'   => 'misc_settings_header',
						'name' => '<h3>' . esc_html__( 'Misc Settings', 'kb-support' ) . '</h3>',
						'type' => 'header'
					),
					'show_credits' => array(
						'id'      => 'show_credits',
						'name'    => esc_html__( 'Display Credit?', 'kb-support' ),
						'desc'    => wp_kses_post( __( 'KB Support is provided for free. If you like our plugin, consider spreading the word by displaying <code>Powered by KB Support</code> below the ticket and reply forms.', 'kb-support' ) ),
						'type'    => 'checkbox'
					),
                    'remove_rating' => array(
						'id'      => 'remove_rating',
						'name'    => esc_html__( 'Remove Rating Request?', 'kb-support' ),
						'desc'    => esc_html__( 'Enable to remove the rating request displayed at the foot of the admin screen.', 'kb-support' ),
						'type'    => 'checkbox'
					),
					'remove_on_uninstall' => array(
						'id'      => 'remove_on_uninstall',
						'name'    => esc_html__( 'Remove Data on Uninstall?', 'kb-support' ),
						'desc'    => esc_html__( 'Check this box if you would like KBS to completely remove all of its data when the plugin is deleted.', 'kb-support' ),
						'type'    => 'checkbox'
					)
				),
				'recaptcha'     => array(
					'recaptcha_settings' => array(
						'id'   => 'recaptcha_settings',
						'name' => '<h3>' . esc_html__( 'Google reCAPTCHA Settings', 'kb-support' ) . '</h3>',
						'type' => 'header'
					),
                    'recaptcha_version' => array(
                        'id'      => 'recaptcha_version',
                        'name'    => esc_html__( 'reCAPTCHA Version', 'kb-support' ),
						'desc'    => esc_html__( 'Select reCAPTCHA version. Be sure to use the correct keys for the version you select.', 'kb-support' ),
						'type'    => 'select',
                        'chosen'  => true,
						'options' => array(
                            'v2' => esc_html__( 'Version 2 Checkbox', 'kb-support' ),
                            'v3' => esc_html__( 'Version 3', 'kb-support' )
                        ),
                        'field_class' => 'recaptcha_version',
						'std'     => 'v2'
                    ),
					'recaptcha_site_key' => array(
						'id'   => 'recaptcha_site_key',
						'name' => esc_html__( 'Site Key', 'kb-support' ),
						'desc' => sprintf( wp_kses_post( __( 'Visit <a href="%s" target="_blank">Google reCAPTCHA</a> to register your site and obtain your site key.', 'kb-support' ) ), 'https://www.google.com/recaptcha/' ),
						'type' => 'text'
					),
					'recaptcha_secret' => array(
						'id'      => 'recaptcha_secret',
						'name'    => esc_html__( 'Secret', 'kb-support' ),
						'desc'    => sprintf( wp_kses_post( __( 'Visit <a href="%s" target="_blank">Google reCAPTCHA</a> to register your site and obtain your secret key.', 'kb-support' ) ), 'https://www.google.com/recaptcha/' ),
						'type'    => 'text'
					),
					'recaptcha_theme' => array(
						'id'          => 'recaptcha_theme',
						'name'        => esc_html__( 'reCAPTCHA Theme', 'kb-support' ),
						'desc'        => esc_html__( 'Select your preferred color scheme.', 'kb-support' ),
						'type'        => 'select',
                        'chosen'      => true,
						'options'     => array( 'dark' => esc_html__( 'Dark', 'kb-support' ), 'light' => esc_html__( 'Light', 'kb-support' ) ),
                        'field_class' => 'kbs_recaptcha_theme',
						'std'         => 'light'
					),
					'recaptcha_type' => array(
						'id'          => 'recaptcha_type',
						'name'        => esc_html__( 'reCAPTCHA Type', 'kb-support' ),
						'desc'        => esc_html__( 'Choose to render an audio reCAPTCHA or an image. Default is image.', 'kb-support' ),
						'type'        => 'select',
                        'chosen'      => true,
						'options'     => array( 'audio' => esc_html__( 'Audio', 'kb-support' ), 'image' => __( 'Image', 'kb-support' ) ),
                        'field_class' => 'kbs_recaptcha_type',
						'std'         => 'image'
					),
					'recaptcha_size' => array(
						'id'          => 'recaptcha_size',
						'name'        => esc_html__( 'reCAPTCHA Size', 'kb-support' ),
						'desc'        => esc_html__( 'Select your preferred size for the reCAPTCHA.', 'kb-support' ),
						'type'        => 'select',
                        'chosen'      => true,
						'options'     => array( 'compact' => esc_html__( 'Compact', 'kb-support' ), 'normal' => esc_html__( 'Normal', 'kb-support' ) ),
                        'field_class' => 'kbs_recaptcha_size',
						'std'         => 'normal'
					),
                    'show_recaptcha' => array(
						'id'      => 'show_recaptcha',
						'name'    => esc_html__( 'reCAPTCHA Registration', 'kb-support' ),
						'desc'    => wp_kses_post( __( 'Choose whether to show a reCAPTCHA on the <code>[kbs_register]</code> form.', 'kb-support' ) ),
						'type'    => 'checkbox'
					)

				)
			)
		)
	);

    if ( ! kbs_participants_enabled() ) {
        if ( isset( $kbs_settings['emails']['main']['copy_participants'] ) )   {
            unset( $kbs_settings['emails']['main']['copy_participants'] );
        }
    }

	return apply_filters( 'kbs_registered_settings', $kbs_settings );
} // kbs_get_registered_settings

/**
 * Adds premium extensions not yet installed to license settings.
 *
 * Enables upsell opportunity
 *
 * @since   1.4.6
 * @param   array   $settings   Array of license settings
 * @return  array   Array of license settings
 */
function kbs_add_premium_extension_license_fields( $settings )   {
	$plugins          = kbs_get_premium_extension_data();
	$plugins          = apply_filters( 'kbs_upsell_extensions_settings', $plugins );
	$license_settings = array();

	foreach( $plugins as $plugin => $data ) {
		$license_settings[] = array(
			'id'   => "{$plugin}_license_upsell",
			'name' => sprintf( esc_html__( '%1$s', 'kb-support' ), $data['name'] ),
			'type' => 'premium_extension',
			'data' => $data
		);
	}

	return array_merge( $settings, $license_settings );
} // kbs_add_premium_extension_license_fields
add_filter( 'kbs_settings_licenses', 'kbs_add_premium_extension_license_fields', 100 );

/**
 * Settings Sanitization.
 *
 * Adds a settings error (for the updated message)
 * At some point this will validate input.
 *
 * @since	1.0
 *
 * @param	arr	$input	The value inputted in the field.
 *
 * @return	str	$input	Sanitizied value.
 */
function kbs_settings_sanitize( $input = array() ) {

	global $kbs_options;

	if ( empty( $_POST['_wp_http_referer'] ) ) {
		return $input;
	}

	parse_str( sanitize_url( wp_unslash( $_POST['_wp_http_referer'] ) ), $referrer );

	$settings = kbs_get_registered_settings();
	$tab      = isset( $referrer['tab'] ) ? $referrer['tab'] : ( kbs_tickets_disabled() ? 'tickets' : 'general');
	$section  = isset( $referrer['section'] ) ? $referrer['section'] : 'main';

	$input = $input ? $input : array();

	$input = apply_filters( 'kbs_settings_' . $tab . '-' . $section . '_sanitize', $input );
	if ( 'main' === $section )  {
		// Check for extensions that aren't using new sections
		$input = apply_filters( 'kbs_settings_' . $tab . '_sanitize', $input );

		// Check for an override on the section for when main is empty
		if ( ! empty( $_POST['kbs_section_override'] ) ) {
			$section = sanitize_text_field( wp_unslash( $_POST['kbs_section_override'] ) );
		}
	}

	// Loop through each setting being saved and pass it through a sanitization filter
	foreach ( $input as $key => $value ) {

		// Get the setting type (checkbox, select, etc)
		$type = isset( $settings[ $tab ][ $key ]['type'] ) ? $settings[ $tab ][ $key ]['type'] : false;

		if ( $type ) {
			// Field type specific filter
			$input[ $key ] = apply_filters( 'kbs_settings_sanitize_' . $type, $value, $key );
		}

		// Specific key filter
		$input[ $key ] = apply_filters( 'kbs_settings_sanitize_' . $key, $value );

		// General filter
		$input[ $key ] = apply_filters( 'kbs_settings_sanitize', $input[ $key ], $key );

	}

	// Loop through the whitelist and unset any that are empty for the tab being saved
	$main_settings    = $section == 'main' ? $settings[ $tab ] : array(); // Check for extensions that aren't using new sections
	$section_settings = ! empty( $settings[ $tab ][ $section ] ) ? $settings[ $tab ][ $section ] : array();

	$found_settings = array_merge( $main_settings, $section_settings );

	if ( ! empty( $found_settings ) ) {
		foreach ( $found_settings as $key => $value ) {

			// Settings used to have numeric keys, now they have keys that match the option ID. This ensures both methods work
			if ( is_numeric( $key ) ) {
				$key = $value['id'];
			}

			if ( empty( $input[ $key ] ) && isset( $kbs_options[ $key ] ) ) {
				unset( $kbs_options[ $key ] );
			}
		}
	}

	// Merge our new settings with the existing
	$output = array_merge( $kbs_options, $input );

	add_settings_error( 'kbs-notices', '', esc_html__( 'Settings updated.', 'kb-support' ), 'updated' );

	return $output;
} // kbs_settings_sanitize

/**
 * Tickets General Settings Sanitization
 *
 * @since	1.1
 * @param	array	$input	The value inputted in the field
 * @return	array	$input	Sanitized value
 */
function kbs_settings_sanitize_tickets_main( $input ) {

	if ( ! current_user_can( 'manage_ticket_settings' ) ) {
		return $input;
	}

	if ( ! empty( $input['enable_sequential'] ) && ! kbs_use_sequential_ticket_numbers() )	{
		// Shows an admin notice about upgrading previous ticket numbers
		add_option( 'kbs_upgrade_sequential', '1' );
	}

	return $input;
} // kbs_settings_sanitize_tickets_main
add_filter( 'kbs_settings_tickets-main_sanitize', 'kbs_settings_sanitize_tickets_main' );

/**
 * Sanitize text fields
 *
 * @since	1.0
 * @param	arr		$input	The field value
 * @return	str		$input	Sanitizied value
 */
function kbs_sanitize_text_field( $input ) {
	return trim( $input );
} // kbs_sanitize_text_field
add_filter( 'kbs_settings_sanitize_text', 'kbs_sanitize_text_field' );

/**
 * Sanitize HTML Class Names
 *
 * @since	1.0
 * @param	str|arr		$class	HTML Class Name(s)
 * @return	str			$class
 */
function kbs_sanitize_html_class( $class = '' ) {

	if ( is_string( $class ) )	{
		$class = sanitize_html_class( $class );
	} else if ( is_array( $class ) )	{
		$class = array_values( array_map( 'sanitize_html_class', $class ) );
		$class = implode( ' ', array_unique( $class ) );
	}

	return $class;

} // kbs_sanitize_html_class

/**
 * Retrieve settings tabs
 *
 * @since	1.0
 * @return	arr		$tabs
 */
function kbs_get_settings_tabs() {

	$settings = kbs_get_registered_settings();

	$tabs                     = array();
	$tabs['general']          = esc_html__( 'General', 'kb-support' );
	$tabs['tickets']          = sprintf( esc_html__( '%s', 'kb-support' ), kbs_get_ticket_label_plural() );
	$tabs['articles']         = sprintf( esc_html__( '%s', 'kb-support' ), kbs_get_article_label_plural() );
	$tabs['emails']           = esc_html__( 'Emails', 'kb-support' );
	$tabs['terms_compliance'] = esc_html__( 'Compliance', 'kb-support' );

	if ( kbs_tickets_disabled() ) {
		unset( $tabs['general'] );
		unset( $tabs['emails'] );
		unset( $tabs['terms_compliance'] );
	}

	$tabs = apply_filters( 'kbs_settings_tabs_before_styles', $tabs );

	$tabs['styles'] = esc_html__( 'Styles', 'kb-support' );

	$tabs = apply_filters( 'kbs_settings_tabs_after_styles', $tabs );

	if ( ! empty( $settings['extensions'] ) ) {
		$tabs['extensions'] = esc_html__( 'Extensions', 'kb-support' );
	}
	if ( ! empty( $settings['licenses'] ) ) {
		$tabs['licenses'] = esc_html__( 'Licenses', 'kb-support' );
	}

	$tabs['misc']   = esc_html__( 'Misc', 'kb-support' );

	return apply_filters( 'kbs_settings_tabs', $tabs );
} // kbs_get_settings_tabs

/**
 * Retrieve settings tabs
 *
 * @since	1.0
 * @return	arr		$section
 */
function kbs_get_settings_tab_sections( $tab = false ) {

	$tabs     = array();
	$sections = kbs_get_registered_settings_sections();

	if( $tab && ! empty( $sections[ $tab ] ) ) {
		$tabs = $sections[ $tab ];
	} else if ( $tab ) {
		$tabs = array();
	}

	return $tabs;
} // kbs_get_settings_tab_sections

/**
 * Get the settings sections for each tab
 * Uses a static to avoid running the filters on every request to this function
 *
 * @since	1.0
 * @return	arr		Array of tabs and sections
 */
function kbs_get_registered_settings_sections() {

	static $sections = false;

	if ( false !== $sections ) {
		return $sections;
	}

	$single   = kbs_get_ticket_label_singular();
	$sections = array(
		'general' => apply_filters( 'kbs_settings_sections_general', array(
			'main'                 => esc_html__( 'General Settings', 'kb-support' ),
			'pages'                => esc_html__( 'Pages Settings', 'kb-support' ),
            'customers'            => esc_html__( 'Customer Settings', 'kb-support' )
		) ),
		'tickets' => apply_filters( 'kbs_settings_sections_tickets', array(
			'main'                 => sprintf( esc_html__( 'General %s Settings', 'kb-support' ), $single ),
			'submit'               => esc_html__( 'Submission Settings', 'kb-support' ),
            'replies'              => esc_html__( 'Reply Settings', 'kb-support' ),
			'agents'               => esc_html__( 'Agent Settings', 'kb-support' ),
			'sla'                  => esc_html__( 'Service Levels', 'kb-support' )
		) ),
		'articles' => apply_filters( 'kbs_settings_sections_articles', array(
			'main'                 => sprintf( esc_html__( 'General %s Settings', 'kb-support' ), kbs_get_article_label_singular() ),
			'restricted_notices'   => esc_html__( 'Restricted Content Notices', 'kb-support' )
		) ),
		'emails' => apply_filters( 'kbs_settings_sections_emails', array(
			'main'                 => esc_html__( 'Email Settings', 'kb-support' ),
			'ticket_logged'        => sprintf( esc_html__( '%s Logged', 'kb-support' ), $single ),
			'ticket_reply'         => esc_html__( 'Reply Added', 'kb-support' ),
			'ticket_closed'        => sprintf( esc_html__( '%s Closed', 'kb-support' ), $single ),
			'ticket_notifications' => esc_html__( 'Notifications', 'kb-support' ),
		) ),
		'terms_compliance' => apply_filters( 'kbs_settings_sections_terms_compliance', array(
			'privacy'              => esc_html__( 'Privacy Policy', 'kb-support' ),
			'terms_conditions'     => esc_html__( 'Terms and Conditions', 'kb-support' )
		) ),
		'styles' => apply_filters( 'kbs_settings_sections_styles', array(
			'main'                 => esc_html__( 'Styles', 'kb-support' ),
			'status_colours'       => sprintf( esc_html__( '%s Status Colours', 'kb-support' ), $single )
		) ),
		'extensions' => apply_filters( 'kbs_settings_sections_extensions', array(
			'main'                 => esc_html__( 'Main', 'kb-support' )
		) ),
		'licenses'   => apply_filters( 'kbs_settings_sections_licenses', array() ),
		'misc'       => apply_filters( 'kbs_settings_sections_misc', array(
			'main'                 => esc_html__( 'Misc Settings', 'kb-support' ),
			'recaptcha'            => esc_html__( 'Google reCAPTCHA', 'kb-support' )
		) )
	);

	if( kbs_tickets_disabled() ){
		unset( $sections['styles']['status_colours'] );
	}

	$sections = apply_filters( 'kbs_settings_sections', $sections );

	return $sections;
} // kbs_get_registered_settings_sections

/**
 * Header Callback
 *
 * Renders the header.
 *
 * @since	1.0
 * @param	arr		$args	Arguments passed by the setting
 * @return	void
 */
function kbs_header_callback( $args ) {
	echo apply_filters( 'kbs_after_setting_output', '', $args );
} // kbs_header_callback

/**
 * Checkbox Callback
 *
 * Renders checkboxes.
 *
 * @since	1.0
 * @param	arr		$args	Arguments passed by the setting
 * @global	$kbs_options	Array of all the KBS Options
 * @return	void
 */
function kbs_checkbox_callback( $args ) {
	$kbs_option = kbs_get_option( $args['id'] );

	if ( isset( $args['faux'] ) && true === $args['faux'] ) {
		$name = '';
	} else {
		$name = 'name="kbs_settings[' . kbs_sanitize_key( $args['id'] ) . ']"';
	}

	$class = kbs_sanitize_html_class( $args['field_class'] );

	$checked = ! empty( $kbs_option ) ? checked( 1, $kbs_option, false ) : '';
	$html = '<input type="checkbox" id="kbs_settings[' . kbs_sanitize_key( $args['id'] ) . ']"' . $name . ' value="1" ' . $checked . ' class="' . $class . '"/>';
	$html .= '<label for="kbs_settings[' . kbs_sanitize_key( $args['id'] ) . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

	echo apply_filters( 'kbs_after_setting_output', $html, $args );
} // kbs_checkbox_callback

/**
 * Multicheck Callback
 *
 * Renders multiple checkboxes.
 *
 * @since	1.0
 * @param	arr		$args	Arguments passed by the setting
 * @global	$kbs_options	Array of all the KBS Options
 * @return	void
 */
function kbs_multicheck_callback( $args ) {
	$kbs_option = kbs_get_option( $args['id'] );

	$class = kbs_sanitize_html_class( $args['field_class'] );

	$html = '';

	if ( ! empty( $args['options'] ) ) {
		foreach( $args['options'] as $key => $option )	{
			if ( isset( $kbs_option[ $key ] ) )	{
				$enabled = $option;
			} else	{
				$enabled = NULL;
			}

			$html .= '<input name="kbs_settings[' . kbs_sanitize_key( $args['id'] ) . '][' . kbs_sanitize_key( $key ) . ']" id="kbs_settings[' . kbs_sanitize_key( $args['id'] ) . '][' . kbs_sanitize_key( $key ) . ']" class="' . $class . '" type="checkbox" value="' . esc_attr( $option ) . '" ' . checked( $option, $enabled, false ) . '/>&nbsp;';

			$html .= '<label for="kbs_settings[' . kbs_sanitize_key( $args['id'] ) . '][' . kbs_sanitize_key( $key ) . ']">' . wp_kses_post( $option ) . '</label><br/>';
		}

		$html .= '<p class="description">' . $args['desc'] . '</p>';
	}

	echo apply_filters( 'kbs_after_setting_output', $html, $args );
} // kbs_multicheck_callback

/**
 * Radio Callback
 *
 * Renders radio boxes.
 *
 * @since	1.0
 * @param	arr		$args	Arguments passed by the setting
 * @global	$kbs_options	Array of all the KBS Options
 * @return	void
 */
function kbs_radio_callback( $args ) {
	$kbs_option = kbs_get_option( $args['id'] );

	$html = '';

	$class = kbs_sanitize_html_class( $args['field_class'] );

	foreach ( $args['options'] as $key => $option )	{
		$checked = false;

		if ( $kbs_option && $key == $kbs_option )	{
			$checked = true;
		} elseif ( isset( $args['std'] ) && $key == $args['std'] && ! $kbs_option )	{
			$checked = true;
		}

		$html .= '<input name="kbs_settings[' . kbs_sanitize_key( $args['id'] ) . ']" id="kbs_settings[' . kbs_sanitize_key( $args['id'] ) . '][' . kbs_sanitize_key( $key ) . ']" class="' . $class . '" type="radio" value="' . kbs_sanitize_key( $key ) . '" ' . checked( true, $checked, false ) . '/>&nbsp;';

		$html .= '<label for="kbs_settings[' . kbs_sanitize_key( $args['id'] ) . '][' . kbs_sanitize_key( $key ) . ']">' . esc_html( $option ) . '</label><br/>';
	}

	$html .= '<p class="description">' . apply_filters( 'kbs_after_setting_output', wp_kses_post( $args['desc'] ), $args ) . '</p>';

	echo $html;
} // kbs_radio_callback

/**
 * Text Callback
 *
 * Renders text fields.
 *
 * @since	1.0
 * @param	arr		$args	Arguments passed by the setting
 * @global	$kbs_options	Array of all the KBS Options
 * @return	void
 */
function kbs_text_callback( $args ) {
	$kbs_option = kbs_get_option( $args['id'] );

	if ( $kbs_option )	{
		$value = $kbs_option;
	} else	{
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	if ( isset( $args['faux'] ) && true === $args['faux'] ) {
		$args['readonly'] = true;
		$value = isset( $args['std'] ) ? $args['std'] : '';
		$name  = '';
	} else {
		$name = 'name="kbs_settings[' . esc_attr( $args['id'] ) . ']"';
	}

	$class = kbs_sanitize_html_class( $args['field_class'] );

	$readonly = $args['readonly'] === true ? ' readonly="readonly"' : '';
	$size     = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html     = '<input type="text" class="' . $class . ' ' . sanitize_html_class( $size ) . '-text" id="kbs_settings[' . kbs_sanitize_key( $args['id'] ) . ']" ' . $name . ' value="' . esc_attr( stripslashes( $value ) ) . '"' . $readonly . '/>';
	$html    .= '<label for="kbs_settings[' . kbs_sanitize_key( $args['id'] ) . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

	echo apply_filters( 'kbs_after_setting_output', $html, $args );
} // kbs_text_callback

/**
 * Number Callback
 *
 * Renders number fields.
 *
 * @since	1.0
 * @param	arr		$args	Arguments passed by the setting
 * @global	$kbs_options	Array of all the KBS Options
 * @return	void
 */
function kbs_number_callback( $args ) {
	$kbs_option = kbs_get_option( $args['id'] );

	if ( $kbs_option ) {
		$value = $kbs_option;
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	if ( isset( $args['faux'] ) && true === $args['faux'] ) {
		$args['readonly'] = true;
		$value = isset( $args['std'] ) ? $args['std'] : '';
		$name  = '';
	} else {
		$name = 'name="kbs_settings[' . esc_attr( $args['id'] ) . ']"';
	}

	$class = kbs_sanitize_html_class( $args['field_class'] );

	$max  = isset( $args['max'] ) ? $args['max'] : 999999;
	$min  = isset( $args['min'] ) ? $args['min'] : 0;
	$step = isset( $args['step'] ) ? $args['step'] : 1;

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="number" step="' . esc_attr( $step ) . '" max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" class="' . $class . ' ' . sanitize_html_class( $size ) . '-text" id="kbs_settings[' . kbs_sanitize_key( $args['id'] ) . ']" ' . $name . ' value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html .= '<label for="kbs_settings[' . kbs_sanitize_key( $args['id'] ) . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

	echo apply_filters( 'kbs_after_setting_output', $html, $args );
} // kbs_number_callback

/**
 * Textarea Callback
 *
 * Renders textarea fields.
 *
 * @since	1.0
 * @param	arr		$args	Arguments passed by the setting
 * @global	$kbs_options	Array of all the KBS Options
 * @return	void
 */
function kbs_textarea_callback( $args ) {
	$kbs_option = kbs_get_option( $args['id'] );

	if ( $kbs_option )	{
		$value = $kbs_option;
	} else	{
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$class = kbs_sanitize_html_class( $args['field_class'] );

	$html = '<textarea class="' . $class . ' large-text" cols="50" rows="5" id="kbs_settings[' . kbs_sanitize_key( $args['id'] ) . ']" name="kbs_settings[' . esc_attr( $args['id'] ) . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
	$html .= '<label for="kbs_settings[' . kbs_sanitize_key( $args['id'] ) . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

	echo apply_filters( 'kbs_after_setting_output', $html, $args );
} // kbs_textarea_callback

/**
 * Password Callback
 *
 * Renders password fields.
 *
 * @since	1.0
 * @param	arr		$args	Arguments passed by the setting
 * @global	$kbs_options	Array of all the KBS Options
 * @return	void
 */
function kbs_password_callback( $args ) {
	$kbs_option = kbs_get_option( $args['id'] );

	if ( $kbs_option )	{
		$value = $kbs_option;
	} else	{
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$class = kbs_sanitize_html_class( $args['field_class'] );

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="password" class="' . $class . ' ' . sanitize_html_class( $size ) . '-text" id="kbs_settings[' . kbs_sanitize_key( $args['id'] ) . ']" name="kbs_settings[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $value ) . '"/>';
	$html .= '<label for="kbs_settings[' . kbs_sanitize_key( $args['id'] ) . ']"> ' . wp_kses_post( $args['desc'] ) . '</label>';

	echo apply_filters( 'kbs_after_setting_output', $html, $args );
} // kbs_password_callback

/**
 * Missing Callback
 *
 * If a function is missing for settings callbacks alert the user.
 *
 * @since	1.0
 * @param	arr		$args	Arguments passed by the setting
 * @return	void
 */
function kbs_missing_callback($args) {
	echo wp_kses_post( sprintf(
		 __( 'The callback function used for the %s setting is missing.', 'kb-support' ),
		'<strong>' . esc_html( $args['id'] ) . '</strong>'
	) );
} // kbs_missing_callback

/**
 * Select Callback
 *
 * Renders select fields.
 *
 * @since	1.0
 * @param	arr		$args	Arguments passed by the setting
 * @global	$kbs_options	Array of all the KBS Options
 * @return	void
 */
function kbs_select_callback( $args ) {
	$kbs_option = kbs_get_option( $args['id'] );

	if ( $kbs_option )	{
		$value = $kbs_option;
	} else	{
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	if ( isset( $args['placeholder'] ) ) {
		$placeholder = $args['placeholder'];
	} else {
		$placeholder = '';
	}

	if ( ! empty( $args['multiple'] ) ) {
		$multiple   = ' MULTIPLE';
		$name_array = '[]';
	} else {
		$multiple   = '';
		$name_array = '';
	}

	$class = kbs_sanitize_html_class( $args['field_class'] );

	if ( isset( $args['chosen'] ) ) {
		$class .= ' kbs_select_chosen';
	}

	$html = '<select id="kbs_settings[' . kbs_sanitize_key( $args['id'] ) . ']" name="kbs_settings[' . esc_attr( $args['id'] ) . ']' . $name_array . '" class="' . $class . '"' . $multiple . ' data-placeholder="' . esc_html( $placeholder ) . '" />';

	foreach ( $args['options'] as $option => $name ) {
		if ( ! empty( $multiple ) && is_array( $value ) ) {
			$selected = selected( true, in_array( $option, $value ), false );
		} else	{
			$selected = selected( $option, $value, false );
		}
		$html .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( $name ) . '</option>';
	}

	$html .= '</select>';
	$html .= '<label for="kbs_settings[' . kbs_sanitize_key( $args['id'] ) . ']"> ' . wp_kses_post( $args['desc'] ) . '</label>';

	echo apply_filters( 'kbs_after_setting_output', $html, $args );
} // kbs_select_callback

/**
 * Color select Callback
 *
 * Renders color select fields.
 *
 * @since	1.0
 * @param	arr		$args	Arguments passed by the setting
 * @global	$kbs_options Array of all the KBS Options
 * @return	void
 */
function kbs_color_select_callback( $args ) {
	$kbs_option = kbs_get_option( $args['id'] );

	if ( $kbs_option )	{
		$value = $kbs_option;
	} else	{
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$class = kbs_sanitize_html_class( $args['field_class'] );

	$html = '<select id="kbs_settings[' . kbs_sanitize_key( $args['id'] ) . ']" class="' . $class . '" name="kbs_settings[' . esc_attr( $args['id'] ) . ']"/>';

	foreach ( $args['options'] as $option => $color ) {
		$selected = selected( $option, $value, false );
		$html .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( $color['label'] ) . '</option>';
	}

	$html .= '</select>';
	$html .= '<label for="kbs_settings[' . kbs_sanitize_key( $args['id'] ) . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

	echo apply_filters( 'kbs_after_setting_output', $html, $args );
} // kbs_color_select_callback

/**
 * Color picker Callback
 *
 * Renders color picker fields.
 *
 * @since	1.4
 * @param	array	$args	Arguments passed by the setting
 * @return	void
 */
function kbs_color_callback( $args ) {
	$kbs_option = kbs_get_option( $args['id'] );

	if ( $kbs_option ) {
		$value = $kbs_option;
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$default = isset( $args['std'] ) ? $args['std'] : '';

	$class = kbs_sanitize_html_class( $args['field_class'] );

	$html = '<input type="text" class="' . $class . ' kbs-color-picker" id="kbs_settings[' . kbs_sanitize_key( $args['id'] ) . ']" name="kbs_settings[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $value ) . '" data-default-color="' . esc_attr( $default ) . '" />';
	$html .= '<label for="kbs_settings[' . kbs_sanitize_key( $args['id'] ) . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

	echo apply_filters( 'kbs_after_setting_output', $html, $args );
} // kbs_color_callback

/**
 * Support Hours Callback
 *
 * Renders support hours callback.
 *
 * @since	1.0
 * @param	arr		$args	Arguments passed by the setting
 * @global	$kbs_options	Array of all the KBS Options
 * @return	void
 */
function kbs_support_hours_callback( $args ) {
	global $wp_locale;

	$days_of_week = kbs_get_days_of_week();

	$kbs_option = kbs_get_option( $args['id'] );

	if ( $kbs_option )	{
		$value = $kbs_option;
	} else	{
		$value = array();
	}

	$class = kbs_sanitize_html_class( $args['field_class'] );

	for( $hour = '00'; $hour <= '23'; $hour++ )	{
		$hours[] = $hour;
	}

	$mins = array( '00', '15', '30', '45' );
	$ampm = array( 'AM', 'PM' );

	$html = '<table style="width: 75%;">';
		$html .= '<tr>';
			$html .= '<th scope="row">' . esc_html__( 'Day of Week', 'kb-support' ) . '</th>';
			$html .= '<th scope="row">' . esc_html__( 'Closed all Day', 'kb-support' ) . '</th>';
			$html .= '<th scope="row">' . esc_html__( 'Open', 'kb-support' ) . '</th>';
			$html .= '<th scope="row">' . esc_html__( 'Close', 'kb-support' ) . '</th>';
		$html .= '</tr>';

		foreach( $days_of_week as $index => $day )	{
			$checked = ! empty( $kbs_option[ $index ]['closed'] ) ? checked( 1, $kbs_option[ $index ]['closed'], false ) : '';
			$open    = ! empty( $kbs_option[ $index ]['open'] )  ? $kbs_option[ $index ]['open']  : '';
			$close   = ! empty( $kbs_option[ $index ]['close'] ) ? $kbs_option[ $index ]['close'] : '';
			$html .= '<tr>';
				$html .= '<th scope="row">' . $day . '</th>';
				$html .= '<td>';
					$html .= '<input type="checkbox" name="kbs_settings[' . esc_attr( $args['id'] ) . '][' . $index . '][closed]" value="1"' . $checked . ' />';
				$html .= '</td>';

				$html .= '<td>';
					$html .= '<select name="kbs_settings[' . esc_attr( $args['id'] ) . '][' . $index . '][open][hour]" id="kbs_settings[' . esc_attr( $args['id'] ) . '][' . $index . '][open][hour]" class="kbs_select_chosen" />';
						if( isset( $kbs_option[ $index ]['open']['hour'] ) ){
							$selected = selected( $kbs_option[ $index ]['open']['hour'], '-1', false );
						}else{
							$selected = '';
						}
						$html .= '<option value="-1"' . $selected . '>&mdash;</option>';
						foreach( $hours as $hour )	{
							$current  = ! empty( $kbs_option[ $index ]['open']['hour'] ) ? $kbs_option[ $index ]['open']['hour'] : '';
							$selected = selected( $hour, $current, false );
							$html .= '<option value="' . $hour . '"' . $selected . '>' . $hour . '</option>';
						}
					$html .= '</select>';

					$html .= '<select name="kbs_settings[' . esc_attr( $args['id'] ) . '][' . $index . '][open][min]" id="kbs_settings[' . esc_attr( $args['id'] ) . '][' . $index . '][open][min]" class="kbs_select_chosen" />';
						if( isset( $kbs_option[ $index ]['open']['min'] ) ){
							$selected = selected( $kbs_option[ $index ]['open']['min'], '-1', false );
						}else{
							$selected ='';
						}

						$html .= '<option value="-1"' . $selected . '>&mdash;</option>';
						foreach( $mins as $min )	{
							$current  = ! empty( $kbs_option[ $index ]['open']['min'] ) ? $kbs_option[ $index ]['open']['min'] : '';
							$selected = selected( $min, $current, false );
							$html .= '<option value="' . $min . '"' . $selected . '>' . $min . '</option>';
						}
					$html .= '</select>';
				$html .= '</td>';

				$html .= '<td>';
					$html .= '<select name="kbs_settings[' . esc_attr( $args['id'] ) . '][' . $index . '][close][hour]" id="kbs_settings[' . esc_attr( $args['id'] ) . '][' . $index . '][close][hour]" class="kbs_select_chosen" />';
						if( isset( $kbs_option[ $index ]['close']['hour'] ) ){
							$selected = selected( $kbs_option[ $index ]['close']['hour'], '-1', false );
						}else{
							$selected ='';
						}
						$html .= '<option value="-1"' . $selected . '>&mdash;</option>';
						foreach( $hours as $hour )	{
							$current  = ! empty( $kbs_option[ $index ]['close']['hour'] ) ? $kbs_option[ $index ]['close']['hour'] : '';
							$selected = selected( $hour, $current, false );
							$html .= '<option value="' . $hour . '"' . $selected . '>' . $hour . '</option>';
						}
					$html .= '</select>';

					$html .= '<select name="kbs_settings[' . esc_attr( $args['id'] ) . '][' . $index . '][close][min]" id="kbs_settings[' . esc_attr( $args['id'] ) . '][' . $index . '][close][min]" class="kbs_select_chosen" />';

						if( isset( $kbs_option[ $index ]['close']['min'] ) ){
							$selected = selected( $kbs_option[ $index ]['close']['min'], '-1', false );
						}else{
							$selected ='';
						}
						$html .= '<option value="-1"' . $selected . '>&mdash;</option>';
						foreach( $mins as $min )	{
							$current  = ! empty( $kbs_option[ $index ]['close']['min'] ) ? $kbs_option[ $index ]['close']['min'] : '';
							$selected = selected( $min, $current, false );
							$html .= '<option value="' . $min . '"' . $selected . '>' . $min . '</option>';
						}
					$html .= '</select>';
				$html .= '</td>';
			$html .= '</tr>';
		}
	$html .= '</table>';

	echo apply_filters( 'kbs_after_setting_output', $html, $args );
} // kbs_support_hours_callback

/**
 * Rich Editor Callback
 *
 * Renders rich editor fields.
 *
 * @since	1.0
 * @param	arr		$args	Arguments passed by the setting
 * @global	$kbs_options	Array of all the KBS Options
 * @global	$wp_version		WordPress Version
 */
function kbs_rich_editor_callback( $args ) {
	$kbs_option = kbs_get_option( $args['id'] );

	if ( $kbs_option )	{
		$value = $kbs_option;

		if ( empty( $args['allow_blank'] ) && empty( $value ) )	{
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}
	} else	{
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$rows = isset( $args['size'] ) ? $args['size'] : 20;

	$class = kbs_sanitize_html_class( $args['field_class'] );

	ob_start();
	wp_editor(
		stripslashes( $value ),
		'kbs_settings_' . esc_attr( $args['id'] ),
		array(
			'textarea_name' => 'kbs_settings[' . esc_attr( $args['id'] ) . ']',
			'textarea_rows' => absint( $rows ),
			'editor_class'  => $class
		)
	);
	$html = ob_get_clean();

	$html .= '<br/><label for="kbs_settings[' . kbs_sanitize_key( $args['id'] ) . ']"> ' . wp_kses_post( $args['desc'] ) . '</label>';

	echo apply_filters( 'kbs_after_setting_output', $html, $args );
} // kbs_rich_editor_callback

/**
 * Upload Callback
 *
 * Renders upload fields.
 *
 * @since	1.0
 * @param	arr		$args	Arguments passed by the setting
 * @global	$kbs_options	Array of all the KBS Options
 * @return	void
 */
function kbs_upload_callback( $args ) {

	$kbs_option = kbs_get_option( $args['id'] );

	if ( $kbs_option )	{
		$value = $kbs_option;
	} else	{
		$value = isset($args['std']) ? $args['std'] : '';
	}

	$class = kbs_sanitize_html_class( $args['field_class'] );

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="text" "' . $class . ' ' . sanitize_html_class( $size ) . '-text" id="kbs_settings[' . kbs_sanitize_key( $args['id'] ) . ']" name="kbs_settings[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html .= '<span>&nbsp;<input type="button" class="kbs_settings_upload_button button-secondary" value="' . esc_html__( 'Upload File', 'kb-support' ) . '"/></span>';
	$html .= '<label for="kbs_settings[' . kbs_sanitize_key( $args['id'] ) . ']"> ' . wp_kses_post( $args['desc'] ) . '</label>';

	echo apply_filters( 'kbs_after_setting_output', $html, $args );
} // kbs_upload_callback

/**
 * Descriptive text callback.
 *
 * Renders descriptive text onto the settings field.
 *
 * @since	1.0
 * @param	arr		$args	Arguments passed by the setting
 * @return	void
 */
function kbs_descriptive_text_callback( $args ) {
	$html = wp_kses_post( $args['desc'] );

	echo apply_filters( 'kbs_after_setting_output', $html, $args );
} // kbs_descriptive_text_callback

/**
 * Registers the premium extension field callback.
 *
 * @since	1.4.6
 * @param	array	$args	Arguments passed by the setting
 * @global	$kbs_options	Array of all the KBS options
 * @return void
 */
if ( ! function_exists( 'kbs_premium_extension_callback' ) ) {

	function kbs_premium_extension_callback( $args )	{

        $data = $args['data'];
        $demo = false;

        $html = '';

		if ( isset( $data['demo_url'] ) ) {
            $demo = true;

			$html .= sprintf(
                '<a href="%s" class="button button-secondary kbs-extension-demo" target="_blank">%s</a>',
                esc_url( $data['demo_url'] ),
                esc_html__( 'Demo', 'kb-support' )
            );
		}

        if ( isset( $data['purchase_url'] ) ) {
            if ( $demo )    {
                $html .= '&nbsp;&nbsp;&nbsp;';
            }

			$data['purchase_url'] = add_query_arg( array(
				'utm_source'   => 'settings',
				'utm_medium'   => 'wp-admin',
				'utm_campaign' => 'licensing',
				'utm_content'  => 'license_box'
			), $data['purchase_url'] );

			$html .= sprintf(
                '<a href="%s" class="button button-secondary kbs-extension-purchase" target="_blank">%s</a>',
                esc_url( $data['purchase_url'] ),
               esc_html__( 'Buy Extension', 'kb-support' )
            );
		}

		$html .= '<label for="kbs_settings[' . kbs_sanitize_key( $args['id'] ) . ']"> '  . wp_kses_post( $args['desc'] ) . '</label>';

        $html .= '<div class="kbs-license-data kbs-license-not-installed license-not-installed-notice">';
            $html .= '<p>' . $data['desc'] . '</p>';
        $html .= '</div>';

        echo wp_kses_post( $html );
	}
} // kbs_premium_extension_callback

/**
 * Hook Callback
 *
 * Adds a do_action() hook in place of the field
 *
 * @since	1.0
 * @param	arr		$args	Arguments passed by the setting
 * @return	void
 */
function kbs_hook_callback( $args ) {
	do_action( 'kbs_' . $args['id'], $args );
} // kbs_hook_callback

/**
 * Set manage_ticket_settings as the cap required to save KBS settings pages
 *
 * @since	1.0
 * @return	str		Capability required
 */
function kbs_set_settings_cap() {
	return 'manage_ticket_settings';
} // kbs_set_settings_cap
add_filter( 'option_page_capability_kbs_settings', 'kbs_set_settings_cap' );

/**
 * Adds the tooltip after the setting field.
 *
 * @since	1.0
 * @param	str		$html	HTML output
 * @param	arr		$args	Array containing tooltip title and description
 * @return	str		Filtered HTML output
 */
function kbs_add_setting_tooltip( $html, $args ) {

	if ( ! empty( $args['tooltip_title'] ) && ! empty( $args['tooltip_desc'] ) ) {
		$tooltip = '<span alt="f223" class="kbs-help-tip dashicons dashicons-editor-help" title="<strong>' . $args['tooltip_title'] . '</strong>: ' . $args['tooltip_desc'] . '"></span>';
		$html .= $tooltip;
	}

	return $html;
}
add_filter( 'kbs_after_setting_output', 'kbs_add_setting_tooltip', 10, 2 );

/**
 * Retrieve a list of all published pages.
 *
 * On large sites this can be expensive, so only load if on the settings page or $force is set to true
 *
 * @since	1.0
 * @param	bool	$force			Force the pages to be loaded even if not on settings
 * @return	arr		$pages_options	An array of the pages
 */
function kbs_get_pages( $force = false ) {

	$pages_options = array( '' => '' ); // Blank option

	if( ( ! isset( $_GET['page'] ) || 'kbs-settings' != $_GET['page'] ) && ! $force ) {
		return $pages_options;
	}

	$pages = get_pages();
	if ( $pages ) {
		foreach ( $pages as $page ) {
			$pages_options[ $page->ID ] = $page->post_title;
		}
	}

	return $pages_options;

} // kbs_get_pages

/**
 * Returns a select list for user role options.
 *
 * @since	1.2.6
 * @return	string		Array of selectable options for user roles.
 */
function kbs_get_user_role_options()	{
	$roles     = array();
    $all_roles = array_reverse( get_editable_roles() );

    foreach( $all_roles as $role => $data ) {
        $name  = translate_user_role( $data['name'] );

        $roles[ $role ] = $name;
    }

	return apply_filters( 'kbs_user_role_options', $roles );
} // kbs_get_user_role_options

/**
 * Returns a select list for target response time options.
 *
 * @since	1.0
 * @param
 * @return	str		Array of selectable options for target response times.
 */
function kbs_get_response_time_options()	{
	$response_times = array(
		HOUR_IN_SECONDS      => esc_html__( '1 Hour', 'kb-support' ),
		2 * HOUR_IN_SECONDS  => esc_html__( '2 Hours', 'kb-support' ),
		3 * HOUR_IN_SECONDS  => esc_html__( '3 Hours', 'kb-support' ),
		4 * HOUR_IN_SECONDS  => esc_html__( '4 Hours', 'kb-support' ),
		5 * HOUR_IN_SECONDS  => esc_html__( '5 Hours', 'kb-support' ),
		6 * HOUR_IN_SECONDS  => esc_html__( '6 Hours', 'kb-support' ),
		7 * HOUR_IN_SECONDS  => esc_html__( '7 Hours', 'kb-support' ),
		8 * HOUR_IN_SECONDS  => esc_html__( '8 Hours', 'kb-support' ),
		12 * HOUR_IN_SECONDS => esc_html__( '12 Hours', 'kb-support' ),
		DAY_IN_SECONDS       => esc_html__( '1 Day', 'kb-support' ),
		2 * DAY_IN_SECONDS   => esc_html__( '2 Days', 'kb-support' ),
		3 * DAY_IN_SECONDS   => esc_html__( '3 Days', 'kb-support' ),
		4 * DAY_IN_SECONDS   => esc_html__( '4 Days', 'kb-support' ),
		5 * DAY_IN_SECONDS   => esc_html__( '5 Days', 'kb-support' ),
		6 * DAY_IN_SECONDS   => esc_html__( '6 Days', 'kb-support' ),
		WEEK_IN_SECONDS      => esc_html__( '1 Week', 'kb-support' ),
		2 * WEEK_IN_SECONDS  => esc_html__( '2 Weeks', 'kb-support' ),
		3 * WEEK_IN_SECONDS  => esc_html__( '3 Weeks', 'kb-support' ),
		4 * WEEK_IN_SECONDS  => esc_html__( '4 Weeks', 'kb-support' )
	);

	return apply_filters( 'kbs_get_response_time_options', $response_times );

} // kbs_get_response_time_options

/**
 * Returns a select list for target resolution time options.
 *
 * @since	1.0
 * @param
 * @return	str		Array of selectable options for target resolution times.
 */
function kbs_get_resolve_time_options()	{
	$resolve_times = array(
		HOUR_IN_SECONDS      => esc_html__( '1 Hour', 'kb-support' ),
		2 * HOUR_IN_SECONDS  => esc_html__( '2 Hours', 'kb-support' ),
		3 * HOUR_IN_SECONDS  => esc_html__( '3 Hours', 'kb-support' ),
		4 * HOUR_IN_SECONDS  => esc_html__( '4 Hours', 'kb-support' ),
		5 * HOUR_IN_SECONDS  => esc_html__( '5 Hours', 'kb-support' ),
		6 * HOUR_IN_SECONDS  => esc_html__( '6 Hours', 'kb-support' ),
		7 * HOUR_IN_SECONDS  => esc_html__( '7 Hours', 'kb-support' ),
		8 * HOUR_IN_SECONDS  => esc_html__( '8 Hours', 'kb-support' ),
		12 * HOUR_IN_SECONDS => esc_html__( '12 Hours', 'kb-support' ),
		DAY_IN_SECONDS       => esc_html__( '1 Day', 'kb-support' ),
		2 * DAY_IN_SECONDS   => esc_html__( '2 Days', 'kb-support' ),
		3 * DAY_IN_SECONDS   => esc_html__( '3 Days', 'kb-support' ),
		4 * DAY_IN_SECONDS   => esc_html__( '4 Days', 'kb-support' ),
		5 * DAY_IN_SECONDS   => esc_html__( '5 Days', 'kb-support' ),
		6 * DAY_IN_SECONDS   => esc_html__( '6 Days', 'kb-support' ),
		WEEK_IN_SECONDS      => esc_html__( '1 Week', 'kb-support' ),
		2 * WEEK_IN_SECONDS  => esc_html__( '2 Weeks', 'kb-support' ),
		3 * WEEK_IN_SECONDS  => esc_html__( '3 Weeks', 'kb-support' ),
		4 * WEEK_IN_SECONDS  => esc_html__( '4 Weeks', 'kb-support' )
	);

	return apply_filters( 'kbs_target_resolve_time_options', $resolve_times );
} // kbs_get_resolve_time_options

/**
 * Adds the settings for ticket status replies.
 *
 * @since   1.3.1
 * @param   array   $settings   Array of settings
 * @return  array   Array of settings
 */
function kbs_settings_for_status_replies( $settings )   {
    $all_statuses   = kbs_get_ticket_statuses();
    $select_options = array();

    foreach( $all_statuses as $status => $label )   {
        if ( 'closed' == $status )  {
            continue;
        }

        $select_options[ $status ] = $label;
    }

	$agent_reply_options = array( 0 => __( 'Current Status', 'kb-support' ) );
	$agent_reply_options = array_merge( $agent_reply_options, $select_options );

	$settings['replies']['agent_reply_status'] = array(
		'id'      => 'agent_reply_status',
		'name'    => esc_html__( 'Agent Reply Status', 'kb-support' ),
		'desc'    => sprintf( esc_html__( 'When an agent replies to a %1$s what should the status change to by default? Agents may be able to change this whilst replying.', 'kb-support' ), kbs_get_ticket_label_singular( true ) ),
		'type'    => 'select',
		'options' => $agent_reply_options,
		'chosen'  => true,
		'std'     => 0,
		'class'   => 'status_reply_agent'
	);

    foreach( $all_statuses as $status => $label )   {
        if ( 'open' == $status )    {
            continue;
        }

        $class = 'status_reply_' . $status;

        if ( 'closed' == $status && ! kbs_get_option( 'customer_can_repoen' ) ) {
            $class .= ' kbs-hidden';
        }

        $status_id = 'reply_while_status_' . $status;

        $settings['replies'][ $status_id ] = array(
            'id'      => $status_id,
            'name'    => sprintf( esc_html__( 'Reply whilst %s?', 'kb-support' ), $label ),
            'desc'    => sprintf( esc_html__( 'When a reply to a %1$s with the status %2$s is received from a customer, what status should the %1$s change to?', 'kb-support' ), kbs_get_ticket_label_singular( true ), $label ),
            'type'    => 'select',
            'options' => $select_options,
            'chosen'  => true,
            'std'     => 'closed' != $status ? $status : 'open',
            'class'   => $class
        );
    }

    return $settings;
} // kbs_settings_for_status_replies
add_filter( 'kbs_ticket_settings', 'kbs_settings_for_status_replies' );

/**
 * Adds the settings for ticket status colours.
 *
 * @since   1.4
 * @param   array   $settings   Array of settings
 * @return  array   Array of settings
 */
function kbs_settings_for_status_colours( $settings )   {
	$default_statuses = kbs_get_default_ticket_statuses();
    $all_statuses     = kbs_get_ticket_statuses( false );
	$status_options   = array();

	foreach( $default_statuses as $default_status )	{
		if( isset( $all_statuses[ $default_status ] ) ) {
			$status_options[ $default_status ] = $all_statuses[ $default_status ];
			unset( $all_statuses[ $default_status ] );
		}
	}

	$status_options = array_merge( $status_options, $all_statuses );

	foreach( $status_options as $status => $label )   {
		$id = 'colour_' . $status;

		$settings['status_colours'][ $id ] = array(
            'id'      => $id,
            'name'    => $label,
            'desc'    => sprintf( esc_html__( 'Select the colour to use for %s in the %s status', 'kb-support' ), kbs_get_ticket_label_plural( true ), $label ),
            'type'    => 'color',
            'std'     => kbs_get_ticket_status_colour( $status, true ),
			'default' => str_replace( '#', '', kbs_get_ticket_status_colour( $status, true ) )
        );
	}

	$settings['status_colours']['colour_reply_admin'] = array(
		'id'      => 'colour_reply_admin',
		'name'    => esc_html__( 'Admin Replied', 'kb-support' ),
		'desc'    => sprintf( esc_html__( 'Select the colour to use when an admin has replied to a %s', 'kb-support' ), kbs_get_ticket_label_singular( true ) ),
		'type'    => 'color',
		'std'     => kbs_get_ticket_reply_status_colour( 'agent' ),
		'default' => '6b5b95'
	);

	$settings['status_colours']['colour_reply_agent'] = array(
		'id'      => 'colour_reply_agent',
		'name'    => esc_html__( 'Agent Replied', 'kb-support' ),
		'desc'    => sprintf( esc_html__( 'Select the colour to use when an agent has replied to a %s', 'kb-support' ), kbs_get_ticket_label_singular( true ) ),
		'type'    => 'color',
		'std'     => kbs_get_ticket_reply_status_colour( 'agent' ),
		'default' => '6b5b95'
	);

	$settings['status_colours']['colour_reply_customer'] = array(
		'id'      => 'colour_reply_customer',
		'name'    => esc_html__( 'Customer Replied', 'kb-support' ),
		'desc'    => sprintf( esc_html__( 'Select the colour to use when a customer has replied to a %s', 'kb-support' ), kbs_get_ticket_label_singular( true ) ),
		'type'    => 'color',
		'std'     => kbs_get_ticket_reply_status_colour( 'customer' ),
		'default' => 'c94c4c'
	);

	return $settings;
} // kbs_settings_for_status_colours
add_filter( 'kbs_settings_styles', 'kbs_settings_for_status_colours' );
