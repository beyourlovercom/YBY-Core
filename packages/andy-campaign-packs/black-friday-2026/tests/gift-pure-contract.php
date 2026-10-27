<?php
/** Standalone no-WordPress tests for BF gift boundaries and price mapping. */
declare(strict_types=1);
define('ABSPATH','/');
class WC_Cart {
    public function __construct(private array $items) {}
    public function get_cart(): array { return $this->items; }
    public function remove_cart_item($key): void { unset($this->items[$key]); }
    public function calculate_totals(): void {}
}
class WooCommerce {}
class WC_Product {
    public float $price=100;
    public function __construct(public string $type='simple',public bool $stock=true) {}
    public function set_price($p): void { $this->price=(float)$p; }
    public function is_type($name): bool { return $name===$this->type; }
    public function is_purchasable(): bool { return $this->type==='simple'; }
    public function is_in_stock(): bool { return $this->stock; }
    public function get_stock_status(): string { return $this->stock ? 'instock' : 'outofstock'; }
    public bool $backorders = false;
    public int $qty = 10;
    public function backorders_allowed(): bool { return $this->backorders; }
    public function managing_stock(): bool { return true; }
    public function get_stock_quantity(): int { return $this->qty; }
}
function wc_get_product($id) { return $GLOBALS['products'][(int)$id]??false; }
function get_woocommerce_currency(): string { return $GLOBALS['c3_currency'] ?? 'USD'; }
function wc_get_price_decimals(): int { return 2; }
function wc_format_decimal($v) { return number_format((float)$v,2,'.',''); }
function apply_filters($name,$value) { return $GLOBALS['gift_catalog'] ?? $value; }
function yes($v,$msg) { if (!$v) throw new RuntimeException('BF_FAIL:'.$msg); }
require dirname(__DIR__).'/includes/class-byl-bf2026-gifts.php';
$GLOBALS['gift_catalog']=array(1001=>array('upgrade_minor'=>0),1002=>array('upgrade_minor'=>999),1003=>array('upgrade_minor'=>-10),1004=>array('upgrade_minor'=>100001));
$catalog=BYL_BF2026_Gifts::catalog();
yes(count($catalog)===2 && $catalog[1001]===0 && $catalog[1002]===999,'catalog validates prices');
$GLOBALS['products']=array(1001=>new WC_Product(),1002=>new WC_Product());
yes(!BYL_BF2026_Gifts::ready(),'one free item is insufficient for two-free-gift promise');
$GLOBALS['gift_catalog'][1005]=array('upgrade_minor'=>0);
$GLOBALS['products'][1005]=new WC_Product();
yes(BYL_BF2026_Gifts::ready(),'two stocked free gifts permit activation');
$GLOBALS['products'][1005]->stock=false;
yes(!BYL_BF2026_Gifts::ready(),'stockout revokes readiness');
$GLOBALS['products'][1005]->stock=true;
$GLOBALS['products'][1005]->backorders=true;
yes(!BYL_BF2026_Gifts::ready(),'backordered gifts do not satisfy free gift promise');
$GLOBALS['products'][1005]->backorders=false;
$GLOBALS['products'][1005]->qty=0;
yes(!BYL_BF2026_Gifts::ready(),'zero managed stock blocks promotion even with instock flag');
$GLOBALS['products'][1005]->qty=10;
yes(BYL_BF2026_Gifts::ready(),'physical available free gift stock restored');
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
$GLOBALS['products'][1002]->stock=false;
$mock = new WC_Cart([$normal,$free,$premium]);
BYL_BF2026_Gifts::reconcile($mock);
yes(count($mock->get_cart())===2,'out-of-stock premium gift removed on cart reconcile');
class BF_Order_Item_Stub {
    public array $meta = [];
    public function add_meta_data($key,$value,$unique): void { $this->meta[$key]=$value; }
}
$item=new BF_Order_Item_Stub();
BYL_BF2026_Gifts::order_line($item,'key',$premium,null);
yes(($item->meta['_andy_campaign_id']??'')===BYL_BF2026_Gifts::ID
    && ($item->meta['_byl_bf2026_upgrade_usd_minor']??0)===999,
    'order-item metadata callback attributes premium gift correctly');
$normalItem=new BF_Order_Item_Stub();
BYL_BF2026_Gifts::order_line($normalItem,'key',$normal,null);
yes($normalItem->meta===array(),'normal merchandise receives no gift metadata');
$_SERVER['REQUEST_METHOD']='POST';
$_POST=array('byl_bf2026_action'=>array('select'));
BYL_BF2026_Gifts::handle_post();
yes(true,'nested action POST ignored without throwing');
unset($_POST,$_SERVER['REQUEST_METHOD']);
$GLOBALS['c3_currency']='EUR';
yes(BYL_BF2026_Gifts::eligibility($c)['slots']===0,'foreign currency fail closed');
echo "BYL_BF2026_PURE_CONTRACT_PASS=20\n";
