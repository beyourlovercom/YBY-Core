<?php
/** R10-H3-C native Woo rollback completeness detector, read-only. */
declare(strict_types=1);
if(!defined('ABSPATH'))exit;

final class Andy_Commerce_R10H3C_Full_Rollback_Shadow {
    private const VENDOR_KEYS=[
        'auto_applied_coupons',
        'wc_sc_auto_applied_coupons',
        'wc_sc_auto_apply_coupons',
        'chosen_shipping_methods',
    ];
    private static function valid($value,$depth=0):bool {
        if($depth>7)return false;
        if($value===null||is_bool($value)||is_int($value))return true;
        if(is_float($value))return is_finite($value);
        if(is_string($value))return strlen($value)<=10000;
        if(!is_array($value)||count($value)>350)return false;
        foreach($value as $k=>$v) {
            if(!is_int($k)&&(!is_string($k)||strlen($k)>180))return false;
            if(!self::valid($v,$depth+1))return false;
        }
        return true;
    }
    public static function capture($cart,$session):?array {
        if(!is_object($cart)||!is_callable([$cart,'get_applied_coupons'])
            ||!is_callable([$cart,'get_totals'])
            ||!is_object($session)||!is_callable([$session,'get'])
            ||!function_exists('wc_get_notices'))return null;
        $coupons=$cart->get_applied_coupons();
        $totals=$cart->get_totals();
        $notices=wc_get_notices();
        if(!is_array($coupons)||count($coupons)>50
            ||!self::valid($coupons)||!is_array($totals)||!self::valid($totals)
            ||!is_array($notices)||!self::valid($notices))return null;
        $sessionValues=[];
        foreach(self::VENDOR_KEYS as $key) {
            $value=$session->get($key,null);
            if(!self::valid($value))return null;
            $sessionValues[$key]=$value;
        }
        return [
            'coupon_order'=>array_values($coupons),
            'native_cart_totals'=>$totals,
            'woocommerce_notices'=>$notices,
            'vendor_history_and_shipping'=>$sessionValues,
        ];
    }
    public static function exactly_restored($before,$after):bool {
        return is_array($before)&&is_array($after)&&$before===$after;
    }
}
