<?php
/**
 * Explicit WP-CLI-only, local disposable Woo M-P1/M-P2 integration smoke.
 *
 * Run with: wp --skip-plugins=andy-commerce eval-file <this-file>
 * NO persisted approval/activation, order, payment, email or production write.
 * Approval and classification options are intercepted in memory in this process.
 * Only isolated sandbox cart/session, fictional products and Woo totals are used.
 */
if (!defined('AICC_MARKETING_RUNTIME_SANDBOX')) define('AICC_MARKETING_RUNTIME_SANDBOX',true);
if (!defined('DB_NAME') || DB_NAME!=='aicc_commerce_p5_20261023'
    || home_url()!=='http://127.0.0.1:18973'
    || wp_get_environment_type()!=='local'
    || !defined('WP_HTTP_BLOCK_EXTERNAL') || WP_HTTP_BLOCK_EXTERNAL!==true
    || !defined('DISABLE_WP_CRON') || DISABLE_WP_CRON!==true
    || get_woocommerce_currency()!=='USD'
    || get_option('aicc_m14_sandbox_active','no')!=='no'
    || !class_exists('WooCommerce') || !isset(WC()->cart,WC()->customer,WC()->session)) {
    throw new RuntimeException('MARKETING_P1P2_LOCAL_WOO_ONLY');
}
require_once dirname(__DIR__,2).'/includes/class-andy-commerce-marketing-p2-woo-adapter.php';
$p1=Andy_Commerce_Marketing_P1_Sandbox_Contract::class;
$p2=Andy_Commerce_Marketing_P2_Woo_Adapter::class;
$checks=0;
$ok=static function(bool $test,string $reason)use(&$checks):void{
    if(!$test)throw new RuntimeException('MARKETING_P1P2_WOO_FAIL '.$reason);
    $checks++;
};
$rules=[
 'fixed'=>['spend_save',['threshold_minor'=>6900,'discount_minor'=>1200]],
 'percent'=>['spend_save',['threshold_minor'=>6900,'percent_bp'=>1000]],
 'gift'=>['free_gift',['threshold_minor'=>9900,'product_id'=>30]],
 'bogo'=>['bogo',['buy_product_id'=>27,'get_product_id'=>28,
                  'buy_qty'=>2,'get_qty'=>1,'percent_bp'=>5000]],
 'bundle'=>['bundles',['product_ids'=>[27,29],'discount_minor'=>2300]],
 'tier'=>['tiered',['tiers'=>[
                  ['minimum_qty'=>2,'percent_bp'=>1000],
                  ['minimum_qty'=>4,'percent_bp'=>2000]]]],
];
$expected=['fixed'=>1200,'percent'=>1850,'gift'=>0,
    'bogo'=>2250,'bundle'=>2300,'tier'=>3700];
$virtual=[
 $p1::SWITCH_OPTION=>'no',
 $p1::APPROVAL_OPTION=>null,
 Andy_Commerce_M7_Draft_Studio::OPTION=>[],
 'andy_commerce_marketing_p2_line_flags'=>[
    '27'=>['eligible_for_promotion'=>true,'special_shipping'=>false,'free_gift'=>false],
    '28'=>['eligible_for_promotion'=>true,'special_shipping'=>false,'free_gift'=>false],
    '29'=>['eligible_for_promotion'=>true,'special_shipping'=>false,'free_gift'=>false],
    '30'=>['eligible_for_promotion'=>false,'special_shipping'=>false,'free_gift'=>true],
 ],
];
foreach($virtual as $key=>$v) {
    add_filter('pre_option_'.$key,static function($old)use(&$virtual,$key){return $virtual[$key];},1,1);
}
$p2::boot();
$cart=WC()->cart;
$customer=WC()->customer;
$customer->set_shipping_country('US');$customer->set_billing_country('US');
$customer->set_shipping_state('CA');$customer->set_billing_state('CA');
$customer->set_shipping_postcode('90001');$customer->set_billing_postcode('90001');
WC()->session->set('chosen_shipping_methods',[]);
$results=[];
try {
    foreach([27,28,29,30] as $id) {
        $ok(wc_get_product($id) instanceof WC_Product,'product '.$id.' exists');
    }
    foreach($rules as $case=>[$type,$rule]) {
        $cart->empty_cart();
        $ok($cart->add_to_cart(27,2)!==false && $cart->add_to_cart(28,1)!==false
            && $cart->add_to_cart(29,1)!==false,$case.' cart setup');
        $cart->calculate_shipping();
        $rates=WC()->shipping()->get_packages()[0]['rates']??[];
        if(count($rates)!==1)throw new RuntimeException('MARKETING_P1P2_EXPECT_ONE_RATE');
        WC()->session->set('chosen_shipping_methods',[reset($rates)->get_id()]);
        $draft=[
            'id'=>'campaign_'.$case.'_001','revision'=>1,'name'=>$case,
            'type'=>$type,'rule'=>$rule,'priority'=>10,
            'lifecycle'=>'scheduled','active'=>false,'status'=>'draft',
            'policy'=>['start_date'=>'','end_date'=>'','product_scope'=>'all',
                'product_ids'=>[],'stack_policy'=>'best_single'],
            'currency'=>'USD','countries'=>['US'],
        ];
        $virtual[Andy_Commerce_M7_Draft_Studio::OPTION]=[$draft['id']=>$draft];
        $virtual[$p1::APPROVAL_OPTION]=$p1::prepare($draft);
        $virtual[$p1::SWITCH_OPTION]='yes';
        $cart->calculate_totals();
        $discount=(int)round((float)$cart->get_discount_total()*100);
        $ok($discount===$expected[$case],$case.' Woo discount expected '.$expected[$case].' actual '.$discount);
        if($case==='gift'){
            $giftLines=array_filter($cart->get_cart(),static fn($x)=>
                !empty($x['andy_commerce_p2_sandbox_gift']));
            $ok(count($giftLines)===1,'one marked gift');
            $gift=reset($giftLines);
            $ok($gift['product_id']===30&&$gift['quantity']===1,'gift exact SKU and quantity');
            $ok((int)round((float)$gift['line_total']*100)===0,'gift native line zero');
        } else {
            $codes=$cart->get_applied_coupons();
            $ok(count($codes)===1 && $codes[0]==='aicc-p2-'.$draft['id'],
                $case.' only one owned coupon');
        }
        // Repeated Woo total calculation must not stack or duplicate offers.
        $cart->calculate_totals();
        $ok((int)round((float)$cart->get_discount_total()*100)===$expected[$case],
            $case.' repeated totals idempotent');
        if($case==='gift'){
            $owned=array_filter($cart->get_cart(),static fn($x)=>
                !empty($x['andy_commerce_p2_sandbox_gift']));
            $ok(count($owned)===1,'gift not duplicated after recalculation');
            // Below-threshold native cart updates must revoke the gift.
            foreach($cart->get_cart() as $key=>$line){
                if(in_array($line['product_id'],[28,29],true))$cart->remove_cart_item($key);
            }
            $cart->calculate_totals();
            $ok(count(array_filter($cart->get_cart(),static fn($x)=>
                !empty($x['andy_commerce_p2_sandbox_gift'])))===0,
                'gift revoked when cart drops below threshold');
            $cart->add_to_cart(28,1);$cart->add_to_cart(29,1);
            $cart->calculate_totals();
            $ok(count(array_filter($cart->get_cart(),static fn($x)=>
                !empty($x['andy_commerce_p2_sandbox_gift'])))===1,
                'gift restored once when eligible again');
        }
        if($case==='fixed'){
            // A user-entered Woo coupon wins; it does not stack with auto.
            // This is the existing disposable Woo native coupon from P5,
            // not an ad-hoc fake virtual code that Woo may reject at validation.
            $manualCode='aicc-p5-only-25';
            $accepted=$cart->apply_coupon($manualCode);
            $ok($accepted===true,'native manual coupon accepted: '.wp_json_encode(
                wc_get_notices('error')));
            $cart->calculate_totals();
            $remaining=array_values($cart->get_applied_coupons());
            $ok($remaining===[$manualCode],
                'manual coupon alone, automatic revoked: codes='.
                wp_json_encode($remaining).' errors='.
                wp_json_encode(wc_get_notices('error')));
            $ok((int)round((float)$cart->get_discount_total()*100)===2500,
                'manual coupon USD25 not stacked');
            $cart->remove_coupon($manualCode);
            $cart->calculate_totals();
            $ok((int)round((float)$cart->get_discount_total()*100)===1200,
                'automatic offer re-evaluated when manual removed');
            $changed=$draft;$changed['revision']=2;
            $virtual[Andy_Commerce_M7_Draft_Studio::OPTION]=[$draft['id']=>$changed];
            $cart->calculate_totals();
            $ok((int)round((float)$cart->get_discount_total()*100)===0,
                'revised campaign approval automatically invalidated');
            $virtual[Andy_Commerce_M7_Draft_Studio::OPTION]=[$draft['id']=>$draft];
            $cart->calculate_totals();
            $ok((int)round((float)$cart->get_discount_total()*100)===1200,
                'approved revision restores proper discount');
        }
        // Disable the campaign without changing any database option.
        $virtual[$p1::SWITCH_OPTION]='no';
        $cart->calculate_totals();
        $ok((int)round((float)$cart->get_discount_total()*100)===0,
            $case.' switch OFF restores native amount');
        $ok($cart->get_applied_coupons()===[],$case.' removes owned coupon on OFF');
        $ok(count(array_filter($cart->get_cart(),static fn($x)=>
            !empty($x['andy_commerce_p2_sandbox_gift'])))===0,
            $case.' removes owned gift on OFF');
        $results[$case]=['discount_usd_minor'=>$discount,'off_cleared'=>true];
    }
    echo json_encode([
        'result'=>'MARKETING_P1P2_NATIVE_WOO_NO_PAYMENT_PASS',
        'checks'=>$checks,
        'campaigns'=>$results,
        'persisted_campaign_settings'=>false,
        'created_orders'=>0,'gateway_requests'=>0,
    ],JSON_THROW_ON_ERROR),PHP_EOL;
} finally {
    $virtual[$p1::SWITCH_OPTION]='no';
    $cart->empty_cart();
}
