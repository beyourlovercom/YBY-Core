<?php
/**
 * R10: off-by-default Black Friday sale-price ledger.
 * No auto-start: a reviewed caller must explicitly prepare and apply.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class BYL_BF2026_R10_Sale_Manager {
    public const OPTION = 'byl_bf2026_sale_ledger_v1';
    private const LOCK = 'byl_bf2026_sale_write_lock_v1';

    public static function ledger(): array {
        $v = get_option(self::OPTION, []);
        return is_array($v) ? $v : [];
    }
    private static function persist(array $v): void {
        update_option(self::OPTION, $v, false);
    }
    private static function exclusive(callable $job): array {
        if (!add_option(self::LOCK, time(), '', false)) {
            return ['ok'=>false,'reason'=>'campaign_write_in_progress'];
        }
        try { return $job(); }
        finally { delete_option(self::LOCK); }
    }
    private static function product(int $id, string $sku) {
        if ($id<=0 || !function_exists('wc_get_product')) { return false; }
        $p=wc_get_product($id);
        return $p && $p->is_type('simple') && (string)$p->get_sku()===$sku ? $p : false;
    }

    /**
     * Inputs are a reviewed exact SKU list, not shopper input or public Dev IDs.
     * A previous prepared/active plan cannot be silently re-randomized.
     */
    public static function prepare(array $skus, int $start_utc, int $end_utc): array {
        return self::exclusive(static function() use($skus,$start_utc,$end_utc) {
            if (!class_exists('WooCommerce') || !class_exists('BYL_BF2026_R10_Price_Policy')
                || !function_exists('get_woocommerce_currency') || get_woocommerce_currency()!=='USD') {
                return ['ok'=>false,'reason'=>'requires_woocommerce_usd'];
            }
            $prior=self::ledger();
            if (in_array(($prior['state']??''),['prepared','active','restoring','conflicts'],true)) {
                return ['ok'=>false,'reason'=>'prior_plan_not_closed'];
            }
            if ($end_utc <= time() || $start_utc >= $end_utc
                || count($skus)<1 || count($skus)>60 || count(array_unique($skus))!==count($skus)) {
                return ['ok'=>false,'reason'=>'invalid_campaign_period_or_skus'];
            }
            $entries=[];
            foreach ($skus as $sku) {
                if (!is_string($sku) || !preg_match('/^[A-Za-z0-9._ -]{2,90}$/D',$sku)) {
                    return ['ok'=>false,'reason'=>'invalid_sku'];
                }
                $id=(int)wc_get_product_id_by_sku($sku);
                $p=self::product($id,$sku);
                if (!$p || !$p->is_purchasable() || !$p->is_in_stock()) {
                    return ['ok'=>false,'reason'=>'product_identity_or_stock_missing','sku'=>$sku];
                }
                $before=BYL_BF2026_R10_Price_Policy::snapshot($p);
                $percent=BYL_BF2026_R10_Price_Policy::draw_percent();
                $after=BYL_BF2026_R10_Price_Policy::plan(
                    $before['regular_price'],$before['sale_price'],$before['current_price'],
                    $percent, wc_get_price_decimals()
                );
                if (!$after['ok']) { return ['ok'=>false,'reason'=>'price_invalid','sku'=>$sku]; }
                if (isset($entries[$id])) {return ['ok'=>false,'reason'=>'duplicate_product_id'];}
                $entries[$id]=[
                    'phase'=>'prepared','sku'=>$sku,'before'=>$before,'after'=>$after
                ];
            }
            $v=[
                'schema'=>1,'state'=>'prepared','created_at'=>time(),
                'start_utc'=>$start_utc,'end_utc'=>$end_utc,
                'entries'=>$entries,'audit'=>[['at'=>time(),'event'=>'prepared']]
            ];
            self::persist($v);
            return ['ok'=>true,'state'=>'prepared','count'=>count($entries),'entries'=>$entries];
        });
    }

    private static function clearance_term(): int {
        $term=get_term_by('slug','clearance','product_cat');
        if ($term && !is_wp_error($term)) {return (int)$term->term_id;}
        $created=wp_insert_term('Clearance','product_cat',['slug'=>'clearance']);
        return is_wp_error($created)?0:(int)$created['term_id'];
    }

    public static function apply(): array {
        return self::exclusive(static function() {
            $v=self::ledger();
            if (($v['state']??'')!=='prepared' || time()<($v['start_utc']??PHP_INT_MAX) || time()>($v['end_utc']??0)) {
                return ['ok'=>false,'reason'=>'not_prepared_or_expired'];
            }
            // All-product preflight before the first price write.
            foreach ($v['entries'] as $id=>$entry) {
                $p=self::product((int)$id,$entry['sku']);
                if (!$p || BYL_BF2026_R10_Price_Policy::snapshot($p) !== $entry['before']) {
                    return ['ok'=>false,'reason'=>'product_changed_since_snapshot','id'=>$id];
                }
            }
            $term_id=self::clearance_term();
            if ($term_id<=0) {return ['ok'=>false,'reason'=>'clearance_category_unavailable'];}
            $v['state']='applying';self::persist($v);
            foreach ($v['entries'] as $id=>&$entry) {
                $p=self::product((int)$id,$entry['sku']);
                $entry['phase']='applying';self::persist($v); // durable before Woo mutation
                try {
                    $p->set_date_on_sale_from(null);
                    $p->set_date_on_sale_to(null);
                    $p->set_sale_price($entry['after']['campaign_sale_price']);
                    $p->save();
                    $saved=BYL_BF2026_R10_Price_Policy::snapshot($p);
                    if ($saved['sale_price'] !== $entry['after']['campaign_sale_price']) {
                        throw new RuntimeException('sale_price_readback_mismatch');
                    }
                    $assigned=wp_set_object_terms((int)$id,[$term_id],'product_cat',true);
                    if (is_wp_error($assigned)) {throw new RuntimeException('clearance_category_write_failed');}
                    $entry['phase']='applied';$entry['applied_at']=time();
                    self::persist($v);
                } catch (Throwable $e) {
                    $entry['failure']='price_or_category_write_failed';
                    $v['state']='conflicts'; self::persist($v);
                    return ['ok'=>false,'reason'=>'apply_partial_requires_restore','id'=>$id];
                }
            }
            unset($entry);
            $v['state']='active';
            $v['audit'][]=['at'=>time(),'event'=>'applied','count'=>count($v['entries'])];
            self::persist($v);
            if (function_exists('wp_schedule_single_event')) {
                wp_schedule_single_event($v['end_utc'],'byl_bf2026_restore_sale_prices');
            }
            return ['ok'=>true,'state'=>'active','count'=>count($v['entries'])];
        });
    }

    public static function restore(string $reason='manual'): array {
        return self::exclusive(static function() use($reason) {
            $v=self::ledger();
            if (!in_array(($v['state']??''),['active','applying','restoring','conflicts'],true)) {
                return ['ok'=>false,'reason'=>'not_active'];
            }
            $v['state']='restoring';self::persist($v);
            $restored=0;$conflicts=[];
            foreach ($v['entries'] as $id=>&$entry) {
                if (($entry['phase']??'')==='restored') {continue;}
                if (($entry['phase']??'')==='prepared') {
                    // No prices were changed for this product.
                    $entry['phase']='restored';self::persist($v);continue;
                }
                $p=self::product((int)$id,$entry['sku']);
                // A crash may leave a journal record in 'applying' before
                // any Woo write. The exact original state needs no restore.
                if (($entry['phase']??'')==='applying' && $p
                    && BYL_BF2026_R10_Price_Policy::snapshot($p)===$entry['before']) {
                    $entry['phase']='restored';$entry['restored_at']=time();
                    self::persist($v);++$restored;continue;
                }
                $check=BYL_BF2026_R10_Price_Policy::restore_check($p,$entry);
                if (!$check['ok']) {
                    $conflicts[]=['id'=>(int)$id,'reason'=>$check['reason']];
                    $entry['failure']=$check['reason'];self::persist($v);continue;
                }
                try {
                    $before=$entry['before'];
                    $p->set_sale_price($before['sale_price']);
                    $p->set_date_on_sale_from($before['sale_from_utc']);
                    $p->set_date_on_sale_to($before['sale_to_utc']);
                    $p->save();
                    $again=BYL_BF2026_R10_Price_Policy::snapshot($p);
                    if ($again['sale_price']!==$before['sale_price']
                        || $again['sale_from_utc']!==$before['sale_from_utc']
                        || $again['sale_to_utc']!==$before['sale_to_utc']) {
                        throw new RuntimeException('restore_readback_mismatch');
                    }
                    $entry['phase']='restored';$entry['restored_at']=time();
                    unset($entry['failure']);++$restored;self::persist($v);
                } catch (Throwable $e) {
                    $conflicts[]=['id'=>(int)$id,'reason'=>'restore_write_or_readback_failed'];
                    $entry['failure']='restore_write_or_readback_failed';self::persist($v);
                }
            }
            unset($entry);
            $v['state']=$conflicts?'conflicts':'restored';
            $v['audit'][]=['at'=>time(),'event'=>'restore','reason'=>$reason,
                'count'=>$restored,'conflicts'=>count($conflicts)];
            self::persist($v);
            // Product categories intentionally persist for /clearance/ history.
            return ['ok'=>!$conflicts,'state'=>$v['state'],'restored'=>$restored,
                'conflicts'=>$conflicts];
        });
    }
}
