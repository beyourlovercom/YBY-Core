<?php
/**
 * Plugin Name: BYL Black Friday 2026 Campaign
 * Description: Independently deployed Black Friday gift selection, controlled by Andy Campaign Lite.
 * Version: 0.2.4
 * Requires Plugins: woocommerce,andy-campaign
 * Text Domain: byl-black-friday-2026
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once __DIR__ . '/includes/class-byl-bf2026-r8-readiness.php';
require_once __DIR__ . '/includes/class-byl-bf2026-gifts.php';
require_once __DIR__ . '/includes/class-byl-bf2026-landing-preview.php';
require_once __DIR__ . '/includes/class-byl-bf2026-r10-price-policy.php';
require_once __DIR__ . '/includes/class-byl-bf2026-r10-sale-manager.php';
require_once __DIR__ . '/includes/class-byl-bf2026-r17-fixed-price.php';
require_once __DIR__ . '/includes/class-byl-bf2026-r10-catalog.php';
require_once __DIR__ . '/includes/class-byl-bf2026-r10-country-gate.php';
// Pure Gift policy/adapter and R12 planner: classes loaded but NO cart mutation hook registered.
require_once __DIR__ . '/includes/class-byl-bf2026-r9-policy.php';
require_once __DIR__ . '/includes/class-byl-bf2026-r9c-woo-adapter.php';
require_once __DIR__ . '/includes/class-byl-bf2026-r12-auto-gift-planner.php';
// R13 native cart writer is gated inside BYL_BF2026_Gifts::boot (Andy Campaign eligible only).
require_once __DIR__ . '/includes/class-byl-bf2026-r13-cart-writer.php';
require_once __DIR__ . '/includes/class-byl-bf2026-r14-canary.php';
require_once __DIR__ . '/includes/class-byl-bf2026-r15-gift-entitlements.php';
// Only the signed-in, single-browser Canary can run the R15 cart logic.
add_action('admin_post_byl_bf2026_choose_gift',['BYL_BF2026_R15_Entitlements','handle_post']);
add_action('admin_post_nopriv_byl_bf2026_choose_gift',['BYL_BF2026_R15_Entitlements','handle_post']);
// Canary bypasses master OFF for exactly one administrator's signed browser.
add_action('init',['BYL_BF2026_R14_Canary','bootstrap'],21);
// Late-binding protection: Store API can restore Woo cart after REST auth
// demotes the WP principal; run before the host's revocation at priority 95.
add_action('woocommerce_cart_loaded_from_session',
    ['BYL_BF2026_R14_Canary','bootstrap'],1);
add_action('admin_menu',['BYL_BF2026_R14_Canary','admin_menu'],30);
add_action('admin_post_byl_bf2026_canary_control',['BYL_BF2026_R14_Canary','admin_post']);
add_action('woocommerce_after_checkout_validation',
    ['BYL_BF2026_R10_Country_Gate','validate_classic'],90,2);
add_action('woocommerce_checkout_validate_order_before_payment',
    ['BYL_BF2026_R10_Country_Gate','validate_store_api'],90,2);
/**
 * R17: Owner-approved four fixed Woo sale prices. Product writes happen once
 * on explicit activation (or one-time update migration for an already-active
 * pack); Gift/Master remains independently gated. No random discounts.
 * Deactivation and Nov 30 LA expiry restore exact pre-launch Woo prices.
 */
if (function_exists('register_activation_hook')) {
    register_activation_hook(__FILE__, ['BYL_BF2026_R17_Fixed_Price','activate']);
}
if (function_exists('register_deactivation_hook')) {
    register_deactivation_hook(__FILE__, ['BYL_BF2026_R17_Fixed_Price','deactivate']);
}
add_action('admin_init',['BYL_BF2026_R17_Fixed_Price','maybe_apply_after_update'],99);
add_action('init',['BYL_BF2026_R17_Fixed_Price','expire_if_due'],9);
add_action('byl_bf2026_r17_restore_fixed_prices',static function () {
    BYL_BF2026_R17_Fixed_Price::restore('scheduled_end');
});
add_action('admin_notices',['BYL_BF2026_R17_Fixed_Price','admin_notice'],15);
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
        'version' => '0.2.4',
        'resources' => array( 'gift' ),
        'ready' => array( 'BYL_BF2026_R15_Entitlements', 'ready' ),
        'boot' => array( 'BYL_BF2026_R15_Entitlements', 'boot' ),
    ) );
}, 10, 1 );


/**
 * Attach the selection widget to the existing canonical Gift page without
 * overwriting Elementor/content or displaying transaction buttons while OFF.
 */
add_filter('the_content', static function ($content) {
    if (!is_string($content) || !function_exists('is_page')
        || !is_page() || !is_main_query() || !in_the_loop()
        || !function_exists('get_page_by_path') || !function_exists('get_the_ID')) {
        return $content;
    }
    $page=get_page_by_path('holiday/gift');
    if (!$page || (int)$page->ID !== (int)get_the_ID()
        || (function_exists('has_shortcode') && has_shortcode($content,'byl_bf2026_gifts'))) {
        return $content;
    }
    // R15 renders verified paid gift choices directly on each Woo product card.
    // Never append the legacy warehouse-approved shortcode: it would incorrectly
    // block the new ERP-assigned random-gift entitlement design.
    if (class_exists('BYL_BF2026_R15_Entitlements')) {return $content;}
    if (!class_exists('Andy_Campaign_Lite') || !class_exists('BYL_BF2026_Gifts')
        || !BYL_BF2026_Gifts::ready()) {return $content;}
    $state=Andy_Campaign_Lite::boot()->decisions()[BYL_BF2026_Gifts::ID]??'unregistered';
    $canary=class_exists('BYL_BF2026_R14_Canary')
        && BYL_BF2026_R14_Canary::granted();
    if ($state!=='eligible' && !$canary) {return $content;}
    return $content."\n".do_shortcode('[byl_bf2026_gifts]');
},35);
