<?php
declare(strict_types=1);
define('ABSPATH','/');
define('BYL_BF2026_R17_TEST_MODE',true);
class WooCommerce {}
$GLOBALS['r17_options']=[];
$GLOBALS['r17_products']=[];
$GLOBALS['r17_events']=[];
$GLOBALS['r17_checks']=0;
$GLOBALS['r17_currency']='USD';
function get_option($k,$d=false){return $GLOBALS['r17_options'][$k]??$d;}
function update_option($k,$v,$autoload=false){$GLOBALS['r17_options'][$k]=$v;return true;}
function delete_option($k){unset($GLOBALS['r17_options'][$k]);return true;}
function add_option($k,$v,$deprecated='',$autoload=false){
 if(array_key_exists($k,$GLOBALS['r17_options']))return false;
 $GLOBALS['r17_options'][$k]=$v;return true;
}
function get_woocommerce_currency(){return $GLOBALS['r17_currency'];}
function wc_get_product($id){return $GLOBALS['r17_products'][$id]??false;}
function wp_next_scheduled($hook){return $GLOBALS['r17_events'][$hook]??false;}
function wp_schedule_single_event($when,$hook){$GLOBALS['r17_events'][$hook]=$when;return true;}
function wp_clear_scheduled_hook($hook){unset($GLOBALS['r17_events'][$hook]);return true;}
function esc_html($x){return htmlspecialchars((string)$x);}
function check17($truth,$msg){if(!$truth)throw new RuntimeException('FAIL: '.$msg);$GLOBALS['r17_checks']++;}
class R17FakeProduct {
 public $id;public $sku;public $reg;public $sale;public $price;public $from=null;public $to=null;public $stock=true;
 function __construct($id,$sku,$reg,$sale,$from=null,$to=null) {
  $this->id=$id;$this->sku=$sku;$this->reg=$reg;$this->sale=$sale;
  $this->price=$sale===''?$reg:$sale;$this->from=$from;$this->to=$to;
 }
 function get_id(){return $this->id;}
 function get_sku(){return $this->sku;}
 function is_type($name){return $name==='simple';}
 function is_purchasable(){return true;}
 function is_in_stock(){return $this->stock;}
 function get_regular_price($ctx='edit'){return $this->reg;}
 function get_sale_price($ctx='edit'){return $this->sale;}
 function get_price($ctx='edit'){return $this->price;}
 function get_date_on_sale_from($ctx='edit'){return $this->from===null?null:new DateTimeImmutable('@'.$this->from);}
 function get_date_on_sale_to($ctx='edit'){return $this->to===null?null:new DateTimeImmutable('@'.$this->to);}
 function set_sale_price($v){$this->sale=(string)$v;}
 function set_date_on_sale_from($v){$this->from=$v;}
 function set_date_on_sale_to($v){$this->to=$v;}
 function save(){$this->price=$this->sale!==''?$this->sale:$this->reg;return $this->id;}
}
require __DIR__.'/../includes/class-byl-bf2026-r17-fixed-price.php';
function reset17() {
 $GLOBALS['r17_products']=[
  9141=>new R17FakeProduct(9141,'TW158','99.00','69.00',111111,222222),
  9118=>new R17FakeProduct(9118,'BX288A shining version','109.00','79.00'),
  9096=>new R17FakeProduct(9096,'BX033B','119.00','69.00'),
  30454=>new R17FakeProduct(30454,'BY489A-Lubes','19.90',''),
  9098=>new R17FakeProduct(9098,'TX223A','69.00',''),
  29799=>new R17FakeProduct(29799,'HX029A','49.00','')
 ];
 $GLOBALS['r17_options']=[];$GLOBALS['r17_events']=[];$GLOBALS['r17_currency']='USD';
}
function snapshot17(){
 $s=[];
 foreach($GLOBALS['r17_products'] as $id=>$p){$s[$id]=[$p->sku,$p->reg,$p->sale,$p->price,$p->from,$p->to];}
 return $s;
}
reset17();
$old=snapshot17();
check17(BYL_BF2026_R17_Fixed_Price::end_utc()===(new DateTimeImmutable('2026-12-01 00:00:00',new DateTimeZone('America/Los_Angeles')))->getTimestamp(),'LA end correct');
check17(!BYL_BF2026_R17_Fixed_Price::ledger(),'default empty');
$apply=BYL_BF2026_R17_Fixed_Price::apply();
check17($apply['ok']===true && $apply['count']===6,'apply exact 4');
$exp=[9141=>'59.00',9118=>'69.00',9096=>'59.00',30454=>'9.90',9098=>'55.00',29799=>'39.00'];
foreach($exp as $id=>$price){check17($GLOBALS['r17_products'][$id]->sale===$price,'sale exact '.$id);}
check17(BYL_BF2026_R17_Fixed_Price::ledger()['state']==='active','ledger active');
check17(count(BYL_BF2026_R17_Fixed_Price::ledger()['items'])===6,'snapshot exactly 6');
check17(count($GLOBALS['r17_events'])===1,'one expiry cron');
check17(BYL_BF2026_R17_Fixed_Price::apply()['state']==='already_active','no second snapshot');
check17($GLOBALS['r17_products'][9141]->from===null,'sale dates cleared');
$restore=BYL_BF2026_R17_Fixed_Price::restore('plugin_deactivation');
check17($restore['ok']===true && $restore['count']===6,'deactivation returns exact six');
check17(snapshot17()===$old,'restored precise original fields');
check17(BYL_BF2026_R17_Fixed_Price::ledger()['state']==='restored','ledger restored');
check17($GLOBALS['r17_products'][9098]->sale==='', 'Anita original sale is blank');
check17($GLOBALS['r17_products'][29799]->sale==='', 'Pulse original sale is blank');
check17($GLOBALS['r17_products'][30454]->sale==='', 'Lubricant original sale is blank');
check17(!count($GLOBALS['r17_events']),'cron removed on restore');
check17(BYL_BF2026_R17_Fixed_Price::restore()['state']==='already_restored','restore idempotence');
$again=BYL_BF2026_R17_Fixed_Price::apply();
check17($again['ok']===true,'second activation reapply allowed');
$restore=BYL_BF2026_R17_Fixed_Price::restore('second_deactivation');
check17($restore['ok']===true && snapshot17()===$old,'second cycle exact restore');
reset17();
$GLOBALS['r17_products'][9118]->sale='78.00';
$GLOBALS['r17_products'][9118]->price='78.00';
$before=snapshot17();
$fail=BYL_BF2026_R17_Fixed_Price::apply();
check17($fail['ok']===false&&$fail['reason']==='unexpected_original_price_9118','fail closed preflight changed existing sale');
check17(snapshot17()===$before,'preflight no partial modification');
reset17();
$GLOBALS['r17_products'][9096]->stock=false;
$fail=BYL_BF2026_R17_Fixed_Price::apply();
check17($fail['ok']===false&&snapshot17()[9141][2]==='69.00','out-of-stock abort before all writes');
reset17();
check17(BYL_BF2026_R17_Fixed_Price::apply()['ok']===true,'conflict cycle apply');
$GLOBALS['r17_products'][9118]->sale='66.00';
$GLOBALS['r17_products'][9118]->price='66.00';
$c=BYL_BF2026_R17_Fixed_Price::restore('conflict');
check17($c['ok']===false && in_array(9118,$c['conflicts'],true),'manual change never overwritten');
check17($GLOBALS['r17_products'][9118]->sale==='66.00','conflicted price unchanged');
check17($GLOBALS['r17_products'][9141]->sale==='69.00','other originals restored');
$GLOBALS['r17_products'][9118]->sale='69.00';$GLOBALS['r17_products'][9118]->price='69.00';
$c=BYL_BF2026_R17_Fixed_Price::restore('resolved');
check17($c['ok']===true && snapshot17()===$old,'resolve and exact restore');
reset17();
$GLOBALS['r17_currency']='EUR';
check17(BYL_BF2026_R17_Fixed_Price::apply()['reason']==='requires_woocommerce_usd','USD guard');
echo 'BF2026_R17_FIXED_PRICE_FIXTURE_PASS='.$GLOBALS['r17_checks'].PHP_EOL;