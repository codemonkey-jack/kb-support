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
		list( $display_version ) = explode( '-', KBS_VERSION );

		// About Page
		add_dashboard_page(
			/* translators: %s: KB Support version */
			sprintf( __( 'Welcome to KB Support %s', 'kb-support' ), $display_version ),
			__( 'Welcome to KB Support', 'kb-support' ),
			$this->minimum_capability,
			'kbs-about',
			array( $this, 'about_screen' )
		);

		// Getting Started Page
		add_dashboard_page(
			__( 'Getting Started with KB Support', 'kb-support' ),
			__( 'Getting Started with KB Support', 'kb-support' ),
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
				/* translators: %s: https://kb-support.com/kb-support-1-1-released/ */
					__( 'Thanks for updating to the latest version of KB Support! Take a moment to review the improvements and bug fixes included within this release below. You can also review the full <a href="%s" target="_blank">release notes here</a>.', 'kb-support' ),
					esc_url( 'https://kb-support.com/kb-support-1-1-released/' )
				);
			?></p>

			<?php kbs_get_newsletter(); ?>

			<div class="kbs-badge"></div>

			<?php $this->tabs(); ?>

            <div class="feature-section clearfix introduction">

                <div class="video feature-section-item">
                    <img src="<?php echo KBS_PLUGIN_URL . '/assets/images/screenshots/11-sequential-ticket-settings.png' ?>" alt="<?php printf( __( 'Sequential %s', 'kb-support' ), $this->ticket_plural ); ?>">
                </div>

                <div class="content feature-section-item last-feature">

                    <h3><?php printf( __( 'Sequential %s Numbers', 'kb-support' ), $this->ticket_singular ); ?></h3>

                    <p><?php printf(
						__( 'No longer do you have to put up with ID\'s being out of sequence for your %1$s. From version %2$s, you can enable sequential %3$s numbers from within settings. Once activated, all %1$s will be updated and all ID\'s will remain in sequence.', 'kb-support' ),
						strtolower( $this->ticket_plural ),
						$display_version,
						strtolower( $this->ticket_singular )
					); ?></p>

                    <p><?php printf(
						__( 'Enable sequential %1$s from <span class="return-to-dashboard"><a href="%2$s">%3$s &rarr; Settings &rarr; %3$s</a></span>', 'kb-support' ),
						strtolower( $this->ticket_plural ),
						add_query_arg( array(
							'post_type' => 'kbs_ticket',
							'page'      => 'kbs-settings',
							'tab'       => 'tickets'
						), admin_url( 'edit.php' ) ),
						$this->ticket_plural
					); ?></p>

                    <a href="https://kb-support.com/articles/enabling-sequential-ticket-numbers/" target="_blank" class="button-secondary">
						<?php esc_html_e( 'Learn More', 'kb-support' ); ?>
                        <span class="dashicons dashicons-external"></span>
                    </a>

                </div>

            </div>
            <!-- /.intro-section -->

            <div class="feature-section clearfix">

                <div class="content feature-section-item">

                    <h3><?php printf(
						__( 'Assign Multiple Agents to a %s', 'kb-support' ),
						$this->ticket_singular
					); ?></h3>

                    <p><?php printf(
						__( 'You can now assign multiple support workers to a single %1$s. In addition to assigning the primary agent, it is possible to assign additional agents.', 'kb-support' ),
						strtolower( $this->ticket_singular )
					); ?></p>

                    <p><?php printf(
						__( 'Support workers that are assigned as additional agents can receive email notification of their assignment and are able to view, update, add replies, and perform all the same actions as the primary agent.', 'kb-support' ),
						strtolower( $this->ticket_singular )
					); ?></p>

					<p><?php printf(
						__( 'Enable multiple agents from <span class="return-to-dashboard"><a href="%1$s">%2$s &rarr; Settings &rarr; %2$s &rarr; Agent Settings</a></span>', 'kb-support' ),
						add_query_arg( array(
							'post_type' => 'kbs_ticket',
							'page'      => 'kbs-settings',
							'tab'       => 'tickets',
							'section'   => 'agents'
						), admin_url( 'edit.php' ) ),
						$this->ticket_plural
					); ?></p>

                    <a href="https://kb-support.com/articles/assigning-multiple-agents-ticket/" target="_blank" class="button-secondary">
						<?php esc_html_e( 'Learn More', 'kb-support' ); ?>
                        <span class="dashicons dashicons-external"></span>
                    </a>

                </div>

                <div class="content feature-section-item last-feature">
                    <img src="<?php echo KBS_PLUGIN_URL . '/assets/images/screenshots/11-additional-agents-metabox.png' ?>"
                         alt="<?php esc_attr_e( 'Assign multiple agents', 'kb-support' ); ?>">
                </div>

            </div>
            <!-- /.feature-section -->

			<div class="feature-section clearfix agent-notifications">

                <div class="video feature-section-item">
                    <img src="<?php echo KBS_PLUGIN_URL . '/assets/images/screenshots/11-agent-notifications.png' ?>" alt="<?php __( 'Agent notifications', 'kb-support' ); ?>">
                </div>

                <div class="content feature-section-item last-feature">

                    <h3><?php _e( 'Agent Assignment Notifications', 'kb-support' ); ?></h3>

                    <p><?php printf(
						__( 'It has always been possible to notify an agent when a %1$s is created and auto assigned to them, or when a customer adds a reply to a %1$s that they are assigned to. The missing piece was notifications as and when a %1$s is reassigned to an agent.', 'kb-support' ),
						strtolower( $this->ticket_singular )
					); ?></p>

					<p><?php printf(
						__( 'You can now configure and fully customise agent notifications to ensure that any time a support worker is assigned to a %1$s as either the primary agent, or an additional agent, they receive an email notification.', 'kb-support' ),
						strtolower( $this->ticket_singular )
					); ?></p>

					<p><?php printf(
						__( 'Head to <span class="return-to-dashboard"><a href="%1$s">%2$s &rarr; Settings &rarr; Emails &rarr; Notifications</a></span> to setup agent notifications.', 'kb-support' ),
						add_query_arg( array(
							'post_type' => 'kbs_ticket',
							'page'      => 'kbs-settings',
							'tab'       => 'emails',
							'section'   => 'ticket_notifications'
						), admin_url( 'edit.php' ) ),
						$this->ticket_plural
					); ?></p>

                </div>

            </div>
            <!-- /.intro-section -->

			<div class="feature-section clearfix">

                <div class="content feature-section-item">

                    <h3><?php _e( 'Export Data to CSV', 'kb-support' ); ?></h3>

                    <p><?php _e( 'Export data from KB Support into a downloadable CSV file.', 'kb-support' ); ?></p>

					<p><?php printf(
						__( 'Select the Tools tab from the <span class="return-to-dashboard"><a href="%1$s">%2$s &rarr; Tools</a></span> menu. From here you can export %3$s and customer data into a CSV file which will automatically be downloaded to your PC.', 'kb-support' ),
						add_query_arg( array(
							'post_type' => 'kbs_ticket',
							'page'      => 'kbs-tools',
							'tab'       => 'export'
						), admin_url( 'edit.php' ) ),
						$this->ticket_plural,
						strtolower( $this->ticket_singular )
					); ?></p>

                </div>

                <div class="content feature-section-item last-feature">
                    <img src="<?php echo KBS_PLUGIN_URL . '/assets/images/screenshots/11-data-export.png' ?>"
                         alt="<?php esc_attr_e( 'Data export', 'kb-support' ); ?>">
                </div>

            </div>
            <!-- /.feature-section -->

			<h4><?php printf( __( 'Additional Updates with Version %s', 'kb-support' ), $display_version ); ?></h4>
            <ul class="ul-disc">
                <li><?php _e( 'Corrected variable name being passed to <code>kbs_register_redirect</code> filter', 'kb-support' ); ?></li>
                <li><?php _e( 'Added a notice for when settings are imported', 'kb-support' ); ?></li>
                <li><?php _e( 'Corrected CSS syntax which was causing alignment issue within the KB Article restrictions metabox', 'kb-support' ); ?></li>
                <li><?php _e( 'Updated contextual help for settings screen', 'kb-support' ); ?></li>
                <li><?php _e( 'Corrected variable name being passed via <code>kbs_auto_assign_agent</code> hook', 'kb-support' ); ?></li>
                <li><?php _e( 'Added hook <code>kbs_update_ticket_meta</code> hook', 'kb-support' ); ?></li>
                <li><?php _e( 'Set <code>$prev_value</code> when updating ticket meta if it is not passed to the function', 'kb-support' ); ?></li>
                <li><?php _e( 'Added filter <code>kbs_disable_ticket_post_lock</code> to enable removal of post lock for tickets', 'kb-support' ); ?></li>
            </ul>

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

            <p class="about-text"><?php
				printf(
				/* translators: %s: https://kb-support.com/support/ */
					__( 'Welcome to the KB Support getting started guide! If you\'re a first time user, you\'re now well on your way to making your support business even more efficient. We encourage you to check out the <a href="%s" target="_blank">plugin documentation</a> and getting started guide below.', 'kb-support' ),
					esc_url( 'https://kb-support.com/support/' )
				);
			?></p>

			<?php kbs_get_newsletter(); ?>

			<div class="kbs-badge"></div>

			<?php $this->tabs(); ?>

            <p class="about-text"><?php _e( 'Getting started with KB Support is easy! It works right from installation but we\'ve put together this quick start guide to help first time users customise the plugin to meet the individual needs of their business. We\'ll have you up and running in no time. Let\'s begin!', 'kb-support' ); ?></p>

            <div class="feature-section clearfix">

                <div class="content feature-section-item">
                    <h3><?php _e( 'STEP 1: Customise Settings', 'kb-support' ); ?></h3>

                    <p><?php printf(
                        __('KB Support settings enable you to define the communication flow and content between your support business and your customers, as well as determine who can submit a %1$s, how %2$s are assigned to support workers, which tasks support workers can undertake, plus much more...', 'kb-support' ),
                        strtolower( $this->ticket_singular ),
                        strtolower( $this->ticket_plural )
                    ); ?></p>

                    <p><?php printf(
						__( 'All of these settings can be managed by going to the menu and selecting <span class="return-to-dashboard"><a href="%s">%s &rarr; Settings</a></span>', 'kb-support' ),
						add_query_arg( array(
							'post_type' => 'kbs_ticket',
							'page'      => 'kbs-settings'
						), admin_url( 'edit.php' ) ),
						$this->ticket_plural
					); ?></p>
                </div>

                <div class="content feature-section-item update-settings">
                    <img src="<?php echo KBS_PLUGIN_URL; ?>assets/images/screenshots/getting-started-options.jpg">
                </div>

            </div>
            <!-- /.feature-section -->

            <div class="feature-section clearfix">

                <div class="content feature-section-item edit-form">
                    <img src="<?php echo KBS_PLUGIN_URL; ?>assets/images/screenshots/getting-started-edit-form.png">
                </div>

                <div class="content feature-section-item last-feature">
                    <h3><?php _e( 'STEP 2: Configure Your Submission Forms', 'kb-support' ); ?></h3>

                    <p><?php printf(
						__( 'Customers will use submission forms to create %s from the front end of your website. Edit the default form we created for you during installation to make sure you have all the fields defined you need to capture all relevant information from your customers. Select from a vast number of field types and re-order them via the easy to use drag and drop interface. Forms are managed via <span class="return-to-dashboard"><a href="%s">%s &rarr; Submission Forms</a></span>', 'kb-support' ),
							strtolower( $this->ticket_plural ),
							add_query_arg( 'post_type', 'kbs_form', admin_url( 'edit.php' ) ),
							$this->ticket_plural
					); ?></p>
                </div>

            </div>
            <!-- /.feature-section -->

            <div class="feature-section clearfix">

                <div class="content feature-section-item add-content">
                    <h3><?php esc_html_e( 'STEP 3: Create your Knowledge Base', 'kb-support' ); ?></h3>

                    <p><?php printf(
						__( 'Your knowledge base is key towards preventing the need for customers to open support %1$s. Well crafted %2$s can assist in your company receiving less support %1$s. Customers are happy as they can resolve their problems or queries quicker than if they have to open a support %3$s.', 'kb-support' ),
						strtolower( $this->ticket_plural ),
						$this->article_plural,
						strtolower( $this->ticket_singular )
					); ?></p>

                    <p><?php printf( __(
						'Check out our post on <a href="%1$s" target="_blank">Writing Effective Knowledge Base Articles</a> and once you\'re ready, use the <code>[kbs_articles]</code> on a page to display your knowledge base to your customers.', 'kb-support' ),
						'https://kb-support.com/writing-effective-knowledge-base-articles/',
						$this->article_plural
					); ?></p>

					<p><?php printf(
						__( 'Select <span class="return-to-dashboard"><a href="%s">%s</a></span>from the menu to start writing.', 'kb-support' ),
							add_query_arg( 'post_type', 'article', admin_url( 'edit.php' ) ),
							$this->article_plural
					); ?></p>

                </div>

                <div class="content feature-section-item last-feature">
                    <img src="<?php echo KBS_PLUGIN_URL; ?>assets/images/screenshots/getting-started-ticket-article-search.png">
                </div>

            </div>
            <!-- /.feature-section -->

            <div class="feature-section clearfix">

                <div class="content feature-section-item display-options">
                    <img src="<?php echo KBS_PLUGIN_URL; ?>assets/images/screenshots/getting-started-extensions.jpg">
                </div>

                <div class="content feature-section-item last-feature">
                    <h3><?php esc_html_e( 'STEP 4: Optionally Add More Functionality', 'kb-support' ); ?></h3>

                    <p><?php printf(
						__( 'There are many more ways in which you can customise your instance of KB Support. Take a look at our range of <a href="%s" target="_blank">extensions</a> to add even more functionality and review our extensive <a href="%s" target="_blank">support documentation</a> for additional help and tips.', 'kb-support' ),
						'https://kb-support.com/extensions/',
						'https://kb-support.com/support/'
					); ?></p>

					<p><?php printf(
						__( 'And of course, if you need any assistance, <a href="%s" target="_blank">log a support ticket</a> via our website and we\'ll be happy to help.', 'kb-support' ),
						'https://kb-support.com/extensions/'
					); ?></p>

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
		$badge_url = KBS_PLUGIN_URL . 'assets/images/kbs-icon-transparent.png';
		?>
        <h1 class="welcome-h1"><?php echo get_admin_page_title(); ?></h1>
		<?php $this->social_media_elements(); ?>

		<style type="text/css" media="screen">
            /*<![CDATA[*/
            .kbs-badge {
                background: url('<?php echo $badge_url; ?>') no-repeat;
            }

            /*]]>*/
        </style>
        
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

            <iframe src="//www.facebook.com/plugins/like.php?href=https%3A%2F%2Fwww.facebook.com%2Fkbsupport&amp;send=false&amp;layout=button&amp;width=100&amp;show_faces=false&amp;font&amp;colorscheme=light&amp;action=like&amp;height=21&amp;appId=220596284639969"
                    scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:100px; height:21px;"
                    allowTransparency="true"></iframe>

            <a href="https://twitter.com/kbsupport_wp" class="twitter-follow-button" data-show-count="false"><?php
				printf(
				/* translators: %s: KB Support twitter user @kbsupport_wp */
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
