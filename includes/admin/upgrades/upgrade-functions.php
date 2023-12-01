<?php
/**
 * Upgrade Functions
 *
 * @package     KBS
 * @subpackage  Admin/Upgrades
 * @copyright   Copyright (c) 2017, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 *
 * Taken from Easy Digital Downloads.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Processes all KBS upgrade actions sent via POST and GET by looking for the 'kbs-upgrade-action'
 * request and running do_action() to call the function
 *
 * @since 1.1
 * @return void
 */
function kbs_process_upgrade_actions() {
	if ( isset( $_POST['kbs-upgrade-action'] ) ) {
		do_action( 'kbs-upgrade-' . sanitize_text_field( wp_unslash( $_POST['kbs-upgrade-action'] ) ), $_POST );
	}

	if ( isset( $_GET['kbs-upgrade-action'] ) ) {
		do_action( 'kbs-upgrade-' . sanitize_text_field( wp_unslash( $_GET['kbs-upgrade-action'] ) ), $_GET );
	}

} // kbs_process_upgrade_actions
add_action( 'admin_init', 'kbs_process_upgrade_actions' );

/**
 * Perform automatic database upgrades when necessary
 *
 * @since	1.0
 * @return	void
*/
function kbs_do_automatic_upgrades() {

	$did_upgrade = false;
	$kbs_version = preg_replace( '/[^0-9.].*/', '', get_option( 'kbs_version' ) );

	if ( version_compare( $kbs_version, '0.9.3', '<' ) ) {
		kbs_v093_upgrades();
	}

	if ( version_compare( $kbs_version, '1.0', '<' ) ) {
		kbs_v10_upgrades();
	}

    if ( version_compare( $kbs_version, '1.1', '<' ) ) {
		kbs_v11_upgrades();
        // A quick update was applied after 1.1 so we need to ensure
        // we still redirect to the welcome page
        set_transient( '_kbs_activation_redirect', true, 30 );
	}

    if ( version_compare( $kbs_version, '1.1.9', '<' ) ) {
		kbs_v119_upgrades();
	}

	if ( version_compare( $kbs_version, '1.1.13', '<' ) ) {
		flush_rewrite_rules();
	}

	if ( version_compare( $kbs_version, '1.2', '<' ) ) {
		kbs_v12_upgrades();
	}

    if ( version_compare( $kbs_version, '1.2.2', '<' ) ) {
		kbs_v122_upgrades();
	}

    if ( version_compare( $kbs_version, '1.2.4', '<' ) ) {
		kbs_v124_upgrades();
	}

    if ( version_compare( $kbs_version, '1.2.6', '<' ) ) {
		kbs_v126_upgrades();
	}

	if ( version_compare( $kbs_version, '1.2.8', '<' ) ) {
		kbs_v128_upgrades();
	}

    if ( version_compare( $kbs_version, '1.2.9', '<' ) ) {
		kbs_v129_upgrades();
	}

	if ( version_compare( $kbs_version, '1.3.1', '<' ) ) {
		kbs_v131_upgrades();
	}

	if ( version_compare( $kbs_version, '1.3.3', '<' ) ) {
		kbs_v133_upgrades();
	}

	if ( version_compare( $kbs_version, '1.3.4', '<' ) ) {
		kbs_v134_upgrades();
	}

	if ( version_compare( $kbs_version, '1.4', '<' ) ) {
		kbs_v14_upgrades();
	}

    if ( version_compare( $kbs_version, '1.4.1', '<' ) ) {
		kbs_v141_upgrades();
	}

    if ( version_compare( $kbs_version, '1.4.2', '<' ) ) {
		kbs_v142_upgrades();
	}

	if ( version_compare( $kbs_version, '1.5', '<' ) ) {
		kbs_v15_upgrades();
	}

	if ( version_compare( $kbs_version, KBS_VERSION, '<' ) )	{

		// Let us know that an upgrade has happened
		$did_upgrade = true;

	}

	if ( $did_upgrade )	{

		// Send to what's new page
		/*if ( substr_count( KBS_VERSION, '.' ) < 2 )	{
			set_transient( '_kbs_activation_redirect', true, 30 );
		}*/

		update_option( 'kbs_version_upgraded_from', get_option( 'kbs_version' ) );
		update_option( 'kbs_version', preg_replace( '/[^0-9.].*/', '', KBS_VERSION ) );

	}

} // kbs_do_automatic_upgrades
add_action( 'admin_init', 'kbs_do_automatic_upgrades' );

/**
 * Display a notice if an upgrade is required.
 *
 * @since	1.0
 */
function kbs_show_upgrade_notice()	{

	if ( isset( $_GET['page'] ) && $_GET['page'] == 'kbs-upgrades' )	{
		return;
	}

	$kbs_version = get_option( 'kbs_version' );

	$kbs_version = preg_replace( '/[^0-9.].*/', '', $kbs_version );

	// Check if there is an incomplete upgrade routine.
	$resume_upgrade = kbs_maybe_resume_upgrade();

	if ( ! empty( $resume_upgrade ) )	{

		$resume_url = add_query_arg( $resume_upgrade, admin_url( 'index.php' ) );
		printf(
			'<div class="notice notice-error"><p>' . wp_kses_post( __( 'KB Support needs to complete an upgrade that was previously started. Click <a href="%s">here</a> to resume the upgrade.', 'kb-support' ) ) . '</p></div>',
			esc_url( $resume_url )
		);

	} else {
        $upgrades_needed = array();
		// Include all 'Stepped' upgrade process notices in this else statement,
		// to avoid having a pending, and new upgrade suggested at the same time

		if ( get_option( 'kbs_upgrade_sequential' ) && kbs_get_tickets() )    {
            $upgrades_needed[] = array(
                'name'        => sprintf(
                    esc_html__( 'KB Support needs to update existing %s.', 'kb-support' ),
                    kbs_get_ticket_label_plural( true )
                ),
                'description' => sprintf(
                    esc_html__( 'This process will update every existing %1$s in order to apply sequential %1$s numbering.', 'kb-support' ),
                    kbs_get_ticket_label_singular( true )
                ),
                'action'      => 'upgrade_sequential_ticket_numbers'
            );
        }

        if ( version_compare( $kbs_version, '1.2.9', '<' ) || ! kbs_has_upgrade_completed( 'upgrade_ticket_sources' ) ) {
            $upgrades_needed[] = array(
                'name'        => sprintf(
                    esc_html__( 'KB Support needs to update existing %s.', 'kb-support' ),
                    kbs_get_ticket_label_plural( true )
                ),
                'description' => sprintf(
                    esc_html__( 'This upgrade process will update every existing %1$s, and %1$s reply, storing the source by which they were logged within the new %2$s Sources taxonomy.', 'kb-support' ),
                    kbs_get_ticket_label_singular( true ),
                    kbs_get_ticket_label_singular()
                ),
                'action'      => 'upgrade_ticket_sources'
            );
        }

		 if ( version_compare( $kbs_version, '1.3', '<' ) || ! kbs_has_upgrade_completed( 'upgrade_ticket_departments' ) ) {
            $upgrades_needed[] = array(
                'name'        => sprintf(
                    esc_html__( 'KB Support needs to update existing %s.', 'kb-support' ),
                    kbs_get_ticket_label_plural( true )
                ),
                'description' => sprintf(
					esc_html__( 'This upgrade process will update all %s to support new department features.', 'kb-support' ),
                    kbs_get_ticket_label_plural( true )
                ),
                'action'      => 'upgrade_ticket_departments'
            );
        }

		if ( version_compare( $kbs_version, '1.3.3', '<' ) || ! kbs_has_upgrade_completed( 'upgrade_article_monthly_count' ) ) {
            $upgrades_needed[] = array(
                'name'        => sprintf(
                    esc_html__( 'KB Support needs to update existing %s.', 'kb-support' ),
                    kbs_get_article_label_plural()
                ),
                'description' => sprintf(
                    esc_html__( 'This upgrade process will update all %s to support new monthly view count features.', 'kb-support' ),
                    kbs_get_article_label_plural()
                ),
                'action'      => 'upgrade_article_monthly_count'
            );
        }

		if ( version_compare( $kbs_version, '1.5.92', '<' ) || ! kbs_has_upgrade_completed( 'upgrade_article_cpt' ) ) {
			$upgrades_needed[] = array(
				'name'        => sprintf(
					esc_html__( 'KB Support needs to update existing %s.', 'kb-support' ),
					kbs_get_article_label_plural()
				),
				'description' => sprintf(
					esc_html__( 'This upgrade process will update all %s to migrate to new custom post type.', 'kb-support' ),
					kbs_get_article_label_plural()
				),
				'action'      => 'upgrade_article_cpt'
			);
		}

        $upgrades_needed = apply_filters( 'kbs_upgrades_needed', $upgrades_needed, $kbs_version );

        if ( ! empty( $upgrades_needed ) )  {
            foreach( $upgrades_needed as $upgrade_needed ) : ?>
				<?php
				if ( ! empty( $upgrade_needed['depends'] ) && ! kbs_has_upgrade_completed( $upgrade_needed['depends'] ) )	{
					continue;
				}
				?>

                <div class="notice notice-error">
                    <p><strong><?php echo esc_html( $upgrade_needed['name'] ); ?></strong></p>
                    <p class="description"><?php echo $upgrade_needed['description']; ?></p>
                    <p><?php echo wp_kses_post( sprintf(
                        __( '<a href="%s" class="button-primary">Start Upgrade</a>', 'kb-support' ),
                        add_query_arg( array(
                            'page'               => 'kbs-upgrades',
                            'kbs-upgrade-action' => esc_html( $upgrade_needed['action'] )
                        ), admin_url( 'index.php' ) ) )
                    ); ?></p>
                </div>
            <?php endforeach;
        }

		/*
		 *  NOTICE:
		 *
		 *  When adding new upgrade notices, please be sure to put the action into the upgrades array during install:
		 *  /includes/install.php @ Appox Line 250
		 *
		 */

	}

} // kbs_show_upgrade_notice
add_action( 'admin_notices', 'kbs_show_upgrade_notice' );

/**
 * Triggers all upgrade functions.
 *
 * This function is usually triggered via AJAX.
 *
 * @since	1.0
 * @return	void
*/
function kbs_trigger_upgrades() {

	if ( ! current_user_can( 'manage_ticket_settings' ) ) {
		wp_die( esc_html__( 'You do not have permission to do perform KBS upgrades', 'kb-support' ), esc_html__( 'Error', 'kb-support' ), array( 'response' => 403 ) );
	}

	update_option( 'kbs_version', KBS_VERSION );

	if ( DOING_AJAX )	{
		die( 'complete' ); // Let AJAX know that the upgrade is complete
	}

} // kbs_trigger_upgrades
add_action( 'wp_ajax_kbs_trigger_upgrades', 'kbs_trigger_upgrades' );

/**
 * For use when doing 'stepped' upgrade routines, to see if we need to start somewhere in the middle.
 *
 * @since	1.0
 * @return	mixed	When nothing to resume returns false, otherwise starts the upgrade where it left off.
 */
function kbs_maybe_resume_upgrade() {

	$doing_upgrade = get_option( 'kbs_doing_upgrade', false );

	if ( empty( $doing_upgrade ) ) {
		return false;
	}

	return $doing_upgrade;

} // kbs_maybe_resume_upgrade

/**
 * Adds an upgrade action to the completed upgrades array.
 *
 * @since	1.0
 * @param	str		$upgrade_action		The action to add to the copmleted upgrades array.
 * @return	bool	If the function was successfully added.
 */
function kbs_set_upgrade_complete( $upgrade_action = '' ) {

	if ( empty( $upgrade_action ) ) {
		return false;
	}

	$completed_upgrades   = kbs_get_completed_upgrades();
	$completed_upgrades[] = $upgrade_action;

	// Remove any blanks, and only show uniques
	$completed_upgrades = array_unique( array_values( $completed_upgrades ) );

	return update_option( 'kbs_completed_upgrades', $completed_upgrades );
} // kbs_set_upgrade_complete

/**
 * Upgrade routine to remove upload_files capability from Support Customer.
 *
 * @since	0.9.3
 * @return	void
 */
function kbs_v093_upgrades()	{
	global $wp_roles;

	if ( class_exists('WP_Roles') ) {
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}
	}

	if ( is_object( $wp_roles ) )	{
		$wp_roles->remove_cap( 'support_customer', 'upload_files' );
	}
} // kbs_v093_upgrades

/**
 * Upgrade routine to remove all sla meta keys from tickets published prior to
 * sla functionality being released within KBS.
 *
 * @since	1.0
 * @return	void
 */
function kbs_v10_upgrades()	{
	global $wpdb, $wp_roles;

	// Remove SLA meta keys
	$wpdb->query( $wpdb->prepare(
		"
		DELETE FROM $wpdb->postmeta
		WHERE meta_key LIKE %s
		",
		'%_kbs_ticket_sla_%'
	) );

	// Add company_id column to customers table and increment version
	@KBS()->customers->create_table();

	// Add the customer role to admins and managers
	if ( class_exists('WP_Roles') )	{
		if ( ! isset( $wp_roles ) )	{
			$wp_roles = new WP_Roles();
		}
	}

	if ( is_object( $wp_roles ) )	{
		$roles = new KBS_Roles;
		$caps  = $roles->get_core_caps();

		foreach( $caps['customer'] as $cap )	{
			$wp_roles->add_cap( 'support_manager', $cap );
			$wp_roles->add_cap( 'administrator', $cap );
			$wp_roles->add_cap( 'support_agent', $cap );
		}
	}

	// Add initial install version
	add_option( 'kbs_install_version', KBS_VERSION, '', 'no' );
} // kbs_v10_upgrades

/**
 * Upgrade routine for version 1.1.
 *
 * - Default settings for agent assignment emails
 * - Default settings for sequential ticket numbers
 *
 * @since	1.1
 * @return	void
 */
function kbs_v11_upgrades()	{

    $single = kbs_get_ticket_label_singular();

    // New setting options
    $new_options = array(
        'sequential_start'          => '1',
        'agent_notices'             => '1',
        'agent_assigned_subject'    => sprintf( esc_html__( 'A %s Has Been Assigned to You - ##{ticket_id}##', 'kb-support' ), $single ),
        'agent_assign_notification' => esc_html__( 'Hey there!', 'kb-support' ) . "\n\n" .
                                      sprintf( esc_html__( 'A %s has been assigned to you at {sitename}.', 'kb-support' ), strtolower( $single ) ) . "\n\n" .
                                      "<strong>{ticket_title} - #{ticket_id}</strong>\n\n" .
                                      sprintf( esc_html__( 'Please login to view and update the %s.', 'kb-support' ), strtolower( $single ) ) . "\n\n" .
                                      "{ticket_admin_url}\n\n" .
                                      esc_html__( 'Regards', 'kb-support' ) . "\n\n" .
                                      '{sitename}'
    );

    foreach( $new_options as $option => $value )    {
        kbs_update_option( $option, $value );
    }

} // kbs_v11_upgrades

/**
 * Upgrade routine for version 1.1.9.
 *
 * - Add setting for attach files. Default to false for existing users.
 *
 * @since	1.1.9
 * @return	void
 */
function kbs_v119_upgrades()	{

    // New setting options
    $new_options = array(
        'attach_files' => '0',
    );

    foreach( $new_options as $option => $value )    {
        kbs_update_option( $option, $value );
    }

} // kbs_v119_upgrades

/**
 * Upgrade routine for version 1.2.
 *
 * - Remove ticket term capabilities from Support Agents.
 * - Add the value setting to all submission form fields
 *
 * @since	1.2
 * @return	void
 */
function kbs_v12_upgrades()	{
	global $wp_roles;

	if ( class_exists( 'WP_Roles' ) ) {
		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}
	}

	if ( is_object( $wp_roles ) )	{
		$wp_roles->remove_cap( 'support_agent', 'manage_ticket_terms' );
		$wp_roles->remove_cap( 'support_agent', 'edit_ticket_terms' );
		$wp_roles->remove_cap( 'support_agent', 'delete_ticket_terms' );
	}

	$form_fields = get_posts( array(
		'posts_per_page' => -1,
		'post_type'      => 'kbs_form_field',
		'post_status'    => 'publish',
		'fields'         => 'ids'
	) );

	if ( $form_fields )	{
		foreach( $form_fields as $field_id )	{
			$settings = get_post_meta( $field_id, '_kbs_field_settings', true );

			if ( ! isset( $settings['value'] ) )	{
				$settings['value'] = '';
				update_post_meta( $field_id, '_kbs_field_settings', $settings );
			}
		}
	}

    flush_rewrite_rules();

} // kbs_v12_upgrades

/**
 * Upgrade routine for version 1.2.2.
 *
 * - Add default privacy setting option values.
 * - Rename terms and condition option names.
 *
 * @since	1.2.2
 * @return	void
 */
function kbs_v122_upgrades()	{
    $kbs_options   = get_option( 'kbs_settings' );
    $terms_label   = ! empty( $kbs_options['agree_label'] ) ? $kbs_options['agree_label'] : esc_html__( 'I have read and agree to the terms and conditions', 'kb-support' );
    $terms_text    = ! empty( $kbs_options['agree_text'] ) ? $kbs_options['agree_text'] : '';
    $terms_heading = ! empty( $kbs_options['agree_heading'] ) ? $kbs_options['agree_heading'] : sprintf( esc_html__( 'Terms and Conditions for Support %s', 'kb-support' ), kbs_get_ticket_label_plural() );

    $new_options = array(
        'show_agree_to_privacy_policy' => false,
        'agree_privacy_label'          => '',
        'ticket_privacy_action'        => 'none',
        'agree_terms_label'            => $terms_label,
        'agree_terms_heading'          => $terms_heading,
        'agree_terms_description'      => '',
        'agree_terms_text'             => $terms_text,
        'agree_privacy_description'    => ''
    );

    foreach( $new_options as $option => $value )    {
        kbs_update_option( $option, $value );
    }

    kbs_delete_option( 'agree_label' );
    kbs_delete_option( 'agree_heading' );
    kbs_delete_option( 'agree_text' );
} // kbs_v122_upgrades

/**
 * Upgrade routine for version 1.2.4.
 *
 * - Add participants option.
 *
 * @since	1.2.4
 * @return	void
 */
function kbs_v124_upgrades()	{

    $new_options = array(
        'enable_participants' => false,
        'copy_participants'   => false
    );

    foreach( $new_options as $option => $value )    {
        kbs_update_option( $option, $value );
    }
} // kbs_v124_upgrades

/**
 * Upgrade routine for version 1.2.6.
 *
 * - Add default options for new settings.
 *
 * @since	1.2.6
 * @return	void
 */
function kbs_v126_upgrades()	{

    $new_options = array(
        'show_name_fields'    => 'both',
        'require_name_fields' => 'both',
        'reg_name_format'     => 'email',
        'default_role'        => 'support_customer',
        'replies_to_load'     => 5,
        'hide_closed_front'   => 0,
        'remove_rating'       => 0
    );

    foreach( $new_options as $option => $value )    {
        kbs_update_option( $option, $value );
    }
} // kbs_v126_upgrades

/**
 * Upgrade routine for version 1.2.8.
 *
 * - Add default options for new settings.
 *
 * @since	1.2.8
 * @return	void
 */
function kbs_v128_upgrades()	{

    $new_options = array(
        'no_notify_received_emails' => ''
    );

    foreach( $new_options as $option => $value )    {
        kbs_update_option( $option, $value );
    }
} // kbs_v128_upgrades

/**
 * Upgrade routine for version 1.2.9.
 *
 * - Create ticket source terms.
 *
 * @since	1.2.9
 * @return	void
 */
function kbs_v129_upgrades()	{

    $source_terms = get_terms( array(
        'taxonomy' => 'ticket_source',
        'hide_empty' => false
    ) );

    if ( empty( $source_terms ) && ! is_wp_error( $source_terms ) )   {
        $sources = array(
            1  => array(
                'slug' => 'kbs-website',
                'name' => esc_html__( 'Website', 'kb-support' ),
                'desc' => sprintf( esc_html__( '%s received via website', 'kb-support' ), kbs_get_ticket_label_plural() )
            ),
            2  => array(
                'slug' => 'kbs-email',
                'name' => esc_html__( 'Email', 'kb-support' ),
                'desc' => sprintf( esc_html__( '%s received via email', 'kb-support' ), kbs_get_ticket_label_plural() )
            ),
            3  => array(
                'slug' => 'kbs-telephone',
                'name' => esc_html__( 'Telephone', 'kb-support' ),
                'desc' => sprintf( esc_html__( '%s received via telephone', 'kb-support' ), kbs_get_ticket_label_plural() )
            ),
            99 => array(
                'slug' => 'kbs-other',
                'name' => esc_html__( 'Other', 'kb-support' ),
                'desc' => sprintf( esc_html__( '%s received via another means', 'kb-support' ), kbs_get_ticket_label_plural() )
            )
        );

        $sources = apply_filters( 'kbs_ticket_log_sources', $sources );

        foreach( $sources as $key => $source )  {
            $name = trim( sanitize_text_field( $source['name'] ) );
            $desc = sanitize_text_field( $source['desc'] );
            $slug = sanitize_text_field( $source['slug'] );

            $insert = wp_insert_term(
                $name,
                'ticket_source',
                array(
                    'description' => $desc,
                    'slug'        => $slug
                )
            );
        }
    }

} // kbs_v129_upgrades

/**
 * Upgrade routine for version 1.3.1.
 *
 * - Add ticket status reply options.
 *
 * @since	1.3.1
 * @return	void
 */
function kbs_v131_upgrades()	{
	$all_statuses = kbs_get_ticket_statuses();
    $options      = array();

    foreach( $all_statuses as $status => $label )   {
        if ( 'open' == $status )  {
            continue;
        }

		$option_id = 'reply_while_status_' . $status;

		if ( 'closed' == $status )	{
			$options[ $option_id ] = 'open';
		} else	{
			$options[ $option_id ] = $status;
		}
    }

	foreach( $options as $key => $value )	{
		kbs_update_option( $key, $value );
	}

} // kbs_v131_upgrades

/**
 * Upgrade routine for version 1.3.3.
 *
 * - Add article views dashboard widget option.
 *
 * @since	1.3.3
 * @return	void
 */
function kbs_v133_upgrades()	{
    $options      = array(
		'article_views_dashboard' => 1
	);

	foreach( $options as $key => $value )	{
		kbs_update_option( $key, $value );
	}
} // kbs_v133_upgrades

/**
 * Upgrade routine for version 1.3.4.
 *
 * - Add agent article view count option.
 *
 * @since	1.3.4
 * @return	void
 */
function kbs_v134_upgrades()	{
    $options      = array(
		'count_agent_article_views' => 0,
		'replies_to_expand'         => 1
	);

	foreach( $options as $key => $value )	{
		kbs_update_option( $key, $value );
	}
} // kbs_v134_upgrades

/**
 * Upgrade routine for version 1.4.
 *
 * - Set default for Agents Set Reply Status option.
 * - Set default for Agent Reply Status option.
 * - Set colours for ticket status and replies.
 *
 * @since	1.4
 * @return	void
 */
function kbs_v14_upgrades()	{
    $options      = array(
		'agent_update_status_reply' => 0,
		'agent_reply_status'        => 0,
        'show_count_menubar'        => 'front'
	);

	$status_colours = array(
		'open'   => '#82b74b',
		'hold'   => '#0074a2',
		'closed' => '#dd3333'
	);

	foreach( $status_colours as $status => $status_colour )	{
		$key = 'colour_' . $status;
		$options[ $key ] = $status_colour;
	}

	$reply_colours = array(
		'admin'    => '#6b5b95',
		'agent'    => '#6b5b95',
		'customer' => '#c94c4c'
	);

	foreach( $reply_colours as $replier => $reply_colour )	{
		$key = 'colour_reply_' . $replier;
		$options[ $key ] = $reply_colour;
	}

	foreach( $options as $key => $value )	{
		kbs_update_option( $key, $value );
	}
} // kbs_v14_upgrades

/**
 * Upgrade routine for version 1.4.1.
 *
 * - Set default colour for tickets in the 'New' status.
 *
 * @since	1.4.1
 * @return	void
 */
function kbs_v141_upgrades()	{
    $options      = array(
		'colour_new' => '#827a93'
	);

	foreach( $options as $key => $value )	{
		kbs_update_option( $key, $value );
	}
} // kbs_v141_upgrades

/**
 * Upgrade routine for version 1.4.2.
 *
 * Reset all agent ticket counts.
 *
 * @since	1.4.2
 * @return	void
 */
function kbs_v142_upgrades()	{
    $agents = kbs_get_agents( true );

    if ( ! empty( $agents ) )   {
        foreach( $agents as $agent_id ) {
            delete_user_option( $agent_id, 'kbs_open_tickets' );
        }
    }
} // kbs_v142_upgrades

/**
 * Upgrade routine for version 1.5.
 *
 * - Create REST API ticket source term.
 *
 * @since	1.5
 * @return	void
 */
function kbs_v15_upgrades()	{
	$name = esc_html__( 'REST API', 'kb-support' );
	$desc = sprintf( esc_html__( '%s received via REST API', 'kb-support' ), kbs_get_ticket_label_plural() );
	$slug = 'kbs-rest';

	$insert = wp_insert_term(
		$name,
		'ticket_source',
		array(
			'description' => $desc,
			'slug'        => $slug
		)
	);

} // kbs_v15_upgrades

function kbs_upgrade_render_upgrade_article_cpt() {
	$migration_complete = kbs_has_upgrade_completed( 'upgrade_article_cpt' );

	if ( $migration_complete ) : ?>
		<div id="kbs-migration-complete" class="notice notice-success">
			<p>
				<?php echo wp_kses_post( sprintf( __( '<strong>Migration complete:</strong> You have already completed the update to %s sources.', 'kb-support' ), kbs_get_ticket_label_singular( true ) ) ); ?>
			</p>
			<p class="return-to-dashboard">
				<a href="<?php echo esc_url( admin_url() ); ?>">
					<?php esc_html_e( 'WordPress Dashboard', 'kb-support' ); ?>
				</a>&nbsp;&#124;&nbsp;
				<a href="<?php echo esc_url( self_admin_url( 'edit.php?post_type=kbs_ticket' ) ); ?>">
					<?php printf( esc_html__( 'KBS %s', 'kb-support' ), kbs_get_ticket_label_plural() ); ?>
				</a>
			</p>
		</div>
		<?php return; ?>
	<?php endif; ?>
	<div id="kbs-migration-ready" class="notice notice-success" style="display: none;">
		<p>
			<?php echo wp_kses_post( sprintf( __( '<strong>%s Upgrade Complete:</strong> All database upgrades have been completed.', 'kb-support' ), kbs_get_ticket_label_singular() ) ); ?>
			<br /><br />
			<?php esc_html_e( 'You may now leave this page.', 'kb-support' ); ?>
		</p>
		<p class="return-to-dashboard">
			<a href="<?php echo esc_url( admin_url() ); ?>">
				<?php esc_html_e( 'WordPress Dashboard', 'kb-support' ); ?>
			</a>&nbsp;&nbsp;&#124;&nbsp;&nbsp;
			<a href="<?php echo esc_url( self_admin_url( 'edit.php?post_type=kbs_ticket' ) ); ?>">
				<?php printf( esc_html__( 'KBS %s', 'kb-support' ), kbs_get_ticket_label_plural() ); ?>
			</a>
		</p>
	</div>

	<div id="kbs-migration-nav-warn" class="notice notice-info">
		<h3><?php esc_html_e( 'Important', 'kb-support' ); ?></h3>
		<p>
			<?php esc_html_e( 'Please leave this screen open and do not navigate away until the process completes.', 'kb-support' ); ?>
		</p>
	</div>

	<style>
		.dashicons.dashicons-yes { display: none; color: rgb(0, 128, 0); vertical-align: middle; }
	</style>

	<div class="metabox-holder">
		<div class="postbox">
			<h2 class="hndle">
				<span><?php printf( esc_html__( 'Update %s data', 'kb-support' ), kbs_get_article_label_plural( true ) ); ?></span>
				<span class="dashicons dashicons-yes"></span>
			</h2>
			<div class="inside migrate-ticket-sources-control">
				<p>
					<?php printf( esc_html__( 'This will update each %s and use the new CPT.', 'kb-support' ), kbs_get_article_label_singular( true ), kbs_get_article_label_singular() ); ?>
				</p>
				<form method="post" id="kbs-update-article-cpt" class="kbs-export-form kbs-import-export-form">
			<span class="step-instructions-wrapper">

				<?php wp_nonce_field( 'kbs_ajax_update_article_cpt', 'kbs_ajax_nonce' ); ?>

				<?php if ( ! $migration_complete ) : ?>
					<span class="kbs-migration allowed">
						<input type="submit" id="update-article-cpt-submit"
							   value="<?php printf( esc_attr__( 'Update %s CPT', 'kb-support' ), kbs_get_article_label_plural() ); ?>"
							   class="button-primary"/>
					</span>
					<input type="hidden" name="action" value="kbs_update_article_cpt">
				<?php else: ?>
					<input type="submit" disabled="disabled" id="update-article-cpt-submit"
						   value="<?php printf( esc_attr__( 'Update %s Sources', 'kb-support' ), kbs_get_article_label_singular() ); ?>"
						   class="button-secondary"/>
					&mdash; <?php printf( esc_html__( '%s Sources have already been updated.', 'kb-support' ), kbs_get_article_label_singular() ); ?>
				<?php endif; ?>

				<span class="spinner"></span>

			</span>
				</form>
			</div><!-- .inside -->
		</div><!-- .postbox -->
	</div>
	<script type="text/javascript">
		jQuery(function ($) {
			$('#kbs-update-article-cpt').on('submit', function (e) {
				e.preventDefault();
				let that = $(this)
				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: $(this).serialize(),
					beforeSend: function () {
						$('.spinner').css('visibility', 'visible')
						that.find('input[type="submit"]').attr('disabled', 'disabled')
					},
					success:function(){
						location.reload()
					}
				})
				return false;
			})
		})
	</script>
	<?php
}

/**
 * Update sequential ticket numbers.
 *
 * @since	1.2.9
 * @return	void
 */
function kbs_upgrade_render_upgrade_sequential_ticket_numbers() {
    $needs_migration = get_option( 'kbs_upgrade_sequential' );

    if ( ! $needs_migration ) : ?>
        <div id="kbs-migration-complete" class="notice notice-success">
			<p>
				<?php echo wp_kses_post( sprintf( __( '<strong>Update complete:</strong> You have already completed the update to %s numbers.', 'kb-support' ), kbs_get_ticket_label_singular( true ) ) ); ?>
			</p>
            <p class="return-to-dashboard">
                <a href="<?php echo esc_url( admin_url() ); ?>">
                    <?php esc_html_e( 'WordPress Dashboard', 'kb-support' ); ?>
                </a>&nbsp;&#124;&nbsp;
                <a href="<?php echo esc_url( self_admin_url( 'edit.php?post_type=kbs_ticket' ) ); ?>">
                    <?php printf( esc_html__( 'KBS %s', 'kb-support' ), kbs_get_ticket_label_plural() ); ?>
                </a>
            </p>
		</div>
		<?php return; ?>
    <?php endif; ?>

    <div id="kbs-migration-ready" class="notice notice-success" style="display: none;">
		<p>
			<?php printf(
                wp_kses_post( __( '<strong>%s Update Complete:</strong> All %s numbers have been updated.', 'kb-support' ) ),
                kbs_get_ticket_label_singular(),
                kbs_get_ticket_label_singular( true )
            ); ?>
			<br /><br />
			<?php esc_html_e( 'You may now leave this page.', 'kb-support' ); ?>
		</p>
        <p class="return-to-dashboard">
            <a href="<?php echo esc_url( admin_url() ); ?>">
                <?php esc_html_e( 'WordPress Dashboard', 'kb-support' ); ?>
            </a>&nbsp;&#124;&nbsp;
            <a href="<?php echo esc_url( self_admin_url( 'edit.php?post_type=kbs_ticket' ) ); ?>">
                <?php printf( esc_html__( 'KBS %s', 'kb-support' ), kbs_get_ticket_label_plural() ); ?>
            </a>
        </p>
	</div>

	<div id="kbs-migration-nav-warn" class="notice notice-info">
		<p>
			<?php wp_kses_post( _e( '<strong>Important:</strong> Please leave this screen open and do not navigate away until the process completes.', 'kb-support' ) ); ?>
		</p>
	</div>

	<style>
		.dashicons.dashicons-yes { display: none; color: rgb(0, 128, 0); vertical-align: middle; }
	</style>
	<script>
		jQuery( function($) {
			$(document).ready(function () {
				$(document).on("DOMNodeInserted", function (e) {
					var element = e.target;

					if (element.id === 'kbs-batch-success') {
						element = $(element);

						element.parent().prev().find('.kbs-migration.allowed').hide();
						element.parent().prev().find('.kbs-migration.unavailable').show();
						var element_wrapper = element.parents().eq(4);
						element_wrapper.find('.dashicons.dashicons-yes').show();

						var next_step_wrapper = element_wrapper.next();
						if (next_step_wrapper.find('.postbox').length) {
							next_step_wrapper.find('.kbs-migration.allowed').show();
							next_step_wrapper.find('.kbs-migration.unavailable').hide();

							if (auto_start_next_step) {
								next_step_wrapper.find('.kbs-export-form').submit();
							}
						} else {
							$('#kbs-migration-nav-warn').hide();
							$('#kbs-migration-ready').slideDown();
						}

					}
				});
			});
		});
	</script>

	<div class="metabox-holder">
		<div class="postbox">
			<h2 class="hndle">
				<span><?php printf( esc_html__( 'Update %s numbers', 'kb-support' ), kbs_get_ticket_label_singular( true ) ); ?></span>
				<span class="dashicons dashicons-yes"></span>
			</h2>
			<div class="inside update-ticket-numbers-control">
				<p>
					<?php printf( esc_html__( 'This will update each %s to use sequential numbering.', 'kb-support' ), kbs_get_ticket_label_singular( true ) ); ?>
				</p>
				<form method="post" id="kbs-update-ticket-numbers-form" class="kbs-export-form kbs-import-export-form">
			<span class="step-instructions-wrapper">

				<?php wp_nonce_field( 'kbs_ajax_export', 'kbs_ajax_export' ); ?>

				<?php if ( $needs_migration ) : ?>
					<span class="kbs-migration allowed">
						<input type="submit" id="update-ticket-numbers-submit" value="<?php printf( esc_attr__( 'Update %s Numbers', 'kb-support' ), kbs_get_ticket_label_singular() ); ?>" class="button-primary"/>
					</span>
				<?php else: ?>
					<input type="submit" disabled="disabled" id="update-ticket-numbers-submit" value="<?php printf( esc_attr__( 'Update %s Numbers', 'kb-support' ), kbs_get_ticket_label_singular() ); ?>" class="button-secondary"/>
					&mdash; <?php printf( esc_html__( '%s numbers have already been updated.', 'kb-support' ), kbs_get_ticket_label_singular() ); ?>
				<?php endif; ?>

				<input type="hidden" name="kbs-export-class" value="KBS_Ticket_Sequential_Numbering_Migration" />
				<span class="spinner"></span>

			</span>
				</form>
			</div><!-- .inside -->
		</div><!-- .postbox -->
	</div>

	<?php
} // kbs_upgrade_render_upgrade_sequential_ticket_numbers

/**
 * Upgrades for KBS v1.2.9 and ticket sources.
 *
 * @since	1.2.9
 * @return	void
 */
function kbs_upgrade_render_upgrade_ticket_sources()	{
	$migration_complete = kbs_has_upgrade_completed( 'upgrade_ticket_sources' );

	if ( $migration_complete ) : ?>
		<div id="kbs-migration-complete" class="notice notice-success">
			<p>
				<?php echo wp_kses_post( sprintf( __( '<strong>Migration complete:</strong> You have already completed the update to %s sources.', 'kb-support' ), kbs_get_ticket_label_singular( true ) ) ); ?>
			</p>
            <p class="return-to-dashboard">
                <a href="<?php echo esc_url( admin_url() ); ?>">
                    <?php esc_html_e( 'WordPress Dashboard', 'kb-support' ); ?>
                </a>&nbsp;&#124;&nbsp;
                <a href="<?php echo esc_url( self_admin_url( 'edit.php?post_type=kbs_ticket' ) ); ?>">
                    <?php printf( esc_html__( 'KBS %s', 'kb-support' ), kbs_get_ticket_label_plural() ); ?>
                </a>
            </p>
		</div>
		<?php return; ?>
	<?php endif; ?>

	<div id="kbs-migration-ready" class="notice notice-success" style="display: none;">
		<p>
			<?php echo wp_kses_post( sprintf( __( '<strong>%s Upgrade Complete:</strong> All database upgrades have been completed.', 'kb-support' ), kbs_get_ticket_label_singular() ) ); ?>
			<br /><br />
			<?php esc_html_e( 'You may now leave this page.', 'kb-support' ); ?>
		</p>
        <p class="return-to-dashboard">
            <a href="<?php echo esc_url( admin_url() ); ?>">
                <?php esc_html_e( 'WordPress Dashboard', 'kb-support' ); ?>
            </a>&nbsp;&nbsp;&#124;&nbsp;&nbsp;
            <a href="<?php echo esc_url( self_admin_url( 'edit.php?post_type=kbs_ticket' ) ); ?>">
                <?php printf( esc_html__( 'KBS %s', 'kb-support' ), kbs_get_ticket_label_plural() ); ?>
            </a>
        </p>
	</div>

	<div id="kbs-migration-nav-warn" class="notice notice-info">
		<h3><?php esc_html_e( 'Important', 'kb-support' ); ?></h3>
		<p>
			<?php esc_html_e( 'Please leave this screen open and do not navigate away until the process completes.', 'kb-support' ); ?>
		</p>
	</div>

	<style>
		.dashicons.dashicons-yes { display: none; color: rgb(0, 128, 0); vertical-align: middle; }
	</style>
	<script>
		jQuery( function($) {
			$(document).ready(function () {
				$(document).on("DOMNodeInserted", function (e) {
					var element = e.target;

					if (element.id === 'kbs-batch-success') {
						element = $(element);

						element.parent().prev().find('.kbs-migration.allowed').hide();
						element.parent().prev().find('.kbs-migration.unavailable').show();
						var element_wrapper = element.parents().eq(4);
						element_wrapper.find('.dashicons.dashicons-yes').show();

						var next_step_wrapper = element_wrapper.next();
						if (next_step_wrapper.find('.postbox').length) {
							next_step_wrapper.find('.kbs-migration.allowed').show();
							next_step_wrapper.find('.kbs-migration.unavailable').hide();

							if (auto_start_next_step) {
								next_step_wrapper.find('.kbs-export-form').submit();
							}
						} else {
							$('#kbs-migration-nav-warn').hide();
							$('#kbs-migration-ready').slideDown();
						}

					}
				});
			});
		});
	</script>

	<div class="metabox-holder">
		<div class="postbox">
			<h2 class="hndle">
				<span><?php printf( esc_html__( 'Update %s sources', 'kb-support' ), kbs_get_ticket_label_singular( true ) ); ?></span>
				<span class="dashicons dashicons-yes"></span>
			</h2>
			<div class="inside migrate-ticket-sources-control">
				<p>
					<?php printf( esc_html__( 'This will update each %s and use the new %s Source taxonomy to identify the means by which it was logged.', 'kb-support' ), kbs_get_ticket_label_singular( true ), kbs_get_ticket_label_singular() ); ?>
				</p>
				<form method="post" id="kbs-update-ticket-sources-form" class="kbs-export-form kbs-import-export-form">
			<span class="step-instructions-wrapper">

				<?php wp_nonce_field( 'kbs_ajax_export', 'kbs_ajax_export' ); ?>

				<?php if ( ! $migration_complete ) : ?>
					<span class="kbs-migration allowed">
						<input type="submit" id="update-ticket-sources-submit" value="<?php printf( esc_attr__( 'Update %s Sources', 'kb-support' ), kbs_get_ticket_label_singular() ); ?>" class="button-primary"/>
					</span>
				<?php else: ?>
					<input type="submit" disabled="disabled" id="update-ticket-sources-submit" value="<?php printf( esc_attr__( 'Update %s Sources', 'kb-support' ), kbs_get_ticket_label_singular() ); ?>" class="button-secondary"/>
					&mdash; <?php printf( esc_html__( '%s Sources have already been updated.', 'kb-support' ), kbs_get_ticket_label_singular() ); ?>
				<?php endif; ?>

				<input type="hidden" name="kbs-export-class" value="KBS_Ticket_Sources_Migration" />
				<span class="spinner"></span>

			</span>
				</form>
			</div><!-- .inside -->
		</div><!-- .postbox -->
	</div>

	<?php
} // kbs_upgrade_render_upgrade_ticket_sources

/**
 * Upgrades for KBS v1.3 and ticket departments.
 *
 * @since	1.3
 * @return	void
 */
function kbs_upgrade_render_upgrade_ticket_departments()	{
	$migration_complete = kbs_has_upgrade_completed( 'upgrade_ticket_departments' );

	if ( $migration_complete ) : ?>
		<div id="kbs-migration-complete" class="notice notice-success">
			<p>
				<?php echo wp_kses_post( sprintf(  __( '<strong>Migration complete:</strong> You have already completed the update to %s departments.', 'kb-support' ) , kbs_get_ticket_label_singular( true ) ) ); ?>
			</p>
            <p class="return-to-dashboard">
                <a href="<?php echo esc_url( admin_url() ); ?>">
                    <?php esc_html_e( 'WordPress Dashboard', 'kb-support' ); ?>
                </a>&nbsp;&#124;&nbsp;
                <a href="<?php echo esc_url( self_admin_url( 'edit.php?post_type=kbs_ticket' ) ); ?>">
                    <?php printf( esc_html__( 'KBS %s', 'kb-support' ), kbs_get_ticket_label_plural() ); ?>
                </a>
            </p>
		</div>
		<?php return; ?>
	<?php endif; ?>

	<div id="kbs-migration-ready" class="notice notice-success" style="display: none;">
		<p>
			<?php echo wp_kses_post( sprintf( __( '<strong>%s Upgrade Complete:</strong> All database upgrades have been completed.', 'kb-support' ), kbs_get_ticket_label_singular() ) ); ?>
			<br /><br />
			<?php esc_html_e( 'You may now leave this page.', 'kb-support' ); ?>
		</p>
        <p class="return-to-dashboard">
            <a href="<?php echo esc_url( admin_url() ); ?>">
                <?php esc_html_e( 'WordPress Dashboard', 'kb-support' ); ?>
            </a>&nbsp;&nbsp;&#124;&nbsp;&nbsp;
            <a href="<?php echo esc_url( self_admin_url( 'edit.php?post_type=kbs_ticket' ) ); ?>">
                <?php printf( esc_html__( 'KBS %s', 'kb-support' ), kbs_get_ticket_label_plural() ); ?>
            </a>
        </p>
	</div>

	<div id="kbs-migration-nav-warn" class="notice notice-info">
		<h3><?php esc_html_e( 'Important', 'kb-support' ); ?></h3>
		<p>
			<?php esc_html_e( 'Please leave this screen open and do not navigate away until the process completes.', 'kb-support' ); ?>
		</p>
	</div>

	<style>
		.dashicons.dashicons-yes { display: none; color: rgb(0, 128, 0); vertical-align: middle; }
	</style>
	<script>
		jQuery( function($) {
			$(document).ready(function () {
				$(document).on("DOMNodeInserted", function (e) {
					var element = e.target;

					if (element.id === 'kbs-batch-success') {
						element = $(element);

						element.parent().prev().find('.kbs-migration.allowed').hide();
						element.parent().prev().find('.kbs-migration.unavailable').show();
						var element_wrapper = element.parents().eq(4);
						element_wrapper.find('.dashicons.dashicons-yes').show();

						var next_step_wrapper = element_wrapper.next();
						if (next_step_wrapper.find('.postbox').length) {
							next_step_wrapper.find('.kbs-migration.allowed').show();
							next_step_wrapper.find('.kbs-migration.unavailable').hide();

							if (auto_start_next_step) {
								next_step_wrapper.find('.kbs-export-form').submit();
							}
						} else {
							$('#kbs-migration-nav-warn').hide();
							$('#kbs-migration-ready').slideDown();
						}

					}
				});
			});
		});
	</script>

	<div class="metabox-holder">
		<div class="postbox">
			<h2 class="hndle">
				<span><?php printf( esc_html__( 'Update %s departments', 'kb-support' ), kbs_get_ticket_label_singular( true ) ); ?></span>
				<span class="dashicons dashicons-yes"></span>
			</h2>
			<div class="inside migrate-ticket-departments-control">
				<p>
					<?php printf( esc_html__( 'This will update each %s adding additional data to support department features.', 'kb-support' ), kbs_get_ticket_label_singular( true ) ); ?>
				</p>
				<form method="post" id="kbs-update-ticket-departments-form" class="kbs-export-form kbs-import-export-form">
			<span class="step-instructions-wrapper">

				<?php wp_nonce_field( 'kbs_ajax_export', 'kbs_ajax_export' ); ?>

				<?php if ( ! $migration_complete ) : ?>
					<span class="kbs-migration allowed">
						<input type="submit" id="update-ticket-departments-submit" value="<?php printf( esc_attr__( 'Update %s Departments', 'kb-support' ), kbs_get_ticket_label_singular() ); ?>" class="button-primary"/>
					</span>
				<?php else: ?>
					<input type="submit" disabled="disabled" id="update-ticket-departments-submit" value="<?php printf( esc_attr__( 'Update %s Departments', 'kb-support' ), kbs_get_ticket_label_singular() ); ?>" class="button-secondary"/>
					&mdash; <?php printf( esc_html__( '%s Departments have already been updated.', 'kb-support' ), kbs_get_ticket_label_singular() ); ?>
				<?php endif; ?>

				<input type="hidden" name="kbs-export-class" value="KBS_Ticket_Department_Migration" />
				<span class="spinner"></span>

			</span>
				</form>
			</div><!-- .inside -->
		</div><!-- .postbox -->
	</div>

	<?php
} // kbs_upgrade_render_upgrade_ticket_departments

/**
 * Upgrades for KBS v1.3.3 and article monthly view counts.
 *
 * @since	1.3.3
 * @return	void
 */
function kbs_upgrade_render_upgrade_article_monthly_count()	{
	$migration_complete = kbs_has_upgrade_completed( 'upgrade_article_monthly_count' );

	if ( $migration_complete ) : ?>
		<div id="kbs-migration-complete" class="notice notice-success">
			<p>
				<?php echo wp_kses_post( sprintf( __( '<strong>Migration complete:</strong> You have already completed the update to %s monthly view counts.', 'kb-support' ), kbs_get_article_label_plural() ) ); ?>
			</p>
            <p class="return-to-dashboard">
                <a href="<?php echo esc_url( admin_url() ); ?>">
                    <?php esc_html_e( 'WordPress Dashboard', 'kb-support' ); ?>
                </a>&nbsp;&#124;&nbsp;
                <a href="<?php echo esc_url( self_admin_url( 'edit.php?post_type=kbs_ticket' ) ); ?>">
                    <?php printf( esc_html__( 'KBS %s', 'kb-support' ), kbs_get_ticket_label_plural() ); ?>
                </a>
            </p>
		</div>
		<?php return; ?>
	<?php endif; ?>

	<div id="kbs-migration-ready" class="notice notice-success" style="display: none;">
		<p>
			<?php echo wp_kses_post( sprintf(  __( '<strong>%s Upgrade Complete:</strong> All database upgrades have been completed.', 'kb-support' ), kbs_get_article_label_plural() ) ); ?>
			<br /><br />
			<?php esc_html_e( 'You may now leave this page.', 'kb-support' ); ?>
		</p>
        <p class="return-to-dashboard">
            <a href="<?php echo esc_url( admin_url() ); ?>">
                <?php esc_html_e( 'WordPress Dashboard', 'kb-support' ); ?>
            </a>&nbsp;&nbsp;&#124;&nbsp;&nbsp;
            <a href="<?php echo esc_url( self_admin_url( 'edit.php?post_type=kbs_ticket' ) ); ?>">
                <?php printf( esc_html__( 'KBS %s', 'kb-support' ), kbs_get_ticket_label_plural() ); ?>
            </a>
        </p>
	</div>

	<div id="kbs-migration-nav-warn" class="notice notice-info">
		<h3><?php esc_html_e( 'Important', 'kb-support' ); ?></h3>
		<p>
			<?php esc_html_e( 'Please leave this screen open and do not navigate away until the process completes.', 'kb-support' ); ?>
		</p>
	</div>

	<style>
		.dashicons.dashicons-yes { display: none; color: rgb(0, 128, 0); vertical-align: middle; }
	</style>
	<script>
		jQuery( function($) {
			$(document).ready(function () {
				$(document).on("DOMNodeInserted", function (e) {
					var element = e.target;

					if (element.id === 'kbs-batch-success') {
						element = $(element);

						element.parent().prev().find('.kbs-migration.allowed').hide();
						element.parent().prev().find('.kbs-migration.unavailable').show();
						var element_wrapper = element.parents().eq(4);
						element_wrapper.find('.dashicons.dashicons-yes').show();

						var next_step_wrapper = element_wrapper.next();
						if (next_step_wrapper.find('.postbox').length) {
							next_step_wrapper.find('.kbs-migration.allowed').show();
							next_step_wrapper.find('.kbs-migration.unavailable').hide();

							if (auto_start_next_step) {
								next_step_wrapper.find('.kbs-export-form').submit();
							}
						} else {
							$('#kbs-migration-nav-warn').hide();
							$('#kbs-migration-ready').slideDown();
						}

					}
				});
			});
		});
	</script>

	<div class="metabox-holder">
		<div class="postbox">
			<h2 class="hndle">
				<span><?php printf( esc_html__( 'Update %s monthly view counts', 'kb-support' ), kbs_get_article_label_singular() ); ?></span>
				<span class="dashicons dashicons-yes"></span>
			</h2>
			<div class="inside migrate-article-monthly-views-control">
				<p>
					<?php printf( esc_html__( 'This will update each %s adding monthly view counts.', 'kb-support' ), kbs_get_article_label_singular() ); ?>
				</p>
				<form method="post" id="kbs-update-article-monthly-views-form" class="kbs-export-form kbs-import-export-form">
			<span class="step-instructions-wrapper">

				<?php wp_nonce_field( 'kbs_ajax_export', 'kbs_ajax_export' ); ?>

				<?php if ( ! $migration_complete ) : ?>
					<span class="kbs-migration allowed">
						<input type="submit" id="update-article-monthly-views-submit" value="<?php printf( esc_attr__( 'Update %s', 'kb-support' ), kbs_get_article_label_plural() ); ?>" class="button-primary"/>
					</span>
				<?php else: ?>
					<input type="submit" disabled="disabled" id="update-article-monthly-views-submit" value="<?php printf( esc_attr__( 'Update %s', 'kb-support' ), kbs_get_article_label_plural() ); ?>" class="button-secondary"/>
					&mdash; <?php printf( esc_html__( '%s have already been updated.', 'kb-support' ), kbs_get_article_label_plural() ); ?>
				<?php endif; ?>

				<input type="hidden" name="kbs-export-class" value="KBS_Article_Monthly_Count_Migration" />
				<span class="spinner"></span>

			</span>
				</form>
			</div><!-- .inside -->
		</div><!-- .postbox -->
	</div>

	<?php
} // kbs_upgrade_render_upgrade_article_monthly_count
