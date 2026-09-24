<?php
/**
 * Plugin Name:       Andy Commerce
 * Plugin URI:        https://ybyglobal.com/
 * Description:       Reusable B2C / WooCommerce Addon for Andy Core.
 * Version:           1.0.0
 * Author:            YBY Global
 * Text Domain:       andy-commerce
 * Requires Plugins:  yby-core,woocommerce
 * Andy Core Addon ID: andy_commerce
 * Andy Core Min Version: 1.9.0
 *
 * @package Andy_Commerce
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'ANDY_COMMERCE_VERSION', '1.0.0' );
define( 'ANDY_COMMERCE_MIN_CORE_VERSION', '1.9.0' );
define( 'ANDY_COMMERCE_PLUGIN_FILE', __FILE__ );
define( 'ANDY_COMMERCE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ANDY_COMMERCE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-yby-woo-order-export-presets.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-yby-woo-order-query-adapter.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-yby-woo-order-csv-streamer.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-yby-woo-order-export-audit.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-yby-woo-order-export-controller.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-yby-woo-order-export-module.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-site-preset.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-shipping-promotion-policy.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-shipping-promotion-runtime.php';

add_action( 'andy_core_register_modules', array( 'YBY_Woo_Order_Export_Module', 'register_module' ), 12, 0 );

function andy_commerce_dependency_status() {
	$core_loaded = defined( 'YBY_CORE_VERSION' ) && class_exists( 'YBY_Addon_Registry' );
	$core_compatible = $core_loaded && version_compare( YBY_CORE_VERSION, ANDY_COMMERCE_MIN_CORE_VERSION, '>=' );
	$woo_loaded = class_exists( 'WooCommerce' );

	return array(
		'core_loaded' => $core_loaded,
		'core_compatible' => $core_compatible,
		'woo_loaded' => $woo_loaded,
		'ready' => $core_loaded && $core_compatible && $woo_loaded,
	);
}

function andy_commerce_register_with_core() {
	if ( ! class_exists( 'YBY_Addon_Registry' ) ) { return false; }

	return YBY_Addon_Registry::register(
		'andy_commerce',
		array(
			'name' => 'Andy Commerce',
			'version' => ANDY_COMMERCE_VERSION,
			'plugin_file' => plugin_basename( ANDY_COMMERCE_PLUGIN_FILE ),
			'min_core_version' => ANDY_COMMERCE_MIN_CORE_VERSION,
			'requires' => array( 'woocommerce' ),
			'admin_url' => admin_url( 'admin.php?page=andy-commerce' ),
			'description' => 'Reusable B2C / WooCommerce capabilities for Andy Core sites.',
		)
	);
}
add_action( 'andy_core_register_addons', 'andy_commerce_register_with_core' );

function andy_commerce_admin_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) { return; }
	$status = andy_commerce_dependency_status();
	if ( $status['ready'] ) { return; }

	$messages = array();
	if ( ! $status['core_loaded'] ) {
		$messages[] = 'Andy Core is required.';
	} elseif ( ! $status['core_compatible'] ) {
		$messages[] = sprintf( 'Andy Core %s or newer is required.', ANDY_COMMERCE_MIN_CORE_VERSION );
	}
	if ( ! $status['woo_loaded'] ) {
		$messages[] = 'WooCommerce is required.';
	}

	printf(
		'<div class="notice notice-error"><p><strong>%s</strong> %s</p></div>',
		esc_html__( 'Andy Commerce is inactive:', 'andy-commerce' ),
		esc_html( implode( ' ', $messages ) )
	);
}

function andy_commerce_bootstrap() {
	$status = andy_commerce_dependency_status();
	if ( ! $status['ready'] ) {
		add_action( 'admin_notices', 'andy_commerce_admin_notice' );
		return;
	}

	require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce.php';
	$plugin = new Andy_Commerce();
	$plugin->run();
}
add_action( 'plugins_loaded', 'andy_commerce_bootstrap', 30 );
