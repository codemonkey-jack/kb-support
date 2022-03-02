<?php
/**
 * Re-order kb_ticket post type
 *
 * @package     KBS
 * @subpackage  Admin
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.5.7
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

    function kbs_add_admin_reorder_scripts(){
        $order_option = get_user_meta( get_current_user_id(), '_kbs_tickets_orderby', true );

        if( $order_option && 'menu_order' ==  $order_option ){

            $suffix      = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
    
            wp_register_script( 'kbs-reorder-admin-scripts', KBS_PLUGIN_URL . '/assets/js/admin/admin-reorder-scripts' . $suffix . '.js', 'jquery', KBS_VERSION, false );
            wp_enqueue_script( 'kbs-reorder-admin-scripts' );

            wp_register_style( 'kbs-admin-reorder', KBS_PLUGIN_URL . '/assets/css/admin/kbs-admin-reorder' . $suffix . '.css', array(), KBS_VERSION );
            wp_enqueue_style( 'kbs-admin-reorder' );
        }
    }
    add_action( 'admin_enqueue_scripts', 'kbs_add_admin_reorder_scripts' );

    /**
     * Adds menu_order values to kbs_ticket post type enabeling reorder
     *
     * @since	1.5.7
     */
	function kbs_update_options() {
		global $wpdb;

		if ( ! isset( $_POST['kbs_tickets_orderby'] ) ) {
			return false;
		}

        //check if ticket ordering is set to menu order
        if( isset( $_POST['kbs_tickets_orderby'] ) && 'menu_order' != $_POST['kbs_tickets_orderby'] ){
            
            return false;
        }

        //save option so we don't re-order the tickets on every user profile update
        if( get_option( 'kbs_order_options_updated', false ) ){

            return false;
            
        }else{

            update_option( 'kbs_order_options_updated', true );
        }

        $results = $wpdb->get_results(
            "
            SELECT ID
            FROM $wpdb->posts
            WHERE post_type = 'kbs_ticket'
            ORDER BY post_date DESC
        "
        );

        foreach ( $results as $key => $result ) {
            $wpdb->update( $wpdb->posts, array( 'menu_order' => $key + 1 ), array( 'ID' => $result->ID ) );
        }

		wp_redirect( 'profile.php' );
	}
    add_action( 'admin_init', 'kbs_update_options' );

/**
 * Saves the order of kb_ticket post types
 *
 * @since	1.5.7
 */

function kbs_update_menu_order() {
    global $wpdb;



    parse_str( $_POST['order'], $data );

    if ( ! is_array( $data ) ) {
        return false;
    }

    $id_arr = array();
    foreach ( $data as $key => $values ) {
        foreach ( $values as $position => $id ) {
            $id_arr[] = $id;
        }
    }

    $menu_order_arr = array();
    foreach ( $id_arr as $key => $id ) {
        $results = $wpdb->get_results( "SELECT menu_order FROM $wpdb->posts WHERE ID = " . intval( $id ) );
        foreach ( $results as $result ) {
            $menu_order_arr[] = $result->menu_order;
        }
    }

    sort( $menu_order_arr );

    foreach ( $data as $key => $values ) {
        foreach ( $values as $position => $id ) {
            $wpdb->update( $wpdb->posts, array( 'menu_order' => $menu_order_arr[ $position ] ), array( 'ID' => intval( $id ) ) );
        }
    }

    do_action( 'kbs_update_menu_order' );

}
add_action( 'wp_ajax_kbs-update-menu-order', 'kbs_update_menu_order' );