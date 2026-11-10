<?php
/**
 * Source-only R10 category/page adapter.
 * Never edits the already-designed /holiday/black-friday/ page.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class BYL_BF2026_R10_Catalog {
    public const LANDING = 'holiday/black-friday';
    public const CLEARANCE_PATH = 'clearance';
    public const GIFT_PATH = 'deals/gift';

    /** Gift category is NOT an implicit free-gift list. */
    public static function validate_gifts(array $rows): array {
        if (count($rows)<2 || count($rows)>60) {
            return ['ok'=>false,'reason'=>'invalid_gift_catalog_size'];
        }
        $random=[];$premium=[];$seen=[];
        foreach ($rows as $row) {
            if (!is_array($row) || !is_int($row['product_id']??null)
                || $row['product_id']<1 || !is_string($row['sku']??null)
                || strlen(trim($row['sku']))<2 || isset($seen[$row['product_id']])
                || !in_array($row['mode']??'', ['random_free','selected_upgrade'],true)) {
                return ['ok'=>false,'reason'=>'invalid_or_duplicate_gift'];
            }
            $seen[$row['product_id']]=true;
            if (($row['approved']??false)!==true
                || ($row['warehouse_verified']??false)!==true) {
                return ['ok'=>false,'reason'=>'warehouse_or_owner_approval_missing'];
            }
            if ($row['mode']==='random_free') {
                if (($row['upgrade_minor']??null)!==0
                    || !is_array($row['allowed_countries']??null)
                    || !$row['allowed_countries']) {
                    return ['ok'=>false,'reason'=>'invalid_random_pool'];
                }
                $random[]=$row;
            } else {
                if (!in_array(($row['upgrade_minor']??null),[1000,1500,2000],true)
                    || !in_array('US',$row['allowed_countries']??[],true)) {
                    return ['ok'=>false,'reason'=>'invalid_premium_tier_or_country'];
                }
                $premium[]=$row;
            }
        }
        if (count($random)<2) {
            return ['ok'=>false,'reason'=>'two_free_options_required'];
        }
        return [
            'ok'=>true,'random_free'=>$random,'premium'=>$premium,
            'gift_category_product_ids'=>array_keys($seen),
        ];
    }

    public static function audit_routes(): array {
        $landing=function_exists('get_page_by_path')?get_page_by_path(self::LANDING):null;
        $clearance=function_exists('get_page_by_path')?get_page_by_path(self::CLEARANCE_PATH):null;
        $gift=function_exists('get_page_by_path')?get_page_by_path(self::GIFT_PATH):null;
        return [
            'landing_exists'=>(bool)$landing,'landing_id'=>$landing?(int)$landing->ID:null,
            'clearance_page_exists'=>(bool)$clearance,
            'gift_page_exists'=>(bool)$gift,
            'designed_landing_always_untouched'=>true,
        ];
    }

    private static function term(string $name, string $slug, int $parent=0): int {
        $found=get_term_by('slug',$slug,'product_cat');
        if ($found && !is_wp_error($found)) {
            if ($parent && (int)$found->parent !== $parent) {return 0;}
            return (int)$found->term_id;
        }
        $created=wp_insert_term($name,'product_cat',['slug'=>$slug,'parent'=>$parent]);
        return is_wp_error($created)?0:(int)$created['term_id'];
    }

    /**
     * Explicit approved management operation ONLY. New pages are drafts.
     * Does not touch existing page content, routes, or permalink settings.
     */
    public static function prepare_catalog(array $approved_sale_ids, array $gift_rows): array {
        $gifts=self::validate_gifts($gift_rows);
        if (!$gifts['ok']) {return $gifts;}
        if (!$approved_sale_ids || count($approved_sale_ids)>60
            || count($approved_sale_ids)!==count(array_unique($approved_sale_ids))) {
            return ['ok'=>false,'reason'=>'invalid_sale_ids'];
        }
        foreach (array_merge($approved_sale_ids,$gifts['gift_category_product_ids']) as $id) {
            if (!is_int($id) || $id<=0 || !function_exists('wc_get_product')
                || !wc_get_product($id)) {
                return ['ok'=>false,'reason'=>'unverified_product_id'];
            }
        }
        $clearance=self::term('Clearance','clearance');
        $deals=self::term('Deals','deals');
        $gift=$deals>0?self::term('Gift','gift',$deals):0;
        if (!$clearance || !$gift) {
            return ['ok'=>false,'reason'=>'category_slug_or_parent_conflict'];
        }
        foreach ($approved_sale_ids as $id) {
            $result=wp_set_object_terms($id,[$clearance],'product_cat',true);
            if (is_wp_error($result)) {return ['ok'=>false,'reason'=>'clearance_assignment_failed'];}
        }
        foreach ($gifts['gift_category_product_ids'] as $id) {
            $result=wp_set_object_terms($id,[$gift],'product_cat',true);
            if (is_wp_error($result)) {return ['ok'=>false,'reason'=>'gift_assignment_failed'];}
        }
        $pages=[];
        $routes=[
            self::CLEARANCE_PATH=>[
                'title'=>'Clearance',
                'content'=>'[products category="clearance" paginate="true" limit="24" columns="4"]'
            ],
            'deals'=>['title'=>'Deals','content'=>'Browse current deals.'],
            self::GIFT_PATH=>[
                'title'=>'Black Friday Gift Selection',
                'content'=>'Gift selection will be available when the campaign is activated.'
            ],
        ];
        $parent=0;
        foreach ($routes as $path=>$settings) {
            $existing=get_page_by_path($path);
            if ($existing) {
                $pages[$path]=['id'=>(int)$existing->ID,'existing'=>true];
                if ($path==='deals') {$parent=(int)$existing->ID;}
                continue;
            }
            $id=wp_insert_post([
                'post_type'=>'page','post_status'=>'draft',
                'post_parent'=>$path===self::GIFT_PATH?$parent:0,
                'post_name'=>$path===self::GIFT_PATH?'gift':$path,
                'post_title'=>$settings['title'],
                'post_content'=>$settings['content']
            ],true);
            if (is_wp_error($id) || !(int)$id) {
                return ['ok'=>false,'reason'=>'draft_page_creation_failed','path'=>$path];
            }
            $pages[$path]=['id'=>(int)$id,'existing'=>false,'status'=>'draft'];
            if ($path==='deals') {$parent=(int)$id;}
        }
        return [
            'ok'=>true,'clearance_term_id'=>$clearance,'gift_term_id'=>$gift,
            'pages'=>$pages,'landing_unchanged'=>true,'new_pages_draft_only'=>true,
        ];
    }
}
