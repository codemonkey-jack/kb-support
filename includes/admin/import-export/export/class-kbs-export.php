<?php
/**
 * Export Class
 *
 * This is the base class for all export methods. Each data export type (customers, tickets, etc) extend this class
 *
 * @package     KBS
 * @subpackage  Admin/Export
 * @copyright   Copyright (c) 2016, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.1
 * @taken from	Easy Digital Downloads
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * KBS_Export Class
 *
 * @since	1.1
 */
class KBS_Export {
	/**
	 * Our export type. Used for export-type specific filters/actions
	 * @var		str
	 * @since	1.1
	 */
	public $export_type = 'default';

	/**
	 * Ticket labels.
	 * @var		str
	 * @since	1.1
	 */
	public $ticket_label_single;
	public $ticket_label_plural;

	/**
	 * Constructor.
	 * @since	1.1
	 */
	public function __construct()	{
		$this->ticket_label_single = kbs_get_ticket_label_singular();
		$this->ticket_label_plural = kbs_get_ticket_label_plural();
	} // __construct

	/**
	 * Can we export?
	 *
	 * @access	public
	 * @since	1.1
	 * @return	bool	Whether we can export or not
	 */
	public function can_export() {
		return (bool) apply_filters( 'kbs_export_capability', current_user_can( 'export_ticket_reports' ) );
	} // can_export

	/**
	 * Set the export headers
	 *
	 * @access	public
	 * @since	1.1
	 * @return	void
	 */
	public function headers() {
		ignore_user_abort( true );

		if ( ! kbs_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) )	{
			set_time_limit( 0 );
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=kbs-export-' . $this->export_type . '-' . date( 'd-m-Y' ) . '.csv' );
		header( "Expires: 0" );
	} // headers

	/**
	 * Set the CSV columns
	 *
	 * @access	public
	 * @since	1.1
	 * @return	arr		$cols	All the columns
	 */
	public function csv_cols() {
		$cols = array(
			'id'   => esc_html__( 'ID',   'kb-support' ),
			'date' => esc_html__( 'Date', 'kb-support' )
		);
		return $cols;
	} // csv_cols

	/**
	 * Retrieve the CSV columns
	 *
	 * @access	public
	 * @since	1.1
	 * @return	arr		$cols	Array of the columns
	 */
	public function get_csv_cols() {
		$cols = $this->csv_cols();
		return apply_filters( 'kbs_export_csv_cols_' . $this->export_type, $cols );
	} // get_csv_cols

	/**
	 * Output the CSV columns
	 *
	 * @access	public
	 * @since	1.1
	 * @uses	KBS_Export::get_csv_cols()
	 * @return	void
	 */
	public function csv_cols_out() {
		$cols = $this->get_csv_cols();
		$i = 1;
		foreach( $cols as $col_id => $column ) {
			echo '"' . esc_html( addslashes( $column ) ) . '"';
			echo $i == count( $cols ) ? '' : ',';
			$i++;
		}
		echo "\r\n";
	} // csv_cols_out

	/**
	 * Get the data being exported
	 *
	 * @access	public
	 * @since	1.1
	 * @return	arr		$data	Data for Export
	 */
	public function get_data() {
		// Just a sample data array
		$data = array(
			0 => array(
				'id'   => '',
				'data' => date( 'F j, Y' )
			),
			1 => array(
				'id'   => '',
				'data' => date( 'F j, Y' )
			)
		);

		$data = apply_filters( 'kbs_export_get_data', $data );
		$data = apply_filters( 'kbs_export_get_data_' . $this->export_type, $data );

		return $data;
	} // get_data

	/**
	 * Output the CSV rows
	 *
	 * @access	public
	 * @since	1.1
	 * @return	void
	 */
	public function csv_rows_out() {
		$data = $this->get_data();

		$cols = $this->get_csv_cols();

		// Output each row
		foreach ( $data as $row ) {
			$i = 1;
			foreach ( $row as $col_id => $column ) {
				// Make sure the column is valid
				if ( array_key_exists( $col_id, $cols ) ) {
					echo '"' . esc_html( addslashes( $column ) ) . '"';
					echo $i == count( $cols ) ? '' : ',';
					$i++;
				}
			}
			echo "\r\n";
		}
	} // csv_rows_out

	/**
	 * Perform the export
	 *
	 * @access	public
	 * @since	1.1
	 * @uses	KBS_Export::can_export()
	 * @uses	KBS_Export::headers()
	 * @uses	KBS_Export::csv_cols_out()
	 * @uses	KBS_Export::csv_rows_out()
	 * @return void
	 */
	public function export() {
		if ( ! $this->can_export() )
			wp_die( esc_html__( 'You do not have permission to export data.', 'kb-support' ), esc_html__( 'Error', 'kb-support' ), array( 'response' => 403 ) );

		// Set headers
		$this->headers();

		// Output CSV columns (headers)
		$this->csv_cols_out();

		// Output CSV rows
		$this->csv_rows_out();

		die();
	} // export

} // KBS_Export
