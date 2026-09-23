<?php
/**
 * Core plugin bootstrap.
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-loader.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-helpers.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-module-registry.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-module-settings-store.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-module-runtime.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-article-toc-module.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-analytics-module.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-security.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-database.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-config.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-site-profile.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-brand-profile.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-case-id.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-page-profile.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-project.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-content.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-project-template.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-project-cpt.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-landing-page-cpt.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-brand-os.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-social-login.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-google-token-verifier.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-google-auth-service.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-google-auth-rest-controller.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-google-one-tap-controller.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-social-login-shortcodes.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-lead-session.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-tracking.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-webhook.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-email-template.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-email-template-schema.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-email-template-store.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-email-erp-contract.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-email-design-settings.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-email-template-registry.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-email-template-renderer.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-email-health-center.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-email-legacy-customizer-governance.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-email-template-runtime.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-email-subject-renderer.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-email-notification-provider.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-notification-manager.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-lead-email.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-inquiry-field-manager.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-inquiry-preset-manager.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-inquiry-manager.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-inquiry-lead-mapper.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-inquiry-renderer.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-inquiry-shortcodes.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-subscribe-shortcode.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-global-popup.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-global-inquiry-dock.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-lead-service.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-lead-management.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-lead-rest-controller.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-connector.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-connector-idempotency.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-connector-audit.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-connector-affiliate-bindings.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-connector-coupon-bindings.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-connector-payout-bindings.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-github-release-client.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-update-verifier.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-update-backup.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-updater.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-docs-runtime.php';
require_once YBY_CORE_PLUGIN_DIR . 'admin/class-yby-admin.php';
require_once YBY_CORE_PLUGIN_DIR . 'admin/class-yby-content-admin.php';
require_once YBY_CORE_PLUGIN_DIR . 'admin/class-yby-inquiry-admin.php';
require_once YBY_CORE_PLUGIN_DIR . 'admin/class-yby-project-studio.php';
require_once YBY_CORE_PLUGIN_DIR . 'admin/class-yby-social-login-admin.php';
require_once YBY_CORE_PLUGIN_DIR . 'admin/class-yby-popup-admin.php';
require_once YBY_CORE_PLUGIN_DIR . 'admin/class-yby-connector-admin.php';
require_once YBY_CORE_PLUGIN_DIR . 'admin/class-yby-email-template-admin.php';
require_once YBY_CORE_PLUGIN_DIR . 'admin/class-yby-docs-os-admin.php';
require_once YBY_CORE_PLUGIN_DIR . 'public/class-yby-public.php';

/**
 * Main plugin orchestrator.
 */
class YBY_Core {

	/**
	 * Loader instance.
	 *
	 * @var YBY_Loader
	 */
	protected $loader;

	/**
	 * Initialize plugin.
	 */
	public function __construct() {
		$this->loader = new YBY_Loader();

		YBY_Module_Registry::adopt_default_modules_once( 'v170_landing_pages', array( 'landing_pages' ) );

		$this->define_system_hooks();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Register system hooks.
	 *
	 * @return void
	 */
	protected function define_system_hooks() {
		$database = new YBY_Database();
		$module_runtime = new YBY_Module_Runtime();

		$this->loader->add_action( 'andy_core_register_modules', 'YBY_Article_TOC_Module', 'register_module', 10, 0 );
		$this->loader->add_action( 'andy_core_register_modules', 'YBY_Analytics_Module', 'register_module', 11, 0 );
		$this->loader->add_action( 'plugins_loaded', 'YBY_Activator', 'sync_capabilities', 1, 0 );
		$this->loader->add_action( 'plugins_loaded', $database, 'maybe_upgrade', 5, 0 );
		$this->loader->add_action( 'plugins_loaded', $module_runtime, 'discover_and_boot', 20, 0 );

		if ( YBY_Module_Registry::is_enabled( 'email_os' ) ) {
			$this->loader->add_action( 'plugins_loaded', 'YBY_Email_Design_Settings', 'install_defaults', 6, 0 );
			$runtime = new YBY_Email_Template_Runtime();
			$this->loader->add_filter( 'wp_new_user_notification_email', $runtime, 'filter_new_user_notification_email', 10, 3 );
			$this->loader->add_filter( 'retrieve_password_notification_email', $runtime, 'filter_reset_password_notification_email', 10, 4 );
		}
	}

	/**
	 * Load text domain.
	 *
	 * @return void
	 */
	protected function set_locale() {
		$this->loader->add_action( 'plugins_loaded', $this, 'load_textdomain' );
	}

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	protected function define_admin_hooks() {
		$admin           = new YBY_Admin( 'yby-core', YBY_CORE_VERSION );
		$content_admin   = new YBY_Content_Admin();
		$brand_os        = new YBY_Brand_OS( 'yby-core', YBY_CORE_VERSION );
		$project_studio  = new YBY_Project_Studio( 'yby-core', YBY_CORE_VERSION );
		$updater         = new YBY_Updater( YBY_CORE_PLUGIN_FILE, YBY_CORE_VERSION );
		$inquiry_enabled = YBY_Module_Registry::is_enabled( 'inquiry_os' );
		$email_enabled   = YBY_Module_Registry::is_enabled( 'email_os' );
		$project_enabled = YBY_Module_Registry::is_enabled( 'project_studio' );
		$social_enabled  = YBY_Module_Registry::is_enabled( 'social_login' );
		$connector_enabled = YBY_Module_Registry::is_enabled( 'connector' );
		$docs_enabled      = YBY_Module_Registry::is_enabled( 'docs_os' );
		$landing_enabled   = YBY_Module_Registry::is_enabled( 'landing_pages' );

		$this->loader->add_action( 'admin_menu', $content_admin, 'add_admin_menu', 15 );
		$this->loader->add_action( 'admin_menu', $project_studio, 'add_admin_menu' );
		$this->loader->add_action( 'admin_menu', $brand_os, 'add_admin_menu' );
		if ( $social_enabled ) {
			$social_login = new YBY_Social_Login_Admin();
			$this->loader->add_action( 'admin_menu', $social_login, 'add_admin_menu' );
			$this->loader->add_action( 'admin_enqueue_scripts', $social_login, 'enqueue_assets' );
		}
		$this->loader->add_action( 'admin_menu', $admin, 'add_admin_menu' );
		if ( $docs_enabled ) {
			$docs_admin = new YBY_Docs_OS_Admin();
			$this->loader->add_action( 'admin_menu', $docs_admin, 'add_admin_menu', 25 );
			$this->loader->add_action( 'admin_enqueue_scripts', $docs_admin, 'enqueue_assets' );
			$this->loader->add_filter( 'parent_file', $docs_admin, 'filter_parent_file' );
			$this->loader->add_filter( 'submenu_file', $docs_admin, 'filter_submenu_file' );
		}
		if ( $landing_enabled ) {
			$landing_pages = new YBY_Landing_Page_CPT();
			$this->loader->add_action( 'init', $landing_pages, 'register', 9, 0 );
			$this->loader->add_action( 'admin_menu', $landing_pages, 'add_admin_menu', 30 );
			$this->loader->add_action( 'admin_init', $landing_pages, 'maybe_flush_rewrite_rules', 1, 0 );
			$this->loader->add_filter( 'parent_file', $landing_pages, 'filter_parent_file' );
			$this->loader->add_filter( 'submenu_file', $landing_pages, 'filter_submenu_file' );
		}
		if ( $inquiry_enabled || $email_enabled ) {
			$popup_admin = new YBY_Popup_Admin( 'yby-core', YBY_CORE_VERSION );
			$this->loader->add_action( 'admin_menu', $popup_admin, 'add_admin_menu' );
			$this->loader->add_action( 'admin_enqueue_scripts', $popup_admin, 'enqueue_assets' );
		}
		if ( $inquiry_enabled ) {
			$inquiry_admin = new YBY_Inquiry_Admin( 'yby-core', YBY_CORE_VERSION );
			$this->loader->add_action( 'admin_menu', $inquiry_admin, 'add_admin_menu', 20 );
			$this->loader->add_action( 'admin_enqueue_scripts', $inquiry_admin, 'enqueue_assets' );
		}
		$this->loader->add_action( 'admin_menu', $project_studio, 'normalize_root_menu', 999 );
		$this->loader->add_action( 'admin_enqueue_scripts', $brand_os, 'enqueue_assets' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_assets' );
		$this->loader->add_action( 'admin_init', $admin, 'redirect_legacy_inquiry_dock' );
		$updater->register_hooks( $this->loader );

		if ( $project_enabled ) {
			$project_cpt = new YBY_Project_CPT();
			$this->loader->add_action( 'init', $project_cpt, 'register' );
			$this->loader->add_action( 'admin_enqueue_scripts', $project_studio, 'enqueue_assets' );
			$this->loader->add_filter( 'parent_file', $project_studio, 'filter_parent_file' );
			$this->loader->add_filter( 'submenu_file', $project_studio, 'filter_submenu_file' );
		}

		if ( $connector_enabled ) {
			$connector_admin = new YBY_Connector_Admin();
			$this->loader->add_action( 'admin_init', $connector_admin, 'handle_secret_action' );
		}
	}

	/**
	 * Register public hooks.
	 *
	 * @return void
	 */
	protected function define_public_hooks() {
		$inquiry_enabled = YBY_Module_Registry::is_enabled( 'inquiry_os' );
		$project_enabled = YBY_Module_Registry::is_enabled( 'project_studio' );
		$social_enabled = YBY_Module_Registry::is_enabled( 'social_login' );
		$connector_enabled = YBY_Module_Registry::is_enabled( 'connector' );
		$docs_enabled = YBY_Module_Registry::is_enabled( 'docs_os' );

		if ( $docs_enabled ) {
			$docs_runtime = new YBY_Docs_Runtime();
			$this->loader->add_action( 'init', $docs_runtime, 'register_content_model', 8, 0 );
			$this->loader->add_action( 'admin_init', $docs_runtime, 'maybe_flush_content_model_rewrite_rules', 2, 0 );
			$this->loader->add_filter( 'query_vars', $docs_runtime, 'register_query_var' );
			$this->loader->add_action( 'wp_enqueue_scripts', $docs_runtime, 'enqueue_assets', 15, 0 );
			$this->loader->add_action( 'template_redirect', $docs_runtime, 'maybe_render_preview', 1, 0 );
			$this->loader->add_action( 'template_redirect', $docs_runtime, 'maybe_render_canonical', 2, 0 );
		}

		if ( $inquiry_enabled || $project_enabled ) {
			$public = new YBY_Public( 'yby-core', YBY_CORE_VERSION );
			$this->loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_assets' );
		}

		if ( $inquiry_enabled ) {
			$lead_rest_route = new YBY_Lead_REST_Controller();
			$inquiry_manager = new YBY_Inquiry_Manager();
			$inquiry_renderer = new YBY_Inquiry_Renderer();
			$inquiry_shortcodes = new YBY_Inquiry_Shortcodes( $inquiry_manager, $inquiry_renderer );
			$subscribe_shortcode = new YBY_Subscribe_Shortcode();
			$global_popup = new YBY_Global_Popup( $inquiry_manager, $inquiry_renderer );
			$inquiry_dock = new YBY_Global_Inquiry_Dock();
			$this->loader->add_action( 'rest_api_init', $lead_rest_route, 'register_routes' );
			$this->loader->add_action( 'init', $inquiry_shortcodes, 'register', 10, 0 );
			$this->loader->add_action( 'init', $subscribe_shortcode, 'register', 10, 0 );
			$this->loader->add_filter( 'the_content', $inquiry_shortcodes, 'capture_modal_shortcodes_in_content', 9, 1 );
			$this->loader->add_action( 'wp_footer', $inquiry_shortcodes, 'render_deferred_modals', 100, 0 );
			$this->loader->add_action( 'wp_footer', $global_popup, 'render', 110, 0 );
			$this->loader->add_action( 'wp_footer', $inquiry_dock, 'render', 120, 0 );
		}

		if ( $social_enabled ) {
			$google_auth_route = new YBY_Google_Auth_REST_Controller();
			$google_one_tap = new YBY_Google_One_Tap_Controller();
			$social_shortcodes = new YBY_Social_Login_Shortcodes();
			$this->loader->add_action( 'rest_api_init', $google_auth_route, 'register_routes' );
			$this->loader->add_action( 'rest_api_init', $google_one_tap, 'register_routes' );
			$this->loader->add_action( 'wp_enqueue_scripts', $google_one_tap, 'enqueue_runtime', 20, 0 );
			$this->loader->add_action( 'wp_logout', $google_one_tap, 'suppress_after_logout', 10, 0 );
			$this->loader->add_action( 'init', $social_shortcodes, 'register', 10, 0 );
		}

		if ( $connector_enabled ) {
			$connector_route = new YBY_Connector();
			$this->loader->add_action( 'rest_api_init', $connector_route, 'register_routes' );
		}
	}

	/**
	 * Load plugin translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'yby-core', false, dirname( plugin_basename( YBY_CORE_PLUGIN_FILE ) ) . '/languages/' );
	}

	/**
	 * Run hooks.
	 *
	 * @return void
	 */
	public function run() {
		$this->loader->run();
	}
}
