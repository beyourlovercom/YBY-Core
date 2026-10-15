<?php
/**
 * Wholesale Country Clone V2, isolated Dev draft service.
 * No Production, email, CRM, tracking, or Ads writes. Does not alter source.
 */
if (!defined('ABSPATH')) { exit; }

final class YBY_Wholesale_Country_Clone_V2 {
    const SOURCE_PAGE_ID = 215;
    const SOURCE_SLUG = 'irrigation-wholesale';
    const SOURCE_BRICKS_SHA256 = '552ac3ff52aa1e66cc20403bfda6da85d824996958abc955724a1679d4aeb19b';
    const FLAG = 'YBY_WHOLESALE_CLONE_V2_DEV_ENABLED';
    const HERO = 'Wholesale Irrigation Systems and Components for Distributors and Projects';

    public static function countries() {
        return [
          'DE'=>'Germany','US'=>'United States','GB'=>'United Kingdom','FR'=>'France',
          'IT'=>'Italy','ES'=>'Spain','CA'=>'Canada','AU'=>'Australia',
          'NZ'=>'New Zealand','MX'=>'Mexico','BR'=>'Brazil','AE'=>'United Arab Emirates',
          'SA'=>'Saudi Arabia','ZA'=>'South Africa','KE'=>'Kenya','NG'=>'Nigeria',
        ];
    }
    public static function slugs($code) {
        $name = self::countries()[$code] ?? '';
        if (!$name) { return null; }
        $slug = sanitize_title($name);
        return [
          'project'=>'wholesale-irrigation-'.$slug,
          'page'=>'irrigation-wholesale-'.$slug,
          'key'=>'wholesale-v2:'.$code.':'.self::SOURCE_PAGE_ID,
          'country'=>$name,
        ];
    }
    public static function preflight($code) {
        $target = self::slugs($code);
        if (!$target) { return new WP_Error('v2_unknown_country','Country is not in the reviewed registry.'); }
        if (strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST)) !== 'dev.ybyirrigation.com' ||
            wp_get_environment_type() !== 'development') {
            return new WP_Error('v2_env','Only the isolated Dev site may run V2.');
        }
        $post = get_post(self::SOURCE_PAGE_ID);
        if (!$post || $post->post_type !== 'landing_page' || $post->post_status !== 'publish' ||
            $post->post_name !== self::SOURCE_SLUG) {
            return new WP_Error('v2_source','Wholesale canonical source is missing or has changed.');
        }
        global $wpdb;
        $raw = $wpdb->get_col($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s ORDER BY meta_id",
            self::SOURCE_PAGE_ID, '_bricks_page_content_2'
        ));
        if (count($raw) !== 2 || !hash_equals(hash('sha256',(string)$raw[0]),hash('sha256',(string)$raw[1])) ||
            !hash_equals(self::SOURCE_BRICKS_SHA256,hash('sha256',(string)$raw[0]))) {
            return new WP_Error('v2_meta_drift','Two identical reviewed Bricks meta records required; source changed.');
        }
        $nodes = get_post_meta(self::SOURCE_PAGE_ID,'_bricks_page_content_2',true);
        if (!is_array($nodes) || count($nodes)!==4) { return new WP_Error('v2_nodes','Expected 4 Bricks nodes.'); }
        $types = array_column($nodes,'name');
        if ($types!==['section','section','code','code']) {
            return new WP_Error('v2_nodes','Wholesale code layout changed.');
        }
        $code_count=0;$hero_count=0;
        foreach($nodes as $node) {
            if (($node['name']??'')!=='code') { continue; }
            $text=(string)($node['settings']['code']??'');
            if (!$text || empty($node['settings']['signature']) ||
                !class_exists('\\Bricks\\Helpers') ||
                !\\Bricks\\Helpers::verify_code_signature($node['settings']['signature'],$text)) {
                return new WP_Error('v2_signature','Wholesale source native signature missing or invalid.');
            }
            $code_count++;
            $hero_count+=substr_count($text,self::HERO);
        }
        if ($code_count!==2 || $hero_count!==1) {
            return new WP_Error('v2_hero','Wholesale hero contract unexpected.');
        }
        $page_settings=get_post_meta(self::SOURCE_PAGE_ID,'_bricks_page_settings',true);
        $page_settings=is_array($page_settings)?$page_settings:[];
        $fingerprint=hash('sha256', implode('|',[
          self::SOURCE_PAGE_ID, self::SOURCE_SLUG, (string)$post->post_status,
          self::SOURCE_BRICKS_SHA256,hash('sha256',serialize($page_settings))
        ]));
        return [
          'country_code'=>$code,'country'=>$target['country'],
          'source_id'=>self::SOURCE_PAGE_ID,'source_hash'=>self::SOURCE_BRICKS_SHA256,
          'fingerprint'=>$fingerprint,'target_project_slug'=>$target['project'],
          'target_page_slug'=>$target['page'],'code_nodes'=>$code_count,
          'source_code_signature_valid'=>true, 'global_inquiry_preserved'=>true,
        ];
    }
    public static function create_drafts($code,$expected_fingerprint) {
        if (!defined(self::FLAG) || true !== constant(self::FLAG)) {
            return new WP_Error('v2_flag_off','Dev draft creation disabled by default.');
        }
        if (!current_user_can('manage_options') || !current_user_can('andy_core_settings_manage') ||
            !current_user_can('edit_posts') || !current_user_can('edit_pages') ||
            !current_user_can('edit_post',self::SOURCE_PAGE_ID)) {
            return new WP_Error('v2_capability','An authorized administrator must run clone.');
        }
        $plan=self::preflight($code);
        if (is_wp_error($plan)) { return $plan; }
        if (!is_string($expected_fingerprint) || !hash_equals($plan['fingerprint'],$expected_fingerprint)) {
            return new WP_Error('v2_changed','Source fingerprint does not match approved preflight.');
        }
        $target=self::slugs($code);
        $posts=get_posts([
          'post_type'=>'any','post_status'=>'any','posts_per_page'=>30,
          'meta_key'=>'_yby_wholesale_clone_v2_key','meta_value'=>$target['key']
        ]);
        if (count($posts)===2) {
            $p=null;$landing=null;
            foreach($posts as $item) {
                if ($item->post_type==='yby_project') { $p=$item; }
                if ($item->post_type==='landing_page') { $landing=$item; }
            }
            if ($p && $landing && in_array($p->post_status,['draft','publish','pending','private'],true) &&
                $landing->post_status==='draft' &&
                (int)get_post_meta($p->ID,'yby_clone_v2_page_id',true)===$landing->ID &&
                (int)get_post_meta($landing->ID,'yby_clone_v2_project_id',true)===$p->ID) {
                return ['project_id'=>$p->ID,'page_id'=>$landing->ID,'reused'=>true,'status'=>'draft'];
            }
        }
        if (count($posts)!==0) { return new WP_Error('v2_partial','A partial/mismatched clone exists; review manually.'); }
        $collision=get_posts([
          'post_type'=>['landing_page','yby_project'], 'post_status'=>'any',
          'posts_per_page'=>40,'name'=>$target['page']
        ]);
        $project_collision=get_posts([
          'post_type'=>'yby_project','post_status'=>'any','posts_per_page'=>40,'name'=>$target['project']
        ]);
        if ($collision || $project_collision) {
            return new WP_Error('v2_collision','Destination slug exists; no overwrite permitted.');
        }
        $source=get_post(self::SOURCE_PAGE_ID);
        $nodes=get_post_meta(self::SOURCE_PAGE_ID,'_bricks_page_content_2',true);
        foreach($nodes as &$node) {
            if (($node['name']??'')==='code') {
                $text=(string)($node['settings']['code']??'');
                if (strpos($text,self::HERO)!==false) {
                    $node['settings']['code']=str_replace(
                        self::HERO,
                        'Wholesale Irrigation Systems and Components for Distributors and Projects in '.$target['country'],
                        $text
                    );
                }
                unset($node['settings']['signature'],$node['settings']['user_id'],$node['settings']['time']);
            }
        }
        unset($node);
        if (!class_exists('\\Bricks\\Helpers') ||
            !method_exists('\\Bricks\\Helpers','generate_new_element_ids') ||
            !class_exists('\\Bricks\\Admin') ||
            !method_exists('\\Bricks\\Admin','process_elements_for_signature') ||
            !class_exists('\\Bricks\\Capabilities') ||
            (defined('BRICKS_LOCK_CODE_SIGNATURES') && BRICKS_LOCK_CODE_SIGNATURES) ||
            !\\Bricks\\Helpers::code_execution_enabled() ||
            !\\Bricks\\Capabilities::current_user_can_execute_code()) {
            return new WP_Error('v2_signer_unavailable','Native Bricks signer unavailable or user denied.');
        }
        $fresh=\\Bricks\\Helpers::generate_new_element_ids($nodes);
        if (!is_array($fresh) || count($fresh)!==count($nodes)) {
            return new WP_Error('v2_ids','Independent Bricks element IDs not generated.');
        }
        $signed=\\Bricks\\Admin::process_elements_for_signature($fresh,false,false);
        if (!is_array($signed) || count($signed)!==count($fresh)) {
            return new WP_Error('v2_sign_failed','Native signature creation failed.');
        }
        $verified=0;
        foreach($signed as $n) {
            if (($n['name']??'')!=='code') { continue; }
            $s=$n['settings']??[];
            if (empty($s['signature']) ||
                !\\Bricks\\Helpers::verify_code_signature($s['signature'],(string)($s['code']??'')) ||
                (int)($s['user_id']??0)!==get_current_user_id()) {
                return new WP_Error('v2_sign_invalid','New Code signature verification failed.');
            }
            $verified++;
        }
        if ($verified!==2) { return new WP_Error('v2_sign_count','Expected two signed Code nodes.'); }
        $project_id=wp_insert_post([
          'post_type'=>'yby_project','post_status'=>'draft',
          'post_name'=>$target['project'],'post_title'=>$target['country'].' Irrigation Wholesale',
        ],true);
        if (is_wp_error($project_id) || !$project_id) {
            return new WP_Error('v2_project_create','Cannot create separate Project draft.');
        }
        $page_id=wp_insert_post([
          'post_type'=>'landing_page','post_status'=>'draft',
          'post_name'=>$target['page'],
          'post_title'=>'IRRIGATION WHOLESALE — '.$target['country'],
          'post_content'=>$source->post_content,
        ],true);
        if (is_wp_error($page_id) || !$page_id) {
            wp_delete_post($project_id,true);
            return new WP_Error('v2_page_create','Page creation failed; project was rolled back.');
        }
        $settings=get_post_meta(self::SOURCE_PAGE_ID,'_bricks_page_settings',true);
        $settings=is_array($settings)?$settings:[];
        update_post_meta($page_id,'_bricks_editor_mode','bricks');
        update_post_meta($page_id,'_bricks_page_content_2',$signed);
        update_post_meta($page_id,'_bricks_page_settings',$settings);
        update_post_meta($page_id,'_yby_wholesale_clone_v2_key',$target['key']);
        update_post_meta($page_id,'yby_clone_v2_project_id',$project_id);
        update_post_meta($page_id,'yby_clone_v2_source_page_id',self::SOURCE_PAGE_ID);
        update_post_meta($page_id,'yby_country',$target['country']);
        update_post_meta($project_id,'_yby_wholesale_clone_v2_key',$target['key']);
        update_post_meta($project_id,'yby_clone_v2_page_id',$page_id);
        update_post_meta($project_id,'yby_clone_v2_source_page_id',self::SOURCE_PAGE_ID);
        update_post_meta($project_id,'yby_country',$target['country']);
        update_post_meta($project_id,'yby_project_name',$target['country'].' Irrigation Wholesale');
        update_post_meta($project_id,'yby_project_status','draft');
        update_post_meta($project_id,'yby_landing_url',home_url('/lp/'.$target['page'].'/'));
        update_post_meta($project_id,'yby_thank_you_url',home_url('/lp/thank-you-irrigation-wholesale/'));
        update_post_meta($project_id,'yby_product_interest','Irrigation Wholesale');
        // Preserve global inquiry and tracking profile "irrigation_wholesale".
        if (get_post_status($page_id)!=='draft' ||
            (int)get_post_meta($project_id,'yby_clone_v2_page_id',true)!==(int)$page_id ||
            count((array)get_post_meta($page_id,'_bricks_page_content_2',true))!==4) {
            wp_delete_post($page_id,true);
            wp_delete_post($project_id,true);
            return new WP_Error('v2_verify','Draft verification failed, rolled back both records.');
        }
        return [
          'project_id'=>$project_id, 'page_id'=>$page_id, 'reused'=>false,
          'status'=>'draft','signed_code_nodes'=>$verified,
        ];
    }
}
