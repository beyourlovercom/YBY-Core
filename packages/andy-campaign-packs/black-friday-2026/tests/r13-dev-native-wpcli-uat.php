<?php
/**
 * R13 Native Woo cart UAT: DEV-only isolated WP-CLI test.
 * Synthetic warehouse attestations exist ONLY in this process.
 * No order, payment, provider shipment or real gift inventory writes.
 */
$host=wp_parse_url(home_url('/'),PHP_URL_HOST);
if ($host!=='dev.beyourlover.com' || !defined('WP_CLI') || !WP_CLI) {
    throw new RuntimeException('DEV_WP_CLI_ONLY');
}
$dir=dirname(__DIR__);
foreach ([
 'class-byl-bf2026-r8-readiness.php'=>'BYL_BF2026_R8_Readiness',
 'class-byl-bf2026-gifts.php'=>'BYL_BF2026_Gifts',
 'class-byl-bf2026-r9-policy.php'=>'BYL_BF2026_R9_Policy',
 'class-byl-bf2026-r9c-woo-adapter.php'=>'BYL_BF2026_R9C_Woo_Adapter',
 'class-byl-bf2026-r12-auto-gift-planner.php'=>'BYL_BF2026_R12_Auto_Gift_Planner',
 'class-byl-bf2026-r13-cart-writer.php'=>'BYL_BF2026_R13_Cart_Writer',
] as $f=>$class) {
    if (!class_exists($class)) {require_once $dir.'/includes/'.$f;}
}
$ids=[30454,25045,9095];
foreach ($ids as $id) {
    $p=wc_get_product($id);
    if (!$p || !$p->is_type('simple') || !$p->is_in_stock()
        || !$p->is_purchasable() || $p->backorders_allowed()) {
        throw new RuntimeException('DEV_PRODUCT_NOT_READY_'.$id);
    }
}
$catalog=[
 30454=>['upgrade_minor'=>0],
 25045=>['upgrade_minor'=>0],
 9095=>['upgrade_minor'=>2000],
];
$approval=[];
foreach ($catalog as $id=>$entry) {
    $approval[$id]=[
      'approved'=>true, // UAT SYNTHETIC ONLY, NOT production approval
      'warehouse_available_qty'=>3,
      'landed_cost_usd_minor'=>100,
      'packed_weight_g'=>120,
      'warehouse_code'=>'dev-uat-no-fulfillment',
      'warehouse_checked_at'=>time(),
      'inventory_evidence_kind'=>'provider_verified', // Test fixture only, no provider contacted
      'reviewed_by'=>'DEV-UAT-SYNTHETIC-NOT-FOR-SHIPPING',
      'allowed_countries'=>($id===9095?['US']:['US','CA']),
    ];
}
add_filter('byl_bf2026_gift_catalog',static fn()=> $catalog,999);
add_filter('byl_bf2026_gift_approvals',static fn()=> $approval,999);
if (get_woocommerce_currency()!=='USD') {throw new RuntimeException('NOT_SOURCE_USD');}
if (!BYL_BF2026_Gifts::ready()) {throw new RuntimeException('DEV_FIXTURE_GIFT_READINESS_FAILED');}
if (!function_exists('wc_load_cart')) {throw new RuntimeException('WC_CART_LOADER_MISSING');}
wc_load_cart();
if (!WC() || !WC()->cart || !WC()->customer) {
    throw new RuntimeException('WC_CART_NOT_INITIALIZED');
}
$cart=WC()->cart;
if (count($cart->get_cart())!==0) {
    throw new RuntimeException('WPCLI_CART_NOT_FRESH_ABORT');
}
WC()->customer->set_shipping_country('US');
BYL_BF2026_Gifts::boot(); // ONLY IN DEV CLI PROCESS, not Campaign production activation
// Installed Dev plugin is older; manually register new R13 hooks ONLY for this CLI process.
BYL_BF2026_R13_Cart_Writer::boot();
echo 'GIFT_SOURCE='.(new ReflectionClass('BYL_BF2026_Gifts'))->getFileName().PHP_EOL;
function check_r13($ok,$name) {
    if (!$ok) { throw new RuntimeException('R13_NATIVE_FAIL_'.$name); }
    echo 'PASS '.$name.PHP_EOL;
}
function gift_rows($cart): array {
    return array_filter($cart->get_cart(),static fn($row)=>
       ($row['andy_campaign_pack_id']??'')==='byl-black-friday-2026'
        && ($row['_byl_bf2026_gift']??false)===true);
}
try {
    $key=$cart->add_to_cart(9096,1);
    if (!$key) {throw new RuntimeException('DEV_EARPOD_ADD_FAILED');}
    $cart->calculate_totals();
    $rows=gift_rows($cart);
    check_r13(count($rows)===1,'US_69_AUTO_ONE_FREE');
    check_r13((float)array_values($rows)[0]['line_total']===0.0,'FREE_PRICE_ZERO');
    $key=$cart->add_to_cart(25358,1);
    if (!$key) {throw new RuntimeException('DEV_PERFUME_ADD_FAILED');}
    $cart->calculate_totals();
    check_r13(count(gift_rows($cart))===1,'US_138_ONE_SLOT');
    echo 'PREMIUM_BEFORE='.json_encode(array_map(static fn($r)=>['id'=>$r['product_id'],'gift'=>$r['_byl_bf2026_gift']??false],$cart->get_cart())).PHP_EOL;
    $premium=BYL_BF2026_R13_Cart_Writer::choose_from_form($cart,9095);
    echo 'PREMIUM_DECISION='.json_encode($premium).PHP_EOL;
    echo 'PREMIUM_AFTER='.json_encode(array_map(static fn($r)=>['id'=>$r['product_id'],'gift'=>$r['_byl_bf2026_gift']??false,'paid'=>$r['_byl_bf2026_paid_slot']??null],$cart->get_cart())).PHP_EOL;
    check_r13(($premium['ok']??false)===true,'US_138_SELECT_UPGRADE');
    $cart->calculate_totals();
    $rows=gift_rows($cart);
    check_r13(count($rows)===1,'US_138_REPLACES_FREE');
    echo 'DEBUG_PAID_TOTALS='.json_encode(array_map(static fn($row)=>['id'=>$row['product_id'],'line_total'=>$row['line_total'],'line_subtotal'=>$row['line_subtotal']??null,'price'=>$row['data']->get_price(),'paid'=>$row['_byl_bf2026_paid_slot']??null],array_values($rows))).PHP_EOL;
    check_r13(abs(array_sum(array_map(static fn($row)=>(float)$row['line_total'],$rows))-20.0)<0.01,
      'US_138_UPGRADE_FEE_20');
    $key=$cart->add_to_cart(34737,1);
    if (!$key) {throw new RuntimeException('DEV_THIRD_MERCH_ADD_FAILED');}
    $cart->calculate_totals();
    $rows=gift_rows($cart);
    $merch=0.0;
    foreach($cart->get_cart() as $line) {
        if(($line['_byl_bf2026_gift']??false)!==true) {$merch+=(float)($line['line_total']??0);}
    }
    check_r13($merch>=199.0,'US_199_THRESHOLD');
    check_r13(count($rows)===2,'US_199_ONE_PAID_ONE_FREE');
    check_r13(count(array_filter($rows,static fn($row)=>(bool)($row['_byl_bf2026_paid_slot']??false)))===1,
      'ONE_PAID_MAX');
    WC()->customer->set_shipping_country('CA');
    $cart->calculate_totals();
    $rows=gift_rows($cart);
    check_r13(count($rows)===2,'CA_199_TWO_FREE');
    check_r13(count(array_filter($rows,static fn($row)=>(bool)($row['_byl_bf2026_paid_slot']??false)))===0,
      'CA_UPGRADE_REVOKED');
    $cart->calculate_totals();
    check_r13(count(gift_rows($cart))===2,'NO_DUPLICATE_ON_RETRY');
    $errors=new WP_Error();
    BYL_BF2026_R13_Cart_Writer::validate_classic(['shipping_country'=>'CA'],$errors);
    check_r13(!$errors->has_errors(),'CLASSIC_CHECKOUT_CA_VALID');
    $order=new WC_Order();
    $order->set_shipping_country('CA');
    $errors=new WP_Error();
    BYL_BF2026_R13_Cart_Writer::validate_store_api($order,$errors);
    check_r13(!$errors->has_errors(),'STORE_API_CA_VALID');
    $errors=new WP_Error();
    BYL_BF2026_R13_Cart_Writer::validate_classic(['shipping_country'=>'FR'],$errors);
    check_r13($errors->has_errors(),'UNAPPROVED_DESTINATION_BLOCKED');
    $line=array_values(gift_rows($cart))[0];
    $item=new WC_Order_Item_Product();
    BYL_BF2026_R13_Cart_Writer::order_meta($item,'uat-item',$line,$order);
    check_r13($item->get_meta('_byl_bf2026_sku',true)===$line['_byl_bf2026_sku'],
      'ORDER_LINE_ORIGINAL_GIFT_SKU');
    check_r13($item->get_meta('_byl_bf2026_warehouse',true)==='dev-uat-no-fulfillment',
      'ORDER_LINE_WAREHOUSE_CONTEXT');
} finally {
    $cart->empty_cart();
    $cart->calculate_totals();
    echo 'CART_CLEANED_EMPTY='. (count($cart->get_cart())===0?'YES':'NO') .PHP_EOL;
}
echo 'BF2026_DEV_NATIVE_WOO_GIFT_UAT_PASS'.PHP_EOL;
