<?php
declare(strict_types=1);
define('ABSPATH','/sandbox/unit/');
$GLOBALS['m11_currency']='USD';
$GLOBALS['m11_decimals']=2;
function get_woocommerce_currency(): string {return $GLOBALS['m11_currency'];}
function wc_get_price_decimals(): int {return $GLOBALS['m11_decimals'];}
class WC_Cart {
    public function __construct(public array $fixture) {}
    public function get_cart(): array {return $this->fixture['cart'];}
    public function get_applied_coupons(): array {return $this->fixture['coupons'];}
    public function get_fee_total() {return $this->fixture['fee'];}
    public function get_cart_contents_total() {return $this->fixture['net'];}
    public function get_shipping_total() {return $this->fixture['shipping'];}
    public function get_total_tax() {return $this->fixture['tax'];}
    public function get_total($mode='view') {return $this->fixture['total'];}
    public function get_shipping_packages(): array {return $this->fixture['packages'];}
}
class WC_Shipping_Zones {
    public static function get_zone_matching_package(array $package): object {
        return new class($package['destination']['country']) {
            public function __construct(private string $country) {}
            public function get_id(): int {return ['US'=>1,'CA'=>2,'GB'=>3,'DE'=>4,'AU'=>5][$this->country]??0;}
        };
    }
}
require_once dirname(__DIR__).'/includes/class-andy-commerce-m11-wc-cart-reader.php';
require_once dirname(__DIR__).'/includes/class-andy-commerce-c3-cart-snapshot-adapter.php';
$n=0;
function passed(bool $v,string $name): void {global $n;$n++;if(!$v)throw new RuntimeException('FAIL '.$name);}
function rejects(callable $callback,string $name): void {try {$callback();throw new RuntimeException('unexpected PASS '.$name);}catch(InvalidArgumentException $e) {passed(true,$name);}}
$fixture=[
'cart'=>['abc'=>['quantity'=>1,'line_subtotal'=>120,'line_total'=>95]],
'coupons'=>['aicc-p5-only-25'],'fee'=>0,'net'=>95,'shipping'=>10,'tax'=>8.4,'total'=>113.4,
'packages'=>[['destination'=>['country'=>'US']]]
];
$policy=['abc'=>['eligible_for_promotion'=>true,'special_shipping'=>false,'free_gift'=>false]];
$GLOBALS["m11_policy"]=$policy;
$get=static fn(array $f,?array $p=null)=>Andy_Commerce_M11_WC_Cart_Reader::read(new WC_Cart($f),$p ?? $GLOBALS["m11_policy"],9900);
$out=$get($fixture);
passed($out['shipping_zone_id']===1,'WC zone');
passed($out['native_merchandise_net_usd_minor']===9500,'native discounted lines');
passed($out['native_payable_usd_minor']===11340,'Woo payable');
passed($out['snapshot']['shipping_usd_minor']===1000,'native shipping');
passed($out['snapshot']['tax_usd_minor']===840,'native tax');
passed($out['snapshot']['verified_woo_data']===true,'real verified flag');
passed(count($out['applied_native_coupon_codes'])===1,'one original coupon');
passed($out['mode']==='READ_ONLY_NEVER_EXECUTE_WOO','shadow only');
foreach(['USD'=>1000000,'EUR'=>900000,'GBP'=>780000,'AUD'=>1500000,'CAD'=>1350000] as $currency=>$rate){
 $s=Andy_Commerce_C3_Cart_Snapshot_Adapter::quote_request($out['snapshot'],$currency,$rate,'DEV_SYNTHETIC',1792746000);
 passed($s['lines'][0]['discount_usd_minor']===2500,'USD coupon allocation '.$currency);
 passed($s['lines'][0]['eligible_for_promotion'],'native eligible '.$currency);
}
foreach(['US'=>1,'CA'=>2,'GB'=>3,'DE'=>4,'AU'=>5,'JP'=>0] as $country=>$id){
 $f=$fixture;$f['packages'][0]['destination']['country']=$country;
 passed($get($f)['shipping_zone_id']===$id,'Woo Zone '.$country);
}
rejects(fn()=>$get($fixture,[]),'missing classification');
$f=$fixture;$f['coupons']=['a','b'];rejects(fn()=>$get($f),'multi native coupon');
$f=$fixture;$f['fee']=1;rejects(fn()=>$get($f),'unverified Woo fee');
$f=$fixture;$f['total']=113.3;rejects(fn()=>$get($f),'unreconciled cart');
$f=$fixture;$f['cart']['abc']['quantity']=3;$f['cart']['abc']['line_subtotal']=120.01;rejects(fn()=>$get($f),'per-unit remainder');
$f=$fixture;$f['packages'][]=$f['packages'][0];rejects(fn()=>$get($f),'multiple shipping packages');
$GLOBALS['m11_currency']='EUR';rejects(fn()=>$get($fixture),'real non-USD Woo base');$GLOBALS['m11_currency']='USD';
$GLOBALS['m11_decimals']=3;rejects(fn()=>$get($fixture),'precision');$GLOBALS['m11_decimals']=2;
echo "PASS M11 authoritative read-only Woo cart snapshot: $n assertions; no Woo writes\n";
