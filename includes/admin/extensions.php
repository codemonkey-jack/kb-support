<?php
/**
 * Admin Extensions
 *
 * @package     KBS
 * @subpackage  Admin/Extensions
 * @copyright   Copyright (c) 2017, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Display the extensions page.
 *
 * @since	1.0.3
 * @return	void
 */
function kbs_extensions_page()	{
	setlocale( LC_MONETARY, get_locale() );
	$extensions     = kbs_get_extensions();
	$tags           = '<a><em><strong><blockquote><ul><ol><li><p>';
	$length         = 55;
	$extensions_url = esc_url( add_query_arg( array(
		'utm_source'   => 'plugin-extensions-page',
		'utm_medium'   => 'plugin',
		'utm_campaign' => 'KBS_Extensions_Page',
		'utm_content'  => 'All Extensions'
	), 'https://kb-support.com/downloads/' ) );

	$newsletter_url = esc_url( add_query_arg( array(
		'utm_source'   => 'plugin-extensions-page',
		'utm_medium'   => 'newsletter',
		'utm_campaign' => 'KBS_Extensions_Page',
		'utm_content'  => 'newsletter_signup'
	), 'https://kb-support.com/#newsletter-signup' ) );

	$slug_corrections = array(
		'ratings-and-satisfaction' => 'ratings-satisfaction',
		'easy-digital-downloads'   => 'edd'
	);

	?>
	<div class="wrap about-wrap kbs-about-wrapp">
		<h1>
			<?php _e( 'Extensions for KB Support', 'kb-support' ); ?>
		</h1>
		<div>
        	<p><a href="<?php echo $extensions_url; ?>" class="button-primary" target="_blank"><?php _e( 'Browse All Extensions', 'kb-support' ); ?></a></p>
			<p class="newsletter-intro"><?php _e( 'These extensions <em><strong>add even more functionality</strong></em> to your KB Support help desk.', 'kb-support' ); ?></p>
            <?php kbs_get_newsletter(); ?>
		</div>

		<div class="kbs-extension-wrapper grid3">
			<?php foreach ( $extensions as $key => $extension ) :
				$the_excerpt = '';
				$slug        = $extension->info->slug;
				$price       = false;
				$link        = 'https://kb-support.com/downloads/' . $slug .'/';
				$link        = esc_url( add_query_arg( array(
					'utm_source'   => 'plugin-extensions-page',
					'utm_medium'   => 'plugin',
					'utm_campaign' => 'KBS_Extensions_Page',
					'utm_content'  => $extension->info->title
				), $link ) );

				if ( array_key_exists( $slug, $slug_corrections ) )	{
					$slug = $slug_corrections[ $slug ];
				}

				if ( isset( $extension->pricing->amount ) ) {
					$price = '&pound;' . number_format( $extension->pricing->amount, 2 );
				} else {
					if ( isset( $extension->pricing->singlesite ) ) {
						$price = '&pound;' . number_format( $extension->pricing->singlesite, 2 );
					}
				}

				if ( ! empty( $extension->info->excerpt ) ) {
					$the_excerpt = $extension->info->excerpt;
				}

				$the_excerpt   = strip_shortcodes( strip_tags( stripslashes( $the_excerpt ), $tags ) );
				$the_excerpt   = preg_split( '/\b/', $the_excerpt, $length * 2+1 );
				$excerpt_waste = array_pop( $the_excerpt );
				$the_excerpt   = implode( $the_excerpt ); ?>

                <article class="col">
                    <div class="kbs-extension-item">
                        <div class="kbs-extension-item-img">
                            <a href="<?php echo $link; ?>" target="_blank"><img src="<?php echo $extension->info->thumbnail; ?>" /></a>
                        </div>
                        <div class="kbs-extension-item-desc">
                            <p class="kbs-extension-item-heading"><?php echo $extension->info->title; ?></p>
                            <div class="kbs-extension-item-excerpt">
                            	<p><?php echo $the_excerpt; ?></p>
                            </div>
                            <div class="kbs-extension-buy-now">
                                <?php if ( ! is_plugin_active( 'kbs-' . $slug . '/' . 'kbs-' . $slug . '.php' ) ) : ?>
                                    <a href="<?php echo $link; ?>" class="button-primary" target="_blank"><?php printf( __( 'Buy Now from %s', 'kb-support' ), $price ); ?></a>
                                <?php else : ?>
                                    <p class="button-primary"><?php _e( 'Already Installed', 'kb-support' ); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </article>
			<?php endforeach; ?>
		</div>
	</div>
	<?php

} // kbs_extensions_page

/**
 * Retrieve the published extensions from kb-support.com and store within transient.
 *
 * @since	1.0.3
 * @return	void
 */
function kbs_get_extensions()	{
	$extensions = get_transient( '_kbs_extensions_feed' );

	if ( false === $extensions || doing_action( 'kbs_daily_scheduled_events' ) )	{
		$route    = esc_url( 'https://kb-support.com/edd-api/products/' );
		$number   = 20;
		$endpoint = add_query_arg( array( 'number' => $number ), $route );
		$response = wp_remote_get( $endpoint );

		if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
			$body    = wp_remote_retrieve_body( $response );
			$content = json_decode( $body );
	
			if ( is_object( $content ) && isset( $content->products ) ) {
				set_transient( '_kbs_extensions_feed', $content->products, DAY_IN_SECONDS / 2 ); // Store for 12 hours
				$extensions = $content->products;
			}
		}
	}

	return $extensions;
} // kbs_get_extensions
add_action( 'kbs_daily_scheduled_events', 'kbs_get_extensions' );
