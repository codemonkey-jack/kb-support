<?php
/**
 * Contextual Help
 *
 * @package     KBS
 * @subpackage  Admin/Articles
 * @copyright   Copyright (c) 2017, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * KB Articles contextual help.
 *
 * @since       1.0
 * @return      void
 */
function kbs_article_contextual_help() {
	$screen = get_current_screen();

	if ( 'kbs_article' != $screen->id )	{
		return;
	}

	$article_singular = kbs_get_article_label_singular();
	$article_plural   = kbs_get_article_label_plural();
	$ticket_singular  = kbs_get_ticket_label_singular();
	$ticket_plural    = kbs_get_ticket_label_plural();

	$screen->set_help_sidebar( kbs_get_contextual_help_sidebar_text() );

    do_action( 'kbs_article_before_general_contextual_help', $screen );
	$screen->add_help_tab( array(
		'id'      => 'kbs-article-general',
		'title'   => __( 'General', 'kb-support' ),
		'content' => apply_filters( 'kbs_article_general_contextual_help',
			'<p>' . sprintf( 
				__( 'In general you should write your %1$s in the same way you would a normal post. You can assign categories and tags as required, and these can be used within the <code>[kbs_articles]</code> shortcode to filter the %2$s to display.', 'kb-support' ),
				$article_singular,
				$article_plural
			) . '</p>' .
			'<p>' . sprintf( 
				__( '<strong>Title</strong> - Give your %1$s a title. Make it relevant and think about possible search terms customers may use.', 'kb-support' ),
				$article_singular
			) . '</p>' .
			'<p>' . sprintf( 
				__( '<strong>Content</strong> - The content of your %1$s. Write it in the way you would normally write customer documentation. Keep search terms in mind.', 'kb-support' ),
				$article_singular
			) . '</p>' .
			'<p>' . sprintf( 
				__( '<strong>Excerpt</strong> - Excerpts take precedence over content when %1$s are displayed following a search or listed by the <code>[kbs_articles]</code>.', 'kb-support' ), $article_plural ) .
			'</p>'
        )
	) );

    do_action( 'kbs_article_before_linked_contextual_help', $screen );
	$screen->add_help_tab( array(
		'id'      => 'kbs-article-linked',
		'title'   => sprintf( __( 'Linked %s', 'kb-support' ), $ticket_plural ),
		'content' => apply_filters( 'kbs_article_linked_contextual_help',
			'<p>' . sprintf( 
				__( '%1$s that have a reference to this %2$s will be displayed here.', 'kb-support' ),
				$ticket_plural,
				$article_singular
			) . '</p>'
        )
	) );

    do_action( 'kbs_article_before_options_contextual_help', $screen );
	$screen->add_help_tab( array(
		'id'      => 'kbs-article-optons',
		'title'   => __( 'Options', 'kb-support' ),
		'content' => apply_filters( 'kbs_article_options_contextual_help',
			'<p>' . sprintf( 
				__( '<strong>Restrict Access</strong> - Select to restrict access to this %1$s to logged in users only.', 'kb-support' ),
				$article_singular
			) . '</p>'
        )
	) );

	do_action( 'kbs_article_contextual_help' );

} // kbs_article_contextual_help
add_action( 'load-post.php', 'kbs_article_contextual_help' );
add_action( 'load-post-new.php', 'kbs_article_contextual_help' );
