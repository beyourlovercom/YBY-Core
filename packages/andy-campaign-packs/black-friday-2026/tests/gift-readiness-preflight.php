<?php
define('ABSPATH','/');
final class R8Product {
    public function __construct(private string $sku, private string $name='Gift', private bool $stock=true,private bool $backorder=false){}
    public function get_sku(){return $this->sku;}
    public function get_name(){return $this->name;}
    public function is_type($t){return $t==='simple';}
    public function is_purchasable(){return true;}
    public function is_in_stock(){return $this->stock;}
    public function backorders_allowed(){return $this->backorder;}
}
$GLOBALS['r8_products']=[
  101=>new R8Product('GIFT-A'),
  102=>new R8Product('GIFT-B'),
  103=>new R8Product('UPGRADE-C'),
  40029=>new R8Product('AICC-UAT-MKT-GIFT','UAT gift DO NOT FULFILL'),
  105=>new R8Product('GIFT-OUT','Out',false),
];
function wc_get_product($id){return $GLOBALS['r8_products'][$id]??false;}
require dirname(__DIR__).'/includes/class-byl-bf2026-r8-readiness.php';
function checkR8($v,$msg) {
 if(!$v)throw new RuntimeException('R8_PREFLIGHT_FAIL '.$msg);
 $GLOBALS['r8_checks']=($GLOBALS['r8_checks']??0)+1;
}
$valid=['approved'=>true,'warehouse_available_qty'=>20,'landed_cost_usd_minor'=>800,
        'packed_weight_g'=>250,'allowed_countries'=>['US','GB']];
$cat=[101=>['upgrade_minor'=>0],102=>['upgrade_minor'=>0],
      103=>['upgrade_minor'=>999]];
$q=BYL_BF2026_R8_Readiness::inspect($cat);
checkR8(!$q['release_ready'],'missing signoff never opens promotion');
checkR8($q['free_approved_count']===0,'missing signoff yields zero free approved');
$approvals=[101=>$valid,102=>$valid,103=>$valid];
$q=BYL_BF2026_R8_Readiness::inspect($cat,$approvals);
checkR8($q['release_ready']&&$q['free_approved_count']===2,'two approved free plus premium qualifies');
checkR8(count($q['rows'])===3&&$q['rows'][2]['upgrade_usd_minor']===999,'premium upcharge tracked in USD cents');
$q=BYL_BF2026_R8_Readiness::inspect([101=>['upgrade_minor'=>0]], [101=>$valid]);
checkR8(!$q['release_ready'],'one free SKU never enough');
$q=BYL_BF2026_R8_Readiness::inspect([101=>['upgrade_minor'=>999],102=>['upgrade_minor'=>0]],[101=>$valid,102=>$valid]);
checkR8(!$q['release_ready'],'premium not counted as second free gift');
$q=BYL_BF2026_R8_Readiness::inspect([40029=>['upgrade_minor'=>0],102=>['upgrade_minor'=>0]],[40029=>$valid,102=>$valid]);
checkR8(!$q['release_ready']&&in_array('test_fixture_never_fulfill',$q['rows'][0]['reasons'],true),
        'hidden UAT fixture never approved even with manifest');
$q=BYL_BF2026_R8_Readiness::inspect([105=>['upgrade_minor'=>0],102=>['upgrade_minor'=>0]],[105=>$valid,102=>$valid]);
checkR8(!$q['release_ready'],'out of stock blocked');
$bad=$valid;unset($bad['warehouse_available_qty']);
$q=BYL_BF2026_R8_Readiness::inspect([101=>['upgrade_minor'=>0],102=>['upgrade_minor'=>0]],[101=>$bad,102=>$valid]);
checkR8(!$q['release_ready']&&in_array('warehouse_qty_unverified',$q['rows'][0]['reasons'],true),'no ERP quantity fail closed');
$bad=$valid;unset($bad['packed_weight_g']);
$q=BYL_BF2026_R8_Readiness::inspect([101=>['upgrade_minor'=>0],102=>['upgrade_minor'=>0]],[101=>$bad,102=>$valid]);
checkR8(!$q['release_ready'],'no packaging weight fail closed');
$bad=$valid;unset($bad['landed_cost_usd_minor']);
$q=BYL_BF2026_R8_Readiness::inspect([101=>['upgrade_minor'=>0],102=>['upgrade_minor'=>0]],[101=>$bad,102=>$valid]);
checkR8(!$q['release_ready'],'no landed cost fail closed');
$bad=$valid;$bad['allowed_countries']=[];
$q=BYL_BF2026_R8_Readiness::inspect([101=>['upgrade_minor'=>0],102=>['upgrade_minor'=>0]],[101=>$bad,102=>$valid]);
checkR8(!$q['release_ready'],'no countries fail closed');
$q=BYL_BF2026_R8_Readiness::inspect([101=>['upgrade_minor'=>0],102=>['upgrade_minor'=>'9.99']],[101=>$valid,102=>$valid]);
checkR8(!$q['release_ready']&&$q['rows'][1]['reasons']===['invalid_php_catalog_row'],'noninteger price rejected');
echo 'BYL_BF2026_R8_READINESS_PASS='.$GLOBALS['r8_checks'].PHP_EOL;
