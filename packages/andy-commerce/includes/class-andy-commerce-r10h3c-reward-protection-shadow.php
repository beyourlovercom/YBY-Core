<?php
/**
 * R10-H3-C exact vendor reward classification, source-only / no bootstrap.
 * Fail closed when vendor data are unknown: no consent to remove coupons.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

final class Andy_Commerce_R10H3C_Reward_Protection_Shadow {
    private const CORE = ['percent','fixed_cart','fixed_product'];
    private const VENDOR_TYPES = [
        'smart_coupon'=>'storeapps_credit',
        'store_credit'=>'webtoffee_credit',
        'wt_sc_bogo'=>'webtoffee_bogo',
        'wbte_sc_bogo'=>'webtoffee_legacy_bogo',
    ];
    private const KNOWN_AUTO_META = [
        'wc_sc_auto_apply_coupon','wc_sc_user_role_ids',
        '_wt_make_auto_coupon','_wbte_sc_auto_coupon_priority',
        'customer_email',
    ];
    private static function outcome(string $status,string $reason): array {
        return ['status'=>$status,'reason'=>$reason,'allow_ordinary_removal'=>$status==='ordinary',
                'writes_performed'=>0];
    }
    public static function classify($coupon,$exact_vendor_versions):array {
        if (!is_object($coupon) || !($coupon instanceof WC_Coupon)
            || $exact_vendor_versions!==true
            || !is_callable([$coupon,'get_discount_type'])
            || !is_callable([$coupon,'get_free_shipping'])
            || !is_callable([$coupon,'get_amount']))return self::outcome('unknown','not_verified');
        if($coupon->get_free_shipping()===true)return self::outcome('protected','native_free_shipping');
        $type=$coupon->get_discount_type();
        if (!is_string($type))return self::outcome('unknown','unknown_type');
        if (isset(self::VENDOR_TYPES[$type]))
            return self::outcome('protected',self::VENDOR_TYPES[$type]);
        if(!in_array($type,self::CORE,true))return self::outcome('unknown','unknown_type');
        $amount=$coupon->get_amount();
        if(!is_numeric($amount) || !is_finite((float)$amount) || (float)$amount<0)
            return self::outcome('unknown','invalid_amount');
        if ((float)$amount==0.0)return self::outcome('protected','zero_value');

        // A generic fixed_cart discount can conceal custom vendor entitlements.
        // Refuse any vendor-specific data not explicitly known to be ordinary
        // coupon auto-apply/restriction metadata. No guessed gift removal.
        $id=is_callable([$coupon,'get_id'])?(int)$coupon->get_id():0;
        if($id>0) {
            if(!function_exists('get_post_meta'))return self::outcome('unknown','metadata_unreadable');
            $meta=get_post_meta($id);
            if(!is_array($meta))return self::outcome('unknown','metadata_unreadable');
            foreach(array_keys($meta) as $key) {
                if (!is_string($key))return self::outcome('unknown','metadata_invalid');
                if (in_array($key,self::KNOWN_AUTO_META,true))continue;
                if (preg_match('/^(?:_?wt_|_?wbte_|wc_sc_|_wc_sc_)/i',$key))
                    return self::outcome('protected','vendor_custom_entitlement_or_unknown_meta');
            }
        }
        return self::outcome('ordinary','verified_native_ordinary');
    }
}
