<?php
/**
 * P6-C disposable WordPress Test-only egress gate assertion.
 * Memory-only pre_http_request filtering; NO outbound HTTP or payment is executed.
 * Never ship in installable Commerce ZIP.
 */
if (!defined('DB_NAME') || DB_NAME !== 'aicc_commerce_p5_20261023'
    || !function_exists('wp_get_environment_type') || wp_get_environment_type() !== 'local'
    || home_url() !== 'http://127.0.0.1:18973'
    || !defined('WP_HTTP_BLOCK_EXTERNAL') || WP_HTTP_BLOCK_EXTERNAL !== true
    || !class_exists('WooCommerce')) {
    throw new RuntimeException('P6C_TEST_EGRESS_NONISOLATED_ENVIRONMENT');
}
$fakeTest='Basic '.base64_encode('sk_test_FAKE_NOT_A_REAL_API_KEY:');
$fakeLive='Basic '.base64_encode('sk_live_FAKE_NOT_A_REAL_API_KEY:');
$cases=[
    ['sandbox_oauth_init', 'https://api.woocommerce.com/stripe-sandbox/oauth-init', 'POST', [], false],
    ['sandbox_oauth_keys', 'https://api.woocommerce.com/stripe-sandbox/oauth-keys', 'POST', [], false],
    ['reject_live_oauth', 'https://api.woocommerce.com/stripe/oauth-init', 'POST', [], true],
    ['reject_oauth_wrong_method', 'https://api.woocommerce.com/stripe-sandbox/oauth-init', 'GET', [], true],
    ['test_secret_prefix_only', 'https://api.stripe.com/v1/charges', 'GET', ['Authorization'=>$fakeTest], false],
    ['reject_live_key_prefix', 'https://api.stripe.com/v1/charges', 'GET', ['Authorization'=>$fakeLive], true],
    ['reject_anonymous_stripe', 'https://api.stripe.com/v1/charges', 'GET', [], true],
    ['reject_external_mail', 'https://api.sendgrid.com/v3/mail/send', 'POST', [], true],
    ['reject_http_downgrade', 'http://api.woocommerce.com/stripe-sandbox/oauth-init', 'POST', [], true],
];
foreach($cases as [$name,$url,$method,$headers,$shouldBlock]) {
    $result=apply_filters('pre_http_request', false, ['method'=>$method,'headers'=>$headers], $url);
    $blocked=is_wp_error($result);
    if ($blocked !== $shouldBlock) {
        throw new RuntimeException('P6C_TEST_EGRESS_GATE_FAIL: '.$name);
    }
}
$gateways=array_keys(WC()->payment_gateways()->payment_gateways());
if ($gateways!==['cod'] || get_option('aicc_m14_sandbox_active','no')!=='no') {
    throw new RuntimeException('P6C_TEST_EGRESS_FRONTEND_COD_OR_M14_REGRESSION');
}
echo 'P6C_STRIPE_TEST_EGRESS_FILTER_PASS 9/9; COD_ONLY; M14_OFF; NO_REMOTE_HTTP_EXECUTED', PHP_EOL;
