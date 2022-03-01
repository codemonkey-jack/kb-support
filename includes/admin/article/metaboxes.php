<?php	
/**
 * Manage article post metaboxes.
 * 
 * @since		1.0
 * @package		KBS
 * @subpackage	Functions/Metaboxes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Returns default KBS Article meta fields.
 *
 * @since	1.0
 * @return	arr		$fields		Array of fields.
 */
function kbs_article_metabox_fields() {

	$fields = array(
		'_kbs_article_restricted'
	);

	return apply_filters( 'kbs_article_metabox_fields_save', $fields );

} // kbs_article_metabox_fields

/**
 * Remove unwanted metaboxes.
 *
 * @since   1.5.1
 * return   void
 */
function kbs_article_remove_metaboxes()    {
    $remove_metaboxes = array(
        'postcustom' => 'normal'
    );

    $remove_metaboxes = apply_filters( 'kbs_article_remove_metaboxes', $remove_metaboxes );

    foreach( $remove_metaboxes as $metabox => $priority )   {
        remove_meta_box( $metabox, 'kbs_article', $priority );
    }
} // kbs_form_remove_metaboxes
add_action( 'admin_head', 'kbs_article_remove_metaboxes', PHP_INT_MAX );

/**
 * Define and add the metaboxes for the article post type.
 *
 * @since	1.0
 * @return	void
 */
function kbs_article_add_meta_boxes( $post )	{
	global $kbs_article_update;

	$kbs_article_update = false;
	
	if ( 'draft' != $post->post_status && 'auto-draft' != $post->post_status )	{
		$kbs_article_update = true;
	}

	add_meta_box(
		'kbs-article-metabox-linked-tickets',
		sprintf( __( 'Linked %s', 'kb-support' ), kbs_get_ticket_label_plural() ),
		'kbs_article_metabox_linked_tickets_callback',
		KBS()->KB->post_type,
		'normal',
		'high',
		array()
	);

	add_meta_box(
		'kbs-article-metabox-restrictions',
		__( 'Restrictions', 'kb-support' ),
		'kbs_article_metabox_restrictions_callback',
		KBS()->KB->post_type,
		'side',
		'',
		array()
	);
} // kbs_article_add_meta_boxes
add_action( 'add_meta_boxes_article', 'kbs_article_add_meta_boxes' );

/**
 * The callback function for the linked tickets metabox.
 *
 * @since	1.0
 * @global	obj		$post				WP_Post object
 * @global	bool	$kbs_ticket_update	True if this article is being updated, false if new
 * @return	void
 */
function kbs_article_metabox_linked_tickets_callback()	{
	global $post, $kbs_article_update;

	/*
	 * Output the items for the linked tickets metabox
	 * @since	1.0
	 * @param	int	$post_id	The KB post ID
	 */
	do_action( 'kbs_article_linked_tickets_fields', $post->ID );
} // kbs_article_metabox_linked_tickets_callback

/**
 * The callback function for the article restrictions metabox.
 *
 * @since	1.0
 * @global	obj		$post				WP_Post object
 * @global	bool	$kbs_ticket_update	True if this article is being updated, false if new
 * @return	void
 */
function kbs_article_metabox_restrictions_callback()	{
	global $post, $kbs_article_update;

	wp_nonce_field( 'kbs_article_meta_save', 'kbs_article_meta_box_nonce' );

	/*
	 * Output the items for the options metabox
	 * @since	1.0
	 * @param	int	$post_id	The KB post ID
	 */
	do_action( 'kbs_article_restrictions_fields', $post->ID );
} // kbs_article_metabox_restrictions_callback

/**
 * Display the Article Linked Tickets metabox.
 *
 * @since	1.0
 * @global	bool	$kbs_ticket_update	True if this article is being updated, false if new.
 * @param	int		$post_id			The KB post ID.
 * @return	str
 */
function kbs_article_metabox_linked_tickets_fields( $post_id )	{
	global $kbs_article_update;

	$linked_tickets = kbs_get_linked_tickets( $post_id );

	if ( ! empty( $linked_tickets ) ) : ?>

    	<?php foreach( $linked_tickets as $linked_ticket ) : ?>
			<p><a href="#TB_inline?width=600&height=550&inlineId=kbs-ticket-content-<?php echo $linked_ticket; ?>" class="thickbox" title="<?php echo get_the_title( $linked_ticket ); ?>">
            	# <?php echo kbs_get_ticket_number( $linked_ticket ) . '</a> - ' . get_the_title( $linked_ticket ); ?>
            </p>

			<div id="kbs-ticket-content-<?php echo $linked_ticket; ?>" class="kbs-hidden">
				<?php do_action( 'kbs_article_before_thickbox_ticket_content', $linked_ticket, $post_id ); ?>
                <?php echo wpautop( get_post_field( 'post_content', $linked_ticket ) ); ?>
                <?php do_action( 'kbs_article_after_thickbox_ticket_content', $linked_ticket, $post_id ); ?>
            </div>

        <?php endforeach; ?>

    <?php else : ?>
    		<p><?php printf( __( 'There are no %s linked to this article.', 'kb-support' ), kbs_get_ticket_label_plural( true ) ); ?></p>
    <?php endif;

} // kbs_article_metabox_linked_tickets_fields
add_action( 'kbs_article_linked_tickets_fields', 'kbs_article_metabox_linked_tickets_fields', 10, 1 );

/**
 * Display the Article restrict article field.
 *
 * @since	1.0
 * @global	bool	$kbs_ticket_update	True if this article is being updated, false if new.
 * @param	int		$post_id			The KB post ID.
 * @return	str
 */
function kbs_article_metabox_restrict_article_field( $post_id )	{
	global $kbs_article_update;

	if ( 'auto-draft' == get_post_status( $post_id ) )	{
		$logged_in_only = kbs_get_option( 'article_restricted', false );
	} else	{	
		$logged_in_only = get_post_meta( $post_id, '_kbs_article_restricted', true );
	}

	?>
    <p><?php echo KBS()->html->checkbox( array(
        'name'    => '_kbs_article_restricted',
        'current' => $logged_in_only
    ) ); ?> <label for="_kbs_article_restricted"></label><?php _e( 'Restrict access?', 'kb-support' ); ?></p>

    <?php
} // kbs_article_metabox_restrict_article_field
add_action( 'kbs_article_restrictions_fields', 'kbs_article_metabox_restrict_article_field', 10, 1 );
