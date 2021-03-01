<?php
/**
 * Scripts
 *
 * @package     KBS
 * @subpackage  Functions
 * @copyright   Copyright (c) 2017, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Load Scripts
 *
 * Enqueues the required scripts.
 *
 * @since	1.0
 * @return	void
 */
function kbs_load_scripts() {
	global $post;

	$js_dir = KBS_PLUGIN_URL . 'assets/js/';

	// Use minified libraries if SCRIPT_DEBUG is turned off
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	// Load AJAX scripts
	wp_register_script( 'kbs-ajax', $js_dir . 'kbs-ajax' . $suffix . '.js', array( 'jquery' ), KBS_VERSION );
	wp_enqueue_script( 'kbs-ajax' );

	$is_submission = kbs_is_submission_form();
	$needs_bs4     = false;
    $user_id       = get_current_user_id();
    $shortcodes    = array( 'kbs_tickets' );

    if ( ! empty( $post ) && ! empty( $post->ID ) ) {
        $needs_bs4 = $post->ID == kbs_get_option( 'tickets_page' );
    }

    if ( ! $needs_bs4 && ! empty( $post ) && is_a( $post, 'WP_Post' ) )	{
        foreach( $shortcodes as $shortcode )    {
            if ( $needs_bs4 )   {
                break;
            }

            $needs_bs4 = has_shortcode( $post->post_content, $shortcode );
        }
    }

	$needs_bs4 = apply_filters( 'kbs_scripts_need_bs4', $needs_bs4 );

	wp_localize_script( 'kbs-ajax', 'kbs_scripts', apply_filters( 'kbs_ajax_script_vars', array(
        'ajax_loader'           => KBS_PLUGIN_URL . 'assets/images/loading.gif',
		'ajaxurl'               => kbs_get_ajax_url(),
        'honeypot_fail'         => __( 'Honeypot validation error', 'kb-support' ),
        'is_submission'         => $is_submission,
		'max_files'             => kbs_get_max_file_uploads(),
		'max_files_exceeded'    => kbs_get_notices( 'max_files', true ),
		'needs_bs4'             => $needs_bs4,
        'one_option'            => __( 'Choose an option', 'kb-support' ),
		'one_or_more_option'    => __( 'Choose one or more options', 'kb-support' ),
        'permalinks'            => get_option( 'permalink_structure' ) ? '1' : '0',
        'recaptcha_site_key'    => kbs_get_option( 'recaptcha_site_key' ),
        'recaptcha_version'     => kbs_get_recaptcha_version(),
        'replies_to_load'       => kbs_get_customer_replies_to_load(),
        'reply_label'           => kbs_get_ticket_reply_label(),
        'search_placeholder'    => __( 'Search options', 'kb-support' ),
        'submit_ticket'         => kbs_get_form_submit_label(),
		'submit_ticket_loading' => __( 'Please Wait...', 'kb-support' ),
        'type_to_search'        => __( 'Type to search', 'kb-support' ),
	) ) );

	if ( $is_submission )	{
		add_thickbox();

		wp_register_script( 'jquery-chosen', $js_dir . 'chosen.jquery' . $suffix . '.js', array( 'jquery' ), KBS_VERSION );
		wp_enqueue_script( 'jquery-chosen' );

		// The live search is registered here, but it is enqueued within /includes/forms/form-functions.php
		wp_register_script( 'kbs-live-search', $js_dir . 'kbs-live-search' . $suffix . '.js', array( 'jquery' ), KBS_VERSION );

		wp_localize_script( 'kbs-live-search', 'kbs_search_vars', apply_filters( 'kbs_search_script_vars', array(
			'ajax_loader'           => KBS_PLUGIN_URL . 'assets/images/loading.gif',
			'ajaxurl'               => kbs_get_ajax_url(),
			'min_search_trigger'    => apply_filters( 'kbs_article_search_trigger_length', 3 ),
		) ) );
	}

	if ( $needs_bs4 )	{
		wp_register_script(
			'kbs-bootstrap-4-js',
			'https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/js/bootstrap.min.js',
			array( 'jquery' ),
			'4.2.1'
		);
		wp_enqueue_script( 'kbs-bootstrap-4-js' );
	}

} // kbs_load_scripts
add_action( 'wp_enqueue_scripts', 'kbs_load_scripts' );

/**
 * Register Styles
 *
 * Checks the styles option and hooks the required filter.
 *
 * @since	1.0
 * @return	void
 */
function kbs_register_styles() {

	if ( kbs_get_option( 'disable_styles', false ) ) {
		return;
	}

	global $post;

    $is_submission = kbs_is_submission_form();
    $shortcodes    = array( 'kbs_tickets', 'kbs_login', 'kbs_register', 'kbs_profile_editor' );
	$needs_bs4     = false;

    if ( ! empty( $post ) && ! empty( $post->ID ) ) {
        $needs_bs4 = $post->ID == kbs_get_option( 'tickets_page' );
    }

    if ( ! $needs_bs4 && ! empty( $post ) && is_a( $post, 'WP_Post' ) )	{
        foreach( $shortcodes as $shortcode )    {
            if ( $needs_bs4 )   {
                break;
            }

            $needs_bs4 = has_shortcode( $post->post_content, $shortcode );
        }

		if ( ! $needs_bs4 && has_shortcode( $post->post_content, 'kbs_submit' ) )   {
			if ( ! kbs_user_can_submit() && 'none' != kbs_get_option( 'show_register_form' ) )  {
				$needs_bs4 = true;
			}
		}

    }

	$needs_bs4 = apply_filters( 'kbs_styles_need_bs4', $needs_bs4 );

	// Use minified libraries if SCRIPT_DEBUG is turned off
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	$file          = 'kbs' . $suffix . '.css';
	$templates_dir = kbs_get_theme_template_dir_name();
	$css_dir       = KBS_PLUGIN_URL . 'assets/css/';

	$child_theme_style_sheet    = trailingslashit( get_stylesheet_directory() ) . $templates_dir . $file;
	$child_theme_style_sheet_2  = trailingslashit( get_stylesheet_directory() ) . $templates_dir . 'kbs.css';
	$parent_theme_style_sheet   = trailingslashit( get_template_directory()   ) . $templates_dir . $file;
	$parent_theme_style_sheet_2 = trailingslashit( get_template_directory()   ) . $templates_dir . 'kbs.css';
	$kbs_plugin_style_sheet     = trailingslashit( kbs_get_templates_dir()    ) . $file;

	// Look in the child theme directory first, followed by the parent theme, followed by the KBS core templates directory
	// Also look for the min version first, followed by non minified version, even if SCRIPT_DEBUG is not enabled.
	// This allows users to copy just kbs.css to their theme
	if ( file_exists( $child_theme_style_sheet ) || ( ! empty( $suffix ) && ( $nonmin = file_exists( $child_theme_style_sheet_2 ) ) ) ) {
		if( ! empty( $nonmin ) ) {
			$url = trailingslashit( get_stylesheet_directory_uri() ) . $templates_dir . 'kbs.css';
		} else {
			$url = trailingslashit( get_stylesheet_directory_uri() ) . $templates_dir . $file;
		}
	} elseif ( file_exists( $parent_theme_style_sheet ) || ( ! empty( $suffix ) && ( $nonmin = file_exists( $parent_theme_style_sheet_2 ) ) ) ) {
		if( ! empty( $nonmin ) ) {
			$url = trailingslashit( get_template_directory_uri() ) . $templates_dir . 'kbs.css';
		} else {
			$url = trailingslashit( get_template_directory_uri() ) . $templates_dir . $file;
		}
	} elseif ( file_exists( $kbs_plugin_style_sheet ) || file_exists( $kbs_plugin_style_sheet ) ) {
		$url = trailingslashit( kbs_get_templates_url() ) . $file;
	}

    if ( $needs_bs4 )	{

		wp_register_style(
			'kbs-bootstrap-4-css',
			'https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css',
			array(),
			'4.2.1'
		);
		wp_enqueue_style( 'kbs-bootstrap-4-css' );

	}

	wp_register_style( 'kbs-styles', $url, array(), KBS_VERSION, 'all' );
	wp_enqueue_style( 'kbs-styles' );

    if ( is_admin_bar_showing() )   {
        wp_register_style( 'kbs-admin-bar', $css_dir . 'kbs-admin-bar' . $suffix . '.css', array(), KBS_VERSION, 'all' );
    	wp_enqueue_style( 'kbs-admin-bar' );
    }

	if ( $is_submission )	{
		// Register the chosen styles here, but we enqueue within kbs_display_form_select_field when needed
		wp_register_style( 'jquery-chosen-css', $css_dir . 'chosen' . $suffix . '.css', array(), KBS_VERSION );
		wp_enqueue_style( 'jquery-chosen-css' );
	}

} // kbs_register_styles
add_action( 'wp_enqueue_scripts', 'kbs_register_styles' );

/**
 * Load Admin Styles
 *
 * Enqueues the required admin styles.
 *
 * @since	1.0
 * @param	str		$hook	Page hook
 * @return	void
 */
function kbs_load_admin_styles( $hook ) {

    if ( ! apply_filters( 'kbs_load_admin_styles', kbs_is_admin_page(), $hook ) ) {
		return;
	}

	$assets_dir  = trailingslashit( KBS_PLUGIN_URL . 'assets' );
	$css_dir     = trailingslashit( $assets_dir . 'css' );
	$suffix      = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	$ui_style = ( 'classic' == get_user_option( 'admin_color' ) ) ? 'classic' : 'fresh';

	if ( 'post.php' == $hook || 'post-new.php' == $hook )	{

		if ( isset( $_GET['post'] ) && 'kbs_ticket' == get_post_type( $_GET['post'] ) )	{
			$ui_style = 'humanity';
		}

	}

	wp_register_style( 'jquery-ui-css', $css_dir . 'jquery-ui-' . $ui_style . $suffix . '.css' );
	wp_enqueue_style( 'jquery-ui-css' );

	wp_register_style( 'kbs-admin', $css_dir . 'kbs-admin' . $suffix . '.css', array(), KBS_VERSION );
	wp_enqueue_style( 'kbs-admin' );

    if ( is_admin_bar_showing() )   {
        wp_register_style( 'kbs-admin-bar', $css_dir . 'kbs-admin-bar' . $suffix . '.css', array(), KBS_VERSION, 'all' );
    	wp_enqueue_style( 'kbs-admin-bar' );
    }

	wp_register_style( 'jquery-chosen-css', $css_dir . 'chosen' . $suffix . '.css', array(), KBS_VERSION );
	wp_enqueue_style( 'jquery-chosen-css' );

} // kbs_load_admin_styles
add_action( 'admin_enqueue_scripts', 'kbs_load_admin_styles' );

/**
 * Load Admin Scripts
 *
 * Enqueues the required admin scripts.
 *
 * @since	1.0
 * @global	$post
 * @param	str		$hook	Page hook
 * @return	void
 */
function kbs_load_admin_scripts( $hook ) {

	if ( ! apply_filters( 'kbs_load_admin_scripts', kbs_is_admin_page(), $hook ) ) {
		return;
	}

	global $wp_version, $post;

	$assets_dir  = trailingslashit( KBS_PLUGIN_URL . 'assets' );
	$js_dir      = trailingslashit( $assets_dir . 'js' );
	$suffix      = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	$admin_deps = array();
	if ( ! kbs_is_admin_page( $hook, 'edit' ) && ! kbs_is_admin_page( $hook, 'new' ) ) {
		$admin_deps = array( 'jquery', 'inline-edit-post' );
	} else {
		$admin_deps = array( 'jquery' );
	}

	wp_register_script( 'kbs-admin-scripts', $js_dir . 'admin-scripts' . $suffix . '.js', $admin_deps, KBS_VERSION, false );
	wp_enqueue_script( 'kbs-admin-scripts' );

	$editing_field_type = false;

	if ( isset( $_GET['kbs-action'] ) && 'edit_form_field' == $_GET['kbs-action'] )	{
		$field_settings = kbs_get_field_settings( $_GET['field_id'] );

		if ( $field_settings )	{
			$editing_field_type = $field_settings['type'];
		}
	}

    $singular = kbs_get_ticket_label_singular();

	wp_localize_script( 'kbs-admin-scripts', 'kbs_vars', array(
		'add_new_ticket'          => sprintf( __( 'Add New %s', 'kb-support' ), $singular ),
		'agent_set_status'        => kbs_agent_can_set_status_on_reply(),
		'admin_url'               => admin_url(),
		'ajax_loader'             => KBS_PLUGIN_URL . 'assets/images/loading.gif',
        'customer_email_required' => __( 'Customer email address is required', 'kb-support' ),
        'customer_name_required'  => __( 'Customer name is required', 'kb-support' ),
        'delete_reply_warn'       => __( "You will permanently delete this reply.\n\nDepending on configuration, your customer may have already received it via email.\n\nClick 'Cancel' to stop, 'OK' to delete.", 'kb-support' ),
		'default_reply_status'    => kbs_agent_get_default_reply_status(),
        'delete_ticket_warn'      => sprintf(
            __( "You are about to permanently delete this %s.\n\nThis action cannot be undone.\n\nClick 'Cancel' to stop, 'OK' to delete.", 'kb-support' ), kbs_get_ticket_label_singular( true )
        ),
        'disable_closure_email'   => kbs_get_option( 'ticket_closed_disable_email', false ),
		'editing_field_type'      => $editing_field_type,
		'editing_ticket'          => isset( $_GET['action'] ) && 'edit' == $_GET['action'] && 'kbs_ticket' == get_post_type( $_GET['post'] ) ? true : false,
		'field_label_missing'     => __( 'Enter a Label for your field.', 'kb-support' ),
		'field_type_missing'      => __( 'Select the field Type', 'kb-support' ),
		'hide_note'               => __( 'Hide Note', 'kb-support' ),
		'hide_participants'       => __( 'Hide participants', 'kb-support' ),
		'hide_reply'              => __( 'Hide Reply', 'kb-support' ),
        'hide_submission'         => __( 'Hide submission data', 'kb-support' ),
		'kbs_version'             => KBS_VERSION,
		'new_media_ui'            => apply_filters( 'kbs_use_35_media_ui', 1 ),
        'new_reply_notice'        => sprintf( __( 'A new reply has been added to this %s. Click OK to reload replies now, or Cancel to ignore.', 'kb-support' ), strtolower( $singular ) ),
		'no_note_content'         => __( 'There is no content in your note', 'kb-support' ),
		'no_ticket_reply_content' => __( 'There is no content in your reply', 'kb-support' ),
		'note_not_added'          => __( 'Your note could not be added', 'kb-support' ),
		'one_option'              => sprintf( __( 'Choose a %s', 'kb-support' ), $singular ),
		'one_or_more_option'      => sprintf( __( 'Choose one or more %s', 'kb-support' ), kbs_get_ticket_label_plural() ),
        'please_wait'             => __( 'Please Wait...', 'kb-support' ),
		'post_id'                 => isset( $post->ID ) ? $post->ID : null,
		'post_type'               => isset( $_GET['post'] ) ? get_post_type( $_GET['post'] ) : false,
        'regenerate_api_key'      => __( 'Are you sure you wish to regenerate this API key?', 'kb-support' ),
        'reply_alerts'            => kbs_alert_agent_ticket_reply(),
		'reply_has_data'          => sprintf( __( 'You have not submitted the reply. If you continue, the reply will not be added to the %s', 'kb-support' ), kbs_get_ticket_label_singular( true ) ),
        'revoke_api_key'          => __( 'Are you sure you wish to revoke this API key?', 'kb-support' ),
		'search_placeholder'      => sprintf( __( 'Type to search all %s', 'kb-support' ), kbs_get_ticket_label_plural() ),
        'send_closure_email'      => __( 'Send closure email?', 'kb-support' ),
		'ticket_confirm_close'    => __( 'Are you sure you wish to close this ticket? Click OK to close, or Cancel to return.', 'kb-support' ),
        'ticket_flag'             => sprintf( __( 'Flag %s', 'kb-support' ), strtolower( $singular ) ),
        'ticket_unflag'           => sprintf( __( 'Unflag %s', 'kb-support' ), strtolower( $singular ) ),
		'ticket_reply_added'      => 'ticket_reply_added',
		'ticket_reply_failed'     => sprintf( __( 'Could not add %s Reply', 'kb-support' ), $singular ),
		'type_to_search'          => sprintf( __( 'Type to search %s', 'kb-support' ), kbs_get_article_label_plural() ),
        'view_reply'              => __( 'View Reply', 'kb-support' ),
		'view_note'               => __( 'View Note', 'kb-support' ),
		'view_participants'       => __( 'View participants', 'kb-support' ),
        'view_submission'         => __( 'View submission data', 'kb-support' )
	) );

	if ( function_exists( 'wp_enqueue_media' ) && version_compare( $wp_version, '3.5', '>=' ) ) {
		// Call for new media manager
		wp_enqueue_media();
	}

	if ( 'post.php' == $hook || 'post-new.php' == $hook )	{
		if ( isset( $_GET['post'] ) && 'kbs_ticket' == get_post_type( $_GET['post'] ) )	{
			$ui_style = 'humanity';
		}
	}

	wp_register_script( 'kbs-font-awesome', KBS_PLUGIN_DIR . '/assets/js/fontawesome.min.js', array(), KBS_VERSION );
	wp_enqueue_script( 'kbs-font-awesome' );

	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );

	wp_register_script( 'jquery-chosen', $js_dir . 'chosen.jquery' . $suffix . '.js', array( 'jquery' ), KBS_VERSION );
	wp_enqueue_script( 'jquery-chosen' );

	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_enqueue_script( 'jquery-ui-dialog' );

	wp_enqueue_script( 'media-upload' );
	wp_enqueue_script( 'thickbox' );
	wp_enqueue_style( 'thickbox' );

} // kbs_load_admin_scripts
add_action( 'admin_enqueue_scripts', 'kbs_load_admin_scripts' );

/**
 * At a Glance Icons
 *
 * Echoes the CSS for the ticket and article post type icons.
 *
 * @since	1.0
 * @return	void
*/
function kbs_admin_icons() {

	$tickets_icon  = '\f468';
	$articles_icon = '\f118';
	?>
	<style type="text/css" media="screen">
		#dashboard_right_now .ticket-count:before {
			content: '<?php echo $tickets_icon; ?>';
		}
		#dashboard_right_now .article-count:before {
			content: '<?php echo $articles_icon; ?>';
		}
	</style>
	<?php
} // kbs_admin_icons
add_action( 'admin_head-index.php','kbs_admin_icons' );
