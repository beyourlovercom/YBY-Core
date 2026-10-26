<?php
/**
 * P6-F evidence gate. Read-only; never enroll gateways, make orders or issue refunds.
 * Needs an explicit local site, fails closed if deployed anywhere else.
 */
if (home_url() !== 'http://127.0.0.1:18973'
    || DB_NAME !== 'aicc_commerce_p5_20261023'
    || wp_get_environment_type() !== 'local') {
    throw new RuntimeException('P6F_NOT_ISOLATED_DISPOSABLE_WOO');
}
if (!class_exists('WooCommerce')) throw new RuntimeException('WOO_NOT_BOOTSTRAPPED');
$gatewayIds=array_keys(WC()->payment_gateways()->payment_gateways());
sort($gatewayIds);
$paymentSupported=(!array_diff(['stripe','woocommerce_payments','ppcp-gateway'],$gatewayIds));
$checks=[
    'localhost_site_and_unique_database' => true,
    'woo_active' => class_exists('WooCommerce'),
    'hpos_enabled' => get_option('woocommerce_custom_orders_table_enabled') === 'yes',
    'native_shop_base_usd' => get_woocommerce_currency() === 'USD',
    'two_decimal_ledger' => wc_get_price_decimals()===2,
    'commerce_runtime_marketing_off' => get_option('aicc_m14_sandbox_active','no') === 'no',
    'original_checkout_page_restored' => wc_get_page_id('checkout')===7,
    'cron_disabled' => defined('DISABLE_WP_CRON') && DISABLE_WP_CRON===true,
    'wp_external_http_block' => defined('WP_HTTP_BLOCK_EXTERNAL') && WP_HTTP_BLOCK_EXTERNAL===true,
    'outgoing_email_intercept_filter' => has_filter('pre_wp_mail')!==false,
    'gateway_allowlist_filter' => has_filter('woocommerce_payment_gateways')!==false,
    'webhook_block_filter' => has_filter('woocommerce_webhook_should_deliver')!==false,
    'cod_only' => $gatewayIds === ['cod'],
];
$fixtureOrders=[58,59,60,61,62];
$orderSummaries=[];
foreach ($fixtureOrders as $id) {
    $o=wc_get_order($id);
    if (!$o || $o->get_type() !== 'shop_order'
        || $o->get_currency() !== 'USD'
        || $o->get_payment_method() !== 'cod'
        || $o->get_transaction_id() !== '') {
        $checks['local_m15_m16_provenance_orders']=false;
        break;
    }
    $orderSummaries[]=['id'=>$id, 'total_minor'=>(int) round(((float)$o->get_total())*100),
        'currency'=>$o->get_currency(), 'gateway'=>'cod', 'external_transaction_id_present'=>false];
}
if (!array_key_exists('local_m15_m16_provenance_orders',$checks))
    $checks['local_m15_m16_provenance_orders']=count($orderSummaries)===5;
$allEnv=array_reduce($checks,static fn($x,$y)=>$x&&$y,true);
$external=[
    'provider_test_mode_connected' => false,
    'dedicated_signed_external_webhook_verified' => false,
    'provider_3ds_and_decline_test_ids_verified' => false,
    'five_currency_provider_captures_verified' => false,
    'real_provider_partial_refund_verified' => false,
];
$report=[
    'audit'=>'P6F_READ_ONLY_GATE_2026_10_25',
    'site'=>'DISPOSABLE_LOCALHOST_ONLY',
    'wp_version'=>get_bloginfo('version'),
    'woo_version'=>WC()->version,
    'hpos_enabled'=>$checks['hpos_enabled'],
    'gateways'=>$gatewayIds,
    'safety_checks'=>$checks,
    'safety_preflight'=> $allEnv ? 'PASS' : 'FAIL',
    'prior_real_local_woo_order_proofs'=>$orderSummaries,
    'external_provider_required'=>$external,
    'p6a_local_simulation'=>'NOT_EVIDENCED',
    'p6b_existing_cod_order_reconciliation'=>'BASELINE_ONLY',
    'p6c_provider_connection'=>'BLOCKED',
    'p6d_payment_auth_3ds_refund'=>'NOT_TESTED',
    'p6e_provider_fx_and_order_binding'=>'NOT_TESTED',
    'p6f_final_release_gate'=>'BLOCKED_PENDING_PROVIDER_TEST_PROOF',
    'real_payment_executed'=>false
];
$out=json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
echo $out,PHP_EOL;
if (!$allEnv) throw new RuntimeException('P6F_SAFETY_PREFLIGHT_FAILED');
