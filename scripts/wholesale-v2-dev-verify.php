<?php
/** M3 Dev-only no-submission rendering and CRM/CTA contract verifier. */
if (!defined('ABSPATH') || !defined('WP_CLI')) { exit(1); }
$site=strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST));
if ($site!=='dev.ybyirrigation.com' || wp_get_environment_type()!=='development') {
    WP_CLI::error('V2 verify limited to isolated Dev');
}
if (!class_exists('\Bricks\Frontend') ||
    !method_exists('\Bricks\Frontend','render_content')) {
    WP_CLI::error('Bricks frontend render method unavailable');
}
$out=['site'=>$site,'screens'=>[],'source_unchanged'=>false,'global_contract'=>[]];
$source=get_post_meta(215,'_bricks_page_content_2',true);
$source_raw_sha=hash('sha256',serialize($source));
$out['source_unchanged']=hash_equals('552ac3ff52aa1e66cc20403bfda6da85d824996958abc955724a1679d4aeb19b',$source_raw_sha);
$target=[819=>'Germany',821=>'United States',823=>'France'];
foreach($target as $id=>$country) {
    $post=get_post($id);
    if (!$post || $post->post_type!=='landing_page' || $post->post_status!=='draft') {
        WP_CLI::error('Expected Dev Draft not found: '.$id);
    }
    $nodes=get_post_meta($id,'_bricks_page_content_2',true);
    $good=0;$country_h1=0;$triggers_in_code=0;$source_profile=0;$bad_solution=0;
    foreach((array)$nodes as $n) {
        if(($n['name']??'')!=='code')continue;
        $code=(string)($n['settings']['code']??'');
        if(!empty($n['settings']['signature']) &&
          \Bricks\Helpers::verify_code_signature($n['settings']['signature'],$code)) $good++;
        $country_h1+=substr_count($code,'Wholesale Irrigation Systems and Components for Distributors and Projects in '.$country);
        $triggers_in_code+=substr_count($code,'data-yby-inquiry-trigger');
        $source_profile+=substr_count($code,'data-yby-page-profile="irrigation_wholesale"');
        $bad_solution+=substr_count(strtolower($code),'irrigation_solution');
    }
    global $post;
    $post=get_post($id);
    setup_postdata($post);
    ob_start();
    $result=\Bricks\Frontend::render_content($nodes);
    $buffer=ob_get_clean();
    wp_reset_postdata();
    $html=(string)$result.$buffer;
    $out['screens'][$id]=[
      'country'=>$country,'post_status'=>get_post_status($id),
      'bricks_nodes'=>count((array)$nodes),'native_signed_code'=>$good,
      'country_heading_count'=>$country_h1,'inquiry_triggers_in_code'=>$triggers_in_code,
      'wholesale_source_profile_count'=>$source_profile,'old_solution_profiles'=>$bad_solution,
      'bricks_render_bytes'=>strlen($html),
      'rendered_wholesale_trigger_count'=>substr_count($html,'data-yby-inquiry-trigger'),
      'preview_url'=>get_preview_post_link($id),
    ];
}
$plugin=WP_PLUGIN_DIR.'/yby-core';
$out['global_contract']=[
 'inquiry_js_exists'=>is_file($plugin.'/public/js/yby-inquiry-components.js'),
 'global_popup_class_exists'=>class_exists('YBY_Global_Popup'),
 'inquiry_renderer_exists'=>class_exists('YBY_Inquiry_Renderer'),
 'thank_you_page_310_published'=>get_post_status(310)==='publish',
 'protected_source_699_draft'=>get_post_status(699)==='draft',
 'source_215_published'=>get_post_status(215)==='publish',
];
WP_CLI::line(wp_json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
