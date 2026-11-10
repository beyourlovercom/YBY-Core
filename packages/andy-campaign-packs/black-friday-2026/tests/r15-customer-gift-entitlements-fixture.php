<?php
declare(strict_types=1);
define('ABSPATH','/');
$GLOBALS['canary']=true;
$GLOBALS['currency']='USD';
$GLOBALS['stock']=[9106=>false,9103=>false,17767=>false];
$GLOBALS['products']=[
 9141=>['TW158',69],32685=>['DW01A',49],9106=>['VA560A',49],
 9103=>['VA562A',48],9123=>['VT500A-G',49],
 9095=>['VX007H',79],9096=>['BX033B',69],9117=>['BX288A',69],
 17625=>['CA594B',79],25358=>['VX588A',69],
 14188=>['VX603A',79],9109=>['S106B',79],17767=>['BA564A',69],
 34737=>['VX607A',79],9137=>['TX218Y',59],
];
$GLOBALS['tiers']=[9141=>10,32685=>10,9106=>10,9103=>10,9123=>10,
 9095=>15,9096=>15,9117=>15,17625=>15,25358=>15,
 14188=>20,9109=>20,17767=>20,34737=>20,9137=>20];
class WooCommerce{}
class BYL_BF2026_R14_Canary {
 public static function runtime_allowed(){return $GLOBALS['canary'];}
}
class BYL_BF2026_R13_Cart_Writer {
 public static function campaign_open(){return true;}
 public static function coupon_line($valid,$product,$coupon,$line){return $valid;}
 public static function coupon_items($items,$coupon,$discounts){return $items;}
}
class Product {
 private float $price;
 public function __construct(private int $id) {$this->price=(float)$GLOBALS['products'][$id][1];}
 public function get_sku(){return $GLOBALS['products'][$this->id][0];}
 public function is_type($type){return $type==='simple';}
 public function is_purchasable(){return true;}
 public function is_in_stock(){return $GLOBALS['stock'][$this->id]??true;}
 public function backorders_allowed(){return false;}
 public function set_price($price){$this->price=(float)$price;}
 public function get_price(){return $this->price;}
}
function wc_get_product($id) {return isset($GLOBALS['products'][$id])?new Product($id):false;}
function get_woocommerce_currency(){return $GLOBALS['currency'];}
function has_term($term,$taxonomy,$id) {
 if($taxonomy!=='product_cat'||!isset($GLOBALS['tiers'][$id]))return false;
 return in_array($term,['gift','gift-upgrade','gift-upgrade-'.$GLOBALS['tiers'][$id]],true);
}
class MockCustomer {
 public string $country='US';
 public function get_shipping_country(){return $this->country;}
 public function get_billing_country(){return $this->country;}
}
class WC_Cart {
 private array $rows=[];
 private int $counter=0;
 public bool $failAdd=false;
 public function __construct(){$this->rows['merch']=['product_id'=>200,'quantity'=>1,'line_total'=>0.0];}
 public function setMerch(int $cents){$this->rows['merch']['line_total']=$cents/100;}
 public function get_cart():array {return $this->rows;}
 public function remove_cart_item($key){if(!isset($this->rows[$key]))return false;unset($this->rows[$key]);return true;}
 public function add_to_cart($id,$qty=1,$variation=0,$attrs=[],$meta=[]) {
  if($this->failAdd)return false;
  $key='woocommerce_cart_item_'.(++$this->counter);
  $this->rows[$key]=array_merge([
    'product_id'=>$id,'quantity'=>$qty,'line_total'=>(float)$GLOBALS['products'][$id][1],
    'data'=>wc_get_product($id)
  ],$meta);
  return $key;
 }
 public function calculate_totals(){
  BYL_BF2026_R15_Entitlements::reprice($this);
  foreach($this->rows as &$r){if(!empty($r['_byl_bf2026_paid_slot'])){$r['line_total']=$r['data']->get_price();}}
  unset($r);
  BYL_BF2026_R15_Entitlements::reconcile($this);
 }
}
class MockWC {
 public $cart;
 public $customer;
 public function __construct(){
  $this->cart=new WC_Cart();$this->customer=new MockCustomer();
 }
}
$GLOBALS['wc']=new MockWC();
function WC(){return $GLOBALS['wc'];}
class BYL_BF2026_R9C_Woo_Adapter {
 public static function quote($cart,$country){
  if($country==='' || strlen($country)!==2)return ['ok'=>false,'reason'=>'shipping_country_required'];
  $minor=0;
  foreach($cart->get_cart() as $line){
    if(!empty($line['_byl_bf2026_paid_slot']))continue;
    $minor+=(int)round((float)$line['line_total']*100);
  }
  return ['ok'=>true,'basis_minor'=>$minor,
    'entitlement'=>BYL_BF2026_R9_Policy::eligibility($minor,$country)];
 }
}
require __DIR__.'/../includes/class-byl-bf2026-r9-policy.php';
require __DIR__.'/../includes/class-byl-bf2026-r15-gift-entitlements.php';
$n=0;
function ok($yes,$name){global $n;if(!$yes)throw new RuntimeException('FAILED_'.$name);++$n;}
$policy=static fn($minor,$c)=>BYL_BF2026_R9_Policy::eligibility($minor,$c);
ok($policy(0,'US')['gift_slots']===0,'US_0_NO_GIFT');
ok($policy(1,'US')['gift_slots']===1,'US_POSITIVE_ONE_RANDOM');
ok($policy(6900,'US')['gift_slots']===1,'US_69_ONE_RANDOM');
ok(!$policy(9899,'US')['premium_allowed'],'US_BELOW99_NO_UPGRADE');
ok($policy(9900,'US')['premium_allowed'],'US_99_ONE_UPGRADE');
ok($policy(19900,'US')['gift_slots']===2,'US_199_TWO');
ok($policy(9899,'CA')['gift_slots']===0,'INTL_9899_NO_RANDOM');
ok($policy(9900,'CA')['gift_slots']===1,'INTL_9900_ONE_RANDOM');
ok($policy(19899,'CA')['gift_slots']===1,'INTL_19899_ONE_RANDOM');
ok($policy(19900,'CA')['gift_slots']===2,'INTL_19900_TWO_RANDOM');
ok($policy(19900,'GB')['gift_slots']===2,'INTL_GB_TWO_RANDOM');
ok($policy(9900,'')['gift_slots']===0,'UNKNOWN_COUNTRY_NO_GUESS');
ok(BYL_BF2026_R15_Entitlements::upgrade(9141)['fee_minor']===1000,'HEART_10_TIER');
ok(BYL_BF2026_R15_Entitlements::upgrade(9095)['fee_minor']===1500,'CAMERA_15_TIER');
ok(BYL_BF2026_R15_Entitlements::upgrade(14188)['fee_minor']===2000,'ANGELA_20_TIER');
ok(BYL_BF2026_R15_Entitlements::upgrade(9106)===null,'OOS_CLOUD_BLOCKED');
ok(BYL_BF2026_R15_Entitlements::upgrade(17767)===null,'OOS_WAVEY_BLOCKED');
ok(BYL_BF2026_R15_Entitlements::upgrade(9999)===null,'UNLISTED_PRODUCT_DENIED');
$c=WC()->cart;$u=WC()->customer;
$c->setMerch(6900);
$e=BYL_BF2026_R15_Entitlements::entitlement($c,'US');
ok($e['ok']&&$e['random_gift_qty']===1&&$e['paid_count']===0,'US_69_ONE_ORDER_ENTITLEMENT');
$c->setMerch(13800);
$r=BYL_BF2026_R15_Entitlements::choose_paid($c,'US',9141);
ok($r['ok']&&$r['changed'],'PAID_10_NATIVE_WOO_ADD');
$c->calculate_totals();
$g=array_filter($c->get_cart(),fn($v)=>!empty($v['_byl_bf2026_paid_slot']));
ok(count($g)===1,'EXACT_ONE_PAID_WOO_LINE');
ok(abs((float)array_values($g)[0]['line_total']-10.0)<0.001,'PAID_FEE_EXACT_10');
$e=BYL_BF2026_R15_Entitlements::entitlement($c,'US');
ok($e['random_gift_qty']===0&&$e['paid_count']===1,'PAID_REPLACES_ONE_RANDOM');
$r=BYL_BF2026_R15_Entitlements::choose_paid($c,'US',9095);
ok($r['ok']&&$r['changed'],'SELECT_15_REPLACES_10');
$g=array_filter($c->get_cart(),fn($v)=>!empty($v['_byl_bf2026_paid_slot']));
ok(count($g)===1,'PAID_NO_DUPLICATE');
ok(abs((float)array_values($g)[0]['line_total']-15.0)<0.001,'PAID_FEE_EXACT_15');
$r=BYL_BF2026_R15_Entitlements::choose_paid($c,'US',9095);
ok($r['ok']&&!$r['changed'],'RETRY_CHOICE_IDEMPOTENT');
$c->setMerch(21700);
$c->calculate_totals();
$e=BYL_BF2026_R15_Entitlements::entitlement($c,'US');
ok($e['slots']===2&&$e['random_gift_qty']===1&&$e['paid_count']===1,'US_199_PAID_PLUS_ONE_RANDOM');
$order=new class {
 public array $meta=[];
 public string $country='US';
 public function get_shipping_country(){return $this->country;}
 public function update_meta_data($key,$val){$this->meta[$key]=$val;}
};
BYL_BF2026_R15_Entitlements::order_meta($order);
ok($order->meta['_byl_bf2026_random_gift_qty']===1,'ORDER_RANDOM_META_ONE');
ok($order->meta['_byl_bf2026_paid_gift_product_id']===9095,'ORDER_PAID_PRODUCT_ID');
$u->country='CA';
$c->calculate_totals();
$g=array_filter($c->get_cart(),fn($v)=>!empty($v['_byl_bf2026_paid_slot']));
ok(count($g)===0,'COUNTRY_CHANGE_REMOVES_US_PAID');
$e=BYL_BF2026_R15_Entitlements::entitlement($c,'CA');
ok($e['slots']===2&&$e['random_gift_qty']===2,'CA_199_TWO_RANDOM_NO_SKU_REQUIRED');
$order->country='CA';
BYL_BF2026_R15_Entitlements::order_meta($order);
ok($order->meta['_byl_bf2026_random_gift_qty']===2,'ORDER_CA_RANDOM_META_TWO');
ok($order->meta['_byl_bf2026_paid_gift_product_id']===0,'ORDER_CA_NO_PAID_ID');
$c->setMerch(9899);
$c->calculate_totals();
$e=BYL_BF2026_R15_Entitlements::entitlement($c,'CA');
ok($e['slots']===0&&$e['random_gift_qty']===0,'CA_BELOW99_NO_GIFT');
$r=BYL_BF2026_R15_Entitlements::choose_paid($c,'CA',9141);
ok(!$r['ok'],'CA_PAID_SELECTION_BLOCKED');
$u->country='US';$c->setMerch(9900);
$r=BYL_BF2026_R15_Entitlements::choose_paid($c,'US',9103);
ok(!$r['ok'],'OOS_CLOUD_IPO_NOT_SELECTABLE');
$GLOBALS['canary']=false;
ok(!BYL_BF2026_R15_Entitlements::ready(),'ANONYMOUS_GLOBAL_GIFT_OFF');
echo 'BF2026_R15_WAREHOUSE_AGNOSTIC_ENTITLEMENTS_PASS='.$n.PHP_EOL;
