<?php
/**
 * Shortcodes
 *
 * @package     KBS
 * @subpackage  Shortcodes
 * @copyright   Copyright (c) 2017, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Login Shortcode
 *
 * Shows a login form allowing users to users to log in. This function simply
 * calls the kbs_login_form function to display the login form.
 *
 * @since	1.0
 * @param	att		$atts	Shortcode attributes
 * @uses	kbs_login_form()
 * @return	str
 */
function kbs_login_form_shortcode( $atts ) {
	$args = shortcode_atts( array(
			'redirect' => '',
	), $atts, 'kbs_login' );

	return kbs_login_form( $args['redirect'] );
} // kbs_login_form_shortcode
add_shortcode( 'kbs_login', 'kbs_login_form_shortcode' );

/**
 * Register Shortcode
 *
 * Shows a registration form allowing users to users to register for the site.
 *
 * @since	1.0
 * @param	arr		$atts		Shortcode attributes
 * @uses	kbs_register_form()
 * @return	str
 */
function kbs_register_form_shortcode( $atts ) {
	$args = shortcode_atts( array(
		'redirect' => '',
	), $atts, 'kbs_register' );

	return kbs_register_form( $args['redirect'] );
} // kbs_register_form_shortcode
add_shortcode( 'kbs_register', 'kbs_register_form_shortcode' );

/**
 * Profile Editor Shortcode
 *
 * Allow users to amend their account details details from the front-end.
 *
 * @since 1.0
 *
 * @param	arr		$atts	Shortcode attributes
 * @return	str
 */
function kbs_profile_editor_shortcode( $atts ) {
	ob_start();

	kbs_get_template_part( 'shortcode', 'profile-editor' );

	return ob_get_clean() . '&nbsp;';
} // kbs_profile_editor_shortcode
add_shortcode( 'kbs_profile_editor', 'kbs_profile_editor_shortcode' );

/**
 * Ticket Form Shortcode
 *
 * Displays the ticket submission form
 *
 * @since	1.0
 * @param	arr		$atts		Shortcode attributes
 * @return	str
 */
function kbs_submit_form_shortcode( $atts ) {
	if ( ! kbs_user_can_submit() )	{
        ob_start();
        echo kbs_display_notice( 'need_login' );

        $register_login = kbs_get_option( 'show_register_form', 'none' );

        if ( 'both' == $register_login || 'login' == $register_login )	{
            echo kbs_login_form( kbs_get_current_page_url() );
        }

        if ( 'both' == $register_login || 'registration' == $register_login )	{
            echo kbs_register_form( kbs_get_current_page_url() );
        }

        $output = ob_get_clean();
        $output = apply_filters( 'kbs_user_cannot_submit', $output );

        return $output;
	} else	{

		$args = shortcode_atts( array(
			'form' => 0,
			), $atts, 'kbs_submit' );
	
		return kbs_display_form( $args['form'] );

	}
} // kbs_submit_form_shortcode
add_shortcode( 'kbs_submit', 'kbs_submit_form_shortcode' );

/**
 * View Tickets Shortcode.
 *
 * Displays a customers ticket, or their ticket history.
 *
 * @since	1.0
 * @param	arr		$atts		Shortcode attributes
 * @return	str
 */
function kbs_tickets_shortcode( $atts )	{
	ob_start();

	if ( isset( $_GET['ticket'] ) )	{
		if ( kbs_get_option( 'logged_in_only' ) && ! is_user_logged_in() )	{
			$redirect = add_query_arg( array( 'ticket' => $_GET['ticket'] ), get_permalink( kbs_get_option( 'tickets_page' ) ) );
			echo kbs_display_notice( 'ticket_login' );
	
			$register_login = kbs_get_option( 'show_register_form', 'none' );
	
			if ( 'both' == $register_login || 'login' == $register_login )	{
				echo kbs_login_form( $redirect );
			}
	
			if ( 'both' == $register_login || 'registration' == $register_login )	{
				echo kbs_register_form( $redirect );
			}
		} else	{
			kbs_get_template_part( 'view', 'ticket' );
		}
	} else	{
		kbs_get_template_part( 'ticket', 'history' );
	}

	return ob_get_clean() . '&nbsp;';
} // kbs_tickets_shortcode
add_shortcode( 'kbs_tickets', 'kbs_tickets_shortcode' );

/**
 * Search Form Shortcode
 *
 * Shows a search form allowing users to search KB Articles. This function simply
 * calls the kbs_article_search_form function to display the search form.
 *
 * @since	1.0
 * @param	arr		$atts	Shortcode attributes
 * @uses	kbs_article_search_form()
 * @return	str
 */
function kbs_article_search_form_shortcode()	{
	return kbs_article_search_form();
} // kbs_article_search_form_shortcode
add_shortcode( 'kbs_search', 'kbs_article_search_form_shortcode' );

/**
 * Articles Shortcode
 *
 * Displays Articles that meet the criteria set within given arguments.
 *
 * @since	1.0
 * @param	att		$atts	Shortcode attributes
 * @return	str
 */
function kbs_articles_shortcode( $atts )	{

	$args = shortcode_atts( array(
		'articles'        => null,    // Article IDs to display
		'number'          => 20,      // Number of posts to display
		'author'          => null,    // Article author IDs to include
		'orderby'         => 'views', // Order by
		'order'           => 'DESC',  // Order
		'tags'            => 0,       // Tag IDs to include, comma seperate
		'categories'      => 0,       // Category IDs to include, comma seperate
		'tax_relation'    => 'AND',   // Tax query relation
		'excerpt'         => 1,       // Whether to display an excerpt
		'hide_restricted' => (bool) kbs_hide_restricted_articles() // Whether to hide restricted articles
	), $atts, 'kbs_articles' );

	if ( isset( $args['articles'] ) )	{
		$args['post__in'] = explode( ',', $args['articles'] );
		unset( $args['articles'] );
	}

	$args['posts_per_page'] = $args['number'];
	unset( $args['number'] );

	if ( isset( $args['author'] ) )	{
		$args['author__in'] = explode( ',', $args['author'] );
		unset( $args['author'] );
	}

	$args['tax_query'] = array();

	if ( ! empty( $args['tags'] ) )	{
		if ( ! empty( $args['categories'] ) )	{
			$args['tax_query'] = array( 'relation' => $args['tax_relation'] );
		}

		$args['tax_query'][] = array(
			'taxonomy' => 'article_tag',
			'terms'    => explode( ',', $args['tags'] )
		);
	}

	if ( ! empty( $args['categories'] ) )	{
		$args['tax_query'][] = array(
			'taxonomy' => 'article_category',
			'terms'    => explode( ',', $args['categories'] )
		);
	}

	if ( 'views' == $args['orderby'] )	{
		$args['meta_key'] = kbs_get_article_view_count_meta_key_name();
		$args['orderby']  = 'meta_value_num';
	}

	if ( 'views_month' == $args['orderby'] )	{
		$args['meta_key'] = kbs_get_article_view_count_meta_key_name( false );
		$args['orderby']  = 'meta_value_num';
	}

	$args['post_type'] = 'kbs_article';

	// Cleanup non WP_Query args
	unset( $args['tax_relation'] );
	unset( $args['categories'] );
	unset( $args['tags'] );

	// Allow developers to filter the WP_Query args
	$args = apply_filters( 'kbs_articles_shortcode_args', $args );

	if ( empty( $args['hide_restricted'] ) )	{
		remove_action( 'pre_get_posts', 'kbs_articles_exclude_restricted' );
	}

	$articles_query = new WP_Query( $args );

	if ( empty( $args['hide_restricted'] ) )	{
		add_action( 'pre_get_posts', 'kbs_articles_exclude_restricted' );
	}

	ob_start();

	if ( $articles_query->have_posts() ) : ?>
        <div id="kbs_articles_list">
            <ul>
    
            <?php while( $articles_query->have_posts() ) :
    
                $articles_query->the_post();
				$article_id     = get_the_ID(); ?>
    
                <?php if ( ! $args['hide_restricted'] || kbs_article_user_can_access( $article_id ) ) : ?>
    
                    <li>
                    	<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        <?php if ( ! empty( $args['excerpt'] ) ) : ?>
                        	<span class="article_excerpt">
								<?php echo kbs_get_article_excerpt( $article_id ); ?>
                            </span>
                        <?php endif; ?>
                    </li>
    
                <?php endif; ?>
    
            <?php endwhile; ?>
    
            </ul>
        </div>

        <?php wp_reset_postdata(); ?>
    <?php else : ?>
        <p><?php printf( __( 'No %s found', 'kb-support' ), kbs_get_article_label_plural( true ) ); ?></p>
    <?php endif;

	return ob_get_clean();

} // kbs_articles_shortcode
add_shortcode( 'kbs_articles', 'kbs_articles_shortcode' );
