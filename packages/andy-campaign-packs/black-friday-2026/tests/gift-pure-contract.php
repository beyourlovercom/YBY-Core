<?php
/** Standalone no-WordPress tests for BF gift boundaries and price mapping. */
declare(strict_types=1);
define('ABSPATH','/');
define('WC_VERSION','11.2.1');
class WC_Cart {
    public static int $calculations = 0;
    public function __construct(private array $items) {}
    public function get_cart(): array { return $this->items; }
    public function remove_cart_item($key): void { unset($this->items[$key]); }
    public function calculate_totals(): void { self::$calculations++; }
}
class WooCommerce {}
class WC_Product {
    public float $price=100;
    public function __construct(public string $type='simple',public bool $stock=true) {}
    public function set_price($p): void { $this->price=(float)$p; }
    public function is_type($name): bool { return $name===$this->type; }
    public function is_purchasable(): bool { return $this->type==='simple'; }
    public function is_in_stock(): bool { return $this->stock; }
    public function get_stock_status(): string { return $this->stock ? 'instock' : 'outofstock'; }
    public bool $backorders = false;
    public int $qty = 10;
    public function backorders_allowed(): bool { return $this->backorders; }
    public function managing_stock(): bool { return true; }
    public function get_stock_quantity(): int { return $this->qty; }
}
function wc_get_product($id) { return $GLOBALS['products'][(int)$id]??false; }
function get_woocommerce_currency(): string { return $GLOBALS['c3_currency'] ?? 'USD'; }
function wc_get_price_decimals(): int { return 2; }
function wc_format_decimal($v) { return number_format((float)$v,2,'.',''); }
function apply_filters($name,$value) { return $GLOBALS['gift_catalog'] ?? $value; }
function yes($v,$msg) { if (!$v) throw new RuntimeException('BF_FAIL:'.$msg); }
require dirname(__DIR__).'/includes/class-byl-bf2026-gifts.php';
$GLOBALS['gift_catalog']=array(1001=>array('upgrade_minor'=>0),1002=>array('upgrade_minor'=>999),1003=>array('upgrade_minor'=>-10),1004=>array('upgrade_minor'=>100001));
$catalog=BYL_BF2026_Gifts::catalog();
yes(count($catalog)===2 && $catalog[1001]===0 && $catalog[1002]===999,'catalog validates prices');
$GLOBALS['products']=array(1001=>new WC_Product(),1002=>new WC_Product());
yes(!BYL_BF2026_Gifts::ready(),'one free item is insufficient for two-free-gift promise');
$GLOBALS['gift_catalog'][1005]=array('upgrade_minor'=>0);
$GLOBALS['products'][1005]=new WC_Product();
yes(BYL_BF2026_Gifts::ready(),'two stocked free gifts permit activation');
$GLOBALS['products'][1005]->stock=false;
yes(!BYL_BF2026_Gifts::ready(),'stockout revokes readiness');
$GLOBALS['products'][1005]->stock=true;
$GLOBALS['products'][1005]->backorders=true;
yes(!BYL_BF2026_Gifts::ready(),'backordered gifts do not satisfy free gift promise');
$GLOBALS['products'][1005]->backorders=false;
$GLOBALS['products'][1005]->qty=0;
yes(!BYL_BF2026_Gifts::ready(),'zero managed stock blocks promotion even with instock flag');
$GLOBALS['products'][1005]->qty=10;
yes(BYL_BF2026_Gifts::ready(),'physical available free gift stock restored');
$normal=['product_id'=>10,'quantity'=>1,'line_total'=>205.0];
$free=['product_id'=>1001,'quantity'=>1,'line_total'=>0.0,'data'=>new WC_Product(),
    'andy_campaign_pack_id'=>BYL_BF2026_Gifts::ID,'_byl_bf2026_gift'=>true];
$premium=['product_id'=>1002,'quantity'=>1,'line_total'=>9.99,'data'=>new WC_Product(),
    'andy_campaign_pack_id'=>BYL_BF2026_Gifts::ID,'_byl_bf2026_gift'=>true];
$c=new WC_Cart([$normal,$free,$premium]);
$e=BYL_BF2026_Gifts::eligibility($c);
yes($e['basis_minor']===20500 && $e['slots']===2,'post-coupon merchandise basis ignores upgrades');
BYL_BF2026_Gifts::reprice($c);
yes($free['data']->price===0.0,'free gift zero');
yes($premium['data']->price===9.99,'premium gift upgrade exact');
yes(BYL_BF2026_Gifts::exclude_gift_coupon(true,null,null,$premium)===false,'gift coupon excluded');
yes(BYL_BF2026_Gifts::exclude_gift_coupon(true,null,null,$normal)===true,'merchandise coupon allowed');
$giftItem = (object) array( 'object' => $premium );
$merchItem = (object) array( 'object' => $normal );
$eligibleItems=BYL_BF2026_Gifts::exclude_gift_discount_items(array($giftItem,$merchItem),null,null);
yes(count($eligibleItems)===1 && $eligibleItems[0] === $merchItem, 'fixed-cart allocation excludes paid gift upgrade');
yes(BYL_BF2026_Gifts::exclude_gift_discount_items('original',null,null)==='original',
    'unrecognized coupon item representation returns unchanged');
$low=BYL_BF2026_Gifts::eligibility(new WC_Cart([['product_id'=>10,'quantity'=>1,'line_total'=>185.0],$premium]));
yes($low['basis_minor']===18500 && $low['slots']===1,'coupon drops below threshold');
$edge=BYL_BF2026_Gifts::eligibility(new WC_Cart([['product_id'=>10,'line_total'=>199.0]]));
yes($edge['slots']===2,'exact 199 threshold');
$empty=BYL_BF2026_Gifts::eligibility(new WC_Cart([$free]));
yes($empty['slots']===0,'gift-only cart no reward');
$GLOBALS['products'][1002]->stock=false;
$mock = new WC_Cart([$normal,$free,$premium]);
BYL_BF2026_Gifts::reconcile($mock);
yes(count($mock->get_cart())===2,'out-of-stock premium gift removed on cart reconcile');
class BF_Order_Item_Stub {
    public array $meta = [];
    public function add_meta_data($key,$value,$unique): void { $this->meta[$key]=$value; }
}
$item=new BF_Order_Item_Stub();
BYL_BF2026_Gifts::order_line($item,'key',$premium,null);
yes(($item->meta['_andy_campaign_id']??'')===BYL_BF2026_Gifts::ID
    && ($item->meta['_byl_bf2026_upgrade_usd_minor']??0)===999,
    'order-item metadata callback attributes premium gift correctly');
$normalItem=new BF_Order_Item_Stub();
BYL_BF2026_Gifts::order_line($normalItem,'key',$normal,null);
yes($normalItem->meta===array(),'normal merchandise receives no gift metadata');
$_SERVER['REQUEST_METHOD']='POST';
$_POST=array('byl_bf2026_action'=>array('select'));
BYL_BF2026_Gifts::handle_post();
yes(true,'nested action POST ignored without throwing');
unset($_POST,$_SERVER['REQUEST_METHOD']);
class WP_Error {
    public array $errors = [];
    public function add($code,$message): void { $this->errors[$code]=$message; }
}
function WC() { return $GLOBALS['bf_woo']; }
$GLOBALS['bf_woo']=(object)array('cart'=>new WC_Cart(array($normal,$premium)));
$invalid=new WP_Error();
BYL_BF2026_Gifts::validate_store_api(null,$invalid);
yes(isset($invalid->errors['byl_bf2026_invalid_gift']),
    'Store API rejects unavailable promotional gift before payment');
$GLOBALS['bf_woo']->cart = new WC_Cart(array($normal,$free));
$valid = new WP_Error();
BYL_BF2026_Gifts::validate_store_api(null,$valid);
yes(count($valid->errors)===0,
    'Store API accepts valid stock and allowance');
$GLOBALS['c3_currency']='EUR';
yes(BYL_BF2026_Gifts::eligibility($c)['slots']===0,'foreign currency fails closed without Commerce quote contract');
class Andy_Commerce_Currency_Runtime_Dev_V1 {
    public static bool $enabled = true;
    public static function allowed(): bool { return self::$enabled; }
    public static function selected(): string { return get_woocommerce_currency(); }
    public static function convert(string $amount): string {
        $rates=['USD'=>1000000,'EUR'=>894610,'GBP'=>749160,'AUD'=>1423900,'CAD'=>1400900];
        $rate=$rates[self::selected()]??1000000;
        return number_format(round((float)$amount*$rate/1000000,2),2,'.','');
    }
}
class Andy_Commerce_Campaign_USD_Basis_V1 {
    public static bool $verified = false;
    public static int $basis = 19900;
    public static function quote($cart): array {
        return self::$verified
            ? ['ok'=>true,'basis_minor'=>self::$basis,'provenance'=>'source-usd']
            : ['ok'=>false,'reason'=>'foreign_coupon_unverified','basis_minor'=>0];
    }
}
Andy_Commerce_Campaign_USD_Basis_V1::$verified=true;
foreach(['EUR','GBP','AUD','CAD'] as $code) {
    $GLOBALS['c3_currency']=$code;
    $e=BYL_BF2026_Gifts::eligibility($c);
    yes($e['slots']===2 && $e['basis_minor']===19900,
        $code.' verified source USD199 qualifies for two gifts');
    Andy_Commerce_Campaign_USD_Basis_V1::$basis=19899;
    yes(BYL_BF2026_Gifts::eligibility($c)['slots']===1,
        $code.' verified source USD198.99 grants one gift');
    Andy_Commerce_Campaign_USD_Basis_V1::$basis=19900;
    Andy_Commerce_Campaign_USD_Basis_V1::$verified=false;
    yes(BYL_BF2026_Gifts::eligibility($c)['slots']===0,
        $code.' failed quote grants zero gifts');
    Andy_Commerce_Campaign_USD_Basis_V1::$verified=true;
}
$GLOBALS['c3_currency']='GBP';
$GLOBALS['products'][1002]->stock=true;
BYL_BF2026_Gifts::reprice($c);
yes(abs($premium['data']->price-7.48)<0.001,
    'GBP premium upgrade converted to local price using Commerce runtime');
Andy_Commerce_Currency_Runtime_Dev_V1::$enabled=false;
yes(BYL_BF2026_Gifts::eligibility($c)['slots']===0,
    'disabled foreign currency runtime blocks Commerce bridge');
Andy_Commerce_Currency_Runtime_Dev_V1::$enabled=true;
$GLOBALS['c3_currency']='JPY';
yes(BYL_BF2026_Gifts::eligibility($c)['slots']===0,
    'unreviewed foreign currency still fails closed without quote');
$GLOBALS['c3_currency']='EUR';
// A real Dev GBP rate can collapse adjacent $198.99/$199.00 thresholds into
// the SAME pence; inverse-FX entitlements therefore require verified base USD.
$dev_gbp_rate_micros=749160;
$gbp_minor=static fn(int $usd) => intdiv($usd*$dev_gbp_rate_micros+500000,1000000);
yes($gbp_minor(19899)===$gbp_minor(19900),
    'GBP quote collision prevents unsafe inverted FX threshold implementation');
class Andy_Campaign_Lite {
    public static function boot() { return new self(); }
    public function decisions(): array { return array(BYL_BF2026_Gifts::ID=>'master_off'); }
}
$before = WC_Cart::$calculations;
$inactive = BYL_BF2026_Gifts::render();
yes(str_contains($inactive,'not currently available') && !str_contains($inactive,'<form'),
    'OFF campaign renders a harmless landing preview without gift selection controls');
yes(WC_Cart::$calculations === $before,
    'OFF shortcode rendering never touches Woo cart calculation');
echo "BYL_BF2026_PURE_CONTRACT_PASS=27\n";
