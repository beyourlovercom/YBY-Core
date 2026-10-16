<?php
/**
 * Important: This file is a REVIEW-ONLY candidate until the Production page-only
 * release authorization gate is satisfied. The Production terminal request was
 * blocked by a platform safety check on 2026-10-16. Do not use an alternative
 * channel to bypass that check. This script must NOT be executed in Production
 * merely because a GitHub PR is opened or its CI passes.
 *
 * The approved Germany #824 is copied as-is to preserve the final mobile fixes.
 * The only content substitution is "Germany" => "United States" within its
 * Code elements and post body, with native Bricks re-signing on the destination.
 */
/**
 * US Wholesale Ads First V1 — isolated Production page-only release based on approved Germany #824.
 * Run: wp eval-file /private/path/script.php plan --user=ADMIN
 *      wp eval-file /private/path/script.php create FINGERPRINT --user=ADMIN
 *      wp eval-file /private/path/script.php publish FINGERPRINT --user=ADMIN
 * No YBY Core upgrade, Project Studio writes, real inquiry, or Ads mutations.
 */
if (!defined('ABSPATH') || !defined('WP_CLI')) { exit(1); }
$verb = (string)($args[0] ?? 'plan');
$expected = (string)($args[1] ?? '');
const YBY_WV2_SRC = 824;
const YBY_WV2_TY = 310;
const YBY_WV2_SLUG = 'irrigation-wholesale-united-states';
const YBY_WV2_H1 = 'Wholesale Irrigation Parts for Distributors, Dealers, and Project Buyers';
const YBY_WV2_KEY = 'wholesale-ads-first-us-v1:824';

function yby_wv2_fail($code, $message) {
    WP_CLI::error($code . ': ' . $message);
}
function yby_wv2_heading_count($html, $expected) {
    $pattern='~<h1\b[^>]*class="yby-wholesale-hero-v3__title"[^>]*>.*?</h1>~si';
    preg_match_all($pattern,$html,$matches);
    $count=0;
    foreach($matches[0] as $h1) {
        $text=trim(preg_replace('/\s+/u',' ',html_entity_decode(strip_tags($h1),ENT_QUOTES|ENT_HTML5,'UTF-8')));
        if ($text===$expected) $count++;
    }
    return $count;
}
function yby_wv2_localize_h1($html, &$modified_count) {
    $pattern='~<h1\b[^>]*class="yby-wholesale-hero-v3__title"[^>]*>.*?</h1>~si';
    $result=preg_replace_callback($pattern,function($m) use (&$modified_count) {
        if (yby_wv2_heading_count($m[0],YBY_WV2_H1.' in Germany')!==1) return $m[0];
        $count=0;
        $localized=str_replace('in Germany','in the United States',$m[0],$count);
        if($count===1) $modified_count++;
        return $localized;
    },$html);
    return $result;
}
function yby_wv2_source() {
    if (strtolower((string) wp_parse_url(home_url('/'),PHP_URL_HOST)) !== 'ybyirrigation.com'
        || wp_get_environment_type() !== 'production') {
        return new WP_Error('HOST','Requires exact Production hostname and environment.');
    }
    $p=get_post(YBY_WV2_SRC);
    $thank=get_post(YBY_WV2_TY);
    if (!$p || $p->ID !== YBY_WV2_SRC || $p->post_type !== 'landing_page' || $p->post_status !== 'publish' ||
        $p->post_name !== 'irrigation-wholesale-germany' ||
        !$thank || $thank->post_type !== 'landing_page' ||
        $thank->post_status !== 'publish' ||
        $thank->post_name !== 'thank-you-irrigation-wholesale') {
        return new WP_Error('SOURCE','Wholesale #215 / Thank You #310 contract changed.');
    }
    if (!class_exists('\Bricks\Admin') || !class_exists('\Bricks\Helpers') ||
        !class_exists('\Bricks\Capabilities') ||
        !method_exists('\Bricks\Helpers','generate_new_element_ids') ||
        !method_exists('\Bricks\Admin','process_elements_for_signature')) {
        return new WP_Error('BRICKS','Bricks native signer and ID utility unavailable.');
    }
    $nodes=get_post_meta(YBY_WV2_SRC,'_bricks_page_content_2',true);
    if (!is_array($nodes) || count($nodes)!==10) {
        return new WP_Error('NODES','The reviewed 10-node Production source layout changed.');
    }
    $code_count=0; $h1_count=0; $triggers=0; $stale=0;
    foreach($nodes as $n) {
        if (($n['name']??'')!=='code') { continue; }
        $settings=$n['settings']??[];
        $c=(string)($settings['code']??'');
        if (!$c || !is_string($c) || empty($settings['signature'])) {
            return new WP_Error('CODE','Source Code or legacy signature missing.');
        }
        $code_count++;
        $h1_count += yby_wv2_heading_count($c,YBY_WV2_H1.' in Germany');
        $triggers += substr_count($c,'data-yby-inquiry-trigger');
        if (!\Bricks\Helpers::verify_code_signature($settings['signature'],$c)) { $stale++; }
    }
    if ($code_count!==4 || $h1_count!==1 || $triggers!==17) {
        return new WP_Error('CONTRACT','The reviewed 4 Code / 1 H1 / 17 trigger layout changed: '.wp_json_encode([$code_count,$h1_count,$triggers]));
    }
    $settings=get_post_meta(YBY_WV2_SRC,'_bricks_page_settings',true);
    $settings=is_array($settings)?$settings:[];
    $fingerprint=hash('sha256',implode('|',[
        YBY_WV2_SRC, $p->post_name, $p->post_status,
        hash('sha256',serialize($nodes)), hash('sha256',serialize($settings))
    ]));
    return [
        'post'=>$p, 'nodes'=>$nodes, 'settings'=>$settings,
        'fingerprint'=>$fingerprint, 'code_count'=>$code_count,
        'triggers'=>$triggers, 'legacy_stale_signatures'=>$stale
    ];
}
function yby_wv2_current() {
    $posts=get_posts([
        'post_type'=>'landing_page','post_status'=>'any','posts_per_page'=>30,
        'name'=>YBY_WV2_SLUG,'suppress_filters'=>true
    ]);
    $by_key=get_posts([
        'post_type'=>'landing_page','post_status'=>'any','posts_per_page'=>30,
        'meta_key'=>'_yby_wholesale_ads_first_v1_key',
        'meta_value'=>YBY_WV2_KEY,
        'suppress_filters'=>true
    ]);
    if (count($posts)>1 || count($by_key)>1) {
        return new WP_Error('COLLISION','Multiple matching Germany pages detected.');
    }
    if (!$posts && !$by_key) return null;
    if (count($posts)!==1 || count($by_key)!==1 ||
        (int)$posts[0]->ID!==(int)$by_key[0]->ID ||
        $posts[0]->post_name!==YBY_WV2_SLUG) {
        return new WP_Error('COLLISION','Slug or marker belongs to an unexpected post.');
    }
    return $posts[0];
}
function yby_wv2_verify($id, $source) {
    $p=get_post($id);
    if (!$p || $p->post_type!=='landing_page' ||
        $p->post_name!==YBY_WV2_SLUG ||
        !in_array($p->post_status,['draft','publish'],true) ||
        get_post_meta($id,'_yby_wholesale_ads_first_v1_key',true)!==YBY_WV2_KEY ||
        (int)get_post_meta($id,'_yby_wholesale_ads_first_source_id',true)!==YBY_WV2_SRC) {
        return new WP_Error('TARGET','Germany page identity/state does not match.');
    }
    $nodes=get_post_meta($id,'_bricks_page_content_2',true);
    $code=0; $triggers=0; $target_heading=0; $source_heading=0; $old_ids=[];
    foreach($source['nodes'] as $n) $old_ids[]=(string)($n['id']??'');
    if (!is_array($nodes) || count($nodes)!==10) return new WP_Error('ELEMENTS','Generated node count mismatch.');
    foreach($nodes as $n) {
        if (in_array((string)($n['id']??''),$old_ids,true)) return new WP_Error('ELEMENT_IDS','Source Bricks node ID reused.');
        if (($n['name']??'')!=='code') continue;
        $settings=$n['settings']??[];
        $c=(string)($settings['code']??'');
        if (empty($settings['signature']) ||
            !\Bricks\Helpers::verify_code_signature($settings['signature'],$c)) {
            return new WP_Error('SIGNED','Unsigned or invalid Germany Code.');
        }
        $code++;
        $target_heading += yby_wv2_heading_count($c,YBY_WV2_H1.' in the United States');
        $source_heading += yby_wv2_heading_count($c,YBY_WV2_H1);
        $triggers += substr_count($c,'data-yby-inquiry-trigger');
        if (strpos($c,'Germany')!==false) return new WP_Error('COUNTRY_COPY','Germany text remained in the US page Code.');
    }
    if($code!==4 || $target_heading!==1 || $source_heading!==0 || $triggers!==17) {
        return new WP_Error('CTA','Germany headline/Code/CTA mismatch: '.wp_json_encode([$code,$target_heading,$source_heading,$triggers]));
    }
    return ['id'=>$id,'status'=>$p->post_status,'signed_code'=>$code,
            'country_heading'=>$target_heading,'wholesale_triggers'=>$triggers,
            'url'=>get_permalink($id)];
}
$src=yby_wv2_source();
if (is_wp_error($src)) yby_wv2_fail($src->get_error_code(),$src->get_error_message());
$existing=yby_wv2_current();
if (is_wp_error($existing)) yby_wv2_fail($existing->get_error_code(),$existing->get_error_message());
if($verb==='plan') {
    $response=['status'=>'PLAN_PASS','source_id'=>YBY_WV2_SRC,'source_basis'=>'Germany #824 final Owner-approved page, including inline mobile CSS',
      'thank_you_id'=>YBY_WV2_TY,'source_fingerprint'=>$src['fingerprint'],
      'source_code'=>$src['code_count'],'source_triggers'=>$src['triggers'],
      'source_legacy_signatures_stale'=>$src['legacy_stale_signatures'],
      'existing'=> $existing?['id'=>$existing->ID,'status'=>$existing->post_status]:null,
      'target_slug'=>YBY_WV2_SLUG, 'plugin_upgrade'=>false, 'ads_changes'=>false, 'owner_approval_required'=>true];
    WP_CLI::line(wp_json_encode($response,JSON_UNESCAPED_SLASHES));
    return;
}
if (!in_array($verb,['create','publish'],true)) yby_wv2_fail('MODE','Unknown operation.');
if (!preg_match('/^[a-f0-9]{64}$/',$expected) ||
    !hash_equals($src['fingerprint'],$expected)) yby_wv2_fail('DRIFT','Owner reviewed source fingerprint changed.');
if (!current_user_can('manage_options') || !current_user_can('edit_pages') ||
    !current_user_can('edit_post',YBY_WV2_SRC) ||
    (defined('BRICKS_LOCK_CODE_SIGNATURES') && BRICKS_LOCK_CODE_SIGNATURES) ||
    !\Bricks\Helpers::code_execution_enabled() ||
    !\Bricks\Capabilities::current_user_can_execute_code()) {
    yby_wv2_fail('CAPABILITY','Production administrator lacks native Bricks signing permission.');
}
if ($verb==='create' && $existing) {
    $verification=yby_wv2_verify($existing->ID,$src);
    if(is_wp_error($verification)) yby_wv2_fail($verification->get_error_code(),$verification->get_error_message());
    WP_CLI::line(wp_json_encode(['status'=>'EXISTING_REUSED','verification'=>$verification],JSON_UNESCAPED_SLASHES));
    return;
}
if ($verb==='create') {
    $nodes=$src['nodes']; $localized_headings=0;
    foreach($nodes as &$n) {
        if (($n['name']??'')!=='code') continue;
        $text=(string)$n['settings']['code'];
        $n['settings']['code']=str_replace(['in Germany','Germany'],['in the United States','United States'],yby_wv2_localize_h1($text,$localized_headings));
        unset($n['settings']['signature'],$n['settings']['user_id'],$n['settings']['time']);
    }
    unset($n);
    if($localized_headings!==1) yby_wv2_fail('H1_TRANSFORM','Exactly one original Wholesale H1 must be localized.');
    $fresh=\Bricks\Helpers::generate_new_element_ids($nodes);
    if(!is_array($fresh) || count($fresh)!==count($nodes)) yby_wv2_fail('NEW_IDS','Bricks ID regeneration failed.');
    $signed=\Bricks\Admin::process_elements_for_signature($fresh,false,false);
    if(!is_array($signed) || count($signed)!==count($fresh)) yby_wv2_fail('SIGNER','Native Bricks signer failed.');
    $source_before=hash('sha256',serialize(get_post_meta(YBY_WV2_SRC,'_bricks_page_content_2',true)));
    $id=wp_insert_post([
      'post_type'=>'landing_page','post_status'=>'draft',
      'post_name'=>YBY_WV2_SLUG,
      'post_title'=>'IRRIGATION WHOLESALE — United States',
      'post_content'=>str_replace(['in Germany','Germany'],['in the United States','United States'],$src['post']->post_content)
    ],true);
    if (is_wp_error($id)||!$id) yby_wv2_fail('INSERT','Could not insert separate Germany draft.');
    update_post_meta($id,'_bricks_editor_mode','bricks');
    update_post_meta($id,'_bricks_page_content_2',wp_slash($signed));
    update_post_meta($id,'_bricks_page_settings',wp_slash($src['settings']));
    update_post_meta($id,'_yby_wholesale_ads_first_v1_key',YBY_WV2_KEY);
    update_post_meta($id,'_yby_wholesale_ads_first_source_id',YBY_WV2_SRC);
    update_post_meta($id,'_yby_wholesale_ads_first_country','United States');
    update_post_meta($id,'rank_math_robots',['noindex','follow']);
    $v=yby_wv2_verify($id,$src);
    if(is_wp_error($v)) {
      wp_delete_post($id,true);
      yby_wv2_fail('ROLLBACK',$v->get_error_code().': '.$v->get_error_message());
    }
    if (!hash_equals($source_before,hash('sha256',serialize(get_post_meta(YBY_WV2_SRC,'_bricks_page_content_2',true))))) {
      wp_delete_post($id,true); yby_wv2_fail('SOURCE_DRIFT','Source changed: new page rolled back.');
    }
    WP_CLI::line(wp_json_encode(['status'=>'CREATED_DRAFT','verification'=>$v,
      'rollback'=>'Revert this standalone Germany page to Draft or delete only this ID. No plugin changed.'],JSON_UNESCAPED_SLASHES));
    return;
}
if ($verb==='publish') {
    if (!$existing || $existing->post_status!=='draft') yby_wv2_fail('STATUS','Target Draft required before publication.');
    $v=yby_wv2_verify($existing->ID,$src);
    if(is_wp_error($v)) yby_wv2_fail('VERIFY',$v->get_error_message());
    $out=wp_update_post(['ID'=>$existing->ID,'post_status'=>'publish'],true);
    if(is_wp_error($out) || !$out) yby_wv2_fail('PUBLISH','WordPress refused independent Germany publication.');
    $v=yby_wv2_verify($existing->ID,$src);
    if(is_wp_error($v) || $v['status']!=='publish') {
      wp_update_post(['ID'=>$existing->ID,'post_status'=>'draft']);
      yby_wv2_fail('ROLLBACK','Publication check failed: reverted Germany to Draft.');
    }
    WP_CLI::line(wp_json_encode(['status'=>'PUBLISHED','verification'=>$v,
     'source_published'=>get_post_status(YBY_WV2_SRC)==='publish',
     'thank_you_published'=>get_post_status(YBY_WV2_TY)==='publish',
     'rollback'=>'Revert Germany page '.$existing->ID.' to draft only; no source or plugin changes.'],JSON_UNESCAPED_SLASHES));
}
