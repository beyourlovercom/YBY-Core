<?php
declare(strict_types=1);
define('ABSPATH','/');
class MiniCart {
    public function __construct(private array $rows) {}
    public function get_cart(){return $this->rows;}
}
require __DIR__.'/../includes/class-byl-bf2026-r10-country-gate.php';
$n=0;
function ck($bool,$note){global $n;if(!$bool)throw new RuntimeException('FAIL '.$note);$n++;}
$active=['state'=>'active','entries'=>[
 101=>['phase'=>'applied'],102=>['phase'=>'applied'],
 103=>['phase'=>'prepared'],104=>['phase'=>'restored']
]];
$cart=new MiniCart([['product_id'=>101,'quantity'=>1]]);
ck(BYL_BF2026_R10_Country_Gate::inspect($cart,'US',$active)['ok'],'US allowed');
ck(!BYL_BF2026_R10_Country_Gate::inspect($cart,'CA',$active)['ok'],'CA denied');
ck(!BYL_BF2026_R10_Country_Gate::inspect($cart,'GB',$active)['ok'],'GB denied');
ck(BYL_BF2026_R10_Country_Gate::inspect($cart,'',$active)['reason']==='shipping_country_required','unknown country blocked');
ck(BYL_BF2026_R10_Country_Gate::inspect($cart,'us',$active)['ok'],'case folded');
ck(BYL_BF2026_R10_Country_Gate::inspect($cart,'CA',['state'=>'restored','entries'=>$active['entries']])['ok'],'inactive no block');
ck(BYL_BF2026_R10_Country_Gate::inspect(new MiniCart([['product_id'=>999]]),'CA',$active)['ok'],'regular item global');
ck(BYL_BF2026_R10_Country_Gate::inspect(new MiniCart([['product_id'=>103]]),'CA',$active)['ok'],'prepared not active');
ck(BYL_BF2026_R10_Country_Gate::inspect(new MiniCart([['product_id'=>104]]),'CA',$active)['ok'],'restored item not active');
ck(!BYL_BF2026_R10_Country_Gate::inspect(new MiniCart([['product_id'=>999,'variation_id'=>102]]),'CA',$active)['ok'],'sale variation blocked');
$gift=new MiniCart([['product_id'=>101,'andy_campaign_pack_id'=>'byl-black-friday-2026','_byl_bf2026_gift'=>true]]);
ck(BYL_BF2026_R10_Country_Gate::inspect($gift,'CA',$active)['ok'],'gift uses separate international warehouse logic');
ck(!BYL_BF2026_R10_Country_Gate::inspect(null,'US',$active)['ok'],'missing cart fail closed');
ck(!BYL_BF2026_R10_Country_Gate::inspect(new MiniCart(['bad']),'US',$active)['ok'],'malformed cart fail closed');
class BYL_BF2026_R10_Sale_Manager {
    public static function ledger(){return $GLOBALS['active_ledger'];}
}
class FakeErrors {
    public array $messages=[];
    public function add($code,$msg){$this->messages[$code]=$msg;}
}
function WC(){return $GLOBALS['woo'];}
$GLOBALS['active_ledger']=$active;
$GLOBALS['woo']=(object)['cart'=>$cart];
$errors=new FakeErrors();
BYL_BF2026_R10_Country_Gate::validate_classic(['shipping_country'=>'CA'],$errors);
ck(count($errors->messages)===1,'Classic CA blocks before payment');
$errors=new FakeErrors();
BYL_BF2026_R10_Country_Gate::validate_classic(['shipping_country'=>'US'],$errors);
ck(!$errors->messages,'Classic US passes');
$errors=new FakeErrors();
BYL_BF2026_R10_Country_Gate::validate_classic([], $errors);
ck(count($errors->messages)===1,'Classic missing shipping country blocks');
$orderGB=new class {public function get_shipping_country(){return 'GB';}};
$errors=new FakeErrors();
BYL_BF2026_R10_Country_Gate::validate_store_api($orderGB,$errors);
ck(count($errors->messages)===1,'Store API GB blocks');
$orderUS=new class {public function get_shipping_country(){return 'US';}};
$errors=new FakeErrors();
BYL_BF2026_R10_Country_Gate::validate_store_api($orderUS,$errors);
ck(!$errors->messages,'Store API US passes');
$GLOBALS['woo']->cart=new MiniCart([['product_id'=>999]]);
$errors=new FakeErrors();
BYL_BF2026_R10_Country_Gate::validate_store_api($orderGB,$errors);
ck(!$errors->messages,'international ordinary item unaffected');
echo 'BF2026_R10_COUNTRY_GATE_FIXTURE_PASS='.$n.PHP_EOL;
