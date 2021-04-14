<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class KBS_Admin_Ticket_Helper {

	/**
	 * Holds the class object.
	 *
	 * @since 1.6.0
	 *
	 * @var object
	 */
	public static $instance;

	public function __construct() {

		add_action( 'wp_ajax_kbs_ajax_update_ticket_status', array( $this, 'actionbar_ticket_status' ) );
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The KBS_Admin_Ticket_Helper object.
	 * @since 1.6.0
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof KBS_Admin_Ticket_Helper ) ) {
			self::$instance = new KBS_Admin_Ticket_Helper();
		}

		return self::$instance;

	}

	public function actionbar_ticket_status() {

		$data = $_POST;

		wp_verify_nonce( $data['nonce'], 'set_status_nonce_' . $data['ticket_id'] );

		if ( empty( $data ) || ! isset( $data['ticket_id'] ) || ! isset( $data['nonce'] ) ) {
			return false;
		}

		if ( 'kbs_ticket' != get_post_type( $data['ticket_id'] ) ) {
			return false;
		}

		$update_fields = array(
			'ID'          => absint( $data['ticket_id'] ),
			'post_status' => sanitize_text_field( $data['status'] ),
			'edit_date'   => current_time( 'mysql' )
		);

		$updated = wp_update_post( $update_fields );

		switch ( $data['status'] ) {
			case 'closed':
				add_post_meta( $data['ticket_id'], '_kbs_ticket_closed_date', current_time( 'mysql' ), true );
				$closed_by = apply_filters( 'kbs_ticket_closed_by', get_current_user_id(), $this );
				add_post_meta( $$data['ticket_id'], '_kbs_ticket_closed_by', $closed_by, true );
				break;
			default:
				do_action( 'kbs_ajax_ticket_status_' . $data['status'], $data );
		}

		update_post_meta( $data['ticket_id'], '_kbs_ticket_last_status_change', current_time( 'timestamp' ) );
		echo 'made it';
		die();
	}
}

KBS_Admin_Ticket_Helper::get_instance();

