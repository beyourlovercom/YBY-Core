<?php
/**
 * BF2026 R13: guarded Woo-native cart changes.
 * Loaded as a class only; transaction writes require explicit Campaign runtime boot.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class BYL_BF2026_R13_Cart_Writer {
    private const PACK = 'byl-black-friday-2026';
    private static bool $busy = false;

    /** Inclusive Nov 10 – Nov 30 in America/Los_Angeles; Dec 1 is OFF. */
    public static function campaign_open(?int $at_utc=null): bool {
        $timezone=new DateTimeZone('America/Los_Angeles');
        $start=(new DateTimeImmutable('2026-11-10 00:00:00',$timezone))->getTimestamp();
        $end=(new DateTimeImmutable('2026-12-01 00:00:00',$timezone))->getTimestamp();
        $now=$at_utc??time();
        return $now>=$start && $now<$end;
    }

    public static function manifest_from_campaign(): array {
        if (!class_exists('BYL_BF2026_Gifts') || !class_exists('BYL_BF2026_R8_Readiness')) {
            return [];
        }
        $catalog=BYL_BF2026_Gifts::catalog();
        $approvals=BYL_BF2026_Gifts::gift_approvals();
        if (!$catalog || !$approvals) {return [];}
        $candidates=[];
        foreach($catalog as $id=>$cents) {
            $candidates[(int)$id]=['upgrade_minor'=>(int)$cents];
        }
        $readiness=BYL_BF2026_R8_Readiness::inspect($candidates,$approvals);
        if (($readiness['release_ready']??false)!==true) {return [];}
        $approved=[];
        foreach($readiness['rows'] as $row) {
            if (($row['status']??'')!=='APPROVED') {continue;}
            $id=(int)$row['id'];
            $p=wc_get_product($id);
            if (!$p) {continue;}
            $a=$approvals[$id]??[];
            $approved[$id]=[
                'product_id'=>$id,'sku'=>(string)$p->get_sku(),
                'approved'=>true,'warehouse_verified'=>true,
                'warehouse_slug'=>(string)($a['warehouse_code']??''),
                'available_units'=>(int)($a['warehouse_available_qty']??0),
                'allowed_countries'=>$a['allowed_countries']??[],
                'upgrade_minor'=>(int)$catalog[$id]
            ];
        }
        return $approved;
    }

    private static function gift(array $line): bool {
        return ($line['andy_campaign_pack_id']??null)===self::PACK
            && ($line['_byl_bf2026_gift']??null)===true;
    }

    private static function gift_meta(array $item): array {
        return [
            'andy_campaign_pack_id'=>self::PACK,
            '_byl_bf2026_gift'=>true,
            '_byl_bf2026_paid_slot'=>(int)$item['upgrade_minor']>0,
            '_byl_bf2026_source_usd_minor'=>(int)$item['upgrade_minor'],
            '_byl_bf2026_warehouse'=>(string)$item['warehouse_slug'],
            '_byl_bf2026_sku'=>(string)$item['sku']
        ];
    }

    private static function source_currency_ok(): bool {
        if (!function_exists('get_woocommerce_currency')
            || !function_exists('wc_get_price_decimals')
            || wc_get_price_decimals()!==2) {return false;}
        $currency=get_woocommerce_currency();
        if ($currency==='USD') {return true;}
        // Foreign-currency gifts require the existing audited source-USD quote;
        // NEVER reverse-convert Woo/YayCurrency display totals.
        return in_array($currency,['EUR','GBP','AUD','CAD'],true)
            && class_exists('Andy_Commerce_Campaign_USD_Basis_V1')
            && class_exists('Andy_Commerce_Currency_Runtime_Dev_V1')
            && Andy_Commerce_Currency_Runtime_Dev_V1::allowed()
            && Andy_Commerce_Currency_Runtime_Dev_V1::selected()===$currency;
    }

    private static function expected_checkout_price(int $source_usd_minor): ?float {
        if (!self::source_currency_ok()) {return null;}
        $price=number_format($source_usd_minor/100,2,'.','');
        if (get_woocommerce_currency()==='USD') {return (float)$price;}
        try {
            return (float)Andy_Commerce_Currency_Runtime_Dev_V1::convert($price);
        } catch(Throwable $e) {return null;}
    }

    public static function commit($cart,string $country,array $manifest,string $seed): array {
        if (self::$busy) {return ['ok'=>false,'reason'=>'cart_commit_busy'];}
        if (!self::source_currency_ok()
            || !class_exists('WC_Cart') || !$cart instanceof WC_Cart) {
            return ['ok'=>false,'reason'=>'source_currency_not_usd'];
        }
        if (!class_exists('BYL_BF2026_R12_Auto_Gift_Planner')) {
            return ['ok'=>false,'reason'=>'auto_planner_missing'];
        }
        $plan=BYL_BF2026_R12_Auto_Gift_Planner::plan($cart,$country,$manifest,$seed);
        if (!$plan['ok']) {return $plan;}
        if (!$plan['remove_keys'] && !$plan['add_free_ids']) {
            return ['ok'=>true,'changed'=>false,'plan'=>$plan];
        }
        $original=$cart->get_cart();
        $new=[];
        self::$busy=true;
        try {
            // Remove invalid/over-limit slots BEFORE adding replacements.
            // Woo's existing gift reconciler can recalculate during add_to_cart
            // and would otherwise remove the new gift for temporary overflow.
            foreach($plan['remove_keys'] as $key) {
                if (isset($original[$key]) && self::gift($original[$key])) {
                    if (!$cart->remove_cart_item($key)) {
                        throw new RuntimeException('remove_old_gift_failed');
                    }
                }
            }
            foreach($plan['add_free_ids'] as $id) {
                $item=$manifest[$id]??null;
                if (!is_array($item) || (int)$item['upgrade_minor']!==0) {
                    throw new RuntimeException('invalid_free_manifest');
                }
                $key=$cart->add_to_cart($id,1,0,[],self::gift_meta($item));
                if (!is_string($key) || $key==='') {
                    throw new RuntimeException('native_add_to_cart_failed');
                }
                $new[]=$key;
            }
            $cart->calculate_totals();
            $check=BYL_BF2026_R12_Auto_Gift_Planner::plan($cart,$country,$manifest,$seed);
            if (!$check['ok'] || $check['remove_keys'] || $check['add_free_ids']) {
                throw new RuntimeException('post_write_entitlement_mismatch');
            }
            return ['ok'=>true,'changed'=>true,'plan'=>$plan,'added_keys'=>$new];
        } catch(Throwable $e) {
            foreach($new as $key) {$cart->remove_cart_item($key);}
            // If old entries were removed before failure, recreate as needed.
            foreach($original as $key=>$line) {
                if (!self::gift($line) || isset($cart->get_cart()[$key])) {continue;}
                $id=(int)($line['product_id']??0);
                if ($id>0 && $id===($manifest[$id]['product_id']??0)) {
                    $restore=$cart->add_to_cart($id,1,0,[],self::gift_meta($manifest[$id]));
                    if (!$restore) {return ['ok'=>false,'reason'=>'rollback_incomplete'];}
                }
            }
            $cart->calculate_totals();
            return ['ok'=>false,'reason'=>'native_cart_transaction_failed'];
        } finally {
            self::$busy=false;
        }
    }

    /** Server-side premium selection: selected paid gift replaces a free slot. */
    public static function choose_premium($cart,string $country,int $id,array $manifest,string $seed): array {
        if (!self::source_currency_ok() || !class_exists('WC_Cart') || !$cart instanceof WC_Cart) {
            return ['ok'=>false,'reason'=>'source_currency_not_usd'];
        }
        $row=$manifest[$id]??null;
        if (!is_array($row) || !in_array(($row['upgrade_minor']??null),[1000,1500,2000],true)
            || strtoupper($country)!=='US') {
            return ['ok'=>false,'reason'=>'paid_gift_or_country_not_allowed'];
        }
        $quote=BYL_BF2026_R9C_Woo_Adapter::quote($cart,strtoupper($country));
        if (!$quote['ok'] || !$quote['entitlement']['premium_allowed']) {
            return ['ok'=>false,'reason'=>'premium_threshold_not_met'];
        }
        if (!function_exists('wc_get_product')
            || ($p=wc_get_product($id))===false
            || !$p->is_type('simple') || !$p->is_in_stock()
            || $p->get_sku()!==$row['sku'] || $p->backorders_allowed()
            || ($row['approved']??false)!==true
            || ($row['warehouse_verified']??false)!==true
            || !in_array('US',$row['allowed_countries']??[],true)) {
            return ['ok'=>false,'reason'=>'sku_warehouse_not_approved'];
        }
        $existing=$cart->get_cart();
        $old=[];
        foreach($existing as $key=>$line) {
            if (!self::gift($line)) {continue;}
            if ((int)$line['product_id']===$id && !empty($line['_byl_bf2026_paid_slot'])) {
                return ['ok'=>true,'changed'=>false,'reason'=>'already_selected'];
            }
            $old[$key]=$line;
        }
        // A paid selection replaces one free slot, not all free entitlements.
        $paidKeys=array_keys(array_filter($old,static fn($l)=>!empty($l['_byl_bf2026_paid_slot'])));
        $freeKeys=array_keys(array_filter($old,static fn($l)=>empty($l['_byl_bf2026_paid_slot'])));
        $remove=$paidKeys[0]??$freeKeys[0]??null;
        $removed=$remove!==null?($old[$remove]??null):null;
        $key=null;
        self::$busy=true;
        try {
            // IMPORTANT: remove the old slot BEFORE adding the new premium.
            // Woo may recalculate during add_to_cart, and the legacy gift
            // reconciler would otherwise revoke the new line as slot overflow.
            if ($remove!==null && !$cart->remove_cart_item($remove)) {
                throw new RuntimeException('paid_old_slot_remove_failed');
            }
            $key=$cart->add_to_cart($id,1,0,[],self::gift_meta($row));
            if (!is_string($key) || $key==='') {throw new RuntimeException('paid_add_failed');}
            $cart->calculate_totals();
            $result=$cart->get_cart()[$key]??null;
            if (!is_array($result) || !self::gift($result)
                || (int)$result['product_id']!==$id
                || empty($result['_byl_bf2026_paid_slot'])
                || ($expected=self::expected_checkout_price((int)$row['upgrade_minor']))===null
                || abs((float)($result['line_total']??-1)-$expected)>0.02) {
                throw new RuntimeException('premium_native_readback_mismatch');
            }
            return ['ok'=>true,'changed'=>true,'selected_id'=>$id,'upgrade_minor'=>$row['upgrade_minor']];
        } catch(Throwable $e) {
            if (is_string($key) && $key!=='') {$cart->remove_cart_item($key);}
            if (is_array($removed) && self::gift($removed)) {
                $original_id=(int)($removed['product_id']??0);
                if ($original_id>0 && isset($manifest[$original_id])) {
                    $restored=$cart->add_to_cart($original_id,1,0,[],self::gift_meta($manifest[$original_id]));
                    if (!$restored) {return ['ok'=>false,'reason'=>'paid_upgrade_rollback_incomplete'];}
                }
            }
            $cart->calculate_totals();
            return ['ok'=>false,'reason'=>'paid_upgrade_native_cart_failed'];
        } finally {self::$busy=false;}
    }

    private static function eligible_coupon($coupon): bool {
        if (!is_object($coupon) || !method_exists($coupon,'get_id')
            || !function_exists('apply_filters')) {return false;}
        $ids=apply_filters('byl_bf2026_verified_influencer_coupon_ids',[]);
        return is_array($ids) && in_array((int)$coupon->get_id(),
            array_values(array_filter($ids,'is_int')),true);
    }

    private static function sale_ids(): array {
        // Snapshot of the 16 already-approved actual WWW promo item IDs.
        // Runtime option/filter can replace it with a newly verified manifest.
        $fallback=[9096,25358,14188,34737,17625,32509,9109,9137,
                   9095,9141,9118,9123,9119,31967,32993,32685];
        if (!function_exists('apply_filters')) {return $fallback;}
        $ids=apply_filters('byl_bf2026_active_sale_product_ids',$fallback);
        return is_array($ids)?array_values(array_filter($ids,static fn($id)=>
            is_int($id) && $id>0 && $id<100000000)):[];
    }

    public static function coupon_line($valid,$product,$coupon,$cart_item) {
        if (!$valid || !self::eligible_coupon($coupon)) {return $valid;}
        $ids=self::sale_ids();
        $p=is_object($product) && method_exists($product,'get_id')
            ? (int)$product->get_id() : (int)($cart_item['product_id']??0);
        $parent=is_object($product) && method_exists($product,'get_parent_id')
            ? (int)$product->get_parent_id() : (int)($cart_item['variation_id']??0);
        return in_array($p,$ids,true) || ($parent>0 && in_array($parent,$ids,true))
            ? false : $valid;
    }

    public static function coupon_items($items,$coupon,$discounts) {
        if (!is_array($items) || !self::eligible_coupon($coupon)) {return $items;}
        $ids=self::sale_ids();
        return array_values(array_filter($items,static function($item) use($ids) {
            $line=is_object($item)?($item->object??null):null;
            if (!is_array($line) || self::gift($line)) {return false;}
            $p=(int)($line['product_id']??0);
            $v=(int)($line['variation_id']??0);
            return !in_array($p,$ids,true) && ($v<=0 || !in_array($v,$ids,true));
        }));
    }

    /** Called only by BYL_BF2026_Gifts::boot() after Andy Campaign eligibility is granted. */
    public static function boot(): void {
        add_action('woocommerce_before_calculate_totals',[__CLASS__,'gift_unit_reprice'],90);
        add_action('woocommerce_after_calculate_totals',[__CLASS__,'auto_sync'],95);
        add_action('woocommerce_checkout_create_order_line_item',[__CLASS__,'order_meta'],95,4);
        add_action('woocommerce_after_checkout_validation',[__CLASS__,'validate_classic'],95,2);
        add_action('woocommerce_checkout_validate_order_before_payment',[__CLASS__,'validate_store_api'],95,2);
        // Only exact verified AffiliateWP coupon IDs are excluded from BF sale lines.
        add_filter('woocommerce_coupon_is_valid_for_product',[__CLASS__,'coupon_line'],95,4);
        add_filter('woocommerce_coupon_get_items_to_apply',[__CLASS__,'coupon_items'],95,3);
    }

    private static function wc_country(): string {
        if (!function_exists('WC') || !WC() || !WC()->customer) {return '';}
        $c=WC()->customer;
        return method_exists($c,'get_shipping_country')
            ? strtoupper(trim((string)$c->get_shipping_country())) : '';
    }

    private static function seed(): string {
        if (!function_exists('WC') || !WC() || !WC()->session) {return '';}
        $seed=WC()->session->get('_byl_bf26_session_gift_seed');
        if (!is_string($seed) || strlen($seed)<16) {
            try {$seed=bin2hex(random_bytes(24));}
            catch(Throwable $e) {return '';}
            WC()->session->set('_byl_bf26_session_gift_seed',$seed);
        }
        return $seed;
    }

    /** Safe idempotent auto-issue from a known destination; no IP-based inference. */
    public static function auto_sync($cart): void {
        if (self::$busy || !self::source_currency_ok()) {return;}
        if (!class_exists('BYL_BF2026_Gifts') || !BYL_BF2026_Gifts::ready()) {return;}
        $country=self::wc_country();
        if (preg_match('/^[A-Z]{2}$/D',$country)!==1) {return;}
        $manifest=self::manifest_from_campaign();
        if (!$manifest) {return;}
        $seed=self::seed();
        if ($seed==='') {return;}
        $result=self::commit($cart,$country,$manifest,$seed);
        if (!$result['ok'] && function_exists('WC') && WC() && WC()->session) {
            WC()->session->set('_byl_bf26_last_gift_issue',(string)($result['reason']??'unknown'));
        }
    }

    private static function issue($cart,string $country): string {
        if (!class_exists('BYL_BF2026_Gifts') || !BYL_BF2026_Gifts::ready()) {
            return ''; // campaign is not currently offering gifts
        }
        if (preg_match('/^[A-Z]{2}$/D',strtoupper($country))!==1) {
            return 'Please enter your shipping country to confirm your complimentary gift.';
        }
        $manifest=self::manifest_from_campaign();
        $seed=self::seed();
        if (!$manifest || $seed==='') {
            return 'Black Friday gifts cannot be confirmed at this time. Please contact support.';
        }
        $plan=BYL_BF2026_R12_Auto_Gift_Planner::plan($cart,$country,$manifest,$seed);
        if (!$plan['ok'] || $plan['remove_keys'] || $plan['add_free_ids']) {
            return 'Please update your cart to confirm the Black Friday gift selection.';
        }
        return '';
    }

    public static function validate_classic($posted,$errors): void {
        if (!function_exists('WC') || !WC() || !WC()->cart
            || !is_object($errors) || !method_exists($errors,'add')) {return;}
        $country=is_array($posted) ? (string)($posted['shipping_country']??'') : '';
        $reason=self::issue(WC()->cart,strtoupper($country));
        if ($reason!=='') {$errors->add('bf2026_gift_allocation',$reason);}
    }

    public static function validate_store_api($order,$errors): void {
        if (!function_exists('WC') || !WC() || !WC()->cart
            || !is_object($errors) || !method_exists($errors,'add')) {return;}
        $country=is_object($order) && method_exists($order,'get_shipping_country')
            ? (string)$order->get_shipping_country() : '';
        $reason=self::issue(WC()->cart,strtoupper($country));
        if ($reason!=='') {$errors->add('bf2026_gift_allocation',$reason);}
    }

    public static function order_meta($item,$key,$values,$order): void {
        if (!is_array($values) || !self::gift($values)
            || !is_object($item) || !method_exists($item,'add_meta_data')) {return;}
        foreach (['_byl_bf2026_warehouse','_byl_bf2026_sku',
                  '_byl_bf2026_source_usd_minor','_byl_bf2026_paid_slot'] as $field) {
            if (array_key_exists($field,$values)) {
                $item->add_meta_data($field,$values[$field],true);
            }
        }
    }

    /** Authenticated POST controller delegates session nonce to Gifts::handle_post(). */
    public static function choose_from_form($cart,int $id): array {
        if (!class_exists('BYL_BF2026_Gifts') || !BYL_BF2026_Gifts::ready()) {
            return ['ok'=>false,'reason'=>'campaign_not_ready'];
        }
        $manifest=self::manifest_from_campaign();
        $country=self::wc_country();
        $seed=self::seed();
        if (!$manifest || $seed==='' || $country==='') {
            return ['ok'=>false,'reason'=>'gift_manifest_or_shipping_country_missing'];
        }
        return self::choose_premium($cart,$country,$id,$manifest,$seed);
    }

    public static function gift_unit_reprice($cart): void {
        if (!self::source_currency_ok() || !class_exists('WC_Cart')
            || !$cart instanceof WC_Cart) {return;}
        $manifest=self::manifest_from_campaign();
        foreach($cart->get_cart() as $line) {
            if (!is_array($line) || !self::gift($line)) {continue;}
            $id=(int)($line['product_id']??0);
            if (!isset($manifest[$id]) || !isset($line['data'])
                || !is_object($line['data']) || !method_exists($line['data'],'set_price')) {
                continue;
            }
            $line['data']->set_price(number_format($manifest[$id]['upgrade_minor']/100,2,'.',''));
        }
    }
}
