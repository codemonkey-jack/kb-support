var KBSShowNotice, kbs_vars;
jQuery(document).ready(function ($) {

	// Setup datepicker
	var kbs_datepicker = $('.kbs_datepicker');
	if ( kbs_datepicker.length > 0 ) {
		var dateFormat = 'mm/dd/yy';
		kbs_datepicker.datepicker({
			dateFormat: dateFormat
		});
	}

	// Setup Chosen menus
	$('.kbs_select_chosen').chosen({
		inherit_select_classes: true,
		placeholder_text_single: kbs_vars.one_option,
		placeholder_text_multiple: kbs_vars.one_or_more_option
	});

	$('.kbs_select_chosen .chosen-search input').each( function() {
		var selectElem = $(this).parent().parent().parent().prev('select.kbs_select_chosen'),
			placeholder = selectElem.data('search-placeholder');
		$(this).attr( 'placeholder', placeholder );
	});

	// Add placeholders for Chosen input fields
	$( '.chosen-choices' ).on( 'click', function () {
		var placeholder = $(this).parent().prev().data('search-placeholder');
		if ( typeof placeholder === 'undefined' ) {
			placeholder = kbs_vars.type_to_search;
		}
		$(this).children('li').children('input').attr( 'placeholder', placeholder );
	});

    // Dismiss admin notices
    $( document ).on( 'click', '.notice-kbs-dismiss .notice-dismiss', function () {
        var notice = $( this ).closest( '.notice-kbs-dismiss' ).data( 'notice' );

        var postData         = {
            notice    : notice,
            action       : 'kbs_dismiss_notice'
        };

        $.ajax({
            type: 'POST',
            dataType: 'json',
            data: postData,
            url: ajaxurl
        });
    });

	/**
	 * Settings screen JS
	 */
	var KBS_Settings = {

		init : function() {
            this.general();
			this.uploads();
		},

        general : function() {
            var kbs_color_picker = $('.kbs-color-picker');

			if( kbs_color_picker.length ) {
				kbs_color_picker.wpColorPicker();
			}

            if ( $( '.logged_in_only' ).length ) {
                if ( $( '.logged_in_only' ).is( ':checked' ) )  {
                    $( '.kbs_option_auto_add_user' ).hide();
                }
            }

            if ( $( '.recaptcha_version' ).length ) {
                if ( 'v2' !== $( '.recaptcha_version' ).val() )  {
                    $( '.kbs_option_recaptcha_theme' ).hide();
                    $( '.kbs_option_recaptcha_type' ).hide();
                    $( '.kbs_option_recaptcha_size' ).hide();
                }
            }

            $( document.body ).on( 'change', '.logged_in_only', function() {
                if ( $(this).is( ':checked' ) ) {
                    $( '.kbs_option_auto_add_user' ).fadeOut( 'fast' );
                } else {
                    $( '.kbs_option_auto_add_user' ).fadeIn( 'fast' );
                }
            });

            $( document.body ).on( 'change', '.recaptcha_version', function() {
                if ( 'v2' !== $( '.recaptcha_version' ).val() )  {
                    $( '.kbs_option_recaptcha_theme' ).fadeOut( 'fast' );
                    $( '.kbs_option_recaptcha_type' ).fadeOut( 'fast' );
                    $( '.kbs_option_recaptcha_size' ).fadeOut( 'fast' );
                } else {
                    $( '.kbs_option_recaptcha_theme' ).fadeIn( 'fast' );
                    $( '.kbs_option_recaptcha_type' ).fadeIn( 'fast' );
                    $( '.kbs_option_recaptcha_size' ).fadeIn( 'fast' );
                }
            });
        },
		uploads : function() {
			// Settings Upload field JS
			if ( typeof wp === 'undefined' || '1' !== kbs_vars.new_media_ui ) {
				// Old Thickbox uploader
				var kbs_settings_upload_button = $( '.kbs_settings_upload_button' );
				if ( kbs_settings_upload_button.length > 0 ) {
					window.formfield = '';

					$( document.body ).on('click', kbs_settings_upload_button, function(e) {
						e.preventDefault();
						window.formfield = $(this).parent().prev();
						window.tbframe_interval = setInterval(function() {
							jQuery('#TB_iframeContent').contents().find('.savesend .button').val(kbs_vars.use_this_file).end().find('#insert-gallery, .wp-post-thumbnail').hide();
						}, 2000);
						tb_show( kbs_vars.add_new_ticket, 'media-upload.php?TB_iframe=true' );
					});

					window.kbs_send_to_editor = window.send_to_editor;
					window.send_to_editor = function (html) {
						if (window.formfield) {
							imgurl = $('a', '<div>' + html + '</div>').attr('href');
							window.formfield.val(imgurl);
							window.clearInterval(window.tbframe_interval);
							tb_remove();
						} else {
							window.kbs_send_to_editor(html);
						}
						window.send_to_editor = window.kbs_send_to_editor;
						window.formfield = '';
						window.imagefield = false;
					};
				}
			} else {
				// WP 3.5+ uploader
				var file_frame;
				window.formfield = '';

				$( document.body ).on('click', '.kbs_settings_upload_button', function(e) {

					e.preventDefault();

					var button = $(this);

					window.formfield = $(this).parent().prev();

					// If the media frame already exists, reopen it.
					if ( file_frame ) {
						//file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
						file_frame.open();
						return;
					}

					// Create the media frame.
					file_frame = wp.media.frames.file_frame = wp.media({
						frame: 'post',
						state: 'insert',
						title: button.data( 'uploader_title' ),
						button: {
							text: button.data( 'uploader_button_text' )
						},
						multiple: false
					});

					file_frame.on( 'menu:render:default', function( view ) {
						// Store our views in an object.
						var views = {};

						// Unset default menu items
						view.unset( 'library-separator' );
						view.unset( 'gallery' );
						view.unset( 'featured-image' );
						view.unset( 'embed' );

						// Initialize the views in our view object.
						view.set( views );
					} );

					// When an image is selected, run a callback.
					file_frame.on( 'insert', function() {

						var selection = file_frame.state().get('selection');
						selection.each( function( attachment ) {
							attachment = attachment.toJSON();
							window.formfield.val(attachment.url);
						});
					});

					// Finally, open the modal
					file_frame.open();
				});
			}
		}
	};
	KBS_Settings.init();

	/**
	 * Tickets screen JS
	 */
	var KBS_Tickets = {
		init : function() {
			this.notes();
            this.options();
			this.participants();
			this.reply();
			this.save();
		},

        save : function()   {
            $( document.body ).on( 'click', '#save-post', function() {
                var ticketResponse = '';
                var tinymceActive  = (typeof tinyMCE !== 'undefined') && tinyMCE.activeEditor && ! tinyMCE.activeEditor.isHidden();

				if (tinymceActive) {
					tinyMCE.triggerSave();
				}

				ticketResponse = $('#kbs_ticket_reply').val();
                if ( ticketResponse.length > 0 )	{
                    return confirm( kbs_vars.reply_has_data );
                }
            });

            $( document.body ).on( 'change', '#ticket_status', function() {
                if ( 'kbs_ticket' === kbs_vars.post_type && 'closed' === $(this).val() && ! kbs_vars.disable_closure_email ) {
                    $(this).parent().append('<br id="kbs-closure-option">');
                    $(this).parent().append('<input type="checkbox" id="kbs-closure-email" name="kbs_closure_email" value="1" style="margin-top:0; margin-left: 4px;">');
                    $(this).parent().append('<label for="kbs-closure-email">' + kbs_vars.send_closure_email + '</label>');
                } else {
                    $('#kbs-closure-option').remove();
                    $('#kbs-closure-email').remove();
                    $('label[for="kbs-closure-email"]').remove();
                }
            });
        },

        options : function()    {
            // Flagging a ticket
            $( document.body ).on( 'click', '.toggle-flagged-status-option-section', function(e) {
                e.preventDefault();

                var postData = {
					ticket_id : kbs_vars.post_id,
					flagged   : $(this).data( 'flag' ),
					action    : 'kbs_set_ticket_flagged_status'
				};

				$.ajax({
					type: 'POST',
					dataType: 'json',
					data: postData,
					url: ajaxurl,
					beforeSend: function()	{
						$('#kbs-ticket-metabox-fields').addClass('kbs-mute');
						$('.toggle-flagged-status-option-section').html(kbs_vars.please_wait);
					},
					success: function (response) {
						if ( true === response.success )	{
							if ( 'flagged' === response.data.flagged )	{
                                $('#kbs-ticket-flag-notice').show();
								$('.toggle-flagged-status-option-section').html(kbs_vars.ticket_unflag);
                                $('.toggle-flagged-status-option-section').data('flag', '0');
							} else   {
                                $('#kbs-ticket-flag-notice').hide();
                                $('.toggle-flagged-status-option-section').html(kbs_vars.ticket_flag);
                                $('.toggle-flagged-status-option-section').data('flag', '1');
                            }

                            kbs_load_ticket_notes(kbs_vars.post_id, response.data.note_id);
						}

						$('#kbs-ticket-metabox-fields').removeClass('kbs-mute');
					}
				}).fail(function (data) {
					if ( window.console && window.console.log ) {
						console.log( data );
					}
				});
            });

            // When Add Reply is clicked
            $( document.body ).on( 'click', '.toggle-add-reply-option-section', function(e) {
                e.preventDefault();

                 $('html, body').animate({
                    scrollTop: $('.kbs-historic-reply-option-fields').offset().top
                }, 500 );
            });

			// Toggle display of participants
            $( document.body ).on( 'click', '.toggle-view-participants-option-section', function(e) {
                e.preventDefault();
                var show = $(this).html() === kbs_vars.view_participants ? true : false;

                if ( show ) {
					$(this).html( kbs_vars.hide_participants );
                } else {
                    $(this).html( kbs_vars.view_participants );
                }

                $('#kbs-ticket-participants-fields').slideToggle();

				if ( show )	{
					$('html, body').animate({
						scrollTop: $('#kbs-ticket-participants-fields').offset().top
					}, 500 );
				}
            });

            // Toggle display of submission form data
            $( document.body ).on( 'click', '.toggle-view-submission-option-section', function(e) {
                e.preventDefault();
                var show = $(this).html() === kbs_vars.view_submission ? true : false;

                if ( show ) {
                    $(this).html( kbs_vars.hide_submission );
                } else {
                    $(this).html( kbs_vars.view_submission );
                }

                $('#kbs-ticket-formdata-fields').slideToggle();

				if ( show )	{
					$('html, body').animate({
						scrollTop: $('#kbs-ticket-formdata-fields').offset().top
					}, 500 );
				}
            });
        },

		participants : function()	{
			// Adds a participant to a ticket
			$( document.body ).on( 'click', '#kbs-add-participant', function(e) {
				e.preventDefault(e);

				var customer_id = $('#participant_id').val(),
					user_email  = $('#participant_email').val();

				if ( '-1' === customer_id && ! user_email )	{
					return;
				}

				var postData = {
					ticket_id   : kbs_vars.post_id,
					participant : customer_id,
					email       : user_email,
					action      : 'kbs_add_participant'
				};

				$.ajax({
					type: 'POST',
					dataType: 'json',
					data: postData,
					url: ajaxurl,
					beforeSend: function()	{
						$('#kbs-ticket-participants-fields').addClass('kbs-mute');
						$('#kbs-add-participant').attr('disabled', true);
					},
					success: function (response) {
						if ( true === response.success )	{
							if ( '-1' !== customer_id )	{
								$('#participant_id option:selected').remove();
							}

							$('.kbs-ticket-participants-list').html( response.data.list );
							$('#participant-count').text(response.data.count);
						}

						$('#participant_email').empty();
						$('#participant_id').val('-1');
						$('#participant_id').trigger('chosen:updated');

						$('#kbs-add-participant').attr('disabled', false);
						$('#kbs-ticket-participants-fields').removeClass('kbs-mute');
					}
				}).fail(function (data) {
					if ( window.console && window.console.log ) {
						console.log( data );
					}
				});
			});

			// Removes a participant from a ticket
			$( document.body ).on( 'click', '.remove-participant', function(e) {
				e.preventDefault(e);

				var participant = $(this).data('participant');

				var postData = {
					participant : participant,
					ticket_id   : kbs_vars.post_id,
					action      : 'kbs_remove_participant'
				};

				$.ajax({
					type: 'POST',
					dataType: 'json',
					data: postData,
					url: ajaxurl,
					beforeSend: function()	{
						$('#kbs-ticket-participants-fields').addClass('kbs-mute');
						$('#kbs-add-participant').attr('disabled', true);
					},
					success: function (response) {
						if ( true === response.success )	{
							$('.kbs-ticket-participants-list').html( response.data.list );
							$('#participant-count').text( response.data.count );
						}

						$('#kbs-add-participant').attr('disabled', false);
						$('#kbs-ticket-participants-fields').removeClass('kbs-mute');
					}
				}).fail(function (data) {
					if ( window.console && window.console.log ) {
						console.log( data );
					}
				});
			});
		},

		reply : function() {

            // Listen for new replies via WP heartbeat
            if( kbs_vars.editing_ticket === '1' && kbs_vars.reply_alerts === '1' )   {
                $( document ).on( 'heartbeat-send', function ( event, data ) {

                    var kbs_last_reply = $( '#kbs-latest-reply' ).val();

                    data.kbs_last_reply = kbs_last_reply;
                    data.kbs_ticket_id  = kbs_vars.post_id;
                });

                // Check for data on heartbeat reply and process
                $( document ).on( 'heartbeat-tick', function ( event, data ) {

                    if ( ! data.has_new_reply ) {
                        return;
                    }

                    $( '#kbs-latest-reply' ).val(data.has_new_reply);
                    if ( confirm( kbs_vars.new_reply_notice ) ) {
                        $('.kbs-historic-reply-option-fields').empty();
                        kbs_load_ticket_replies( kbs_vars.post_id, 0, 1 );
                    }
                });
            }

            // Delete a reply
            $( document.body ).on( 'click', '.reply-delete', function(event) {
                event.preventDefault();

                if ( confirm( kbs_vars.delete_ticket_warn ) ) {
                    window.location = $(this).attr('href');
                }
            });

			// Reply to ticket Requests
			$( document.body ).on( 'click', '#kbs-reply-close, #kbs-reply-update', function(event) {

				event.preventDefault();

				var ticket_id      = kbs_vars.post_id;
				var ticketResponse = '';
				var ticketStatus   = kbs_vars.agent_set_status ? $('#ticket_reply_status').val() : kbs_vars.default_reply_status;
				var formData       = $('#post').serialize();
				var tinymceActive  = (typeof tinyMCE !== 'undefined') && tinyMCE.activeEditor && ! tinyMCE.activeEditor.isHidden();

				if (tinymceActive) {
					tinyMCE.triggerSave();
				}

				ticketResponse = $('#kbs_ticket_reply' ).val();

				if ( ticketResponse.length === 0 )	{
					window.alert( kbs_vars.no_ticket_reply_content );
					return false;
				}

				if ( 'kbs-reply-close' === event.target.id || 'closed' === ticketStatus )	{
					var confirmClose = confirm( kbs_vars.ticket_confirm_close );

					if (confirmClose === false) {
						return;
					}
				}

				var postData         = {
					ticket_id    : ticket_id,
					response     : ticketResponse,
					status       : ticketStatus,
					close_ticket : ( 'kbs-reply-close' === event.target.id ? 1 : 0 ),
					form_data    : formData,
					action       : 'kbs_insert_ticket_reply'
				};

				$.ajax({
					type: 'POST',
					dataType: 'json',
					data: postData,
					url: ajaxurl,
					beforeSend: function()	{
						$('#kbs-new-reply-loader').html('<img src="' + kbs_vars.ajax_loader + '" />');
					},
					success: function ( response ) {
						if ( response.reply_id ) {
							kbs_load_ticket_reply( ticket_id, response.reply_id, 1 );
							if ( '' !== response.status_option ) {
								jQuery( '#helptain-action-bar li.ticket-status' ).html( 'Status: ' + response.status_option ).css( 'background-color', response.status_color );
							}
							return true;
						} else {
							window.alert( kbs_vars.reply_not_added );
						}
						$( '#kbs-new-reply-loader' ).html( '' );
					}
				}).fail(function (data) {
					if ( window.console && window.console.log ) {
						console.log( data );
					}
				});

			});

			$( document.body ).on( 'click', '#kbs-replies-next-page', function() {
				var ticket_id = $('#kbs-replies-next-page').data('ticket-id');
				var page      = $('#kbs-replies-next-page').data('load-page');

				$('.kbs-replies-load-more').remove();
				kbs_load_ticket_replies( ticket_id, 0, page );
			});

		},
		notes : function() {
			// Add a new ticket note
			$( document.body ).on( 'click', '#kbs-add-note', function() {
				var note_content = $('#kbs_new_note').val();
				var ticket_id    = kbs_vars.post_id;

				if ( note_content.length < 1 )	{
					window.alert(kbs_vars.no_note_content);
					return;
				}

				var postData         = {
					ticket_id    : ticket_id,
					note_content : note_content,
					action       : 'kbs_insert_ticket_note'
				};

				$.ajax({
					type: 'POST',
					dataType: 'json',
					data: postData,
					url: ajaxurl,
					beforeSend: function()	{
						$('#kbs-add-note').hide();
						$('#kbs-new-note-loader').html('<img src="' + kbs_vars.ajax_loader + '" />');
					},
					success: function (response) {
						if (response.note_id)	{
							kbs_load_ticket_note(ticket_id, response.note_id);
							$('#kbs_new_note').val('');
						} else	{
							window.alert(kbs_vars.note_not_added);
						}
						$('#kbs-new-note-loader').html('');
						$('#kbs-add-note').show();
					}
				}).fail(function (data) {
					if ( window.console && window.console.log ) {
						console.log( data );
					}
				});

			});

			// Auto load ticket replies and notes
			if( kbs_vars.editing_ticket === '1' ) {
				setTimeout( function() {
					kbs_load_ticket_replies( kbs_vars.post_id, 0, 1 );
					kbs_load_ticket_notes( kbs_vars.post_id, 0 );
				}, 200);
			}
		}
	};

	KBS_Tickets.init();

	/**
	 * Forms screen JS
	 */
	var KBS_Forms = {

		init : function() {
			this.forms();
			this.move();
		},

		forms : function() {

			var toggleFieldOptions = function(kbs_selected_field)	{
				if ( 'text' === kbs_selected_field || 'date_field' === kbs_selected_field || 'department' === kbs_selected_field || 'email' === kbs_selected_field || 'number' === kbs_selected_field || 'select' === kbs_selected_field || 'textarea' === kbs_selected_field || 'url' === kbs_selected_field )	{

					$('#kbs_meta_field_placeholder_wrap').show();
				} else	{
					$('#kbs_meta_field_placeholder_wrap').hide();
				}

				if ( 'text' === kbs_selected_field || 'date_field' === kbs_selected_field || 'department' === kbs_selected_field || 'email' === kbs_selected_field || 'hidden' === kbs_selected_field || 'number' === kbs_selected_field || 'select' === kbs_selected_field || 'textarea' === kbs_selected_field || 'url' === kbs_selected_field )	{

					$('#kbs_meta_field_hide_label_wrap').show();
				} else	{
					$('#kbs_meta_field_hide_label_wrap').hide();
				}

				if ( 'select' === kbs_selected_field || 'checkbox_list' === kbs_selected_field || 'radio' === kbs_selected_field )	{
					$('#kbs_meta_field_select_options_wrap').show();
				} else	{
					$('#kbs_meta_field_select_options_wrap').hide();
				}

				if ( 'select' === kbs_selected_field )	{
					$('#kbs_meta_field_select_multiple_wrap').show();
				} else	{
					$('#kbs_meta_field_select_multiple_wrap').hide();
				}

				if ( 'select' === kbs_selected_field || 'department' === kbs_selected_field || 'ticket_category_dropdown' === kbs_selected_field )	{
                    $('#kbs_meta_field_select_blank_wrap').show();
					$('#kbs_meta_field_select_searchable_wrap').show();
				} else	{
                    $('#kbs_meta_field_select_blank_wrap').hide();
					$('#kbs_meta_field_select_searchable_wrap').hide();
				}

				if ( 'checkbox' === kbs_selected_field )	{
					$('#kbs_meta_field_option_selected_wrap').show();
				} else	{
					$('#kbs_meta_field_option_selected_wrap').hide();
				}

				if ( 'checkbox' === kbs_selected_field || 'file_upload' === kbs_selected_field )	{
					$('#kbs_meta_field_required_wrap').hide();
				} else	{
					$('#kbs_meta_field_required_wrap').show();
				}

				if ( 'recaptcha' === kbs_selected_field )	{
					$('#kbs_meta_field_required_wrap').hide();
					$('#kbs_meta_field_label_class_wrap').hide();
					$('#kbs_meta_field_input_class_wrap').hide();
				} else	{
					$('#kbs_meta_field_required_wrap').show();
					$('#kbs_meta_field_label_class_wrap').show();
					$('#kbs_meta_field_input_class_wrap').show();
				}

				if ( 'text' === kbs_selected_field || 'email' === kbs_selected_field || 'hidden' === kbs_selected_field || 'url' === kbs_selected_field || 'textarea' === kbs_selected_field || 'rich_editor' === kbs_selected_field || 'department' === kbs_selected_field )	{
					$('#kbs_meta_field_mapping_wrap').show();
				} else	{
					$('#kbs_meta_field_mapping_wrap').hide();
				}

				if ( 'hidden' === kbs_selected_field )	{
					$('#kbs_meta_field_value_wrap').show();
				} else	{
					$('#kbs_meta_field_value_wrap').hide();
				}

				if( 'post_title' === $('#kbs_field_mapping').val() )	{
					$('#kbs_meta_field_kb_search_wrap').show();
				} else	{
					$('#kbs_meta_field_kb_search_wrap').hide();
				}

			};

			// Preload field options when editing
			if ( kbs_vars.editing_field_type )	{
				toggleFieldOptions(kbs_vars.editing_field_type);
			}

			var kbs_field_type = $('.kbs_field_type');

			$( document.body ).on('change', kbs_field_type, function()	{
				toggleFieldOptions(kbs_field_type.val());
			});

			$( document.body ).on('change', $('#kbs_field_mapping'), function()	{
				if( 'post_title' === $('#kbs_field_mapping').val() )	{
					$('#kbs_meta_field_kb_search_wrap').show();
				} else	{
					$('#kbs_meta_field_kb_search_wrap').hide();
				}
			});

			$( document.body ).on('change', $('#kbs_field_select_chosen'), function()	{
				if( $('#kbs_field_select_chosen').is(':checked') )	{
					$('#kbs_meta_field_select_search_text_wrap').show('fast');
				} else	{
					$('#kbs_meta_field_select_search_text_wrap').hide('fast');
				}
			});

			// Send Add New Field Requests
			$( document.body ).on( 'click', '#kbs-add-form-field', function(event) {

				event.preventDefault();
				if ( $('#kbs_field_label').val().length < 1 )	{
					window.alert( kbs_vars.field_label_missing );
					return false;
				}
				if ( $('#kbs_field_type').val() === '-1' )	{
					window.alert( kbs_vars.field_type_missing );
					return false;
				}

				var return_url       = $('#form_return_url').val();
				var postData         = {
					action           : 'kbs_add_form_field',
                    blank            : $('#kbs_field_select_blank').val(),
					chosen           : ( $('#kbs_field_select_chosen').is(':checked') )   ? $('#kbs_field_select_chosen').val()   : 0,
                    chosen_search    : $('#kbs_field_select_chosen_search').val(),
					description      : $('#kbs_field_description').val(),
					description_pos  : $('input[name=kbs_field_description_pos]').filter(':checked').val(),
					form_data        : $('#post').serialize(),
					form_id          : kbs_vars.post_id,
					hide_label       : ( $('#kbs_field_hide_label').is(':checked') )      ? $('#kbs_field_hide_label').val()      : 0,
					input_class      : $('#kbs_field_input_class').val(),
					kb_search        : ( $('#kbs_field_kb_search').is(':checked') )       ? $('#kbs_field_kb_search').val()     : 0,
					label            : $('#kbs_field_label').val(),
					label_class      : $('#kbs_field_label_class').val(),
					mapping          : $('#kbs_field_mapping').val(),
					placeholder      : $('#kbs_field_placeholder').val(),
					required         : ( $('#kbs_field_required').is(':checked') )        ? $('#kbs_field_required').val()        : 0,
					selected         : ( $('#kbs_field_option_selected').is(':checked') ) ? $('#kbs_field_option_selected').val() : 0,
					select_multiple  : ( $('#kbs_field_select_multiple').is(':checked') ) ? $('#kbs_field_select_multiple').val() : 0,
					select_options   : $('textarea#kbs_field_select_options').val(),
					type             : $('#kbs_field_type').val(),
					value            : $('#kbs_field_value').val()
				};

				$.ajax({
					type: 'POST',
					dataType: 'json',
					data: postData,
					url: ajaxurl,
					beforeSend: function()	{
						$('#kbs-field-add').addClass('kbs-hidden');
						$('#kbs-loading').removeClass('kbs-hidden');
					},
					success: function (response) {
						window.location.href = return_url + '&kbs-message=' + response.message;
						return true;
					}
				}).fail(function (data) {
					$('#kbs-field-add').removeClass('kbs-hidden');
					$('#kbs-loading').addClass('kbs-hidden');
					if ( window.console && window.console.log ) {
						console.log( data );
					}
				});

			});

			// Send Edit Field Requests
			$( document.body ).on( 'click', '#kbs-save-form-field', function(event) {

				event.preventDefault();

				if ( $('#kbs_field_label').val().length < 1 )	{
					window.alert( kbs_vars.field_label_missing );
					return false;
				}
				if ( $('#kbs_field_type').val() === '-1' )	{
					window.alert( kbs_vars.field_type_missing );
					return false;
				}

				var return_url       = $('#form_return_url').val();
				var postData         = {
					action           : 'kbs_save_form_field',
                    blank            : $('#kbs_field_select_blank').val(),
					chosen           : ( $('#kbs_field_select_chosen').is(':checked') )   ? $('#kbs_field_select_chosen').val()   : 0,
                    chosen_search    : $('#kbs_field_select_chosen_search').val(),
					description      : $('#kbs_field_description').val(),
					description_pos  : $('input[name=kbs_field_description_pos]').filter(':checked').val(),
					field_id         : $('#kbs_edit_field').val(),
					form_data        : $('#post').serialize(),
					form_id          : kbs_vars.post_id,
					hide_label       : ( $('#kbs_field_hide_label').is(':checked') )      ? $('#kbs_field_hide_label').val()      : 0,
					input_class      : $('#kbs_field_input_class').val(),
					kb_search        : ( $('#kbs_field_kb_search').is(':checked') ) ? $('#kbs_field_kb_search').val() : 0,
					label            : $('#kbs_field_label').val(),
					label_class      : $('#kbs_field_label_class').val(),
					mapping          : $('#kbs_field_mapping').val(),
					placeholder      : $('#kbs_field_placeholder').val(),
					required         : ( $('#kbs_field_required').is(':checked') ) ? $('#kbs_field_required').val() : 0,
					selected         : ( $('#kbs_field_option_selected').is(':checked') ) ? $('#kbs_field_option_selected').val() : 0,
					select_options   : $('textarea#kbs_field_select_options').val(),
					select_multiple  : ( $('#kbs_field_select_multiple').is(':checked') ) ? $('#kbs_field_select_multiple').val() : 0,
					type             : $('#kbs_field_type').val(),
					value            : $('#kbs_field_value').val()
				};

				$.ajax({
					type: 'POST',
					dataType: 'json',
					data: postData,
					url: ajaxurl,
					beforeSend: function()	{
						$('#kbs-field-save').addClass('kbs-hidden');
						$('#kbs-loading').removeClass('kbs-hidden');
					},
					success: function (response) {
						window.location.href = return_url + '&kbs-message=' + response.message;
						return true;
					}
				}).fail(function (data) {
					$('#kbs-field-save').removeClass('kbs-hidden');
					$('#kbs-loading').addClass('kbs-hidden');
					if ( window.console && window.console.log ) {
						console.log( data );
					}
				});

			});

		},

		move : function() {

			$('.kbs_sortable_table tbody').sortable({
				handle: '.kbs_draghandle', items: '.kbs_sortable_row', opacity: 0.6, cursor: 'move', axis: 'y', update: function() {
					var order = $(this).sortable('serialize') + '&action=kbs_order_form_fields';

					$.post(ajaxurl, order, function()	{
						// Success
					});
				}
			});

		}

	};
	KBS_Forms.init();

	/**
	 * Customer management screen JS
	 */
	var KBS_Customer = {
		vars: {
			customer_wrap_editable:  $( '.kbs-customer-wrapper .editable' ),
			customer_wrap_edit_item: $( '.kbs-customer-wrapper .edit-item' ),
			user_id: $('input[name="customerinfo[user_id]"]'),
			note: $( '#customer-note' )
		},
		init : function() {
			this.add_customer();
            this.new_customer();
			this.edit_customer();
			this.cancel_edit();
			this.add_email();
			this.user_search();
			this.remove_user();
			this.add_note();
			this.delete_checked();
		},
		add_customer: function() {
			$( document.body ).on( 'click', '#kbs-add-customer-save', function(e) {
				e.preventDefault();
				var button  = $(this);
				var wrapper = button.parent();

				var customer_name     = $('#customer-name').val();
				var customer_company  = $('#customer_company').val();
				var customer_email    = $('#customer-email').val();
				var nonce             = $('add_customer_nonce').val();

				var postData = {
					action             : 'kbs_add_customer',
					customer_name      : customer_name,
					customer_company   : customer_company,
					customer_email     : customer_email,
					_wpnonce           : nonce
				};

				$.ajax({
					type: 'POST',
					dataType: 'json',
					data: postData,
					url: ajaxurl,
					beforeSend: function()	{
						$('.notice-wrap').html('');
						wrapper.find('.spinner').css('visibility', 'visible');
						button.attr('disabled', true);
					},
					success: function (response)	{
						if ( ! response.error )	{
							window.location.href = response.redirect;
							return true;
						} else	{
							button.attr('disabled', false);
							$('.notice-wrap').html('<div class="notice notice-error"><p>' + response.message + '</p></div>');
							wrapper.find('.spinner').css('visibility', 'hidden');
						}
					}
				}).fail(function (data) {
					if ( window.console && window.console.log ) {
						console.log( data );
					}
				});

			});
		},
        new_customer: function() {
            $( document.body ).on( 'click', '#kbs-new-customer-save', function( e ) {
                e.preventDefault();

                var button  = $(this);

                var customer_name    = $('#kbs_name').val(),
                    customer_email   = $('#kbs_email').val(),
                    customer_company = $('#kbs_company').val();

				// Return early if no name or email is entered
				if ('' === customer_name) {
					alert(kbs_vars.customer_name_required);
					return;
				}
                if ('' === customer_email) {
					alert(kbs_vars.customer_email_required);
					return;
				}

                var postData = {
					action             : 'kbs_new_customer_for_ticket',
					customer_name      : customer_name,
                    customer_email     : customer_email,
					customer_company   : customer_company
				};

                $.ajax({
					type: 'POST',
					dataType: 'json',
					data: postData,
					url: ajaxurl,
					beforeSend: function()	{
						button.attr('disabled', true);
                        $('#add-customer').addClass('kbs-mute');
					},
					success: function (response)	{
						if ( response.success )	{
							// Update customer list and exit
                            $('#kbs_customer_id').append(response.data.option);
                            $('#kbs_customer_id').val(response.data.id);
                            $('#kbs_customer_id').trigger('chosen:updated');
                            button.attr('disabled', false);
                            $('#kbs_name').val('');
                            $('#kbs_email').val('');
                            $('#kbs_company').val('-1');
                            $('#kbs_company').trigger('chosen:updated');
                            tb_remove();
						} else	{
							button.attr('disabled', false);
                            if ( response.data.message )    {
                                alert(response.data.message);
                            }
						}
					}
				}).fail(function (data) {
					if ( window.console && window.console.log ) {
						console.log( data );
					}
				});

            });
        },
		edit_customer: function() {
			$( document.body ).on( 'click', '#edit-customer', function( e ) {
				e.preventDefault();

				KBS_Customer.vars.customer_wrap_editable.hide();
				KBS_Customer.vars.customer_wrap_edit_item.fadeIn().css( 'display', 'block' );
			});
		},
		cancel_edit: function() {
			$( document.body ).on( 'click', '#kbs-edit-customer-cancel', function( e ) {
				e.preventDefault();
				KBS_Customer.vars.customer_wrap_edit_item.hide();
				KBS_Customer.vars.customer_wrap_editable.show();

				$( '.kbs_user_search_results' ).html('');
			});
		},
		add_email: function() {
			$( document.body ).on( 'click', '#add-customer-email', function(e) {
				e.preventDefault();
				var button  = $(this);
				var wrapper = button.parent();

				wrapper.parent().find('.notice-wrap').remove();
				wrapper.find('.spinner').css('visibility', 'visible');
				button.attr('disabled', true);

				var customer_id = wrapper.find('input[name="customer-id"]').val();
				var email       = wrapper.find('input[name="additional-email"]').val();
				var primary     = wrapper.find('input[name="make-additional-primary"]').is(':checked');
				var nonce       = wrapper.find('input[name="add_email_nonce"]').val();

				var postData = {
					kbs_action:  'customer-add-email',
					customer_id: customer_id,
					email:       email,
					primary:     primary,
					_wpnonce:    nonce
				};

				$.post(ajaxurl, postData, function( response ) {

					if ( true === response.success ) {
						window.location.href=response.redirect;
					} else {
						button.attr('disabled', false);
						wrapper.after('<div class="notice-wrap"><div class="notice notice-error inline"><p>' + response.message + '</p></div></div>');
						wrapper.find('.spinner').css('visibility', 'hidden');
					}

				}, 'json');

			});
		},
		user_search: function() {
			// Upon selecting a user from the dropdown, we need to update the User ID
			$( document.body ).on('click.kbsSelectUser', '.kbs_user_search_results a', function( e ) {
				e.preventDefault();
				var user_id = $(this).data('userid');
				KBS_Customer.vars.user_id.val(user_id);
			});
		},
		remove_user: function() {
			$( document.body ).on( 'click', '#disconnect-customer', function( e ) {
				e.preventDefault();
				var customer_id = $('input[name="customerinfo[id]"]').val();

				var postData = {
					kbs_action:   'disconnect-userid',
					customer_id: customer_id,
					_wpnonce:     $( '#edit-customer-info #_wpnonce' ).val()
				};

				$.post(ajaxurl, postData, function() {
					window.location.href=window.location.href;
				}, 'json');

			});
		},
		add_note : function() {
			$( document.body ).on( 'click', '#add-customer-note', function( e ) {
				e.preventDefault();
				var postData = {
					kbs_action : 'add-customer-note',
					customer_id : $( '#customer-id' ).val(),
					customer_note : KBS_Customer.vars.note.val(),
					add_customer_note_nonce: $( '#add_customer_note_nonce' ).val()
				};

				if( postData.customer_note ) {

					$.ajax({
						type: 'POST',
						data: postData,
						url: ajaxurl,
						success: function ( response ) {
							$( '#kbs-customer-notes' ).prepend( response );
							$( '.kbs-no-customer-notes' ).hide();
							KBS_Customer.vars.note.val( '' );
						}
					}).fail( function ( data ) {
						if ( window.console && window.console.log ) {
							console.log( data );
						}
					});

				} else {
					var border_color = KBS_Customer.vars.note.css( 'border-color' );
					KBS_Customer.vars.note.css( 'border-color', 'red' );
					setTimeout( function() {
						KBS_Customer.vars.note.css( 'border-color', border_color );
					}, 500 );
				}
			});
		},
		delete_checked: function() {
			$( '#kbs-customer-delete-confirm' ).change( function() {
				var submit_button = $('#kbs-delete-customer');

				if ( $(this).prop('checked') ) {
					submit_button.attr('disabled', false);
				} else {
					submit_button.attr('disabled', true);
				}
			});
		}
	};
	KBS_Customer.init();

	/**
	 * Company post screen JS
	 */
	var KBS_Company = {
		init : function() {
			this.contacts();
		},

		contacts : function()	{
			$( '#_kbs_company_customer' ).change( function() {

				if ( 0 === $('#_kbs_company_customer').val() )	{
					return;
				}

				var postData = {
					action : 'kbs_get_customer_data',
					company_id : kbs_vars.post_id,
					customer_id : $( '#_kbs_company_customer' ).val()
				};

				$.ajax({
					type: 'POST',
					dataType: 'json',
					data: postData,
					url: ajaxurl,
					success: function ( response ) {
						$( '#_kbs_company_contact' ).val( response.name );
						$( '#_kbs_company_email' ).val( response.email );
						$( '#_kbs_company_phone' ).val( response.phone );
						$( '#_kbs_company_website' ).val( response.url );
					}
				}).fail( function ( data ) {
					if ( window.console && window.console.log ) {
						console.log( data );
					}
				});

			});
		}

	};
	KBS_Company.init();

    /**
	 * Export screen JS
	 */
	var KBS_Export = {

		init : function() {
			this.submit();
			this.dismiss_message();
		},

		submit : function() {

			var self = this;

			$( document.body ).on( 'submit', '.kbs-export-form', function(e) {
				e.preventDefault();

				var submitButton = $(this).find( 'input[type="submit"]' );

				if ( ! submitButton.hasClass( 'button-disabled' ) ) {

					var data = $(this).serialize();

					submitButton.addClass( 'button-disabled' );
					$(this).find('.notice-wrap').remove();
					$(this).append( '<div class="notice-wrap"><span class="spinner is-active"></span><div class="kbs-progress"><div></div></div></div>' );

					// start the process
					self.process_step( 1, data, self );

				}

			});
		},

		process_step : function( step, data, self ) {

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					form: data,
					action: 'kbs_do_ajax_export',
					step: step,
				},
				dataType: 'json',
				success: function( response ) {
					if( 'done' === response.step || response.error || response.success ) {

						// We need to get the actual in progress form, not all forms on the page
						var export_form    = $('.kbs-export-form').find('.kbs-progress').parent().parent();
						var notice_wrap    = export_form.find('.notice-wrap');

						export_form.find('.button-disabled').removeClass('button-disabled');

						if ( response.error ) {

							var error_message = response.message;
							notice_wrap.html('<div class="updated error"><p>' + error_message + '</p></div>');

						} else if ( response.success ) {

							var success_message = response.message;
							notice_wrap.html('<div id="kbs-batch-success" class="updated notice is-dismissible"><p>' + success_message + '<span class="notice-dismiss"></span></p></div>');

						} else {

							notice_wrap.remove();
							window.location = response.url;

						}

					} else {
						$('.kbs-progress div').animate({
							width: response.percentage + '%',
						}, 50, function() {
							// Animation complete.
						});
						self.process_step( parseInt( response.step ), data, self );
					}

				}
			}).fail(function (response) {
				if ( window.console && window.console.log ) {
					console.log( response );
				}
			});

		},

		dismiss_message : function() {
			$('body').on( 'click', '#kbs-batch-success .notice-dismiss', function() {
				$('#kbs-batch-success').parent().slideUp('fast');
			});
		}

	};
	KBS_Export.init();

	// AJAX user search
	$('.kbs-ajax-user-search').keyup(function() {
		var user_search = $(this).val();
		var exclude     = '';

		if ( $(this).data('exclude') ) {
			exclude = $(this).data('exclude');
		}

		$('.kbs-ajax').show();
		var data = {
			action: 'kbs_search_users',
			user_name: user_search,
			exclude: exclude
		};

		document.body.style.cursor = 'wait';

		$.ajax({
			type: 'POST',
			data: data,
			dataType: 'json',
			url: ajaxurl,
			success: function (search_response) {

				$('.kbs-ajax').hide();
				$('.kbs_user_search_results').removeClass('hidden');
				$('.kbs_user_search_results span').html('');
				$(search_response.results).appendTo('.kbs_user_search_results span');
				document.body.style.cursor = 'default';
			}
		});
	});

	$( document.body ).on('click.kbsSelectUser', '.kbs_user_search_results span a', function(e) {
		e.preventDefault();
		var login = $(this).data('login');
		$('.kbs-ajax-user-search').val(login);
		$('.kbs_user_search_results').addClass('hidden');
		$('.kbs_user_search_results span').html('');
	});

	$( document.body ).on('click.kbsCancelUserSearch', '.kbs_user_search_results a.kbs-ajax-user-cancel', function(e) {
		e.preventDefault();
		$('.kbs-ajax-user-search').val('');
		$('.kbs_user_search_results').addClass('hidden');
		$('.kbs_user_search_results span').html('');
	});

	if( $('#kbs_dashboard_tickets').length ) {
		$.ajax({
			type: 'GET',
			data: {
				action: 'kbs_load_dashboard_widget'
			},
			url: ajaxurl,
			success: function (response) {
				$('#kbs_dashboard_tickets .inside').html( response );
			}
		});
	}

	$(document).on('keydown', '.customer-note-input', function(e) {
		if(e.keyCode === 13 && (e.metaKey || e.ctrlKey)) {
			$('#add-customer-note').click();
		}
	});


	/**
	 * Floating widget settings conditions
	 */
	if ( $( 'input[name="kbs_settings[floating_widget]"]' ).is( ':checked' ) ) {
		$( 'tr.kbs_option_floating_widget_form, tr.kbs_option_floating_widget_icon, tr.kbs_option_floating_widget_position, tr.kbs_option_floating_widget_color, tr.kbs_option_floating_widget_label' ).show();
	} else {
		$( 'tr.kbs_option_floating_widget_form, tr.kbs_option_floating_widget_icon, tr.kbs_option_floating_widget_position, tr.kbs_option_floating_widget_color, tr.kbs_option_floating_widget_label' ).hide();
	}

	$( 'input[name="kbs_settings[floating_widget]"]' ).on( 'change', function () {

		if ( $( this ).is( ':checked' ) ) {
			$( 'tr.kbs_option_floating_widget_form, tr.kbs_option_floating_widget_icon, tr.kbs_option_floating_widget_position, tr.kbs_option_floating_widget_color, tr.kbs_option_floating_widget_label' ).show();
		} else {
			$( 'tr.kbs_option_floating_widget_form, tr.kbs_option_floating_widget_icon, tr.kbs_option_floating_widget_position, tr.kbs_option_floating_widget_color, tr.kbs_option_floating_widget_label' ).hide();
		}
	} );

	/**
	 * Forms redirection action conditions
	 */

	if ( 'redirect' === $( 'select#kbs_form_submission' ).val() ) {
		$( '#kbs_form_submission_text_wrapper' ).hide();
	} else {
		$( '#kbs_form_redirect_wrapper' ).hide();
	}

	$( 'select#kbs_form_submission' ).on( 'change', function () {

		if ( 'redirect' === $( this ).val() ) {
			$( '#kbs_form_submission_text_wrapper' ).hide();
			$( '#kbs_form_redirect_wrapper' ).show();
		} else {
			$( '#kbs_form_submission_text_wrapper' ).show();
			$( '#kbs_form_redirect_wrapper' ).hide();
		}
	} );

});

// Retrieve ticket replies
function kbs_load_ticket_replies( ticket_id, reply_id, page )	{

	jQuery('#kbs-replies-loader').html('<img src="' + kbs_vars.ajax_loader + '" />');

	jQuery.post(ajaxurl,
		{
			action: 'kbs_display_ticket_replies',
			kbs_ticket_id: ticket_id,
			kbs_reply_id: reply_id,
			kbs_page: page
		},
		function(response)	{
			jQuery( '.kbs-historic-reply-option-fields' ).append( response );
			jQuery('#kbs-replies-loader').html('');
		}
	);
}

function kbs_load_ticket_reply( ticket_id, reply_id, page ) {

	jQuery( '#kbs-replies-loader' ).html( '<img src="' + kbs_vars.ajax_loader + '" />' );

	jQuery.post( ajaxurl,
		{
			action       : 'kbs_display_ticket_replies',
			kbs_ticket_id: ticket_id,
			kbs_reply_id : reply_id,
			kbs_page     : page,
		},
		function ( response ) {
			jQuery( '.kbs-historic-reply-option-fields' ).prepend( '<div class="kbs_historic_replies_wrapper " expanded="true">' + response + '</div>' );
			jQuery( '#kbs-replies-loader' ).html( '' );
			jQuery( '#kbs-new-reply-loader' ).html( '' );
			jQuery( 'iframe#kbs_ticket_reply_ifr' ).contents().find( 'body#tinymce' ).html( '' );
			jQuery( '#kbs-ticket-reply-wrap' ).addClass( 'kbs-hidden' );
		}
	);
}

// Retrieve ticket note
function kbs_load_ticket_note( ticket_id, note_id )	{
	jQuery('#kbs-notes-loader').html('<img src="' + kbs_vars.ajax_loader + '" />');

	jQuery.post( ajaxurl, {
			action       : 'kbs_display_ticket_notes',
			kbs_ticket_id: ticket_id,
			kbs_note_id  : note_id,
		},
		function ( response ) {
			jQuery( '.kbs-historic-reply-option-fields' ).prepend( '<div class="kbs_historic_replies_wrapper " expanded="true">' + response + '</div>' );
			jQuery( '#kbs-notes-loader' ).html( '' );
			jQuery( '#kbs-ticket-add-note-container' ).addClass( 'kbs-hidden' );
		}
	);
}

// Retrieve ticket notes
function kbs_load_ticket_notes( ticket_id, note_id )	{
	jQuery('#kbs-notes-loader').html('<img src="' + kbs_vars.ajax_loader + '" />');

	jQuery.post(ajaxurl, { action: 'kbs_display_ticket_notes', kbs_ticket_id: ticket_id, kbs_note_id: note_id },
		function(response)	{
			jQuery('.kbs-notes-option-fields').prepend(response);
			jQuery('#kbs-notes-loader').html('');
		}
	);
}

/**
 * Shows message pop-up notice or confirmation message.
 *
 * @since 1.2.4
 * @type {{warn: KBSShowNotice.warn, note: KBSShowNotice.note}}
 * @returns {void}
 */
KBSShowNotice = {

	/**
	 * Shows a delete confirmation pop-up message on the ticket screen.
     *
     * @since 1.2.4
     * @param      string  The object type to which the notice applies.
	 * @return     bool    Returns true if the message is confirmed.
	 */
	warn : function( type ) {
        if ( type === undefined )   {
            type = 'ticket';
        }

        var msg = kbs_vars.delete_ticket_warn || '';

		if ( confirm(msg) ) {
			return true;
		}

		return false;
	},

	/**
	 * Shows an alert message.
	 *
	 * @since 2.7.0
	 *
	 * @param text The text to display in the message.
	 */
	note : function(text) {
		alert(text);
	}
};
