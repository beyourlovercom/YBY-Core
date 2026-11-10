<?php
declare(strict_types=1);
define('ABSPATH','/');
$GLOBALS['pages']=[
 'holiday/black-friday'=>(object)['ID'=>33288,'post_status'=>'private','post_content'=>'FROZEN DESIGN'],
 'deals/clearance'=>(object)['ID'=>42276,'post_status'=>'private','post_content'=>'[products category=clearance]'],
 'holiday/gift'=>(object)['ID'=>42280,'post_status'=>'private','post_content'=>'PRIVATE GIFT CATALOG'],
];
$GLOBALS['terms']=[];
$GLOBALS['assignments']=[];
$GLOBALS['mockSkus']=[101=>'SALE-101',102=>'SALE-102',11=>'FREE-11',12=>'FREE-12',13=>'GIFT-13'];
class MockGiftProduct {
    public function __construct(private int $id){}
    public function get_sku(){return $GLOBALS['mockSkus'][$this->id];}
    public function is_type($type){return $type==='simple';}
    public function is_purchasable(){return true;}
    public function is_in_stock(){return true;}
}
function wc_get_product($id){return isset($GLOBALS['mockSkus'][$id])?new MockGiftProduct($id):false;}
function is_wp_error($x){return false;}
function get_page_by_path($p){return $GLOBALS['pages'][$p]??null;}
function get_term_by($by,$slug,$taxonomy){return $GLOBALS['terms'][$slug]??false;}
function wp_insert_term($name,$tax,$args){
    $id=count($GLOBALS['terms'])+1;
    $GLOBALS['terms'][$args['slug']]=(object)['term_id'=>$id,'parent'=>$args['parent']??0];
    return ['term_id'=>$id];
}
function wp_set_object_terms($id,$terms,$tax,$append){
    foreach($terms as $term){$GLOBALS['assignments'][$id][$term]=true;}
    return $terms;
}
function wp_insert_post($details,$return_error=false){throw new RuntimeException('Must not create new pages');}
require __DIR__.'/../includes/class-byl-bf2026-r10-catalog.php';
$n=0;
function yes($value,$name){global $n;if(!$value)throw new RuntimeException('FAIL '.$name);++$n;}
$rows=[
 ['product_id'=>11,'sku'=>'FREE-11','mode'=>'random_free','upgrade_minor'=>0,'approved'=>true,'warehouse_verified'=>true,'allowed_countries'=>['US','CA']],
 ['product_id'=>12,'sku'=>'FREE-12','mode'=>'random_free','upgrade_minor'=>0,'approved'=>true,'warehouse_verified'=>true,'allowed_countries'=>['US']],
 ['product_id'=>13,'sku'=>'GIFT-13','mode'=>'selected_upgrade','upgrade_minor'=>1000,'approved'=>true,'warehouse_verified'=>true,'allowed_countries'=>['US']],
];
$g=BYL_BF2026_R10_Catalog::validate_gifts($rows);
yes($g['ok']===true,'validated gifts');
yes(count($g['random_free'])===2,'free and paid never mixed');
yes(count($g['premium'])===1,'single premium candidate');
$bad=$rows;$bad[0]['approved']=false;
yes(!BYL_BF2026_R10_Catalog::validate_gifts($bad)['ok'],'unapproved item blocked');
$bad=$rows;$bad[1]['allowed_countries']=[];
yes(!BYL_BF2026_R10_Catalog::validate_gifts($bad)['ok'],'missing country blocked');
$bad=$rows;$bad[2]['upgrade_minor']=999;
yes(!BYL_BF2026_R10_Catalog::validate_gifts($bad)['ok'],'unapproved tier blocked');
$bad=$rows;$bad[2]['allowed_countries']=['GB'];
yes(!BYL_BF2026_R10_Catalog::validate_gifts($bad)['ok'],'paid upgrade US only');
$bad=$rows;$bad[1]['product_id']=11;
yes(!BYL_BF2026_R10_Catalog::validate_gifts($bad)['ok'],'duplicate id blocked');
$routes=BYL_BF2026_R10_Catalog::audit_routes();
yes($routes['landing_id']===33288,'real legacy landing ID');
yes($routes['clearance_ready']===true,'clearance private accepted');
yes($routes['gift_ready']===true,'gift private accepted');
$pages=$GLOBALS['pages'];
$bad=$rows;$bad[2]['sku']='WRONG-SKU';
$x=BYL_BF2026_R10_Catalog::prepare_catalog([101,102],$bad);
yes($x['ok']===false && $x['reason']==='gift_identity_or_woo_stock_mismatch','exact Woo SKU required');
yes(!$GLOBALS['terms'] && !$GLOBALS['assignments'],'invalid SKU preflight no writes');
$GLOBALS['pages']['holiday/gift']->post_status='draft';
$x=BYL_BF2026_R10_Catalog::prepare_catalog([101,102],$rows);
yes($x['ok']===false && $x['reason']==='canonical_routes_missing_or_inactive','draft gift page rejected');
$GLOBALS['pages']['holiday/gift']->post_status='publish';
$GLOBALS['pages']['deals/clearance']->post_status='publish';
$routes=BYL_BF2026_R10_Catalog::audit_routes();
yes($routes['clearance_ready'] && $routes['gift_ready'],'public routes accepted');
$run=BYL_BF2026_R10_Catalog::prepare_catalog([101,102],$rows);
yes($run['ok']===true,'canonical catalog prepared');
yes($run['no_page_writes']===true,'no page mutation');
yes($run['pages_verified']['holiday/black-friday']===33288,'landing preserved');
yes($run['pages_verified']['deals/clearance']===true,'canonical clearance path');
yes($run['pages_verified']['holiday/gift']===true,'canonical gift path');
yes(count($GLOBALS['pages'])===count($pages),'no new pages');
yes($GLOBALS['pages']['holiday/black-friday']->post_content==='FROZEN DESIGN','original design untouched');
yes($GLOBALS['terms']['gift']->parent===0,'gift root category');
yes($GLOBALS['terms']['gift-free']->parent===$GLOBALS['terms']['gift']->term_id,'free child category');
yes($GLOBALS['terms']['gift-upgrade']->parent===$GLOBALS['terms']['gift']->term_id,'upgrade child category');
yes(count($GLOBALS['assignments'][101])===1,'sale assigned only clearance');
yes(count($GLOBALS['assignments'][102])===1,'other sale assigned only clearance');
yes(count($GLOBALS['assignments'][11])===2,'free gift root and child');
yes(count($GLOBALS['assignments'][13])===2,'upgrade root and child');
yes(isset($GLOBALS['assignments'][11][$GLOBALS['terms']['gift-free']->term_id]),'free subterm assignment');
yes(isset($GLOBALS['assignments'][13][$GLOBALS['terms']['gift-upgrade']->term_id]),'upgrade subterm assignment');
$again=BYL_BF2026_R10_Catalog::prepare_catalog([101,102],$rows);
yes($again['ok']===true && count($GLOBALS['terms'])===4,'idempotent term lookup');
yes(count($GLOBALS['pages'])===count($pages),'idempotent pages');
echo 'BF2026_R12_PUBLIC_CATALOG_FIXTURE_PASS='.$n.PHP_EOL;
