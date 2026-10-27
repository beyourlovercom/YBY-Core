<?php
/**
 * Theme-independent Notification UI foundation.
 *
 * R1/R2: opt-in display engine only. Native WooCommerce notices are untouched.
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Notification_UI_Module {
	const MODULE_ID = 'notification_ui';
	const VERSION = '1.0.0';

	public static function register_module() {
		return YBY_Module_Registry::register(
			self::MODULE_ID,
			array(
				'name'            => 'Notification UI（灵动岛通知）',
				'description'     => 'AICC 毛玻璃 × LELO 计时；独立展示引擎。默认关闭，关闭后原有通知不受影响。',
				'version'         => self::VERSION,
				'schema_version'  => '1',
				'default_enabled'=> false,
				'status'          => 'ready',
				'capability'      => 'andy_core_settings_manage',
				'boot'            => array( __CLASS__, 'boot' ),
				'assets'          => array(
					'frontend' => array(
						'enqueue'   => array( __CLASS__, 'enqueue_assets' ),
						'condition' => array( __CLASS__, 'should_enqueue_assets' ),
						'priority'  => 18,
						'handles'   => array( 'andy-core-notification-ui' ),
					),
				),
				'dependencies'    => array( 'core_runtime', 'module_registry' ),
			)
		);
	}

	public static function boot( $module ) {
		unset( $module );
		// Only the core-owned JS/CSS engine is registered at R2.
		// WooCommerce success and native notice filters are an R3 gate.
	}

	public static function should_enqueue_assets( $module ) {
		unset( $module );
		return YBY_Module_Registry::is_enabled( self::MODULE_ID )
			&& ! is_admin()
			&& ! is_feed()
			&& ! is_embed();
	}

	public static function enqueue_assets( $module ) {
		if ( ! self::should_enqueue_assets( $module ) ) { return; }
		$handle = 'andy-core-notification-ui';
		wp_enqueue_script(
			$handle,
			YBY_CORE_PLUGIN_URL . 'public/assets/js/andy-core-notification-ui.js',
			array(),
			YBY_CORE_VERSION,
			true
		);
		wp_localize_script(
			$handle,
			'AndyCoreNotificationUIConfig',
			array(
				'cssUrl'        => YBY_CORE_PLUGIN_URL . 'public/assets/css/andy-core-notification-ui.css?ver=' . rawurlencode( YBY_CORE_VERSION ),
				'holdMs'        => 2000,
				'enterMs'       => 230,
				'exitMs'        => 230,
				'dedupeMs'      => 1200,
				'criticalMs'    => 3000,
			)
		);
	}
}
