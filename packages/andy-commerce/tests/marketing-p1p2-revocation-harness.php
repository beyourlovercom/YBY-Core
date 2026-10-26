<?php
/**
 * M-P1/M-P2 pure Woo-shaped revocation, provenance and manual-coupon checks.
 * No WP DB, network, real shop hooks or gateway; runtime stays uninstalled.
 */
declare(strict_types=1);
define('ABSPATH','/tmp/marketing-p1p2-harness/');
define('DB_NAME','aicc_commerce_p5_20261023');
define('AICC_MARKETING_RUNTIME_SANDBOX',true);
define('WP_HTTP_BLOCK_EXTERNAL',true);
define('DISABLE_WP_CRON',true);
$harnessHome='http://127.0.0.1:18973';
$settings=[];
$hooks=[];
function home_url(string $path=''):string {global $harnessHome;return $harnessHome.$path;}
function wp_get_environment_type():string{return 'local';}
function get_woocommerce_currency():string{return 'USD';}
function wc_get_price_decimals():int{return 2;}
function get_option(string $key,$default=null) {global $settings;return $settings[$key]??$default;}
function add_filter($name,$callback,$priority=10,$arity=1):void {global $hooks;$hooks[]=$name;}
function add_action($name,$callback,$priority=10,$arity=1):void {global $hooks;$hooks[]=$name;}
class WooCommerce {}
class WC_Coupon {
    public function __construct(private string $code){}
    public function get_code():string{return $this->code;}
}
class WC_Product {
    public string $price='10';
    public function set_price($p):void{$this->price=(string)$p;}
}
class WC_Cart {
    public array $couponCodes=[];
    public array $lines=[];
    public int $recalculations=0;
    public function get_cart():array{return $this->lines;}
    public function get_applied_coupons():array{return $this->couponCodes;}
    public function has_discount($code):bool{return in_array($code,$this->couponCodes,true);}
    public function remove_coupon($code):void{
        $this->couponCodes=array_values(array_filter($this->couponCodes,fn($x)=>$x!==$code));
    }
    public function calculate_totals():void{$this->recalculations++;}
    public function remove_cart_item($key):void{unset($this->lines[$key]);}
}
class P2_Test_Session {
    private array $values=[];
    public function get($key){return $this->values[$key]??null;}
    public function set($key,$value):void{$this->values[$key]=$value;}
}
$wcContext=(object)['cart'=>new WC_Cart(),'session'=>new P2_Test_Session()];
function WC(){global $wcContext;return $wcContext;}
require_once dirname(__DIR__).'/includes/class-andy-commerce-marketing-p2-woo-adapter.php';

$assertions=0;
$ok=static function(bool $value,string $description)use(&$assertions):void {
    if(!$value)throw new RuntimeException('MARKETING_P1P2_FAIL '.$description);
    $assertions++;
};
$contract=Andy_Commerce_Marketing_P1_Sandbox_Contract::class;
$adapter=Andy_Commerce_Marketing_P2_Woo_Adapter::class;
$draft=[
    'id'=>'campaign_demo_01','revision'=>2,'name'=>'Sample',
    'type'=>'spend_save','rule'=>['threshold_minor'=>6900,'discount_minor'=>500],
    'policy'=>['start_date'=>'','end_date'=>'','product_scope'=>'all','product_ids'=>[],
       'stack_policy'=>'best_single'],
    'countries'=>['US'],'priority'=>50,'currency'=>'USD',
    'lifecycle'=>'scheduled','active'=>false,'status'=>'draft'
];
$settings[Andy_Commerce_M7_Draft_Studio::OPTION]=[$draft['id']=>$draft];
$approved=$contract::prepare($draft);
$settings[$contract::APPROVAL_OPTION]=$approved;
$settings[$contract::SWITCH_OPTION]='no';
$ok($contract::current()===null,'default off');
$adapter::boot();
$ok(in_array('woocommerce_coupon_is_valid',$hooks,true),
    'revocation coupon validation hooks still present when OFF');
$ok(in_array('woocommerce_after_calculate_totals',$hooks,true),
    'cart cleanup hooks still present when OFF');
$settings[$contract::SWITCH_OPTION]='yes';
$ok(($contract::current()['id']??null)===$draft['id'],'matching approved snapshot');
$wrong=$draft;$wrong['revision']=3;
$settings[Andy_Commerce_M7_Draft_Studio::OPTION][$draft['id']]=$wrong;
$ok($contract::current()===null,'draft revision change invalidates approved snapshot');
$settings[Andy_Commerce_M7_Draft_Studio::OPTION][$draft['id']]=$draft;
$settings[$contract::APPROVAL_OPTION]['fingerprint']=str_repeat('0',64);
$ok($contract::current()===null,'tampered approval invalidates');
$settings[$contract::APPROVAL_OPTION]=$approved;
$harnessHome='https://dev.beyourlover.com';
$ok(!$contract::isolated() && $contract::current()===null,'Dev URL cannot activate sandbox');
$harnessHome='http://127.0.0.1:18973';

$autoCode='aicc-p2-'.$draft['id'];
$wcContext->session->set('andy_commerce_p2_coupon_origin',[
    'code'=>$autoCode,'fingerprint'=>$approved['fingerprint']]);
$wcContext->session->set('andy_commerce_p2_discount_minor',1200);
$ok($adapter::coupon_valid(true,new WC_Coupon($autoCode))===true,
    'owned coupon with matching fingerprint valid');
$ok($adapter::coupon_valid(true,new WC_Coupon('MANUAL'))===true,'manual coupon untouched');
$ok(($adapter::coupon_data(null,$autoCode,null)['amount']??null)==='12.00',
    'signed campaign coupon has exact minor units');
$wcContext->session->set('andy_commerce_p2_coupon_origin',[
    'code'=>$autoCode,'fingerprint'=>str_repeat('0',64)]);
$ok($adapter::coupon_valid(true,new WC_Coupon($autoCode))===false,
    'stale origin rejects existing coupon');
$ok($adapter::coupon_data(null,$autoCode,null)===null,'stale origin has no virtual coupon');
$wcContext->session->set('andy_commerce_p2_coupon_origin',[
    'code'=>$autoCode,'fingerprint'=>$approved['fingerprint']]);

// Turn off approval while a cart still carries a native automatic coupon/gift.
$wcContext->cart->couponCodes=[$autoCode];
$wcContext->cart->lines=['g1'=>['product_id'=>404,'quantity'=>1,
    'andy_commerce_p2_sandbox_gift'=>$approved['fingerprint'],'data'=>new WC_Product()]];
$settings[$contract::SWITCH_OPTION]='no';
$ok($adapter::coupon_valid(true,new WC_Coupon($autoCode))===false,
    'switch OFF rejects existing session coupon');
$adapter::recalculate($wcContext->cart);
$ok($wcContext->cart->couponCodes===[],'switch OFF removes existing auto coupon');
$ok($wcContext->cart->lines===[],'switch OFF removes existing campaign gift');
$ok($wcContext->session->get('andy_commerce_p2_coupon_origin')===null
    &&$wcContext->session->get('andy_commerce_p2_discount_minor')===null,
    'switch OFF clears session coupon and source');

$settings[$contract::SWITCH_OPTION]='yes';
// An existing native coupon wins and removes our stale automatic promotion.
$wcContext->cart->couponCodes=['MANUAL',$autoCode];
$wcContext->cart->lines=['g2'=>['product_id'=>404,'quantity'=>1,
    'andy_commerce_p2_sandbox_gift'=>$approved['fingerprint'],'data'=>new WC_Product()]];
$adapter::recalculate($wcContext->cart);
$ok($wcContext->cart->couponCodes===['MANUAL'],'manual coupon always wins');
$ok($wcContext->cart->lines===[],'manual coupon removes gift');
$ok($adapter::coupon_valid(true,new WC_Coupon($autoCode))===false,
    'manual conflict clears origin');

// Gift repricing requires matching campaign provenance AND exactly 1 quantity.
$gift=$draft;$gift['type']='free_gift';
$gift['rule']=['threshold_minor'=>9900,'product_id'=>404];
$gift['revision']=4;
$settings[Andy_Commerce_M7_Draft_Studio::OPTION][$gift['id']]=$gift;
$settings[$contract::APPROVAL_OPTION]=$contract::prepare($gift);
$giftFingerprint=$settings[$contract::APPROVAL_OPTION]['fingerprint'];
$notOwned=new WC_Product();$valid=new WC_Product();$wrongQty=new WC_Product();
$wcContext->cart->lines=[
    'fake'=>['product_id'=>404,'quantity'=>1,'andy_commerce_p2_sandbox_gift'=>true,'data'=>$notOwned],
    'owned'=>['product_id'=>404,'quantity'=>1,'andy_commerce_p2_sandbox_gift'=>$giftFingerprint,'data'=>$valid],
    'excess'=>['product_id'=>404,'quantity'=>2,'andy_commerce_p2_sandbox_gift'=>$giftFingerprint,'data'=>$wrongQty],
];
$adapter::gift_zero_price($wcContext->cart);
$ok($notOwned->price==='10','unowned gift cannot become free');
$ok($valid->price==='0','owned one-unit gift is zero priced');
$ok($wrongQty->price==='10','gift quantity tamper is not free');

echo "MARKETING_P1P2_SOURCE_HARNESS_PASS=$assertions; DEFAULT_OFF=YES; REVOCATION=YES; MANUAL_PRIORITY=YES; GIFT_PROVENANCE=YES\n";
