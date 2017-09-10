<?php
/**
 * Batch Tickets Export Class
 *
 * This class handles ticket exports
 *
 * @package     KBS
 * @subpackage  Admin/Export
 * @copyright   Copyright (c) 2017, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * KBS_Batch_Export_Tickets Class
 *
 * @since	1.1
 */
class KBS_Batch_Export_Tickets extends KBS_Batch_Export {

	/**
	 * Our export type. Used for export-type specific filters/actions
	 *
	 * @var		str
	 * @since	1.1
	 */
	public $export_type = 'tickets';

	/**
	 * Set the CSV columns
	 *
	 * @access	public
	 * @since	1.1
	 * @return	arr		$cols	All the columns
	 */
	public function csv_cols() {

		$cols = array(
			'id'            => __( 'ID', 'kb-support' ),
			'ticket_number' => sprintf( __( '%s Number',   'kb-support' ), kbs_get_ticket_label_singular() ),
			'date'          => __( 'Log Date', 'kb-support' ),
			'status'        => __( 'Status', 'kb-support' ),
			'categories'    => __( 'Categories', 'kb-support' ),
			'company'       => __( 'Company', 'kb-support' ),
			'customer'      => __( 'Customer', 'kb-support' ),
			'agent'         => __( 'Agent', 'kb-support' ),
			'agents'        => __( 'Additional Agents', 'kb-support' ),
			'title'         => __( 'Title', 'kb-support' ),
			'content'       => __( 'Content', 'kb-support' ),
			'replies'       => __( 'Replies', 'kb-support' )
		);

		return $cols;
	} // csv_cols

	/**
	 * Get the Export Data
	 *
	 * @access	public
	 * @since	1.1
	 * @return	arr		$data	The data for the CSV file
	 */
	public function get_data() {

		$data = array();

		$args = array(
			'number'   => 30,
			'page'     => $this->step,
			'status'   => $this->status,
			'order'    => 'ASC',
			'orderby'  => 'date'
		);

		if ( ! empty( $this->start ) || ! empty( $this->end ) )	{
			$args['date_query'] = array(
				array(
					'after'     => date( 'Y-n-d 00:00:00', strtotime( $this->start ) ),
					'before'    => date( 'Y-n-d 23:59:59', strtotime( $this->end ) ),
					'inclusive' => true
				)
			);
		}

		$tickets = kbs_get_tickets( $args );

		if ( $tickets )	{

			foreach ( $tickets as $ticket ) {
				$ticket        = new KBS_Ticket( $ticket->ID );
				$ticket_meta   = $ticket->ticket_meta;
				$user_info     = $ticket->user_info;
				$user_id       = isset( $user_info['id'] ) && $user_info['id'] != -1 ? $user_info['id'] : $user_info['email'];
				$agent_id      = $ticket->agent_id;

				if ( is_numeric( $user_id ) ) {
					$user = get_userdata( $user_id );
				} else {
					$user = false;
				}

				if ( ! empty( $agent_id ) ) {
					$agent = get_userdata( $agent_id );
				} else {
					$agent = false;
				}

				$additional_agents = array();
				if ( ! empty( $ticket->agents ) )	{
					foreach( $ticket->agents as $additional_agent )	{
						$additional_agents[] = get_userdata( $additional_agent )->display_name;
					}
				}

				$data[] = array(
					'id'                 => $ticket->ID,
					'ticket_number'      => kbs_format_ticket_number( kbs_get_ticket_number( $ticket->ID ) ),
					'date'               => $ticket->date,
					'status'             => $ticket->status_nicename,
					'categories'         => strip_tags( get_the_term_list( $ticket->ID, 'ticket_category', '', ', ', '') ),
					'company'            => kbs_get_company_name( $ticket->company_id ),
					'customer'           => $ticket->first_name . ' ' . $ticket->last_name,
					'agent'              => $agent ? $agent->display_name : __( 'Unassigned', 'kb-support' ),
					'agents'             => implode( ', ', $additional_agents ),
					'title'              => $ticket->ticket_title,
					'content'            => $ticket->ticket_content,
					'replies'            => $ticket->get_reply_count(),
				);

			}

			$data = apply_filters( 'kbs_export_get_data', $data );
			$data = apply_filters( 'kbs_export_get_data_' . $this->export_type, $data );
	
			return $data;

		}

		return false;
	} // get_data

	/**
	 * Return the calculated completion percentage
	 *
	 * @since	1.1
	 * @return	int
	 */
	public function get_percentage_complete() {
		$status = $this->status;
		$args   = array(
			'start-date' => date( 'n/d/Y', strtotime( $this->start ) ),
			'end-date'   => date( 'n/d/Y', strtotime( $this->end ) ),
		);

		if ( 'any' == $status ) {
			$total = array_sum( (array) kbs_count_tickets( $args ) );
		} else {
			$total = kbs_count_tickets( $args )->$status;
		}

		$percentage = 100;

		if ( $total > 0 ) {
			$percentage = ( ( 30 * $this->step ) / $total ) * 100;
		}

		if ( $percentage > 100 ) {
			$percentage = 100;
		}

		return $percentage;
	} // get_percentage_complete

	/**
	 * Set the properties specific to the Events export
	 *
	 * @since	1.1
	 * @param	arr		$request	The Form Data passed into the batch processing
	 */
	public function set_properties( $request ) {
		$this->start  = isset( $request['ticket_start'] )    ? sanitize_text_field( $request['ticket_start'] ) : '';
		$this->end    = isset( $request['ticket_end']  )     ? sanitize_text_field( $request['ticket_end']  )  : '';
		$this->status = isset( $request['ticket_status'] )   ? $request['ticket_status']                       : 'any';
		$this->cat    = isset( $request['ticket_cat'] )      ? get_term( (int) $request['ticket_cat'], 'ticket_category' ) : false;
	} // set_properties

} // KBS_Batch_Export_Tickets
