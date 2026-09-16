<?php
/**
 * Plugin Name:       Andy Core
 * Plugin URI:        https://ybyglobal.com/
 * Description:       Core platform plugin for managed WordPress websites, including Case ID, tracking, configuration, lead sessions, and inquiry runtime.
 * Version:           1.5.8
 * Author:            YBY Global
 * Text Domain:       yby-core
 * Domain Path:       /languages
 *
 * @package YBY_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'YBY_CORE_VERSION', '1.5.8' );
define( 'YBY_DATABASE_VERSION', '1.5.0' );
define( 'YBY_CORE_PLUGIN_FILE', __FILE__ );
define( 'YBY_CORE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'YBY_CORE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-activator.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-deactivator.php';
require_once YBY_CORE_PLUGIN_DIR . 'inc/class-yby-core.php';

/**
 * Activate plugin.
 *
 * @return void
 */
function yby_core_activate() {
	YBY_Activator::activate();
}

/**
 * Deactivate plugin.
 *
 * @return void
 */
function yby_core_deactivate() {
	YBY_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'yby_core_activate' );
register_deactivation_hook( __FILE__, 'yby_core_deactivate' );

/**
 * Bootstrap core plugin.
 *
 * @return void
 */
function yby_core_run() {
	$plugin = new YBY_Core();
	$plugin->run();
}

yby_core_run();
