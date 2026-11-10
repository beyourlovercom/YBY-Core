<?php
declare(strict_types=1);
define('ABSPATH','/');
class WooCommerce {}
$GLOBALS['currency']='USD';
$GLOBALS['options']=[];
$GLOBALS['events']=[];
$GLOBALS['catalog']=[];
$GLOBALS['terms']=[];
function get_option($k,$d=[]){return $GLOBALS['options'][$k]??$d;}
function update_option($k,$v,$autoload=false){$GLOBALS['options'][$k]=$v;return true;}
function add_option($k,$v,$dep='',$auto=false){if(isset($GLOBALS['options'][$k]))return false;$GLOBALS['options'][$k]=$v;return true;}
function delete_option($k){unset($GLOBALS['options'][$k]);}
function get_woocommerce_currency(){return $GLOBALS['currency'];}
function wc_get_price_decimals(){return 2;}
function wc_get_product_id_by_sku($sku){
    foreach($GLOBALS['catalog'] as $id=>$p){if($p->get_sku()===$sku)return $id;}
    return 0;
}
function wc_get_product($id){return $GLOBALS['catalog'][$id]??false;}
function is_wp_error($x){return false;}
function get_term_by($a,$b,$c){return $GLOBALS['terms'][$b]??false;}
function wp_insert_term($name,$tax,$args){$GLOBALS['terms'][$args['slug']]=(object)['term_id'=>99];return ['term_id'=>99];}
function wp_set_object_terms($id,$terms,$tax,$append){return $terms;}
function wp_schedule_single_event($when,$hook){$GLOBALS['events'][]=[$when,$hook];return true;}
class MockProduct {
    public $id;public $sku;public $regular;public $sale;public $price;
    public $from;public $to;public $stock;
    public function __construct($id,$sku,$regular,$sale,$price,$from=null,$to=null,$stock=true){
        $this->id=$id;$this->sku=$sku;$this->regular=$regular;$this->sale=$sale;
        $this->price=$price;$this->from=$from;$this->to=$to;$this->stock=$stock;
    }
    public function get_id(){return $this->id;}
    public function get_sku(){return $this->sku;}
    public function is_type($t){return $t==='simple';}
    public function is_purchasable(){return true;}
    public function is_in_stock(){return $this->stock;}
    public function get_regular_price($view='edit'){return $this->regular;}
    public function get_sale_price($view='edit'){return $this->sale;}
    public function get_price($view='edit'){return $this->price;}
    public function get_date_on_sale_from($view='edit'){return $this->from===null?null:(new DateTimeImmutable('@'.$this->from));}
    public function get_date_on_sale_to($view='edit'){return $this->to===null?null:(new DateTimeImmutable('@'.$this->to));}
    public function set_sale_price($v){$this->sale=(string)$v;}
    public function set_date_on_sale_from($v){$this->from=$v===null?null:(int)$v;}
    public function set_date_on_sale_to($v){$this->to=$v===null?null:(int)$v;}
    public function save(){
        $this->price=$this->sale!==''?$this->sale:$this->regular;
        return $this->id;
    }
}
require __DIR__.'/../includes/class-byl-bf2026-r10-price-policy.php';
require __DIR__.'/../includes/class-byl-bf2026-r10-sale-manager.php';
$N=0;
function check10($a,$name){global $N;if(!$a)throw new RuntimeException('FAIL '.$name);++$N;}
$now=time();
$GLOBALS['catalog']=[
   123=>new MockProduct(123,'SKU-ABC','100.00','75.00','75.00',$now-9000,$now+50000),
   456=>new MockProduct(456,'SKU-DEF','50.00','','50.00'),
   789=>new MockProduct(789,'SKU-OUT','50.00','','50.00',null,null,false)
];
$old123=BYL_BF2026_R10_Price_Policy::snapshot($GLOBALS['catalog'][123]);
$old456=BYL_BF2026_R10_Price_Policy::snapshot($GLOBALS['catalog'][456]);
check10($old123['sale_from_utc']===$now-9000,'snapshot start');
check10($old123['sale_to_utc']===$now+50000,'snapshot expiry');
check10(BYL_BF2026_R10_Price_Policy::plan('100','','100',99)['ok']===false,'reject 99 pct');
check10(BYL_BF2026_R10_Price_Policy::plan('0','','0',15)['ok']===false,'reject zero base');
check10(BYL_BF2026_R10_Price_Policy::plan('100','75','75',15)['campaign_sale_price']==='75.00','never increase existing sale');
check10(BYL_BF2026_R10_Price_Policy::plan('100','75','75',50)['campaign_sale_price']==='50.00','50 pct baseline');
check10(BYL_BF2026_R10_Price_Policy::plan('100','oops','75',20)['ok']===false,'invalid legacy sale');
check10(BYL_BF2026_R10_Price_Policy::plan('100','75','bad',20)['ok']===false,'invalid current');
check10(BYL_BF2026_R10_Sale_Manager::prepare(['SKU-ABC','SKU-ABC'],$now,$now+3600)['ok']===false,'duplicate SKU');
check10(BYL_BF2026_R10_Sale_Manager::prepare(['SKU-ABC','NO-SUCH'],$now,$now+3600)['ok']===false,'missing product');
check10(BYL_BF2026_R10_Sale_Manager::prepare(['SKU-OUT'],$now,$now+3600)['ok']===false,'out of stock');
check10(BYL_BF2026_R10_Sale_Manager::prepare(['SKU-ABC'],$now,$now-1)['ok']===false,'past event');
$p=BYL_BF2026_R10_Sale_Manager::prepare(['SKU-ABC','SKU-DEF'],$now,$now+3600);
check10($p['ok'] && $p['count']===2,'prepare 2');
check10($p['entries'][123]['phase']==='prepared','phase prepared');
check10(in_array($p['entries'][123]['after']['tier_percent'],BYL_BF2026_R10_Price_Policy::PERCENT_TIERS,true),'valid random tier');
check10(BYL_BF2026_R10_Sale_Manager::prepare(['SKU-ABC'],$now,$now+3600)['ok']===false,'no reroll');
check10(BYL_BF2026_R10_Price_Policy::snapshot($GLOBALS['catalog'][123])===$old123,'no mutation on prepare');
$a=BYL_BF2026_R10_Sale_Manager::apply();
check10($a['ok'] && $a['count']===2,'apply 2');
check10(BYL_BF2026_R10_Sale_Manager::ledger()['state']==='active','ledger active');
check10($GLOBALS['catalog'][123]->from===null&&$GLOBALS['catalog'][123]->to===null,'remove conflicting old sale dates');
check10((float)$GLOBALS['catalog'][123]->sale<=75,'never raise original price');
check10(count($GLOBALS['terms'])===1&&isset($GLOBALS['terms']['clearance']),'create clearance category');
check10(count($GLOBALS['events'])===1&&$GLOBALS['events'][0][1]==='byl_bf2026_restore_sale_prices','schedule end');
check10(BYL_BF2026_R10_Sale_Manager::apply()['ok']===false,'no double launch');
$modified=$GLOBALS['catalog'][456];
$modified->sale='14.99';$modified->price='14.99';
$restore=BYL_BF2026_R10_Sale_Manager::restore('manual');
check10($restore['ok']===false && $restore['restored']===1,'one restored one conflict');
check10(count($restore['conflicts'])===1,'conflict recorded');
check10($GLOBALS['catalog'][456]->sale==='14.99','do not overwrite manual edit');
check10(BYL_BF2026_R10_Price_Policy::snapshot($GLOBALS['catalog'][123])['sale_price']===$old123['sale_price'],'original sale restored');
check10($GLOBALS['catalog'][123]->from===$old123['sale_from_utc'],'original sale date restored');
check10($GLOBALS['catalog'][123]->to===$old123['sale_to_utc'],'original expiry restored');
$entry=BYL_BF2026_R10_Sale_Manager::ledger()['entries'][456];
$modified->sale=$entry['after']['campaign_sale_price'];$modified->price=$modified->sale;
$done=BYL_BF2026_R10_Sale_Manager::restore('conflict_resolved');
check10($done['ok']===true && $done['state']==='restored','restoration completed');
check10(BYL_BF2026_R10_Price_Policy::snapshot($GLOBALS['catalog'][456])===$old456,'original no-sale product restored');
check10(BYL_BF2026_R10_Sale_Manager::ledger()['entries'][123]['phase']==='restored','ledger line restored');
check10($GLOBALS['catalog'][123]->sku==='SKU-ABC','SKU intact');
check10(BYL_BF2026_R10_Sale_Manager::restore('duplicate')['ok']===false,'idempotent end');
echo 'BF2026_R10_PRICE_RESTORE_FIXTURE_PASS='.$N.PHP_EOL;
