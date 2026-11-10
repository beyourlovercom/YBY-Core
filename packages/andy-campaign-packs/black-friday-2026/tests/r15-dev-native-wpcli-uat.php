<?php
/** BF2026 R15: DEV-ONLY WooCommerce native cart + order-meta UAT.
 * Uses temporary WP product category assignments, restores all before exit.
 * Never persists or pays an order, posts an ERP shipment, or edits WWW.
 */
if (!defined('WP_CLI') || !WP_CLI ||
    wp_parse_url(home_url('/'),PHP_URL_HOST)!=='dev.beyourlover.com') {
    throw new RuntimeException('R15_DEV_CLI_ONLY');
}
define('BYL_BF2026_DEV_CLI_TEST',true);
if (!class_exists('BYL_BF2026_R15_Entitlements') || !class_exists('WooCommerce')
    || !BYL_BF2026_R15_Entitlements::ready()) {
    throw new RuntimeException('R15_DEV_PLUGIN_NOT_READY');
}
// The Canary bootstrap ran earlier at WP init before this CLI-only flag.
// Explicitly boot the R15 Woo hooks inside this isolated test process.
BYL_BF2026_R15_Entitlements::boot();
if (get_option('andy_campaign_lite_master','no')!=='no'
    || get_option('byl_bf2026_canary_v1',null)!==null) {
    throw new RuntimeException('DEV_MASTER_OR_BROWSER_CANARY_NOT_OFF');
}
if (get_woocommerce_currency()!=='USD') {throw new RuntimeException('SOURCE_NOT_USD');}
if (!function_exists('wc_load_cart')) {throw new RuntimeException('WC_NATIVE_CART_UNAVAILABLE');}
wc_load_cart();
if (!WC() || !WC()->cart || !WC()->customer || WC()->cart->get_cart()) {
    throw new RuntimeException('NOT_FRESH_WPCLI_CART');
}
$cart=WC()->cart;$customer=WC()->customer;
$giftIds=[9141,9095];
$original=[];
$createdTerms=[];
$GLOBALS['r15_native_pass']=0;
function r15check($ok,$name) {
    if (!$ok) {throw new RuntimeException('R15_NATIVE_FAILED_'.$name);}
    ++$GLOBALS['r15_native_pass'];
    echo 'PASS '.$name.PHP_EOL;
}
try {
    // The Dev store lacks the production Gift taxonomy. Create it ONLY in
    // this Dev site; keep the original product-category IDs to restore.
    $tids=[];
    foreach ([['gift',0],['gift-upgrade','gift'],
                ['gift-upgrade-10','gift-upgrade'],
                ['gift-upgrade-15','gift-upgrade']] as $entry) {
        [$slug,$parent]=$entry;
        $term=get_term_by('slug',$slug,'product_cat');
        if (!$term) {
            $r=wp_insert_term($slug,'product_cat',[
                'slug'=>$slug,'parent'=>is_string($parent)?$tids[$parent]:0,
                'description'=>'Dev-only Black Friday product-category UAT'
            ]);
            if (is_wp_error($r)) {throw new RuntimeException('CREATE_TERM_'.$slug.'_'.$r->get_error_message());}
            $tid=(int)$r['term_id'];
            $createdTerms[]=$tid;
        } else {
            $tid=(int)$term->term_id;
        }
        $tids[$slug]=$tid;
    }
    foreach ($giftIds as $id) {
        $p=wc_get_product($id);
        if (!$p || !$p->is_type('simple') || !$p->is_in_stock()
            || !$p->is_purchasable() || $p->backorders_allowed()) {
            throw new RuntimeException('PRODUCT_NOT_USABLE_'.$id);
        }
        $original[$id]=wp_get_post_terms($id,'product_cat',['fields'=>'ids']);
        $tier=($id===9141)?'gift-upgrade-10':'gift-upgrade-15';
        wp_set_post_terms($id,array_values(array_unique(array_merge(
            $original[$id],
            [$tids['gift'],$tids['gift-upgrade'],$tids[$tier]]
        ))),'product_cat',false);
    }
    r15check(BYL_BF2026_R15_Entitlements::upgrade(9141)['fee_minor']===1000,
        'DEV_HEART_REAL_SKU_AND_TIER');
    r15check(BYL_BF2026_R15_Entitlements::upgrade(9095)['fee_minor']===1500,
        'DEV_CAMERA_REAL_SKU_AND_TIER');
    foreach ([9096,25358,34737] as $id) {
        $p=wc_get_product($id);
        if (!$p || !$p->is_in_stock()) {
            throw new RuntimeException('MERCHANDISE_NOT_IN_STOCK_'.$id);
        }
    }
    $customer->set_shipping_country('CA');
    $key1=$cart->add_to_cart(9096,1);
    if (!$key1) {throw new RuntimeException('ADD_69_FAILED');}
    $cart->calculate_totals();
    $q=BYL_BF2026_R15_Entitlements::entitlement($cart,'CA');
    r15check($q['ok'] && $q['random_gift_qty']===0 && $q['basis_minor']<9900,
        'CA_USD69_ZERO_FREE');
    $key2=$cart->add_to_cart(25358,1);
    if (!$key2) {throw new RuntimeException('ADD_138_FAILED');}
    $cart->calculate_totals();
    $q=BYL_BF2026_R15_Entitlements::entitlement($cart,'CA');
    r15check($q['ok'] && $q['random_gift_qty']===1 &&
        $q['basis_minor']>=9900 && $q['basis_minor']<19900,
        'CA_USD138_ONE_RANDOM');
    $key3=$cart->add_to_cart(34737,1);
    if (!$key3) {throw new RuntimeException('ADD_217_FAILED');}
    $cart->calculate_totals();
    $q=BYL_BF2026_R15_Entitlements::entitlement($cart,'CA');
    r15check($q['ok']&&$q['random_gift_qty']===2 &&
        $q['basis_minor']>=19900,'CA_USD217_TWO_RANDOM');
    $order=new WC_Order();
    $order->set_shipping_country('CA');
    BYL_BF2026_R15_Entitlements::order_meta($order);
    r15check((int)$order->get_meta('_byl_bf2026_random_gift_qty',true)===2,
        'CA_IN_MEMORY_ORDER_META_TWO_RANDOM');
    r15check((int)$order->get_meta('_byl_bf2026_paid_gift_product_id',true)===0,
        'CA_ORDER_NO_PAID_SKU');
    $customer->set_shipping_country('US');
    $cart->calculate_totals();
    $result=BYL_BF2026_R15_Entitlements::choose_paid($cart,'US',9141);
    echo 'US_CHOOSE_HEART_DECISION='.json_encode($result).PHP_EOL;
    r15check(!empty($result['ok']) && !empty($result['changed']),
        'US_SELECT_HEART_NATIVE_WOO');
    $cart->calculate_totals();
    $giftLines=array_filter($cart->get_cart(),static fn($v)=>
        !empty($v['_byl_bf2026_paid_slot']));
    r15check(count($giftLines)===1 &&
        abs((float)array_values($giftLines)[0]['line_total']-10.0)<0.01,
        'US_HEART_EXACT_10_DOLLARS');
    $q=BYL_BF2026_R15_Entitlements::entitlement($cart,'US');
    r15check($q['ok'] && $q['random_gift_qty']===1 && $q['paid_count']===1,
        'US_217_ONE_PAID_ONE_RANDOM');
    $result=BYL_BF2026_R15_Entitlements::choose_paid($cart,'US',9095);
    r15check(!empty($result['ok']) && !empty($result['changed']),
        'US_REPLACE_HEART_WITH_CAMERA');
    $cart->calculate_totals();
    $giftLines=array_filter($cart->get_cart(),static fn($v)=>
        !empty($v['_byl_bf2026_paid_slot']));
    r15check(count($giftLines)===1 &&
        abs((float)array_values($giftLines)[0]['line_total']-15.0)<0.01,
        'US_ONE_PAID_CAMERA_EXACT_15');
    $order=new WC_Order();
    $order->set_shipping_country('US');
    BYL_BF2026_R15_Entitlements::order_meta($order);
    r15check((int)$order->get_meta('_byl_bf2026_paid_gift_product_id',true)===9095,
        'US_ORDER_CAMERA_PRODUCT_META');
    $customer->set_shipping_country('CA');
    $cart->calculate_totals();
    $giftLines=array_filter($cart->get_cart(),static fn($v)=>
        !empty($v['_byl_bf2026_paid_slot']));
    r15check(count($giftLines)===0,'CA_COUNTRY_CHANGED_AUTO_REVOKES_PAID');
    $q=BYL_BF2026_R15_Entitlements::entitlement($cart,'CA');
    r15check($q['random_gift_qty']===2,'CA_RECOVERS_TWO_RANDOM');
    $cart->calculate_totals();
    r15check(count(array_filter($cart->get_cart(),static fn($v)=>
        !empty($v['_byl_bf2026_paid_slot'])))===0,'RETRY_NO_DUPLICATE_PAID');
    echo 'BF2026_R15_DEV_NATIVE_WOO_UAT_PASS='.$GLOBALS['r15_native_pass'].PHP_EOL;
} finally {
    $cart->empty_cart();
    $cart->calculate_totals();
    foreach($original as $id=>$categories) {
        wp_set_post_terms($id,$categories,'product_cat',false);
    }
    foreach(array_reverse($createdTerms) as $tid) {
        wp_delete_term($tid,'product_cat');
    }
    echo 'DEV_CART_EMPTY='.(count($cart->get_cart())===0?'YES':'NO').PHP_EOL;
    echo 'DEV_GIFT_TERM_RESTORE_ATTEMPTED='.count($createdTerms).PHP_EOL;
}
