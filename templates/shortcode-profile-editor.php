<?php
/**
 * This template is used to display the customer profile editor form with [kbs_profile_editor]
 *
 * @uses	$current_user	The user object of the currently logged in user.
 */
if ( ! is_user_logged_in() ) : ?>

	<?php echo kbs_display_notice( 'profile_login' ); ?>
	<?php echo kbs_login_form(); ?>

<?php else :
	global $current_user;

    $customer = new KBS_Customer( $current_user->ID, true );

	do_action( 'kbs_notices' ); ?>

	<div id="kbs_item_wrapper" class="kbs_profile_wrapper" style="float: left">
		<div class="profile_info_wrapper data_section">

			<?php do_action( 'kbs_profile_editor_before' ); ?>

			<form id="kbs_profile_editor_form" class="kbs_form" action="" method="post">
            	<div class="kbs_item_info customer_info">
                	<fieldset id="kbs_ticket_info_details">
                        <legend><?php _e( 'Update your Profile Data', 'kb-support' ); ?></legend>

						<div class="kbs_profile_editor_firstname">
                            <p>
                            	<label for="kbs_first_name"><?php _e( 'First Name', 'kb-support' ); ?></label>
                                <input type="text" class="kbs-input" name="kbs_first_name" id="kbs-first-name" value="<?php echo esc_attr( $current_user->first_name ); ?>" />
                            </p>
                        </div>

                        <div class="kbs_profile_editor_lastname">
                            <p>
                            	<label for="kbs_last_name"><?php _e( 'Last Name', 'kb-support' ); ?></label>
                                <input type="text" class="kbs-input" name="kbs_last_name" id="kbs-last-name" value="<?php echo esc_attr( $current_user->last_name ); ?>" />
                            </p>
                        </div>

						<div class="kbs_profile_editor_displayname">
                        	<p>
                                <label for="kbs_display_name"><?php _e( 'Display Name', 'kb-support' ); ?></label>
                                <select name="kbs_display_name" id="kbs_display_name" class="select kbs-select kbs-input">

								<?php if ( ! empty( $current_user->first_name ) ): ?>
	                                <option <?php selected( $current_user->display_name, $current_user->first_name ); ?> value="<?php echo esc_attr( $current_user->first_name ); ?>"><?php echo esc_html( $current_user->first_name ); ?></option>
                                <?php endif; ?>

                                    <option <?php selected( $current_user->display_name, $current_user->user_nicename ); ?> value="<?php echo esc_attr( $current_user->user_nicename ); ?>"><?php echo esc_html( $current_user->user_nicename ); ?></option>

                                    <?php if ( ! empty( $current_user->last_name ) ): ?>
                                        <option <?php selected( $current_user->display_name, $current_user->last_name ); ?> value="<?php echo esc_attr( $current_user->last_name ); ?>"><?php echo esc_html( $current_user->last_name ); ?></option>
                                    <?php endif; ?>

                                    <?php if ( ! empty( $current_user->first_name ) && ! empty( $current_user->last_name ) ): ?>
                                        <option <?php selected( $current_user->display_name, $current_user->first_name . ' ' . $current_user->last_name ); ?> value="<?php echo esc_attr( $current_user->first_name . ' ' . $current_user->last_name ); ?>"><?php echo esc_html( $current_user->first_name . ' ' . $current_user->last_name ); ?></option>
                                        <option <?php selected( $current_user->display_name, $current_user->last_name . ' ' . $current_user->first_name ); ?> value="<?php echo esc_attr( $current_user->last_name . ' ' . $current_user->first_name ); ?>"><?php echo esc_html( $current_user->last_name . ' ' . $current_user->first_name ); ?></option>
                                    <?php endif; ?>

                                </select>

                                <?php do_action( 'kbs_profile_editor_name' ); ?>
                            </p>
                        </div>

						<?php do_action( 'kbs_profile_editor_after_name' ); ?>

						<div class="kbs_profile_editor_email">
                            <p>
                                <label for="kbs_email"><?php _e( 'Primary Email', 'kb-support' ); ?></label>
                                <?php if ( $customer->id > 0 ) : ?>

                                    <?php if ( 1 === count( $customer->emails ) ) : ?>
                                        <input name="kbs_email" id="kbs_email" class="text kbs-input required" type="email" value="<?php echo esc_attr( $customer->email ); ?>" />
                                    <?php else: ?>
                                        <?php
                                            $emails           = array();
                                            $customer->emails = array_reverse( $customer->emails, true );

                                            foreach ( $customer->emails as $email ) {
                                                $emails[ $email ] = $email;
                                            }

                                            $email_select_args = array(
                                                'options'          => $emails,
                                                'name'             => 'kbs_email',
                                                'id'               => 'kbs_email',
                                                'selected'         => $customer->email,
                                                'show_option_none' => false,
                                                'show_option_all'  => false,
                                            );

                                            echo KBS()->html->select( $email_select_args );
                                        ?>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <input name="kbs_email" id="kbs_email" class="text kbs-input " type="email" value="<?php echo esc_attr( $current_user->user_email ); ?>" />
                                <?php endif; ?>

                                <?php do_action( 'kbs_profile_editor_email' ); ?>

                            </p>
						</div>

						<?php if ( $customer->id > 0 && count( $customer->emails ) > 1 ) : ?>

							<div class="kbs_profile_editor_additional_emails">
                                <p>
                                	<label for="kbs_emails"><?php _e( 'Additional Emails', 'kb-support' ); ?></label>
                                    <ul class="kbs-profile-emails">
										<?php foreach ( $customer->emails as $email ) : ?>

                                            <?php if ( $email === $customer->email ) { continue; } ?>

                                            <li class="kbs-profile-email">
                                                <?php echo $email; ?>
                                                <span class="actions">
                                                    <?php
                                                        $remove_url = wp_nonce_url(
                                                            add_query_arg(
                                                                array(
                                                                    'email'      => $email,
                                                                    'kbs_action' => 'profile-remove-email',
                                                                    'redirect'   => esc_url( kbs_get_current_page_url() ),
                                                                )
                                                            ),
                                                            'kbs-remove-customer-email'
                                                        );
                                                    ?>
                                                    <a href="<?php echo $remove_url ?>" class="delete"><?php _e( 'Remove', 'kb-support' ); ?></a>
                                                </span>
                                            </li>

                                        <?php endforeach; ?>
                                    </ul>
                                </p>
                            </div>

                        <?php endif; ?>

						<?php do_action( 'kbs_profile_editor_after_email' ); ?>

                        <p class="kbs_form_section_heading"><?php _e( 'Update Preferences', 'kb-support' ); ?></p>

						<?php $hide_closed = kbs_customer_maybe_hide_closed_tickets( $customer->user_id ); ?>

                        <div class="kbs_profile_editor_hide_closed">
                            <p>
                            	<label for="kbs_hide_closed"><?php printf( __( 'Hide Closed %s', 'kb-support' ), kbs_get_ticket_label_plural() ); ?></label>
                                <input type="checkbox" name="kbs_hide_closed" id="kbs-hide-closed" value="1"<?php checked( '1', $hide_closed ); ?> />
                            </p>
                        </div>

						<?php do_action( 'kbs_profile_editor_after_hide_closed' ); ?>

						<div class="kbs_profile_editor_tickets_per_page">
                            <p>
                            	<label for="kbs_tickets_per_page">
									<?php
										printf( __( '%s per Page', 'kb-support' ),
										kbs_get_ticket_label_plural() );
									?>
								</label>
                                <input type="number" class="kbs-input" name="kbs_tickets_per_page" id="kbs-tickets-per-page" value="<?php echo esc_attr( $customer->get_tickets_per_page() ); ?>" min="1" max="50" step="1" /><span class="kbs-description"><?php printf( __( 'How many %s do you want to load per page on the %s Manager page?', 'kb-support' ), kbs_get_ticket_label_plural( true ), kbs_get_ticket_label_singular() ); ?></span>
                            </p>
                        </div>

						<?php do_action( 'kbs_profile_editor_after_tickets_per_page' ); ?>

						<?php
							$orderby = $customer->get_tickets_orderby();
							$orderby_options = kbs_get_ticket_orderby_options();
						?>
						<div class="kbs_profile_editor_tickets_orderby">
                            <p>
                            	<label for="kbs-tickets-orderby">
									<?php
										printf( __( 'Default %s Orderby', 'kb-support' ),
										kbs_get_ticket_label_plural() );
									?>
								</label>
								<select name="kbs_tickets_orderby" id="kbs-tickets-orderby" class="select kbs-select kbs-input">
									<?php foreach( $orderby_options as $ob_value => $ob_label ) : ?>
										<?php $selected = selected( $orderby, $ob_value, false ); ?>
										<option value="<?php echo $ob_value; ?>"<?php echo $selected; ?>>
											<?php echo $ob_label ?>
										</option>
									<?php endforeach; ?>
								</select>
                                <span class="kbs-description"><?php printf( __( 'Choose how to order your %s by default', 'kb-support' ), kbs_get_ticket_label_plural( true ) ); ?></span>
                            </p>
                        </div>

						<?php do_action( 'kbs_profile_editor_after_orderby' ); ?>

						<?php
							$order = $orderby = $customer->get_tickets_order();
							$order_options = array(
								'DESC' => __( 'Descending Order', 'kb-support' ),
								'ASC'  => __( 'Ascending Order', 'kb-support' )
							);
						?>
						<div class="kbs_profile_editor_tickets_order">
                            <p>
                            	<label for="kbs-tickets-order">
									<?php
										printf( __( 'Default %s Order', 'kb-support' ),
										kbs_get_ticket_label_plural() );
									?>
								</label>
								<select name="kbs_tickets_order" id="kbs-tickets-order" class="select kbs-select kbs-input">
									<?php foreach( $order_options as $o_value => $o_label ) : ?>
										<?php $selected = selected( $order, $o_value, false ); ?>
										<option value="<?php echo $o_value; ?>"<?php echo $selected; ?>>
											<?php echo $o_label ?>
										</option>
									<?php endforeach; ?>
								</select>
                                <span class="kbs-description"><?php printf( __( 'Choose how to order your %s by default', 'kb-support' ), kbs_get_ticket_label_plural( true ) ); ?></span>
                            </p>
                        </div>

						<?php do_action( 'kbs_profile_editor_after_orderby' ); ?>

						<div class="kbs_profile_editor_replies_to_load">
                            <p>
                            	<label for="kbs_number_replies"><?php _e( 'Replies to Load', 'kb-support' ); ?></label>
                                <input type="number" class="kbs-input" name="kbs_number_replies" id="kbs-number-replies" value="<?php echo esc_attr( $customer->get_replies_to_load() ); ?>" min="0" max="50" step="1" /><span class="kbs-description"><?php printf( __( 'How many replies do you want to initially load on the %s Manager page? <code>0</code> loads all.', 'kb-support' ), kbs_get_ticket_label_singular() ); ?></span>
                            </p>
                        </div>

                        <?php do_action( 'kbs_profile_editor_after_replies_to_load' ); ?>

                        <p class="kbs_form_section_heading"><?php _e( 'Change Password', 'kb-support' ); ?></p>
						<div class="kbs_profile_editor_password">
                            <p>
                                <label for="kbs_new_user_pass1"><?php _e( 'New Password', 'kb-support' ); ?></label>
                                <input name="kbs_new_user_pass1" id="kbs_new_user_pass1" class="password kbs-input" type="password" />
                            </p>
						</div>

						<div class="kbs_profile_editor_password_confirm">
							<p>
                                <label for="kbs_new_user_pass2"><?php _e( 'Re-enter Password', 'kb-support' ); ?></label>
                                <input name="kbs_new_user_pass2" id="kbs_new_user_pass2" class="password kbs-input" type="password" />
								<?php do_action( 'kbs_profile_editor_password' ); ?>
                            </p>
                        </div>

						<?php do_action( 'kbs_profile_editor_after_password' ); ?>

						<div class="kbs_profile_editor_save">
                            <p id="kbs_profile_submit_wrap">
                                <input type="hidden" name="kbs_profile_editor_nonce" value="<?php echo wp_create_nonce( 'kbs-profile-editor-nonce' ); ?>" />
                                <input type="hidden" name="kbs_action" value="edit_user_profile" />
                                <input type="hidden" name="kbs_redirect" value="<?php echo esc_url( kbs_get_current_page_url() ); ?>" />
                                <input name="kbs_profile_editor_submit" id="kbs_profile_editor_submit" type="submit" class="kbs_submit" value="<?php _e( 'Save Changes', 'kb-support' ); ?>" />
                            </p>
                        </div>

                    </fieldset>
                </div>
            </form>

        </div>
    </div>

	<?php do_action( 'kbs_profile_editor_after' ); ?>

<?php endif; ?>
