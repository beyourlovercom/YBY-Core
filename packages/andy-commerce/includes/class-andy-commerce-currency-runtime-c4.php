<?php
/**
 * C-R4/C-R5: strictly disposable local Woo runtime proof.
 * Fixed USD coupons and flat shipping are quoted once in the shopper currency.
 * No effect when C1 guard is false; percentage discounts remain untouched.
 * Does NOT implement a general production-ready tax/shipping FX engine.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
final class Andy_Commerce_Currency_Runtime_C4 {
    private static array $shippingRateConversions = [];
    /** Trusted Woo flat-rate original quote, including evaluated dynamic formulas. */
    public static function source_shipping_quote(string $rateId): ?array {
        if (!Andy_Commerce_Currency_Runtime_C1::allowed() || !WC()->session) return null;
        $v=WC()->session->get('aicc_r13_woo_source_shipping_quotes',[]);
        return is_array($v) && is_array($v[$rateId]??null)?$v[$rateId]:null;
    }
    public static function boot(): void {
        if (!Andy_Commerce_Currency_Runtime_C1::allowed()) return;
        add_filter('woocommerce_coupon_get_amount', [__CLASS__, 'coupon'], 60, 2);
        add_filter('woocommerce_package_rates', [__CLASS__, 'shipping_rates'], 60, 2);
        add_action('rest_api_init', [__CLASS__, 'register_test_route'], 30);
    }
    public static function to_local_minor(int $usdMinor): int {
        if ($usdMinor < 0 || $usdMinor > Andy_Commerce_Currency_OS_V1::MAX_USD_MINOR) {
            throw new InvalidArgumentException('Invalid base USD minor units');
        }
        return Andy_Commerce_Currency_OS_V1::convert_usd_minor(
            $usdMinor,
            Andy_Commerce_Currency_Runtime_C1::quote(Andy_Commerce_Currency_Runtime_C1::test_currency())['rate_micros']
        );
    }
    private static function minor($raw): int {
        if (!is_scalar($raw) || !preg_match('/^(?:0|[1-9][0-9]{0,7})(?:\\.[0-9]{1,2})?$/D', (string)$raw)) {
            throw new InvalidArgumentException('Unverified USD amount');
        }
        [$w,$f]=array_pad(explode('.', (string)$raw, 2),2,'');
        return (int)$w*100+(int)str_pad($f,2,'0');
    }
    public static function coupon($value,$coupon) {
        if (!Andy_Commerce_Currency_Runtime_C1::allowed() || !($coupon instanceof WC_Coupon)) return $value;
        if (!in_array($coupon->get_discount_type('edit'), ['fixed_cart','fixed_product'], true)) return $value;
        $converted=self::to_local_minor(self::minor($value));
        return number_format($converted/100,2,'.','');
    }
    public static function shipping_rates($rates,$package) {
        if (!Andy_Commerce_Currency_Runtime_C1::allowed() || !is_array($rates)) return $rates;
        foreach ($rates as $key=>$rate) {
            if (!($rate instanceof WC_Shipping_Rate)) continue;
            if ($rate->get_method_id()!=='flat_rate') continue;
            $objectId=spl_object_id($rate);
            if (isset(self::$shippingRateConversions[$objectId])) continue;
            $sourceMinor=self::minor($rate->get_cost());
            $local=self::to_local_minor($sourceMinor);
            // Woo's flat-rate net shipping price is stored in base USD.
            // For native taxable rates recompute the destination shipping tax
            // from WC_Tax instead of retaining the stale USD tax allocation.
            // Inclusive *shipping prices* are a separate release gate.
            if (wc_string_to_bool(apply_filters('woocommerce_shipping_prices_include_tax',false))) continue;
            $oldTaxes=$rate->get_taxes();
            $shippingTaxRates=WC_Tax::get_shipping_tax_rates();
            if ($oldTaxes && !$shippingTaxRates) continue; // deny uncertain rate context
            $localPrice=number_format($local/100,2,'.','');
            $newTaxes=$shippingTaxRates ? WC_Tax::calc_shipping_tax($localPrice,$shippingTaxRates) : [];
            $rate->set_cost($localPrice);
            $rate->set_taxes($newTaxes);
            if (WC()->session) {
                $quotes=WC()->session->get('aicc_r13_woo_source_shipping_quotes',[]);
                if(!is_array($quotes))$quotes=[];
                $q=Andy_Commerce_Currency_Runtime_C1::quote(Andy_Commerce_Currency_Runtime_C1::test_currency());
                $quotes[$rate->get_id()]=['rate_id'=>$rate->get_id(),
                    'source_usd_minor'=>$sourceMinor,'native_minor'=>$local,
                    'currency'=>$q['currency'],'rate_micros'=>$q['rate_micros'],
                    'snapshot_hash'=>$q['snapshot_hash'],'provider'=>'WOO_NATIVE_FLAT_RATE',
                    'evaluated_dynamic'=>true];
                WC()->session->set('aicc_r13_woo_source_shipping_quotes',$quotes);
            }
            self::$shippingRateConversions[$objectId]=true;
        }
        return $rates;
    }
    public static function register_test_route(): void {
        if (!Andy_Commerce_Currency_Runtime_C1::allowed()) return;
        register_rest_route('aicc-currency-test/v1','/select',[
            'methods'=>'POST',
            'permission_callback'=>static function(WP_REST_Request $request): bool {
                return wp_verify_nonce((string)$request->get_header('X-AICC-Nonce'),'aicc_currency_test_selection')!==false;
            },
            'callback'=>static function(WP_REST_Request $request) {
                $code=$request->get_param('currency');
                if (!is_string($code) || !array_key_exists($code,Andy_Commerce_Currency_Runtime_C1::TEST_RATES)) {
                    return new WP_Error('aicc_currency_invalid','Invalid currency', ['status'=>400]);
                }
                if (!Andy_Commerce_Currency_Runtime_C1::select_for_sandbox($code)) {
                    return new WP_Error('aicc_currency_session','No isolated Woo session', ['status'=>409]);
                }
                return rest_ensure_response(['currency'=>get_woocommerce_currency(),'sandbox_only'=>true]);
            }
        ]);
    }
}
