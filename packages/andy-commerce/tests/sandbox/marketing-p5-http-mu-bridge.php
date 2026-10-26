<?php
/**
 * M-P5 local HTTP test bridge. NEVER package in Andy Commerce.
 * Copy temporarily as isolated MU plugin, then REMOVE with test teardown.
 * Synthetic offers only; no M-P4 Checkout lifecycle hook is asserted here.
 */
if(!defined('ABSPATH'))exit;
add_action('woocommerce_init', static function():void {
    if(!defined('AICC_MARKETING_RUNTIME_SANDBOX')
        || AICC_MARKETING_RUNTIME_SANDBOX!==true
        ||!defined('DB_NAME') || DB_NAME!=='aicc_commerce_p5_20261023'
        || home_url()!=='http://127.0.0.1:18973'
        || wp_get_environment_type()!=='local'
        || !defined('WP_HTTP_BLOCK_EXTERNAL')||WP_HTTP_BLOCK_EXTERNAL!==true
        || !defined('DISABLE_WP_CRON')||DISABLE_WP_CRON!==true
        ||get_woocommerce_currency()!=='USD'
        ||get_option('aicc_m14_sandbox_active','no')!=='no') return;

    // Sandbox files copied from the exact #228 GitHub HEAD by test driver.
    $root=WPMU_PLUGIN_DIR.'/aicc-marketing-p5-snapshot';
    $file=$root.'/class-andy-commerce-marketing-p2-woo-adapter.php';
    if(!is_file($file))return;
    require_once $file;
    $p1=Andy_Commerce_Marketing_P1_Sandbox_Contract::class;
    $case=get_option('aicc_marketing_p5_local_case','off');
    $rules=[
        'fixed'=>['spend_save',['threshold_minor'=>6900,'discount_minor'=>1200]],
        'percent'=>['spend_save',['threshold_minor'=>6900,'percent_bp'=>1000]],
        'gift'=>['free_gift',['threshold_minor'=>9900,'product_id'=>30]],
        'bogo'=>['bogo',['buy_product_id'=>27,'buy_qty'=>2,'get_product_id'=>28,
                         'get_qty'=>1,'percent_bp'=>5000]],
        'bundle'=>['bundles',['product_ids'=>[27,29],'discount_minor'=>2300]],
        'tier'=>['tiered',['tiers'=>[['minimum_qty'=>2,'percent_bp'=>1000],
                                    ['minimum_qty'=>4,'percent_bp'=>2000]]]],
    ];
    if(!is_string($case)||!isset($rules[$case]))return;
    $draft=[
        'id'=>'campaign_p5_'.$case.'_01','revision'=>1,'name'=>'P5 test '.$case,
        'type'=>$rules[$case][0],'rule'=>$rules[$case][1],
        'priority'=>10,'lifecycle'=>'scheduled','active'=>false,
        'status'=>'draft','policy'=>[
            'start_date'=>'','end_date'=>'','product_scope'=>'all',
            'product_ids'=>[],'stack_policy'=>'best_single'],
        'currency'=>'USD','countries'=>['US'],
    ];
    $flags=[
        '27'=>['eligible_for_promotion'=>true,'special_shipping'=>false,'free_gift'=>false],
        '28'=>['eligible_for_promotion'=>true,'special_shipping'=>false,'free_gift'=>false],
        '29'=>['eligible_for_promotion'=>true,'special_shipping'=>false,'free_gift'=>false],
        '30'=>['eligible_for_promotion'=>false,'special_shipping'=>false,'free_gift'=>true],
    ];
    // Only memory filters. No published campaigns or plugin-option writes.
    $options=[
        Andy_Commerce_M7_Draft_Studio::OPTION=>[$draft['id']=>$draft],
        $p1::SWITCH_OPTION=>'yes',
        $p1::APPROVAL_OPTION=>$p1::prepare($draft),
        'andy_commerce_marketing_p2_line_flags'=>$flags,
        'andy_commerce_marketing_p3_sku_enabled'=>'yes',
        'andy_commerce_marketing_p4_order_cap_enabled'=>'no',
    ];
    foreach($options as $key=>$value){
        add_filter('pre_option_'.$key,static function($previous)use($value){return $value;},1,1);
    }
    Andy_Commerce_Marketing_P2_Woo_Adapter::boot();
},5);
