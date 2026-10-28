<?php
/** Pure R6 boundary checks: no WordPress DB, orders, Stripe, products or writes. */
declare(strict_types=1);
define('ABSPATH','/');
$GLOBALS['r6_cur']='USD';
$GLOBALS['r6_rate']=['USD'=>1000000,'EUR'=>894610,'GBP'=>749160,'AUD'=>1423900,'CAD'=>1400900];
$GLOBALS['r6_allowed']=true;
$GLOBALS['r6_tax']='no';
class WC_Cart {
    public function __construct(private array $lines,private array $coupons=[]) {}
    public function get_cart(): array { return $this->lines; }
    public function get_applied_coupons(): array { return $this->coupons; }
}
class WC_Product {
    public function __construct(private string $usd) {}
    public function get_price($context='view'): string { return $this->usd; }
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
 ok(Andy_Commerce_Campaign_USD_Basis_V1::quote($oneCoupon)['reason']==='foreign_coupon_unverified',
     $c.' coupon fails closed');
}
$GLOBALS['r6_cur']='GBP';
$rate=$GLOBALS['r6_rate']['GBP'];
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
