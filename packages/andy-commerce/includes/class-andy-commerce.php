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
// R13 pure trusted source USD ↔ Woo display reconciliation (no runtime hooks).
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-currency-r13-dual-ledger.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-currency-r13-native-tax-shipping.php';
// Pure three-ledger Stripe TEST provider balance audit: no hooks/provider writes.
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-currency-r13-settlement-audit.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-currency-r13-fx-quote-guard.php';
// Exact-host + named-admin + short-lived Dev-only Currency UAT gate; default OFF.
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-currency-r13-dev-canary.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-currency-runtime-c4.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-currency-runtime-c6.php';
// Fail-closed TEST-only post-provider refund ledger. No charge/refund API writes.
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-currency-r13-stripe-refund-ledger.php';
// R8-R10 separate real Dev runtime, default OFF; never loaded as active WWW runtime.
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-currency-runtime-dev-v1.php';
// R6 read-only, no-hook authoritative USD basis for Campaign gift thresholds.
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-campaign-usd-basis-v1.php';
// YayCurrency Pro-only read-only source USD adapter; no FX/checkout hooks.
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-campaign-yay-usd-basis-v1.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-order-coupon-column.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-m4-shipping-shadow.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-m5-progress-projection.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-m5-woo-cart-adapter.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-marketing-v2-rule-fields.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-m7-draft-studio.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-m8-promotion-evaluator.php';
// M9 pure multi-rule USD shadow evaluator; no Woo pricing/payment hooks.
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-m9-usd-rule-engine.php';
// One integration owner: Marketing M-P1..P5 source from Draft PR #228.
// Default OFF; public/WWW requests never satisfy the exact local/Dev UAT gate.
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-marketing-p2-woo-adapter.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-marketing-p5-checkout-hooks.php';
require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-marketing-dev-uat.php';

class Andy_Commerce {
	public function run() {
		Andy_Commerce_Currency_V2_Dev::boot();
		Andy_Commerce_Currency_R13_Dev_Canary::boot();
		Andy_Commerce_Currency_Runtime_C1::boot();
		Andy_Commerce_Currency_Runtime_Dev_V1::admin_boot();
		Andy_Commerce_Currency_Runtime_Dev_V1::boot();
		// Hooks are registered only for the named disposable environment or
		// a deliberately armed HTTPS Dev Woo administrator (never guests).
		Andy_Commerce_Marketing_P2_Woo_Adapter::boot();
		Andy_Commerce_Marketing_P5_Checkout_Hooks::boot();
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
			Andy_Commerce_Marketing_Dev_UAT::boot();
			$admin = new Andy_Commerce_Admin();
			add_action( 'admin_menu', array( $admin, 'add_admin_menu' ), 35 );
		}
	}
}
