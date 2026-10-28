<?php
/**
 * Plugin Name: Andy Campaign
 * Description: Code-first campaign package registry and safe operator switches. No built-in discounts.
 * Version: 0.1.0
 * Author: YBY Global
 * Text Domain: andy-campaign
 * Requires Plugins: yby-core,woocommerce
 *
 * @package Andy_Campaign
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'ANDY_CAMPAIGN_VERSION', '0.1.0' );
define( 'ANDY_CAMPAIGN_FILE', __FILE__ );

require_once __DIR__ . '/includes/class-andy-campaign-lite.php';

add_action( 'plugins_loaded', static function () {
    if ( ! class_exists( 'WooCommerce' ) || ! class_exists( 'YBY_Andy_OS_Navigation' ) ) {
        if ( is_admin() ) {
            add_action( 'admin_notices', static function () {
                if ( current_user_can( 'activate_plugins' ) ) {
                    echo '<div class="notice notice-error"><p>Andy Campaign requires active Andy Core and WooCommerce.</p></div>';
                }
            } );
        }
        return;
    }
    Andy_Campaign_Lite::boot();
}, 40 );
