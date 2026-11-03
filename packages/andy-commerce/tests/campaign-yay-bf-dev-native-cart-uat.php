<?php
/**
 * DEV only, real native Woo cart totals on an in-process isolated cart.
 * Reuses the original-plugin + private-class BF Dev loader. No DB writes.
 */
require __DIR__.'/campaign-yay-bf-dev-loader-and-cart-uat.php';
if(!defined('WP_CLI') || !WP_CLI
 || wp_parse_url(home_url('/'),PHP_URL_HOST)!=='dev.beyourlover.com'
 || get_option('andy_campaign_lite_master','no')!=='no'){
    throw new RuntimeException('BF_NATIVE_CART_DEV_GATE_DENIED');
}
$cases=[
 ['99.50',2,null,0,19900,2],
 ['100.00',1,'98.99',1,19899,1],
 ['100.00',1,'99.00',1,19900,2],
 ['100.00',1,'99.01',1,19901,2],
];
$passed=0;
foreach($cases as [$price1,$qty1,$price2,$qty2,$basis,$slots]){
    $p1=wc_get_product(40026);
    if(!$p1 instanceof WC_Product)throw new RuntimeException('BF_NATIVE_CART_PRODUCT_1_NOT_FOUND');
    $p1->set_regular_price($price1);
    $p1->set_price($price1);
    $cartData=['merch1'=>[
        'key'=>'merch1','data'=>$p1,'product_id'=>40026,
        'variation_id'=>0,'variation'=>[],'quantity'=>$qty1,
        'line_subtotal'=>'0.00','line_total'=>'0.00',
        'line_subtotal_tax'=>0,'line_tax'=>0,'line_tax_data'=>[],
    ]];
    if($price2!==null){
        $p2=wc_get_product(40027);
        if(!$p2 instanceof WC_Product)throw new RuntimeException('BF_NATIVE_CART_PRODUCT_2_NOT_FOUND');
        $p2->set_regular_price($price2);
        $p2->set_price($price2);
        $cartData['merch2']=[
            'key'=>'merch2','data'=>$p2,'product_id'=>40027,
            'variation_id'=>0,'variation'=>[],'quantity'=>$qty2,
            'line_subtotal'=>'0.00','line_total'=>'0.00',
            'line_subtotal_tax'=>0,'line_tax'=>0,'line_tax_data'=>[],
        ];
    }
    $cart=new WC_Cart();
    $cart->set_cart_contents($cartData);
    try {
        $cart->calculate_totals();
    } catch(Throwable $e){
        echo 'BF_DEV_NATIVE_CART_CALC_ERROR_CLASS='.get_class($e).PHP_EOL;
        echo 'BF_DEV_NATIVE_CART_CALC_ERROR_CODE='.substr((string)$e->getMessage(),0,180).PHP_EOL;
        exit(24);
    }
    $quote=Andy_Commerce_Campaign_Yay_USD_Basis_Candidate_UAT::quote($cart);
    $gift=BYL_BF2026_Gifts_Candidate_UAT::eligibility($cart);
    if(($quote['ok']??null)!==true ||
      ($quote['basis_minor']??null)!==$basis ||
      ($gift['slots']??null)!==$slots ||
      ($gift['basis_minor']??null)!==$basis){
        echo 'BF_DEV_NATIVE_CART_MISMATCH reason='.($quote['reason']??'no').
            ' quote_basis='.($quote['basis_minor']??'null').
            ' expected='.$basis.
            ' gift_basis='.($gift['basis_minor']??'null').
            ' gift_slots='.($gift['slots']??'null').PHP_EOL;
        exit(25);
    }
    echo 'BF_DEV_NATIVE_CART_PASS currency='.$wanted.
        ' usd_minor='.$basis.' slots='.$slots.
        ' woo_local_total='.$cart->get_cart_contents_total().PHP_EOL;
    ++$passed;
}
echo 'BF_DEV_WOO_CALCULATED_CART_PASS='.$passed.' currency='.$wanted.
    ' no_coupon_or_payment_or_db_write=YES'.PHP_EOL;
