<?php
/** Isolated Woo native compatibility regression (never use Dev/WWW DB). */
if (!defined('ABSPATH') || get_option('siteurl') !== 'http://commerce-p5.invalid'
    || !defined('WP_HTTP_BLOCK_EXTERNAL') || !WP_HTTP_BLOCK_EXTERNAL
    || !function_exists('wc_create_order')) throw new RuntimeException('P5 isolation gate failed');
$check=static function($condition,$label) { if (!$condition) throw new RuntimeException($label); };
$p=new WC_Product_Simple();
$p->set_name('Synthetic tax and coupon product');
$p->set_status('publish'); $p->set_regular_price('40'); $p->set_price('40');
$p->set_tax_status('taxable');
$id=$p->save();
$c=new WC_Coupon(); $c->set_code('aicc-p5-native-five'); $c->set_discount_type('fixed_cart'); $c->set_amount('5'); $c->save();
$check(wc_get_coupon_id_by_code('aicc-p5-native-five')>0,'coupon persist failed');
$order=wc_create_order(['created_via'=>'aicc-p5-compatible']);
$order->set_currency('USD'); $order->add_product(wc_get_product($id),2);
$order->apply_coupon('aicc-p5-native-five');
$order->calculate_totals();
$check(abs((float)$order->get_subtotal()-80.0)<0.001,'subtotal changed');
$check(abs((float)$order->get_discount_total()-5.0)<0.001,'native coupon discount');
$check(abs((float)$order->get_total()-75.0)<0.001,'native coupon total');
echo 'P5_NATIVE_COUPON_USD_PASS subtotal=80.00 discount=5.00 total=75.00'.PHP_EOL;
$zone=new WC_Shipping_Zone(); $zone->set_zone_name('Synthetic zone US'); $zone->set_zone_order(9); $zid=$zone->save();
$check((int)$zid>0,'shipping zone persist failed');
$zone->add_location('US','country');
$zone->add_shipping_method('flat_rate');
$reloaded=new WC_Shipping_Zone($zid);
$check(count($reloaded->get_shipping_methods())===1,'shipping method mismatch');
echo 'P5_NATIVE_SHIPPING_ZONE_PASS'.PHP_EOL;
$variable=new WC_Product_Variable(); $variable->set_name('Synthetic variation parent'); $variable->set_status('publish');
$parent=$variable->save();
$v=new WC_Product_Variation(); $v->set_parent_id($parent); $v->set_regular_price('29.99'); $v->set_price('29.99'); $vid=$v->save();
$check((int)$vid>0 && abs((float)wc_get_product($vid)->get_price()-29.99)<0.001,'variation mismatch');
echo 'P5_NATIVE_VARIATION_PRICE_PASS'.PHP_EOL;
$check(get_option('andy_commerce_m7_inactive_drafts_v1',[])===[],'campaign default not OFF');
echo 'P5_NATIVE_COMPATIBILITY_PASS; NO_GATEWAY=TRUE'.PHP_EOL;
