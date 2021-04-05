<?php
/**
 * Misc Functions
 *
 * @package     KBS
 * @subpackage  Functions
 * @copyright   Copyright (c) 2017, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Default contextual help sidebar text
 *
 * @since	1.1.6
 * @return	str      The text that makes up the contextual help sidebar
 */
function kbs_get_contextual_help_sidebar_text() {
    $sidebar_text = '
        <p><strong>' . __( 'More Information:', 'kb-support' ) . '</strong></p>' .
		'<p>' . sprintf(
			__( '<a href="%s" target="_blank">Documentation</a>', 'kb-support' ),
			esc_url( 'https://kb-support.com/support/' )
		) . '</p>' .
		'<p>' . sprintf(
			__( '<a href="%s" target="_blank">Twitter</a>', 'kb-support' ),
			esc_url( 'https://twitter.com/kbsupport_wp/' )
		) . '</p>' .
		'<p>' . sprintf(
			__( '<a href="%s" target="_blank">Facebook</a>', 'kb-support' ),
			esc_url( 'https://www.facebook.com/kbsupport/' )
		) . '</p>' .
		'<p>' . sprintf(
			__( '<a href="%s" target="_blank">Post an issue</a> on <a href="%s" target="_blank">GitHub</a>', 'kb-support' ),
			esc_url( 'https://github.com/KB-Support/kb-support/issues' ),
			esc_url( 'https://github.com/KB-Support/kb-support' )
		) . '</p>' .
		'<p>' . sprintf(
			__( '<a href="%s" target="_blank">Extensions</a>', 'kb-support' ),
			esc_url( 'https://kb-support.com/extensions/' )
		) . '</p>' .
        '<p>' . sprintf(
			__( '<a href="%s" target="_blank">Leave a Review</a>', 'kb-support' ),
			esc_url( 'https://wordpress.org/support/plugin/kb-support/reviews/' )
		) . '</p>';

    return $sidebar_text;
} // kbs_get_contextual_help_sidebar_text

/**
 * Add the ticket notification to the WordPress toolbar.
 *
 * @since   1.4
 * @param   object  $admin_bar  WP_Admin_Bar class object
 * @return  void
 */
function kbs_admin_bar_menu_items( $admin_bar ) {
    $show_menu_bar = kbs_get_option( 'show_count_menubar', 'none' );

    if ( 'none' == $show_menu_bar || ! kbs_is_agent() ) {
        return;
    }

    if ( ( 'front' == $show_menu_bar && is_admin() ) || ( 'admin' == $show_menu_bar && ! is_admin() ) )  {
        return;
    }

    if ( kbs_is_ticket_admin() || ! kbs_get_option( 'restrict_agent_view' ) )	{
		$count = kbs_get_open_ticket_count( 'open' );
	} else	{
		$agent = new KBS_Agent( get_current_user_id() );

		if ( $agent )	{
			$count = $agent->open_tickets;
		}
	}

    if ( empty( $count ) )  {
        return;
    }

    $icon  = '<span class="ab-icon dashicons dashicons-sos"></span> ';
    $title = sprintf(
        '<span class="kbs-ticket-counter count-%d">%d</span>',
        absint( $count ),
        number_format_i18n( $count )
    );

    $admin_bar->add_menu( array(
        'id'     => 'kbs-ticket-count',
        'parent' => null,
        'group'  => null,
        'title'  => $icon . $title,
        'href'   => admin_url( 'edit.php?post_type=kbs_ticket' ),
        'meta'   => array(
            'title' => sprintf( __( 'Open %s', 'kb-support' ), kbs_get_ticket_label_plural() )
        )
    ) );
} // kbs_admin_bar_menu_items
add_action( 'admin_bar_menu', 'kbs_admin_bar_menu_items', 500 );

/**
 * Checks if Guest checkout is enabled
 *
 * @since	1.0
 * @return	bool	$ret	True if guest checkout is enabled, false otherwise
 */
function kbs_no_guest_checkout() {
	$ret = kbs_get_option( 'logged_in_only', false );
	return (bool) apply_filters( 'kbs_no_guest_checkout', $ret );
} // kbs_no_guest_checkout

/**
 * Checks if users can only submit tickets when logged in
 *
 * @since	1.0
 * @return	bool	$ret	Whether or not the logged_in_only setting is set
 */
function kbs_logged_in_only() {
	$ret = kbs_get_option( 'logged_in_only', false );
	return (bool) apply_filters( 'kbs_logged_in_only', $ret );
} // kbs_logged_in_only

/**
 * Get User IP
 *
 * Returns the IP address of the current visitor
 *
 * @since	1.0
 * @return	str		$ip		User's IP address
 */
function kbs_get_ip() {

	$ip = '127.0.0.1';

	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		//check ip from share internet
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		//to check ip is pass from proxy
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} elseif( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
		$ip = $_SERVER['REMOTE_ADDR'];
	}
	return apply_filters( 'kbs_get_ip', $ip );
} // kbs_get_ip

/**
 * Month Num To Name
 *
 * Takes a month number and returns the name three letter name of it.
 *
 * @since	1.0
 *
 * @param	int		$n
 * @return	str		Short month name
 */
function kbs_month_num_to_name( $n ) {
	$timestamp = mktime( 0, 0, 0, $n, 1, 2005 );

	return date_i18n( "M", $timestamp );
} // kbs_month_num_to_name

/**
 * Get the current page URL
 *
 * @since	1.0
 * @return	str		$page_url	Current page URL
 */
function kbs_get_current_page_url()	{

	global $wp;

	if ( get_option( 'permalink_structure' ) ) {

		$base = trailingslashit( home_url( $wp->request ) );

	} else {

		$base = add_query_arg( $wp->query_string, '', trailingslashit( home_url( $wp->request ) ) );
		$base = remove_query_arg( array( 'post_type', 'name' ), $base );

	}

	$scheme = is_ssl() ? 'https' : 'http';
	$uri    = set_url_scheme( $base, $scheme );

	if ( is_front_page() ) {
		$uri = home_url( '/' );
	}

	$uri = apply_filters( 'kbs_get_current_page_url', $uri );

	return $uri;
} // kbs_get_current_page_url

/**
 * Retrieve timezone
 *
 * @since	1.0
 * @return	str		$timezone	The timezone ID
 */
function kbs_get_timezone_id() {

	// if site timezone string exists, return it
	if ( $timezone = get_option( 'timezone_string' ) )
		return $timezone;

	// get UTC offset, if it isn't set return UTC
	if ( ! ( $utc_offset = 3600 * get_option( 'gmt_offset', 0 ) ) )
		return 'UTC';

	// attempt to guess the timezone string from the UTC offset
	$timezone = timezone_name_from_abbr( '', $utc_offset );

	// last try, guess timezone string manually
	if ( $timezone === false ) {

		$is_dst = date( 'I' );

		foreach ( timezone_abbreviations_list() as $abbr ) {
			foreach ( $abbr as $city ) {
				if ( $city['dst'] == $is_dst &&  $city['offset'] == $utc_offset )
					return $city['timezone_id'];
			}
		}
	}

	// fallback
	return 'UTC';
} // kbs_get_timezone_id

/**
 * Get PHP Arg Separator Output
 *
 * @since	1.0
 * @return	str		Arg separator output
 */
function kbs_get_php_arg_separator_output() {
	return ini_get( 'arg_separator.output' );
} // kbs_get_php_arg_separator_output

/**
 * Get File Extension
 *
 * Returns the file extension of a filename.
 *
 * @since    1.1
 *
 * @param    unknown     $str    File name
 * @return   mixed       File extension
 */
function kbs_get_file_extension( $str ) {
	$parts = explode( '.', $str );
	return end( $parts );
} // kbs_get_file_extension

/**
 * Given an object or array of objects, convert them to arrays
 *
 * @since   1.1
 * @param   object|array   $object An object or an array of objects
 * @return  arr            An array or array of arrays, converted from the provided object(s)
 */
function kbs_object_to_array( $object = array() ) {

	if ( empty( $object ) || ( ! is_object( $object ) && ! is_array( $object ) ) ) {
		return $object;
	}

	if ( is_array( $object ) ) {
		$return = array();
		foreach ( $object as $item ) {
			if ( is_a( $object, 'KBS_Ticket' ) ) {
				$return[] = $object->array_convert();
			} else {
				$return[] = kbs_object_to_array( $item );
			}

		}
	} else {
		if ( is_a( $object, 'KBS_Ticket' ) ) {
			$return = $object->array_convert();
		} else {
			$return = get_object_vars( $object );

			// Now look at the items that came back and convert any nested objects to arrays
			foreach ( $return as $key => $value ) {
				$value = ( is_array( $value ) || is_object( $value ) ) ? kbs_object_to_array( $value ) : $value;
				$return[ $key ] = $value;
			}
		}
	}

	return $return;

} // kbs_object_to_array

/**
 * Validate the form honeypot to protect against bots.
 *
 * @since	1.0
 * @param	arr		$data	Form post data
 * @return	void
 */
function kbs_do_honeypot_check( $data )	{
	if ( ! empty( $data['kbs_honeypot'] ) )	{
		wp_die( __( "Ha! I don't think so little honey bee. No bots allowed in this Honey Pot!", 'kb-support' ) );
	}

	return;
} // kbs_do_honeypot_check

/**
 * Retrieve reCAPTCHA version.
 *
 * @since   1.5.2
 * @return  string  reCAPTCHA version
 */
function kbs_get_recaptcha_version()    {
    return kbs_get_option( 'recaptcha_version', 'v2' );
} // kbs_get_recaptcha_version

/**
 * Display a Notice.
 *
 * @since	1.0
 * @param	str		$m		The notice message key.
 * @return	str		The HTML string for the notice
 */
function kbs_display_notice( $m )	{
	$notices = kbs_get_notices( $m );

	if ( $notices )	{
		return '<div class="kbs_alert kbs_alert_' . $notices['class'] . '">' . $notices['notice'] . '</div>';
	}
} // kbs_display_notice

/**
 * Front end notices.
 *
 * @since	1.0
 * @param	str		$notice			The message key to display.
 * @param	bool	$notice_only	True to only return the message string, false to return class/notice array.
 * @return	arr|str	Notice.
 */
function kbs_get_notices( $notice = '', $notice_only = false )	{
	$notices = array(
		'agents_cannot_submit' => array(
			'class'  => 'info',
			'notice' => sprintf(
				__( 'Support Workers cannot submit %s here. Please go to your <a href="%s">admin panel</a> to open a new %s.', 'kb-support' ),
				kbs_get_ticket_label_plural( true ),
				admin_url( 'post-new.php?post_type=kbs_ticket' ),
				kbs_get_ticket_label_singular( true )
			)
		),
		'need_login' => array(
			'class'  => 'info',
			'notice' => sprintf( __( 'You must be logged in to create a support %s.', 'kb-support' ), kbs_get_ticket_label_singular( true ) )
		),
		'profile_login' => array(
			'class'  => 'info',
			'notice' => __( 'You must log in to manage your profile.', 'kb-support' )
		),
		'username_incorrect' => array(
			'class'  => 'error',
			'notice' => __( 'The username was not recognised.', 'kb-support' )
		),
		'password_incorrect' => array(
			'class'  => 'error',
			'notice' => __( 'An incorrect password was entered.', 'kb-support' )
		),
		'missing_registration_data' => array(
			'class'  => 'error',
			'notice' => __( 'All registration fields are mandatory.', 'kb-support' )
		),
		'could_not_register' => array(
			'class'  => 'error',
			'notice' => __( 'Unable to register your user account.', 'kb-support' )
		),
		'empty_first_name' => array(
			'class'  => 'error',
			'notice' => __( 'Please enter your first name.', 'kb-support' )
		),
		'empty_last_name' => array(
			'class'  => 'error',
			'notice' => __( 'Please enter your last name.', 'kb-support' )
		),
		'email_unavailable' => array(
			'class'  => 'error',
			'notice' => __( 'The email address you entered is already registered.', 'kb-support' )
		),
		'email_invalid' => array(
			'class'  => 'error',
			'notice' => __( 'You entered an invalid email address.', 'kb-support' )
		),
		'email_removed' => array(
			'class'  => 'success',
			'notice' => __( 'Email address removed.', 'kb-support' )
		),
		'email_remove_failed' => array(
			'class'  => 'error',
			'notice' => __( 'Unable to remove email address.', 'kb-support' )
		),
		'empty_password' => array(
			'class'  => 'error',
			'notice' => __( 'Please enter a password.', 'kb-support' )
		),
		'password_mismatch' => array(
			'class'  => 'error',
			'notice' => __( 'Passwords do not match.', 'kb-support' )
		),
		'ticket_submitted' => array(
			'class'  => 'success',
			'notice' => __( "Your support request has been successfully received. We'll be in touch as soon as possible.", 'kb-support' )
		),
		'ticket_closed' => array(
			'class'  => 'success',
			'notice' => sprintf( __( 'The %s was successfully closed', 'kb-support' ), kbs_get_ticket_label_singular( true ) )
		),
		'ticket_close_failed' => array(
			'class'  => 'error',
			'notice' => sprintf( __( 'Could not close %s', 'kb-support' ), kbs_get_ticket_label_singular( true ) )
		),
		'ticket_failed' => array(
			'class'  => 'error',
			'notice' => __( 'There was an error submitting your support request. Please try again', 'kb-support' )
		),
		'no_ticket' => array(
			'class'  => 'error',
			'notice' => sprintf( __( 'No %s found.', 'kb-support' ), kbs_get_ticket_label_singular( true ) )
		),
		'ticket_login' => array(
			'class'  => 'info',
			'notice' => sprintf( __( 'You must log in to view your %s.', 'kb-support' ), kbs_get_ticket_label_plural( true ) )
		),
		'category_restricted' => array(
			'class'  => 'info',
			'notice' => sprintf( __( 'Access to %s in this category is restricted.', 'kb-support' ), kbs_get_article_label_plural() )
		),
		'category_restricted_login' => array(
			'class'  => 'info',
			'notice' => sprintf( __( 'Access to %s in this category is restricted. Log in to continue.', 'kb-support' ), kbs_get_article_label_plural() )
		),
		'tag_restricted' => array(
			'class'  => 'info',
			'notice' => sprintf( __( 'Access to %s with this tag is restricted.', 'kb-support' ), kbs_get_article_label_plural() )
		),
		'tag_restricted_login' => array(
			'class'  => 'info',
			'notice' => sprintf( __( 'Access to %s with this tag is restricted. Log in to continue.', 'kb-support' ), kbs_get_article_label_plural() )
		),
		'missing_reply' => array(
			'class'  => 'error',
			'notice' => __( 'Please enter your reply.', 'kb-support' )
		),
		'reply_success' => array(
			'class'  => 'success',
			'notice' => __( 'Your reply has been received. If necessary, one of our agents will be in touch shortly.', 'kb-support' )
		),
		'reply_fail' => array(
			'class'  => 'error',
			'notice' => __( 'Your reply could not be processed.', 'kb-support' )
		),
		'max_files' => array(
			'class'  => 'error',
			'notice' => sprintf( __( 'The maximum number of files you are allowed to upload is %s.', 'kb-support' ), kbs_get_max_file_uploads() )
		),
		'invalid_customer' => array(
			'class'  => 'error',
			'notice' => sprintf( __( 'You are not allowed to manage this %s.', 'kb-support' ), kbs_get_ticket_label_singular( true ) )
		),
		'profile_updated' => array(
			'class'  => 'success',
			'notice' => __( 'Profile updated successfully.', 'kb-support' )
		),
        'recaptcha_failed' => array(
			'class'  => 'error',
			'notice' => __( 'reCAPTCHA validation failed.', 'kb-support' )
		),
	);

	$notices = apply_filters( 'kbs_get_notices', $notices );

	if ( ! empty( $notice ) )	{
		if ( ! array_key_exists( $notice, $notices ) )	{
			return false;
		}

		if ( ! $notice_only )	{
			return $notices[ $notice ];
		} else	{
			return $notices[ $notice ]['notice'];
		}
	}

	return $notices;
} // kbs_get_notices

/**
 * Retrieve days of week.
 *
 * @since	1.0
 * @return	arr		Array of days of week values. $day_number => $day_name
 */
function kbs_get_days_of_week()	{
	global $wp_locale;

	$days_of_week = array();
	$week_start   = get_option( 'start_of_week' );

	$days_of_week[ $week_start ] = $wp_locale->get_weekday( $week_start );

	for( $day_index = 0; $day_index <= 6; $day_index++ )	{
		if ( '1' == $week_start && '0' == $day_index )	{
			continue;
		}
		$days_of_week[ $day_index ] = $wp_locale->get_weekday( $day_index );
	}

	if ( '1' == $week_start )	{
		$days_of_week[ 0 ] = $wp_locale->get_weekday( 0 );
	}

	return $days_of_week;
} // kbs_get_days_of_week

/**
 * Adds credit information after the ticket and reply form.
 *
 * @since	1.0
 * @return	str		Credit text
 */
function kbs_add_credit_text()	{

	if ( kbs_get_option( 'show_credits', false ) )	{
		ob_start(); ?>

		<span class="kbs-description"><?php printf( __( 'Powered by <a href="%s" title="KB Support" target="_blank">KB Support</a>. The ultimate help desk and knowledge base support tool plugin for WordPress. <a href="%s" target="_blank">Download for free</a>.', 'kb-support' ), 'https://kb-support.com/', 'https://wordpress.org/plugins/kb-support' ); ?></span>

		<?php echo ob_get_clean();
	}

} // kbs_add_credit_text
add_action( 'kbs_after_ticket_form', 'kbs_add_credit_text' );
add_action( 'kbs_after_single_ticket_form', 'kbs_add_credit_text' );

/**
 * Marks a function as deprecated and informs when it has been used.
 *
 * There is a hook kbs_deprecated_function_run that will be called that can be used
 * to get the backtrace up to what file and function called the deprecated
 * function.
 *
 * The current behavior is to trigger a user error if WP_DEBUG is true.
 *
 * This function is to be used in every function that is deprecated.
 *
 * @uses do_action() Calls 'kbs_deprecated_function_run' and passes the function name, what to use instead,
 *   and the version the function was deprecated in.
 * @uses apply_filters() Calls 'kbs_deprecated_function_trigger_error' and expects boolean value of true to do
 *   trigger or false to not trigger error.
 *
 * @param string  $function    The function that was called
 * @param string  $version     The version of KB Support that deprecated the function
 * @param string  $replacement Optional. The function that should have been called
 * @param array   $backtrace   Optional. Contains stack backtrace of deprecated function
 */
function _kbs_deprecated_function( $function, $version, $replacement = null, $backtrace = null ) {
	do_action( 'kbs_deprecated_function_run', $function, $replacement, $version );

	$show_errors = current_user_can( 'manage_options' );

	// Allow plugin to filter the output error trigger
	if ( WP_DEBUG && apply_filters( 'kbs_deprecated_function_trigger_error', $show_errors ) ) {
		if ( ! is_null( $replacement ) ) {
			trigger_error( sprintf(
				__( '%1$s is <strong>deprecated</strong> since KB Support version %2$s! Use %3$s instead.', 'kb-support' ),
				$function,
				$version,
				$replacement
			) );
			trigger_error(  print_r( $backtrace, 1 ) ); // Limited to previous 1028 characters, but since we only need to move back 1 in stack that should be fine.
			// Alternatively we could dump this to a file.
		} else {
			trigger_error( sprintf(
				__( '%1$s is <strong>deprecated</strong> since KB Support version %2$s with no alternative available.', 'kb-support' ),
				$function,
				$version
			) );
			trigger_error( print_r( $backtrace, 1 ) );// Limited to previous 1028 characters, but since we only need to move back 1 in stack that should be fine.
			// Alternatively we could dump this to a file.
		}
	}
} // _kbs_deprecated_function

/**
 * Marks an argument in a function deprecated and informs when it's been used
 *
 * There is a hook kbs_deprecated_argument_run that will be called that can be used
 * to get the backtrace up to what file and function called the deprecated
 * function.
 *
 * The current behavior is to trigger a user error if WP_DEBUG is true.
 *
 * This function is to be used in every function that has an argument being deprecated.
 *
 * @uses do_action() Calls 'kbs_deprecated_argument_run' and passes the argument, function name, what to use instead,
 *   and the version the function was deprecated in.
 * @uses apply_filters() Calls 'kbs_deprecated_argument_trigger_error' and expects boolean value of true to do
 *   trigger or false to not trigger error.
 *
 * @param	str		$argument		The argument that is being deprecated
 * @param	str		$function		The function that was called
 * @param	str		$version		The version of WordPress that deprecated the function
 * @param	str		$replacement	Optional. The function that should have been called
 * @param	arr		$backtrace		Optional. Contains stack backtrace of deprecated function
 */
function _kbs_deprected_argument( $argument, $function, $version, $replacement = null, $backtrace = null )	{
	do_action( 'kbs_deprecated_argument_run', $argument, $function, $replacement, $version );

	$show_errors = current_user_can( 'manage_options' );

	// Allow plugin to filter the output error trigger
	if ( WP_DEBUG && apply_filters( 'kbs_deprecated_argument_trigger_error', $show_errors ) )	{
		if ( ! is_null( $replacement ) )	{
			trigger_error( sprintf(
				__( 'The %1$s argument of %2$s is <strong>deprecated</strong> since KB Support version %3$s! Please use %4$s instead.', 'kb-support' ),
				$argument, $function, $version, $replacement
			) );
			trigger_error( print_r( $backtrace, 1 ) ); // Limited to previous 1028 characters, but since we only need to move back 1 in stack that should be fine.
			// Alternatively we could dump this to a file.
		} else	{
			trigger_error( sprintf(
				__( 'The %1$s argument of %2$s is <strong>deprecated</strong> since KB Support version %3$s with no alternative available.', 'kb-support' ),
				$argument,
				$function,
				$version
			) );
			trigger_error( print_r( $backtrace, 1 ) );// Limited to previous 1028 characters, but since we only need to move back 1 in stack that should be fine.
			// Alternatively we could dump this to a file.
		}
	}
} // _kbs_deprected_argument

/**
 * Checks whether a function is disabled.
 *
 * @since	1.0
 *
 * @param	str		$function	Name of the function.
 * @return	bool	Whether or not function is disabled.
 */
function kbs_is_func_disabled( $function ) {
	$disabled = explode( ',',  ini_get( 'disable_functions' ) );

	return in_array( $function, $disabled );
} // kbs_is_func_disabled

/**
 * Get Country List
 *
 * @since	1.0
 * @return	arr		$countries	A list of the available countries
 */
function kbs_get_country_list() {
	$countries = array(
		''   => '',
		'GB' => 'United Kingdom',
		'US' => 'United States',
		'CA' => 'Canada',
		'AF' => 'Afghanistan',
		'AX' => '&#197;land Islands',
		'AL' => 'Albania',
		'DZ' => 'Algeria',
		'AS' => 'American Samoa',
		'AD' => 'Andorra',
		'AO' => 'Angola',
		'AI' => 'Anguilla',
		'AQ' => 'Antarctica',
		'AG' => 'Antigua and Barbuda',
		'AR' => 'Argentina',
		'AM' => 'Armenia',
		'AW' => 'Aruba',
		'AU' => 'Australia',
		'AT' => 'Austria',
		'AZ' => 'Azerbaijan',
		'BS' => 'Bahamas',
		'BH' => 'Bahrain',
		'BD' => 'Bangladesh',
		'BB' => 'Barbados',
		'BY' => 'Belarus',
		'BE' => 'Belgium',
		'BZ' => 'Belize',
		'BJ' => 'Benin',
		'BM' => 'Bermuda',
		'BT' => 'Bhutan',
		'BO' => 'Bolivia',
		'BQ' => 'Bonaire, Saint Eustatius and Saba',
		'BA' => 'Bosnia and Herzegovina',
		'BW' => 'Botswana',
		'BV' => 'Bouvet Island',
		'BR' => 'Brazil',
		'IO' => 'British Indian Ocean Territory',
		'BN' => 'Brunei Darrussalam',
		'BG' => 'Bulgaria',
		'BF' => 'Burkina Faso',
		'BI' => 'Burundi',
		'KH' => 'Cambodia',
		'CM' => 'Cameroon',
		'CV' => 'Cape Verde',
		'KY' => 'Cayman Islands',
		'CF' => 'Central African Republic',
		'TD' => 'Chad',
		'CL' => 'Chile',
		'CN' => 'China',
		'CX' => 'Christmas Island',
		'CC' => 'Cocos Islands',
		'CO' => 'Colombia',
		'KM' => 'Comoros',
		'CD' => 'Congo, Democratic People\'s Republic',
		'CG' => 'Congo, Republic of',
		'CK' => 'Cook Islands',
		'CR' => 'Costa Rica',
		'CI' => 'Cote d\'Ivoire',
		'HR' => 'Croatia/Hrvatska',
		'CU' => 'Cuba',
		'CW' => 'Cura&Ccedil;ao',
		'CY' => 'Cyprus',
		'CZ' => 'Czech Republic',
		'DK' => 'Denmark',
		'DJ' => 'Djibouti',
		'DM' => 'Dominica',
		'DO' => 'Dominican Republic',
		'TP' => 'East Timor',
		'EC' => 'Ecuador',
		'EG' => 'Egypt',
		'GQ' => 'Equatorial Guinea',
		'SV' => 'El Salvador',
		'ER' => 'Eritrea',
		'EE' => 'Estonia',
		'ET' => 'Ethiopia',
		'FK' => 'Falkland Islands',
		'FO' => 'Faroe Islands',
		'FJ' => 'Fiji',
		'FI' => 'Finland',
		'FR' => 'France',
		'GF' => 'French Guiana',
		'PF' => 'French Polynesia',
		'TF' => 'French Southern Territories',
		'GA' => 'Gabon',
		'GM' => 'Gambia',
		'GE' => 'Georgia',
		'DE' => 'Germany',
		'GR' => 'Greece',
		'GH' => 'Ghana',
		'GI' => 'Gibraltar',
		'GL' => 'Greenland',
		'GD' => 'Grenada',
		'GP' => 'Guadeloupe',
		'GU' => 'Guam',
		'GT' => 'Guatemala',
		'GG' => 'Guernsey',
		'GN' => 'Guinea',
		'GW' => 'Guinea-Bissau',
		'GY' => 'Guyana',
		'HT' => 'Haiti',
		'HM' => 'Heard and McDonald Islands',
		'VA' => 'Holy See (City Vatican State)',
		'HN' => 'Honduras',
		'HK' => 'Hong Kong',
		'HU' => 'Hungary',
		'IS' => 'Iceland',
		'IN' => 'India',
		'ID' => 'Indonesia',
		'IR' => 'Iran',
		'IQ' => 'Iraq',
		'IE' => 'Ireland',
		'IM' => 'Isle of Man',
		'IL' => 'Israel',
		'IT' => 'Italy',
		'JM' => 'Jamaica',
		'JP' => 'Japan',
		'JE' => 'Jersey',
		'JO' => 'Jordan',
		'KZ' => 'Kazakhstan',
		'KE' => 'Kenya',
		'KI' => 'Kiribati',
		'KW' => 'Kuwait',
		'KG' => 'Kyrgyzstan',
		'LA' => 'Lao People\'s Democratic Republic',
		'LV' => 'Latvia',
		'LB' => 'Lebanon',
		'LS' => 'Lesotho',
		'LR' => 'Liberia',
		'LY' => 'Libyan Arab Jamahiriya',
		'LI' => 'Liechtenstein',
		'LT' => 'Lithuania',
		'LU' => 'Luxembourg',
		'MO' => 'Macau',
		'MK' => 'Macedonia',
		'MG' => 'Madagascar',
		'MW' => 'Malawi',
		'MY' => 'Malaysia',
		'MV' => 'Maldives',
		'ML' => 'Mali',
		'MT' => 'Malta',
		'MH' => 'Marshall Islands',
		'MQ' => 'Martinique',
		'MR' => 'Mauritania',
		'MU' => 'Mauritius',
		'YT' => 'Mayotte',
		'MX' => 'Mexico',
		'FM' => 'Micronesia',
		'MD' => 'Moldova, Republic of',
		'MC' => 'Monaco',
		'MN' => 'Mongolia',
		'ME' => 'Montenegro',
		'MS' => 'Montserrat',
		'MA' => 'Morocco',
		'MZ' => 'Mozambique',
		'MM' => 'Myanmar',
		'NA' => 'Namibia',
		'NR' => 'Nauru',
		'NP' => 'Nepal',
		'NL' => 'Netherlands',
		'AN' => 'Netherlands Antilles',
		'NC' => 'New Caledonia',
		'NZ' => 'New Zealand',
		'NI' => 'Nicaragua',
		'NE' => 'Niger',
		'NG' => 'Nigeria',
		'NU' => 'Niue',
		'NF' => 'Norfolk Island',
		'KP' => 'North Korea',
		'MP' => 'Northern Mariana Islands',
		'NO' => 'Norway',
		'OM' => 'Oman',
		'PK' => 'Pakistan',
		'PW' => 'Palau',
		'PS' => 'Palestinian Territories',
		'PA' => 'Panama',
		'PG' => 'Papua New Guinea',
		'PY' => 'Paraguay',
		'PE' => 'Peru',
		'PH' => 'Phillipines',
		'PN' => 'Pitcairn Island',
		'PL' => 'Poland',
		'PT' => 'Portugal',
		'PR' => 'Puerto Rico',
		'QA' => 'Qatar',
		'XK' => 'Republic of Kosovo',
		'RE' => 'Reunion Island',
		'RO' => 'Romania',
		'RU' => 'Russian Federation',
		'RW' => 'Rwanda',
		'BL' => 'Saint Barth&eacute;lemy',
		'SH' => 'Saint Helena',
		'KN' => 'Saint Kitts and Nevis',
		'LC' => 'Saint Lucia',
		'MF' => 'Saint Martin (French)',
		'SX' => 'Saint Martin (Dutch)',
		'PM' => 'Saint Pierre and Miquelon',
		'VC' => 'Saint Vincent and the Grenadines',
		'SM' => 'San Marino',
		'ST' => 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe',
		'SA' => 'Saudi Arabia',
		'SN' => 'Senegal',
		'RS' => 'Serbia',
		'SC' => 'Seychelles',
		'SL' => 'Sierra Leone',
		'SG' => 'Singapore',
		'SK' => 'Slovak Republic',
		'SI' => 'Slovenia',
		'SB' => 'Solomon Islands',
		'SO' => 'Somalia',
		'ZA' => 'South Africa',
		'GS' => 'South Georgia',
		'KR' => 'South Korea',
		'SS' => 'South Sudan',
		'ES' => 'Spain',
		'LK' => 'Sri Lanka',
		'SD' => 'Sudan',
		'SR' => 'Suriname',
		'SJ' => 'Svalbard and Jan Mayen Islands',
		'SZ' => 'Swaziland',
		'SE' => 'Sweden',
		'CH' => 'Switzerland',
		'SY' => 'Syrian Arab Republic',
		'TW' => 'Taiwan',
		'TJ' => 'Tajikistan',
		'TZ' => 'Tanzania',
		'TH' => 'Thailand',
		'TL' => 'Timor-Leste',
		'TG' => 'Togo',
		'TK' => 'Tokelau',
		'TO' => 'Tonga',
		'TT' => 'Trinidad and Tobago',
		'TN' => 'Tunisia',
		'TR' => 'Turkey',
		'TM' => 'Turkmenistan',
		'TC' => 'Turks and Caicos Islands',
		'TV' => 'Tuvalu',
		'UG' => 'Uganda',
		'UA' => 'Ukraine',
		'AE' => 'United Arab Emirates',
		'UY' => 'Uruguay',
		'UM' => 'US Minor Outlying Islands',
		'UZ' => 'Uzbekistan',
		'VU' => 'Vanuatu',
		'VE' => 'Venezuela',
		'VN' => 'Vietnam',
		'VG' => 'Virgin Islands (British)',
		'VI' => 'Virgin Islands (USA)',
		'WF' => 'Wallis and Futuna Islands',
		'EH' => 'Western Sahara',
		'WS' => 'Western Samoa',
		'YE' => 'Yemen',
		'ZM' => 'Zambia',
		'ZW' => 'Zimbabwe'
	);

	return apply_filters( 'kbs_countries', $countries );
} // kbs_get_country_list

/**
 * Check if the upgrade routine has been run for a specific action
 *
 * @since   1.2.9
 * @param   string  $upgrade_action     The upgrade action to check completion for
 * @return  bool    If the action has been added to the copmleted actions array
 */
function kbs_has_upgrade_completed( $upgrade_action = '' )	{

	if ( empty( $upgrade_action ) )	{
		return false;
	}

	$completed_upgrades = kbs_get_completed_upgrades();

	return in_array( $upgrade_action, $completed_upgrades );

} // kbs_has_upgrade_completed

/**
 * Retrieve the array of completed upgrade actions.
 *
 * @since   1.2.9
 * @return  array   The array of completed upgrades.
 */
function kbs_get_completed_upgrades()	{

	$completed_upgrades = get_option( 'kbs_completed_upgrades', array() );

	return $completed_upgrades;

} // kbs_get_completed_upgrades

/**
 * Premium extensions data.
 *
 * @since	1.4.6
 * @return	array	Array of premium extension data
 */
function kbs_get_premium_extension_data()	{
	$extensions = array(
		'advanced_ticket_assignment' => array(
			'name'         => 'Advanced Ticket Assignment',
			'desc'         => __( 'Define custom rules to automate ticket assignment and enhance your workflow.', 'kb-support' ),
			'plugin_url'   => 'kbs-advanced-ticket-assignment/kbs-advanced-ticket-assignment.php',
			'demo_url'     => 'https://kb-support.com/register-your-demo/?demo_ref=1892bfbbcd6410b5b34a2a6ee35e50fb',
			'purchase_url' => 'https://kb-support.com/downloads/advanced-ticket-assignment/'
		),
		'canned_replies' => array(
			'name'         => 'Canned Replies',
			'desc'         => __( 'Easily create a use Canned Replies to instantly add ticket replies.', 'kb-support' ),
			'plugin_url'   => 'kbs-canned-replies/kbs-canned-replies.php',
			'demo_url'     => 'https://kb-support.com/register-your-demo/?demo_ref=eefa9cce664b79abb6407ebd07e4e3a5',
			'purchase_url' => 'https://kb-support.com/downloads/canned-replies/'
		),
		'custom_ticket_status' => array(
			'name'         => 'Custom Ticket Status',
			'desc'         => __( 'Create additional ticket statuses to meet your business needs.', 'kb-support' ),
			'plugin_url'   => 'kbs-custom-status/kbs-custom-status.php',
			'demo_url'     => 'https://kb-support.com/register-your-demo/?demo_ref=60cfff6b93cc42216c03bc3886a5cb11',
			'purchase_url' => 'https://kb-support.com/downloads/custom-ticket-status/'
		),
		'easy_digital_downloads' => array(
			'name'         => 'Easy Digital Downloads',
			'desc'         => __( 'Integrate with EDD and its Software Licensing extension for the ultimate customer experience.', 'kb-support' ),
			'plugin_url'   => 'kbs-edd/kbs-edd.php',
			'demo_url'     => 'https://kb-support.com/register-your-demo/?demo_ref=cb0277e636b56fe9ef4d1fcbd8603ae6',
			'purchase_url' => 'https://kb-support.com/downloads/easy-digital-downloads/'
		),
		'email_signatures' => array(
			'name'         => 'Email Signatures',
			'desc'         => __( 'Enable agents to register custom signatures which can be inserted into customer emails.', 'kb-support' ),
			'plugin_url'   => 'kbs-email-signatures/kbs-email-signatures.php',
			'purchase_url' => 'https://kb-support.com/downloads/email-signatures/'
		),
		'email_support' => array(
			'name'         => 'Email Support',
			'desc'         => __( 'Enable customers and agents to respond to tickets via email.', 'kb-support' ),
			'plugin_url'   => 'kbs-email-support/kbs-email-support.php',
			'demo_url'     => 'https://kb-support.com/register-your-demo/?demo_ref=6c847b75c663cf62807249618cc80a40',
			'purchase_url' => 'https://kb-support.com/downloads/email-support/'
		),
		'knowledge_base_integrations' => array(
			'name'         => 'Knowledge Base Integrations',
			'desc'         => __( 'Integrate your favourite knowledge base with KB Support.', 'kb-support' ),
			'plugin_url'   => 'kbs-kb-integrations/kbs-kb-integrations.php',
			'purchase_url' => 'https://kb-support.com/downloads/knowledge-base-integrations/'
		),
		'mailchimp_integration' => array(
			'name'         => 'MailChimp Integration',
			'desc'         => __( 'Enable customers to subscribe to your MailChimp lists during ticket submission.', 'kb-support' ),
			'plugin_url'   => 'kbs-mailchimp-integration/kbs-mailchimp-integration.php',
			'purchase_url' => 'https://kb-support.com/downloads/mailchimp-integration/'
		),
		'ratings_and_satisfaction' => array(
			'name'         => 'Ratings and Satisfaction',
			'desc'         => __( 'Get feedback on your performance for support tickets and quality of documentation.', 'kb-support' ),
			'plugin_url'   => 'kbs-ratings-satisfaction/kbs-ratings-satisfaction.php',
			'purchase_url' => 'https://kb-support.com/downloads/ratings-and-satisfaction/'
		),
		'reply_approvals' => array(
			'name'         => 'Reply Approvals',
			'desc'         => __( 'Add an approval process to selected agent ticket replies.', 'kb-support' ),
			'plugin_url'   => 'kbs-reply-approvals/kbs-reply-approvals.php',
			'purchase_url' => 'https://kb-support.com/downloads/reply-approvals/'
		),
		'woocommerce' => array(
			'name'         => 'WooCommerce',
			'desc'         => __( 'Integrate with your WooCommerce store for the ultimate customer experience.', 'kb-support' ),
			'plugin_url'   => 'kbs-woocommerce/kbs-woocommerce.php',
			'demo_url'     => 'https://kb-support.com/register-your-demo/?demo_ref=11c28e3c2627aabf93a2b1a6c1836fe2',
			'purchase_url' => 'https://kb-support.com/downloads/woocommerce/'
		),
        'zapier' => array(
			'name'         => 'Zapier',
			'desc'         => __( 'Connect KB Support to thousands of 3rd party applications via zapier.com.', 'kb-support' ),
			'plugin_url'   => 'kbs-zapier/kbs-zapier.php',
			'purchase_url' => 'https://kb-support.com/downloads/zapier/'
		)
	);

	return $extensions;
} // kbs_get_premium_extension_data

/**
 * Retrieve current promotions.
 *
 * @since   1.4.9
 * @param   bool    $active_only    True to retrieve only active promotions
 * @return  array   Array of promotion data, or an empty array
 */
function kbs_get_current_promotions( $active_only = true )   {
    $promotions = array(
        'BF2021' => array(
            'name'        => __( 'Black Friday & Cyber Monday', 'kb-support' ),
            'campaign'    => 'bfcm2021',
            'image'       => 'bfcm-header.svg',
            'product'     => '',
            'start'       => strtotime( '2021-11-22 00:00:00' ),
            'finish'      => strtotime( '2021-12-05 23:59:59' ),
            'timezone'    => 'GMT',
            'discount'    => '40%',
            'cta'         => __( 'Shop Now!', 'kb-support' ),
            'cta_url'     => 'https://kb-support.com/extensions/',
            'description' => __( 'Save <strong>%7$s</strong> on all KB Support purchases <strong>this week</strong>.<br>Including renewals and upgrades!', 'kb-support' )
        ),
        'FLASH2020' => array(
            'name'        => __( 'Flash Sale', 'kb-support' ),
            'campaign'    => 'flash-sale',
            'image'       => 'flash-sale-header.svg',
            'product'     => '',
            'start'       => strtotime( '2020-12-21 00:00:00' ),
            'finish'      => strtotime( '2021-01-03 23:59:59' ),
            'timezone'    => 'GMT',
            'discount'    => '33%',
            'cta'         => __( 'Shop Now!', 'kb-support' ),
            'cta_url'     => 'https://kb-support.com/extensions/',
            'description' => __( 'Save <strong>%7$s</strong> on all KB Support purchases <strong>now</strong>. Including renewals and upgrades!', 'kb-support' )
        ),
        'STAYSAFE' => array(
            'name'        => __( 'Flash Sale', 'kb-support' ),
            'campaign'    => 'covid-19-sale',
            'image'       => 'flash-sale-header.svg',
            'product'     => '',
            'start'       => strtotime( '2021-01-11 00:00:00' ),
            'finish'      => strtotime( '2021-01-31 23:59:59' ),
            'timezone'    => 'GMT',
            'discount'    => '33%',
            'cta'         => __( 'Shop Now!', 'kb-support' ),
            'cta_url'     => 'https://kb-support.com/extensions/',
            'description' => __( 'We are supporting small businesses during the pandemic.<br>Save <strong>%7$s</strong> on all KB Support purchases <strong>now</strong>. Including renewals and upgrades!', 'kb-support' )
        )
    );

    foreach( $promotions as $promotion => $data )  {
        if ( ! empty( $data['description'] ) )  {
            $promotions[ $promotion ]['description'] = sprintf(
                $data['description'],
                $data['name'],
                $data['image'],
                $data['product'],
                $data['start'],
                $data['finish'],
                $data['timezone'],
                $data['discount'],
                $data['cta'],
                $data['cta_url'],
                $data['description']
            );
        }

        if ( $active_only ) {
            $now    = time();
            $start  = $data['start'];
            $finish = $data['finish'];

            if ( ( $now > $start ) && ( $now < $finish ) ) {
                continue;
            }

            unset( $promotions[ $promotion ] );
        }
    }

    return $promotions;
} // kbs_get_current_promotions


/**
 * Callback to sort tabs/fields on priority.
 *
 * @return bool
 * @since 1.6.0
 *
 */
function helptain_sort_data_by_priority( $a, $b ) {
	if ( ! isset( $a['priority'], $b['priority'] ) ) {
		return - 1;
	}
	if ( $a['priority'] == $b['priority'] ) {
		return 0;
	}

	return $a['priority'] < $b['priority'] ? - 1 : 1;
}

/**
 * Callback to sort replies and notes by post_date
 *
 * @return bool
 * @since 1.6.0
 *
 */
function helptain_sort_data_by_date( $a, $b ) {
	$a_time = false;
	$b_time = false;

	if ( isset( $a->post_date ) ) {
		$a_time = $a->post_date;
	}

	if ( isset( $a->comment_date ) ) {
		$a_time = $a->comment_date;
	}

	if ( isset( $b->post_date ) ) {
		$b_time = $b->post_date;
	}

	if ( isset( $b->comment_date ) ) {
		$b_time = $b->comment_date;
	}

	if ( ! $a_time && ! $b_time ) {
		return - 1;
	}

	if ( $a_time == $b_time ) {
		return 0;
	}

	return $a_time > $b_time ? - 1 : 1;
}
