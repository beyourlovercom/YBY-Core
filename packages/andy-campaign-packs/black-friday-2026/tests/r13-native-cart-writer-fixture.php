<?php
declare(strict_types=1);
define('ABSPATH','/');
class FakeProduct {
    public function __construct(private int $id,private string $sku,private bool $stock=true){}
    public function get_sku(){return $this->sku;}
    public function is_type($type){return $type==='simple';}
    public function is_purchasable(){return true;}
    public function is_in_stock(){return $this->stock;}
    public function backorders_allowed(){return false;}
}
class WC_Cart {
    private array $lines=[];
    private int $counter=0;
    public bool $failAdd=false;
    public function get_cart(): array {return $this->lines;}
    public function add_to_cart($id,$qty=1,$variation=0,$attrs=[],$meta=[]){
        if($this->failAdd){return false;}
        $p=wc_get_product($id);
        if(!$p){return false;}
        $key='native_'.(++$this->counter);
        $this->lines[$key]=array_merge(['product_id'=>$id,'quantity'=>$qty,
            'line_total'=>0,'data'=>$p],$meta);
        return $key;
    }
    public function remove_cart_item($key){if(!isset($this->lines[$key]))return false;unset($this->lines[$key]);return true;}
    public function calculate_totals():void {
        foreach($this->lines as &$line){
            if(!empty($line['_byl_bf2026_gift'])){
                $line['line_total']=(float)($line['_byl_bf2026_source_usd_minor']??0)/100;
            }
        }
        unset($line);
    }
    public function merchandise(float $usd):void {
        $this->lines['merch']=['product_id'=>9096,'quantity'=>1,'line_total'=>$usd];
    }
}
$GLOBALS['products']=[];
foreach([11,12,13] as $id){$GLOBALS['products'][$id]=new FakeProduct($id,'FREE-'.$id);}
$GLOBALS['products'][14]=new FakeProduct(14,'PAID-14');
function wc_get_product($id){return $GLOBALS['products'][$id]??false;}
function get_woocommerce_currency(){return $GLOBALS['currency']??'USD';}
function wc_get_price_decimals(){return 2;}
require __DIR__.'/../includes/class-byl-bf2026-r9-policy.php';
require __DIR__.'/../includes/class-byl-bf2026-r9c-woo-adapter.php';
require __DIR__.'/../includes/class-byl-bf2026-r12-auto-gift-planner.php';
require __DIR__.'/../includes/class-byl-bf2026-r13-cart-writer.php';
$seed='independent-guest-session-secret-1234567890';
$manifest=[];
foreach([11,12,13] as $id) {
 $manifest[$id]=['product_id'=>$id,'sku'=>'FREE-'.$id,'approved'=>true,
 'warehouse_verified'=>true,'warehouse_slug'=>'us-ny','available_units'=>10,
 'allowed_countries'=>['US','CA'],'upgrade_minor'=>0];
}
$manifest[14]=['product_id'=>14,'sku'=>'PAID-14','approved'=>true,
 'warehouse_verified'=>true,'warehouse_slug'=>'us-la','available_units'=>10,
 'allowed_countries'=>['US'],'upgrade_minor'=>1000];
$n=0;
function ck($yes,$label){global $n;if(!$yes)throw new RuntimeException('FAILED '.$label);++$n;}
function gifts(WC_Cart $cart):array {
 return array_filter($cart->get_cart(),static fn($l)=>!empty($l['_byl_bf2026_gift']));
}
$cart=new WC_Cart();$cart->merchandise(69);
$r=BYL_BF2026_R13_Cart_Writer::commit($cart,'US',$manifest,$seed);
ck($r['ok']&&$r['changed'],'native add 1 gift');
ck(count(gifts($cart))===1,'gift real line');
$first=array_values(gifts($cart))[0];
ck($first['_byl_bf2026_source_usd_minor']===0 && $first['line_total']===0.0,'free truly free');
ck($first['_byl_bf2026_warehouse']==='us-ny','warehouse fulfillment meta');
$r=BYL_BF2026_R13_Cart_Writer::commit($cart,'US',$manifest,$seed);
ck($r['ok']&&!$r['changed'],'recalc idempotent');
ck(count(gifts($cart))===1,'no duplicate free');
$cart->merchandise(217);
$r=BYL_BF2026_R13_Cart_Writer::commit($cart,'US',$manifest,$seed);
ck($r['ok']&&count(gifts($cart))===2,'199 receives second native gift');
ck(count(array_unique(array_column(gifts($cart),'product_id')))===2,'distinct gift SKUs');
$r=BYL_BF2026_R13_Cart_Writer::choose_premium($cart,'US',14,$manifest,$seed);
ck($r['ok']&&$r['changed'],'native paid gift selected');
ck(count(gifts($cart))===2,'paid replaces, does not add 3rd');
ck(count(array_filter(gifts($cart),fn($x)=>!empty($x['_byl_bf2026_paid_slot'])))===1,'exactly one paid');
ck(array_sum(array_column(gifts($cart),'line_total'))===10.0,'paid upgrade exact 10 USD');
$r=BYL_BF2026_R13_Cart_Writer::commit($cart,'US',$manifest,$seed);
ck($r['ok']&&!$r['changed'],'paid+free stable after totals');
$r=BYL_BF2026_R13_Cart_Writer::choose_premium($cart,'US',14,$manifest,$seed);
ck($r['ok']&&!$r['changed'],'retry premium idempotent');
$cart->merchandise(138);
$r=BYL_BF2026_R13_Cart_Writer::commit($cart,'US',$manifest,$seed);
ck($r['ok']&&count(gifts($cart))===1,'drop below199 one slot');
ck(count(array_filter(gifts($cart),fn($x)=>!empty($x['_byl_bf2026_paid_slot'])))===1,'premium retained at138');
$cart->merchandise(69);
$r=BYL_BF2026_R13_Cart_Writer::commit($cart,'US',$manifest,$seed);
ck($r['ok']&&count(gifts($cart))===1,'drop below99 still 1 gift');
ck(count(array_filter(gifts($cart),fn($x)=>!empty($x['_byl_bf2026_paid_slot'])))===0,'paid revoked under99');
$cart->merchandise(217);
BYL_BF2026_R13_Cart_Writer::commit($cart,'US',$manifest,$seed);
BYL_BF2026_R13_Cart_Writer::choose_premium($cart,'US',14,$manifest,$seed);
$r=BYL_BF2026_R13_Cart_Writer::commit($cart,'CA',$manifest,$seed);
ck($r['ok']&&count(gifts($cart))===2,'country change refill');
ck(count(array_filter(gifts($cart),fn($x)=>!empty($x['_byl_bf2026_paid_slot'])))===0,'non-US paid revoked');
$r=BYL_BF2026_R13_Cart_Writer::commit($cart,'CA',$manifest,$seed);
ck($r['ok']&&!$r['changed'],'CA idempotent');
$r=BYL_BF2026_R13_Cart_Writer::choose_premium($cart,'CA',14,$manifest,$seed);
ck(!$r['ok']&&count(gifts($cart))===2,'CA paid blocked');
$oldCount=count(gifts($cart));
$r=BYL_BF2026_R13_Cart_Writer::commit($cart,'',$manifest,$seed);
ck(!$r['ok']&&count(gifts($cart))===$oldCount,'unknown destination no write');
$GLOBALS['currency']='EUR';
$r=BYL_BF2026_R13_Cart_Writer::commit($cart,'US',$manifest,$seed);
ck(!$r['ok']&&count(gifts($cart))===$oldCount,'unverified FX no charge');
$GLOBALS['currency']='USD';
$empty=new WC_Cart();$empty->merchandise(69);$empty->failAdd=true;
$r=BYL_BF2026_R13_Cart_Writer::commit($empty,'US',$manifest,$seed);
ck(!$r['ok']&&count(gifts($empty))===0,'failed add rollback');
$cart->merchandise(0);
$r=BYL_BF2026_R13_Cart_Writer::commit($cart,'US',$manifest,$seed);
ck($r['ok']&&count(gifts($cart))===0,'empty paid merchandise removes all gifts');
echo 'BF2026_R13_NATIVE_CART_WRITER_FIXTURE_PASS='.$n.PHP_EOL;
