var kbs_scripts;
jQuery(document).ready(function ($) {

	/* = Datepicker
	====================================================================================== */
	var kbs_datepicker = $( '.kbs_datepicker' );
	if ( kbs_datepicker.length > 0 ) {
		var dateFormat = 'mm/dd/yy';
		kbs_datepicker.datepicker( {
			dateFormat: dateFormat
		} );
	}

	/* = Chosen select fields
	================================r====================================================== */
	if ( kbs_scripts.is_submission )	{
		$('.kbs-select-chosen').chosen({
			inherit_select_classes: true,
            placeholder_text_single: kbs_scripts.one_option,
            placeholder_text_multiple: kbs_scripts.one_or_more_option
		});

        $('.kbs-select-chosen .chosen-search input').each( function() {
            var selectElem = $(this).parent().parent().parent().prev('select.kbs-select-chosen'),
                placeholder = selectElem.data('search-placeholder');
            $(this).attr( 'placeholder', placeholder );
        });

        // Add placeholders for Chosen input fields
        $( '.chosen-choices' ).on( 'click', function () {
            var placeholder = $(this).parent().prev().data('search-placeholder');
            if ( typeof placeholder === 'undefined' ) {
                placeholder = kbs_scripts.type_to_search;
            }
            $(this).children('li').children('input').attr( 'placeholder', placeholder );
        });
	}

    /* = Accordian
	====================================================================================== */
    if ( kbs_scripts.needs_bs4 )	{
		$('.kbs-accordian').collapse({
			toggle: false
		});
	}

	/* = Scroller
	====================================================================================== */
	$( document.body ).on( 'click', '.kbs-scroll', function(e) {
		e.preventDefault();

		var target = $(this).attr('href');

		$('html, body').animate({
			scrollTop: $(target).offset().top
		}, 500 );
	});

	/* = reCAPTCHA V3
	====================================================================================== */
    if ( $( '#recaptcha-action' ).length ) {
        kbs_recaptcha_V3();
    }

	/* = Floating widget wrapper toggle
	 ====================================================================================== */
	$( document.body ).on( 'click', '.kbs-beacon-header-navigation span', function () {
		var btn    = $( this ),
		    id     = btn.attr( 'id' ),
		    target = $( 'div[data-toggle="' + id + '"]' );

		$( '.kbs-beacon-header-navigation span' ).toggleClass( 'active' );
		$( 'div[data-toggle]' ).not( target ).addClass( 'hide' );
		target.removeClass( 'hide' );

		if ( 'kbs-beacon-ask' === id ) {
			$( '#kbs-beacon .kbs-beacon-search-wrapper' ).addClass( 'hide' );
		} else {
			$( '#kbs-beacon .kbs-beacon-search-wrapper' ).removeClass( 'hide' );
		}
	} );

    /* = Ticket submission form validation and submission
	====================================================================================== */
	$(document).on('click', '#kbs_ticket_form #kbs_ticket_submit', function(e) {
		var kbsTicketForm = $(this).parents('#kbs_ticket_form');

		if( typeof kbsTicketForm.checkValidity === 'function' && false === kbsTicketForm.checkValidity() ) {
			return;
		}

		e.preventDefault();
		$(this).val(kbs_scripts.submit_ticket_loading);
		$(this).prop('disabled', true);
		$(this).after(' <span id="kbs-loading" class="kbs-loader"><img src="' + kbs_scripts.ajax_loader + '" /></span>');
		kbsTicketForm.find('input').removeClass('error');

		var tinymceActive = (typeof tinyMCE !== 'undefined') && tinyMCE.activeEditor && ! tinyMCE.activeEditor.isHidden();

		if (tinymceActive) {
			tinyMCE.triggerSave();
		}

        var ticketData = kbsTicketForm.serialize();

		$.ajax({
			type       : 'POST',
			dataType   : 'json',
			data       : ticketData,
			url        : kbs_scripts.ajaxurl,
			success    : function (response) {
				if ( response.error )	{
					kbsTicketForm.find('.kbs_alert_error').show('fast');
					kbsTicketForm.find('.kbs_alert_error').html(response.error);

					kbsTicketForm.find('#kbs_ticket_submit').prop('disabled', false);
					kbsTicketForm.find('#kbs_ticket_form_submit').find('#kbs-loading').remove();
					kbsTicketForm.find('#kbs_ticket_submit').val(kbs_scripts.submit_ticket);
				} else	{
					kbsTicketForm.append( '<input type="hidden" name="kbs__form_action" value="submit_ticket" />' );
					kbsTicketForm.append( '<input type="hidden" name="action" value="kbs__form_action" />' );
					var ticketDataa = kbsTicketForm.serialize();

					$.ajax({
						type       : 'POST',
						dataType   : 'json',
						data       : ticketDataa,
						url        : kbs_scripts.ajaxurl,
						success    : function (myResponse) {

							if ( myResponse.error ) {

							} else {
								if ( 'undefined' != typeof myResponse.kbs_submission_text ) {
									kbsTicketForm.parents( '#kbs_ticket_wrap' ).addClass( 'form-submitted' );
									kbsTicketForm.parents( '#kbs_ticket_form_wrap' ).html( '<p>' + myResponse.kbs_submission_text + '</p>' );
								} else if ( 'undefined' != typeof myResponse.kbs_submission_redirect ) {
									window.location.href = myResponse.kbs_submission_redirect
								}
							}
						}
					}).fail(function (data) {
						if ( window.console && window.console.log ) {
							console.log(data);
						}
					});

				}
			}
		}).fail(function (data) {
			if ( window.console && window.console.log ) {
				console.log( data );
			}
		});

	});

	/* = Ticket reply form validation and submission
	====================================================================================== */
    // Mark reply as read
	$( document ).on( 'click', '.ticket_reply_content, .toggle-view-reply-option-section[aria-expanded="false"]', function () {
		var reply_id = $( this ).data( 'key' );
		kbs_cust_read_reply( reply_id );
		var reply_attention = $( this ).parents( '.kbs-replies-row-header' ).find( '.new-reply' );
		if ( 'undefined' != reply_attention ) {
			setTimeout( function () {
				reply_attention.hide();
			}, 600 );
		}
	} );

    // Load more replies
    $( document.body ).on( 'click', '#kbs-replies-next-page', function(e) {
        e.preventDefault();

        var ticket_id = $('#kbs-replies-next-page').data('ticket-id');
        var page      = $('#kbs-replies-next-page').attr('data-load-page');
        var postData  = {
            kbs_ticket_id : ticket_id,
            kbs_page      : page,
            action        : 'kbs_load_front_end_replies'
        };

        $.ajax({
			type       : 'POST',
			dataType   : 'json',
			data       : postData,
			url        : kbs_scripts.ajaxurl,
            beforeSend: function()	{
                $('.kbs_replies_load_more').hide();
                $('#kbs-replies-loader').html('<img src="' + kbs_scripts.ajax_loader + '" />');
            },
			success : function (response) {
                $('#kbs-loading-replies').replaceWith(response.data.replies);
                $('#kbs-replies-loader').html('');

                if ( response.data.next_page > 0 && response.data.next_page > page )    {
                    $('.kbs_replies_load_more').show();
                    $('#kbs-replies-next-page').attr('data-load-page', response.data.next_page);
                }
			}
		}).fail(function (data) {
			if ( window.console && window.console.log ) {
				console.log( data );
			}
		});
    });

    // Post a new reply
	$(document).on('click', '#kbs_ticket_reply_form #kbs_reply_submit', function(e) {
		var kbsReplyForm = document.getElementById('kbs_ticket_reply_form');

		if( typeof kbsReplyForm.checkValidity === 'function' && false === kbsReplyForm.checkValidity() ) {
			return;
		}

		e.preventDefault();
		$(this).val(kbs_scripts.submit_ticket_loading);
		$(this).prop('disabled', true);
		$(this).after(' <span id="kbs-loading" class="kbs-loader kbs-hidden"><img src="' + kbs_scripts.ajax_loader + '" /></span>');
		$('input').removeClass('error');

		var tinymceActive = (typeof tinyMCE !== 'undefined') && tinyMCE.activeEditor && ! tinyMCE.activeEditor.isHidden();

		if (tinymceActive) {
			tinyMCE.triggerSave();
		}

		var $form       = $('#kbs_ticket_reply_form');
		var ticketData  = $('#kbs_ticket_reply_form').serialize();

		$.ajax({
			type       : 'POST',
			dataType   : 'json',
			data       : ticketData,
			url        : kbs_scripts.ajaxurl,
			success    : function (response) {
				if ( response.error )	{
					$form.find('.kbs_alert').show('fast');
					$form.find('.kbs_alert').html(response.error);
					$form.find('#' + response.field).addClass('error');
					$form.find('#' + response.field).focus();
					$('#kbs_reply_submit').prop('disabled', false);
					$('#kbs-loading').remove();
					$('#kbs_reply_submit').val(kbs_scripts.reply_label);
				} else	{
					$form.append( '<input type="hidden" name="kbs_action" value="submit_ticket_reply" />' );
					$form.get(0).submit();
				}
			}
		}).fail(function (data) {
			if ( window.console && window.console.log ) {
				console.log( data );
			}
		});

	});

});

/* = Mark ticket replies as read
====================================================================================== */
function kbs_cust_read_reply(reply_id)  {
    jQuery.post(
        kbs_scripts.ajaxurl,
        {
            action: 'kbs_read_ticket_reply',
            reply_id:    reply_id
        }
    );
}

/* = reCAPTCHA V3
====================================================================================== */
function kbs_recaptcha_V3()  {
    var recaptcha_version  = kbs_scripts.recaptcha_version,
        recaptcha_site_key = kbs_scripts.recaptcha_site_key;

    if ( 'v3' === recaptcha_version && false !== recaptcha_site_key )  {
        grecaptcha.ready(function() {
            grecaptcha.execute(recaptcha_site_key, {
                action: 'submit_kbs_form'
            }).then(function(token) {
                jQuery('#g-recaptcha-response').val( token );
                jQuery('#recaptcha-action').val( 'submit_kbs_form' );
            });
        });

        setInterval(function () {
            grecaptcha.ready(function() {
                grecaptcha.execute(recaptcha_site_key, {
                    action: 'submit_kbs_form'
                }).then(function(token) {
                    jQuery('#g-recaptcha-response').val( token );
                    jQuery('#recaptcha-action').val( 'submit_kbs_form' );
                });
            });
        }, 90 * 1000);
    }
}
