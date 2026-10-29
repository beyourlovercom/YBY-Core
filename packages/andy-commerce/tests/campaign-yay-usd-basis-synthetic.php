<?php
/**
 * No WordPress DB, no external HTTP and no payments. Pure synthetic interface
 * tests of the YayCurrency → source-USD campaign basis adapter.
 *
 * This test DOES NOT establish live YayCommerce/Woo compatibility.
 */
define('ABSPATH','/tmp/mock-wordpress/');
$GLOBALS['currency']='USD';
$GLOBALS['coupons']=[];
$GLOBALS['coupon_amount']='20.00';
$GLOBALS['percent_coupon']=false;
$GLOBALS['price_mismatch']=false;
$GLOBALS['dual_engine']=false;
$GLOBALS['rates']=[
    'USD'=>1.0,'EUR'=>0.90,'GBP'=>0.77,'AUD'=>1.50,'CAD'=>1.35
];
function get_option($k,$fallback='') {
    if($k==='woocommerce_currency') return 'USD';
    if($k==='woocommerce_prices_include_tax')return 'no';
    return $fallback;
}
function get_woocommerce_currency(){return $GLOBALS['currency'];}
function wc_get_price_decimals(){return 2;}
function has_filter($hook){return $hook==='yay_currency_convert_price'?10:false;}
function apply_filters($hook,$value){
    if($hook!=='yay_currency_convert_price')return $value;
    return number_format(round((float)$value*$GLOBALS['rates'][$GLOBALS['currency']],2),2,'.','');
}
function is_plugin_active($slug){return $slug==='yaycurrency/yaycurrency.php';}
class Andy_Commerce_Currency_Runtime_Dev_V1{
    public static function allowed(){return $GLOBALS['dual_engine'];}
}
class WC_Product{
    public function __construct(private string $usd){}
    public function get_price($context='view'){return $this->usd;}
}
class WC_Coupon{
    public function __construct(private string $code){}
    public function get_id(){return $this->code==='fixed20'||$this->code==='percent10'?7:0;}
    public function get_discount_type($ctx='edit'){return $this->code==='fixed20'?'fixed_cart':'percent';}
    public function get_product_ids($ctx='edit'){return [];}
    public function get_excluded_product_ids($ctx='edit'){return [];}
    public function get_product_categories($ctx='edit'){return [];}
    public function get_excluded_product_categories($ctx='edit'){return [];}
    public function get_exclude_sale_items($ctx='edit'){return false;}
    public function get_limit_usage_to_x_items($ctx='edit'){return null;}
    public function get_amount($ctx='edit'){return $GLOBALS['coupon_amount'];}
}
class WC_Cart{
    public function __construct(private array $lines,private array $coupons=[]){}
    public function get_cart(){return $this->lines;}
    public function get_applied_coupons(){return $this->coupons;}
    public function get_coupon_discount_amount($code,$ex_tax=true){
        return number_format((float)apply_filters('yay_currency_convert_price',$GLOBALS['coupon_amount']),2,'.','');
    }
}
require dirname(__DIR__).'/includes/class-andy-commerce-campaign-yay-usd-basis-v1.php';
function assert_true($condition,$message){
    if(!$condition)throw new RuntimeException('YAY_USD_BASIS_TEST_FAIL '.$message);
}
function merch(string $usd,int $qty=1):array{
    $u=round((float)$usd*$GLOBALS['rates'][$GLOBALS['currency']],2);
    return ['data'=>new WC_Product($usd),'quantity'=>$qty,
        'line_subtotal'=>number_format($u*$qty,2,'.',''),
        'line_total'=>number_format($u*$qty,2,'.','')];
}
function fixedCart(string $usd):WC_Cart {
    $p=merch($usd);
    $p['line_total']=number_format(max(0,(float)$p['line_subtotal']-(float)apply_filters('yay_currency_convert_price',$GLOBALS['coupon_amount'])),2,'.','');
    return new WC_Cart([$p],['fixed20']);
}
$passes=0;
foreach(['USD','EUR','GBP','AUD','CAD'] as $currency){
    $GLOBALS['currency']=$currency;
    foreach([['198.99',19899,1],['199.00',19900,2],['199.01',19901,2]] as [$usd,$expected,$slots]){
        $r=Andy_Commerce_Campaign_Yay_USD_Basis_V1::quote(new WC_Cart([merch($usd)]));
        assert_true($r['ok']&&$r['basis_minor']===$expected&&$r['gift_slots']===$slots,$currency.' raw '.$usd);
        $passes++;
    }
    foreach([['218.99',19899,1],['219.00',19900,2],['219.01',19901,2]] as [$usd,$expected,$slots]){
        $r=Andy_Commerce_Campaign_Yay_USD_Basis_V1::quote(fixedCart($usd));
        assert_true($r['ok']&&$r['basis_minor']===$expected&&$r['gift_slots']===$slots,$currency.' fixed cart '.$usd.':'.json_encode($r));
        $passes++;
    }
    $gift=['andy_campaign_pack_id'=>'byl-black-friday-2026','_byl_bf2026_gift'=>true,
        'data'=>new WC_Product('999.00'),'quantity'=>1,'line_subtotal'=>'0.00','line_total'=>'0.00'];
    $r=Andy_Commerce_Campaign_Yay_USD_Basis_V1::quote(new WC_Cart([merch('198.99'),$gift]));
    assert_true($r['ok']&&$r['gift_slots']===1,$currency.' gift excluded');
    $passes++;
    $upgrade=['andy_campaign_pack_id'=>'byl-black-friday-2026','_byl_bf2026_upgrade'=>true,
        'data'=>new WC_Product('99.00'),'quantity'=>1,'line_subtotal'=>'50.00','line_total'=>'50.00'];
    $r=Andy_Commerce_Campaign_Yay_USD_Basis_V1::quote(new WC_Cart([merch('198.99'),$upgrade]));
    assert_true($r['ok']&&$r['gift_slots']===1,$currency.' upgrade excluded');
    $passes++;
    $r=Andy_Commerce_Campaign_Yay_USD_Basis_V1::quote(new WC_Cart([merch('219.00')],['percent10']));
    assert_true(!$r['ok']&&$r['reason']==='coupon_type_or_restrictions_not_verified',$currency.' unsupported percent denied');
    $passes++;
    $bad=merch('199.00');$bad['line_subtotal']='1.00';$bad['line_total']='1.00';
    $r=Andy_Commerce_Campaign_Yay_USD_Basis_V1::quote(new WC_Cart([$bad]));
    assert_true(!$r['ok']&&$r['reason']==='native_yay_price_mismatch',$currency.' local mismatch denied');
    $passes++;
}
$GLOBALS['currency']='EUR';$GLOBALS['dual_engine']=true;
$r=Andy_Commerce_Campaign_Yay_USD_Basis_V1::quote(new WC_Cart([merch('199.00')]));
assert_true(!$r['ok']&&$r['reason']==='dual_currency_engine','dual engine denied');
$passes++;
// Zero-value merchandise must never unlock the one-free-gift entitlement.
assert_true(Andy_Commerce_Campaign_Yay_USD_Basis_V1::gift_slots_from_usd_minor(0)===0,
    'zero USD source never grants a gift');
$passes++;
$GLOBALS['currency']='USD';
$GLOBALS['dual_engine']=false;
$r=Andy_Commerce_Campaign_Yay_USD_Basis_V1::quote(new WC_Cart([merch('0.00')]));
assert_true($r['ok'] && $r['basis_minor']===0 && $r['gift_slots']===0,
    'zero-price merchandise never grants a gift even in a valid currency context');
$passes++;
$r=Andy_Commerce_Campaign_Yay_USD_Basis_V1::quote(fixedCart('20.00'));
assert_true($r['ok'] && $r['basis_minor']===0 && $r['gift_slots']===0,
    'full-cart coupon reducing USD eligible merchandise to zero grants no gift');
$passes++;
assert_true($passes===54,'expected 54 checks');
echo 'YAY_USD_BASIS_SYNTHETIC_CONTRACT_PASS='.$passes.PHP_EOL;
