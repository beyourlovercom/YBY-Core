<?php
/**
 * Germany Wholesale #824 — inline signed CSS after cached pageCss failed to surface.
 * Modes: wp eval-file script.php plan --user=1
 *        wp eval-file script.php apply SHA256 --user=1
 * Only Germany target #824 changes; independent private backup and rollback.
 */
if (!defined('ABSPATH')||!defined('WP_CLI'))exit(1);
const YBY_DE_PAGE=824;
const YBY_DE_SOURCE=215;
const YBY_DE_STYLE_ID='yby-wholesale-de-inline-css-v2';
const YBY_DE_CLONE_KEY='wholesale-ads-first-de-v1:215';
function yby_de_error($code,$details){WP_CLI::error($code.': '.$details);}
function yby_de_read(){
  $hostname=strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST));
  if($hostname!=='ybyirrigation.com'||wp_get_environment_type()!=='production')return new WP_Error('HOST','Production only');
  $post=get_post(YBY_DE_PAGE);
  if(!$post||$post->post_type!=='landing_page'||$post->post_status!=='publish'||
     $post->post_name!=='irrigation-wholesale-germany'||
     get_post_meta(YBY_DE_PAGE,'_yby_wholesale_ads_first_v1_key',true)!==YBY_DE_CLONE_KEY||
     get_post_status(YBY_DE_SOURCE)!=='publish'||get_post_status(310)!=='publish') {
     return new WP_Error('SCOPE','Germany identity or protected source state changed');
  }
  $nodes=get_post_meta(YBY_DE_PAGE,'_bricks_page_content_2',true);
  if(!is_array($nodes)||count($nodes)!==10)return new WP_Error('ELEMENTS','Expected ten Germany Bricks elements');
  $codes=0;$triggers=0;$country=0;$africa=0;$inline=0;$hero_code=0;
  foreach($nodes as $n){
    if(($n['name']??'')!=='code')continue;
    $text=(string)($n['settings']['code']??'');
    if(empty($n['settings']['signature'])||
       !class_exists('\Bricks\Helpers')||
       !\Bricks\Helpers::verify_code_signature($n['settings']['signature'],$text)){
        return new WP_Error('SIGNATURE','Target Bricks Code signature invalid');
    }
    $codes++;
    $triggers+=substr_count($text,'data-yby-inquiry-trigger');
    $country+=substr_count($text,'and Project Buyers in Germany');
    $africa+=preg_match_all('/African markets/i',$text);
    $inline+=substr_count($text,YBY_DE_STYLE_ID);
    if((string)($n['id']??'')==='1b0234')$hero_code++;
  }
  if($codes!==4||$triggers!==17||$country!==1||$africa!==0||$hero_code!==1) {
    return new WP_Error('CONTRACT','Wholesale/Germany/marketing/hero Code contract has changed');
  }
  return ['post'=>$post,'nodes'=>$nodes,
          'fingerprint'=>hash('sha256',serialize($nodes)),
          'inline_marker'=>$inline,'codes'=>$codes,'triggers'=>$triggers];
}
$mode=(string)($args[0]??'plan');
$expected=(string)($args[1]??'');
$state=yby_de_read();
if(is_wp_error($state))yby_de_error($state->get_error_code(),$state->get_error_message());
if($mode==='plan'){
   WP_CLI::line(wp_json_encode([
      'status'=>'PLAN_PASS','target_id'=>824,'fingerprint'=>$state['fingerprint'],
      'code_signatures'=>$state['codes'],'triggers'=>$state['triggers'],
      'inline_style_already_present'=>$state['inline_marker']>0
   ],JSON_UNESCAPED_SLASHES));return;
}
if($mode!=='apply')yby_de_error('MODE','Only plan/apply allowed');
if(!preg_match('/^[a-f0-9]{64}$/',$expected)||
   !hash_equals($state['fingerprint'],$expected))yby_de_error('SOURCE_DRIFT','Page changed after preflight');
if($state['inline_marker']!==0)yby_de_error('IDEMPOTENT','Germany style is already present');
if(!current_user_can('manage_options')||
   !current_user_can('edit_post',YBY_DE_PAGE)||
   !class_exists('\Bricks\Admin')||
   !class_exists('\Bricks\Capabilities')||
   (defined('BRICKS_LOCK_CODE_SIGNATURES')&&BRICKS_LOCK_CODE_SIGNATURES)||
   !\Bricks\Helpers::code_execution_enabled()||
   !\Bricks\Capabilities::current_user_can_execute_code()) {
   yby_de_error('CAPABILITY','Production Bricks signer denied');
}
$css='<style id="'.YBY_DE_STYLE_ID.'">'."\n".
'@media (max-width:991px) {'."\n".
'.yby-wholesale-hero-v3__content { display:block!important; width:100%!important; height:auto!important; max-width:100%!important; min-width:0!important; padding:30px 12px 34px!important; }'."\n".
'.yby-wholesale-hero-v3__content-inner { display:block!important; width:100%!important; max-width:100%!important; min-width:0!important; flex:none!important; }'."\n".
'.yby-wholesale-hero-v3__title { width:100%!important; max-width:100%!important; min-width:0!important; font-size:clamp(27px,7vw,35px)!important; line-height:1.13!important; white-space:normal!important; overflow-wrap:anywhere!important; }'."\n".
'.yby-wholesale-hero-v3__title-line, .yby-wholesale-hero-v3__title span { display:block!important; max-width:100%!important; white-space:normal!important; overflow-wrap:anywhere!important; word-break:normal!important; }'."\n".
'.yby-wholesale-hero-v3__desc { display:block!important; width:100%!important; max-width:100%!important; min-width:0!important; white-space:normal!important; overflow-wrap:anywhere!important; word-break:normal!important; }'."\n".
'.yby-wholesale-hero-v3__actions { display:flex!important; flex-wrap:wrap!important; gap:12px!important; }'."\n".
'}'."\n".
'@media (max-width:480px) {'."\n".
'.yby-wholesale-hero-v3__title { font-size:28px!important; line-height:1.13!important; }'."\n".
'.yby-wholesale-hero-v3__actions { flex-direction:column!important; align-items:flex-start!important; }'."\n".
'.yby-wholesale-hero-v3__btn { max-width:100%!important; }'."\n".
'}'."\n".
'</style>'."\n";
$before=$state['nodes'];$nodes=$before;$modified=0;
foreach($nodes as &$n){
  if(($n['name']??'')!=='code')continue;
  if((string)($n['id']??'')==='1b0234'){
    $n['settings']['code']=$css.(string)$n['settings']['code'];
    $modified++;
  }
  unset($n['settings']['signature'],$n['settings']['user_id'],$n['settings']['time']);
}
unset($n);
if($modified!==1)yby_de_error('CODE_ID','Exact signed hero Code node missing');
$signed=\Bricks\Admin::process_elements_for_signature($nodes,false,false);
if(!is_array($signed)||count($signed)!==10)yby_de_error('SIGN','Bricks native signing failed');
$codes=0;$triggers=0;$marker=0;
foreach($signed as $node){
  if(($node['name']??'')!=='code')continue;
  $text=(string)($node['settings']['code']??'');
  if(empty($node['settings']['signature'])||
     !\Bricks\Helpers::verify_code_signature($node['settings']['signature'],$text)) {
     yby_de_error('SIGNATURE','Unsigned or invalid new Bricks Code');
  }
  $codes++;$triggers+=substr_count($text,'data-yby-inquiry-trigger');
  $marker+=substr_count($text,YBY_DE_STYLE_ID);
}
if($codes!==4||$triggers!==17||$marker!==1)yby_de_error('VERIFY','Signed contract mismatch before write');
$backup=__DIR__.'/germany-824-pre-inline-css-'.gmdate('YmdHis').'.ser';
if(file_put_contents($backup,serialize($before),LOCK_EX)===false)yby_de_error('BACKUP','Failed to write private rollback');
@chmod($backup,0600);
update_post_meta(YBY_DE_PAGE,'_bricks_page_content_2',wp_slash($signed));
$after=yby_de_read();
if(is_wp_error($after)||$after['inline_marker']!==1){
  update_post_meta(YBY_DE_PAGE,'_bricks_page_content_2',wp_slash($before));
  yby_de_error('ROLLED_BACK','Validation failed, target Bricks content restored');
}
do_action('litespeed_purge_url',get_permalink(YBY_DE_PAGE));
WP_CLI::line(wp_json_encode([
 'status'=>'APPLIED','target_id'=>824,'code_signatures'=>4,
 'wholesale_triggers'=>17,'inline_css_marker'=>YBY_DE_STYLE_ID,
 'backup_path'=>$backup,'source_215_unchanged'=>get_post_status(215)==='publish',
 'thank_you_310_unchanged'=>get_post_status(310)==='publish'
],JSON_UNESCAPED_SLASHES));
