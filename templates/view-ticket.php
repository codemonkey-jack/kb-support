<?php
/**
 * This template is used to display a single ticket for a customer.
 *
 * @shortcode	[kbs_view_ticket]
 */
global $current_user;

$singular = kbs_get_ticket_label_singular();
$plural   = kbs_get_ticket_label_plural();
$visible  = false;

if ( is_numeric( $_GET['ticket'] ) )	{
	$field   = 'id';
	if ( is_user_logged_in() )	{
		$visible = true;
	}
} else	{
	$visible = true;
	$field   = 'key';
}

$ticket = kbs_get_ticket_by( $field, $_GET['ticket'] );

if ( $visible && ! empty( $ticket->ID ) ) :

	$ticket       = new KBS_Ticket( $ticket->ID );
	$use_user_id  = false;
	$customer_id  = $ticket->customer_id;
	$status_class = '';
	$alt_status   = '';

	if ( is_user_logged_in() ) :
		$use_user_id = true;
		$customer_id = $current_user->ID;
	endif;

	$customer = new KBS_Customer( $customer_id, $use_user_id ); ?>

	<?php if ( ! kbs_customer_can_access_ticket( $ticket, $customer ) ) : ?>

    	<?php echo kbs_display_notice( 'invalid_customer' ); ?>

    <?php else :

		$time_format = get_option( 'time_format' );
		$date_format = get_option( 'date_format' ); ?>

		<?php do_action( 'kbs_notices' ); ?>
        <div id="kbs_item_wrapper" class="kbs_ticket_wrapper">
            <div class="ticket_info_wrapper data_section">

                <?php do_action( 'kbs_before_single_ticket_form', $ticket ); ?>

                <form<?php kbs_maybe_set_enctype(); ?> id="kbs_ticket_reply_form" class="kbs_form" action="" method="post">

					<div class="kbs_item_info ticket_info">
                        <fieldset id="kbs_ticket_info_details">
							<legend><?php printf( __( 'Support %s Details # %s', 'kb-support' ), $singular, kbs_format_ticket_number( kbs_get_ticket_number( $ticket->ID ) ) ); ?></legend>

							<div class="container-fluid ticket_manager_data text-left">

								<div id="kbs-ticket-customer-date" class="row kbs_ticket_data">
									<table id="single-ticket-table" class="wpchill-table">
										<thead>
										<tr>
											<th><?php esc_html_e( 'Title', 'kb-support' ); ?></th>
											<th><?php esc_html_e( 'Status', 'kb-support' ); ?></th>
											<th><?php esc_html_e( 'Updated', 'kb-support' ); ?></th>
											<th><?php esc_html_e( 'Agent', 'kb-support' ); ?></th>
										</tr>
										</thead>
										<tbody>
										<tr>
											<td>
													<span class="ticket_subject"><?php echo esc_attr( $ticket->ticket_title ); ?></span>
											</td>
											<td>
													<span class="ticket_status"><span
															class="kbs-label kbs-label-status"
															style="background-color: <?php echo kbs_get_ticket_status_colour( $ticket->post_status ); ?>;">
															<?php echo $ticket->status_nicename; ?></span></span>
											</td>
											<td>
												<div class="wpchill-tooltip"><span>[?]</span>
													<div
														class="wpchill-tooltip-content"><?php echo esc_html__( 'Sent on ', 'kb-support' ) . date_i18n( $date_format, strtotime( $ticket->date ) ); ?></div>
												</div>

												  <span class="ticket_updated"><?php echo date_i18n( $time_format . ' \o\n ' . $date_format, strtotime( $ticket->modified_date ) ); ?> <?php printf( __( '(%s ago)', 'kb-support' ), human_time_diff( strtotime( $ticket->modified_date ), current_time( 'timestamp' ) ) ); ?> </span>
											</td>
											<?php do_action( 'kbs_single_ticket_after_date_logged_by', $ticket ); ?>
											<td>
											<span class="ticket_agent">
											<?php if ( !empty( $ticket->agent_id ) ) :
												$agent = get_userdata( $ticket->agent_id )->display_name;

												if ( kbs_display_agent_status() ) :
													$status       = kbs_get_agent_online_status( $ticket->agent_id );
													$status_class = 'kbs_agent_status_' . $status;
													$alt_status   = sprintf(
														__( '%s is %s', 'kb-support' ),
														$agent,
														$status
													);

												endif;
											else :
												$agent = __( 'No Agent Assigned', 'kb-support' );
											endif; ?>

											<span class="<?php echo $status_class; ?>"
												  title="<?php echo $alt_status; ?>">
												<?php echo $agent; ?>
											</span>
										</span>
											</td>
										</tr>
										</tbody>
									</table>

									<?php do_action( 'kbs_single_ticket_before_major_items', $ticket ); ?>

									<div class="major_ticket_items">
										<?php do_action( 'kbs_single_ticket_after_subject', $ticket ); ?>

										<div class="row kbs_ticket_subject">
											<div class="col-md">
											<span class="ticket_content">
												<label><?php _e( 'Content', 'kb-support' ); ?>:</label> <?php echo $ticket->get_content(); ?>
											</span>
											</div>
										</div>

										<?php do_action( 'kbs_single_ticket_after_content', $ticket ); ?>
									</div>

								<?php if ( ! empty( $ticket->files ) ) : ?>
                                    <p>
                                        <a class="button kbs_action_button" data-toggle="collapse" href="#kbs-ticket-files" role="button" aria-expanded="false" aria-controls="kbs-ticket-files">
                                            <?php printf(
                                                _n( 'View %s Attachment', 'View %s Attachments', count( $ticket->files ), 'kb-support' ),
                                                count( $ticket->files )
                                            ); ?>
                                        </a>
                                    </p>
                                    <div class="collapse" id="kbs-ticket-files">
                                        <div class="card card-body">
                                            <?php echo implode( '<br>', kbs_get_ticket_files_list( $ticket->files ) ); ?>
                                        </div>
                                    </div>
                                    <?php do_action( 'kbs_single_ticket_after_files', $ticket ); ?>
								<?php endif; ?>

							</div><!-- .container -->

						</fieldset>

						<?php do_action( 'kbs_before_single_ticket_form_replies', $ticket ); ?>

						<fieldset id="kbs_ticket_replies">
							<legend><?php _e( 'Replies', 'kb-support' ); ?></legend>

							<?php
							$load = kbs_get_customer_replies_to_load( $current_user->ID );
							$args = array(
								'ticket_id' => $ticket->ID,
								'number'    => empty( $load ) ? -1 : $load,
								'page'      => 1
							);

							$replies_query = new KBS_Replies_Query( $args );
							$replies       = $replies_query->get_replies();
							$count_expand  = 1;
							?>

							<?php if ( ! empty( $replies ) ) : ?>
                                <div id="kbs-ticket-replies" class="kbs-accordion">
                                    <?php foreach( $replies as $reply ) : ?>

                                        <?php
										$current_user_replay = absint( $reply->post_author ) == get_current_user_id();
										$read_reply          = get_post_meta( $reply->ID, '_kbs_reply_customer_read', true );
										$reply_content       = apply_filters( 'the_content', $reply->post_content );
										$reply_content       = str_replace( ']]>', ']]&gt;', $reply_content );
										$files               = kbs_ticket_has_files( $reply->ID );
										$file_count          = ( $files ? count( $files ) : false );
										$heading             = apply_filters( 'kbs_front_replies_title', sprintf(
											'%s by %s',
											date_i18n( $time_format . ' \o\n ' . $date_format, strtotime( $reply->post_date ) ),
											kbs_get_reply_author_name( $reply->ID, true )
										) );
                                        ?>

                                        <div id="kbs-reply-card" class="card kbs_replies_wrapper">
                                            <div class="card-header kbs-replies-row-header">
                                                <span class="kbs-replies-row-title">
                                                    <?php echo $heading; ?>
                                                </span>

                                                <span class="kbs-replies-row-actions">
                                                    <a href="#" class="toggle-view-reply-option-section " data-toggle="collapse" data-target="#kbs_ticket_reply-<?php echo $reply->ID; ?>" aria-expanded="false" aria-controls="kbs_ticket_reply-<?php echo $reply->ID; ?>" data-key="<?php echo $reply->ID; ?>">
                                                        <?php _e( 'View Reply', 'kb-support' ); ?>
                                                    </a>
                                                </span>
												<?php echo (!$current_user_replay && '' == $read_reply ) ? '<span class="new-reply"><sup>( ! )</sup></span>' : ''; ?>
                                            </div>

                                            <div id="kbs_ticket_reply-<?php echo esc_attr($reply->ID); ?>" class="collapse" aria-labelledby="kbs_ticket_reply-<?php echo $reply->ID; ?>-heading" data-parent="#kbs-ticket-replies">
                                                <div class="card-body">
                                                    <?php echo $reply_content; ?>
                                                    <?php if ( $files ) : ?>
                                                    <div class="kbs_ticket_reply_files">
                                                        <strong><?php printf(
                                                            __( 'Attached Files (%d)', 'kb-support' ),
                                                            $file_count
                                                        ); ?></strong>
                                                        <ol>
                                                            <?php foreach( $files as $file ) : ?>
                                                                <li>
                                                                    <a href="<?php echo wp_get_attachment_url( $file->ID ); ?>" target="_blank">
                                                                        <?php echo basename( get_attached_file( $file->ID ) ); ?>
                                                                    </a>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        </ol>
                                                    </div>
                                                <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
										<?php $count_expand++; ?>
                                    <?php endforeach; ?>
                                    <div id="kbs-loading-replies"></div>
                                </div><!-- .kbs-accordian -->

                                <div id="kbs-replies-loader"></div>

                                <?php if ( isset( $args['page'] ) && $args['page'] < $replies_query->pages ) : ?>
                                    <?php printf(
                                        '<p class="kbs_replies_load_more"><a href="#" class="button kbs_action_button" id="kbs-replies-next-page" data-ticket-id="%d" data-load-page="%d" role="button">%s</a></p>',
                                        $ticket->ID,
                                        ( (int)$args['page'] + 1 ),
                                        __( 'Load More', 'kb-support' )
                                    ); ?>
                                <?php endif; ?>

							<?php else : ?>
								<span class="kbs-description ticket-no-replies">
									<?php _e( 'Replies will be displayed here when created.', 'kb-support' ); ?>
								</span>
							<?php endif; ?>

                        </fieldset>

                        <?php do_action( 'kbs_before_single_ticket_reply', $ticket ); ?>

                        <fieldset id="kbs_ticket_new_reply">
                            <legend><?php _e( 'Add a Reply', 'kb-support' ); ?></legend>

                            <?php if ( 'closed' != $ticket->status || kbs_customer_can_repoen_ticket( $customer->id, $ticket->ID ) ) : ?>

                                <div class="kbs_alert kbs_alert_error kbs_hidden"></div>

                                <?php if ( 'closed' == $ticket->status ) : ?>
                                    <div class="kbs_alert kbs_alert_info">
                                        <?php printf( __( 'This %s has been closed. If you enter a new reply, it will be reopened.', 'kb-support' ), strtolower( $singular ) ); ?>
                                    </div>
                                <?php endif; ?>

                                <div id="new-reply" class="ticket_reply_fields">

                                    <?php $wp_settings  = apply_filters( 'kbs_ticket_reply_editor_settings', array(
                                        'media_buttons' => false,
                                        'textarea_rows' => get_option( 'default_post_edit_rows', 10 ),
                                        'teeny'         => true,
                                        'quicktags'     => false
                                    ) );
                                    echo wp_editor( '', 'kbs_reply', $wp_settings ); ?>

                                    <?php if ( kbs_file_uploads_are_enabled() ) : ?>
                                        <?php do_action( 'kbs_before_single_ticket_files', $ticket ); ?>
                                        <div class="reply_files">
                                            <p>
                                                <label for="kbs_files"><?php _e( 'Attach Files', 'kb-support' ); ?></label><br />
                                                <?php for ( $i = 1; $i <= kbs_get_max_file_uploads(); $i++ ) : ?>
                                                    <input type="file" class="kbs-input" name="kbs_files[]" />
                                                <?php endfor; ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>

                                    <?php do_action( 'kbs_before_single_ticket_email', $ticket ); ?>

                                    <?php if ( ! is_user_logged_in() ) : ?>

                                        <div class="reply_confirm_email">
                                            <p><label for="kbs_confirm_email"><?php _e( 'Confirm your Email Address', 'kb-support' ); ?></label>
                                                <span class="kbs-description"><?php _e( 'So we can verify your identity', 'kb-support' ); ?></span>
                                                <input type="email" class="kbs-input" name="kbs_confirm_email" id="kbs-confirm-email" />
                                            </p>
                                        </div>

                                    <?php endif; ?>

                                    <?php do_action( 'kbs_before_single_ticket_close', $ticket ); ?>

                                    <div class="reply_close">
                                        <p><input type="checkbox" name="kbs_close_ticket" id="kbs-close-ticket" />
                                            <?php printf( __( 'This %s can be closed', 'kb-support' ), strtolower( $singular ) ); ?>
                                        </p>
                                    </div>

                                    <?php kbs_render_hidden_reply_fields( $ticket->ID ); ?>
                                    <?php do_action( 'kbs_before_single_ticket_reply_submit', $ticket ); ?>
                                    <input class="button" name="kbs_ticket_reply" id="kbs_reply_submit" type="submit" value="<?php echo esc_attr( kbs_get_ticket_reply_label() ); ?>" />

                                </div>

                            <?php else : ?>
                                <div class="kbs_alert kbs_alert_info">
                                    <?php printf(
                                        __( 'This %s is closed.', 'kb-support' ),
                                        strtolower( $singular ) );
                                    ?>
                                </div>
                            <?php endif; ?>

                        </fieldset>

					</div><!-- .kbs_item_info ticket_info -->

				</form>

                <?php do_action( 'kbs_after_single_ticket_form', $ticket ); ?>

            </div>
        </div>

	<?php endif; ?>

<?php elseif ( ! $visible ) : ?>
	<?php
	$args = array();
	if ( isset( $_GET['ticket'] ) )	{
		$args = array( 'ticket' => $_GET['ticket'] );
	}
    $redirect  = add_query_arg( $args, get_permalink( kbs_get_option( 'tickets_page' ) ) );

	?>
	<?php echo kbs_display_notice( 'ticket_login' ); ?>
    <?php echo kbs_login_form( $redirect ); ?>
<?php else : ?>
	<?php echo kbs_display_notice( 'no_ticket' ); ?>
<?php endif; ?>
