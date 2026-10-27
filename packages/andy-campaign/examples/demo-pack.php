<?php
/**
 * Example only. Package as a separate reviewed WordPress plugin to enable it.
 * Deliberately performs no discounts, orders, shipping, inventory, or payment changes.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'andy_campaign_register', static function ( $host ) {
    $host->register( array(
        'id' => 'demo-cart-notice',
        'name' => 'Demo Cart Notice',
        'version' => '0.1.0',
        'resources' => array( 'checkout-display' ),
        'boot' => static function ( $id ) {
            // Only the Lite host may execute this callback after master/campaign/schedule gates.
            add_action( 'woocommerce_before_cart', static function () use ( $id ) {
                echo '<p class="woocommerce-info">' . esc_html( 'Active campaign: ' . $id ) . '</p>';
            } );
        },
    ) );
}, 10, 1 );
