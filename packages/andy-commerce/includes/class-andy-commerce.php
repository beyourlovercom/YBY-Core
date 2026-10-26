<?php
/**
 * Andy Commerce runtime bootstrap.
 *
 * @package Andy_Commerce
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once ANDY_COMMERCE_PLUGIN_DIR . 'admin/class-andy-commerce-admin.php';
// Pure USD-first Currency OS V1: loaded without any Woo/cart/checkout hooks.
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-currency-os-v1.php';
// M2 fixed-endpoint reference FX client, never polled during cart/payment.
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-currency-reference-fx-v1.php';
// Dev-only Currency OS V2 admin visual and shortcode preview; no Woo price hooks.
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-currency-v2-dev.php';
// C-R1 runtime: source-loaded everywhere, but its Woo hooks are strictly
// isolated to a disposable local sandbox with an explicit wp-config flag.
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-currency-runtime-c1.php';
// C-R3 pure rounding ledger proposal; no order metadata or payment hooks.
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-currency-runtime-reconcile.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-order-coupon-column.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-m4-shipping-shadow.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-m5-progress-projection.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-m5-woo-cart-adapter.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-marketing-v2-rule-fields.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-m7-draft-studio.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-m8-promotion-evaluator.php';
// M9 pure multi-rule USD shadow evaluator; no Woo pricing/payment hooks.
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-m9-usd-rule-engine.php';

class Andy_Commerce {
	public function run() {
		Andy_Commerce_Currency_V2_Dev::boot();
		Andy_Commerce_Currency_Runtime_C1::boot();
		Andy_Commerce_Shipping_Promotion_Policy::boot();
		if ( ! class_exists( 'BYL_Shipping_Promotion_Runtime' ) ) {
			Andy_Commerce_Shipping_Promotion_Runtime::boot();
		} else {
			// Keep legacy shipping eligibility/coupon hooks as sole authority
			// until separately approved retirement; register only a default choice.
			Andy_Commerce_Shipping_Promotion_Runtime::boot_default_shipping_preference();
		}
		if ( is_admin() ) {
			Andy_Commerce_M7_Draft_Studio::boot();
			$admin = new Andy_Commerce_Admin();
			add_action( 'admin_menu', array( $admin, 'add_admin_menu' ), 35 );
		}
	}
}
