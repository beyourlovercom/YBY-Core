<?php
/** Inert unit fixture: legacy Marketing V1 screens stay URL-addressable but are not duplicate L3 menu entries. */
declare(strict_types=1);
define('ABSPATH','/');
class Andy_Campaign_Lite {}
class Andy_Commerce_M7_Draft_Studio { public static function dev_only(): bool { return true; } }
class Andy_Commerce_Currency_V2_Dev { public static function access(): bool { return true; } }
function current_user_can($cap) { return $cap === 'manage_woocommerce'; }
function must($ok,$case) { if(!$ok)throw new RuntimeException('MENU_FAIL '.$case); }
require dirname(__DIR__) . '/admin/class-andy-commerce-admin.php';
must(Andy_Commerce_Admin::marketing_l3_route_exists('marketing-create','andy-commerce'),
    'legacy direct route retained for history');
$menu=Andy_Commerce_Admin::register_l3_items(array(),'andy-commerce');
$tabs=array_column($menu,'tab');
must(in_array('currency-os',$tabs,true),'Currency L3 stays visible');
foreach (array('marketing-create','marketing-templates','marketing-simulate','marketing-logs') as $legacy) {
    must(!in_array($legacy,$tabs,true),'no duplicate legacy L3 when Campaign exists: '.$legacy);
}
must(count($tabs)===1,'only Currency contributed by Commerce custom L3');
echo "COMMERCE_MARKETING_V1_CAMPAIGN_MENU_PASS=7\n";
