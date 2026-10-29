<?php
/**
 * DEV only: real native Woo calculated cart and temporary, random isolated
 * Woo coupons with YayCurrency Pro selected. Coupon fixtures are deleted in
 * finally; no orders, emails, payments, gift changes, inventory or WWW.
 */
require __DIR__.'/campaign-yay-bf-dev-loader-and-cart-uat.php';
if(!defined('WP_CLI') || !WP_CLI
 || wp_parse_url(home_url('/'),PHP_URL_HOST)!=='dev.beyourlover.com'
 || get_option('andy_campaign_lite_master','no')!=='no'
 || get_option('woocommerce_currency')!=='USD'
 || !class_exists('WC_Discounts') || !class_exists('WC_Coupon')) {
    throw new RuntimeException('BF_DEV_NATIVE_COUPON_GATE_DENIED');
}
$cases=[
    ['fixed_cart','219.00',1,null,0,'20.00',19900,2],
    ['percent','220.00',1,null,0,'10',19800,1],
    ['fixed_product','209.00',1,null,0,'10.00',19900,2],
    ['fixed_cart','109.50',2,null,0,'20.00',19900,2],
    ['fixed_cart','100.00',1,'119.00',1,'20.00',19900,2],
];
$passed=0;
foreach($cases as $spec){
    [$type,$p1price,$qty1,$p2price,$qty2,$discount,$expected,$slots]=$spec;
    $code='aicc-yay-dev-'.substr(bin2hex(random_bytes(12)),0,24);
    $cid=0;
    try {
        if(wc_get_coupon_id_by_code($code))
            throw new RuntimeException('BF_DEV_COUPON_COLLISION');
        $coupon=new WC_Coupon();
        $coupon->set_code($code);
        $coupon->set_discount_type($type);
        $coupon->set_amount($discount);
        $coupon->set_individual_use(true);
        $cid=$coupon->save();
        if(!$cid)throw new RuntimeException('BF_DEV_COUPON_SAVE_REJECTED');
        $p1=wc_get_product(40026);
        if(!$p1 instanceof WC_Product)throw new RuntimeException('BF_DEV_COUPON_P1_MISSING');
        $p1->set_regular_price($p1price);
        $p1->set_price($p1price);
        $cartData=['merch1'=>[
            'key'=>'merch1','data'=>$p1,'product_id'=>40026,
            'variation_id'=>0,'variation'=>[],'quantity'=>$qty1,
            'line_subtotal'=>'0.00','line_total'=>'0.00',
            'line_subtotal_tax'=>0,'line_tax'=>0,'line_tax_data'=>[],
        ]];
        if($p2price!==null){
            $p2=wc_get_product(40027);
            if(!$p2 instanceof WC_Product)throw new RuntimeException('BF_DEV_COUPON_P2_MISSING');
            $p2->set_regular_price($p2price);
            $p2->set_price($p2price);
            $cartData['merch2']=[
                'key'=>'merch2','data'=>$p2,'product_id'=>40027,
                'variation_id'=>0,'variation'=>[],'quantity'=>$qty2,
                'line_subtotal'=>'0.00','line_total'=>'0.00',
                'line_subtotal_tax'=>0,'line_tax'=>0,'line_tax_data'=>[],
            ];
        }
        $cart=new WC_Cart();
        $cart->set_cart_contents($cartData);
        $cart->set_applied_coupons([$code]);
        $cart->calculate_totals();
        $q=Andy_Commerce_Campaign_Yay_USD_Basis_Candidate_UAT::quote($cart);
        $eligible=BYL_BF2026_Gifts_Candidate_UAT::eligibility($cart);
        if(($q['ok']??null)!==true
            || ($q['basis_minor']??null)!==$expected
            || ($eligible['basis_minor']??null)!==$expected
            || ($eligible['slots']??null)!==$slots){
            echo 'BF_DEV_COUPON_UAT_REJECT currency='.$wanted.
                ' type='.$type.' qty='.$qty1.
                ' reason='.($q['reason']??'UNKNOWN').
                ' source='.($q['basis_minor']??'null').
                ' expected='.$expected.
                ' slots='.($eligible['slots']??'null').
                ' native_local_net='.$cart->get_cart_contents_total().PHP_EOL;
            throw new RuntimeException('BF_DEV_COUPON_RECONCILIATION_FAILED');
        }
        echo 'BF_DEV_COUPON_UAT_PASS currency='.$wanted.' type='.$type.
            ' qty='.$qty1.' products='.count($cartData).
            ' USD_NET_MINOR='.$expected.' gift_slots='.$slots.
            ' native_local_net='.$cart->get_cart_contents_total().PHP_EOL;
        ++$passed;
    } catch(Throwable $e){
        echo 'BF_DEV_COUPON_UAT_DIAGNOSTIC_CLASS='.get_class($e).
            ' CODE='.substr(preg_replace('/[^a-zA-Z0-9_-]/','?',(string)$e->getMessage()),0,120).PHP_EOL;
        throw $e;
    } finally {
        if($cid>0) {
            $item=wc_get_product($cid);
            // Woo coupons aren't WC products; delete only verified unique code.
            $found=new WC_Coupon($cid);
            if($found->get_id()===$cid && $found->get_code()===$code) {
                $found->delete(true);
                echo 'BF_DEV_TEMP_COUPON_REMOVED=YES'.PHP_EOL;
            } else {
                echo 'BF_DEV_TEMP_COUPON_REMOVAL_REQUIRES_REVIEW=YES'.PHP_EOL;
            }
        }
    }
}
echo 'BF_DEV_WOO_NATIVE_COUPON_PASS='.$passed.
    ' currency='.$wanted.' no_payment_or_order=YES'.PHP_EOL;
