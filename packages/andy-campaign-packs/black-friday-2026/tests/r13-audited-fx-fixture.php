<?php
declare(strict_types=1);
define('ABSPATH','/');
$GLOBALS['usd_cents']=19899;
$GLOBALS['fx_allowed']=true;
$GLOBALS['currency']='GBP';
class WC_Cart {
  public function get_cart():array{return [['product_id'=>9096,'line_total'=>149.08,'quantity'=>1]];}
}
function get_woocommerce_currency(){return $GLOBALS['currency'];}
function wc_get_price_decimals(){return 2;}
class Andy_Commerce_Currency_Runtime_Dev_V1 {
 public static function allowed(){return $GLOBALS['fx_allowed'];}
 public static function selected(){return 'GBP';}
 public static function convert($usd){return number_format((float)$usd*0.74916,2,'.','');}
}
class Andy_Commerce_Campaign_USD_Basis_V1 {
 public static function quote($cart) {return ['ok'=>true,'basis_minor'=>$GLOBALS['usd_cents']];}
}
require __DIR__.'/../includes/class-byl-bf2026-r9-policy.php';
require __DIR__.'/../includes/class-byl-bf2026-r9c-woo-adapter.php';
require __DIR__.'/../includes/class-byl-bf2026-r13-cart-writer.php';
$n=0;
function ok($v,$label){global $n;if(!$v)throw new RuntimeException('FAIL_'.$label);++$n;}
$c=new WC_Cart();
$lower=BYL_BF2026_R9C_Woo_Adapter::quote($c,'US');
ok($lower['ok']&&$lower['basis_minor']===19899,'source quote 19899');
ok($lower['entitlement']['gift_slots']===1,'19899 one slot');
$GLOBALS['usd_cents']=19900;
$upper=BYL_BF2026_R9C_Woo_Adapter::quote($c,'US');
ok($upper['ok']&&$upper['basis_minor']===19900,'source quote 19900');
ok($upper['entitlement']['gift_slots']===2,'19900 two slots');
ok(Andy_Commerce_Currency_Runtime_Dev_V1::convert('198.99')
  ===Andy_Commerce_Currency_Runtime_Dev_V1::convert('199.00'),
  'visible GBP same penny but actual USD different');
$GLOBALS['fx_allowed']=false;
$denied=BYL_BF2026_R9C_Woo_Adapter::quote($c,'US');
ok(!$denied['ok'] && $denied['reason']==='audited_source_usd_basis_unavailable',
  'non-audited FX blocked');
$GLOBALS['fx_allowed']=true;
$GLOBALS['currency']='JPY';
$denied=BYL_BF2026_R9C_Woo_Adapter::quote($c,'US');
ok(!$denied['ok'],'unverified currency blocked');
$GLOBALS['currency']='GBP';
$GLOBALS['usd_cents']=-1;
$denied=BYL_BF2026_R9C_Woo_Adapter::quote($c,'US');
ok(!$denied['ok']&&$denied['reason']==='invalid_audited_source_usd_quote',
  'corrupt source quote blocked');
echo 'BF2026_R13_AUDITED_FX_FIXTURE_PASS='.$n.PHP_EOL;
