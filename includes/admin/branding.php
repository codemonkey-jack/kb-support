<?php
/**
 * Weclome Page Class
 *
 * @package     KBS
 * @subpackage  Admin/Branding
 * @copyright   Copyright (c) 2022
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.5.7
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
	exit;

/**
 * KBS_Branding Class
 *
 * A general class for plugin branding.
 *
 * @since    1.5.7
 */
class KBS_Branding {


	/**
	 * Load admin hooks
	 */
	public function __construct() {
        
		add_action( 'in_admin_header', array( $this, 'kbs_page_header' ) );

		add_filter( 'kbs_page_header', array( $this, 'page_header_locations' ) );
	}

	/**
	 * Display the KB Support Admin Page Header
	 *
	 * @param bool $extra_class
	 */
	public static function kbs_page_header($extra_class = '') {
		// Only display the header on pages that belong to kbs
		if ( ! apply_filters( 'kbs_page_header', false ) ) {
			return;
		}
		?>
		<div class="kbs-page-header <?php echo ( $extra_class ) ? esc_attr( $extra_class ) : ''; ?>">
			<div class="kbs-header-logo">

				<img src="<?php echo esc_attr( KBS_PLUGIN_URL . 'assets/images/kbs-logo.png' ); ?>" class="kbs-logo" />
			</div>
			<div class="kbs-header-links">
				<a href="https://kb-support.com/support/" target="_blank" rel="noreferrer nofollow" id="get-help"
				   class="button button-secondary"><span
							class="dashicons dashicons-external"></span><?php esc_html_e( 'Documentation', 'kb-support' ); ?>
				</a>
				<a class="button button-secondary"
				   href="https://kb-support.com/log-a-support-ticket/" target="_blank" rel="noreferrer nofollow"><span
							class="dashicons dashicons-email-alt"></span><?php echo esc_html__( 'Contact us for support!', 'kb-support' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Set the kbs header locations
	 *
	 * @param $return
	 *
	 * @return bool|mixed
	 * @since 1.5.7
	 */
	public function page_header_locations( $return ) {

		$current_screen = get_current_screen();

		if ( 'kbs_ticket' === $current_screen->post_type ) {
			return true;
		}

		return $return;
	}

}

new KBS_Branding();
