<?php
/**
 * Andy Commerce Currency OS M1 — deterministic USD-ledger quote preview.
 *
 * This service is pure and intentionally has no WooCommerce hooks, DB writes,
 * payment gateway calls, remote FX fetches, or external plugin dependencies.
 * It is NOT a settlement/multi-currency checkout implementation.
 */
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Andy_Commerce_Currency_OS_V1 {
    public const RATE_SCALE = 1000000;
    public const SUPPORTED = ['USD', 'EUR', 'GBP', 'AUD', 'CAD'];
    public const MAX_USD_MINOR = 10000000000; // USD 100m is above normal carts.

    private static function minor($value, string $field): int {
        if ( ! is_int($value) || $value < 0 || $value > self::MAX_USD_MINOR ) {
            throw new InvalidArgumentException('Invalid USD minor units: ' . $field);
        }
        return $value;
    }

    public static function convert_usd_minor(int $amount, int $rate_micros): int {
        self::minor($amount, 'amount');
        if ($rate_micros < 100000 || $rate_micros > 10000000) {
            throw new InvalidArgumentException('Unsupported FX rate; fail closed');
        }
        if ($amount > intdiv(PHP_INT_MAX - 500000, $rate_micros)) {
            throw new OverflowException('FX multiplication overflow');
        }
        return intdiv(($amount * $rate_micros) + 500000, self::RATE_SCALE);
    }

    /**
     * Explicit immutable exchange-rate snapshot, supplied by a future
     * authenticated rate provider; never fetched here.
     */
    public static function quote(array $request): array {
        $currency = $request['currency'] ?? null;
        $rate = $request['rate_micros'] ?? null;
        if ( ! is_string($currency) || ! in_array($currency, self::SUPPORTED, true)
            || ! is_int($rate) || ($currency === 'USD' && $rate !== self::RATE_SCALE) ) {
            throw new InvalidArgumentException('Unsupported currency or missing USD FX snapshot');
        }
        if ( ! isset($request['rate_source'], $request['rate_timestamp'])
            || ! is_string($request['rate_source']) || ! preg_match('/^[A-Za-z0-9._:-]{3,64}$/', $request['rate_source'])
            || ! is_int($request['rate_timestamp']) || $request['rate_timestamp'] < 1 ) {
            throw new InvalidArgumentException('Missing auditable rate source/timestamp');
        }
        $threshold = self::minor($request['threshold_usd_minor'] ?? null, 'threshold');
        $shippingUsd = self::minor($request['shipping_usd_minor'] ?? null, 'shipping');
        $taxUsd = self::minor($request['tax_usd_minor'] ?? null, 'tax');
        $lines = $request['lines'] ?? null;
        if ( ! is_array($lines) || !$lines || count($lines) > 100 ) {
            throw new InvalidArgumentException('Cart line count out of bounds');
        }
        $grossUsd = $netUsd = $eligibleUsd = $grossLocal = $discountLocal = $netLocal = 0;
        $details = [];
        foreach ($lines as $index => $line) {
            if ( ! is_array($line) ) {
                throw new InvalidArgumentException('Malformed line');
            }
            $qty = $line['qty'] ?? null;
            if ( ! is_int($qty) || $qty < 1 || $qty > 1000 ) {
                throw new InvalidArgumentException('Invalid quantity');
            }
            $unit = self::minor($line['unit_usd_minor'] ?? null, 'unit');
            $discount = self::minor($line['discount_usd_minor'] ?? null, 'discount');
            if ($unit > intdiv(self::MAX_USD_MINOR, $qty)) {
                throw new OverflowException('Line quantity overflow');
            }
            $gross = $unit * $qty;
            if ($discount > $gross) {
                throw new InvalidArgumentException('Discount exceeds line value');
            }
            $net = $gross - $discount;
            $eligible = ($line['eligible_for_promotion'] ?? false) === true
                && ($line['free_gift'] ?? false) !== true;
            if ($grossUsd > self::MAX_USD_MINOR - $gross
                || $netUsd > self::MAX_USD_MINOR - $net
                || ($eligible && $eligibleUsd > self::MAX_USD_MINOR - $net)) {
                throw new OverflowException('Cart balance overflow');
            }
            $grossUsd += $gross;
            $netUsd += $net;
            if ($eligible) { $eligibleUsd += $net; }

            $localGross = self::convert_usd_minor($gross, $rate);
            $localNet = self::convert_usd_minor($net, $rate);
            $grossLocal += $localGross;
            $netLocal += $localNet;
            $discountLocal += $localGross - $localNet;
            $details[] = [
                'index' => $index, 'qty' => $qty, 'eligible_for_promotion' => $eligible,
                'gross_usd_minor' => $gross, 'discount_usd_minor' => $discount,
                'net_usd_minor' => $net,
                'gross_local_minor' => $localGross,
                'discount_local_minor' => $localGross - $localNet,
                'net_local_minor' => $localNet,
            ];
        }
        if ($netUsd > self::MAX_USD_MINOR - $shippingUsd
            || $netUsd + $shippingUsd > self::MAX_USD_MINOR - $taxUsd) {
            throw new OverflowException('Order balance overflow');
        }
        $localShipping = self::convert_usd_minor($shippingUsd, $rate);
        $localTax = self::convert_usd_minor($taxUsd, $rate);
        $payableLocal = $netLocal + $localShipping + $localTax;

        return [
            'mode' => 'DEV_SHADOW_NO_PAYMENT',
            'currency' => $currency,
            'base_currency' => 'USD',
            'fx_snapshot' => [
                'rate_micros' => $rate,
                'source' => $request['rate_source'],
                'timestamp' => $request['rate_timestamp'],
            ],
            'lines' => $details,
            'gross_usd_minor' => $grossUsd,
            'discount_usd_minor' => $grossUsd - $netUsd,
            'eligible_subtotal_usd_minor' => $eligibleUsd,
            'threshold_usd_minor' => $threshold,
            'promotion_eligible' => $eligibleUsd >= $threshold,
            'merchandise_net_usd_minor' => $netUsd,
            'shipping_usd_minor' => $shippingUsd,
            'tax_usd_minor' => $taxUsd,
            'accounting_total_usd_minor' => $netUsd + $shippingUsd + $taxUsd,
            'gross_local_minor' => $grossLocal,
            'discount_local_minor' => $discountLocal,
            'merchandise_net_local_minor' => $netLocal,
            'shipping_local_minor' => $localShipping,
            'tax_local_minor' => $localTax,
            'payable_local_minor' => $payableLocal,
        ];
    }
}
