<?php
/**
 * Wholesale Country Clone V2, isolated read-only Dev source preflight.
 * No draft-creation method, no Production/email/CRM/Ads writes or source mutations.
 */
if (!defined('ABSPATH')) { exit; }

final class YBY_Wholesale_Country_Clone_V2 {
    const SOURCE_PAGE_ID = 215;
    const SOURCE_SLUG = 'irrigation-wholesale';
    const SOURCE_BRICKS_SHA256 = '552ac3ff52aa1e66cc20403bfda6da85d824996958abc955724a1679d4aeb19b';
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
        $code_count=0;$hero_count=0;$source_stale_signatures=0;
        foreach($nodes as $node) {
            if (($node['name']??'')!=='code') { continue; }
            $text=(string)($node['settings']['code']??'');
            if (!$text || empty($node['settings']['signature']) || !class_exists('\Bricks\Helpers')) {
                return new WP_Error('v2_signature_missing','Wholesale source Code or signature missing.');
            }
            // Source SHA-256 is locked above. Legacy source signatures may be stale under
            // current Bricks; do not modify that historical published source. All generated
            // Code is independently signed and strictly verified before a draft DB write.
            if (!\Bricks\Helpers::verify_code_signature($node['settings']['signature'],$text)) {
                $source_stale_signatures++;
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
          'source_stale_signatures'=>$source_stale_signatures, 'new_native_signatures_required'=>true,
          'global_inquiry_preserved'=>true,
        ];
    }
}
