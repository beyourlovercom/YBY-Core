<?php
/**
 * Dev-only WP-CLI isolated currency selection for read-only UAT.
 * Executed with --require BEFORE WordPress/plugin bootstrap.
 * Never creates or changes Woo sessions, products, coupons or orders.
 */
if (!defined('WP_CLI') || !WP_CLI) {
    throw new RuntimeException('YAY_DEV_UAT_CLI_ONLY');
}
$id=getenv('AICC_YAY_TEST_CURRENCY_ID');
if (!in_array($id,['40058','40060','40062','40063','40064'],true)) {
    throw new RuntimeException('YAY_DEV_UAT_UNKNOWN_CURRENCY');
}
$_COOKIE['yay_currency_widget']=$id;
