<?php
/**
 * BF2026 R17: six fixed, owner-approved Woo sale prices with exact reversible ledger.
 * Independent of Gift and Andy Campaign Master. No randomized discount logic.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class BYL_BF2026_R17_Fixed_Price {
    private const LEDGER = 'byl_bf2026_r17_fixed_prices_v1';
    private const LOCK = 'byl_bf2026_r17_price_lock';
    private const ERROR = 'byl_bf2026_r17_price_error';
    private const VERSION = 'byl_bf2026_r17_price_initialized';
    private const HOOK = 'byl_bf2026_r17_restore_fixed_prices';
    private const RELEASE = '0.2.4';
    private const TARGETS = [
        9141  => ['sku'=>'TW158',                  'regular'=>'99.00',  'expected'=>'69.00', 'target'=>'59.00'],
        9118  => ['sku'=>'BX288A shining version', 'regular'=>'109.00', 'expected'=>'79.00', 'target'=>'69.00'],
        9096  => ['sku'=>'BX033B',                 'regular'=>'119.00', 'expected'=>'69.00', 'target'=>'59.00'],
        30454 => ['sku'=>'BY489A-Lubes',          'regular'=>'19.90',  'expected'=>'19.90','target'=>'9.90', 'no_sale_ok'=>true],
        9098  => ['sku'=>'TX223A',                 'regular'=>'69.00',  'expected'=>'69.00', 'target'=>'55.00', 'no_sale_ok'=>true],
        29799 => ['sku'=>'HX029A',                 'regular'=>'49.00',  'expected'=>'49.00', 'target'=>'39.00', 'no_sale_ok'=>true],
    ];
    public static function ledger(): array {
        $s=get_option(self::LEDGER, []);
        return is_array($s) ? $s : [];
    }
    private static function save(array $s): void {
        if (update_option(self::LEDGER, $s, false) === false &&
            get_option(self::LEDGER, null) !== $s) {
            throw new RuntimeException('ledger_save_failed');
        }
    }
    public static function end_utc(): int {
        return (new DateTimeImmutable('2026-12-01 00:00:00',
            new DateTimeZone('America/Los_Angeles')))->getTimestamp();
    }
    private static function start_utc(): int {
        return (new DateTimeImmutable('2026-11-10 00:00:00',
            new DateTimeZone('America/Los_Angeles')))->getTimestamp();
    }
    private static function money($value): string {
        return is_numeric($value) ? number_format((float)$value, 2, '.', '') : '';
    }
    private static function product(int $id, array $rule) {
        if (!function_exists('wc_get_product')) { return false; }
        $p=wc_get_product($id);
        return $p && $p->is_type('simple') && (string)$p->get_sku()===$rule['sku'] ? $p : false;
    }
    private static function snapshot($p): array {
        $date=static fn($d): ?int => is_object($d) && method_exists($d,'getTimestamp')
            ? (int)$d->getTimestamp():null;
        return [
            'regular'=>(string)$p->get_regular_price('edit'),
            'sale'=>(string)$p->get_sale_price('edit'),
            'current'=>(string)$p->get_price('edit'),
            'from'=>$date($p->get_date_on_sale_from('edit')),
            'to'=>$date($p->get_date_on_sale_to('edit')),
        ];
    }
    private static function with_lock(callable $callback): array {
        if (!add_option(self::LOCK, time(), '', false)) {
            return ['ok'=>false,'reason'=>'price_operation_locked'];
        }
        try { return $callback(); } finally { delete_option(self::LOCK); }
    }
    public static function activate(): array {
        $result=self::apply();
        if (!$result['ok']) {
            // Best effort immediate rollback of any partially-applied campaign prices.
            if (in_array((self::ledger()['state']??''),['conflicts','applying'],true)) {
                $rollback=self::restore('activation_failed_rollback');
                if (!$rollback['ok']) {
                    $result['reason']='rollback_conflict_'.implode('_',$rollback['conflicts']??[]);
                }
            }
            update_option(self::ERROR, $result['reason']??'activation_failed', false);
            if (function_exists('wp_die') && !defined('BYL_BF2026_R17_TEST_MODE')) {
                wp_die('Black Friday product prices were NOT changed: ' .
                    esc_html($result['reason']??'unknown') . '. Correct the products before activation.',
                    'Black Friday price preflight failed', ['response'=>409]);
            }
            return $result;
        }
        delete_option(self::ERROR);
        update_option(self::VERSION, self::RELEASE, false);
        return $result;
    }
    public static function maybe_apply_after_update(): void {
        if (get_option(self::VERSION, '')===self::RELEASE
            || !function_exists('current_user_can') || !current_user_can('manage_woocommerce')) {return;}
        // WordPress plugin ZIP replacements on active plugins may not fire activation hooks.
        $result=self::apply();
        if ($result['ok']) {
            delete_option(self::ERROR);
            update_option(self::VERSION, self::RELEASE, false);
        } else {
            if (in_array((self::ledger()['state']??''),['conflicts','applying'],true)) {
                $rollback=self::restore('upgrade_failed_rollback');
                if (!$rollback['ok']) {
                    $result['reason']='rollback_conflict_'.implode('_',$rollback['conflicts']??[]);
                }
            }
            update_option(self::ERROR,$result['reason']??'update_failed',false);
        }
    }
    public static function apply(): array {
        if (!class_exists('WooCommerce') || !function_exists('get_woocommerce_currency')
            || get_woocommerce_currency()!=='USD') {
            return ['ok'=>false,'reason'=>'requires_woocommerce_usd'];
        }
        $now=time();
        if ($now<self::start_utc() || $now>=self::end_utc()) {
            return ['ok'=>false,'reason'=>'outside_2026_nov10_nov30_window'];
        }
        return self::with_lock(static function() {
            $prior=self::ledger();
            if (($prior['state']??'')==='active') {
                return ['ok'=>true,'state'=>'already_active','count'=>count($prior['items']??[])];
            }
            if (in_array(($prior['state']??''),['prepared','applying','restoring','conflicts'],true)) {
                return ['ok'=>false,'reason'=>'unresolved_previous_price_operation'];
            }
            // Never write prices if old R10 experimental ledger has pending work.
            if (class_exists('BYL_BF2026_R10_Sale_Manager')) {
                $old=BYL_BF2026_R10_Sale_Manager::ledger();
                if (in_array(($old['state']??''),['prepared','active','applying','restoring','conflicts'],true)) {
                    return ['ok'=>false,'reason'=>'old_sale_ledger_unresolved'];
                }
            }
            $items=[];
            foreach (self::TARGETS as $id=>$rule) {
                $p=self::product($id,$rule);
                if (!$p || !$p->is_purchasable() || !$p->is_in_stock()) {
                    return ['ok'=>false,'reason'=>'identity_or_stock_mismatch_'.$id];
                }
                $before=self::snapshot($p);
                // Three full-price products may have no explicit Woo Sale Price.
                // Preserve that absence in the exact original ledger, rather
                // than writing the regular price into the sale field on restore.
                $valid_sale = !empty($rule['no_sale_ok'])
                    ? ($before['sale']==='' ||
                        self::money($before['sale'])===$rule['regular'])
                    : self::money($before['sale'])===$rule['expected'];
                if (self::money($before['regular'])!==$rule['regular']
                    || !$valid_sale
                    || self::money($before['current'])!==$rule['expected']
                    || (float)$rule['target']>=(float)$rule['expected']) {
                    return ['ok'=>false,'reason'=>'unexpected_original_price_'.$id];
                }
                $items[$id]=['sku'=>$rule['sku'],'before'=>$before,
                    'target'=>$rule['target'],'phase'=>'prepared'];
            }
            $s=['version'=>1,'state'=>'prepared','created_at'=>time(),
                'end_utc'=>self::end_utc(),'items'=>$items];
            self::save($s);
            // Fail before ANY product mutation if scheduled restoration is unavailable.
            if (!function_exists('wp_next_scheduled') ||
                !function_exists('wp_schedule_single_event')) {
                return ['ok'=>false,'reason'=>'scheduler_unavailable_no_prices_changed'];
            }
            if (!wp_next_scheduled(self::HOOK)) {
                $scheduled=wp_schedule_single_event(self::end_utc(),self::HOOK);
                if ($scheduled!==true) {
                    return ['ok'=>false,'reason'=>'scheduler_failed_no_prices_changed'];
                }
            }
            $s['state']='applying';self::save($s);
            foreach (self::TARGETS as $id=>$rule) {
                $entry=&$s['items'][$id];
                $entry['phase']='applying';self::save($s);
                try {
                    $p=self::product($id,$rule);
                    if (!$p || self::snapshot($p)!==$entry['before']) {
                        throw new RuntimeException('product_changed_during_apply');
                    }
                    $p->set_date_on_sale_from(null);
                    $p->set_date_on_sale_to(null);
                    $p->set_sale_price($entry['target']);
                    $p->save();
                    $fresh=self::product($id,$rule);
                    $actual=$fresh?self::snapshot($fresh):[];
                    if (self::money($actual['sale']??null)!==self::money($entry['target'])
                        || self::money($actual['current']??null)!==self::money($entry['target'])) {
                        throw new RuntimeException('sale_readback_failed');
                    }
                    $entry['phase']='applied';$entry['applied_at']=time();self::save($s);
                } catch (Throwable $e) {
                    $entry['failure']=$e->getMessage();
                    $s['state']='conflicts';self::save($s);
                    return ['ok'=>false,'reason'=>'price_apply_partial_restore_required_'.$id];
                }
                unset($entry);
            }
            $s['state']='active';$s['activated_at']=time();self::save($s);
            if (function_exists('wp_next_scheduled') && function_exists('wp_schedule_single_event')
                && !wp_next_scheduled(self::HOOK)) {
                wp_schedule_single_event(self::end_utc(),self::HOOK);
            }
            return ['ok'=>true,'state'=>'active','count'=>count($items)];
        });
    }
    public static function restore(string $reason='manual'): array {
        return self::with_lock(static function() use($reason) {
            $s=self::ledger();
            if (($s['state']??'')==='restored' || !$s) {
                return ['ok'=>true,'state'=>'already_restored','count'=>0];
            }
            if (!in_array(($s['state']??''),['prepared','active','applying','restoring','conflicts'],true)) {
                return ['ok'=>false,'reason'=>'invalid_restore_state'];
            }
            $s['state']='restoring';self::save($s);
            $count=0;$conflicts=[];
            foreach ($s['items'] as $id=>&$entry) {
                if (($entry['phase']??'')==='restored') {continue;}
                $rule=self::TARGETS[(int)$id]??null;
                $p=$rule?self::product((int)$id,$rule):false;
                if (!$p || !is_array($entry['before']??null)) {
                    $conflicts[]=(int)$id;continue;
                }
                $current=self::snapshot($p);$before=$entry['before'];
                if ($current===$before) {
                    $entry['phase']='restored';$entry['restored_at']=time();self::save($s);
                    continue;
                }
                if (self::money($current['regular'])!==self::money($before['regular'])
                    || self::money($current['sale'])!==self::money($entry['target'])
                    || self::money($current['current'])!==self::money($entry['target'])
                    || $current['from']!==null || $current['to']!==null) {
                    $entry['failure']='concurrent_external_price_change';
                    $conflicts[]=(int)$id;self::save($s);continue;
                }
                try {
                    $p->set_sale_price($before['sale']);
                    $p->set_date_on_sale_from($before['from']);
                    $p->set_date_on_sale_to($before['to']);
                    $p->save();
                    $fresh=self::product((int)$id,$rule);
                    $now=$fresh?self::snapshot($fresh):[];
                    if (self::money($now['sale']??null)!==self::money($before['sale'])
                        || $now['from']!==$before['from'] || $now['to']!==$before['to']) {
                        throw new RuntimeException('restore_readback_failed');
                    }
                    $entry['phase']='restored';$entry['restored_at']=time();++$count;
                    unset($entry['failure']);self::save($s);
                } catch (Throwable $e) {
                    $entry['failure']=$e->getMessage();
                    $conflicts[]=(int)$id;self::save($s);
                }
            }
            unset($entry);
            $s['state']=$conflicts?'conflicts':'restored';
            $s['restore_reason']=$reason;$s['restore_at']=time();
            $s['conflict_ids']=$conflicts;self::save($s);
            if (!$conflicts && function_exists('wp_clear_scheduled_hook')) {
                wp_clear_scheduled_hook(self::HOOK);
            }
            return ['ok'=>!$conflicts,'state'=>$s['state'],'count'=>$count,
                'conflicts'=>$conflicts];
        });
    }
    public static function expire_if_due(): void {
        $s=self::ledger();
        if (time()>=self::end_utc() &&
            in_array(($s['state']??''),['active','applying','conflicts'],true)) {
            self::restore('end_date_request');
        }
    }
    public static function deactivate(): void {
        $a=self::restore('plugin_deactivation');
        if (!$a['ok'] && function_exists('wp_die')) {
            wp_die('Black Friday prices could NOT all be restored; conflict IDs: ' .
                esc_html(implode(',', $a['conflicts']??[])) .
                '. Review price ledger before disabling this plugin.',
                'Black Friday restoration conflict', ['response'=>409]);
        }
    }
    public static function admin_notice(): void {
        $error=get_option(self::ERROR,'');
        if (!$error || !function_exists('current_user_can')
            || !current_user_can('manage_woocommerce')) {return;}
        echo '<div class="notice notice-error"><p>Black Friday price setup NOT applied: '.
            esc_html((string)$error).'. No price changes should be assumed.</p></div>';
    }
}