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
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-case-id.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-page-profile.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-project.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-content.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-project-template.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-project-cpt.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-brand-os.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-lead-session.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-tracking.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-webhook.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-email-template.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-email-subject-renderer.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-email-notification-provider.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-notification-manager.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-lead-email.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-lead-service.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/api/class-yby-lead-rest-controller.php';
require_once YBY_CORE_PLUGIN_DIR . 'admin/class-yby-admin.php';
require_once YBY_CORE_PLUGIN_DIR . 'admin/class-yby-project-studio.php';
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

		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
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
		$project_cpt    = new YBY_Project_CPT();
		$brand_os       = new YBY_Brand_OS( 'yby-core', YBY_CORE_VERSION );
		$project_studio = new YBY_Project_Studio( 'yby-core', YBY_CORE_VERSION );

		$this->loader->add_action( 'init', $project_cpt, 'register' );
		$this->loader->add_action( 'admin_menu', $project_studio, 'add_admin_menu' );
		$this->loader->add_action( 'admin_menu', $brand_os, 'add_admin_menu' );
		$this->loader->add_action( 'admin_menu', $admin, 'add_admin_menu' );
		$this->loader->add_action( 'admin_enqueue_scripts', $project_studio, 'enqueue_assets' );
		$this->loader->add_action( 'admin_enqueue_scripts', $brand_os, 'enqueue_assets' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_assets' );
	}

	/**
	 * Register public hooks.
	 *
	 * @return void
	 */
	protected function define_public_hooks() {
		$public          = new YBY_Public( 'yby-core', YBY_CORE_VERSION );
		$lead_rest_route = new YBY_Lead_REST_Controller();

		$this->loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_assets' );
		$this->loader->add_action( 'rest_api_init', $lead_rest_route, 'register_routes' );
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
