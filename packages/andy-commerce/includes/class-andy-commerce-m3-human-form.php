<?php
/** M3 operator form -> existing validated draft schema. No Woo writes. */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
final class Andy_Commerce_M3_Human_Form {
    private const TYPES=['spend_save','bogo','bundle','tiered','free_gift','free_shipping'];

    private static function string($value,int $max=120):string {
        if (!is_string($value) || strlen($value)>$max || !preg_match('//u',$value)) {
            throw new InvalidArgumentException('Invalid text field');
        }
        return trim($value);
    }
    private static function int($value,int $min=1,int $max=1000000000):int {
        if (!is_string($value) && !is_int($value)) { throw new InvalidArgumentException('Invalid whole number'); }
        $s=(string)$value;
        if (!preg_match('/^(0|[1-9][0-9]{0,11})$/D',$s)) { throw new InvalidArgumentException('Expected whole number'); }
        $n=(int)$s;
        if ($n<$min || $n>$max) { throw new InvalidArgumentException('Whole number out of bounds'); }
        return $n;
    }
    private static function money($value,bool $allowZero=false):int {
        $s=self::string($value,32);
        if (!preg_match('/^(0|[1-9][0-9]{0,9})(?:\\.([0-9]{1,2}))?$/D',$s,$m)) {
            throw new InvalidArgumentException('Enter a currency amount with at most 2 decimals');
        }
        $whole=(int)explode('.',$s)[0];
        $fraction=isset($m[2])?(int)str_pad($m[2],2,'0'):0;
        $minor=$whole*100+$fraction;
        if ($minor>1000000000000 || (!$allowZero && $minor<1)) { throw new InvalidArgumentException('Invalid positive amount'); }
        return $minor;
    }
    private static function percent($value):int {
        $s=self::string($value,12);
        if (!preg_match('/^(0|[1-9][0-9]{0,2})(?:\\.([0-9]{1,2}))?$/D',$s,$m)) {
            throw new InvalidArgumentException('Invalid percentage');
        }
        $bps=(int)explode('.',$s)[0]*100+(isset($m[2])?(int)str_pad($m[2],2,'0'):0);
        if ($bps<1 || $bps>10000) { throw new InvalidArgumentException('Percentage must be between 0.01 and 100'); }
        return $bps;
    }
    private static function ids($values,bool $optional=false):array {
        if ((!is_array($values) || count($values)>100) && !($optional && ($values===null || $values===''))) {
            throw new InvalidArgumentException('Select products');
        }
        if ($optional && (!$values || $values===[''])) { return []; }
        $ids=[];
        foreach ($values as $value) { $ids[]=self::int($value,1,2147483647); }
        if (!$optional && !$ids) { throw new InvalidArgumentException('At least one product is required'); }
        if (count(array_unique($ids))!==count($ids)) { throw new InvalidArgumentException('Duplicate products selected'); }
        return $ids;
    }
    private static function date($value):?string {
        if ($value==='' || $value===null) { return null; }
        $s=self::string($value,30);
        if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}$/D',$s)) { throw new InvalidArgumentException('Invalid time'); }
        $dt=DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i',$s,new DateTimeZone('UTC'));
        if (!$dt || $dt->format('Y-m-d\\TH:i')!==$s) { throw new InvalidArgumentException('Invalid UTC time'); }
        return $dt->format('Y-m-d\\TH:i:s\\Z');
    }
    private static function award(array $fields):array {
        $mode=self::string($fields['award_mode']??'',16);
        if ($mode==='fixed') { return ['discount_minor'=>self::money($fields['award_value']??null)]; }
        if ($mode==='percent') { return ['discount_bps'=>self::percent($fields['award_value']??null)]; }
        throw new InvalidArgumentException('Select fixed amount or percent');
    }

    public static function decode(array $post):array {
        $fields=$post['campaign_fields']??null;
        if (!is_array($fields)) { throw new InvalidArgumentException('Missing campaign form'); }
        $type=self::string($fields['type']??'',32);
        if (!in_array($type,self::TYPES,true)) { throw new InvalidArgumentException('Unsupported campaign type'); }
        $countries=$fields['countries']??[];
        if (!is_array($countries) || count($countries)>30) { throw new InvalidArgumentException('Invalid region selection'); }
        foreach ($countries as $country) {
            if (!is_string($country) || !preg_match('/^[A-Z]{2}$/D',$country)) {
                throw new InvalidArgumentException('Invalid country code');
            }
        }
        $rule=$fields['rule']??[];
        if (!is_array($rule)) { throw new InvalidArgumentException('Missing promotion conditions'); }
        $r=[];
        switch($type) {
            case 'spend_save':
                $r=['threshold_minor'=>self::money($rule['threshold']??null)]+self::award($rule);
                $products=self::ids($rule['product_ids']??null,true);
                if ($products) { $r['product_ids']=$products; }
                break;
            case 'bogo':
                $r=[
                    'buy_product_ids'=>self::ids($rule['buy_ids']??null),
                    'get_product_ids'=>self::ids($rule['get_ids']??null),
                    'buy_qty'=>self::int($rule['buy_qty']??null,1,100),
                    'get_qty'=>self::int($rule['get_qty']??null,1,100),
                    'reward_bps'=>self::percent($rule['reward_percent']??null)
                ];
                break;
            case 'bundle':
                $ids=self::ids($rule['product_ids']??null);
                $qty=$rule['quantities']??null;
                if (!is_array($qty) || count($qty)!==count($ids) || count($ids)<2 || count($ids)>25) {
                    throw new InvalidArgumentException('Bundle needs 2–25 products and quantities');
                }
                $r=['requires'=>[]];
                foreach ($ids as $i=>$id) { $r['requires'][]=['product_id'=>$id,'quantity'=>self::int($qty[$i]??null,1,100)]; }
                if (($rule['award_mode']??null)==='bundle_price') {
                    $r['bundle_price_minor']=self::money($rule['award_value']??null,true);
                } elseif (($rule['award_mode']??null)==='percent') {
                    $r['discount_bps']=self::percent($rule['award_value']??null);
                } else { throw new InvalidArgumentException('Select bundle pricing method'); }
                break;
            case 'tiered':
                $qty=$rule['tier_quantities']??null;
                $values=$rule['tier_values']??null;
                $modes=$rule['tier_modes']??null;
                if (!is_array($qty) || !is_array($values) || !is_array($modes)
                    || count($qty)<1 || count($qty)>20
                    || count($qty)!==count($values) || count($qty)!==count($modes)) {
                    throw new InvalidArgumentException('Invalid quantity discount tiers');
                }
                $r=['tiers'=>[]];
                foreach ($qty as $i=>$n) {
                    $r['tiers'][]=['min_qty'=>self::int($n,1,5000)]
                        +self::award(['award_mode'=>$modes[$i],'award_value'=>$values[$i]]);
                }
                $products=self::ids($rule['product_ids']??null,true);
                if ($products) { $r['product_ids']=$products; }
                break;
            case 'free_gift':
                $r=[
                    'threshold_minor'=>self::money($rule['threshold']??null),
                    'reward_product_id'=>self::int($rule['gift_id']??null,1,2147483647),
                    'max_gift_qty'=>self::int($rule['gift_qty']??null,1,5)
                ];
                break;
            case 'free_shipping':
                $method=self::string($rule['shipping_method']??'',40);
                if (!preg_match('/^free_shipping:[1-9][0-9]{0,8}$/D',$method)) {
                    throw new InvalidArgumentException('Choose a valid Woo free shipping method');
                }
                $r=['threshold_minor'=>self::money($rule['threshold']??null),'shipping_method_id'=>$method];
                break;
        }
        $id=self::string($fields['id']??'',60);
        $name=self::string($fields['name']??'',120);
        $currency=self::string($fields['currency']??'',3);
        $priorityValue=$fields['priority']??'0';
        $priority=self::string($priorityValue,5);
        if (!preg_match('/^-?(0|[1-9][0-9]{0,3})$/D',$priority)) { throw new InvalidArgumentException('Invalid priority'); }
        $draft=[
            'schema_version'=>1,'id'=>$id,'name'=>$name,'type'=>$type,
            'currency'=>$currency,'status'=>'draft','active'=>false,
            'priority'=>(int)$priority,'countries'=>array_values($countries),
            'start_utc'=>self::date($fields['start_utc']??''),
            'end_utc'=>self::date($fields['end_utc']??''),
            'stack_with_coupon'=>($fields['stack_with_coupon']??null)==='1',
            'notes'=>self::string($fields['notes']??'',1000),
            'rule'=>$r
        ];
        // Reuse canonical validator; refuses invalid or activated campaigns.
        Andy_Commerce_Marketing_Draft_Sandbox::validate($draft);
        return $draft;
    }

    public static function summary(array $draft):string {
        $type=(string)($draft['type']??'');
        $r=is_array($draft['rule']??null)?$draft['rule']:[];
        $currency=(string)($draft['currency']??'USD');
        $amount=static fn(int $value):string=>$currency.' '.self::moneyLabel($value);
        $discount=isset($r['discount_bps'])
            ? self::percentLabel((int)$r['discount_bps']).'%'
            : $amount((int)($r['discount_minor']??0));
        switch($type) {
            case 'spend_save':
                return '满 '.$amount((int)($r['threshold_minor']??0))
                    .(isset($r['discount_bps'])?'，优惠 '.$discount:'，立减 '.$discount);
            case 'bogo':
                return '买 '.(int)($r['buy_qty']??1).' 件，另 '.(int)($r['get_qty']??1).' 件优惠 '
                    .self::percentLabel((int)($r['reward_bps']??10000)).'%';
            case 'bundle':
                return '组合 '.count((array)($r['requires']??[])).' 种商品，'
                    .(isset($r['bundle_price_minor'])
                        ? '套装价 '.$amount((int)$r['bundle_price_minor'])
                        : '折扣 '.self::percentLabel((int)($r['discount_bps']??0)).'%');
            case 'tiered':
                return '多买多折 · '.count((array)($r['tiers']??[])).' 档阶梯';
            case 'free_gift':
                return '满 '.$amount((int)($r['threshold_minor']??0)).'，赠送指定商品';
            case 'free_shipping':
                return '满 '.$amount((int)($r['threshold_minor']??0)).'，免运费（仅草稿）';
            default: return '仅草稿 · OFF';
        }
    }
    public static function moneyLabel(int $minor):string { return number_format($minor/100,2,'.',''); }
    public static function percentLabel(int $bps):string { return number_format($bps/100,2,'.',''); }
}
