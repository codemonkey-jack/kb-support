<?php
/**
 * Install Function
 *
 * @package     KBS
 * @subpackage  Functions/Install
 * @copyright   Copyright (c) 2017, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Install
 *
 * Runs on plugin install by setting up the post types, custom taxonomies,
 * flushing rewrite rules to initiate the new 'downloads' slug and also
 * creates the plugin and populates the settings fields for those plugin
 * pages. After successful install, the user is redirected to the KBS Welcome
 * screen.
 *
 * @since	1.0
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

		kbs_run_install();

	}

} // kbs_install
register_activation_hook( KBS_PLUGIN_FILE, 'kbs_install' );

/**
 * Run the KBS Install process
 *
 * @since	1.0
 * @return	void
 */
function kbs_run_install() {
	global $wpdb, $kbs_options, $wp_version;

	// Bail if already installed
	$already_installed = get_option( 'kbs_installed' );
	if ( $already_installed )	{
		return;
	}

	// Setup the Custom Post Types
	kbs_setup_post_types();

	// Setup the Custom Taxonomies
	kbs_setup_kbs_ticket_category_taxonomy();
	kbs_setup_kbs_ticket_tag_taxonomy();
    kbs_setup_kbs_ticket_source_taxonomy();
	kbs_setup_kbs_ticket_department_taxonomy();

	// Clear the permalinks
	flush_rewrite_rules( false );

	// Add Upgraded From Option
	$current_version = get_option( 'kbs_version' );
	if ( $current_version ) {
		update_option( 'kbs_version_upgraded_from', $current_version );
	}

    // Add Ticket source default terms
    $sources = array(
        array(
            'slug' => 'kbs-website',
            'name' => __( 'Website', 'kb-support' ),
            'desc' => sprintf( __( '%s received via website', 'kb-support' ), kbs_get_ticket_label_plural() )
        ),
        array(
            'slug' => 'kbs-email',
            'name' => __( 'Email', 'kb-support' ),
            'desc' => sprintf( __( '%s received via email', 'kb-support' ), kbs_get_ticket_label_plural() )
        ),
		array(
            'slug' => 'kbs-rest',
            'name' => __( 'REST API', 'kb-support' ),
            'desc' => sprintf( __( '%s received via REST API', 'kb-support' ), kbs_get_ticket_label_plural() )
        ),
        array(
            'slug' => 'kbs-telephone',
            'name' => __( 'Telephone', 'kb-support' ),
            'desc' => sprintf( __( '%s received via telephone', 'kb-support' ), kbs_get_ticket_label_plural() )
        ),
        array(
            'slug' => 'kbs-other',
            'name' => __( 'Other', 'kb-support' ),
            'desc' => sprintf( __( '%s received via another means', 'kb-support' ), kbs_get_ticket_label_plural() )
        )
    );

    $sources = apply_filters( 'kbs_ticket_log_sources', $sources );

    foreach( $sources as $source )  {
        $name = trim( sanitize_text_field( $source['name'] ) );
        $desc = sanitize_text_field( $source['desc'] );
        $slug = sanitize_text_field( $source['slug'] );

        $insert = wp_insert_term(
            $name,
            'ticket_source',
            array(
                'description' => $desc,
                'slug'        => $slug
            )
        );
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
			if ( ! is_array( $tab_sections ) || ! array_key_exists( $section, $tab_sections ) ) {
				$section = 'main';
				$settings = $sections;
			}

			foreach ( $settings as $option ) {
				if ( ! empty( $option['std'] ) ) {
					if ( 'checkbox' == $option['type'] )	{
						$options[ $option['id'] ] = '1';
					} else	{
                        if ( 'article_excerpt_length' == $option['id'] )    {
                            $options[ $option['id'] ] = '55';
                        } else  {
						  $options[ $option['id'] ] = $option['std'];
                        }
					}
					
				}
			}
		}

	}

	// Create ticket page if it has not been created
	if ( ! array_key_exists( 'tickets_page', $current_options ) )	{
		// Tickets page
		$tickets_page = wp_insert_post(
			array(
				'post_title'     => __( 'Ticket Manager', 'kb-support' ),
				'post_content'   => '[kbs_tickets]',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed'
			)
		);

		// Store the page ID in KBS options
		if ( ! empty( $tickets_page ) )	{
			$options['tickets_page']  = $tickets_page;
		}

	}

	$default_submission_form = get_option( 'kbs_default_submission_form_created', false );

	// Create default submission form if needed
	if ( ! $default_submission_form )	{

		$submission_form_id = wp_insert_post( array(
			'post_type'    => 'kbs_form',
			'post_status'  => 'publish',
			'post_title'   => __( 'Ticket Submissions', 'kb-support' ),
			'post_content' => '',
			'post_author'  => 1
		) );

	}

	// Create ticket page if it has not been created
	if ( ! empty( $submission_form_id ) )	{

		kbs_add_default_fields_to_form( $submission_form_id );

		$form = new KBS_Form( $submission_form_id );

		// Tells us the default submission form was created so we don't create another
		add_option( 'kbs_default_submission_form_created', $submission_form_id, '', 'no' );

		// Add the form submission page
		$submission_page = wp_insert_post( array(
			'post_title'     => sprintf( __( 'Log a Support %s', 'kb-support' ), kbs_get_ticket_label_singular() ),
			'post_content'   => $form->get_shortcode(),
			'post_status'    => 'publish',
			'post_author'    => 1,
			'post_type'      => 'page',
			'comment_status' => 'closed'
		) );

		// Store the page ID in KBS options
		if ( ! empty( $submission_page ) )	{
			$options['submission_page']  = $submission_page;
		}

	}

	$merged_options = array_merge( $kbs_options, $options );
	$kbs_options    = $merged_options;

	update_option( 'kbs_settings', $merged_options );
	update_option( 'kbs_version', KBS_VERSION );
	add_option( 'kbs_install_version', KBS_VERSION, '', 'no' );
	add_option( 'kbs_installed', current_time( 'mysql' ), '', 'no' );

	// Create KBS support roles
	$roles = new KBS_Roles;
	$roles->add_roles();
	$roles->add_caps();

	// Create the customer databases
	@KBS()->customers->create_table();
	@KBS()->customer_meta->create_table();

	// Add a temporary option to note that KBS pages have been created
	set_transient( '_kbs_installed', $merged_options, 30 );

	if ( ! $current_version ) {
		require_once KBS_PLUGIN_DIR . 'includes/admin/upgrades/upgrade-functions.php';

		// When new upgrade routines are added, mark them as complete on fresh install
		$upgrade_routines = array(
            'upgrade_ticket_sources',
            'upgrade_ticket_departments',
			'upgrade_article_monthly_count'
            
        );

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
 * When a new Blog is created in multisite, see if KBS is network activated, and run the installer.
 *
 * @since	1.0
 * @param	object	$site	The WP_Site object for the new site
 * @return	void
 */
function kbs_new_blog_created( $site ) {
	if ( is_plugin_active_for_network( plugin_basename( KBS_PLUGIN_FILE ) ) ) {
		switch_to_blog( $site->blog_id );
		kbs_install();
		restore_current_blog();
	}
} // kbs_new_blog_created
add_action( 'wp_insert_site', 'kbs_new_blog_created' );

/**
 * Drop our custom tables when an mu site is deleted
 *
 * @since   1.2.2
 * @param   array   $tables  The tables to drop
 * @param   int     $blog_id The Blog ID being deleted
 * @return  array   The tables to drop
 */
function kbs_wpmu_drop_tables( $tables, $blog_id ) {

	switch_to_blog( $blog_id );
	$customers_db     = new KBS_DB_Customers();
	$customer_meta_db = new KBS_DB_Customer_Meta();

	if ( $customers_db->installed() ) {
		$tables[] = $customers_db->table_name;
		$tables[] = $customer_meta_db->table_name;
	}

	restore_current_blog();

	return $tables;

} // kbs_wpmu_drop_tables
add_filter( 'wpmu_drop_tables', 'kbs_wpmu_drop_tables', 10, 2 );

/**
 * Post-installation
 *
 * Runs just after plugin installation and exposes the
 * kbs_after_install hook.
 *
 * @since	1.0
 * @return	void
 */
function kbs_after_install() {

	if ( ! is_admin() ) {
		return;
	}

	$kbs_options     = get_transient( '_kbs_installed' );
    $kbs_table_check = get_option( '_kbs_table_check', false );

    if ( false === $kbs_table_check || current_time( 'timestamp' ) > $kbs_table_check ) {

        if ( ! @KBS()->customer_meta->installed() ) {

			// Create the customer meta database (this ensures it creates it on multisite instances where it is network activated)
			@KBS()->customer_meta->create_table();

		}

		if ( ! @KBS()->customers->installed() ) {
			// Create the customers database (this ensures it creates it on multisite instances where it is network activated)
			@KBS()->customers->create_table();
			@KBS()->customer_meta->create_table();

			do_action( 'kbs_after_install', $kbs_options );
		}

		update_option( '_kbs_table_check', ( current_time( 'timestamp' ) + WEEK_IN_SECONDS ) );

    }

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
 * @since	1.0
 * @return	void
 */
function kbs_install_roles_on_network() {

	if( ! is_multisite() ) {
		return;
	}

	global $wp_roles;

	if ( ! is_object( $wp_roles ) ) {
		return;
	}

	if ( empty( $wp_roles->roles ) || ! array_key_exists( 'support_manager', $wp_roles->roles ) ) {

		// Create KBS support roles
		$roles = new KBS_Roles;
		$roles->add_roles();
		$roles->add_caps();

	}

} // kbs_install_roles_on_network
add_action( 'admin_init', 'kbs_install_roles_on_network' );

/**
 * Deactivate
 *
 * Runs on plugin deactivation to remove scheduled tasks.
 *
 * @since	1.0
 * @return	void
 */
function kbs_deactivate_plugin()	{
	$kbs_cron = new KBS_Cron;
	$kbs_cron->unschedule_events();
} // kbs_deactivate_plugin
register_deactivation_hook( KBS_PLUGIN_FILE, 'kbs_deactivate_plugin' );
