<?php
/**
 * M4 BYL49 V2 Dev-only approved Canary.
 * One Woo shipping availability authority at a time. Legacy policy persisted
 * data and source remain intact, no DB migration or WordPress admin changes.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class Andy_Commerce_M4_V2_Dev_Canary {
    public const OPTION_NAME = 'andy_commerce_m4_v2_dev_canary_gate_v1';
    public const TOKEN = 'BYL49_V2_DEV_ONLY_20261012';
    public const TARGET_BASIS = 'post_discount_merchandise_subtotal_excluding_tax_and_shipping';

    public static function environment_ok(): bool {
        return defined('WP_ENVIRONMENT_TYPE')
            && in_array(WP_ENVIRONMENT_TYPE, ['staging','development'], true)
            && function_exists('home_url')
            && parse_url(home_url(), PHP_URL_HOST) === 'dev.beyourlover.com';
    }

    public static function fingerprint(array $settings): string {
        return hash('sha256', wp_json_encode($settings, JSON_UNESCAPED_SLASHES));
    }

    /** Fail-closed against concurrent changes to the v1 global/zone policy. */
    public static function enabled(): bool {
        if (!self::environment_ok() || !class_exists('Andy_Commerce_Shipping_Promotion_Policy')) return false;
        $gate=get_option(self::OPTION_NAME, false);
        if (!is_array($gate) || ($gate['token']??null)!==self::TOKEN
            || ($gate['enabled']??null)!==true
            || ($gate['expires_utc']??0) < time()
            || ($gate['expires_utc']??0) > time() + DAY_IN_SECONDS
            || !is_string($gate['v1_fingerprint']??null)) return false;
        $settings=get_option(Andy_Commerce_Shipping_Promotion_Policy::OPTION_NAME, null);
        return is_array($settings)
            && ($settings['eligibility_basis']??null) === Andy_Commerce_Shipping_Promotion_Policy::ELIGIBILITY_BASIS
            && self::fingerprint($settings) === $gate['v1_fingerprint'];
    }

    /** Only the old PHP hook may be replaced; unknown hooks prevent takeover. */
    public static function boot(): bool {
        if (!self::enabled() || !class_exists('Andy_Commerce_Shipping_Promotion_Runtime')) return false;
        $hook='woocommerce_shipping_free_shipping_is_available';
        global $wp_filter;
        $callbacks=$wp_filter[$hook]->callbacks??[];
        $count=0;
        foreach ($callbacks as $rows) $count+=count($rows);
        if ($count!==1 || has_filter($hook,
            [Andy_Commerce_Shipping_Promotion_Runtime::class,'filter_free_shipping'])!==10) return false;
        if (!remove_filter($hook,
            [Andy_Commerce_Shipping_Promotion_Runtime::class,'filter_free_shipping'],10)) return false;
        add_filter($hook,[self::class,'filter_free_shipping'],10,3);
        return true;
    }

    public static function is_gift(array $line): bool {
        foreach (['andy_commerce_auto_gift','andy_commerce_gift','byl_auto_gift',
            'yby_auto_gift','is_free_gift','free_gift','gift'] as $key) {
            if (!empty($line[$key])) return true;
        }
        return false;
    }

    /** Amounts arrive from server Woo cart line_total, never browser inputs. */
    public static function amount_minor(array $items,int $decimals): ?int {
        if ($decimals<0 || $decimals>4 || count($items)<1 || count($items)>1000) return null;
        $factor=10**$decimals;
        $total=0;
        foreach ($items as $line) {
            if (!is_array($line) || !array_key_exists('line_total',$line)) return null;
            $raw=$line['line_total'];
            if ((!is_string($raw) && !is_int($raw) && !is_float($raw))
                || !is_numeric($raw) || (float)$raw<0 || !is_finite((float)$raw)
                || (float)$raw>10000000) return null;
            if (self::is_gift($line)) continue;
            $minor=(int)round(((float)$raw)*$factor);
            $total+=$minor;
            if ($total>1000000000) return null;
        }
        return $total;
    }

    /** Pure per-zone threshold calculation. No offers, Woo rate creation or writes. */
    public static function evaluate(array $items,float $threshold,int $decimals): array {
        $base=['eligible'=>false,'confirmed'=>false,'qualified_minor'=>null,
            'threshold_minor'=>null,'remaining_minor'=>null];
        if (!is_finite($threshold) || $threshold<0 || $threshold>10000000
            || $decimals<0 || $decimals>4) return $base;
        $amount=self::amount_minor($items,$decimals);
        if ($amount===null) return $base;
        $thresholdMinor=(int)round($threshold*(10**$decimals));
        if ($thresholdMinor>1000000000) return $base;
        return ['eligible'=>$amount >= $thresholdMinor,'confirmed'=>true,
            'qualified_minor'=>$amount,'threshold_minor'=>$thresholdMinor,
            'remaining_minor'=>max(0,$thresholdMinor-$amount)];
    }

    /** Only called by Woo for the requested shipping package on authorized Dev. */
    public static function filter_free_shipping($prior,$package,$shipping_method): bool {
        if (!self::enabled() || !Andy_Commerce_Shipping_Promotion_Policy::is_runtime_active()
            || !function_exists('WC') || !WC() || !WC()->cart
            || !method_exists(WC()->cart,'get_cart') || !class_exists('WC_Shipping_Zones')
            || !is_array($package)) return (bool)$prior;
        // Multiple shipping packages never flatten into a fictional rate.
        $shipping=WC()->shipping();
        if (!$shipping || !method_exists($shipping,'get_packages')
            || count((array)$shipping->get_packages())!==1) return false;
        $zone=WC_Shipping_Zones::get_zone_matching_package($package);
        if (!is_object($zone) || !method_exists($zone,'get_id')) return false;
        $settings=Andy_Commerce_Shipping_Promotion_Policy::get_persisted_settings();
        if (!is_array($settings)) return false;
        $threshold=Andy_Commerce_Shipping_Promotion_Policy::get_persisted_zone_effective_threshold((int)$zone->get_id());
        if ($threshold===null) return false;
        $decimals=function_exists('wc_get_price_decimals')?(int)wc_get_price_decimals():2;
        $status=self::evaluate((array)WC()->cart->get_cart(),(float)$threshold,$decimals);
        if (!$status['confirmed'] || !$status['eligible']) return false;
        if (($settings['mode']??null)==='automatic') return true;
        if (($settings['mode']??null)!=='require_code') return false;
        $code=Andy_Commerce_Shipping_Promotion_Policy::normalize_code((string)$settings['free_shipping_code']);
        foreach ((array)WC()->cart->get_applied_coupons() as $applied) {
            if ($code===Andy_Commerce_Shipping_Promotion_Policy::normalize_code((string)$applied)) return true;
        }
        return false;
    }
}
