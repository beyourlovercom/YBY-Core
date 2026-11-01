<?php
/**
 * R10-H3 read-only native transaction snapshot. Fail-close on incomplete
 * rollback, including ordered coupon list and vendor session state.
 * No Woo hook is registered and no cart/session write occurs.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class Andy_Commerce_R10H3_Rollback_Integrity_Shadow {
    private const SESSION_KEYS = [
        'auto_applied_coupons',
        'wc_sc_auto_applied_coupons',
        'wc_sc_auto_apply_coupons',
        'chosen_shipping_methods',
    ];
    private static function safe_value($value): bool {
        if ($value === null || is_string($value) && strlen($value)<=200) return true;
        if (!is_array($value) || count($value)>100) return false;
        foreach($value as $k=>$item) {
            if (!is_int($k) && (!is_string($k) || strlen($k)>80))return false;
            if (is_array($item)) {
                if (!self::safe_value($item))return false;
            } elseif (!is_string($item) && !is_int($item) && !is_float($item)
                && !is_bool($item) && $item!==null) return false;
            if (is_string($item) && strlen($item)>200)return false;
        }
        return true;
    }
    public static function capture($cart,$session): ?array {
        if(!is_object($cart)||!is_callable([$cart,'get_applied_coupons'])
            || !is_object($session)||!is_callable([$session,'get'])) return null;
        $coupons=$cart->get_applied_coupons();
        if(!is_array($coupons) || count($coupons)>50) return null;
        foreach($coupons as $code)if(!is_string($code) || strlen($code)>160)return null;
        $values=[];
        foreach(self::SESSION_KEYS as $key){
            $item=$session->get($key,null);
            if(!self::safe_value($item))return null;
            $values[$key]=$item;
        }
        return ['ordered_coupons'=>array_values($coupons),
            'vendor_and_shipping_sessions'=>$values];
    }
    public static function exact_rollback($before,$after): bool {
        return is_array($before) && is_array($after)
            && array_key_exists('ordered_coupons',$before)
            && array_key_exists('vendor_and_shipping_sessions',$before)
            && $before===$after;
    }
}
