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

    /**
     * Read both records because Dev's active BYL legacy policy ($49) differs
     * from the dormant Andy Commerce v1 option ($69). A switch of ownership
     * is not itself authorized by the discount-basis Canary.
     */
    public static function snapshot(): ?array {
        $cls=Andy_Commerce_Shipping_Promotion_Policy::class;
        if (!self::environment_ok() || !class_exists($cls)
            || !method_exists($cls,'settings_source')
            || $cls::settings_source() !== 'byl-platform-legacy') return null;
        $preset=$cls::preset();
        $name=$preset['legacy_option_name']??null;
        if (!is_string($name) || $name!== 'byl_shipping_promotion_settings') return null;
        $effective=$cls::get_persisted_settings();
        $legacy=get_option($name,null);
        $commerce=get_option($cls::OPTION_NAME,null);
        if (!is_array($effective) || !is_array($legacy) || !is_array($commerce)
            || ($effective['eligibility_basis']??null)!==$cls::ELIGIBILITY_BASIS
            || ($legacy['eligibility_basis']??null)!==$cls::ELIGIBILITY_BASIS
            || ($commerce['eligibility_basis']??null)!==$cls::ELIGIBILITY_BASIS) return null;
        return [
            'effective_source'=>'byl-platform-legacy',
            'v1_fingerprint'=>self::fingerprint($effective),
            'legacy_raw_fingerprint'=>self::fingerprint($legacy),
            'commerce_v1_fingerprint'=>self::fingerprint($commerce),
        ];
    }

    /** Construct a candidate; does not persist options or modify hooks. */
    public static function candidate_gate(int $ttlSeconds=1800): ?array {
        if ($ttlSeconds < 60 || $ttlSeconds > 43200) return null;
        $snapshot=self::snapshot();
        if (!$snapshot) return null;
        return ['token'=>self::TOKEN,'enabled'=>true,
            'expires_utc'=>time()+$ttlSeconds]+$snapshot;
    }

    /** Fail closed on source switch, policy drift, expiry or tampered gate. */
    public static function enabled(): bool {
        if (!self::environment_ok()) return false;
        $gate=get_option(self::OPTION_NAME,false);
        if (!is_array($gate) || ($gate['token']??null)!==self::TOKEN
            || ($gate['enabled']??null)!==true
            || !is_int($gate['expires_utc']??null)
            || $gate['expires_utc'] < time()
            || $gate['expires_utc'] > time() + 43200) return false;
        $snapshot=self::snapshot();
        if (!$snapshot) return false;
        foreach($snapshot as $key=>$value){
            if (!isset($gate[$key]) || !is_string($gate[$key])
                || !hash_equals($value,$gate[$key])) return false;
        }
        return true;
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
            || !is_array($package)) return false;
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
