jQuery(document).ready(function ($) {

	var search_timeout_id = null;

	// Hide the articles when the close link is clicked
	$('#close-search').click(function() {
        $('.kbs-article-search-results').hide('slow');
    });

	// Execute the article search
	function find_article( search_text, $element )	{

		var $form = $element.parents('form#kbs_ticket_form');
		$form.find('#kbs-article-results').html('');

		if( search_text.length < kbs_search_vars.min_search_trigger )	{
			$form.find('.kbs-article-search-results').hide('slow');
			return;
		}

		$form.find('.kbs-article-search-results').hide('fast');
		$form.find('#kbs-loading').html('<img src="' + kbs_scripts.ajax_loader + '" />');
		$form.find('#kbs-loading').show('fast');

		var postData = {
			term   : search_text,
			action : 'kbs_ajax_article_search'
		};

		$.ajax({
			type       : 'POST',
			dataType   : 'json',
			data       : postData,
			url        : kbs_scripts.ajaxurl,
			success    : function (response) {
				if ( response.articles && '' !== response.articles )	{
					$form.find('#kbs-article-results').html(response.articles);
					$form.find('.kbs-article-search-results').show('slow');
				} else	{
					$form.find('#kbs-article-results').html();
					$form.find('.kbs-article-search-results').hide('slow');
				}
			},
			complete: function()	{
				$form.find('#kbs-loading').hide('fast');
				$form.find('#kbs-loading').html('');
			}
		}).fail(function (data) {
			if ( window.console && window.console.log ) {
				console.log( data );
			}
		});

	}

	// Calls the article search function
	$( '.kbs-article-search' ).keyup( function(e)	{
		clearTimeout( search_timeout_id );
		search_timeout_id = setTimeout( find_article.bind( undefined, e.target.value ,$(this)), 500 );
	});

	// Calls the article search function
	$( '#kbs-beacon-search-input' ).keyup( function(e)	{
		clearTimeout( search_timeout_id );
		search_timeout_id = setTimeout( find_floating_article.bind( undefined, e.target.value ,$(this)), 500 );
	});

	// Execute the article search
	function find_floating_article( search_text, $element )	{

		var wrapper = $element.parents( '#kbs-beacon' ).find( '.kbs-beacon-articles-wrapper' );
		wrapper.find( '#kbs-article-results' ).html( '' );

		if ( search_text.length < kbs_search_vars.min_search_trigger ) {
			return;
		}

		var postData = {
			term  : search_text,
			action: 'kbs_ajax_floating_article_search'
		};

		$.ajax( {
			type    : 'POST',
			dataType: 'json',
			data    : postData,
			url     : kbs_scripts.ajaxurl,
			success : function ( response ) {
				if ( response.articles && '' !== response.articles ) {
					wrapper.html( response.articles );
				} else {
					wrapper.html();
				}
			},
			complete: function () {

			}
		} ).fail( function ( data ) {
			if ( window.console && window.console.log ) {
				console.log( data );
			}
		} );
	}
});
