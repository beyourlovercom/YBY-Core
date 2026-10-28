<?php
/** Pure R6 boundary checks: no WordPress DB, orders, Stripe, products or writes. */
declare(strict_types=1);
define('ABSPATH','/');
$GLOBALS['r6_cur']='USD';
$GLOBALS['r6_rate']=['USD'=>1000000,'EUR'=>894610,'GBP'=>749160,'AUD'=>1423900,'CAD'=>1400900];
$GLOBALS['r6_allowed']=true;
$GLOBALS['r6_tax']='no';
class WC_Cart {
    public function __construct(private array $lines,private array $coupons=[],private array $discounts=[]) {}
    public function get_cart(): array { return $this->lines; }
    public function get_applied_coupons(): array { return $this->coupons; }
    public function get_coupon_discount_amount($code,$ex_tax=true) {
        return $this->discounts[$code]??0;
    }
}
class WC_Product {
    public function __construct(private string $usd) {}
    public function get_price($context='view'): string { return $this->usd; }
}
class WC_Coupon {
    public function __construct(private string $code) {}
    private function item(): array { return $GLOBALS['r6_coupon_data'][$this->code]??[]; }
    public function get_id() { return ($this->item()['id']??0); }
    public function get_discount_type($context='view') { return $this->item()['type']??''; }
    public function get_amount($context='view') { return $this->item()['amount']??'0'; }
    public function get_product_ids($context='view') { return $this->item()['product_ids']??[]; }
    public function get_excluded_product_ids($context='view') { return $this->item()['excluded_products']??[]; }
    public function get_product_categories($context='view') { return $this->item()['product_categories']??[]; }
    public function get_excluded_product_categories($context='view') { return $this->item()['excluded_categories']??[]; }
    public function get_exclude_sale_items($context='view') { return $this->item()['exclude_sale_items']??false; }
    public function get_limit_usage_to_x_items($context='view') { return $this->item()['limit_items']??null; }
}
class Andy_Commerce_Currency_Runtime_Dev_V1 {
    public const CODES=['USD','EUR','GBP','AUD','CAD'];
    public static function site(): bool { return true; }
    public static function rates(): ?array {
        return ['rate_micros'=>$GLOBALS['r6_rate'],'snapshot_hash'=>'r6-synthetic'];
    }
    public static function allowed(): bool { return $GLOBALS['r6_allowed']; }
    public static function selected(): string { return $GLOBALS['r6_cur']; }
}
function get_woocommerce_currency() { return $GLOBALS['r6_cur']; }
function wc_get_price_decimals() { return 2; }
function get_option($key,$default=null) { return $key==='woocommerce_prices_include_tax'?$GLOBALS['r6_tax']:$default; }
function ok($value,$label) {
 if (!$value) throw new RuntimeException('R6_BASIS_FAIL '.$label);
 $GLOBALS['r6_pass']=($GLOBALS['r6_pass']??0)+1;
}
require dirname(__DIR__).'/includes/class-andy-commerce-campaign-usd-basis-v1.php';
$usd=new WC_Cart([['quantity'=>1,'line_total'=>'185.00','data'=>new WC_Product('205.00')]],[ 'native-fixed-cart' ]);
$q=Andy_Commerce_Campaign_USD_Basis_V1::quote($usd);
ok($q['ok']&&$q['basis_minor']===18500&&$q['coupon_count']===1,'USD coupon net Woo line');
$usdGift=new WC_Cart([
 ['quantity'=>1,'line_total'=>'205.00','data'=>new WC_Product('205.00')],
 ['quantity'=>1,'line_total'=>'9.99','data'=>new WC_Product('59.00'),
  'andy_campaign_pack_id'=>'byl-black-friday-2026','_byl_bf2026_gift'=>true]
]);
ok(Andy_Commerce_Campaign_USD_Basis_V1::quote($usdGift)['basis_minor']===20500,'exclude paid gift upgrade');
$unknown=new WC_Cart([['quantity'=>1,'line_total'=>'0.00','andy_campaign_pack_id'=>'alien']]);
ok(Andy_Commerce_Campaign_USD_Basis_V1::quote($unknown)['reason']==='unknown_campaign_line','unknown promo marker fail closed');
$round=static fn(int $usd,int $micros):string => number_format(intdiv($usd*$micros+500000,1000000)/100,2,'.','');
foreach(['EUR','GBP','AUD','CAD'] as $c) {
 $GLOBALS['r6_cur']=$c;
 $fx=$GLOBALS['r6_rate'][$c];
 foreach([19899,19900,20500] as $usdMinor) {
  $amount=$round($usdMinor,$fx);
  $cart=new WC_Cart([['quantity'=>1,'line_total'=>$amount,'data'=>new WC_Product(number_format($usdMinor/100,2,'.',''))]]);
  $q=Andy_Commerce_Campaign_USD_Basis_V1::quote($cart);
  ok($q['ok'] && $q['basis_minor']===$usdMinor &&
     $q['currency']===$c && $q['fx_rate_micros']===$fx,
     $c.' exact source USD cents '.$usdMinor);
 }
 $oneCoupon=new WC_Cart([['quantity'=>1,'line_total'=>$round(18500,$fx),
     'data'=>new WC_Product('205.00')]],['promo20']);
 ok(Andy_Commerce_Campaign_USD_Basis_V1::quote($oneCoupon)['reason']==='foreign_coupon_type_unverified',
     $c.' unknown coupon fails closed');
}
$GLOBALS['r6_coupon_data']=['fix20'=>['id'=>900,'type'=>'fixed_cart','amount'=>'20.00']];
foreach(['EUR','GBP','AUD','CAD'] as $code) {
    $GLOBALS['r6_cur']=$code;
    $rate=$GLOBALS['r6_rate'][$code];
    foreach ([19899,19900,20500] as $netUsd) {
        $baseUsd=$netUsd+2000;
        $localBase=intdiv($baseUsd*$rate+500000,1000000);
        $localCoupon=intdiv(2000*$rate+500000,1000000);
        $localNet=$localBase-$localCoupon;
        $cart=new WC_Cart([
           ['quantity'=>1,'line_subtotal'=>number_format($localBase/100,2,'.',''),
            'line_total'=>number_format($localNet/100,2,'.',''),
            'data'=>new WC_Product(number_format($baseUsd/100,2,'.',''))]
        ],['fix20'],['fix20'=>number_format($localCoupon/100,2,'.','')]);
        $q=Andy_Commerce_Campaign_USD_Basis_V1::quote($cart);
        ok($q['ok'] && $q['basis_minor']===$netUsd &&
           $q['provenance']==='woo_one_line_fixed_cart_source_usd_and_native_coupon_verified',
           $code.' one native fixed-cart coupon USD net '. $netUsd);
    }
    $q=Andy_Commerce_Campaign_USD_Basis_V1::quote(new WC_Cart([
        ['quantity'=>1,'line_subtotal'=>'200.00','line_total'=>'180.00','data'=>new WC_Product('205.00')]
    ], ['fix20'], ['fix20'=>'20.00']));
    ok($q['reason']==='coupon_subtotal_mismatch',
       $code.' subtotal mismatch fail closed');
}
$GLOBALS['r6_cur']='GBP';
$rate=$GLOBALS['r6_rate']['GBP'];
$localBase=intdiv(21900*$rate+500000,1000000);
$localCoupon=intdiv(2000*$rate+500000,1000000);
$makeFixed=static function(array $extra=[],$discountOverride=null,$gift=null)use($localBase,$localCoupon) {
   $line=['quantity'=>1,'line_subtotal'=>number_format($localBase/100,2,'.',''),
      'line_total'=>number_format(($localBase-$localCoupon)/100,2,'.',''),'data'=>new WC_Product('219.00')];
   $rows=[$line];if($gift!==null)$rows[]=$gift;
   return new WC_Cart($rows,['fix20'],['fix20'=>$discountOverride??number_format($localCoupon/100,2,'.','')]);
};
$q=Andy_Commerce_Campaign_USD_Basis_V1::quote($makeFixed([],'14.90'));
ok($q['reason']==='coupon_native_allocation_mismatch','mismatched Woo discount denied');
$GLOBALS['r6_coupon_data']['fix20']['excluded_products']=[12];
ok(Andy_Commerce_Campaign_USD_Basis_V1::quote($makeFixed())['reason']==='foreign_coupon_restrictions_unverified',
   'restricted product coupon denied');
unset($GLOBALS['r6_coupon_data']['fix20']['excluded_products']);
$GLOBALS['r6_coupon_data']['fix20']['type']='percent';
ok(Andy_Commerce_Campaign_USD_Basis_V1::quote($makeFixed())['reason']==='foreign_coupon_type_unverified',
   'percentage coupon not silently treated as fixed cart');
$GLOBALS['r6_coupon_data']['fix20']['type']='fixed_cart';
$gift=['quantity'=>1,'line_subtotal'=>'9.99','line_total'=>'9.00','data'=>new WC_Product('59.00'),
       'andy_campaign_pack_id'=>'byl-black-friday-2026','_byl_bf2026_gift'=>true];
ok(Andy_Commerce_Campaign_USD_Basis_V1::quote($makeFixed([],null,$gift))['reason']==='coupon_discounted_gift',
   'gift discounted by fixed cart coupon fails closed');
$another=['quantity'=>1,'line_subtotal'=>'10.00','line_total'=>'10.00','data'=>new WC_Product('10.00')];
ok(Andy_Commerce_Campaign_USD_Basis_V1::quote($makeFixed([],null,$another))['reason']==='foreign_coupon_cart_shape_unverified',
   'two merchandise lines still fail closed');
$GLOBALS['r6_coupon_data']['fix20']['type']='fixed_cart';

ok($round(19899,$rate)===$round(19900,$rate),
   'GBP local penny collision explicitly reproduced');
$GLOBALS['r6_tax']='yes';
ok(Andy_Commerce_Campaign_USD_Basis_V1::quote(
 new WC_Cart([['quantity'=>1,'line_total'=>$round(20500,$rate),'data'=>new WC_Product('205.00')]])
)['reason']==='tax_inclusive_unverified','inclusive-tax foreign fails closed');
$GLOBALS['r6_tax']='no';
$GLOBALS['r6_allowed']=false;
ok(Andy_Commerce_Campaign_USD_Basis_V1::quote(
 new WC_Cart([['quantity'=>1,'line_total'=>$round(20500,$rate),'data'=>new WC_Product('205.00')]])
)['reason']==='foreign_runtime_not_active','disarmed runtime fails closed');
$GLOBALS['r6_allowed']=true;
ok(Andy_Commerce_Campaign_USD_Basis_V1::quote(
 new WC_Cart([['quantity'=>1,'line_total'=>'999.99','data'=>new WC_Product('205.00')]])
)['reason']==='local_price_mismatch','external or changed price fails closed');
ok(Andy_Commerce_Campaign_USD_Basis_V1::quote(
 new WC_Cart([['quantity'=>0,'line_total'=>'0.00','data'=>new WC_Product('0.00')]])
)['reason']==='quantity_invalid','invalid quantity fails closed');
ok(Andy_Commerce_Campaign_USD_Basis_V1::quote(
 new WC_Cart([['quantity'=>1,'line_total'=>'-1.00','data'=>new WC_Product('205.00')]])
)['reason']==='line_total_invalid','negative line fails closed');
echo 'COMMERCE_CAMPAIGN_USD_BASIS_PASS='.$GLOBALS['r6_pass']."\n";
