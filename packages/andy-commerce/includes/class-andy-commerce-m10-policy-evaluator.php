<?php
/** Dev-only deterministic policy prefilter. Cannot activate promotions. */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
final class Andy_Commerce_M10_Policy_Evaluator {
    public static function check(array $draft, array $snapshot):array {
        if (($draft['active']??null)!==false || ($draft['status']??null)!=='draft') {
            return ['eligible'=>false,'reason'=>'not_inactive_draft'];
        }
        $policy=$draft['policy']??[];
        if (!is_array($policy))return ['eligible'=>false,'reason'=>'invalid_policy'];
        try {
            $policy=Andy_Commerce_M10_Policy_Fields::normalize([
                'start_date'=>$policy['start_date']??'',
                'end_date'=>$policy['end_date']??'',
                'product_scope'=>$policy['product_scope']??'all',
                'product_ids'=>implode(',',(array)($policy['product_ids']??[])),
                'stack_policy'=>$policy['stack_policy']??'best_single',
            ]);
        } catch (Throwable $e) {
            return ['eligible'=>false,'reason'=>'invalid_policy'];
        }
        $today=$snapshot['date_utc']??null;
        if(!is_string($today)||!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$today))
            return ['eligible'=>false,'reason'=>'unverified_date'];
        if($policy['start_date']!==''&&$today<$policy['start_date'])
            return ['eligible'=>false,'reason'=>'not_started'];
        if($policy['end_date']!==''&&$today>$policy['end_date'])
            return ['eligible'=>false,'reason'=>'expired'];
        if($policy['product_scope']==='selected') {
            $ids=$snapshot['product_ids']??null;
            if(!is_array($ids)||!$ids)return ['eligible'=>false,'reason'=>'unverified_products'];
            foreach($ids as $id)if(!is_int($id)||$id<=0)
                return ['eligible'=>false,'reason'=>'unverified_products'];
            if(!array_intersect($policy['product_ids'],$ids))
                return ['eligible'=>false,'reason'=>'product_excluded'];
        }
        if(($snapshot['manual_coupon']??null)!==null&&
            ($snapshot['manual_coupon']??'')!=='' &&
            $policy['stack_policy']==='coupon_exclusive')
            return ['eligible'=>false,'reason'=>'coupon_conflict'];
        return ['eligible'=>true,'reason'=>'shadow_qualified','policy'=>$policy];
    }
}
