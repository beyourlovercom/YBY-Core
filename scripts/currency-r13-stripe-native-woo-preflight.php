<?php
/**
 * Execute ONLY using WP-CLI on the separately created HTTPS Woo/Stripe test site:
 * wp eval-file scripts/currency-r13-stripe-native-woo-preflight.php
 *
 * Read-only preflight: no webhook registration, PI, refund or settings edits.
 * Requires three explicit *local* wp-config.php flags to avoid inspecting an
 * arbitrary host or a shared live/Dev database by mistake.
 */
if (!defined('ABSPATH') || !defined('AICC_CURRENCY_STRIPE_TEST_SANDBOX')
    || AICC_CURRENCY_STRIPE_TEST_SANDBOX !== true
    || !defined('AICC_CURRENCY_STRIPE_TEST_HOST')
    || !defined('AICC_CURRENCY_STRIPE_TEST_DB')
    || !defined('AICC_CURRENCY_STRIPE_TEST_NO_MAIL')
    || AICC_CURRENCY_STRIPE_TEST_NO_MAIL !== true) {
    throw new RuntimeException('R13 Stripe preflight: missing explicit isolated wp-config controls');
}
$host = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
$expectedHost = strtolower((string) AICC_CURRENCY_STRIPE_TEST_HOST);
if ($host === '' || $host !== $expectedHost || strpos(home_url('/'), 'https://') !== 0
    || strpos(site_url('/'), 'https://') !== 0
    || DB_NAME !== AICC_CURRENCY_STRIPE_TEST_DB
    || in_array($host, ['www.beyourlover.com','dev.beyourlover.com','beyourlover.com'], true)
    || preg_match('/(?:^|\.)(?:ybybottle|ybyirrigation|pairdoll)\.com$/D', $host)
    || wp_get_environment_type() === 'production') {
    throw new RuntimeException('R13 Stripe preflight: host, TLS, DB or environment isolation failed');
}
if (!class_exists('WooCommerce') || !function_exists('is_plugin_active')) {
    require_once ABSPATH.'wp-admin/includes/plugin.php';
}
if (!class_exists('WooCommerce')
    || !is_plugin_active('woocommerce-gateway-stripe/woocommerce-gateway-stripe.php')
    || strtoupper((string) get_option('woocommerce_currency', '')) !== 'USD') {
    throw new RuntimeException('R13 Stripe preflight: native Woo/Stripe or base USD missing');
}
foreach (['yaycurrency/yaycurrency.php', 'yaycurrency-pro/yaycurrency.php',
          'yaycurrency-premium/yaycurrency.php'] as $plugin) {
    if (is_plugin_active($plugin)) {
        throw new RuntimeException('R13 Stripe preflight: duplicate currency engine is active');
    }
}
$settings = get_option('woocommerce_stripe_settings', []);
if (!is_array($settings) || ($settings['testmode'] ?? '') !== 'yes'
    || ($settings['enabled'] ?? '') !== 'yes') {
    throw new RuntimeException('R13 Stripe preflight: Woo Stripe TEST mode not enabled');
}
if (!has_action('woocommerce_api_wc_stripe')) {
    throw new RuntimeException('R13 Stripe preflight: native Woo Stripe callback hook absent');
}
if (!class_exists('WC_Stripe_Mode') || !WC_Stripe_Mode::is_test()) {
    throw new RuntimeException('R13 Stripe preflight: native Stripe mode is not test');
}
// This is intentionally NOT used to read or print webhook signing secrets.
// A successfully connected test-mode gateway and signed Stripe delivery are
// separate acceptance gates, verified by test events and Woo's webhook UI.
echo 'R13_NATIVE_WOO_STRIPE_TEST_HOST_AND_DB_ISOLATION_PASS'.PHP_EOL;
echo 'R13_NATIVE_WOO_STRIPE_TEST_MODE_AND_CALLBACK_HOOK_PASS'.PHP_EOL;
echo 'R13_CALLBACK_PATH=https://'.$host.'/?wc-api=wc_stripe'.PHP_EOL;
echo 'R13_PUBLIC_HTTPS_DELIVERY_AND_SIGNED_EVENT_STILL_REQUIRED'.PHP_EOL;
