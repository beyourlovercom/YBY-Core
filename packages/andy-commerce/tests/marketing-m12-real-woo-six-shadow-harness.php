<?php
/** M12 true Woo-shaped cart into M10 six-type shadow; mutation methods throw. */
declare(strict_types=1);
define('ABSPATH','/sandbox/m12/');
function get_woocommerce_currency(): string {return $GLOBALS['m12_currency']??'USD';}
function wc_get_price_decimals(): int {return 2;}
class WC_Product { public function exists(): bool {return true;} }
class WC_Cart {
    public function __construct(public array $f) {}
    public function get_cart(): array {return $this->f['cart'];}
    public function get_applied_coupons(): array {return $this->f['coupons'];}
    public function get_fee_total() {return $this->f['fee'];}
    public function get_cart_contents_total() {return $this->f['net'];}
    public function get_shipping_total() {return $this->f['shipping'];}
    public function get_total_tax() {return $this->f['tax'];}
    public function get_total($mode='view') {return $this->f['total'];}
    public function get_shipping_packages(): array {return $this->f['packages'];}
    public function apply_coupon() {throw new RuntimeException('WOO_MUTATION_NOT_ALLOWED');}
    public function calculate_totals() {throw new RuntimeException('WOO_MUTATION_NOT_ALLOWED');}
    public function add_fee() {throw new RuntimeException('WOO_MUTATION_NOT_ALLOWED');}
}
class WC_Shipping_Zones {
    public static function get_zone_matching_package(array $p): object {
        return new class($p['destination']['country']){
            public function __construct(private string $country){}
            public function get_id(): int {return ['US'=>1,'CA'=>2,'GB'=>3,'DE'=>4,'AU'=>5][$this->country]??0;}
        };
    }
}
class M12_Customer {
    public function __construct(public string $country='US'){}
    public function get_shipping_country(): string {return $this->country;}
}
function WC(): object {return $GLOBALS['m12_woo'];}
$GLOBALS['m12_woo']=(object)['customer'=>new M12_Customer()];
require_once dirname(__DIR__).'/includes/class-andy-commerce-currency-os-v1.php';
require_once dirname(__DIR__).'/includes/class-andy-commerce-m12-woo-six-shadow.php';
$n=0;
function ok(bool $v,string $label):void {global $n;$n++;if(!$v)throw new RuntimeException('M12_FAIL '.$label);}
function rejects(callable $fn,string $label):void {
    try{$fn();throw new RuntimeException('M12_UNEXPECTED_ACCEPT '.$label);}
    catch (InvalidArgumentException $e) {ok(true,$label);}
}
$cart=[
    'cart'=>[
        'key101'=>['product_id'=>101,'data'=>new WC_Product(),'quantity'=>2,'line_subtotal'=>100,'line_total'=>80],
        'key202'=>['product_id'=>202,'data'=>new WC_Product(),'quantity'=>1,'line_subtotal'=>50,'line_total'=>45],
        'key303'=>['product_id'=>303,'data'=>new WC_Product(),'quantity'=>1,'line_subtotal'=>60,'line_total'=>60],
    ],
    'coupons'=>['FIRST25'],'fee'=>0,'net'=>185,'shipping'=>10,'tax'=>8,'total'=>203,
    'packages'=>[['destination'=>['country'=>'US']]],
];
$flags=['eligible_for_promotion'=>true,'special_shipping'=>false,'free_gift'=>false];
$classification=['key101'=>$flags,'key202'=>$flags,'key303'=>$flags];
$policy=['start_date'=>'2026-11-01','end_date'=>'2026-11-30','product_scope'=>'all',
    'product_ids'=>[],'stack_policy'=>'best_single'];
$mk=static fn(string $id,string $type,array $rule):array=>[
    'id'=>$id,'type'=>$type,'priority'=>20,'rule'=>$rule,'lifecycle'=>'draft',
    'currency'=>'USD','countries'=>['US'],'active'=>false,'status'=>'draft','policy'=>$policy,
];
$drafts=[
    $mk('fixed','spend_save',['threshold_minor'=>6900,'discount_minor'=>1200]),
    $mk('percent','spend_save',['threshold_minor'=>6900,'percent_bp'=>1000]),
    $mk('gift','free_gift',['threshold_minor'=>9900,'product_id'=>404]),
    $mk('bogo','bogo',['buy_product_id'=>101,'buy_qty'=>2,'get_product_id'=>202,'get_qty'=>1,'percent_bp'=>5000]),
    $mk('bundle','bundles',['product_ids'=>[101,303],'discount_minor'=>2300]),
    $mk('tier','tiered',['tiers'=>[['minimum_qty'=>2,'percent_bp'=>1000],['minimum_qty'=>4,'percent_bp'=>2000]]]),
];
$expected=['fixed'=>1200,'percent'=>1850,'gift'=>0,'bogo'=>2250,'bundle'=>2300,'tier'=>3700];
foreach ($drafts as $d){
    $r=Andy_Commerce_M12_Woo_Six_Shadow::preview(new WC_Cart($cart),$classification,9900,[$d],'2026-11-15');
    $sim=$r['simulation'];
    ok($sim['projected_discount_usd_minor']===$expected[$d['id']],$d['id'].' projection');
    ok($sim['evaluations'][0]['eligible']===true,$d['id'].' eligible');
    ok($r['native_woo']['actual_payable_usd_minor']===20300,$d['id'].' native price');
    ok($r['native_woo']['eligible_usd_minor']===18500,$d['id'].' native net');
    ok($r['runtime_enabled']===false&&$r['real_checkout_enabled']===false
       &&$r['native_woo_mutations']===0&&$sim['actual_discount_applied']===false
       &&$sim['actual_gift_awarded']===false,$d['id'].' zero mutations');
    ok($sim['input_provenance']==='WOO_NATIVE_READ_ONLY',$d['id'].' provenance');
}
$r=Andy_Commerce_M12_Woo_Six_Shadow::preview(new WC_Cart($cart),$classification,9900,$drafts,'2026-11-15');
ok($r['simulation']['selected_discount_id']==='tier','best of fixed/percent/gift/bogo/bundle/tier');
ok(count($r['simulation']['qualified_gifts'])===1,'gift stock unverified');
$exclusive=$drafts[0];$exclusive['policy']['stack_policy']='coupon_exclusive';
$r=Andy_Commerce_M12_Woo_Six_Shadow::preview(new WC_Cart($cart),$classification,9900,[$exclusive],'2026-11-15');
ok($r['simulation']['evaluations'][0]['reason']==='coupon_conflict','native coupon conflict');
$exclude=$classification;$exclude['key303']=['eligible_for_promotion'=>false,'special_shipping'=>true,'free_gift'=>false];
$r=Andy_Commerce_M12_Woo_Six_Shadow::preview(new WC_Cart($cart),$exclude,9900,$drafts,'2026-11-15');
ok($r['native_woo']['eligible_usd_minor']===12500,'special line excluded');
ok($r['native_woo']['merchandise_net_usd_minor']===18500,'special still in native total');
ok($r['simulation']['qualified_before_campaign_usd_minor']===12500,'sim baseline excl special');
$bad=$classification;unset($bad['key303']);rejects(fn()=>Andy_Commerce_M12_Woo_Six_Shadow::preview(new WC_Cart($cart),$bad,9900,$drafts,'2026-11-15'),'missing classification');
$badCart=$cart;$badCart['coupons']=['A','B'];rejects(fn()=>Andy_Commerce_M12_Woo_Six_Shadow::preview(new WC_Cart($badCart),$classification,9900,$drafts,'2026-11-15'),'multiple coupons');
$badCart=$cart;$badCart['fee']=1;rejects(fn()=>Andy_Commerce_M12_Woo_Six_Shadow::preview(new WC_Cart($badCart),$classification,9900,$drafts,'2026-11-15'),'unknown fees');
$badCart=$cart;$badCart['total']=204;rejects(fn()=>Andy_Commerce_M12_Woo_Six_Shadow::preview(new WC_Cart($badCart),$classification,9900,$drafts,'2026-11-15'),'native mismatch');
$GLOBALS['m12_currency']='EUR';
rejects(fn()=>Andy_Commerce_M12_Woo_Six_Shadow::preview(new WC_Cart($cart),$classification,9900,$drafts,'2026-11-15'),'foreign store base');
$GLOBALS['m12_currency']='USD';
$GLOBALS['m12_woo']->customer->country='';
rejects(fn()=>Andy_Commerce_M12_Woo_Six_Shadow::preview(new WC_Cart($cart),$classification,9900,$drafts,'2026-11-15'),'untrusted shipping country');
echo "M12_NATIVE_WOO_SIX_SHADOW_PASS=$n NO_WOO_MUTATION\n";
