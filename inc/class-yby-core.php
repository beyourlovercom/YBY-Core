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
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-lead-service.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-lead-management.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-lead-rest-controller.php';
require_once YBY_CORE_PLUGIN_DIR . 'admin/class-yby-admin.php';
require_once YBY_CORE_PLUGIN_DIR . 'admin/class-yby-inquiry-admin.php';
require_once YBY_CORE_PLUGIN_DIR . 'admin/class-yby-project-studio.php';
require_once YBY_CORE_PLUGIN_DIR . 'admin/class-yby-social-login-admin.php';
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

		$this->loader->add_action( 'plugins_loaded', $database, 'maybe_upgrade', 5, 0 );
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
		$admin          = new YBY_Admin( 'yby-core', YBY_CORE_VERSION );
		$inquiry_admin  = new YBY_Inquiry_Admin( 'yby-core', YBY_CORE_VERSION );
		$project_cpt    = new YBY_Project_CPT();
		$brand_os       = new YBY_Brand_OS( 'yby-core', YBY_CORE_VERSION );
		$project_studio = new YBY_Project_Studio( 'yby-core', YBY_CORE_VERSION );
		$social_login   = new YBY_Social_Login_Admin();

		$this->loader->add_action( 'init', $project_cpt, 'register' );
		$this->loader->add_action( 'admin_menu', $project_studio, 'add_admin_menu' );
		$this->loader->add_action( 'admin_menu', $brand_os, 'add_admin_menu' );
		$this->loader->add_action( 'admin_menu', $social_login, 'add_admin_menu' );
		$this->loader->add_action( 'admin_menu', $admin, 'add_admin_menu' );
		$this->loader->add_action( 'admin_menu', $inquiry_admin, 'add_admin_menu' );
		$this->loader->add_action( 'admin_enqueue_scripts', $project_studio, 'enqueue_assets' );
		$this->loader->add_action( 'admin_enqueue_scripts', $brand_os, 'enqueue_assets' );
		$this->loader->add_action( 'admin_enqueue_scripts', $social_login, 'enqueue_assets' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_assets' );
		$this->loader->add_action( 'admin_enqueue_scripts', $inquiry_admin, 'enqueue_assets' );
	}

	/**
	 * Register public hooks.
	 *
	 * @return void
	 */
	protected function define_public_hooks() {
		$public             = new YBY_Public( 'yby-core', YBY_CORE_VERSION );
		$lead_rest_route    = new YBY_Lead_REST_Controller();
		$google_auth_route  = new YBY_Google_Auth_REST_Controller();
		$google_one_tap     = new YBY_Google_One_Tap_Controller();
		$social_shortcodes  = new YBY_Social_Login_Shortcodes();
		$inquiry_manager    = new YBY_Inquiry_Manager();
		$inquiry_renderer   = new YBY_Inquiry_Renderer();
		$inquiry_shortcodes = new YBY_Inquiry_Shortcodes( $inquiry_manager, $inquiry_renderer );

		$this->loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_assets' );
		$this->loader->add_action( 'rest_api_init', $lead_rest_route, 'register_routes' );
		$this->loader->add_action( 'rest_api_init', $google_auth_route, 'register_routes' );
		$this->loader->add_action( 'rest_api_init', $google_one_tap, 'register_routes' );
		$this->loader->add_action( 'wp_enqueue_scripts', $google_one_tap, 'enqueue_runtime', 20, 0 );
		$this->loader->add_action( 'wp_logout', $google_one_tap, 'suppress_after_logout', 10, 0 );
		$this->loader->add_action( 'init', $social_shortcodes, 'register', 10, 0 );
		$this->loader->add_action( 'init', $inquiry_shortcodes, 'register', 10, 0 );
		$this->loader->add_filter( 'the_content', $inquiry_shortcodes, 'capture_modal_shortcodes_in_content', 9, 1 );
		$this->loader->add_action( 'wp_footer', $inquiry_shortcodes, 'render_deferred_modals', 100, 0 );
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
