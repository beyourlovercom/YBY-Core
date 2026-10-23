<?php
/**
 * C3: read-only, explicit Woo-like cart snapshot to USD minor unit contract.
 * Callers must verify the Woo cart's USD currency, actual applied coupon allocation,
 * eligible line classification and shipping/tax totals before constructing snapshot.
 * Does not introspect WC(), mutate sessions, prices, taxes, shipping or orders.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
final class Andy_Commerce_C3_Cart_Snapshot_Adapter {
    public static function quote_request(array $snapshot, string $currency, int $fxMicro, string $source, int $timestamp): array {
        if (($snapshot['cart_empty'] ?? false) === true || ($snapshot['verified_woo_data'] ?? false) !== true) {
            throw new InvalidArgumentException('Empty or unverified Woo cart snapshot');
        }
        if (($snapshot['base_currency'] ?? null)!=='USD'
            || !is_array($snapshot['lines'] ?? null)
            || count($snapshot['lines'])<1 || count($snapshot['lines'])>100
            || !is_int($snapshot['shipping_usd_minor'] ?? null)
            || !is_int($snapshot['tax_usd_minor'] ?? null)
            || !is_int($snapshot['threshold_usd_minor'] ?? null)) {
            throw new InvalidArgumentException('Invalid or unverified USD cart snapshot');
        }
        $lines=[];
        foreach($snapshot['lines'] as $line) {
            if (!is_array($line)
                || !is_int($line['quantity'] ?? null) || $line['quantity']<1
                || !is_int($line['unit_usd_minor'] ?? null)
                || !is_int($line['applied_discount_usd_minor'] ?? null)
                || !is_bool($line['eligible_for_promotion'] ?? null)
                || !is_bool($line['free_gift'] ?? null)
                || !is_bool($line['special_shipping'] ?? null)) {
                throw new InvalidArgumentException('Incomplete USD cart line contract');
            }
            $lines[]=[
                'qty'=>$line['quantity'],
                'unit_usd_minor'=>$line['unit_usd_minor'],
                'discount_usd_minor'=>$line['applied_discount_usd_minor'],
                'eligible_for_promotion'=>$line['eligible_for_promotion'] && !$line['special_shipping'],
                'free_gift'=>$line['free_gift'],
            ];
        }
        return [
            'currency'=>$currency, 'rate_micros'=>$fxMicro,
            'rate_source'=>$source,'rate_timestamp'=>$timestamp,
            'threshold_usd_minor'=>$snapshot['threshold_usd_minor'],
            'shipping_usd_minor'=>$snapshot['shipping_usd_minor'],
            'tax_usd_minor'=>$snapshot['tax_usd_minor'],
            'lines'=>$lines,
        ];
    }
}
