<?php
/**
 * Plugin Name: BYL Black Friday 2026 Campaign
 * Description: Independently deployed Black Friday gift selection, controlled by Andy Campaign Lite.
 * Version: 0.1.1-dev
 * Requires Plugins: woocommerce,andy-campaign
 * Text Domain: byl-black-friday-2026
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once __DIR__ . '/includes/class-byl-bf2026-r8-readiness.php';
require_once __DIR__ . '/includes/class-byl-bf2026-gifts.php';
require_once __DIR__ . '/includes/class-byl-bf2026-landing-preview.php';
require_once __DIR__ . '/includes/class-byl-bf2026-r10-price-policy.php';
require_once __DIR__ . '/includes/class-byl-bf2026-r10-sale-manager.php';
require_once __DIR__ . '/includes/class-byl-bf2026-r10-catalog.php';
require_once __DIR__ . '/includes/class-byl-bf2026-r10-country-gate.php';
// Pure Gift policy/adapter and R12 planner: classes loaded but NO cart mutation hook registered.
require_once __DIR__ . '/includes/class-byl-bf2026-r9-policy.php';
require_once __DIR__ . '/includes/class-byl-bf2026-r9c-woo-adapter.php';
require_once __DIR__ . '/includes/class-byl-bf2026-r12-auto-gift-planner.php';
add_action('woocommerce_after_checkout_validation',
    ['BYL_BF2026_R10_Country_Gate','validate_classic'],90,2);
add_action('woocommerce_checkout_validate_order_before_payment',
    ['BYL_BF2026_R10_Country_Gate','validate_store_api'],90,2);
// R10 classes do not register product, cart, category or pricing writes.
// Only an explicitly activated, approved price ledger can later be restored.
add_action('byl_bf2026_restore_sale_prices', static function () {
    $ledger=BYL_BF2026_R10_Sale_Manager::ledger();
    if (in_array(($ledger['state']??''),['active','applying','conflicts'],true)) {
        BYL_BF2026_R10_Sale_Manager::restore('scheduled_end');
    }
});
if (function_exists('register_deactivation_hook')) {
    register_deactivation_hook(__FILE__, static function () {
        $ledger=BYL_BF2026_R10_Sale_Manager::ledger();
        if (!in_array(($ledger['state']??''),['active','applying','restoring','conflicts'],true)) {return;}
        $result=BYL_BF2026_R10_Sale_Manager::restore('plugin_deactivation');
        if (empty($result['ok']) && function_exists('wp_die')) {
            wp_die('Black Friday product prices were not fully restored. Review the saved price ledger before removing this plugin.',
                'Black Friday price restoration incomplete',['response'=>409]);
        }
    });
}
/** Keep gift landing-page shortcode renderable while the campaign is OFF.
 * This registers display only: all cart writes remain behind the Campaign runtime.
 */
add_action( 'init', static function () {
    if ( class_exists( 'WooCommerce' ) ) {
        add_shortcode( 'byl_bf2026_gifts', array( 'BYL_BF2026_Gifts', 'render' ) );
        BYL_BF2026_Landing_Preview::register();
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
        'version' => '0.1.1-dev',
        'resources' => array( 'gift' ),
        'ready' => array( 'BYL_BF2026_Gifts', 'ready' ),
        'boot' => array( 'BYL_BF2026_Gifts', 'boot' ),
    ) );
}, 10, 1 );
