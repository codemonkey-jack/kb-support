<?php
/**
 * Admin Pages
 *
 * @package     KBS
 * @subpackage  Admin/Pages
 * @copyright   Copyright (c) 2017, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Creates the admin submenu pages under the Tickets menu and assigns their
 * links to global variables
 *
 * @since	0.1
 * @return	void
 */
function kbs_add_options_link() {
	$customer_view_role  = apply_filters( 'kbs_view_customers_role', 'view_ticket_reports' );

    do_action( 'kbs_menu_top' );

	$customer_view_role  = kbs_get_view_customers_required_capability();

	add_submenu_page(
        'edit.php?post_type=kbs_ticket',
        __( 'Companies', 'kb-support' ),
        __( 'Companies', 'kb-support' ),
        $customer_view_role,
        'edit.php?post_type=kbs_company'
    );

    do_action( 'kbs_menu_after_companies' );

	add_submenu_page(
        'edit.php?post_type=kbs_ticket',
        __( 'Customers', 'kb-support' ),
        __( 'Customers', 'kb-support' ),
        $customer_view_role,
        'kbs-customers',
        'kbs_customers_page'
    );

    do_action( 'kbs_menu_after_customers' );

	add_submenu_page(
        null,
        __( 'KBS Upgrades', 'kb-support' ),
        __( 'KBS Upgrades', 'kb-support' ),
        'manage_ticket_settings',
        'kbs-upgrades',
        'kbs_upgrades_screen'
    );

    do_action( 'kbs_menu' );
} // kbs_add_options_link
add_action( 'admin_menu', 'kbs_add_options_link', 20 );

/**
 * Add a licensing link to the menu.
 *
 * @since   1.4.6
 * @return  void
 */
function kbs_add_licensing_menu_link()  {
    global $submenu;

    $submenu['edit.php?post_type=kbs_ticket'][900] = array(
        __( 'Manage Extensions', 'kb-support' ),
        'manage_ticket_settings',
        add_query_arg( array(
            'post_type' => 'kbs_ticket',
            'page'      => 'kbs-settings',
            'tab'       => 'licenses'
        ), admin_url( 'edit.php' ) )
    );
} // kbs_add_licensing_menu_link
add_action( 'admin_menu', 'kbs_add_licensing_menu_link', 900 );

/**
 * Display the open ticket count next to the tickets menu item.
 *
 * @since	1.2.5
 * @return	void
 */
function kbs_menu_open_ticket_count()	{
	if ( ! kbs_get_option( 'show_count', false ) )	{
		return;
	}

	global $menu, $current_user;

	if ( kbs_is_ticket_admin() || ! kbs_get_option( 'restrict_agent_view' ) )	{
		$count = kbs_get_open_ticket_count( 'open' );
	} else	{
		$agent = new KBS_Agent( $current_user->ID );

		if ( $agent )	{
			$count = $agent->open_tickets;
		}
	}

	if ( empty( $count ) )	{
		return;
	}

	foreach ( $menu as $key => $value ) {

		if ( 'edit.php?post_type=kbs_ticket' == $menu[ $key ][2] )	{

			$menu[ $key ][0] .= sprintf(
				' <span class="update-plugins count-%d"><span class="pending-count">%d</span></span>',
				absint( $count ),
				number_format_i18n( $count )
			);
		}
	}

} // kbs_menu_open_ticket_count
add_action( 'admin_menu', 'kbs_menu_open_ticket_count', 21 );

/**
 *  Determines whether the current admin page is a specific KBS admin page.
 *
 *  Only works after the `wp_loaded` hook, & most effective
 *  starting on `admin_menu` hook. Failure to pass in $view will match all views of $main_page.
 *  Failure to pass in $main_page will return true if on any KBS page
 *
 *  @since	0.1
 *
 *  @param	str		$page	Main page's slug
 *  @param	str		$view	Page view ( ex: `edit` or `delete` )
 *  @return	bool	True if KBS admin page we're looking for or a KBS page or if $page is empty, any KBS page
 */
function kbs_is_admin_page( $passed_page = '', $passed_view = '' ) {

	global $pagenow, $typenow;

	$found      = false;
	$post_type  = isset( $_GET['post_type'] )  ? strtolower( $_GET['post_type'] )  : false;
	$action     = isset( $_GET['action'] )     ? strtolower( $_GET['action'] )     : false;
	$taxonomy   = isset( $_GET['taxonomy'] )   ? strtolower( $_GET['taxonomy'] )   : false;
	$page       = isset( $_GET['page'] )       ? strtolower( $_GET['page'] )       : false;
	$view       = isset( $_GET['view'] )       ? strtolower( $_GET['view'] )       : false;
	$kbs_action = isset( $_GET['kbs-action'] ) ? strtolower( $_GET['kbs-action'] ) : false;
	$tab        = isset( $_GET['tab'] )        ? strtolower( $_GET['tab'] )        : false;

	switch ( $passed_page ) {
		case 'kbs_ticket':
			switch ( $passed_view ) {
				case 'list-table':
					if ( ( 'kbs_ticket' == $typenow || 'kbs_ticket' === $post_type ) && $pagenow == 'edit.php' ) {
						$found = true;
					}
					break;
				case 'edit':
					if ( ( 'kbs_ticket' == $typenow || 'kbs_ticket' === $post_type ) && $pagenow == 'post.php' ) {
						$found = true;
					}
					break;
				case 'new':
					if ( ( 'kbs_ticket' == $typenow || 'kbs_ticket' === $post_type ) && $pagenow == 'post-new.php' ) {
						$found = true;
					}
					break;
				default:
					if ( ( 'kbs_ticket' == $typenow || 'kbs_ticket' === $post_type ) || 'kbs_ticket' === $post_type || ( 'post-new.php' == $pagenow && 'kbs_ticket' === $post_type ) || ( 'edit.php' == $pagenow && 'kbs_ticket' === $post_type ) ) {
						$found = true;
					}
					break;
			}
			break;
		case 'categories':
			switch ( $passed_view ) {
				case 'list-table':
				case 'new':
					if ( ( 'kbs_ticket' == $typenow || 'kbs_ticket' === $post_type ) && $pagenow == 'edit-tags.php' && 'edit' !== $action && 'ticket_category' === $taxonomy ) {
						$found = true;
					} elseif ( ( 'kbs_ticket' == $typenow || 'kbs_ticket' === $post_type ) && $pagenow == 'edit-tags.php' && 'edit' !== $action && 'department' === $taxonomy ) {
						$found = true;
					}
					break;
				case 'edit':
					if ( ( 'kbs_ticket' == $typenow || 'kbs_ticket' === $post_type ) && $pagenow == 'edit-tags.php' && 'edit' === $action && 'ticket_category' === $taxonomy ) {
						$found = true;
					} elseif ( ( 'kbs_ticket' == $typenow || 'kbs_ticket' === $post_type ) && $pagenow == 'edit-tags.php' && 'edit' === $action && 'department' === $taxonomy ) {
						$found = true;
					}
					break;
				default:
					if ( ( 'kbs_ticket' == $typenow || 'kbs_ticket' === $post_type ) && $pagenow == 'edit-tags.php' && 'ticket_category' === $taxonomy ) {
						$found = true;
					} elseif ( ( 'kbs_ticket' == $typenow || 'kbs_ticket' === $post_type ) && $pagenow == 'edit-tags.php' && 'department' === $taxonomy ) {
						$found = true;
					}
					break;
			}
			break;
		case 'tags':
			switch ( $passed_view ) {
				case 'list-table':
				case 'new':
					if ( ( 'kbs_ticket' == $typenow || 'kbs_ticket' === $post_type ) && $pagenow == 'edit-tags.php' && 'edit' !== $action && 'ticket_tax' === $taxonomy ) {
						$found = true;
					}
					break;
				case 'edit':
					if ( ( 'kbs_ticket' == $typenow || 'kbs_ticket' === $post_type ) && $pagenow == 'edit-tags.php' && 'edit' === $action && 'ticket_tax' === $taxonomy ) {
						$found = true;
					}
					break;
				default:
					if ( ( 'kbs_ticket' == $typenow || 'kbs_ticket' === $post_type ) && $pagenow == 'edit-tags.php' && 'ticket_tax' === $taxonomy ) {
						$found = true;
					}
					break;
			}
			break;
		case 'kb':
			switch ( $passed_view ) {
				case 'list-table':
					if ( ( 'kb' == $typenow || 'kb' === $post_type ) && $pagenow == 'edit.php' ) {
						$found = true;
					}
					break;
				case 'edit':
					if ( ( 'kb' == $typenow || 'kb' === $post_type ) && $pagenow == 'post.php' ) {
						$found = true;
					}
					break;
				case 'new':
					if ( ( 'kb' == $typenow || 'kb' === $post_type ) && $pagenow == 'post-new.php' ) {
						$found = true;
					}
					break;
				default:
					if ( ( 'kb' == $typenow || 'kb' === $post_type ) || 'kb' === $post_type || ( 'post-new.php' == $pagenow && 'kb' === $post_type ) ) {
						$found = true;
					}
					break;
			}
			break;
		case 'kbs_form':
			switch ( $passed_view ) {
				case 'list-table':
					if ( ( 'kbs_form' == $typenow || 'kbs_form' === $post_type ) && $pagenow == 'edit.php' ) {
						$found = true;
					}
					break;
				case 'edit':
					if ( ( 'kbs_form' == $typenow || 'kbs_form' === $post_type ) && $pagenow == 'post.php' ) {
						$found = true;
					}
					break;
				case 'new':
					if ( ( 'kbs_form' == $typenow || 'kbs_form' === $post_type ) && $pagenow == 'post-new.php' ) {
						$found = true;
					}
					break;
				default:
					if ( ( 'kbs_form' == $typenow || 'kbs_form' === $post_type ) || 'kbs_form' === $post_type || ( 'post-new.php' == $pagenow && 'kbs_ticket' === $post_type ) ) {
						$found = true;
					}
					break;
			}
			break;
		case 'settings':
			switch ( $passed_view ) {
				case 'general':
					if ( ( 'kbs_ticket' == $typenow || 'kbs_ticket' === $post_type ) && $pagenow == 'edit.php' && 'kbs-settings' === $page && ( 'general' === $tab || false === $tab ) ) {
						$found = true;
					}
					break;
				case 'sla':
					if ( ( 'kbs_ticket' == $typenow || 'kbs_ticket' === $post_type ) && $pagenow == 'edit.php' && 'kbs-settings' === $page && ( 'sla' === $tab || false === $tab ) ) {
						$found = true;
					}
					break;
				case 'emails':
					if ( ( 'kbs_ticket' == $typenow || 'kbs_ticket' === $post_type ) && $pagenow == 'edit.php' && 'kbs-settings' === $page && 'emails' === $tab ) {
						$found = true;
					}
					break;
				default:
					if ( ( 'kbs_ticket' == $typenow || 'kbs_ticket' === $post_type ) && $pagenow == 'edit.php' && 'kbs-settings' === $page ) {
						$found = true;
					}
					break;
			}
			break;
		default:
			global $kbs_settings_page;

			$admin_pages = apply_filters( 'kbs_admin_pages', array( $kbs_settings_page ) );
            $post_types  = array( 'kbs_ticket', 'article', 'kbs_form', 'kbs_form_field', 'kbs_company' );

			if ( in_array( $typenow, $post_types ) || 'index.php' == $pagenow || 'post-new.php' == $pagenow || 'post.php' == $pagenow ) {
				$found = true;
				if ( 'kbs-upgrades' === $page ) {
					$found = false;
				}
			} elseif ( in_array( $pagenow, $admin_pages ) ) {
				$found = true;
			}
			break;
	}

	return (bool) apply_filters( 'kbs_is_admin_page', $found, $page, $view, $passed_page, $passed_view );
} // kbs_is_admin_page
