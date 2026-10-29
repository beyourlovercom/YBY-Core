<?php
/**
 * V1 USD gift eligibility audit using Woo's ACTUAL native discount engine.
 * Strictly the disposable Woo CI database: NEVER run on Dev or Production.
 */
if (!defined('ABSPATH') || !defined('DB_NAME')
 || DB_NAME!=='commerce_p5_isolated'
 || get_option('siteurl')!=='http://commerce-p5.invalid'
 || !defined('WP_HTTP_BLOCK_EXTERNAL') || WP_HTTP_BLOCK_EXTERNAL!==true
 || get_option('woocommerce_currency')!=='USD') {
    throw new RuntimeException('YAY_USD_NATIVE_TEST_ISOLATION_DENIED');
}
require_once dirname(__DIR__).'/includes/class-andy-commerce-campaign-source-usd-discounts-v1.php';
if(function_exists('wc_load_cart'))wc_load_cart();
$assert=static function($b,$message){if(!$b)throw new RuntimeException('YAY_USD_NATIVE_FAIL_'.$message);};
$fixtures=[];$passes=0;
try {
    foreach(['p1','p2'] as $name) {
        $p=new WC_Product_Simple();
        $p->set_name('USD replay disposable '.$name);
        $p->set_status('publish');
        $p->set_catalog_visibility('hidden');
        $p->set_regular_price('100.00');
        $p->set_price('100.00');
        $p->set_manage_stock(false);
        $id=$p->save();
        $fixtures[]=['product',$id];
        if($name==='p1')$p1=$id;else $p2=$id;
    }
    foreach([
        ['fixed_cart','20.00','usd-replay-cart20'],
        ['percent','10','usd-replay-percent10'],
        ['fixed_product','10.00','usd-replay-product10'],
    ] as [$type,$amount,$code]) {
        $c=new WC_Coupon();
        $c->set_code($code);
        $c->set_discount_type($type);
        $c->set_amount($amount);
        $id=$c->save();
        $fixtures[]=['coupon',$id];
        $codes[$type]=$code;
    }
    $cases=[
        ['fixed_cart','219.00',1,'100.00',0],
        ['percent','219.00',1,'100.00',0],
        ['fixed_product','219.00',1,'100.00',0],
        ['fixed_cart','109.50',2,'100.00',0],
        ['percent','118.99',1,'100.00',1],
        ['fixed_product','119.00',1,'100.00',1],
    ];
    foreach($cases as [$type,$p1price,$qty,$p2price,$second]){
        $p=wc_get_product($p1);$p->set_regular_price($p1price);$p->set_price($p1price);$p->save();
        $p=wc_get_product($p2);$p->set_regular_price($p2price);$p->set_price($p2price);$p->save();
        WC()->cart->empty_cart();
        $assert((bool)WC()->cart->add_to_cart($p1,$qty),'add_primary');
        if($second)$assert((bool)WC()->cart->add_to_cart($p2,1),'add_second');
        $assert((bool)WC()->cart->apply_coupon($codes[$type]),'native_coupon_'.$type);
        WC()->cart->calculate_totals();
        $lines=[];
        foreach(WC()->cart->get_cart() as $key=>$line) {
            $product=$line['data'];
            $usd=(string)$product->get_price('edit');
            $usdMinor=(int)round((float)$usd*100);
            $lines[(string)$key]=[
                'product'=>$product,'quantity'=>(int)$line['quantity'],
                'usd_unit_minor'=>$usdMinor,'cart_item'=>$line,
            ];
        }
        $quote=Andy_Commerce_Campaign_Source_USD_Discounts_V1::replay(
            $lines,WC()->cart->get_applied_coupons());
        $assert(($quote['ok']??false)===true,'replay_'.$type.'_'.($quote['reason']??''));
        $nativeNet=(int)round((float)WC()->cart->get_cart_contents_total()*100);
        $nativeDiscount=(int)round((float)WC()->cart->get_discount_total()*100);
        $assert($quote['basis_minor']===$nativeNet,'net_'.$type.'_got_'.$quote['basis_minor'].'_native_'.$nativeNet);
        $assert($quote['discount_minor']===$nativeDiscount,'discount_'.$type);
        $assert($quote['subtotal_minor']-$quote['discount_minor']===$quote['basis_minor'],'ledger');
        echo 'YAY_NATIVE_USD_DISCOUNT_PASS '.$type.' qty='.$qty.' second='.$second.' USD_NET='.$nativeNet.PHP_EOL;
        ++$passes;
    }
    // An unsupported or missing coupon may never grant free gift eligibility.
    $line=array_values($lines)[0];
    $bad=Andy_Commerce_Campaign_Source_USD_Discounts_V1::replay(['one'=>$line],['not-a-real-coupon']);
    $assert(($bad['ok']??true)===false,'unknown_coupon_fails_closed');++$passes;
    $assert($passes===7,'count');
    echo 'YAY_NATIVE_WOO_USD_DISCOUNT_REPLAY_PASS='.$passes.PHP_EOL;
} finally {
    if(WC()->cart)WC()->cart->empty_cart();
    foreach(array_reverse($fixtures) as [$type,$id]){
        if($type==='coupon')wp_delete_post($id,true);
        else{$p=wc_get_product($id);if($p)$p->delete(true);}
    }
    echo 'YAY_DISPOSABLE_FIXTURES_REMOVED='.count($fixtures).PHP_EOL;
}
