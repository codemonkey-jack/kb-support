<?php
/**
 * Weclome Page Class
 *
 * @package     KBS
 * @subpackage  Admin/Welcome
 * @copyright   Copyright (c) 2017, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * KBS_Welcome Class
 *
 * A general class for About and Credits page.
 *
 * @since	1.0
 */
class KBS_Welcome {

	/**
	 * @var	str		The capability users should have to view the page
	 */
	public $minimum_capability = 'manage_ticket_settings';

	/**
	 * @var	str		Singular label for tickets
	 */
	public $ticket_singular = 'Ticket';

	/**
	 * @var	str		Plural label for tickets
	 */
	public $ticket_plural = 'Tickets';

	/**
	 * @var	str		Singular label for KB Articles
	 */
	public $article_singular = 'KB Article';

	/**
	 * @var	str		Plural label for KB Articles
	 */
	public $article_plural = 'KB Articles';

	/**
	 * Get things started
	 *
	 * @since	1.0
	 */
	public function __construct()	{
		$this->ticket_singular  = kbs_get_ticket_label_singular();
		$this->ticket_plural    = kbs_get_ticket_label_plural();
		$this->article_singular = kbs_get_article_label_singular();
		$this->article_plural   = kbs_get_article_label_plural();

		add_action( 'admin_menu', array( $this, 'admin_menus') );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'admin_init', array( $this, 'welcome'    ) );
	} // __construct

	/**
	 * Register the Dashboard Pages which are later hidden but these pages
	 * are used to render the Welcome and Credits pages.
	 *
	 * @access	public
	 * @since	1.0
	 * @return	void
	 */
	public function admin_menus() {
		// About Page
		add_dashboard_page(
			__( 'Welcome to KB Support', 'kb-support' ),
			__( 'Welcome to KB Support', 'kb-support' ),
			$this->minimum_capability,
			'kbs-about',
			array( $this, 'about_screen' )
		);

		// Changelog Page
		add_dashboard_page(
			__( 'KB Support Changelog', 'kb-support' ),
			__( 'KB Support Changelog', 'kb-support' ),
			$this->minimum_capability,
			'kbs-changelog',
			array( $this, 'changelog_screen' )
		);

		// Getting Started Page
		add_dashboard_page(
			__( 'Getting started with KB Support', 'kb-support' ),
			__( 'Getting started with KB Support', 'kb-support' ),
			$this->minimum_capability,
			'kbs-getting-started',
			array( $this, 'getting_started_screen' )
		);		

	} // admin_menus

	/**
	 * Hide Individual Dashboard Pages
	 *
	 * @access	public
	 * @since	1.0
	 * @return	void
	 */
	public function admin_head() {
		remove_submenu_page( 'index.php', 'kbs-about' );
		remove_submenu_page( 'index.php', 'kbs-changelog' );
		remove_submenu_page( 'index.php', 'kbs-getting-started' );
	} // admin_head

	/**
	 * Navigation tabs
	 *
	 * @access	public
	 * @since	1.0
	 * @return	void
	 */
	public function tabs()	{
		$selected        = isset( $_GET['page'] ) ? $_GET['page'] : 'kbs-getting-started';
		$about_url       = esc_url( admin_url( add_query_arg( array( 'page' => 'kbs-about' ), 'index.php' ) ) );
		$get_started_url = esc_url( admin_url( add_query_arg( array( 'page' => 'kbs-getting-started' ), 'index.php' ) ) );
        $extensions_url  = esc_url( admin_url( 'edit.php?post_type=kbs_ticket&page=kbs-extensions' ) );
		?>

		<h2 class="nav-tab-wrapper wp-clearfix">			
			<a href="<?php echo $about_url; ?>" class="nav-tab <?php echo $selected == 'kbs-about' ? 'nav-tab-active' : ''; ?>">
				<?php _e( "What's New", 'kb-support' ); ?>
			</a>
			<a href="<?php echo $get_started_url; ?>" class="nav-tab <?php echo $selected == 'kbs-getting-started' ? 'nav-tab-active' : ''; ?>">
				<?php _e( 'Getting Started', 'kb-support' ); ?>
			</a>
			<a href="<?php echo $extensions_url; ?>" class="nav-tab <?php echo $selected == 'kbs-extensions' ? 'nav-tab-active' : ''; ?>">
				<?php _e( 'Extensions', 'kb-support' ); ?>
			</a>
		</h2>

		<?php
	} // tabs

	/**
	 * Render About Screen
	 *
	 * @access	public
	 * @since	1.0
	 * @return	void
	 */
	public function about_screen() {
		list( $display_version ) = explode( '-', KBS_VERSION );
        ?>
		<div class="wrap about-wrap">

			<?php $this->get_welcome_header() ?>
			<p class="about-text"><?php
				printf(
				/* translators: %s: https://kb-support.com/support/ */
					__( 'Thanks for activating or updating to the latest version of KB Support! If you\'re a first time user, welcome! You\'re well on your way to making your support business even more efficient. We encourage you to check out the <a href="%s" target="_blank">plugin documentation</a> and getting started guide below.', 'kb-support' ),
					esc_url( 'https://kb-support.com/support/' )
				);
				?></p>

			<?php kbs_get_newsletter(); ?>

			<?php $this->tabs(); ?>

            <div class="feature-section clearfix introduction">

                <div class="video feature-section-item">
                    <img src="<?php echo KBS_PLUGIN_URL . '/assets/images/kbs-logo.png' ?>" alt="<?php esc_attr_e( 'KB Support', 'kb-support' ); ?>">
                </div>

                <div class="content feature-section-item last-feature">

                    <h3><?php esc_html_e( 'KB Support - Democratizing Generosity', 'kb-support' ); ?></h3>

                    <p><?php esc_html_e( 'Give empowers you to easily accept donations and setup fundraising campaigns, directly within WordPress. We created Give to provide a better donation experience for you and your users. Robust, flexible, and intuitive, the plugin is built from the ground up to be the goto donation solution for WordPress. Create powerful donation forms, embed them throughout your website, start a campaign, and exceed your fundraising goals with Give. This plugin is actively developed and proudly supported by folks who are dedicated to helping you and your cause.', 'kb-support' ); ?></p>
                    <a href="https://kb-support.com/" target="_blank" class="button-secondary">
						<?php esc_html_e( 'Learn More', 'kb-support' ); ?>
                        <span class="dashicons dashicons-external"></span>
                    </a>

                </div>

            </div>
            <!-- /.intro-section -->

            <div class="feature-section clearfix">

                <div class="content feature-section-item">

                    <h3><?php esc_html_e( 'Getting to Know KB Support', 'kb-support' ); ?></h3>

                    <p><?php esc_html_e( 'Before you get started with Give we suggest you take a look at the online documentation. There you will find the getting started guide which will help you get up and running quickly. If you have a question, issue or bug with the Core plugin please submit an issue on the Give website. We also welcome your feedback and feature requests. Welcome to Give. We hope you much success with your cause.', 'kb-support' ); ?></p>

                    <h4>Find Out More:</h4>
                    <ul class="ul-disc">
                        <li><a href="https://kb-support.com/" target="_blank"><?php esc_html_e( 'Visit the KB Support Website', 'kb-support' ); ?></a></li>
                        <li><a href="https://kb-support.com/features/" target="_blank"><?php esc_html_e( 'View the KB Support Features', 'kb-support' ); ?></a></li>
                        <li><a href="https://kb-support.com/support/" target="_blank"><?php esc_html_e( 'Read the Documentation', 'kb-support' ); ?></a></li>
                    </ul>

                </div>

                <div class="content  feature-section-item last-feature">
                    <img src="<?php echo KBS_PLUGIN_URL . '/assets/images/kbs-logo.png' ?>"
                         alt="<?php esc_attr_e( 'A Give donation form', 'kb-support' ); ?>">
                </div>

            </div>
            <!-- /.feature-section -->
        </div>

		<?php
	} // about_screen

	/**
	 * Render Getting Started Screen
	 *
	 * @access	public
	 * @since	1.0
	 * @return	void
	 */
	public function getting_started_screen()	{

        list( $display_version ) = explode( '-', KBS_VERSION );
        $default_form = get_option( 'kbs_default_submission_form_created' );
		$form_url     = '#';

		if ( $default_form && 'publish' == get_post_status( $default_form ) )	{
			$form_url = admin_url( 'post.php?post=' . $default_form . '&action=edit' );
		}

		?>
        <div class="wrap about-wrap get-started">

			<?php $this->get_welcome_header() ?>

            <p class="about-text"><?php esc_html_e( 'Welcome to the getting started guide.', 'kb-support' ); ?></p>

			<?php kbs_get_newsletter(); ?>

			<?php $this->tabs(); ?>

            <p class="about-text"><?php _e( 'Getting started with KB Support is easy! It works right from installation but we\'ve put together this quick start guide to help first time users customise the plugin to meet the individual needs of their business. We\'ll have you up and running in no time. Let\'s begin!', 'kb-support' ); ?></p>

            <div class="feature-section clearfix">

                <div class="content feature-section-item">
                    <h3><?php _e( 'STEP 1: Customise Settings', 'kb-support' ); ?></h3>

                    <p><?php printf(
                        __('KB Support settings enable you to define the communication flow and content between your support business and your customers, as well as determine who can submit a %1$s, how %2$s are assigned to support workers, what tasks support workers can undertake, plus much more...', 'kb-support' ),
                        strtolower( $this->ticket_singular ),
                        strtolower( $this->ticket_plural )
                    ); ?></p>

                    <p><?php esc_html_e( 'All of these features begin by simply going to the menu and choosing "Donations > Add Form".', 'kb-support' ); ?></p>
                </div>

                <div class="content feature-section-item last-feature">
                    <img src="<?php echo KBS_PLUGIN_URL; ?>assets/images/kbs-logo.png">
                </div>

            </div>
            <!-- /.feature-section -->

            <div class="feature-section clearfix">

                <div class="content feature-section-item multi-level-gif">
                    <img src="<?php echo KBS_PLUGIN_URL; ?>assets/images/kbs-logo.png">
                </div>

                <div class="content feature-section-item last-feature">
                    <h3><?php esc_html_e( 'STEP 2: Customize Your Donation Forms', 'kb-support' ); ?></h3>

                    <p><?php esc_html_e( 'Each donation form you create can be customized to receive either a pre-determined set donation amount or have multiple suggested levels of giving. Choosing "Multi-level Donation" opens up the donation levels view where you can add as many levels as you\'d like with your own custom names and suggested amounts. As well, you can allow donors to give a custom amount and even set up donation goals.', 'kb-support' ); ?></p>
                </div>

            </div>
            <!-- /.feature-section -->

            <div class="feature-section clearfix">

                <div class="content feature-section-item add-content">
                    <h3><?php esc_html_e( 'STEP 3: Add Additional Content', 'kb-support' ); ?></h3>

                    <p><?php esc_html_e( 'Every donation form you create with Give can be used on its own stand-alone page, or it can be inserted into any other page or post throughout your site via a shortcode or widget.', 'kb-support' ); ?></p>

                    <p><?php esc_html_e( 'You can choose these different modes by going to the "Form Content" section. From there, you can choose to add content before or after the donation form on a page, or if you choose "None" perhaps you want to instead use the shortcode. You can find the shortcode in the top right column directly under the Publish/Save button. This feature gives you the most amount of flexibility with controlling your content on your website all within the same page.', 'kb-support' ); ?></p>
                </div>

                <div class="content feature-section-item last-feature">
                    <img src="<?php echo KBS_PLUGIN_URL; ?>assets/images/kbs-logo.png">
                </div>

            </div>
            <!-- /.feature-section -->

            <div class="feature-section clearfix">

                <div class="content feature-section-item display-options">
                    <img src="<?php echo KBS_PLUGIN_URL; ?>assets/images/kbs-logo.png">
                </div>

                <div class="content feature-section-item last-feature">
                    <h3><?php esc_html_e( 'STEP 4: Configure Your Display Options', 'kb-support' ); ?></h3>

                    <p><?php esc_html_e( 'Lastly, you can present the form in a number of different ways that each create their own unique donor experience. The "Modal" display mode opens the credit card fieldset within a popup window. The "Reveal" mode will slide into place the additional fields. If you\'re looking for a simple button, then "Button" more is the way to go. This allows you to create a customizable "Donate Now" button which will open the donation form upon clicking. There\'s tons of possibilities here, give it a try!', 'kb-support' ); ?></p>
                </div>


            </div>
            <!-- /.feature-section -->

			<hr />

			<div class="return-to-dashboard">
            	<a href="<?php echo admin_url( 'edit.php?post_type=kbs_ticket&page=kbs-settings' ); ?>">
					<?php _e( 'Configure Settings', 'kb-support' ); ?>
                </a> |
                <a href="<?php echo esc_url( self_admin_url( 'edit.php?post_type=kbs_ticket' ) ); ?>">
                    <?php printf( __( 'Go to %s', 'kb-support' ), $this->ticket_plural ); ?>
                </a> |
                 <a href="<?php echo esc_url( self_admin_url( 'edit.php?post_type=kbs_form' ) ); ?>">
                    <?php _e( 'Manage Submission Forms', 'kb-support' ); ?>
                </a> |
                <a href="<?php echo esc_url( self_admin_url( 'edit.php?post_type=' . KBS()->KB->post_type ) ); ?>">
                    <?php printf( __( 'Go to %s', 'kb-support' ), $this->article_plural ); ?>
                </a> |
                <a href="https://kb-support.com/extensions/" target="_blank">
                    <?php _e( 'View Extensions', 'kb-support' ); ?>
                </a> |
                <a href="<?php echo admin_url(); ?>">
                    <?php _e( 'WordPress Dashboard', 'kb-support' ); ?>
                </a>
            </div>

		</div>
		<?php
	} // getting_started_screen

	/**
	 * Parse the KBS readme.txt file
	 *
	 * @since	1.0
	 * @return	str		$readme		HTML formatted readme file
	 */
	public function parse_readme() {
		$file = file_exists( KBS_PLUGIN_DIR . 'readme.txt' ) ? KBS_PLUGIN_DIR . 'readme.txt' : null;

		if ( ! $file ) {
			$readme = '<p>' . __( 'No valid changelog was found.', 'kb-support' ) . '</p>';
		} else {
			$readme = file_get_contents( $file );
			$readme = nl2br( esc_html( $readme ) );
			$readme = explode( '== Changelog ==', $readme );
			$readme = end( $readme );

			$readme = preg_replace( '/`(.*?)`/', '<code>\\1</code>', $readme );
			$readme = preg_replace( '/[\040]\*\*(.*?)\*\*/', ' <strong>\\1</strong>', $readme );
			$readme = preg_replace( '/[\040]\*(.*?)\*/', ' <em>\\1</em>', $readme );
			$readme = preg_replace( '/= (.*?) =/', '<h4>\\1</h4>', $readme );
			$readme = preg_replace( '/\[(.*?)\]\((.*?)\)/', '<a href="\\2">\\1</a>', $readme );
		}

		return $readme;
	} // parse_readme

    /**
	 * The header section for the welcome screen.
	 *
	 * @since 1.1
	 */
	public function get_welcome_header() {
		// Badge for welcome page
		$badge_url = KBS_PLUGIN_URL . 'assets/images/kbs-logo.png';
		?>
        <h1 class="welcome-h1"><?php echo get_admin_page_title(); ?></h1>
		<?php $this->social_media_elements(); ?>

        
        <?php
    } // get_welcome_header

	/**
	 * Social Media Like Buttons
	 *
	 * Various social media elements to KB Support
     *
     * @since   1.1
	 */
	public function social_media_elements() { ?>

        <div class="social-items-wrap">

            <iframe src="//www.facebook.com/plugins/like.php?href=https%3A%2F%2Fwww.facebook.com%2Fkbsupport&amp;send=false&amp;layout=button_count&amp;width=100&amp;show_faces=false&amp;font&amp;colorscheme=light&amp;action=like&amp;height=21&amp;appId=220596284639969"
                    scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:100px; height:21px;"
                    allowTransparency="true"></iframe>

            <a href="https://twitter.com/kbsupport_wp" class="twitter-follow-button" data-show-count="false"><?php
				printf(
				/* translators: %s: Give twitter user @givewp */
					esc_html_e( 'Follow %s', 'kb-support' ),
					'@kbsupport_wp'
				);
				?></a>
            <script>!function (d, s, id) {
                    var js, fjs = d.getElementsByTagName(s)[0], p = /^http:/.test(d.location) ? 'http' : 'https';
                    if (!d.getElementById(id)) {
                        js = d.createElement(s);
                        js.id = id;
                        js.src = p + '://platform.twitter.com/widgets.js';
                        fjs.parentNode.insertBefore(js, fjs);
                    }
                }(document, 'script', 'twitter-wjs');
            </script>

        </div>
        <!--/.social-items-wrap -->

		<?php
	} // social_media_elements

	/**
	 * Sends user to the Welcome page on first activation of KBS as well as each
	 * time KBS is upgraded to a new major version
	 *
	 * @access	public
	 * @since	1.0
	 * @return	void
	 */
	public function welcome() {
		// Bail if no activation redirect
		if ( ! get_transient( '_kbs_activation_redirect' ) )	{
			return;
		}

		// Delete the redirect transient
		delete_transient( '_kbs_activation_redirect' );

		// Bail if activating from network, or bulk
		if ( is_network_admin() || isset( $_GET['activate-multi'] ) )	{
			return;
		}

		$upgrade = get_option( 'kbs_version_upgraded_from' );

		if ( ! $upgrade ) { // First time install
			wp_safe_redirect( admin_url( 'index.php?page=kbs-getting-started' ) ); exit;
		} else { // Update
			wp_safe_redirect( admin_url( 'index.php?page=kbs-about' ) ); exit;
		}
	} // welcome
}
new KBS_Welcome();
