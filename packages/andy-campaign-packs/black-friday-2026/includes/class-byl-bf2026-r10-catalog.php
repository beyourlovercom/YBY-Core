<?php
/**
 * R11 approved Black Friday catalog: existing PRIVATE WWW routes only.
 * SOURCE ONLY; nothing here registers a cart hook or activates promotions.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class BYL_BF2026_R10_Catalog {
    public const LANDING = 'holiday/black-friday';
    public const CLEARANCE_PATH = 'deals/clearance';
    public const GIFT_PATH = 'holiday/gift';

    /** Categories describe display membership, never automatic gift entitlement. */
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
                || ($row['warehouse_verified']??false)!==true
                || !is_array($row['allowed_countries']??null)
                || !$row['allowed_countries']) {
                return ['ok'=>false,'reason'=>'warehouse_or_country_approval_missing'];
            }
            if ($row['mode']==='random_free') {
                if (($row['upgrade_minor']??null)!==0) {
                    return ['ok'=>false,'reason'=>'random_gift_must_be_free'];
                }
                $random[]=$row;
            } else {
                if (!in_array(($row['upgrade_minor']??null),[1000,1500,2000],true)
                    || !in_array('US',$row['allowed_countries'],true)) {
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

    private static function page(string $path) {
        return function_exists('get_page_by_path') ? get_page_by_path($path) : null;
    }

    public static function audit_routes(): array {
        $landing=self::page(self::LANDING);
        $clearance=self::page(self::CLEARANCE_PATH);
        $gift=self::page(self::GIFT_PATH);
        return [
            'landing_exists'=>(bool)$landing,
            'landing_id'=>$landing?(int)$landing->ID:null,
            'clearance_page_exists'=>(bool)$clearance,
            'clearance_private'=>$clearance && ($clearance->post_status??'')==='private',
            'gift_page_exists'=>(bool)$gift,
            'gift_private'=>$gift && ($gift->post_status??'')==='private',
            'designed_landing_always_untouched'=>true,
        ];
    }

    private static function term(string $name, string $slug, int $parent=0): int {
        $found=get_term_by('slug',$slug,'product_cat');
        if ($found && !is_wp_error($found)) {
            if ((int)$found->parent !== $parent) {return 0;}
            return (int)$found->term_id;
        }
        $created=wp_insert_term($name,'product_cat',['slug'=>$slug,'parent'=>$parent]);
        return is_wp_error($created)?0:(int)$created['term_id'];
    }

    /**
     * EXPLICIT approved admin operation only; require known private pages.
     * No page creation, permalink changes, promotion activation or price edits.
     * Gifting eligibility still requires per-country warehouse proof at checkout.
     */
    public static function prepare_catalog(array $approved_sale_ids, array $gift_rows): array {
        $gifts=self::validate_gifts($gift_rows);
        if (!$gifts['ok']) {return $gifts;}
        if (!$approved_sale_ids || count($approved_sale_ids)>60
            || count($approved_sale_ids)!==count(array_unique($approved_sale_ids))) {
            return ['ok'=>false,'reason'=>'invalid_sale_ids'];
        }
        $routes=self::audit_routes();
        if (!$routes['landing_exists'] || !$routes['clearance_private']
            || !$routes['gift_private']) {
            return ['ok'=>false,'reason'=>'canonical_private_routes_missing'];
        }

        // Preflight ALL IDs and canonical SKUs BEFORE the first category write.
        $gift_by_id=[];
        foreach ($gift_rows as $row) {$gift_by_id[$row['product_id']]=$row;}
        foreach (array_merge($approved_sale_ids,$gifts['gift_category_product_ids']) as $id) {
            if (!is_int($id) || $id<=0 || !function_exists('wc_get_product')) {
                return ['ok'=>false,'reason'=>'unverified_product_id'];
            }
            $p=wc_get_product($id);
            if (!$p || !method_exists($p,'get_sku') || !method_exists($p,'is_type')
                || !$p->is_type('simple')) {
                return ['ok'=>false,'reason'=>'invalid_product_type_or_id','id'=>$id];
            }
            if (isset($gift_by_id[$id])) {
                if ((string)$p->get_sku() !== $gift_by_id[$id]['sku']
                    || !$p->is_purchasable() || !$p->is_in_stock()) {
                    return ['ok'=>false,'reason'=>'gift_identity_or_woo_stock_mismatch','id'=>$id];
                }
            }
        }

        $clearance=self::term('Clearance','clearance');
        $gift=self::term('Gift Collection','gift');
        $free=$gift>0?self::term('Complimentary Gifts','gift-free',$gift):0;
        $upgrade=$gift>0?self::term('Gift Upgrades','gift-upgrade',$gift):0;
        if (!$clearance || !$gift || !$free || !$upgrade) {
            return ['ok'=>false,'reason'=>'category_slug_or_parent_conflict'];
        }
        foreach ($approved_sale_ids as $id) {
            $r=wp_set_object_terms($id,[$clearance],'product_cat',true);
            if (is_wp_error($r)) {return ['ok'=>false,'reason'=>'clearance_assignment_failed'];}
        }
        foreach ($gift_rows as $row) {
            $child=$row['mode']==='random_free'?$free:$upgrade;
            $r=wp_set_object_terms($row['product_id'],[$gift,$child],'product_cat',true);
            if (is_wp_error($r)) {return ['ok'=>false,'reason'=>'gift_assignment_failed'];}
        }
        return [
            'ok'=>true,'clearance_term_id'=>$clearance,'gift_term_id'=>$gift,
            'free_term_id'=>$free,'upgrade_term_id'=>$upgrade,
            'pages_verified'=>[
                self::LANDING=>$routes['landing_id'],
                self::CLEARANCE_PATH=>true,
                self::GIFT_PATH=>true,
            ],
            'landing_unchanged'=>true,'no_page_writes'=>true,
        ];
    }
}
