<?php
/**
 * GTM4WP provider adapter for the Analytics Control Layer.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Analytics_GTM4WP_Adapter {
	const PROVIDER = 'gtm4wp';

	public static function provider_available() {
		return defined( 'GTM4WP_VERSION' );
	}

	public static function provider_version() {
		return self::provider_available() ? (string) GTM4WP_VERSION : '';
	}

	public static function runtime_config() {
		$module_enabled = class_exists( 'YBY_Module_Registry' ) && YBY_Module_Registry::is_enabled( 'analytics' );
		$settings = class_exists( 'YBY_Analytics_Module' ) ? YBY_Analytics_Module::get_settings() : array();
		$data_layer_name = isset( $settings['data_layer_name'] ) ? (string) $settings['data_layer_name'] : 'dataLayer';

		return array(
			'enabled' => (bool) $module_enabled,
			'provider' => self::PROVIDER,
			'provider_ready' => self::provider_available(),
			'provider_version' => self::provider_version(),
			'data_layer_name' => $data_layer_name,
			'injects_container' => false,
			'owns_woocommerce_ecommerce' => false,
		);
	}
}
