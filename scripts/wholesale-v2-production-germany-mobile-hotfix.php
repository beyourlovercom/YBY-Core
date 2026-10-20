<?php
/**
 * Germany Wholesale Production #824 — page-only responsive/market copy hotfix.
 * Modes: wp eval-file script.php plan --user=1
 *        wp eval-file script.php apply EXPECTED_FINGERPRINT --user=1
 * Backup to non-public /home/.../.byl-uat/wholesale-v2-release/.
 * Never alters mother #215, TY #310, Core plugin, real leads or Ads.
 */
if (!defined('ABSPATH') || !defined('WP_CLI')) exit(1);
const YBY_WV2_HOTFIX_POST = 824;
const YBY_WV2_HOTFIX_SOURCE = 215;
const YBY_WV2_HOTFIX_MARKER = 'yby-wholesale-de-mobile-uat-v1';
const YBY_WV2_HOTFIX_KEY = 'wholesale-ads-first-de-v1:215';
function yby_wv2_herr($code,$message){WP_CLI::error($code.': '.$message);}
function yby_wv2_hstate(){
    if (strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST))!=='ybyirrigation.com' ||
        wp_get_environment_type()!=='production') return new WP_Error('HOST','Production host/environment not matched.');
    $p=get_post(YBY_WV2_HOTFIX_POST);
    if (!$p || $p->post_type!=='landing_page' || $p->post_status!=='publish' ||
        $p->post_name!=='irrigation-wholesale-germany' ||
        get_post_meta($p->ID,'_yby_wholesale_ads_first_v1_key',true)!==YBY_WV2_HOTFIX_KEY ||
        (int)get_post_meta($p->ID,'_yby_wholesale_ads_first_source_id',true)!==YBY_WV2_HOTFIX_SOURCE ||
        get_post_status(215)!=='publish' || get_post_status(310)!=='publish') {
        return new WP_Error('IDENTITY','Germany target or protected source state changed.');
    }
    $nodes=get_post_meta($p->ID,'_bricks_page_content_2',true);
    $settings=get_post_meta($p->ID,'_bricks_page_settings',true);
    if(!is_array($nodes)||count($nodes)!==10||!is_array($settings)||!isset($settings['customCss'])) {
        return new WP_Error('BRICKS','Expected Germany Bricks data and customCss missing.');
    }
    $codes=0;$triggers=0;$germany=0;$africa=0;
    foreach($nodes as $n){
        if(($n['name']??'')!=='code')continue;
        $code=(string)($n['settings']['code']??'');
        if(empty($n['settings']['signature']) ||
           !class_exists('\Bricks\Helpers') ||
           !\Bricks\Helpers::verify_code_signature($n['settings']['signature'],$code)) {
            return new WP_Error('SIGNATURE','Existing signed Code is invalid.');
        }
        $codes++;
        $triggers+=substr_count($code,'data-yby-inquiry-trigger');
        $germany+=substr_count($code,'and Project Buyers in Germany');
        $africa+=preg_match_all('/African markets/i',$code);
    }
    if($codes!==4 || $triggers!==17 || $germany!==1) {
        return new WP_Error('CONTRACT', 'Wholesale Code/trigger/Germany contract mismatch.');
    }
    $fingerprint=hash('sha256',serialize([$nodes,$settings]));
    return ['post'=>$p,'nodes'=>$nodes,'settings'=>$settings,
      'fingerprint'=>$fingerprint,'africa_mentions'=>$africa,'code_count'=>$codes,
      'triggers'=>$triggers];
}
$mode=(string)($args[0]??'plan');
$expected=(string)($args[1]??'');
$state=yby_wv2_hstate();
if(is_wp_error($state))yby_wv2_herr($state->get_error_code(),$state->get_error_message());
if($mode==='plan'){
  WP_CLI::line(wp_json_encode([
    'status'=>'HOTFIX_PLAN_PASS','id'=>824,'fingerprint'=>$state['fingerprint'],
    'africa_mentions'=>$state['africa_mentions'],
    'signed_code'=>$state['code_count'],'wholesale_triggers'=>$state['triggers'],
    'css_hotfix_present'=>strpos((string)$state['settings']['customCss'],YBY_WV2_HOTFIX_MARKER)!==false,
    'source_215_untouched'=>true
  ],JSON_UNESCAPED_SLASHES));return;
}
if($mode!=='apply')yby_wv2_herr('MODE','Only plan/apply permitted.');
if(!preg_match('/^[a-f0-9]{64}$/',$expected)||!hash_equals($state['fingerprint'],$expected)) {
    yby_wv2_herr('FINGERPRINT','Target page changed since reviewed plan.');
}
if(!current_user_can('manage_options') ||
   !current_user_can('edit_post',824) ||
   !class_exists('\Bricks\Admin') ||
   !class_exists('\Bricks\Capabilities') ||
   !\Bricks\Helpers::code_execution_enabled() ||
   !\Bricks\Capabilities::current_user_can_execute_code() ||
   (defined('BRICKS_LOCK_CODE_SIGNATURES')&&BRICKS_LOCK_CODE_SIGNATURES)) {
    yby_wv2_herr('CAPABILITY','Native Bricks code signer denied.');
}
if($state['africa_mentions']!==2 || strpos((string)$state['settings']['customCss'],YBY_WV2_HOTFIX_MARKER)!==false) {
    yby_wv2_herr('PREVIOUS_OR_CHANGED','Expected exactly 2 Africa phrases and no previous hotfix.');
}
$old_nodes=$state['nodes'];$old_settings=$state['settings'];$nodes=$old_nodes;$settings=$old_settings;
$phrase1=0;$phrase2=0;
foreach($nodes as &$n){
    if(($n['name']??'')!=='code')continue;
    if((string)($n['id']??'')==='1b0234'){
        $c=(string)$n['settings']['code'];
        $c=str_replace('Reliable supply for African markets.','Wholesale sourcing for the German market.',$c,$phrase1);
        $c=str_replace('Support for African Markets','Wholesale Supply for Germany',$c,$phrase2);
        $n['settings']['code']=$c;
    }
    unset($n['settings']['signature'],$n['settings']['user_id'],$n['settings']['time']);
}
unset($n);
if($phrase1!==1||$phrase2!==1)yby_wv2_herr('COPY','Exactly one occurrence of each Africa claim is required.');
$css = "\n/* ".YBY_WV2_HOTFIX_MARKER." — Germany-only mobile overflow protection */\n".
"@media (max-width: 991px) {\n".
".yby-wholesale-hero-v3, .yby-wholesale-hero-v3__hero, .yby-wholesale-hero-v3__content, .yby-wholesale-hero-v3__content-inner { min-width:0!important; max-width:100%!important; }\n".
".yby-wholesale-hero-v3__content-inner { width:100%!important; }\n".
".yby-wholesale-hero-v3__title { width:100%!important; max-width:100%!important; font-size:clamp(27px, 7vw, 35px)!important; line-height:1.12!important; white-space:normal!important; overflow-wrap:anywhere!important; }\n".
".yby-wholesale-hero-v3__title-line { display:block!important; white-space:normal!important; overflow-wrap:anywhere!important; word-break:normal!important; max-width:100%!important; }\n".
".yby-wholesale-hero-v3__desc { width:100%!important; max-width:100%!important; min-width:0!important; white-space:normal!important; overflow-wrap:anywhere!important; }\n".
".yby-wholesale-hero-v3__actions { flex-wrap:wrap!important; gap:12px!important; }\n".
"}\n".
"@media (max-width: 480px) {\n".
".yby-wholesale-hero-v3__title { font-size:28px!important; line-height:1.13!important; }\n".
".yby-wholesale-hero-v3__actions { flex-direction:row!important; }\n".
".yby-wholesale-hero-v3__btn { max-width:100%!important; }\n".
"}\n";
$settings['customCss']=(string)$settings['customCss'].$css;
$signed=\Bricks\Admin::process_elements_for_signature($nodes,false,false);
if(!is_array($signed)||count($signed)!==10)yby_wv2_herr('SIGN','Native Bricks signing failed.');
$verified=0;$triggers=0;$africa=0;$ger=0;
foreach($signed as $n){
  if(($n['name']??'')!=='code')continue;
  $code=(string)($n['settings']['code']??'');
  if(empty($n['settings']['signature']) ||
     !\Bricks\Helpers::verify_code_signature($n['settings']['signature'],$code)) {
      yby_wv2_herr('SIGNATURE','New native Code signature failed.');
  }
  $verified++;
  $triggers+=substr_count($code,'data-yby-inquiry-trigger');
  $ger+=substr_count($code,'and Project Buyers in Germany');
  $africa+=preg_match_all('/African markets/i',$code);
}
if($verified!==4||$triggers!==17||$ger!==1||$africa!==0)yby_wv2_herr('VERIFY','Before-write Bricks or marketing contract failed.');
$backup=__DIR__.'/germany-824-pre-mobile-hotfix-'.gmdate('YmdHis').'.ser';
if(file_put_contents($backup,serialize(['nodes'=>$old_nodes,'settings'=>$old_settings]),LOCK_EX)===false) {
    yby_wv2_herr('BACKUP','Private rollback backup write failed.');
}
@chmod($backup,0600);
update_post_meta(824,'_bricks_page_content_2',wp_slash($signed));
update_post_meta(824,'_bricks_page_settings',wp_slash($settings));
$after=yby_wv2_hstate();
$okay=!is_wp_error($after)
    && $after['africa_mentions']===0
    && strpos((string)$after['settings']['customCss'],YBY_WV2_HOTFIX_MARKER)!==false;
if(!$okay){
    update_post_meta(824,'_bricks_page_content_2',wp_slash($old_nodes));
    update_post_meta(824,'_bricks_page_settings',wp_slash($old_settings));
    yby_wv2_herr('ROLLBACK','Post-write check failed; only target page 824 restored.');
}
do_action('litespeed_purge_url',get_permalink(824));
WP_CLI::line(wp_json_encode([
 'status'=>'HOTFIX_APPLIED','id'=>824,'code_signatures'=>4,
 'wholesale_triggers'=>17,'africa_mentions'=>0,'mobile_css_marker'=>YBY_WV2_HOTFIX_MARKER,
 'backup_path'=>$backup,'source_215_unchanged'=>get_post_status(215)==='publish',
 'thankyou_310_unchanged'=>get_post_status(310)==='publish'
],JSON_UNESCAPED_SLASHES));
