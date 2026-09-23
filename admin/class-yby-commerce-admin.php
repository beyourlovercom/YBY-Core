<?php
/** Andy Commerce admin shell. @package YBY_Core */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Commerce_Admin {
	const PAGE_SLUG = 'yby-commerce';
	const CAPABILITY = 'manage_woocommerce';

	public function add_admin_menu() {
		if ( empty( YBY_Module_Registry::module( YBY_Woo_Order_Export_Module::MODULE_ID ) ) || ! YBY_Module_Registry::is_available( YBY_Woo_Order_Export_Module::MODULE_ID ) ) { return; }
		add_submenu_page(
			YBY_Project_Studio::menu_slug(),
			__( 'Commerce', 'yby-core' ),
			__( 'Commerce', 'yby-core' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	public function render_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) { wp_die( esc_html__( 'You do not have permission to access Commerce.', 'yby-core' ) ); }
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'order-export';
		if ( ! in_array( $tab, array( 'order-export', 'settings' ), true ) ) { $tab = 'order-export'; }
		$notice = '';
		if ( 'settings' === $tab && isset( $_POST['yby_woo_export_settings_submit'] ) ) {
			check_admin_referer( 'yby_woo_export_settings_save', 'yby_woo_export_settings_nonce' );
			$raw = wp_unslash( $_POST['yby_woo_export_settings'] ?? array() );
			YBY_Woo_Order_Export_Module::save_settings( is_array( $raw ) ? $raw : array() );
			$notice = __( 'Commerce export settings saved.', 'yby-core' );
		}
		$module_enabled = YBY_Module_Registry::is_enabled( YBY_Woo_Order_Export_Module::MODULE_ID );
		$settings = YBY_Woo_Order_Export_Module::get_settings();
		$presets = YBY_Woo_Order_Export_Presets::presets();
		$order_statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : array();
		include YBY_CORE_PLUGIN_DIR . 'admin/views/commerce-order-export.php';
	}
}
