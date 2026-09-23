<?php
/**
 * Analytics / GTM Control Layer V1 module foundation.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Analytics_Module {
	const MODULE_ID = 'analytics';
	const VERSION = '1.0.0';

	public static function register_module() {
		return YBY_Module_Registry::register(
			self::MODULE_ID,
			array(
				'name' => 'Analytics / GTM',
				'description' => '统一 Analytics 控制层：事件契约、Site Profile、GTM4WP Adapter、Consent Adapter 与重复 GTM 诊断。',
				'version' => self::VERSION,
				'schema_version' => '1',
				'default_enabled' => false,
				'status' => 'ready',
				'capability' => 'andy_core_settings_manage',
				'boot' => array( __CLASS__, 'boot' ),
				'settings' => array(
					'page' => YBY_Helpers::admin_page_slug(),
					'tab' => 'modules',
				),
				'storage' => array(
					'option_key' => 'yby_analytics_settings_v1',
					'schema_version' => '1',
				),
				'dependencies' => array( 'core_runtime', 'module_registry' ),
			)
		);
	}

	public static function boot( $module ) {
		unset( $module );
	}

	public static function defaults() {
		return array(
			'provider' => 'gtm4wp',
			'data_layer_name' => 'dataLayer',
			'site_profile' => 'auto',
			'consent_mode' => 'respect_existing',
			'debug' => false,
		);
	}

	public static function sanitize_settings( $raw ) {
		$raw = is_array( $raw ) ? $raw : array();

		$provider = isset( $raw['provider'] ) ? sanitize_key( $raw['provider'] ) : 'gtm4wp';
		if ( 'gtm4wp' !== $provider ) { $provider = 'gtm4wp'; }

		$data_layer_name = isset( $raw['data_layer_name'] ) ? trim( (string) $raw['data_layer_name'] ) : 'dataLayer';
		if ( ! preg_match( '/^[A-Za-z_$][A-Za-z0-9_$]{0,63}$/', $data_layer_name ) ) { $data_layer_name = 'dataLayer'; }

		$site_profile = isset( $raw['site_profile'] ) ? sanitize_key( $raw['site_profile'] ) : 'auto';
		if ( 'auto' !== $site_profile ) { $site_profile = 'auto'; }

		$consent_mode = isset( $raw['consent_mode'] ) ? sanitize_key( $raw['consent_mode'] ) : 'respect_existing';
		if ( 'respect_existing' !== $consent_mode ) { $consent_mode = 'respect_existing'; }

		return array(
			'provider' => $provider,
			'data_layer_name' => $data_layer_name,
			'site_profile' => $site_profile,
			'consent_mode' => $consent_mode,
			'debug' => ! empty( $raw['debug'] ),
		);
	}

	public static function get_settings() {
		return YBY_Module_Settings_Store::get(
			self::MODULE_ID,
			self::defaults(),
			array( __CLASS__, 'sanitize_settings' )
		);
	}

	public static function save_settings( $raw ) {
		return YBY_Module_Settings_Store::save(
			self::MODULE_ID,
			$raw,
			array( __CLASS__, 'sanitize_settings' )
		);
	}
}
