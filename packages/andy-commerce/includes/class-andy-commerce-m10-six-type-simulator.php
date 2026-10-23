<?php
/** Six marketing templates: trusted synthetic USD-first simulation, never Woo runtime. */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
require_once __DIR__.'/class-andy-commerce-m8-promotion-evaluator.php';
require_once __DIR__.'/class-andy-commerce-m10-policy-fields.php';
require_once __DIR__.'/class-andy-commerce-m10-policy-evaluator.php';
final class Andy_Commerce_M10_Six_Type_Simulator {
    private const TYPES=['spend_save','free_gift','bogo','bundles','tiered'];
    private static function date($s):bool {
        if(!is_string($s)||!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$s))return false;
        $d=DateTimeImmutable::createFromFormat('!Y-m-d',$s,new DateTimeZone('UTC'));
        return $d!==false&&$d->format('Y-m-d')===$s;
    }
    private static function rule(string $type,array $r):bool {
        $money=static fn($v):bool=>is_int($v)&&$v>=0&&$v<=100000000;
        $pct=static fn($v):bool=>is_int($v)&&$v>0&&$v<=10000;
        if($type==='free_gift')return $money($r['threshold_minor']??null)&&$r['threshold_minor']>0
            &&is_int($r['product_id']??null)&&$r['product_id']>0;
        if($type==='tiered'){
            if(!is_array($r['tiers']??null)||!$r['tiers']||count($r['tiers'])>20)return false;
            $prev=0;
            foreach($r['tiers'] as $t){
                if(!is_array($t)||!is_int($t['minimum_qty']??null)||$t['minimum_qty']<=$prev
                    ||$t['minimum_qty']>1000||!$pct($t['percent_bp']??null))return false;
                $prev=$t['minimum_qty'];
            }
            return true;
        }
        $fixed=array_key_exists('discount_minor',$r);$percent=array_key_exists('percent_bp',$r);
        if($fixed===$percent||($fixed&&(!$money($r['discount_minor'])||$r['discount_minor']===0))
            ||($percent&&!$pct($r['percent_bp'])))return false;
        if($type==='spend_save')return $money($r['threshold_minor']??null)&&$r['threshold_minor']>0;
        if($type==='bogo'){
            foreach(['buy_product_id','get_product_id','buy_qty','get_qty'] as $k)
                if(!is_int($r[$k]??null)||$r[$k]<1||$r[$k]>2147483647)return false;
            return $r['buy_product_id']!==$r['get_product_id']
                &&$r['buy_qty']<=1000&&$r['get_qty']<=1000;
        }
        if($type==='bundles'){
            $ids=$r['product_ids']??null;
            if(!is_array($ids)||count($ids)<2||count($ids)>20
                ||count(array_unique($ids,SORT_REGULAR))!==count($ids))return false;
            foreach($ids as $id)if(!is_int($id)||$id<=0||$id>2147483647)return false;
            return true;
        }
        return false;
    }
    public static function preview(array $s,array $drafts):array {
        $out=['mode'=>'DEV_SHADOW_NO_PAYMENT','runtime_enabled'=>false,
            'actual_discount_applied'=>false,'actual_gift_awarded'=>false,
            'shipping_changed'=>false,'stock_verified'=>false,
            'projected_discount_usd_minor'=>0,'selected_discount_id'=>null,
            'qualified_gifts'=>[],'evaluations'=>[],'reason'=>'no_qualifying_draft'];
        $country=$s['country']??null;$currency=$s['currency']??null;
        $coupon=$s['manual_coupon']??null;$rate=$s['fx_rate_micros']??null;$items=$s['items']??null;
        if(($s['source']??null)!=='DEV_SYNTHETIC'||($s['trusted']??null)!==true
            ||!self::date($s['date_utc']??null)
            ||!in_array($currency,['USD','EUR','GBP','AUD','CAD'],true)
            ||!is_string($country)||!preg_match('/^[A-Z]{2}$/D',$country)
            ||!is_int($rate)||$rate<10000||$rate>100000000
            ||($coupon!==null&&(!is_string($coupon)||strlen($coupon)>50))
            ||!is_array($items)||!$items||count($items)>100||count($drafts)>100){
            $out['reason']='invalid_snapshot';return $out;
        }
        $ids=[];$total=0;
        foreach($items as $line){
            if(!is_array($line)||!is_int($line['product_id']??null)
                ||$line['product_id']<1||isset($ids[$line['product_id']])
                ||($line['catalog_present']??null)!==true
                ||!is_int($line['quantity']??null)||$line['quantity']<1||$line['quantity']>1000
                ||!is_int($line['discounted_line_minor']??null)
                ||$line['discounted_line_minor']<0||$line['discounted_line_minor']>100000000){
                $out['reason']='unverified_cart_line';return $out;
            }
            $ids[$line['product_id']]=true;$total+=$line['discounted_line_minor'];
            if($total>1000000000){$out['reason']='invalid_cart_total';return $out;}
        }
        $out['qualified_before_campaign_usd_minor']=$total;$candidates=[];
        foreach($drafts as $d){
            $id=is_array($d)?($d['id']??''):'';$type=is_array($d)?($d['type']??''):'';
            $e=['id'=>is_string($id)?$id:'','type'=>is_string($type)?$type:'',
                'eligible'=>false,'reason'=>'invalid_draft','discount_usd_minor'=>0];
            if(!is_array($d)||!is_string($id)||!preg_match('/^[A-Za-z0-9_-]{1,64}$/D',$id)
                ||!in_array($type,self::TYPES,true)||!is_int($d['priority']??null)
                ||$d['priority']<0||$d['priority']>1000||!is_array($d['rule']??null)){
                $out['evaluations'][]=$e;continue;
            }
            if(!in_array($d['lifecycle']??'draft',['draft','review','scheduled'],true)){
                $e['reason']='lifecycle_not_eligible';$out['evaluations'][]=$e;continue;
            }
            if(($d['currency']??null)!=='USD'){
                $e['reason']='currency_not_usd';$out['evaluations'][]=$e;continue;
            }
            if(!is_array($d['countries']??null)||!in_array($country,$d['countries'],true)){
                $e['reason']='country_excluded';$out['evaluations'][]=$e;continue;
            }
            $v=Andy_Commerce_M10_Policy_Evaluator::check($d,[
                'date_utc'=>$s['date_utc'],'product_ids'=>array_keys($ids),'manual_coupon'=>$coupon]);
            if(!$v['eligible']){$e['reason']=$v['reason'];$out['evaluations'][]=$e;continue;}
            if(!self::rule($type,$d['rule'])){
                $e['reason']='invalid_rule';$out['evaluations'][]=$e;continue;
            }
            $p=$v['policy'];
            $eligible=$p['product_scope']==='selected'
                ?array_values(array_filter($items,static fn($x):bool=>
                    in_array($x['product_id'],$p['product_ids'],true))):$items;
            if(!$eligible){$e['reason']='product_excluded';$out['evaluations'][]=$e;continue;}
            $basis=array_sum(array_column($eligible,'discounted_line_minor'));
            $e['qualified_basis_usd_minor']=$basis;
            if($type==='free_gift'){
                if($basis<$d['rule']['threshold_minor'])$e['reason']='below_threshold';
                else{
                    $e['eligible']=true;$e['reason']='gift_qualified_stock_unverified';
                    $e['gift_product_id']=$d['rule']['product_id'];
                    $out['qualified_gifts'][]=['id'=>$id,'gift_product_id'=>$d['rule']['product_id'],
                        'stock_verified'=>false,'awarded'=>false];
                }
                $out['evaluations'][]=$e;continue;
            }
            $m8=Andy_Commerce_M8_Promotion_Evaluator::preview([
                'items'=>$eligible,'currency'=>'USD','country'=>$country,'manual_coupon'=>null],
                [array_merge($d,['countries'=>[$country],'stack_with_coupon'=>true])]);
            $saving=$m8['projected_discount_minor']??0;
            if(!is_int($saving)||$saving<=0)$e['reason']='rule_not_qualified';
            else{
                $e['eligible']=true;$e['reason']='preview_only';$e['discount_usd_minor']=$saving;
                $candidates[]=['id'=>$id,'amount'=>$saving,'priority'=>$d['priority']];
            }
            $out['evaluations'][]=$e;
        }
        usort($candidates,static fn($a,$b):int=>
            ($b['amount']<=>$a['amount']) ?: (($b['priority']<=>$a['priority'])?:strcmp($a['id'],$b['id'])));
        $winner=$candidates[0]??null;$award=$winner['amount']??0;
        $out['projected_discount_usd_minor']=$award;
        $out['selected_discount_id']=$winner['id']??null;
        $out['qualified_after_campaign_usd_minor']=$total-$award;
        $out['currency']=$currency;
        $out['qualified_after_campaign_local_minor']=class_exists('Andy_Commerce_Currency_OS_V1')
            ?Andy_Commerce_Currency_OS_V1::convert_usd_minor($total-$award,$rate):null;
        $out['reason']=$winner?'preview_only':($out['qualified_gifts']
            ?'gift_qualification_only':'no_qualifying_draft');
        return $out;
    }
}
