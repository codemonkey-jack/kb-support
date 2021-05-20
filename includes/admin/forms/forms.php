<?php
	defined( 'ABSPATH' ) or die( "Direct access to this page is disabled!!!" );

/**
 * Manage kbs-form posts.
 *
 * @since		1.0
 * @package		KBS
 * @subpackage	Forms
 */

/**
 * Define the columns that should be displayed for the KBS Forms post lists screen
 *
 * @since	1.0
 * @param	arr		$columns	An array of column name ⇒ label. The label is shown as the column header.
 * @return	arr		$columns	Filtered array of column name => label to be shown as the column header.
 */
function kbs_set_kbs_form_post_columns( $columns ) {

	$columns = array(
        'cb'          => '<input type="checkbox" />',
		'title'       => __( 'Name', 'kb-support' ),
		'shortcode'   => __( 'Shortcode', 'kb-support' ),
		'author'      => __( 'Author', 'kb-support' ),
		'fields'      => __( 'Field Count', 'kb-support' ),
		'submissions' => __( 'Submissions', 'kb-support' ),
    );

	return apply_filters( 'kbs_form_post_columns', $columns );

} // kbs_set_kbs_form_post_columns
add_filter( 'manage_kbs_form_posts_columns' , 'kbs_set_kbs_form_post_columns' );

/**
 * Define the data to be displayed within the KBS Forms post custom columns.
 *
 * @since	1.0
 * @param	str		$column_name	The name of the current column for which data should be displayed.
 * @param	int		$post_id		The ID of the current post for which data is being displayed.
 * @return	str
 */
function kbs_set_kbs_form_column_data( $column_name, $post_id ) {

	$kbs_form = new KBS_Form( $post_id );

	switch ( $column_name ) {
		case 'fields':
			echo count( $kbs_form->get_fields() );
			break;

		case 'shortcode':
			echo '<code>' . $kbs_form->get_shortcode() . '</code>';
			break;

		case 'submissions':
			echo $kbs_form->get_submission_count();
			break;
	}

} // kbs_set_kbs_ticket_column_data
add_action( 'manage_kbs_form_posts_custom_column' , 'kbs_set_kbs_form_column_data', 10, 2 );

/**
 * Save the KBS Form custom posts
 *
 * @since	1.0
 * @param	int		$post_id		The ID of the post being saved.
 * @param	obj		$post			The WP_Post object of the post being saved.
 * @param	bool	$update			Whether an existing post if being updated or not.
 * @return	void
 */
function kbs_form_post_save( $post_id, $post, $update )	{

	// Remove the save post action to avoid loops
	remove_action( 'save_post_kbs_form', 'kbs_form_post_save', 10, 3 );

	// Fire the before save action but only if this is not a new article creation (i.e $post->post_status == 'draft')
	if ( $update === true )	{
		do_action( 'kbs_form_before_save', $post_id, $post, $update );
	}

	$redirect        = isset( $_POST['kbs_form_redirect'] ) ? $_POST['kbs_form_redirect'] : kbs_get_option( 'tickets_page' );
	$submission      = isset( $_POST['kbs_form_submission'] ) ? $_POST['kbs_form_submission'] : 'redirect';
	$submission_text = isset( $_POST['kbs_form_submission_text'] ) ? $_POST['kbs_form_submission_text'] : esc_html__( 'Ticket has been created. You can check your tickets [link] here [/link]', 'kb-support' );

	update_post_meta( $post_id, '_redirect_page', $redirect );
	update_post_meta( $post_id, '_submission_action', $submission );
	update_post_meta( $post_id, '_submission_text', $submission_text );

	// Fire the after save action
	do_action( 'kbs_form_after_save', $post_id, $post, $update );

	// Re-add the save post action
	add_action( 'save_post_kbs_form', 'kbs_form_post_save', 10, 3 );
} // kbs_form_post_save
add_action( 'save_post_kbs_form', 'kbs_form_post_save', 10, 3 );

/**
 * Delete all form fields when a form is deleted.
 *
 * This function is fired just before the form is removed from the DB
 * using the before_delete_post hook.
 *
 * @since	1.0
 * @param	int		$form_id		The ID of the post being saved.
 * @return	void
 */
function kbs_delete_all_form_fields( $form_id )	{

	if ( 'kbs_form' != get_post_type( $form_id ) )	{
		return;
	}

	/**
	 * Fires immediately before deleting all form fields
	 *
	 * @since	1.0
	 * @param	int	$form_id
	 */
	do_action( 'kbs_pre_delete_all_form_fields', $form_id );

	$kbs_form = new KBS_Form( $form_id );

	foreach( $kbs_form->fields as $field )	{
		$kbs_form->delete_field( $field->ID );
	}

	/**
	 * Fires immediately after deleting all form fields
	 *
	 * @since	1.0
	 * @param	int	$form_id
	 */
	do_action( 'kbs_post_delete_all_form_fields', $form_id );

} // kbs_delete_all_form_fields
add_action( 'before_delete_post', 'kbs_delete_all_form_fields' );

/**
 * Insert the shortcode after the form title.
 *
 * @since	1.0
 * @param	obj		$post		The WP_Post object.
 * @return	str
 */
function kbs_form_edit_form_after_title()	{
	global $post;

	if ( 'kbs_form' == get_post_type() ) : ?>
    	<?php $kbs_form = new KBS_Form( $post->ID ); ?>
		<input type="text" readonly size="25" onclick="this.focus(); this.select()" id="kbs-form-shortcode" name="kbs_form_shortcode" value='<?php echo $kbs_form->get_shortcode(); ?>' title="<?php _e( 'To copy the shortcode, click here then press Ctrl + C (PC) or Cmd + C (Mac).', 'kb-support' ); ?>" />
	<?php endif;

} // kbs_form_edit_form_after_title
add_action( 'edit_form_top', 'kbs_form_edit_form_after_title' );
