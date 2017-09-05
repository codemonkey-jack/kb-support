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

	if ( version_compare( $kbs_version, KBS_VERSION, '<' ) )	{

		// Let us know that an upgrade has happened
		$did_upgrade = true;

	}

	if ( $did_upgrade )	{

		// Send to what's new page
		if ( substr_count( KBS_VERSION, '.' ) < 2 )	{
			set_transient( '_kbs_activation_redirect', true, 30 );
		}

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
			'<div class="notice notice-error"><p>' . __( 'KB Support needs to complete an upgrade that was previously started. Click <a href="%s">here</a> to resume the upgrade.', 'kb-support' ) . '</p></div>',
			esc_url( $resume_url )
		);

	} else {

		// Include all 'Stepped' upgrade process notices in this else statement,
		// to avoid having a pending, and new upgrade suggested at the same time

		if ( get_option( 'kbs_upgrade_sequential' ) && kbs_get_tickets() ) {
			printf(
				'<div class="updated"><p>' . __( 'KB Support needs to upgrade past %s numbers to make them sequential, click <a href="%s">here</a> to start the upgrade.', 'kb-support' ) . '</p></div>',
				kbs_get_ticket_label_singular( true ),
				admin_url( 'index.php?page=kbs-upgrades&kbs-upgrade=upgrade_sequential_ticket_numbers' )
			);
		}

		/*
		 *  NOTICE:
		 *
		 *  When adding new upgrade notices, please be sure to put the action into the upgrades array during install:
		 *  /includes/install.php @ Appox Line 198
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
		wp_die( __( 'You do not have permission to do perform KBS upgrades', 'kb-support' ), __( 'Error', 'kb-support' ), array( 'response' => 403 ) );
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
 * Upgrades for KBS v1.1 and sequential ticket numbers
 *
 * @since	1.1
 * @return	void
 */
function kbs_v11_upgrade_sequential_ticket_numbers()	{

	if ( ! current_user_can( 'manage_ticket_settings' ) )	{
		wp_die( __( 'You do not have permission to perform upgrades', 'kb-support' ), __( 'Error', 'kb-support' ), array( 'response' => 403 ) );
	}

	ignore_user_abort( true );

	if ( ! kbs_is_func_disabled( 'set_time_limit' ) )	{
		set_time_limit( 0 );
	}

	$step   = isset( $_GET['step'] )  ? absint( $_GET['step'] )  : 1;
	$total  = isset( $_GET['total'] ) ? absint( $_GET['total'] ) : false;

	if( empty( $total ) || $total <= 1 ) {
		$tickets = kbs_count_tickets();
		foreach( $tickets as $ticket ) {
			$total += $status;
		}
	}

	$args = array(
		'number' => 100,
		'page'   => $step,
		'status' => 'any',
		'order'  => 'ASC'
	);

	$tickets = new KBS_Tickets_Query( $args );
	$tickets = $tickets->get_tickets();

	if ( $tickets )	{

		$prefix = kbs_get_option( 'ticket_prefix' );
		$suffix = kbs_get_option( 'ticket_suffix' );
		$number = ! empty( $_GET['custom'] ) ? absint( $_GET['custom'] ) : intval( kbs_get_option( 'sequential_start', 1 ) );

		foreach( $tickets as $ticket )	{

			// Re-add the prefix and suffix
			$ticket_number = $prefix . $number . $suffix;

			kbs_update_ticket_meta( $ticket->ID, '_kbs_ticket_number', $ticket_number );

			// Increment the ticket number
			$number++;

		}

		// Tickets found so upgrade them
		$step++;
		$redirect = add_query_arg( array(
			'page'        => 'kbs-upgrades',
			'kbs-upgrade' => 'upgrade_sequential_ticket_numbers',
			'step'        => $step,
			'custom'      => $number,
			'total'       => $total
		), admin_url( 'index.php' ) );
		wp_redirect( $redirect ); exit;

	} else {
		// No more tickets found, finish up
		delete_option( 'kbs_upgrade_sequential' );
		delete_option( 'kbs_doing_upgrade' );

		wp_redirect( admin_url() );
		exit;
	}

} // kbs_v11_upgrade_sequential_ticket_numbers
add_action( 'kbs_upgrade_sequential_ticket_numbers', 'kbs_v11_upgrade_sequential_ticket_numbers' );
