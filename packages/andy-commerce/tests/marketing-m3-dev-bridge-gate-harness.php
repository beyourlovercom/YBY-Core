<?php
/** M3 Dev bridge fail-closed contract harness; no real WordPress bootstrap. */
declare(strict_types=1);
define('ABSPATH', __DIR__);
require_once dirname(__DIR__).'/includes/class-andy-commerce-m3-dev-bridge.php';
$cases=0;
$check=static function(bool $condition,string $name) use (&$cases):void {
 $cases++;
 if(!$condition) { throw new RuntimeException('M3 Bridge gate failed: '.$name); }
};
$check(Andy_Commerce_M3_Dev_Bridge::enabled()===false,'disabled unless explicitly opted-in');
$source=file_get_contents(dirname(__DIR__).'/includes/class-andy-commerce-m3-dev-bridge.php');
$check(str_contains($source,"['development','staging']"),'Dev environment allowlist');
$check(str_contains($source,"dev.beyourlover.com"),'exact Dev host restriction');
$check(str_contains($source,"ANDY_COMMERCE_M3_DEV_DRAFTS_ENABLED"),'explicit site opt-in');
$check(str_contains($source,"admin_post_andy_commerce_m3_draft_request"),'authenticated route only');
$check(!str_contains($source,'admin_post_nopriv_'),'no anonymous route');
$check(str_contains($source,"REQUEST_METHOD"),'POST method check');
$check(str_contains($source,"manage_woocommerce"),'capability check');
$check(!str_contains($source,'dbDelta('),'no implicit migration');
$check(!str_contains($source,'register_rest_route('),'no public rest route');
$check(!str_contains($source,"add_action('woocommerce_"),'no Woo runtime hooks');
echo "M3_DEV_BRIDGE_GATE_PASS={$cases}\n";
