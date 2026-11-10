<?php
declare(strict_types=1);
define('ABSPATH','/');
$GLOBALS['pages']=['holiday/black-friday'=>(object)['ID'=>100,'post_content'=>'FROZEN DESIGN']];
$GLOBALS['terms']=[];
$GLOBALS['assignments']=[];
class MockGiftProduct {}
function wc_get_product($id){return $id>0?new MockGiftProduct():false;}
function is_wp_error($e){return false;}
function get_page_by_path($path){return $GLOBALS['pages'][$path]??null;}
function get_term_by($by,$value,$taxonomy){return $GLOBALS['terms'][$value]??false;}
function wp_insert_term($name,$tax,$args){
    $id=count($GLOBALS['terms'])+1;
    $GLOBALS['terms'][$args['slug']]=(object)['term_id'=>$id,'parent'=>$args['parent']??0];
    return ['term_id'=>$id];
}
function wp_set_object_terms($id,$term_ids,$tax,$append){
    foreach($term_ids as $term){$GLOBALS['assignments'][$id][$term]=true;}
    return $term_ids;
}
function wp_insert_post($v,$as_error=false){
    $id=200+count($GLOBALS['pages']);
    $parent=($v['post_parent']??0);
    $path=$parent?('deals/'.$v['post_name']):$v['post_name'];
    $GLOBALS['pages'][$path]=(object)[
        'ID'=>$id,'post_status'=>$v['post_status'],'post_content'=>$v['post_content']
    ];
    return $id;
}
require __DIR__.'/../includes/class-byl-bf2026-r10-catalog.php';
$n=0;function yes($v,$name){global $n;if(!$v)throw new RuntimeException('FAIL '.$name);++$n;}
$giftRows=[
 ['product_id'=>11,'sku'=>'FREE-11','mode'=>'random_free','upgrade_minor'=>0,
     'approved'=>true,'warehouse_verified'=>true,'allowed_countries'=>['US','CA']],
 ['product_id'=>12,'sku'=>'FREE-12','mode'=>'random_free','upgrade_minor'=>0,
     'approved'=>true,'warehouse_verified'=>true,'allowed_countries'=>['US']],
 ['product_id'=>13,'sku'=>'GIFT-13','mode'=>'selected_upgrade','upgrade_minor'=>1000,
     'approved'=>true,'warehouse_verified'=>true,'allowed_countries'=>['US']],
];
$r=BYL_BF2026_R10_Catalog::validate_gifts($giftRows);
yes($r['ok']===true,'approved free and paid modes');
yes(count($r['random_free'])===2,'only two free choices');
yes(count($r['premium'])===1,'premium separate');
yes(count($r['gift_category_product_ids'])===3,'both modes in gift term');
$bad=$giftRows;$bad[0]['approved']=false;
yes(!BYL_BF2026_R10_Catalog::validate_gifts($bad)['ok'],'no unapproved gift');
$bad=$giftRows;$bad[0]['upgrade_minor']=10;
yes(!BYL_BF2026_R10_Catalog::validate_gifts($bad)['ok'],'free must cost zero');
$bad=$giftRows;$bad[2]['upgrade_minor']=999;
yes(!BYL_BF2026_R10_Catalog::validate_gifts($bad)['ok'],'premium tier strict');
$bad=$giftRows;$bad[2]['allowed_countries']=['GB'];
yes(!BYL_BF2026_R10_Catalog::validate_gifts($bad)['ok'],'premium US only');
$bad=$giftRows;$bad[1]['mode']='selected_upgrade';$bad[1]['upgrade_minor']=1000;
yes(!BYL_BF2026_R10_Catalog::validate_gifts($bad)['ok'],'need two free');
$bad=$giftRows;$bad[1]['product_id']=11;
yes(!BYL_BF2026_R10_Catalog::validate_gifts($bad)['ok'],'duplicate gift');
$a=BYL_BF2026_R10_Catalog::audit_routes();
yes($a['landing_exists'] && $a['landing_id']===100,'existing design discovered');
yes(!$a['clearance_page_exists'] && !$a['gift_page_exists'],'missing catalog routes');
$run=BYL_BF2026_R10_Catalog::prepare_catalog([101,102],$giftRows);
yes($run['ok']===true,'catalog categories planned');
yes($run['landing_unchanged'],'landing preserved flag');
yes($GLOBALS['pages']['holiday/black-friday']->post_content==='FROZEN DESIGN','frozen Elementor untouched');
yes($GLOBALS['pages']['clearance']->post_status==='draft','clearance draft');
yes($GLOBALS['pages']['deals/gift']->post_status==='draft','gift draft');
yes(count($GLOBALS['assignments'][101])===1,'sale category');
yes(count($GLOBALS['assignments'][13])===1,'gift category');
yes($GLOBALS['terms']['gift']->parent===$GLOBALS['terms']['deals']->term_id,'child product term under deals');
yes($run['new_pages_draft_only']===true,'no auto publish');
$prev=$GLOBALS['pages'];$runAgain=BYL_BF2026_R10_Catalog::prepare_catalog([101,102],$giftRows);
yes($runAgain['ok']===true,'idempotent existing routes');
yes(count($prev)===count($GLOBALS['pages']),'no duplicate pages');
yes($GLOBALS['pages']['holiday/black-friday']->post_content==='FROZEN DESIGN','never edit holiday');
echo 'BF2026_R10_CATALOG_FIXTURE_PASS='.$n.PHP_EOL;
