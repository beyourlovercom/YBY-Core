<?php
/**
 * M8: pure, deterministic OFF-by-default Commerce campaign evaluation.
 *
 * This class intentionally has no Woo hooks, WC_Cart mutations, coupons,
 * tax/shipping writes, inventory gifts or order side effects. Input values
 * are integer currency minor units supplied by a trusted server adapter.
 */
declare(strict_types=1);

final class Andy_Commerce_M8_Promotion_Evaluator {
    private const TYPES = ['bogo', 'bundles', 'spend_save', 'tiered', 'free_gift'];

    public static function preview(array $snapshot, array $drafts): array {
        $base = [
            'schema_version'=>1, 'mode'=>'simulation_only', 'applied'=>false,
            'runtime_enabled'=>false, 'gift_awarded'=>false,
            'qualified_basis'=>'post_discount_merchandise_excluding_tax_shipping_fees',
            'projected_discount_minor'=>0, 'selected_campaign'=>null,
            'evaluations'=>[], 'reason'=>'no_qualifying_draft'
        ];
        $lines=$snapshot['items']??null;
        $currency=$snapshot['currency']??null;
        $country=$snapshot['country']??null;
        $coupon=$snapshot['manual_coupon']??null;
        if (!is_array($lines)||count($lines)>100||!is_string($currency)
            ||!preg_match('/^[A-Z]{3}$/D',$currency)||!is_string($country)
            ||!preg_match('/^[A-Z]{2}$/D',$country)
            ||($coupon!==null&&!is_string($coupon))) {
            return $base+['invalid'=>true];
        }
        $items=[];$total=0;
        foreach($lines as $x) {
            if(!is_array($x)||!is_int($x['product_id']??null)
                ||($x['product_id']??0)<=0||!is_int($x['quantity']??null)
                ||$x['quantity']<=0||$x['quantity']>1000
                ||!is_int($x['discounted_line_minor']??null)
                ||$x['discounted_line_minor']<0||$x['discounted_line_minor']>100000000) {
                return $base+['invalid'=>true];
            }
            $product=$x['product_id'];
            $items[]=['product'=>$product,'qty'=>$x['quantity'],'amount'=>$x['discounted_line_minor']];
            $total+=$x['discounted_line_minor'];
            if($total>1000000000)return $base+['invalid'=>true];
        }
        if(!$items||count($drafts)>100)return $base+['invalid'=>true];
        $eligible=[];
        foreach($drafts as $i=>$draft) {
            if(!is_array($draft)||!in_array($draft['type']??null,self::TYPES,true)
                ||($draft['active']??false)!==false
                ||($draft['status']??'draft')!=='draft')continue;
            // Only inactive DRAFTS are evaluated; nothing becomes a live offer.
            $id=$draft['id']??null;
            if(!is_string($id)||!preg_match('/^[A-Za-z0-9_-]{1,64}$/D',$id))continue;
            if(($draft['currency']??'')!==$currency)continue;
            $countries=$draft['countries']??[];
            if(!is_array($countries)||!in_array($country,$countries,true))continue;
            if($coupon!==null&&$coupon!==''&&($draft['stack_with_coupon']??false)!==true)continue;
            $rule=$draft['rule']??null;
            if(!is_array($rule))continue;
            $saving=self::discount($draft['type'],$rule,$items,$total);
            if($saving===null)continue;
            $eligible[]=[
                'id'=>$id,'type'=>$draft['type'],
                'priority'=>is_int($draft['priority']??null)?$draft['priority']:0,
                'discount_minor'=>min($saving,$total),
            ];
        }
        usort($eligible,static function(array $a,array $b):int {
            return ($b['priority']<=>$a['priority']) ?: strcmp($a['id'],$b['id']);
        });
        // Deterministically choose ONE; never stack drafts/generate discounts twice.
        $winner=$eligible[0]??null;
        return array_merge($base, [
            'qualified_minor'=>$total,
            'projected_discount_minor'=>$winner['discount_minor']??0,
            'selected_campaign'=>$winner['id']??null,
            'evaluations'=>$eligible,
            'reason'=>$winner?'preview_only':'no_qualifying_draft'
        ]);
    }

    private static function amount($rule,string $key):?int {
        $v=$rule[$key]??null;
        return is_int($v)&&$v>=0&&$v<=100000000 ? $v:null;
    }

    private static function discount(string $type,array $rule,array $items,int $total):?int {
        $threshold=self::amount($rule,'threshold_minor');
        $discount=self::amount($rule,'discount_minor');
        $percent=self::amount($rule,'percent_bp');
        if($type==='spend_save') {
            if($threshold===null||$total<$threshold)return null;
            if($discount!==null)return min($discount,$total);
            if($percent!==null&&$percent<=10000)return intdiv($total*$percent,10000);
            return null;
        }
        if($type==='tiered') {
            $tiers=$rule['tiers']??null;
            if(!is_array($tiers)||count($tiers)>20)return null;
            $qty=array_sum(array_column($items,'qty'));
            $best=0;
            foreach($tiers as $tier) {
                if(!is_array($tier)||!is_int($tier['minimum_qty']??null)
                    ||!is_int($tier['percent_bp']??null)
                    ||$tier['minimum_qty']<1||$tier['percent_bp']<0
                    ||$tier['percent_bp']>10000) return null;
                if($qty >= $tier['minimum_qty']) $best=max($best,$tier['percent_bp']);
            }
            return intdiv($total*$best,10000);
        }
        if($type==='bogo') {
            $p=$rule['buy_product_id']??null;$buy=$rule['buy_qty']??null;
            $get=$rule['get_product_id']??null;$getQty=$rule['get_qty']??null;
            if(!is_int($p)||!is_int($get)||!is_int($buy)||!is_int($getQty)
                ||$p<=0||$get<=0||$buy<=0||$getQty<=0||$buy>1000||$getQty>1000)return null;
            $countBuy=0;$countGet=0;$getAmount=0;
            foreach($items as $item) {
                if($item['product']===$p)$countBuy+=$item['qty'];
                if($item['product']===$get){$countGet+=$item['qty'];$getAmount+=$item['amount'];}
            }
            // Reject identical buy/reward SKU: no self-qualifying gift loops.
            if($p===$get||$countGet===0)return null;
            $groups=min(intdiv($countBuy,$buy),intdiv($countGet,$getQty));
            if($groups<1)return null;
            $rewardUnits=min($countGet,$groups*$getQty);
            $basis=intdiv($getAmount*$rewardUnits,$countGet);
            if($percent!==null&&$percent<=10000)return intdiv($basis*$percent,10000);
            return $discount!==null?min($discount*$rewardUnits,$basis):null;
        }
        if($type==='bundles') {
            $products=$rule['product_ids']??null;
            if(!is_array($products)||count($products)<2||count($products)>20)return null;
            $ids=array_values(array_unique($products,SORT_REGULAR));
            if(count($ids)!==count($products))return null;
            $amount=0;
            foreach($ids as $id){
                if(!is_int($id)||$id<=0)return null;
                $found=false;
                foreach($items as $item) {
                    if($item['product']===$id) {$found=true;$amount+=$item['amount'];break;}
                }
                if(!$found)return null;
            }
            if($percent!==null&&$percent<=10000)return intdiv($amount*$percent,10000);
            return $discount!==null?min($discount,$amount):null;
        }
        // M8 never awards a physical gift; explicitly review-only.
        if($type==='free_gift')return null;
        return null;
    }
}
