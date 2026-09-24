<?php
/**
 * Andy Commerce admin controller.
 *
 * @package Andy_Commerce
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Andy_Commerce_Admin {
	const PAGE_SLUG = 'andy-commerce';

	public function add_admin_menu() {
		add_menu_page(
			__( 'Andy Commerce', 'andy-commerce' ),
			__( 'Andy Commerce', 'andy-commerce' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			array( $this, 'render_page' ),
			'dashicons-cart',
			57
		);

		add_submenu_page(
			self::PAGE_SLUG,
			__( 'Overview', 'andy-commerce' ),
			__( 'Overview', 'andy-commerce' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access Andy Commerce.', 'andy-commerce' ) );
		}

		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
		if ( ! in_array( $tab, array( 'overview', 'order-export', 'settings' ), true ) ) { $tab = 'overview'; }

		if ( 'overview' === $tab ) {
			$status = andy_commerce_dependency_status();
			$export_registered = ! empty( YBY_Module_Registry::module( YBY_Woo_Order_Export_Module::MODULE_ID ) );
			$export_enabled = $export_registered && YBY_Module_Registry::is_enabled( YBY_Woo_Order_Export_Module::MODULE_ID );
			include ANDY_COMMERCE_PLUGIN_DIR . 'admin/views/overview.php';
			return;
		}

		$notice = '';
		if ( 'settings' === $tab && isset( $_POST['yby_woo_export_settings_submit'] ) ) {
			check_admin_referer( 'yby_woo_export_settings_save', 'yby_woo_export_settings_nonce' );
			$raw = wp_unslash( $_POST['yby_woo_export_settings'] ?? array() );
			YBY_Woo_Order_Export_Module::save_settings( is_array( $raw ) ? $raw : array() );
			$notice = __( 'Commerce export settings saved.', 'andy-commerce' );
		}

		$module_enabled = YBY_Module_Registry::is_enabled( YBY_Woo_Order_Export_Module::MODULE_ID );
		$settings = YBY_Woo_Order_Export_Module::get_settings();
		$presets = YBY_Woo_Order_Export_Presets::presets();
		$order_statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : array();
		include ANDY_COMMERCE_PLUGIN_DIR . 'admin/views/order-export.php';
	}
}
