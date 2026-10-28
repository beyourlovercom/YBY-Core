<?php
/**
 * Theme-independent Notification UI foundation.
 *
 * Core-owned island (ON) plus independent compact native notice fallback skin (ON/OFF).
 * @package YBY_Core
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class YBY_Notification_UI_Module {
	const MODULE_ID = 'notification_ui';
	const VERSION = '1.0.0';

	public static function register_module() {
		$registered = YBY_Module_Registry::register(
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
		// Native notice presentation must be compact even when the island is OFF.
		if ( function_exists( 'add_action' ) ) {
			add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_native_skin' ), 40 );
			// Hide global Woo banners before first paint on opted-in pages only.
			add_action( 'wp_head', array( __CLASS__, 'print_notice_preflight' ), 2 );
		}
		return $registered;
	}

	public static function boot( $module ) {
		unset( $module );
		// All Woo frontend notices are bridged conservatively by Core JS.
		// Native skin stays available regardless of island toggle.
	}

	public static function should_enqueue_assets( $module ) {
		unset( $module );
		return YBY_Module_Registry::is_enabled( self::MODULE_ID )
			&& ! is_admin()
			&& ! is_feed()
			&& ! is_embed();
	}

	/** All Woo frontend routes, including product, account and checkout notices. */
	public static function should_enqueue_woo_classic() {
		if ( ! class_exists( 'WooCommerce' ) || ! self::should_enqueue_assets( array() ) ) { return false; }
		return true; // Do not lose account, product, order-pay, or error notices.
	}

	/** Scoped, theme-independent native notice fallback, even when module OFF. */
	public static function enqueue_native_skin() {
		if ( is_admin() || ! class_exists( 'WooCommerce' ) ) { return; }
		wp_enqueue_style(
			'andy-core-native-notice-skin',
			YBY_CORE_PLUGIN_URL . 'public/assets/css/andy-core-native-notice-skin.css',
			array(),
			YBY_CORE_VERSION
		);
	}

	/** First paint: temporarily defer only top-level Woo notices while the island initializes. */
	public static function print_notice_preflight() {
		if ( ! self::should_enqueue_woo_classic() ) { return; }
		?>
		<style id="andy-core-notice-preflight-css">
		html.andy-core-notice-preflight .woocommerce-notices-wrapper > :is(.woocommerce-message,.woocommerce-info,.wc-block-components-notice-banner.is-success,.wc-block-components-notice-banner.is-info),
		html.andy-core-notice-preflight .woocommerce > :is(.woocommerce-message,.woocommerce-info,.wc-block-components-notice-banner.is-success,.wc-block-components-notice-banner.is-info) { display:none!important; }
		</style>
		<script id="andy-core-notice-preflight-js">
		(function (w,d) {
			'use strict';
			var root=d.documentElement;
			if (!root || w.__andyCoreNoticePreflight) return;
			var gate={active:true,timedOut:false};
			gate.release=function () {
				if (!gate.active) return;
				gate.active=false;
				root.classList.remove('andy-core-notice-preflight');
				w.clearTimeout(gate.timeoutId);
			};
			root.classList.add('andy-core-notice-preflight');
			w.__andyCoreNoticePreflight=gate;
			gate.timeoutId=w.setTimeout(function () {
				gate.timedOut=true;
				gate.release();
			},2500);
		})(window,document);
		</script>
		<?php
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
		if ( self::should_enqueue_woo_classic() ) {
			wp_enqueue_script(
				'andy-core-notification-woo-classic',
				YBY_CORE_PLUGIN_URL . 'public/assets/js/andy-core-notification-woo-classic.js',
				array( $handle ),
				YBY_CORE_VERSION,
				true
			);
		}
	}
}
