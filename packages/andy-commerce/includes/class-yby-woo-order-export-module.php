<?php
/** Woo Order Export module foundation. @package Andy_Commerce */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Woo_Order_Export_Module {
	const MODULE_ID = 'woo_order_export';
	const VERSION = '1.0.0';

	public static function register_module() {
		return YBY_Module_Registry::register(
			self::MODULE_ID,
			array(
				'name' => 'Woo Order Export',
				'description' => 'WooCommerce 订单只读 CSV 导出工具。',
				'version' => self::VERSION,
				'schema_version' => '1',
				'default_enabled' => false,
				'status' => 'ready',
				'capability' => 'manage_woocommerce',
				'availability' => array( __CLASS__, 'woocommerce_available' ),
				'availability_message' => '需要已启用的 WooCommerce。',
				'boot' => array( __CLASS__, 'boot' ),
				'settings' => array( 'page' => 'yby-commerce', 'tab' => 'order-export' ),
				'storage' => array( 'option_key' => 'yby_woo_order_export_settings_v1', 'schema_version' => '1' ),
				'dependencies' => array( 'core_runtime', 'module_registry' ),
			)
		);
	}

	public static function woocommerce_available( $module = array() ) {
		unset( $module );
		return class_exists( 'WooCommerce' ) || defined( 'WC_VERSION' );
	}

	public static function boot( $module ) {
		unset( $module );
		$controller = new YBY_Woo_Order_Export_Controller();
		add_action( 'admin_post_' . YBY_Woo_Order_Export_Controller::ACTION, array( $controller, 'handle_export' ) );
	}

	public static function defaults() {
		return array( 'default_preset' => 'default', 'batch_size' => 200, 'bom' => true, 'audit_enabled' => true );
	}

	public static function sanitize_settings( $raw ) {
		$raw = is_array( $raw ) ? $raw : array();
		$preset = isset( $raw['default_preset'] ) ? sanitize_key( $raw['default_preset'] ) : 'default';
		if ( empty( YBY_Woo_Order_Export_Presets::get( $preset ) ) ) { $preset = 'default'; }
		$batch = isset( $raw['batch_size'] ) ? absint( $raw['batch_size'] ) : 200;
		$batch = min( 500, max( 25, $batch ) );
		return array(
			'default_preset' => $preset,
			'batch_size' => $batch,
			'bom' => ! array_key_exists( 'bom', $raw ) || ! empty( $raw['bom'] ),
			'audit_enabled' => ! array_key_exists( 'audit_enabled', $raw ) || ! empty( $raw['audit_enabled'] ),
		);
	}

	public static function get_settings() {
		return YBY_Module_Settings_Store::get( self::MODULE_ID, self::defaults(), array( __CLASS__, 'sanitize_settings' ) );
	}

	public static function save_settings( $raw ) {
		return YBY_Module_Settings_Store::save( self::MODULE_ID, $raw, array( __CLASS__, 'sanitize_settings' ) );
	}
}
