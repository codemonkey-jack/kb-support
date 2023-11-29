jQuery(document).on('change', '.kbs_option_disable_tickets input', function () {
    var input = jQuery(this),
        inputTR = jQuery(this).parents('tr.kbs_option_disable_tickets');
    var targets = jQuery(this).parents('table').find('tr').not(inputTR);

    jQuery('.kbs-settings-sub-nav li').each(function (el, value) {
        targets.push(value);
    });
    kbsAdminConditions({input: input, inputTR: inputTR, targets: targets});
});

jQuery(document).ready(function ($) {
    var input = jQuery('.kbs_option_disable_tickets input'),
        inputTR = input.parents('tr.kbs_option_disable_tickets');
    var targets = input.parents('table').find('tr').not(inputTR);

    input.parents('#wpbody-content').find('.kbs-settings-sub-nav li').each(function (el, value) {
        targets.push(value);
    });
    kbsAdminConditions({input: input, inputTR: inputTR, targets: targets});
});

jQuery(document).on('change', '.kbs_option_disable_kb_articles input', function () {
    var input = jQuery(this),
        inputTR = jQuery(this).parents('tr.kbs_option_disable_kb_articles');
    var targets = jQuery(this).parents('table').find('tr').not(inputTR);

    jQuery('.kbs-settings-sub-nav li').each(function (el, value) {
        targets.push(value);
    });
    kbsAdminConditions({input: input, inputTR: inputTR, targets: targets});
});

jQuery(document).ready(function ($) {
    var input = jQuery('.kbs_option_disable_kb_articles input'),
        inputTR = input.parents('tr.kbs_option_disable_kb_articles');
    var targets = input.parents('table').find('tr').not(inputTR);

    input.parents('#wpbody-content').find('.kbs-settings-sub-nav li').each(function (el, value) {
        targets.push(value);
    });
    kbsAdminConditions({input: input, inputTR: inputTR, targets: targets});
});

// Show/hide the settings based on the checkbox.
function kbsAdminConditions(object) {
    var input = object.input,
        inputTr = object.inputTr,
        targets = object.targets;
    if (input.length && input.is(':checked')) {
        targets.hide();
    } else {
        targets.show();
    }
}

// Dismiss admin notices
jQuery(document).on('click', '.notice-kbs-dismiss .notice-dismiss', function () {
    var notice = jQuery(this).closest('.notice-kbs-dismiss').data('notice');

    var postData = {
        notice: notice,
        action: 'kbs_dismiss_notice'
    };

    jQuery.ajax({
        type: 'POST',
        dataType: 'json',
        data: postData,
        url: ajaxurl
    });
});
