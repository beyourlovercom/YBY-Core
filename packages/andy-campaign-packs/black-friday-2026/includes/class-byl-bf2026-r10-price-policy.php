<?php
/**
 * Black Friday 2026 price policy. Pure, deterministic after a one-time draw.
 * No hooks, no products mutated, no WordPress state modified.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class BYL_BF2026_R10_Price_Policy {
    public const PERCENT_TIERS = [15,20,25,30,35,40,45,50];

    public static function draw_percent(): int {
        return self::PERCENT_TIERS[random_int(0,count(self::PERCENT_TIERS)-1)];
    }

    public static function plan(string $regular, string $previous_sale,
        string $current, int $percent, int $decimals=2): array {
        if ($decimals<0 || $decimals>4 || !in_array($percent,self::PERCENT_TIERS,true)
            || !is_numeric($regular) || !is_finite((float)$regular)
            || (float)$regular<=0 || (float)$regular>10000000) {
            return ['ok'=>false,'reason'=>'invalid_regular_or_tier'];
        }
        if ($previous_sale!=='' && (!is_numeric($previous_sale)
            || !is_finite((float)$previous_sale) || (float)$previous_sale<0)) {
            return ['ok'=>false,'reason'=>'invalid_previous_sale'];
        }
        if ($current!=='' && (!is_numeric($current)
            || !is_finite((float)$current) || (float)$current<0)) {
            return ['ok'=>false,'reason'=>'invalid_current_price'];
        }
        $original=(float)$regular;
        $proposed=round($original*(100-$percent)/100,$decimals);
        $current_numeric=$current===''?$original:(float)$current;
        $target=min($proposed,$current_numeric);
        if ($target<0 || $target>=$original) {
            return ['ok'=>false,'reason'=>'invalid_target'];
        }
        $format=static fn(float $price)=>number_format($price,$decimals,'.','');
        return [
            'ok'=>true,
            'tier_percent'=>$percent,
            'regular_price'=>$format($original),
            'previous_sale_price'=>$previous_sale,
            'previous_current_price'=>$current,
            'campaign_sale_price'=>$format($target),
            'effective_discount_percent'=>round(100*(1-$target/$original),2),
            'never_raise_existing_effective_price'=>$target <= $current_numeric,
        ];
    }

    public static function snapshot($product): array {
        if (!is_object($product) || !method_exists($product,'get_id')
            || !method_exists($product,'get_sku')
            || !method_exists($product,'get_regular_price')
            || !method_exists($product,'get_sale_price')
            || !method_exists($product,'get_price')) {
            return ['ok'=>false,'reason'=>'invalid_product'];
        }
        $datetime=static function ($date): ?int {
            return is_object($date) && method_exists($date,'getTimestamp')
                ? (int)$date->getTimestamp() : null;
        };
        return [
            'ok'=>true,
            'id'=>(int)$product->get_id(),
            'sku'=>(string)$product->get_sku(),
            'regular_price'=>(string)$product->get_regular_price('edit'),
            'sale_price'=>(string)$product->get_sale_price('edit'),
            'current_price'=>(string)$product->get_price('edit'),
            'sale_from_utc'=>$datetime($product->get_date_on_sale_from('edit')),
            'sale_to_utc'=>$datetime($product->get_date_on_sale_to('edit')),
        ];
    }

    /** Never restore over changes another operator has made during the event. */
    public static function restore_check($product, array $entry): array {
        if (($entry['phase']??'')!=='applied' || !is_array($entry['before']??null)
            || !is_array($entry['after']??null)) {
            return ['ok'=>false,'reason'=>'no_applied_snapshot'];
        }
        $current=self::snapshot($product);
        if (!$current['ok'] || $current['id'] !== ($entry['before']['id']??null)
            || $current['sku'] !== ($entry['before']['sku']??null)
            || $current['regular_price'] !== ($entry['before']['regular_price']??null)
            || $current['sale_price'] !== ($entry['after']['campaign_sale_price']??null)
            || $current['sale_from_utc']!==null || $current['sale_to_utc']!==null) {
            return ['ok'=>false,'reason'=>'concurrent_product_edit_conflict'];
        }
        return ['ok'=>true,'reason'=>'restore_exact_snapshot'];
    }
}
