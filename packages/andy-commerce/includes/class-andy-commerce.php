<?php
/**
 * Andy Commerce runtime bootstrap.
 *
 * @package Andy_Commerce
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once ANDY_COMMERCE_PLUGIN_DIR . 'admin/class-andy-commerce-admin.php';

class Andy_Commerce {
	public function run() {
		Andy_Commerce_Shipping_Promotion_Policy::boot();
		if ( ! class_exists( 'BYL_Shipping_Promotion_Runtime' ) ) {
			Andy_Commerce_Shipping_Promotion_Runtime::boot();
		} else {
			// Keep legacy shipping eligibility/coupon hooks as sole authority
			// until separately approved retirement; register only a default choice.
			Andy_Commerce_Shipping_Promotion_Runtime::boot_default_shipping_preference();
		}
		if ( is_admin() ) {
			$m3_bridge = null;
			// M3 Dev-only. No automatic migrations or checkout/cart hooks.
			if ( defined( 'ANDY_COMMERCE_M3_DEV_DRAFTS_ENABLED' ) && ANDY_COMMERCE_M3_DEV_DRAFTS_ENABLED === true
				&& defined( 'WP_ENVIRONMENT_TYPE' ) && in_array( WP_ENVIRONMENT_TYPE, array( 'development', 'staging' ), true )
				&& parse_url( home_url(), PHP_URL_HOST ) === 'dev.beyourlover.com' ) {
				require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-marketing-draft-sandbox.php';
				require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-marketing-draft-repository.php';
				require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-marketing-draft-admin-adapter.php';
				require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-marketing-draft-request-controller.php';
				require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-m3-dev-bridge.php';
				if ( Andy_Commerce_M3_Dev_Bridge::enabled() ) {
					global $wpdb;
					$m3_bridge = new Andy_Commerce_M3_Dev_Bridge( $wpdb );
					$m3_bridge->register();
				}
			}
			$admin = new Andy_Commerce_Admin( $m3_bridge );
			add_action( 'admin_menu', array( $admin, 'add_admin_menu' ), 35 );
		}
	}
}
