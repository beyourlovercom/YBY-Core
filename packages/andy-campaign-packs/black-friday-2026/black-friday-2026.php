<?php
/**
 * Plugin Name: BYL Black Friday 2026 Campaign
 * Description: Independently deployed Black Friday gift selection, controlled by Andy Campaign Lite.
 * Version: 0.1.0
 * Requires Plugins: woocommerce,andy-campaign
 * Text Domain: byl-black-friday-2026
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once __DIR__ . '/includes/class-byl-bf2026-gifts.php';
/** Keep gift landing-page shortcode renderable while the campaign is OFF.
 * This registers display only: all cart writes remain behind the Campaign runtime.
 */
add_action( 'init', static function () {
    if ( class_exists( 'WooCommerce' ) ) {
        add_shortcode( 'byl_bf2026_gifts', array( 'BYL_BF2026_Gifts', 'render' ) );
    }
}, 12 );

add_action( 'andy_campaign_register', static function ( $host ) {
    if ( ! $host instanceof Andy_Campaign_Lite ) { return; }
    $host->register( array(
        'id' => 'byl-black-friday-2026',
        'name' => 'BYL Black Friday 2026',
        'version' => '0.1.0',
        'resources' => array( 'gift' ),
        'ready' => array( 'BYL_BF2026_Gifts', 'ready' ),
        'boot' => array( 'BYL_BF2026_Gifts', 'boot' ),
    ) );
}, 10, 1 );
