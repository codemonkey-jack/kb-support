<?php

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit;

/**
 * Uninstall KB Support.
 *
 * Removes all settings, custom posts & taxonomies, pages, roles & capabilities.
 *
 * @package     KBS
 * @subpackage  Uninstall
 * @copyright   Copyright (c) 2017, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 *
 */

/**
 * Determine whether to run multisite uninstall or standard.
 *
 * @since   1.2.2
 */
if ( is_multisite() )   {
    global $wpdb;

    foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" ) as $blog_id )  {
        switch_to_blog( $blog_id );
        kbs_uninstall();
        restore_current_blog();
    }

} else  {
    kbs_uninstall();
}

/**
 * The main uninstallation function.
 *
 * The uninstall will only execute if the user has explicity
 * enabled the option for data to be removed.
 *
 * @since   1.2.2
 */
function kbs_uninstall()    {
    // Call the KBS main class file
    include_once( 'kb-support.php' );

    global $wpdb, $wp_roles;

    if ( kbs_get_option( 'remove_on_uninstall' ) )	{

        // Delete the Custom Post Types
        $kbs_taxonomies = array( 'ticket_category', 'ticket_tag', 'article_category', 'article_tag', 'kbs_log_type', 'ticket_source', 'department' );
        $kbs_post_types = array( 'kbs_ticket', 'kbs_ticket_reply', 'kbs_article', 'kbs_form', 'kbs_form_field', 'kbs_company', 'kbs_log' );

        foreach ( $kbs_post_types as $post_type ) {

            $kbs_taxonomies = array_merge( $kbs_taxonomies, get_object_taxonomies( $post_type ) );
            $items = get_posts( array(
                'post_type'   => $post_type,
                'post_status' => 'any',
                'numberposts' => -1,
                'fields'      => 'ids'
            ) );

            if ( $items ) {
                foreach ( $items as $item )	{
                    wp_delete_post( $item, true );
                }
            }
        }

        // Delete Terms & Taxonomies
        foreach ( array_unique( array_filter( $kbs_taxonomies ) ) as $taxonomy )	{

            $terms = $wpdb->get_results( $wpdb->prepare(
                "SELECT t.*, tt.*
                FROM $wpdb->terms
                AS t
                INNER JOIN $wpdb->term_taxonomy
                AS tt
                ON t.term_id = tt.term_id
                WHERE tt.taxonomy IN ('%s')
                ORDER BY t.name ASC", $taxonomy
            ) );

            // Delete Terms.
            if ( $terms ) {
                foreach ( $terms as $term ) {
                    $wpdb->delete( $wpdb->term_relationships, array( 'term_taxonomy_id' => $term->term_taxonomy_id ) );
                    $wpdb->delete( $wpdb->term_taxonomy, array( 'term_taxonomy_id' => $term->term_taxonomy_id ) );
                    $wpdb->delete( $wpdb->terms, array( 'term_id' => $term->term_id ) );
                }
            }

            // Delete Taxonomies.
            $wpdb->delete( $wpdb->term_taxonomy, array( 'taxonomy' => $taxonomy ), array( '%s' ) );
        }

        // Delete Plugin Pages
        $kbs_pages = array( 'submission_page', 'tickets_page' );
        foreach ( $kbs_pages as $kbs_page ) {

            $page = kbs_get_option( $kbs_page, false );

            if ( $page )	{
                wp_delete_post( $page, true );
            }

        }

        // Delete all Plugin Options
        delete_option( 'kbs_last_ticket_number' );
		delete_option( 'kbs_completed_upgrades' );
        delete_option( '_kbs_table_check' );
        delete_option( 'kbs_settings' );
        delete_option( 'kbs_version' );

        // Delete Custom Capabilities
        KBS()->roles->remove_caps();

        // Delete Custom Roles
        $kbs_roles = array( 'support_manager', 'support_agent', 'support_customer' );
        foreach ( $kbs_roles as $role ) {
            remove_role( $role );
        }

        // Remove all database tables
        $wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "kbs_customers" );
        $wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "kbs_customermeta" );

        // Remove any transients and options we've left behind
        $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_kbs\_%'" );
        $wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '\_transient\_timeout\_kbs\_%'" );

        $kbs_all_options = array(
            'kbs_default_submission_form_created',
            'kbs_version_upgraded_from',
            $wpdb->prefix . 'kbs_customers_db_version',
            $wpdb->prefix . 'kbs_customermeta_db_version',
            'kbs_install_version',
            'kbs_installed'
        );

        foreach( $kbs_all_options as $kbs_all_option )	{
            delete_option( $kbs_all_option );
        }

    }
} // kbs_uninstall
