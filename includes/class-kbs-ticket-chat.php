<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * KBS_Stats Class
 *
 * Base class for other stats classes
 *
 * Primarily for setting up dates and ranges
 *
 * @since   1.0
 */
class KBS_Ticket_Chat {
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	} // __construct

	public function init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_block_assets' ), 1 );
	}

	public function enqueue_block_assets() {
		$screen = get_current_screen();

		if ( $screen->id == 'kbs_ticket' ) {
			wp_enqueue_script( 'kb-ticket-chat', KBS_PLUGIN_URL . 'dist/bundle.js', array( 'wp-i18n', 'wp-element', 'wp-editor', 'wp-blocks', 'wp-components', 'wp-api', 'wp-data', 'wp-dom-ready', 'wp-edit-post', 'wp-hooks' ), MODULA_LITE_VERSION, true );

			wp_enqueue_style( 'kb-ticket-chat-style', KBS_PLUGIN_URL . 'dist/style.css' );

			wp_localize_script(
				'kb-ticket-chat',
				'kbApiSettings',
				array(
					'root'  => esc_url_raw( rest_url() ),
					'nonce' => wp_create_nonce( 'kb_wp_rest' ),
				)
			);
		}
	}

}

