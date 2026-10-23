<?php
/** Marketing M10 Dev-only metadata contract. No Woo hooks or execution. */
if(!defined('ABSPATH'))exit;
final class Andy_Commerce_M10_Policy_Fields {
    public static function normalize(array $fields):array {
        $start=trim((string)($fields['start_date']??''));
        $end=trim((string)($fields['end_date']??''));
        foreach([$start,$end] as $date) {
            if($date==='')continue;
            $dt=DateTimeImmutable::createFromFormat('!Y-m-d',$date,new DateTimeZone('UTC'));
            if(!$dt||$dt->format('Y-m-d')!==$date)throw new InvalidArgumentException('Invalid UTC date');
        }
        if($start!==''&&$end!==''&&$start>$end)throw new InvalidArgumentException('Date range reversed');
        $scope=$fields['product_scope']??'all';
        if(!in_array($scope,['all','selected'],true))throw new InvalidArgumentException('Invalid product scope');
        $raw=trim((string)($fields['product_ids']??''));
        $ids=[];
        if($raw!=='') {
            foreach(explode(',',$raw) as $part) {
                $part=trim($part);
                if(!preg_match('/^[1-9]\d{0,9}$/D',$part)||(float)$part>2147483647)
                    throw new InvalidArgumentException('Invalid product ID');
                $ids[]=(int)$part;
            }
        }
        if(count($ids)>20||count($ids)!==count(array_unique($ids)))
            throw new InvalidArgumentException('Duplicate or excessive products');
        if(($scope==='selected'&&!$ids)||($scope==='all'&&$ids))
            throw new InvalidArgumentException('Scope and products mismatch');
        $stack=$fields['stack_policy']??'best_single';
        if(!in_array($stack,['best_single','coupon_exclusive'],true))
            throw new InvalidArgumentException('Unsupported stacking strategy');
        return ['start_date'=>$start,'end_date'=>$end,'product_scope'=>$scope,
            'product_ids'=>$ids,'stack_policy'=>$stack,'evaluation'=>'dev_shadow_only'];
    }
}
