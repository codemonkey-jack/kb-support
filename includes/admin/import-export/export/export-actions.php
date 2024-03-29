<?php
/**
 * Exports Actions
 *
 * These are actions related to exporting data from KBS.
 *
 * @package     KBS
 * @subpackage  Admin/Export
 * @copyright   Copyright (c) 2016, Mike Howard
 * @since       1.1
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Process the download file generated by a batch export
 *
 * @since	1.1
 * @return	void
 */
function kbs_process_batch_export_download() {

    if ( ! isset( $_REQUEST['kbs-action'] ) || 'download_batch_export' !== $_REQUEST['kbs-action'] )    {
        return;
    }

	if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'kbs-batch-export' ) ) {
		wp_die( esc_html__( 'Nonce verification failed', 'kb-support' ), esc_html__( 'Error', 'kb-support' ), array( 'response' => 403 ) );
	}

	require_once( KBS_PLUGIN_DIR . '/includes/admin/import-export/export/class-batch-export.php' );

	if( isset( $_REQUEST['class'] ) ){
		$class = sanitize_text_field( wp_unslash( $_REQUEST['class'] ) );
	
		do_action( 'kbs_batch_export_class_include', $class );

		$export = new $class;
		$export->export();

	}else{
		return;
	}
} // kbs_process_batch_export_download
add_action( 'init', 'kbs_process_batch_export_download' );

/*------------------------------
 * SETTINGS
 *----------------------------*/
/**
 * Process a settings export that generates a .json file of the shop settings
 *
 * @since       1.1
 * @return      void
 */
function kbs_tools_settings_process_export() {

    if ( ! isset( $_POST['kbs-action'] ) || 'export_settings' != $_POST['kbs-action'] )	{
		return;
	}

	if ( empty( $_POST['kbs_export_nonce'] ) )    {
		return;
    }

	if ( ! wp_verify_nonce( $_POST['kbs_export_nonce'], 'kbs_export_nonce' ) ) {
		return;
    }

	if ( ! current_user_can( 'export_ticket_reports' ) ) {
		return;
    }

	$settings = array();
	$settings = get_option( 'kbs_settings' );

	ignore_user_abort( true );

	if ( ! kbs_is_func_disabled( 'set_time_limit' ) )
		set_time_limit( 0 );

	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=' . apply_filters( 'kbs_settings_export_filename', 'kbs-settings-export-' . date( 'd-m-Y' ) ) . '.json' );
	header( "Expires: 0" );

	echo json_encode( $settings );
	exit;
} // kbs_tools_settings_process_export
add_action( 'init', 'kbs_tools_settings_process_export' );

/**
 * Add a hook allowing extensions to register a hook on the batch export process
 *
 * @since	1.1
 * @return	void
 */
function kbs_register_batch_exporters() {
	if ( is_admin() ) {
		do_action( 'kbs_register_batch_exporter' );
	}
} // kbs_register_batch_exporters
add_action( 'plugins_loaded', 'kbs_register_batch_exporters' );

/**
 * Register the customers batch exporter
 * @since	1.1
 */
function kbs_register_customers_batch_export() {
	add_action( 'kbs_batch_export_class_include', 'kbs_include_customers_batch_processer', 10, 1 );
} // kbs_register_customers_batch_export
add_action( 'kbs_register_batch_exporter', 'kbs_register_customers_batch_export', 10 );

/**
 * Loads the customers batch process if needed
 *
 * @since 	1.1
 * @param	str		$class	The class being requested to run for the batch export
 * @return	void
 */
function kbs_include_customers_batch_processer( $class ) {
	if ( 'KBS_Batch_Export_Customers' === $class ) {
		require_once( KBS_PLUGIN_DIR . '/includes/admin/import-export/export/class-batch-export-customers.php' );
	}
} // kbs_include_customers_batch_processer

/**
 * Register the events batch exporter
 * @since	1.1
 */
function kbs_register_tickets_batch_export() {
	add_action( 'kbs_batch_export_class_include', 'kbs_include_tickets_batch_processer', 10, 1 );
} // kbs_register_tickets_batch_export
add_action( 'kbs_register_batch_exporter', 'kbs_register_tickets_batch_export', 10 );

/**
 * Loads the tickets batch process if needed
 *
 * @since 	1.1
 * @param	str		$class	The class being requested to run for the batch export
 * @return	void
 */
function kbs_include_tickets_batch_processer( $class ) {
	if ( 'KBS_Batch_Export_Tickets' === $class ) {
		require_once( KBS_PLUGIN_DIR . '/includes/admin/import-export/export/class-batch-export-tickets.php' );
	}
} // kbs_include_tickets_batch_processer
