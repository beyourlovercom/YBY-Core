<?php
/** Synthetic cart fixtures. NO real order, coupons, stock or payments. */
declare(strict_types=1);
define('ABSPATH','/');
class WC_Cart {
    public function __construct(private array $rows) {}
    public function get_cart(): array { return $this->rows; }
}
class Fake_Product {
    public function __construct(private int $id) {}
    public function is_type($x){return $x==='simple';}
    public function is_purchasable(){return true;}
    public function is_in_stock(){return true;}
    public function backorders_allowed(){return false;}
    public function get_sku(){return 'SKU-'.$this->id;}
    public function get_name(){return 'Synthetic test product';}
}
function wc_get_product($id){return new Fake_Product((int)$id);}
$test_currency='USD';
function get_woocommerce_currency(){global $test_currency;return $test_currency;}
require __DIR__.'/../includes/class-byl-bf2026-r8-readiness.php';
require __DIR__.'/../includes/class-byl-bf2026-r9-policy.php';
require __DIR__.'/../includes/class-byl-bf2026-r9c-woo-adapter.php';
$n=0;
function good(bool $v,string $name):void {global $n;if(!$v)throw new Exception('FAIL '.$name);++$n;}
$prices=[5001=>1000,5002=>1500,5003=>2000,6001=>0,6002=>0];
$approved=[];
foreach($prices as $id=>$price) {
    $approved[$id]=[
        'approved'=>true,'warehouse_available_qty'=>15,
        'landed_cost_usd_minor'=>300,'packed_weight_g'=>120,
        'warehouse_code'=>'us-moreplus',
        'warehouse_checked_at'=>time()-120,
        'inventory_evidence_kind'=>'provider_verified',
        'reviewed_by'=>'synthetic_test_only',
        'allowed_countries'=>['US']
    ];
}
function base_cart($sum): WC_Cart{return new WC_Cart(['normal'=>['product_id'=>900,'quantity'=>1,'line_total'=>(string)$sum]]);}
function gift($id,$price):array{return [
    'product_id'=>$id,'quantity'=>1,'line_total'=>(string)($price/100),
    'andy_campaign_pack_id'=>'byl-black-friday-2026','_byl_bf2026_gift'=>true
];}
foreach([0,0.01,98.99,99,198.99,199,250] as $sum) {
    foreach(['US','CA','TW'] as $country) {
        $q=BYL_BF2026_R9C_Woo_Adapter::quote(base_cart($sum),$country);
        good($q['ok'] === true,'quote '.$sum.$country);
        good($q['entitlement']['premium_allowed']===($sum>=99&&$country==='US'),'premium '.$sum.$country);
        good($q['entitlement']['gift_slots']===($sum<=0?0:($sum>=199?2:1)),'slots '.$sum.$country);
    }
}
$cart=base_cart(99);
$p=BYL_BF2026_R9C_Woo_Adapter::plan_premium($cart,'US',5003,$prices,$approved);
good($p['ok'] && $p['action']==='add','US $99 add');
good($p['upgrade_minor']===2000,'server price +20');
good($p['cart_item_data']['_byl_bf2026_paid_slot']===true,'signed paid marker plan');
good(BYL_BF2026_R9C_Woo_Adapter::plan_premium(base_cart(98.99),'US',5003,$prices,$approved)['ok']===false,'under 99');
good(BYL_BF2026_R9C_Woo_Adapter::plan_premium(base_cart(199),'CA',5003,$prices,$approved)['ok']===false,'non US');
$two=new WC_Cart(['normal'=>['product_id'=>900,'line_total'=>'199','quantity'=>1],
    'first'=>gift(6001,0),'second'=>gift(6002,0)]);
$p=BYL_BF2026_R9C_Woo_Adapter::plan_premium($two,'US',5003,$prices,$approved);
good($p['ok'] && $p['action']==='replace','2 gifts replace one');
good($p['remove_key']==='first','keep second random');
$withPaid=new WC_Cart(['normal'=>['product_id'=>900,'line_total'=>'199','quantity'=>1],
    'first'=>gift(5001,10),'second'=>gift(6002,0)]);
$p=BYL_BF2026_R9C_Woo_Adapter::plan_premium($withPaid,'US',5003,$prices,$approved);
good($p['ok'] && $p['remove_key']==='first','switch paid without removing random');
$p=BYL_BF2026_R9C_Woo_Adapter::plan_premium($withPaid,'US',5001,$prices,$approved);
good($p['ok'] && $p['action']==='already_selected','repeat idempotent');
$badPaidFirst=new WC_Cart(['normal'=>['product_id'=>900,'line_total'=>'199'],
    'first'=>gift(5001,10),'second'=>gift(5002,15)]);
good(BYL_BF2026_R9C_Woo_Adapter::plan_premium(
    $badPaidFirst,'US',5001,$prices,$approved)['ok']===false,
    'do not allow early idempotence to hide a second premium');
$bad=new WC_Cart(['normal'=>['product_id'=>900,'line_total'=>'199'],
    'first'=>gift(5001,10),'second'=>gift(5002,15)]);
good(BYL_BF2026_R9C_Woo_Adapter::plan_premium($bad,'US',5003,$prices,$approved)['ok']===false,'2 paid rejected');
$bad2=new WC_Cart(['normal'=>['product_id'=>900,'line_total'=>'199'],
    'first'=>gift(6001,0),'second'=>gift(6001,0)]);
good(BYL_BF2026_R9C_Woo_Adapter::plan_premium($bad2,'US',5003,$prices,$approved)['ok']===false,'duplicate gift rejected');
$cartWithFee=new WC_Cart(['normal'=>['product_id'=>900,'line_total'=>'98.99'],
    'premium'=>gift(5001,10)]);
$q=BYL_BF2026_R9C_Woo_Adapter::quote($cartWithFee,'US');
good($q['basis_minor']===9899,'upgrade cannot unlock itself');
good($q['entitlement']['premium_allowed']===false,'discounted below threshold revokes');
$without=array_diff_key($approved,[5003=>true]);
good(BYL_BF2026_R9C_Woo_Adapter::plan_premium(base_cart(150),'US',5003,$prices,$without)['ok']===false,'missing owner approval');
$stale=$approved;$stale[5003]['warehouse_checked_at']=time()-86500;
good(BYL_BF2026_R9C_Woo_Adapter::plan_premium(base_cart(150),'US',5003,$prices,$stale)['ok']===false,'stale provider stock');
$badCountry=$approved;$badCountry[5003]['allowed_countries']=['CA'];
good(BYL_BF2026_R9C_Woo_Adapter::plan_premium(base_cart(150),'US',5003,$prices,$badCountry)['ok']===false,'US missing in approval');
$test_currency='EUR';
good(BYL_BF2026_R9C_Woo_Adapter::quote(base_cart(150),'US')['ok']===false,'FX source unknown fail closed');
$test_currency='USD';
good(BYL_BF2026_R9C_Woo_Adapter::quote(new WC_Cart(['bad'=>['line_total'=>'-1']]),'US')['ok']===false,'negative amount');
good(BYL_BF2026_R9C_Woo_Adapter::quote(new WC_Cart(['bad'=>['line_total'=>'nope']]),'US')['ok']===false,'malformed amount');
class Synthetic_Coupon {public function __construct(private int $id){} public function get_id(){return $this->id;}}
function discount_row($id,$variation=0,$giftFlag=false){return (object)['object'=>[
    'product_id'=>$id,'variation_id'=>$variation,
    'andy_campaign_pack_id'=>$giftFlag?'byl-black-friday-2026':'',
    '_byl_bf2026_gift'=>$giftFlag
]];}
$items=[discount_row(101),discount_row(201),discount_row(900,202),discount_row(5003,0,true)];
$eligible=BYL_BF2026_R9C_Woo_Adapter::influencer_discount_items($items,new Synthetic_Coupon(555),[555],[201,202]);
good(count($eligible)===1,'coupon only regular item');
good($eligible[0]->object['product_id']===101,'regular item retained');
good(count(BYL_BF2026_R9C_Woo_Adapter::influencer_discount_items($items,new Synthetic_Coupon(556),[555],[201,202]))===4,'other coupon untouched');
good(count(BYL_BF2026_R9C_Woo_Adapter::influencer_discount_items([discount_row(201)],new Synthetic_Coupon(555),[555],[201]))===0,'only BF items no discount');
echo 'BF2026_R9C_NATIVE_ADAPTER_FIXTURE_PASS='.$n.PHP_EOL;
