<?php
/**
 * Admin Plugin
 *
 * @package     KBS
 * @subpackage  Admin/Functions
 * @copyright   Copyright (c) 2017, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Plugins row action links
 *
 * @since	1.0
 * @param	arr		$links	Defined action links
 * @param	str		$file	Plugin file path and name being processed
 * @return	srr		Filtered action links
 */
function kbs_plugin_action_links( $links, $file )	{

	$settings_link = '<a href="' . admin_url( 'edit.php?post_type=kbs_ticket&page=kbs-settings' ) . '">' . esc_html__( 'Settings', 'kb-support' ) . '</a>';

	if ( $file == 'kb-support/kb-support.php' )	{
		array_unshift( $links, $settings_link );
	}

	return $links;

} // kbs_plugin_action_links
add_filter( 'plugin_action_links', 'kbs_plugin_action_links', 10, 2 );

/**
 * Plugin row meta links
 *
 * @since	1.0
 * @param	arr		$input	Defined meta links
 * @param	str		$file	Plugin file path and name being processed
 * @return	arr		Filtered meta links
 */
function kbs_plugin_row_meta( $input, $file )	{

	if ( $file != 'kb-support/kb-support.php' )	{
		return $input;
	}

	$links = array(
		'<a href="' . esc_url( 'https://kb-support.com/support/' ) . '" target="_blank">' . esc_html__( 'Documentation', 'kb-support' ) . '</a>',
		'<a href="' . esc_url( 'https://kb-support.com/extensions/' ) . '" target="_blank">' . esc_html__( 'Extensions', 'kb-support' ) . '</a>'
	);

	$input = array_merge( $input, $links );

	return $input;

} // kbs_plugin_row_meta
add_filter( 'plugin_row_meta', 'kbs_plugin_row_meta', 10, 2 );

/**
 * Adds rate us text to admin footer when KB Support admin pages are viewed.
 *
 * @since	1.0
 * @param	str		$footer_text	The footer text to output
 * @return	str		Filtered footer text for output
 */
function kbs_admin_footer_rate_us( $footer_text )	{
	global $typenow;

    $disable = kbs_get_option( 'remove_rating' );

	if ( ! $disable && ( kbs_is_registered_post_type( $typenow ) ) )	{
        $rate_text = sprintf(
            __( 'Thank you for using <a href="%1$s" target="_blank">KB Support</a>! Please <a href="%2$s" target="_blank">rate us on WordPress.org</a>', 'kb-support' ),
            'https://kb-support.com',
			'https://wordpress.org/support/plugin/kb-support/reviews/?rate=5#new-post'
		);

		$footer_text = str_replace( '</span>', '', $footer_text ) . ' | ' . $rate_text . '</span>';
	}

	return $footer_text;
} // kbs_admin_footer_rate_us
add_filter( 'admin_footer_text', 'kbs_admin_footer_rate_us' );
