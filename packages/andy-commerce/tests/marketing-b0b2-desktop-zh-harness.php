<?php
/**
 * Desktop-only Simplified Chinese marketing right-content UI contract.
 * Core owns left navigation; this is STATIC regression, not a live WP UAT.
 */
declare(strict_types=1);
define('ABSPATH', '/test/');
$root = dirname(__DIR__);
$files = [
 'M7' => $root . '/admin/views/marketing-m7-dev-studio.php',
 'M9' => $root . '/admin/views/marketing-m9-usd-multirule-lab.php',
 'V2' => $root . '/admin/views/marketing-v2-rule-editor.php',
 'fallback' => $root . '/admin/views/marketing-promotions.php',
];
$sources = [];
foreach($files as $key=>$file) $sources[$key] = file_get_contents($file);
$n=0;
$ok = static function(bool $cond,string $label) use (&$n):void {
    if(!$cond) throw new RuntimeException("Marketing B0-B2 FAIL: ".$label);
    ++$n;
};
foreach([
 'M9'=>['营销 OS · 美元多规则工作台','订单场景模拟','独立营销规则',
   '新建未启用活动草稿','币种换算结果','模拟达标','未达到门槛','重新计算规则',
   '保存为未启用草稿','未修改 Woo 运费','非实时模拟汇率'],
 'M7'=>['营销 OS · 活动工作台','进入美元多规则工作台','活动草稿',
   '新建未启用草稿','模拟预览','赠品','未启用'],
 'V2'=>['活动规则','可视化字段（推荐）','满额门槛（USD）','固定减免金额（USD）',
   '百分比','赠品发放仍未启用。','高级 JSON'],
 'fallback'=>['营销活动','活动类型','执行状态','未启用']
] as $key=>$texts) {
 foreach($texts as $label) $ok(str_contains($sources[$key],$label),"$key 中文可见文案: $label");
}
foreach($sources as $key=>$source) {
 $ok(!str_contains($source,'add_menu_page(')
     && !str_contains($source,'add_submenu_page(')
     && !str_contains($source,'register_nav_menus('),
     "$key never creates left-navigation entries");
 $ok(!str_contains($source,'woocommerce_cart_calculate_fees')
     && !str_contains($source,'woocommerce_package_rates'),
     "$key never hooks live Woo discount/shipping");
}
$core=file_get_contents(dirname(__DIR__,3).'/admin/class-yby-andy-os-navigation.php');
$ok(str_contains($core,"'andy-core-marketing'") &&
    str_contains($core,"'营销'") &&
    str_contains($core,"case 'andy-core-marketing'") &&
    str_contains($core,"'marketing-promotions'"),
    'Andy Core canonical native L2 marketing navigation remains owner');
$ok(str_contains($sources['fallback'],'suppress_legacy_tabs'),
    'legacy fallback tabs suppressed under Andy Core server-side shell');
$ok(str_contains($sources['M9'],"wp_nonce_field('andy_commerce_m7_save_draft')") &&
    str_contains($sources['M7'],"wp_nonce_field('andy_commerce_m7_save_draft')"),
    'WordPress original draft nonces preserved');
$ok(str_contains($sources['M9'],'max-width:1360px') &&
    str_contains($sources['V2'],'repeat(4,minmax(0,1fr))') &&
    str_contains($sources['M7'],'max-width:1360px'),
    'desktop 4-column, wide-form layouts frozen');
$ok(str_contains($sources['M9'],'仅模拟') && str_contains($sources['M9'],'未启用') &&
    !str_contains($sources['M9'],'All promotions OFF.'),
    'simulation never presented as live award');
$ok(str_contains($sources['V2'],'draft_rule_editor') &&
    str_contains($sources['V2'],'value="advanced"'),
    'existing visual/advanced editor contract stays intact');
echo "MARKETING_DESKTOP_ZH_B0B2_STATIC_ASSERTIONS={$n} PASS; CORE_NAV=OWNER; LIVE_MUTATIONS=OFF\n";
