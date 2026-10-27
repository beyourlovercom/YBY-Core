<?php
/** Standalone no-WordPress tests for BF gift boundaries and price mapping. */
declare(strict_types=1);
define('ABSPATH','/');
class WC_Cart {
    public function __construct(private array $items) {}
    public function get_cart(): array { return $this->items; }
}
class WC_Product {
    public float $price=100;
    public function set_price($p): void { $this->price=(float)$p; }
}
function get_woocommerce_currency(): string { return $GLOBALS['c3_currency'] ?? 'USD'; }
function wc_get_price_decimals(): int { return 2; }
function wc_format_decimal($v) { return number_format((float)$v,2,'.',''); }
function apply_filters($name,$value) { return $GLOBALS['gift_catalog'] ?? $value; }
function yes($v,$msg) { if (!$v) throw new RuntimeException('BF_FAIL:'.$msg); }
require dirname(__DIR__).'/includes/class-byl-bf2026-gifts.php';
$GLOBALS['gift_catalog']=array(1001=>array('upgrade_minor'=>0),1002=>array('upgrade_minor'=>999),1003=>array('upgrade_minor'=>-10),1004=>array('upgrade_minor'=>100001));
$catalog=BYL_BF2026_Gifts::catalog();
yes(count($catalog)===2 && $catalog[1001]===0 && $catalog[1002]===999,'catalog validates prices');
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
$low=BYL_BF2026_Gifts::eligibility(new WC_Cart([['product_id'=>10,'quantity'=>1,'line_total'=>185.0],$premium]));
yes($low['basis_minor']===18500 && $low['slots']===1,'coupon drops below threshold');
$edge=BYL_BF2026_Gifts::eligibility(new WC_Cart([['product_id'=>10,'line_total'=>199.0]]));
yes($edge['slots']===2,'exact 199 threshold');
$empty=BYL_BF2026_Gifts::eligibility(new WC_Cart([$free]));
yes($empty['slots']===0,'gift-only cart no reward');
$GLOBALS['c3_currency']='EUR';
yes(BYL_BF2026_Gifts::eligibility($c)['slots']===0,'foreign currency fail closed');
echo "BYL_BF2026_PURE_CONTRACT_PASS=10\n";
