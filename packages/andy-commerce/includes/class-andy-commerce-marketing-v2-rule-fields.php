<?php
/**
 * Marketing V2 M1 — typed admin form -> existing M8 draft-only rule schema.
 * Pure normalizer: no WooCommerce hooks, promotions, option writes or FX calls.
 */
if (!defined('ABSPATH')) { exit; }

final class Andy_Commerce_Marketing_V2_Rule_Fields {
    private static function minor($value): int {
        if (!is_scalar($value) || !preg_match('/^\d{1,7}(?:\.\d{1,2})?$/D',trim((string)$value))) {
            throw new InvalidArgumentException('Enter a non-negative amount with at most 2 decimals.');
        }
        $parts=explode('.',trim((string)$value),2);
        $major=(int)$parts[0];
        $cents=isset($parts[1]) ? (int)str_pad($parts[1],2,'0') : 0;
        $out=$major*100+$cents;
        if($out>100000000) throw new InvalidArgumentException('Amount exceeds draft limit.');
        return $out;
    }
    private static function bp($value): int {
        if (!is_scalar($value)||!preg_match('/^\d{1,3}(?:\.\d{1,2})?$/D',trim((string)$value))) {
            throw new InvalidArgumentException('Enter a percent with up to two decimals.');
        }
        $parts=explode('.',trim((string)$value),2);
        $out=(int)$parts[0]*100+(isset($parts[1]) ? (int)str_pad($parts[1],2,'0'):0);
        if($out>10000) throw new InvalidArgumentException('Discount percent must be 0–100.');
        return $out;
    }
    private static function positive($value,int $max=1000): int {
        if(!is_scalar($value)||!preg_match('/^[1-9]\d*$/D',(string)$value)
            ||(int)$value>$max)throw new InvalidArgumentException('Enter an allowed positive whole number.');
        return (int)$value;
    }
    private static function discount(array $values,array $rule):array {
        $mode=$values['discount_mode']??null;
        if($mode==='fixed'){$rule['discount_minor']=self::minor($values['fixed']??null);}
        elseif($mode==='percent'){$rule['percent_bp']=self::bp($values['percent']??null);}
        else throw new InvalidArgumentException('Select fixed or percentage discount.');
        return $rule;
    }
    public static function normalize(string $type,array $all): array {
        if(!in_array($type,['spend_save','bogo','bundles','tiered','free_gift'],true)) {
            throw new InvalidArgumentException('Unsupported marketing rule.');
        }
        $fields=$all[$type]??null;
        if(!is_array($fields)||count($fields)>25)throw new InvalidArgumentException('Malformed rule fields.');
        switch($type){
        case 'spend_save':
            return self::discount($fields,['threshold_minor'=>self::minor($fields['threshold']??null)]);
        case 'bogo':
            $buy=self::positive($fields['buy_product_id']??null,2147483647);
            $get=self::positive($fields['get_product_id']??null,2147483647);
            if($buy===$get)throw new InvalidArgumentException('Buy and get products must be different.');
            return self::discount($fields,[
                'buy_product_id'=>$buy,'buy_qty'=>self::positive($fields['buy_qty']??null),
                'get_product_id'=>$get,'get_qty'=>self::positive($fields['get_qty']??null)
            ]);
        case 'bundles':
            $raw=$fields['product_ids']??null;
            if(!is_string($raw)||strlen($raw)>225)throw new InvalidArgumentException('Invalid bundle products.');
            $ids=array_map('trim',explode(',',$raw));
            if(count($ids)<2||count($ids)>20)throw new InvalidArgumentException('Bundle requires 2–20 unique products.');
            $clean=[];
            foreach($ids as $id)$clean[]=self::positive($id,2147483647);
            if(count(array_unique($clean))!==count($clean))throw new InvalidArgumentException('Duplicate bundle product.');
            return self::discount($fields,['product_ids'=>$clean]);
        case 'tiered':
            $tiers=[];
            $previous=0;
            for($i=1;$i<=3;$i++) {
                $qty=$fields['minimum_qty_'.$i]??'';
                $pct=$fields['percent_'.$i]??'';
                if((string)$qty===''&&(string)$pct==='')continue;
                $q=self::positive($qty);
                if($q<=$previous)throw new InvalidArgumentException('Tier quantities must ascend.');
                $tiers[]=['minimum_qty'=>$q,'percent_bp'=>self::bp($pct)];
                $previous=$q;
            }
            if(!$tiers)throw new InvalidArgumentException('At least one tier required.');
            return ['tiers'=>$tiers];
        case 'free_gift':
            // Stored as a draft only. Existing M8 evaluator never awards a gift.
            $threshold=self::minor($fields['threshold']??null);
            if ($threshold<=0) throw new InvalidArgumentException('Gift threshold must be positive USD.');
            return [
                'threshold_minor'=>$threshold,
                'product_id'=>self::positive($fields['product_id']??null,2147483647),
            ];
        }
        throw new InvalidArgumentException('Unsupported marketing rule.');
    }

    public static function major($minor):string {
        return is_int($minor)&&$minor>=0 ? number_format($minor/100,2,'.','') : '';
    }
    public static function percentage($bp):string {
        return is_int($bp)&&$bp>=0 ? number_format($bp/100,2,'.','') : '';
    }
}
