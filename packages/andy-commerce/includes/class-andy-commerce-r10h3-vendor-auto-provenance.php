<?php
/**
 * R10-H3: exact-vendor AUTO provenance readout, with NO Woo registration.
 * Source-only, no coupon/cart/session/option writes and no manual-origin grant.
 * Caller must supply server-side WC Cart/Session objects, not browser data.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class Andy_Commerce_R10H3_Vendor_Auto_Provenance {
    private const EXACT = [
        'woocommerce/woocommerce.php' => '11.2.1',
        'yaycurrency-pro/yay-currency.php' => '3.3.5',
        'wt-smart-coupon-pro/wt-smart-coupon-pro.php' => '3.2.0',
        'woocommerce-smart-coupons/woocommerce-smart-coupons.php' => '9.51.0',
    ];

    private static function result(string $status, ?string $provider=null): array {
        return [
            'status'=>$status,
            'provider'=>$provider,
            'origin_verified'=>$status==='auto_verified',
            'can_replace'=>$status==='auto_verified',
            'writes_performed'=>0,
        ];
    }

    private static function same_code($a,$b): bool {
        if (!is_string($a)||!is_string($b)||strlen($a)>160||strlen($b)>160) return false;
        if (function_exists('wc_is_same_coupon')) return wc_is_same_coupon($a,$b);
        return strcasecmp(trim($a),trim($b))===0;
    }

    private static function claims($raw,string $code): ?bool {
        if (!is_array($raw)||count($raw)>100) return null;
        $matched=false;
        $seen=[];
        foreach ($raw as $value) {
            if (!is_string($value) || strlen($value)>160) return null;
            $canonical=strtolower(trim($value));
            if ($canonical==='' || isset($seen[$canonical])) return null;
            $seen[$canonical]=true;
            if (self::same_code($value,$code)) $matched=true;
        }
        return $matched;
    }

    private static function exact_stack(): bool {
        if (!defined('WC_VERSION') || WC_VERSION!=='11.2.1') return false;
        // WP admin plugin-file API is not necessarily loaded on the public
        // cart. Loading declarations here changes no plugin or site setting.
        if ((!function_exists('get_plugins') || !function_exists('is_plugin_active'))
            && defined('ABSPATH') && is_file(ABSPATH.'wp-admin/includes/plugin.php')) {
            require_once ABSPATH.'wp-admin/includes/plugin.php';
        }
        if (!function_exists('get_plugins') || !function_exists('is_plugin_active')) return false;
        $plugins=get_plugins();
        if(!is_array($plugins))return false;
        foreach(self::EXACT as $slug=>$version) {
            if(!is_plugin_active($slug) || ($plugins[$slug]['Version']??null)!==$version)return false;
        }
        return true;
    }

    /**
     * This read is authoritative only about a vendor's EXISTING session
     * history, NOT about whether it is currently eligible for auto-apply.
     * Neither a coupon checkbox nor eligible-auto lists are trusted.
     * If both vendors claim a code, or a claim is stale, reject.
     */
    public static function inspect($code,$cart,$session): array {
        if (!is_string($code)||trim($code)===''||strlen($code)>160
            || !self::exact_stack()
            || !is_object($session)||!is_callable([$session,'get'])
            || !is_object($cart)||!is_callable([$cart,'get_applied_coupons'])) {
            return self::result('unverified_runtime');
        }
        $applied=$cart->get_applied_coupons();
        if (!is_array($applied)||count($applied)>50) return self::result('unverified_cart');
        $present=false;$seen=[];
        foreach($applied as $value) {
            if(!is_string($value)||strlen($value)>160)return self::result('unverified_cart');
            $canonical=strtolower(trim($value));
            if($canonical==='' || isset($seen[$canonical]))return self::result('unverified_cart');
            $seen[$canonical]=true;
            if(self::same_code($code,$value))$present=true;
        }
        if(!$present)return self::result('not_applied');

        // WebToffee 3.2.0 tracks auto-applied codes under this WC session key.
        // StoreApps 9.51.0 tracks actual auto-applied codes under a DISTINCT
        // key, not wc_sc_auto_apply_coupon_ids (eligibility list).
        $wt=self::claims($session->get('auto_applied_coupons',[]),$code);
        $sa=self::claims($session->get('wc_sc_auto_applied_coupons',[]),$code);
        if ($wt===null||$sa===null) return self::result('unverified_vendor_session');
        if($wt && $sa)return self::result('ambiguous_dual_vendor');
        if(!$wt && !$sa)return self::result('not_auto_proven');

        if($sa){
            if(!class_exists('WC_SC_Auto_Apply_Coupon',false)
                || !is_callable(['WC_SC_Auto_Apply_Coupon','get_instance']))return self::result('unverified_vendor_runtime');
            $obj=WC_SC_Auto_Apply_Coupon::get_instance();
            if(!is_object($obj)||!is_callable([$obj,'is_coupon_applied_by_auto_apply'])
                || $obj->is_coupon_applied_by_auto_apply($code)!==true) {
                return self::result('unverified_vendor_runtime');
            }
            return self::result('auto_verified','storeapps');
        }
        return self::result('auto_verified','webtoffee');
    }
}
