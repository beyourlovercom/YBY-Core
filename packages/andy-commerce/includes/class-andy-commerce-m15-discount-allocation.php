<?php
/**
 * M15 deterministic SKU-level discount allocation for review and isolated UAT.
 * No Woo hooks, order writes, coupon application or payment side effects.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class Andy_Commerce_M15_Discount_Allocation {
    public static function plan(
        string $type,
        array $rule,
        array $nativeLines,
        int $projectedUsdMinor
    ): array {
        if (!in_array($type,['spend_save','bogo','bundles','tiered'],true)
            || $projectedUsdMinor < 1 || $projectedUsdMinor > 100000000
            || !$nativeLines || count($nativeLines)>100) {
            throw new InvalidArgumentException('Unverified M15 allocation scope');
        }
        $byId=[];
        foreach($nativeLines as $line) {
            if(!is_array($line)||!is_int($line['product_id']??null)
                ||($line['product_id']??0)<1 ||isset($byId[$line['product_id']])
                || !is_int($line['quantity']??null)||$line['quantity']<1||$line['quantity']>1000
                ||!is_int($line['discounted_line_minor']??null)
                ||$line['discounted_line_minor']<0||$line['discounted_line_minor']>100000000
                ||($line['eligible_for_promotion']??null)!==true) {
                throw new InvalidArgumentException('Unverified eligible native Woo line');
            }
            $byId[$line['product_id']]=$line;
        }
        $basis=[];$rewardUnits=null;
        if($type==='bogo') {
            $buy=$rule['buy_product_id']??null;$reward=$rule['get_product_id']??null;
            $buyQty=$rule['buy_qty']??null;$getQty=$rule['get_qty']??null;
            if(!is_int($buy)||!is_int($reward)||!is_int($buyQty)||!is_int($getQty)
                ||$buy===$reward||$buyQty<1||$getQty<1
                ||!isset($byId[$buy],$byId[$reward])) {
                throw new InvalidArgumentException('BOGO SKU/quantity not provable');
            }
            $groups=min(
                intdiv($byId[$buy]['quantity'],$buyQty),
                intdiv($byId[$reward]['quantity'],$getQty)
            );
            if($groups<1)throw new InvalidArgumentException('BOGO not eligible');
            $discountedUnits=min($byId[$reward]['quantity'],$groups*$getQty);
            // Keep reward attribution strictly on the actual reward SKU, never on buyer.
            $rewardBasis=intdiv(
                $byId[$reward]['discounted_line_minor']*$discountedUnits,
                $byId[$reward]['quantity']
            );
            if($projectedUsdMinor>$rewardBasis) {
                throw new InvalidArgumentException('BOGO discount exceeds reward SKU basis');
            }
            $basis[$reward]=$rewardBasis;
            $rewardUnits=$discountedUnits;
        } elseif($type==='bundles') {
            $ids=$rule['product_ids']??null;
            if(!is_array($ids)||count($ids)<2||count($ids)>20||count(array_unique($ids,SORT_REGULAR))!==count($ids)) {
                throw new InvalidArgumentException('Unverified Bundle SKU set');
            }
            foreach($ids as $id) {
                if(!is_int($id)||!isset($byId[$id]))
                    throw new InvalidArgumentException('Bundle line missing from Woo cart');
                $basis[$id]=$byId[$id]['discounted_line_minor'];
            }
        } else {
            foreach($byId as $id=>$line)$basis[$id]=$line['discounted_line_minor'];
        }
        ksort($basis,SORT_NUMERIC);
        $total=array_sum($basis);
        if($total<1||$projectedUsdMinor>$total)
            throw new InvalidArgumentException('Discount exceeds eligible SKU basis');
        // Cent-exact largest-remainder weighted allocation with stable product-ID ties.
        $alloc=[];$rank=[];$used=0;
        foreach($basis as $id=>$amount) {
            $numerator=$projectedUsdMinor*$amount;
            $whole=intdiv($numerator,$total);
            $alloc[$id]=$whole;$used+=$whole;
            $rank[]=['id'=>$id,'remainder'=>$numerator%$total];
        }
        usort($rank, static fn($a,$b):int =>
            ($b['remainder']<=>$a['remainder']) ?: ($a['id']<=>$b['id'])
        );
        for($i=0;$i<$projectedUsdMinor-$used;$i++) $alloc[$rank[$i]['id']]++;
        $out=[];$check=0;
        foreach($alloc as $id=>$amount){
            if($amount<0||$amount>$basis[$id])throw new LogicException('Invalid cent allocation');
            $qty=$byId[$id]['quantity'];
            $discountedQty=$type==='bogo' ? $rewardUnits : $qty;
            $whole=intdiv($amount,$discountedQty);
            $remainder=$amount%$discountedQty;
            $units=[];
            for($u=0;$u<$qty;$u++) {
                $units[]=$u<$discountedQty ? $whole+($u<$remainder?1:0) : 0;
            }
            $out[]=[
                'product_id'=>$id,'quantity'=>$qty,'discounted_quantity'=>$discountedQty,
                'eligible_net_usd_minor'=>$basis[$id],
                'discount_usd_minor'=>$amount,'unit_discounts_usd_minor'=>$units,
                'remaining_line_usd_minor'=>$basis[$id]-$amount,
            ];
            $check+=$amount;
        }
        if($check!==$projectedUsdMinor)throw new LogicException('M15 minor units do not reconcile');
        return [
            'mode'=>'M15_SKU_ALLOCATION_READ_ONLY',
            'promotion_type'=>$type,'projected_discount_usd_minor'=>$projectedUsdMinor,
            'allocated_discount_usd_minor'=>$check,
            'sku_lines'=>$out,'checkout_mutations'=>0,
        ];
    }
}
