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
			$admin = new Andy_Commerce_Admin();
			add_action( 'admin_menu', array( $admin, 'add_admin_menu' ), 35 );
		}
	}
}
