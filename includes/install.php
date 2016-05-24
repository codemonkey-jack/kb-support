<?php
/**
 * Install Function
 *
 * @package     KBS
 * @subpackage  Functions/Install
 * @copyright   Copyright (c) 2016, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Install
 *
 * Runs on plugin install by setting up the post types, custom taxonomies,
 * flushing rewrite rules to initiate the new 'downloads' slug and also
 * creates the plugin and populates the settings fields for those plugin
 * pages. After successful install, the user is redirected to the KBS Welcome
 * screen.
 *
 * @since	0.1
 * @global	$wpdb
 * @global	$kbs_options
 * @global	$wp_version
 * @param 	bool	$network_side	If the plugin is being network-activated
 * @return	void
 */
function kbs_install( $network_wide = false ) {
	global $wpdb;

	if ( is_multisite() && $network_wide ) {

		foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs LIMIT 100" ) as $blog_id ) {

			switch_to_blog( $blog_id );
			kbs_run_install();
			restore_current_blog();

		}

	} else {

		edd_run_install();

	}

} // kbs_install
register_activation_hook( KBS_PLUGIN_FILE, 'kbs_install' );

/**
 * Run the KBS Install process
 *
 * @since	0.1
 * @return	void
 */
function kbs_run_install() {
	global $wpdb, $kbs_options, $wp_version;

	// Setup the Custom Post Types
	kbs_setup_edd_post_types();

	// Setup the Custom Taxonomies
	kbs_setup_download_taxonomies();

	// Clear the permalinks
	flush_rewrite_rules( false );

	// Add Upgraded From Option
	$current_version = get_option( 'kbs_version' );
	if ( $current_version ) {
		update_option( 'kbs_version_upgraded_from', $current_version );
	}

	// Setup some default options
	$options = array();

	// Pull options from WP, not KBS' global
	$current_options = get_option( 'kbs_settings', array() );

	// Populate some default values
	foreach( kbs_get_registered_settings() as $tab => $sections ) {	
		foreach( $sections as $section => $settings) {

			// Check for backwards compatibility
			$tab_sections = kbs_get_settings_tab_sections( $tab );
			if( ! is_array( $tab_sections ) || ! array_key_exists( $section, $tab_sections ) ) {
				$section = 'main';
				$settings = $sections;
			}

			foreach ( $settings as $option ) {

				if( 'checkbox' == $option['type'] && ! empty( $option['std'] ) ) {
					$options[ $option['id'] ] = '1';
				}

			}
		}

	}

	$merged_options = array_merge( $kbs_options, $options );
	$kbs_options    = $merged_options;

	update_option( 'kbs_settings', $merged_options );
	update_option( 'kbs_version', KBS_VERSION );

	// Create KBS support roles
	$roles = new KBS_Roles;
	$roles->add_roles();
	$roles->add_caps();

	// Add a temporary option to note that EDD pages have been created
	set_transient( '_kbs_installed', $merged_options, 30 );

	if ( ! $current_version ) {
		require_once KBS_PLUGIN_DIR . 'includes/admin/upgrades/upgrade-functions.php';

		// When new upgrade routines are added, mark them as complete on fresh install
		$upgrade_routines = array();

		foreach ( $upgrade_routines as $upgrade ) {
			kbs_set_upgrade_complete( $upgrade );
		}
	}

	// Bail if activating from network, or bulk
	if ( is_network_admin() || isset( $_GET['activate-multi'] ) ) {
		return;
	}

	// Add the transient to redirect
	set_transient( '_kbs_activation_redirect', true, 30 );
} // kbs_run_install

/**
 * When a new Blog is created in multisite, see if KBS is network activated, and run the installer
 *
 * @since	0.1
 * @param	int		$blog_id	The Blog ID created
 * @param	int		$user_id	The User ID set as the admin
 * @param	str		$domain		The URL
 * @param	str		$path		Site Path
 * @param	int		$site_id	The Site ID
 * @param	arr		$meta		Blog Meta
 * @return	void
 */
function kbs_new_blog_created( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {

	if ( is_plugin_active_for_network( plugin_basename( KBS_PLUGIN_FILE ) ) ) {

		switch_to_blog( $blog_id );
		kbs_install();
		restore_current_blog();

	}

} // kbs_new_blog_created
add_action( 'wpmu_new_blog', 'kbs_new_blog_created', 10, 6 );

/**
 * Post-installation
 *
 * Runs just after plugin installation and exposes the
 * kbs_after_install hook.
 *
 * @since	0.1
 * @return	void
 */
function kbs_after_install() {

	if ( ! is_admin() ) {
		return;
	}

	$kbs_options     = get_transient( '_kbs_installed' );

	if ( false !== $kbs_options ) {
		// Delete the transient
		delete_transient( '_kbs_installed' );
	}


} // kbs_after_install
add_action( 'admin_init', 'kbs_after_install' );

/**
 * Install user roles on sub-sites of a network
 *
 * Roles do not get created when KBS is network activation so we need to create them during admin_init
 *
 * @since	0.1
 * @return	void
 */
function kbs_install_roles_on_network() {

	global $wp_roles;

	if( ! is_object( $wp_roles ) ) {
		return;
	}

	if( ! in_array( 'support_manager', $wp_roles->roles ) ) {

		// Create KBS support roles
		$roles = new KBS_Roles;
		$roles->add_roles();
		$roles->add_caps();

	}

} // kbs_install_roles_on_network
add_action( 'admin_init', 'kbs_install_roles_on_network' );
