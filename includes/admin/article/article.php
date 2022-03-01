<?php
/**
 * Manage article posts.
 * 
 * @since		1.0
 * @package		KBS
 * @subpackage	Posts
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Remove block editor for article.
 *
 * @since	1.5
 * @param	bool	$block_editor	Whether or not to use block editor
 * @param	string	$post_type		Post type
 * @return	bool	True to use block editor, or false
 */
function kbs_article_remove_block_editor( $block_editor, $post_type )	{
	if ( 'kbs_article' == $post_type )	{
		$block_editor = false;
	}

	return $block_editor;
} // kbs_article_remove_block_editor
add_filter( 'use_block_editor_for_post_type', 'kbs_article_remove_block_editor', 10, 2 );

/**
 * Define the columns that should be displayed for the Article post lists screen
 *
 * @since	1.0
 * @param	arr		$columns	An array of column name ⇒ label. The label is shown as the column header.
 * @return	arr		$columns	Filtered array of column name => label to be shown as the column header.
 */
function kbs_set_article_post_columns( $columns ) {

	$category_labels = kbs_get_taxonomy_labels( 'article_category' );
	$tag_labels      = kbs_get_taxonomy_labels( 'article_tag' );

	$columns = array(
        'cb'               => '<input type="checkbox" />',
		'title'            => __( 'Title', 'kb-support' ),
		'date'             => __( 'Date', 'kb-support' ),
		'article_category' => $category_labels['menu_name'],
        'article_tag'      => $tag_labels['menu_name'],
		'author'           => __( 'Author', 'kb-support' ),
		'views'            => __( 'Views', 'kb-support' ),
		'visibility'       => __( 'Visibility', 'kb-support' ),
		'linked'           => sprintf( __( 'Linked %s', 'kb-support' ), kbs_get_ticket_label_plural() )
    );
	
	return apply_filters( 'kbs_article_post_columns', $columns );
	
} // kbs_set_article_post_columns
add_filter( 'manage_article_posts_columns' , 'kbs_set_article_post_columns' );

/**
 * Define sortable columns.
 *
 * @since	1.0.4
 * @param	arr		$columns	Array of sortable columns
 * @return	arr		Filtered attay of sortable columns
 */
function kbs_set_article_sortable_post_columns( $columns ) {
    $columns['views'] = 'views';
 
    return $columns;
} // kbs_set_article_sortable_post_columns
add_filter( 'manage_edit-article_sortable_columns', 'kbs_set_article_sortable_post_columns' );

/**
 * Define the data to be displayed within the Article post custom columns.
 *
 * @since	1.0
 * @param	str		$column_name	The name of the current column for which data should be displayed.
 * @param	int		$post_id		The ID of the current post for which data is being displayed.
 * @return	str
 */
function kbs_set_articles_column_data( $column_name, $post_id ) {

	switch ( $column_name ) {
		case 'article_category':
			$terms = get_the_terms( $post_id, 'article_category' );
			$links = array();
			if ( ! empty( $terms ) )	{
				foreach ( $terms as $term )	{
					$restricted = '';
					$link = get_term_link( $term, 'article_category' );
					if ( is_wp_error( $link ) )	{
						return $link;
					}
					$links[] = '<a href="' . esc_url( $link ) . '" rel="tag">' . $term->name . '</a> ' . $restricted;
				}
				echo implode( '<br />', $links );
			} else	{
				echo '&mdash;';
			}
			break;

		case 'article_tag':
			$terms = get_the_term_list( $post_id, 'article_tag', '', '<br />' );

			if ( ! empty( $terms ) )	{
				echo $terms;
			} else	{
				echo '&mdash;';
			}
			break;

		case 'views':
			$total   = kbs_get_article_view_count( $post_id );
			$monthly = kbs_get_article_view_count( $post_id, false );

			echo $total . ' / ' . $monthly;
			break;

		case 'visibility':
			if ( kbs_article_is_restricted( $post_id ) )	{
				echo '<span class="padlock" title="' . __( 'This is a restricted article', 'kb-support' ) . '"></span>';
			}
			do_action( 'kbs_article_column_visibility', $post_id );
			break;

		case 'linked':
			$linked_tickets = kbs_get_linked_tickets( $post_id );
			$ticket_ids     = array();

			if ( $linked_tickets )	{

				$linked_tickets = apply_filters( 'kbs_articles_post_column_linked', $linked_tickets, $post_id );

				foreach( $linked_tickets as $ticket_id )	{
					$ticket_ids[] = '<a href="' . kbs_get_ticket_url( $ticket_id, true ) . '">' . $ticket_id . '</a>';
				}

				if ( ! empty( $ticket_ids ) )	{
					echo implode( ', ', $ticket_ids );
				}

			} else	{
				echo '&mdash;';
			}
			break;
			
		default:
			$output = __( 'No callback found for post column', 'kb-support' );
			$output = apply_filters( 'kbs_articles_column_data_' . $column_name, $output, $post_id );
			echo $output;
			break;
	}

} // kbs_set_articles_column_data
add_action( 'manage_article_posts_custom_column' , 'kbs_set_articles_column_data', 10, 2 );

/**
 * Adds a row action to reset view count.
 *
 * @since	1.0
 * @param	arr		$actions	Array of current row actions
 * @param	obj		$post		The article WP_Post object
 * @return	arr		Filtered array of current row actions
 */
function kbs_add_article_row_actions( $actions, $post )	{

	if ( KBS()->KB->post_type == get_post_type( $post ) )	{
		$reset_url = add_query_arg( array(
			'kbs-action' => 'reset_article_views',
			'article_id' => $post->ID
		), admin_url( 'edit.php' ) );

		$actions['reset_views'] = sprintf(
			__( '<a href="%s">Reset Views</a>', 'kb-support' ),
			wp_nonce_url( $reset_url, 'reset_views', 'kbs-nonce' )
		);
	}

	return $actions;

} // kbs_add_article_row_actions
add_filter( 'post_row_actions', 'kbs_add_article_row_actions', 10, 2 );

/**
 * Add Article Filters
 *
 * Adds taxonomy drop down filters for articles.
 *
 * @since	1.0
 * @return	void
 */
function kbs_add_article_filters() {
	global $typenow;

	if ( 'kbs_article' == $typenow ) {
		$terms = get_terms( 'article_category' );

		if ( is_array( $terms ) && count( $terms ) > 0 )	{
			$category_labels = kbs_get_taxonomy_labels( 'article_category' );

			echo "<select name='article_category' id='article_category' class='postform'>";
				echo "<option value=''>" . sprintf( __( 'Show all %s', 'kb-support' ), strtolower( $category_labels['name'] ) ) . "</option>";

				foreach ( $terms as $term )	{
					$selected = isset( $_GET['article_category'] ) && $_GET['article_category'] == $term->slug ? ' selected="selected"' : '';
					echo '<option value="' . esc_attr( $term->slug ) . '"' . $selected . '>' . esc_html( $term->name ) .' (' . $term->count .')</option>';
				}

			echo "</select>";
		}

		$terms = get_terms( 'article_tag' );
		if ( is_array( $terms ) && count( $terms ) > 0 )	{
			$tag_labels = kbs_get_taxonomy_labels( 'article_tag' );

			echo "<select name='article_tag' id='article_tag' class='postform'>";
				echo "<option value=''>" . sprintf( __( 'Show all %s', 'kb-support' ), strtolower( $tag_labels['name'] ) ) . "</option>";

				foreach ( $terms as $term ) {
					$selected = isset( $_GET['article_tag'] ) && $_GET['article_tag'] == $term->slug ? ' selected="selected"' : '';
					echo '<option value="' . esc_attr( $term->slug ) . '"' . $selected . '>' . esc_html( $term->name ) .' (' . $term->count .')</option>';
				}

			echo "</select>";
		}

		if ( isset( $_REQUEST['all_posts'] ) && '1' === $_REQUEST['all_posts'] )	{

			echo '<input type="hidden" name="all_posts" value="1" />';

		} elseif ( ! current_user_can( 'view_ticket_reports' ) )	{

			$author_id = get_current_user_id();
			echo '<input type="hidden" name="author" value="' . esc_attr( $author_id ) . '" />';

		}
	}

} // kbs_add_article_filters
add_action( 'restrict_manage_posts', 'kbs_add_article_filters', 100 );

/**
 * Order posts by custom columns.
 *
 * @since	1.0.4
 */
function kbs_article_posts_orderby_by_custom_column( $query )	{
	global $pagenow;

    if ( ! is_admin() || 'edit.php' != $pagenow || ! $query->is_main_query() || KBS()->KB->post_type != $query->get( 'post_type' ) )	{
        return;
	}

    $orderby = $query->get( 'orderby' );

	switch ( $orderby )	{
		case 'views':
			$query->set( 'meta_key', kbs_get_article_view_count_meta_key_name() );
			$query->set( 'orderby', 'meta_value_num' );
			break;

		default:
			do_action( 'kbs_article_posts_orderby_by_custom_column_' . $orderby, $query );
	}

} // kbs_article_posts_orderby_by_custom_column
add_action( 'pre_get_posts', 'kbs_article_posts_orderby_by_custom_column' );

/**
 * Save the Article custom posts
 *
 * @since	1.0
 * @param	int		$post_id		The ID of the post being saved.
 * @param	obj		$post			The WP_Post object of the post being saved.
 * @param	bool	$update			Whether an existing post if being updated or not.
 *
 * @return	void
 */
function kbs_article_post_save( $post_id, $post, $update )	{	

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )	{
		return;
	}

	if ( isset( $post->post_type ) && 'revision' == $post->post_type ) {
		return;
	}

	// Remove the save post action to avoid loops
	remove_action( 'save_post_article', 'kbs_article_post_save', 10, 3 );

	if ( ! $update )	{
		$total_key = kbs_get_article_view_count_meta_key_name();
		$month_key = kbs_get_article_view_count_meta_key_name( false );

		add_post_meta( $post_id, $total_key, 0, true );
		add_post_meta( $post_id, $month_key, 0, true );
	}

	$url = remove_query_arg( 'kbs-message' );

	if (
		! isset( $_POST['kbs_article_meta_box_nonce'] )
		|| ! wp_verify_nonce( $_POST['kbs_article_meta_box_nonce'], 'kbs_article_meta_save' )
	)	{
		return;
	}

	// Fire the before save action but only if this is not a new article creation (i.e $post->post_status == 'draft')
	if ( $update === true )	{
		do_action( 'kbs_article_before_save', $post_id, $post, $update );
	}

	$fields = kbs_article_metabox_fields();

	foreach( $fields as $field )	{
		$posted_value = '';

		if ( ! empty( $_POST[ $field ] ) ) {

			if ( is_string( $_POST[ $field ] ) )	{
				$posted_value = sanitize_text_field( $_POST[ $field ] );
			} elseif ( is_int( $_POST[ $field ] ) )	{
				$posted_value = $_POST[ $field ];
			} elseif( is_array( $_POST[ $field ] ) )	{
				$posted_value = array_map( 'absint', $_POST[ $field ] );
			}
		}

		$new_value = apply_filters( 'kbs_article_metabox_save_' . $field, $posted_value );

		if ( ! empty( $new_value ) ) {
			update_post_meta( $post_id, $field, $new_value );
		} else {
			delete_post_meta( $post_id, $field );
		}
	}

	// Fire the after save action
	do_action( 'kbs_article_after_save', $post_id, $post, $update );

	// Re-add the save post action
	add_action( 'save_post_article', 'kbs_article_post_save', 10, 3 );
}
add_action( 'save_post_article', 'kbs_article_post_save', 10, 3 );
