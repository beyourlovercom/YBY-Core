<?php
/**
 * Business OS V1 B1: optional, site-scoped WordPress management shell.
 *
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Business_OS_Module {
	const MODULE_ID = 'business_os';
	const VERSION   = '1.0.0';

	/**
	 * Register the optional extension through the existing Core module runtime.
	 *
	 * @return bool
	 */
	public static function register_module() {
		return YBY_Module_Registry::register(
			self::MODULE_ID,
			array(
				'name'                 => 'Business OS',
				'description'          => 'B2B 网站管理基础模块；真实客户、报价、PI、订单与履约均以 ERP 为准。',
				'version'              => self::VERSION,
				'schema_version'       => '1',
				'status'               => 'ready',
				'default_enabled'      => false,
				'capability'           => 'andy_core_settings_manage',
				'availability'         => array( __CLASS__, 'is_site_eligible' ),
				'availability_message' => 'Business OS 当前仅适用于 BeYourLover 站点。',
				'admin_menu'           => array( 'YBY_Business_OS_Admin', 'register_admin_menu' ),
				'admin_menu_priority'  => 31,
				'settings'             => array( 'page' => 'andy-business' ),
				'assets'               => array(
					'admin' => array(
						'enqueue'   => array( 'YBY_Business_OS_Admin', 'enqueue_assets' ),
						'condition' => array( 'YBY_Business_OS_Admin', 'is_business_screen' ),
						'priority'  => 20,
						'handles'   => array( 'andy-business-os-admin' ),
					),
				),
				'dependencies'         => array( 'core_runtime', 'module_registry' ),
			)
		);
	}

	/**
	 * Restrict runtime to the BYL site identity or exact known BYL hosts.
	 * No suffix match: untrusted lookalike hosts must remain ineligible.
	 *
	 * @param array $module Registry metadata, unused.
	 * @return bool
	 */
	public static function is_site_eligible( $module = array() ) {
		unset( $module );
		$key = YBY_Site_Profile::get_brand_key();
		if ( in_array( $key, array( 'byl', 'beyourlover', 'beyourlovercom' ), true ) ) {
			return true;
		}
		$host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		return in_array( $host, array( 'beyourlover.com', 'www.beyourlover.com', 'dev.beyourlover.com', 'localdev.beyourlover.com' ), true );
	}
}
