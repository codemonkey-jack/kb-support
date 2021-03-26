<?php


class Tests_KBS extends KBS_UnitTestCase {
	protected $object;

	public function setUp() {
		parent::setUp();
		$this->object = KBS();
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function test_kbs_instance() {
		$this->assertClassHasStaticAttribute( 'instance', 'KB_Support' );
	}

	/**
	 * @covers KB_Support::setup_constants
	 */
	public function test_constants() {
		// Plugin Folder URL
		$path = str_replace( 'tests/', '', plugin_dir_url( __FILE__ ) );
		$this->assertSame( KBS_PLUGIN_URL, $path );

		// Plugin Folder Path
		$path = str_replace( 'tests/', '', plugin_dir_path( __FILE__ ) );
		$path = substr( $path, 0, -1 );
		$kbs  = substr( KBS_PLUGIN_DIR, 0, -1 );
		$this->assertSame( $kbs, $path );

		// Plugin Root File
		$path = str_replace( 'tests/', '', plugin_dir_path( __FILE__ ) );
		$this->assertSame( KBS_PLUGIN_FILE, $path.'kb-support.php' );
	}

	/**
	 * @covers KB_Support::includes
	 */
	public function test_includes() {
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/settings/register-settings.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/install.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/ajax-functions.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/template-functions.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/post-types.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/class-kbs-db.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/class-kbs-stats.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/class-kbs-roles.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/class-kbs-cron.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/class-kbs-logging.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/class-kbs-license-handler.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/article/article-actions.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/article/class-kbs-articles-query.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/article/article-functions.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/article/article-restricted.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/article/article-content.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/article/article-search.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/tickets/class-kbs-ticket-stats.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/tickets/class-kbs-tickets-query.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/tickets/class-kbs-ticket.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/tickets/ticket-actions.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/tickets/ticket-functions.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/files.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/formatting.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/scripts.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/emails/email-actions.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/emails/class-kbs-emails.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/emails/class-kbs-email-tags.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/class-kbs-html-elements.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/emails/email-functions.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/emails/email-template.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/class-kbs-form.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/form-functions.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/misc-functions.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/login-register.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/class-kbs-customer.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/class-kbs-company.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/user-functions.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/agent-functions.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/company-functions.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/class-kbs-db-customers.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/class-kbs-db-customer-meta.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/shortcodes.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/sla.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/admin-pages.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/admin-notices.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/admin-plugin.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/dashboard-widgets.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/customers/customers-page.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/customers/customer-functions.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/customers/customer-actions.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/customers/contextual-help.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/tickets/tickets.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/tickets/metaboxes.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/tickets/contextual-help.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/article/article.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/article/metaboxes.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/article/contextual-help.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/companies/company.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/companies/metaboxes.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/companies/contextual-help.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/forms/forms.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/forms/metaboxes.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/forms/form-actions.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/forms/contextual-help.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/settings/display-settings.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/settings/contextual-help.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/thickbox.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/tools.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/upgrades/upgrade-functions.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/upgrades/upgrades.php' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'includes/admin/welcome.php' );

        /** Check Assets Exist */
		$this->assertFileExists( KBS_PLUGIN_DIR . 'assets/css/chosen.css' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'assets/css/jquery-ui-classic.css' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'assets/css/jquery-ui-classic.min.css' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'assets/css/jquery-ui-fresh.css' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'assets/css/jquery-ui-fresh.min.css' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'assets/css/jquery-ui-humanity.css' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'assets/css/jquery-ui-humanity.min.css' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'assets/css/kbs-admin.css' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'assets/css/kbs-admin.min.css' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'assets/images/kbs-cross-hair.png' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'assets/images/kbs-logo.png' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'assets/images/loading.gif' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'assets/images/tick.png' );

		$this->assertFileExists( KBS_PLUGIN_DIR . 'assets/js/admin-scripts.js' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'assets/js/admin-scripts.min.js' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'assets/js/chosen.jquery.js' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'assets/js/chosen.jquery.min.js' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'assets/js/jquery.colorbox-min.js' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'assets/js/jquery.validate.min.js' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'assets/js/kbs-ajax.js' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'assets/js/kbs-ajax.min.js' );

		$this->assertFileExists( KBS_PLUGIN_DIR . 'templates/kbs.css' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'templates/kbs.min.css' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'assts/css/hover.min.css' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'templates/images/agent_status_online.gif' );
		$this->assertFileExists( KBS_PLUGIN_DIR . 'templates/images/agent_status_offline.gif' );


	}
}
