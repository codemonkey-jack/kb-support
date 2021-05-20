<?php

/**
 * Contains all metabox functions for the kbs_form post type
 *
 * @package		KBS
 * @subpackage	Forms
 * @since		1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Render the option for setting the redirect on submission setting.
 *
 * @since   1.1.6
 * @param   obj     $post   WP_Post object
 * @return  void
 */
function kbs_render_form_field_redirect_setting( $post )    {
	if ( 'kbs_form' == $post->post_type )	{
		$selected = get_post_meta( $post->ID, '_redirect_page', true );
		?>
		<div id="kbs_form_submission_wrapper" class="misc-pub-section curtime misc-pub-redirect">
			<label for="kbs_form_submission"
				   class="screen-reader-text"><?php _e( 'Redirect to', 'kb-support' ) ?></label>
			<?php _e( 'After submission action:', 'kb-support' ); ?><br>
			<?php echo KBS()->html->select( array(
				'name'             => 'kbs_form_submission',
				'selected'         => 'redirect',
				'chosen'           => true,
				'show_option_all'  => false,
				'show_option_none' => false,
				'options'          => array(
					'redirect' => esc_html__( 'Redirect to page', 'kb-support' ),
					'text'     => esc_html__( 'Display text', 'kb-support' )
				),
				'selected'         => kbs_get_form_submission_options( $post->ID ),
			) ); ?>
		</div>

		<div id="kbs_form_redirect_wrapper" class="misc-pub-section curtime misc-pub-redirect">
			<label for="kbs_form_redirect" class="screen-reader-text"><?php _e( 'Redirect to', 'kb-support' ) ?></label>
			<?php _e( 'Redirect after submission to:', 'kb-support' ); ?><br>
			<?php echo KBS()->html->select( array(
				'name'             => 'kbs_form_redirect',
				'selected'         => 0,
				'chosen'           => true,
				'show_option_all'  => false,
				'show_option_none' => false,
				'options'          => kbs_get_pages( true ),
				'selected'         => kbs_get_form_redirect_target( $post->ID ),
				'data'             => array(
					'search-type'        => 'pages',
					'search-placeholder' => __( 'Type to search all pages', 'kb-support' ),
				)
			) ); ?>
		</div>

		<div id="kbs_form_submission_text_wrapper" class="misc-pub-section curtime misc-pub-redirect">
			<label for="kbs_form_submission_text" class="screen-reader-text"><?php _e( 'Redirect to', 'kb-support' ) ?></label>
			<?php _e( 'Text after form submission:', 'kb-support' ); ?><br>
			<?php echo KBS()->html->textarea( array(
				'name'     => 'kbs_form_submission_text',
				'value'    => esc_html__( 'Ticket has been created. You can check your tickets [link] here [/link]', 'kb-support' ),
				'class'    => 'large-text',
				'disabled' => false,
				'rows'     => 30,
				'cols'     => 20,
				'desc'     => esc_html__( 'Use [link]text[/link] to anchor the text, where the anchor will be the ticket manager page.', 'kb-support' )
			) ); ?>
		</div>
		<?php
	}
} // kbs_render_form_field_redirect_setting
add_action( 'post_submitbox_misc_actions', 'kbs_render_form_field_redirect_setting' );

/**
 * Remove unwanted metaboxes.
 *
 * @since   1.5.1
 * return   void
 */
function kbs_form_remove_metaboxes()    {
    $remove_metaboxes = array(
        'postcustom' => 'normal'
    );

    $remove_metaboxes = apply_filters( 'kbs_forms_remove_metaboxes', $remove_metaboxes );

    foreach( $remove_metaboxes as $metabox => $priority )   {
        remove_meta_box( $metabox, 'kbs_form', $priority );
    }
} // kbs_form_remove_metaboxes
add_action( 'admin_head', 'kbs_form_remove_metaboxes', PHP_INT_MAX );

/**
 * Define and add the metaboxes for the kbs_form post type.
 * Uses function_exists to verify the callback function exists.
 *
 * @since	1.0
 * @param
 * @return
 */
function kbs_form_add_meta_boxes( $post )	{
	add_meta_box(
		'kbs_form_fields_mb',
		__( 'Form Fields', 'kb-support' ),
		'kbs_form_fields_mb_callback',
		'kbs_form',
		'normal',
		'high'
	);

	add_meta_box(
		'kbs_form_add_field_mb',
		__( 'Add a New Field', 'kb-support' ),
		$post->post_status != 'auto-draft' ? 'kbs_form_add_field_mb_callback' : 'kbs_form_not_ready_mb_callback',
		'kbs_form',
		'side',
		'high'
	);

} // kbs_form_add_meta_boxes
add_action( 'add_meta_boxes_kbs_form', 'kbs_form_add_meta_boxes' );

/**
 * Render form fields meta box.
 *
 * @since	1.0
 * @param	obj		$post		The form post object (WP_Post).
 * @return
 */
function kbs_form_fields_mb_callback( $post )	{

	global $post;

	/*
	 * Output the form fields
	 * @since	1.0
	 */
	do_action( 'kbs_form_mb_form_fields', $post->ID );

} // kbs_form_fields_mb_callback

/**
 * Render add field meta box.
 *
 * @since	1.0
 * @param	obj		$post		The form post object (WP_Post).
 * @param	arr		$args		Arguments passed to metabox.
 * @return
 */
function kbs_form_add_field_mb_callback( $post, $args )	{

	global $post;

	/*
	 * Output the new field form
	 * @since	1.0
	 */
	do_action( 'kbs_form_mb_add_form_field', $post->ID, $args );

} // kbs_form_add_field_mb_callback

/**
 * Render form not ready meta box.
 *
 * This meta box is displayed when a form is initially created
 * and set to the 'auto-draft' status.
 *
 * @since	1.0
 * @param	obj		$post		The form post object (WP_Post).
 * @param	arr		$args		Arguments passed to metabox.
 * @return
 */
function kbs_form_not_ready_mb_callback( $post, $args )	{

	?>
   <p><i class="fas fa-exclamation-circle" aria-hidden="true"></i> <?php _e( 'Please save or publish your form before adding fields.', 'kb-support' ); ?></p>
    <?php

} // kbs_form_not_ready_mb_callback

/**
 * Output the existing form fields.
 *
 * @since	1.0
 * @param	int		$post_id	The form post ID.
 * @return	str
 */
function kbs_display_meta_box_form_fields( $post_id )	{

	$form   = new KBS_Form( $post_id );

	?>
	<div id="kbs_form_fields" class="kbs_meta_table_wrap">
        <table class="widefat kbs_sortable_table" width="100%" cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th style="width: 20px"></th>
                    <th style="width: 20%"><?php _e( 'Field Label', 'kb-support' ); ?></th>
                    <th style="width: 20%"><?php _e( 'Type', 'kb-support' ); ?></th>
                    <th style="width: 30%" class="settings"><?php _e( 'Settings', 'kb-support' ); ?></th>
                    <th style="width: 25%"><?php _e( 'Actions', 'kb-support' ); ?></th>
                    <?php do_action( 'kbs_form_field_table_head', $post_id ); ?>
                </tr>
            </thead>
            <tbody>
            <?php if ( is_array( $form->fields ) ) : ?>
            	<?php foreach( $form->fields as $field ) : ?>
                    <tr id="<?php echo 'fields_' . $field->ID; ?>" class="kbs_field_wrapper kbs_sortable_row" data-key="<?php echo esc_attr( $field->ID ); ?>">
                        <?php do_action( 'kbs_render_field_row', $field, $form ); ?>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
            	<tr>
            		<td colspan="5"><?php _e( 'No fields exist for this form', 'kb-support' ); ?></td>
                </tr>
            <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th style="width: 20px"></th>
                    <th style="width: 30%"><?php _e( 'Field Label', 'kb-support' ); ?></th>
                    <th style="width: 20%"><?php _e( 'Type', 'kb-support' ); ?></th>
                    <th style="width: 30%" class="settings"><?php _e( 'Settings', 'kb-support' ); ?></th>
                    <th style="width: 20%"><?php _e( 'Actions', 'kb-support' ); ?></th>
                    <?php do_action( 'kbs_form_field_table_foot', $post_id ); ?>
                </tr>
            </tfoot>
        </table>
    </div>
	<?php

} // kbs_display_meta_box_form_fields
add_action( 'kbs_form_mb_form_fields', 'kbs_display_meta_box_form_fields', 10 );

/**
 * Render the form field row.
 *
 * @since	1.0
 * @param	obj		$field	The WP_Post object for the field.
 * @param	obj		$form	The WP_Post object for the form.
 * return	void
 */
function kbs_render_form_field_row( $field, $form )	{

	$settings = $form->get_field_settings( $field->ID );

	$url  = remove_query_arg( array( 'edit_field', 'delete_field', 'kbs-message', 'kbs-action-nonce' ) );

	$edit = wp_nonce_url(
		add_query_arg(
			array(
				'kbs-action'  => 'edit_form_field',
				'field_id'    => $field->ID,
				'kbs-message' => 'editing_field'
			),
			$url
		),
		'edit_form_field',
		'kbs-action-nonce'
	);

	$delete = wp_nonce_url(
		add_query_arg(
			array(
				'kbs-action' => 'delete_form_field',
				'field_id'   => $field->ID
			),
			$url
		),
		'delete_form_field',
		'kbs-action-nonce'
	);

	?>
    <td><span class="kbs_draghandle"></span></td>
	<?php echo KBS()->html->hidden( array(
        'name'  => 'kbs_form_field[' . $field->ID . '][index]',
        'value' => $field->menu_order,
        'class' => 'kbs_sortable_index'
    ) ); ?>

    <td><?php echo $field->post_title; ?>
		<?php if ( ! empty( $settings['description'] ) ) : ?>
        	<br />
            <span class="description"><?php echo esc_html( $settings['description'] ); ?></span>
        <?php endif; ?>
    </td>

    <td><?php echo kbs_get_field_type( $settings['type'] ); ?></td>

    <td><?php echo kbs_display_field_setting_icons( $field->ID ); ?></td>

    <td>
    	<a href="<?php echo $edit; ?>" class="button button-primary button-small"><?php _e( 'Edit', 'kb-support' ); ?></a>

        <?php if ( kbs_can_delete_field( $field->ID ) ) : ?>
	        <a href="<?php echo $delete; ?>" class="button button-secondary button-small"><?php _e( 'Delete', 'kb-support' ); ?></a>
        <?php endif; ?>
    </td>

    <?php
} // kbs_render_form_field_row
add_action( 'kbs_render_field_row', 'kbs_render_form_field_row', 10, 2 );

/**
 * Render the row for entering the label.
 *
 * @since	1.0
 * @param	int		$post_id	The form post ID.
 * @param	arr		$args		Function arguments
 * @return	str
 */
function kbs_render_field_label_row( $post_id, $args )	{
	global $kbs_edit_field;

	kbs_maybe_editing_field();

	?>

	<div id="kbs_meta_field_label_wrap">
		<p><strong><?php _e( 'Label', 'kb-support' ); ?></strong><br />
		<label for="kbs_field_label">
			<?php echo KBS()->html->text( array(
				'name'  => 'kbs_field_label',
				'value' => ! empty( $kbs_edit_field ) ? esc_attr( get_the_title( $kbs_edit_field->ID ) ) : null,
				'class' => 'kbs_input'
			) ); ?>
		</label></p>
	</div>
	<?php

} // kbs_render_field_label_row
add_action( 'kbs_form_mb_add_form_field', 'kbs_render_field_label_row', 10, 2 );

/**
 * Render the row for entering the description.
 *
 * @since	1.0
 * @param	int		$post_id	The form post ID.
 * @param	arr		$args		Function arguments
 * @return	str
 */
function kbs_render_field_description_row( $post_id, $args )	{
	global $kbs_edit_field;

	kbs_maybe_editing_field();

	$checked_label = empty( $kbs_edit_field->settings['description_pos'] ) || 'label' == $kbs_edit_field->settings['description_pos'] ? ' checked="checked"' : '';

	$checked_field = '';
	if ( ! empty( $kbs_edit_field->settings['description_pos'] ) && 'field' == $kbs_edit_field->settings['description_pos'] )	{
		$checked_field = ' checked="checked"';
	}

	?>

	<div id="kbs_meta_field_description_wrap">
		<p><strong><?php _e( 'Description', 'kb-support' ); ?></strong><br />
		<label for="kbs_field_description">
			<?php echo KBS()->html->text( array(
				'name'  => 'kbs_field_description',
				'value' => ! empty( $kbs_edit_field ) ? $kbs_edit_field->settings['description'] : null,
				'class' => 'kbs_input'
			) ); ?>
		</label><br />
		<input type="radio" name="kbs_field_description_pos" value="label"<?php echo $checked_label; ?>><span class="description"><?php _e( 'After Label', 'kb-support' ); ?></span>
        &nbsp;&nbsp;&nbsp;
        <input type="radio" name="kbs_field_description_pos" value="field"<?php echo $checked_field; ?>><span class="description"><?php _e( 'After Field', 'kb-support' ); ?></span></p>
	</div>
	<?php

} // kbs_render_field_label_row
add_action( 'kbs_form_mb_add_form_field', 'kbs_render_field_description_row', 15, 2 );

/**
 * Render the row for selecting the type.
 *
 * @since	1.0
 * @param	int		$post_id	The form post ID.
 * @param	arr		$args		Function arguments
 * @return	str
 */
function kbs_render_field_type_row( $post_id, $args )	{
	global $kbs_edit_field;

	?>
	<div id="kbs_meta_field_type_wrap">
		<p><strong><?php _e( 'Type', 'kb-support' ); ?></strong><br />
		<label for="kbs_field_type">
			<?php echo KBS()->html->field_types_dropdown( array(
				'selected' => ! empty( $kbs_edit_field ) ? $kbs_edit_field->settings['type'] : 0
			) ); ?>
		</label></p>
	</div>
	<?php

} // kbs_render_field_label_row
add_action( 'kbs_form_mb_add_form_field', 'kbs_render_field_type_row', 20, 2 );

/**
 * Render the row for selecting the mapping.
 *
 * @since	1.0
 * @param	int		$post_id	The form post ID.
 * @param	arr		$args		Function arguments
 * @return	str
 */
function kbs_render_field_mapping_row( $post_id, $args )	{
	global $kbs_edit_field;

	$options = kbs_get_available_mappings( $post_id );

	if ( ! empty( $kbs_edit_field->settings['mapping'] ) )	{
		$options[ $kbs_edit_field->settings['mapping'] ] = kbs_get_mappings( $kbs_edit_field->settings['mapping'] );
	}

	?>
	<div id="kbs_meta_field_mapping_wrap">
		<p><strong><?php _e( 'Maps to', 'kb-support' ); ?></strong><br />
		<label for="kbs_field_type">
			<?php echo KBS()->html->select( array(
				'name'             => 'kbs_field_mapping',
				'selected'         => ! empty( $kbs_edit_field->settings['mapping'] ) ? $kbs_edit_field->settings['mapping'] : 0,
				'class'            => 'kbs_field_mapping',
				'show_option_all'  => false,
				'show_option_none' =>'',
                'chosen'           => true,
				'options'          => $options,
                'data'             => array(
                    'search-type'        => 'fields',
                    'search-placeholder' => __( 'Type to search all mappings', 'kb-support' ),
                )
			) ); ?>
		</label></p>
	</div>
	<?php

} // kbs_render_field_mapping_row
add_action( 'kbs_form_mb_add_form_field', 'kbs_render_field_mapping_row', 25, 2 );

/**
 * Render the row for setting the default value.
 *
 * @since	1.0
 * @param	int		$post_id	The form post ID.
 * @param	arr		$args		Function arguments
 * @return	str
 */
function kbs_render_field_value_row( $post_id, $args )	{
	global $kbs_edit_field;

	$options = kbs_get_available_mappings( $post_id );

	if ( ! empty( $kbs_edit_field->settings['mapping'] ) )	{
		$options[ $kbs_edit_field->settings['mapping'] ] = kbs_get_mappings( $kbs_edit_field->settings['mapping'] );
	}

	?>
	<div id="kbs_meta_field_value_wrap">
		<p><strong><?php _e( 'Initial Value', 'kb-support' ); ?></strong><br />
		<label for="kbs_field_value">
			<?php echo KBS()->html->text( array(
				'name'  => 'kbs_field_value',
				'value' => ! empty( $kbs_edit_field->settings['value'] ) ? esc_html( $kbs_edit_field->settings['value'] ) : null,
				'class' => 'kbs_input'
			) ); ?>
		</label></p>
	</div>
	<?php

} // kbs_render_field_value_row
add_action( 'kbs_form_mb_add_form_field', 'kbs_render_field_value_row', 30, 2 );

/**
 * Render the row for setting as required.
 *
 * @since	1.0
 * @param	int		$post_id	The form post ID.
 * @param	arr		$args		Function arguments
 * @return	str
 */
function kbs_render_field_required_row( $post_id, $args )	{
	global $kbs_edit_field;

	$required = false;

	$default = get_post_meta( $kbs_edit_field, '_default_field', true );

	if ( 'email' == $default )	{
		$required = true;
	}

	do_action( 'kbs_form_mb_field_options', $post_id, $args );

	if ( empty( $required ) ) : ?>
        <div id="kbs_meta_field_required_wrap">
            <p><label for="kbs_field_required">
                <?php echo KBS()->html->checkbox( array(
                    'name'    => 'kbs_field_required',
                    'current' => ! empty( $kbs_edit_field->settings['required'] ) ? $kbs_edit_field->settings['required'] : null
                ) ); ?>
                <strong><?php _e( 'Required?', 'kb-support' ); ?></strong></label></p>
        </div>
    <?php else : ?>
    	<?php echo KBS()->html->hidden( array(
			'name'  => 'kbs_field_required',
			'value' => '1'
		) );
    endif;

} // kbs_render_field_required_row
add_action( 'kbs_form_mb_add_form_field', 'kbs_render_field_required_row', 35, 2 );

/**
 * Render the row for entering the label class.
 *
 * @since	1.0
 * @param	int		$post_id	The form post ID.
 * @param	arr		$args		Function arguments
 * @return	str
 */
function kbs_render_field_label_class_row( $post_id, $args )	{
	global $kbs_edit_field;

	?>
	<div id="kbs_meta_field_label_class_wrap">
		<p><strong><?php _e( 'Label Class', 'kb-support' ); ?></strong><br />
		<label for="kbs_field_label_class">
			<?php echo KBS()->html->text( array(
				'name'  => 'kbs_field_label_class',
				'value' => ! empty( $kbs_edit_field->settings['label_class'] ) ? $kbs_edit_field->settings['label_class'] : null,
				'class' => 'kbs_input'
			) ); ?>
		</label></p>
	</div>
	<?php

} // kbs_render_field_label_row
add_action( 'kbs_form_mb_add_form_field', 'kbs_render_field_label_class_row', 40, 2 );

/**
 * Render the row for entering the input class.
 *
 * @since	1.0
 * @param	int		$post_id	The form post ID.
 * @param	arr		$args		Function arguments
 * @return	str
 */
function kbs_render_field_input_class_row( $post_id, $args )	{
	global $kbs_edit_field;

	?>
	<div id="kbs_meta_field_input_class_wrap">
		<p><strong><?php _e( 'Input Class', 'kb-support' ); ?></strong><br />
		<label for="kbs_field_input_class">
			<?php echo KBS()->html->text( array(
				'name'  => 'kbs_field_input_class',
				'value' => ! empty( $kbs_edit_field->settings['input_class'] ) ? $kbs_edit_field->settings['input_class'] : null,
				'class' => 'kbs_input'
			) ); ?>
		</label></p>
	</div>
	<?php

} // kbs_render_field_label_row
add_action( 'kbs_form_mb_add_form_field', 'kbs_render_field_input_class_row', 45, 2 );

/**
 * Render the row for adding the new field.
 *
 * @since	1.0
 * @param	int		$post_id	The form post ID.
 * @param	arr		$args		Function arguments
 * @return	str
 */
function kbs_render_field_add_field_btn_row( $post_id, $args )	{
	global $kbs_edit_field;

	$cancel = remove_query_arg( array(
		'edit_field', 'delete_field', 'kbs-message', 'kbs-action-nonce'
	), wp_get_referer() );

	?>
	<div id="kbs_meta_field_add_form_btn_wrap">
		<?php echo KBS()->html->hidden( array(
			'name'  => 'form_return_url',
			'value' => remove_query_arg( array( 'kbs-message', 'kbs-action-nonce', 'kbs-action', 'field_id' ) )
		) ); ?>

        <?php if ( ! $kbs_edit_field ) : ?>

            <span id="kbs-field-add">
                <a id="kbs-add-form-field" class="button-primary kbs_add" style="margin: 6px 0 10px;"><?php _e( 'Add Field', 'kb-support' ); ?></a>
            </span>

        <?php else : ?>
			<?php echo KBS()->html->hidden( array(
				'name'  => 'kbs_edit_field',
				'value' => $kbs_edit_field->ID,
				'class' => 'kbs_sortable_index'
			) ); ?>
            <span id="kbs-field-save">
                <a id="kbs-save-form-field" class="button-primary kbs_save" style="margin: 6px 0 10px;"><?php _e( 'Edit', 'kb-support' ); ?></a>
                <a href="<?php echo $cancel; ?>" id="kbs-cancel" class="button-secondary kbs_cancel" style="margin: 6px 15px 10px;"><?php _e( 'Cancel', 'kb-support' ); ?></a>
            </span>

        <?php endif; ?>

        <span id="kbs-loading" class="kbs-loader kbs-hidden"><img src="<?php echo KBS_PLUGIN_URL . 'assets/images/loading.gif'; ?>" /></span>
	</div>
	<?php

} // kbs_render_field_label_row
add_action( 'kbs_form_mb_add_form_field', 'kbs_render_field_add_field_btn_row', 90, 2 );

/**
 * Render the rows for setting field options.
 *
 * @since	1.0
 * @param	int		$post_id	The form post ID.
 * @param	arr		$args		Function arguments
 * @return	str
 */
function kbs_render_field_options_rows( $post_id )	{
	global $kbs_edit_field;

	?>

    <div id="kbs_meta_field_kb_search_wrap">
    	<p><label for="kbs_field_kb_search">
			<?php echo KBS()->html->checkbox( array(
				'name'        => 'kbs_field_kb_search',
				'current' => ! empty( $kbs_edit_field->settings['kb_search'] ) ? $kbs_edit_field->settings['kb_search'] : null
			) ); ?>
			<strong><?php printf( __( 'Enable %s Ajax Search?', 'kb-support' ), kbs_get_article_label_plural() ); ?></strong></label>
        </p>
    </div>

    <div id="kbs_meta_field_select_options_wrap">
    	<p><strong><?php _e( 'Options', 'kb-support' ); ?></strong><br />
		<label for="kbs_field_select_options">
			<?php echo KBS()->html->textarea( array(
				'name'        => 'kbs_field_select_options',
				'value'       => ! empty( $kbs_edit_field->settings['select_options'] ) ? implode( "\n", $kbs_edit_field->settings['select_options'] ) : null,
				'placeholder' => __( 'One entry per line', 'kb-support' ),
				'class'       => 'kbs_input'
			) ); ?>
		</label></p>
    </div>

    <div id="kbs_meta_field_select_multiple_wrap">
    	<p><label for="kbs_field_select_multiple">
			<?php echo KBS()->html->checkbox( array(
				'name'        => 'kbs_field_select_multiple',
				'current' => ! empty( $kbs_edit_field->settings['select_multiple'] ) ? $kbs_edit_field->settings['select_multiple'] : null
			) ); ?>
			<strong><?php _e( 'Multiple Select?', 'kb-support' ); ?></strong></label>
        </p>
    </div>

    <div id="kbs_meta_field_option_selected_wrap">
    	<p><label for="kbs_field_option_selected">
			<?php echo KBS()->html->checkbox( array(
				'name' => 'kbs_field_option_selected',
				'current' => ! empty( $kbs_edit_field->settings['selected'] ) ? $kbs_edit_field->settings['selected'] : null
			) ); ?>
			<strong><?php _e( 'Initially Selected?', 'kb-support' ); ?></strong></label>
        </p>
    </div>

    <div id="kbs_meta_field_select_blank_wrap">
    	<p><label for="kbs_field_select_blank">
			<?php echo KBS()->html->checkbox( array(
				'name' => 'kbs_field_select_blank',
				'current' => ! empty( $kbs_edit_field->settings['blank'] ) ? $kbs_edit_field->settings['blank'] : null
			) ); ?>
			<strong><?php _e( 'Blank First?', 'kb-support' ); ?></strong></label>
        </p>
    </div>

    <div id="kbs_meta_field_select_searchable_wrap">
    	<p><label for="kbs_field_select_chosen">
			<?php echo KBS()->html->checkbox( array(
				'name' => 'kbs_field_select_chosen',
				'current' => ! empty( $kbs_edit_field->settings['chosen'] ) ? $kbs_edit_field->settings['chosen'] : null
			) ); ?>
			<strong><?php _e( 'Searchable?', 'kb-support' ); ?></strong></label>
        </p>
    </div>

    <div id="kbs_meta_field_select_search_text_wrap">
    	<p><strong><?php _e( 'Search Text', 'kb-support' ); ?></strong><br>
        <label for="kbs_field_select_chosen_search">
			<?php echo KBS()->html->text( array(
				'name'  => 'kbs_field_select_chosen_search',
				'value' => ! empty( $kbs_edit_field->settings['chosen_search'] ) ? $kbs_edit_field->settings['chosen_search'] : null,
                'class' => 'kbs_input',
                'placeholder' => __( 'Optional search text', 'kb-support' )
			) ); ?>
        </label></p>
    </div>

    <div id="kbs_meta_field_placeholder_wrap">
    	<p><strong><?php _e( 'Placeholder', 'kb-support' ); ?></strong><br />
		<label for="kbs_field_placeholder">
			<?php echo KBS()->html->text( array(
				'name'  => 'kbs_field_placeholder',
				'value' => ! empty( $kbs_edit_field->settings['placeholder'] ) ? $kbs_edit_field->settings['placeholder'] : null,
				'class' => 'kbs_input'
			) ); ?>
		</label></p>
    </div>

    <div id="kbs_meta_field_hide_label_wrap">
		<p><label for="kbs_field_hide_label">
			<?php echo KBS()->html->checkbox( array(
				'name' => 'kbs_field_hide_label',
				'current' => ! empty( $kbs_edit_field->settings['hide_label'] ) ? $kbs_edit_field->settings['hide_label'] : null
			) ); ?>
			<strong><?php _e( 'Hide Label?', 'kb-support' ); ?></strong></label>
        </p>
	</div>
    <?php
} // kbs_render_field_options_rows
add_action( 'kbs_form_mb_field_options', 'kbs_render_field_options_rows', 10, 1 );

/**
 * Determines if a field is being edited.
 *
 * @since	1.0
 * @global	$kbs_edit_field		The field post object if editing, or false.
 * @param	int		$post_id	The form post ID.
 * @return	void
 */
function kbs_maybe_editing_field()	{

	global $kbs_edit_field;

	if ( ! isset( $_GET['kbs-action'], $_GET['field_id'] ) )	{
		$kbs_edit_field = false;
	} else	{
		$kbs_edit_field = kbs_get_field( absint( $_GET['field_id'] ) );
	}

} // kbs_maybe_editing_field
