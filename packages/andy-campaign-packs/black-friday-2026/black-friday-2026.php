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

/**
 * Dev preview safety: while Core's /gift/ page is only a draft, do not let
 * WordPress redirect a 404 to the unrelated /gift-selection-fee/ product.
 * Stops interfering automatically as soon as Core publishes an actual /gift/
 * page or landing_page. Never changes the public WWW site's canonical logic.
 */
function byl_bf2026_draft_gift_redirect_guard( $redirect_url, $requested_url ) {
    if ( ! function_exists( 'home_url' ) || ! function_exists( 'wp_parse_url' )
         || wp_parse_url( home_url( '/' ), PHP_URL_HOST ) !== 'dev.beyourlover.com'
         || ! is_string( $requested_url ) ) { return $redirect_url; }
    $path = wp_parse_url( $requested_url, PHP_URL_PATH );
    if ( ! is_string( $path ) || '/gift' !== rtrim( $path, '/' )
         || ! function_exists( 'get_posts' ) ) { return $redirect_url; }
    $published = get_posts( array(
        'post_type' => array( 'page', 'landing_page' ),
        'post_status' => 'publish',
        'name' => 'gift',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => true,
    ) );
    return is_array( $published ) && ! empty( $published ) ? $redirect_url : false;
}
add_filter( 'redirect_canonical', 'byl_bf2026_draft_gift_redirect_guard', 20, 2 );

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
