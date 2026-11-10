<?php
/**
 * BF2026 R12 automatic random-free gift planner. SOURCE-ONLY, no WC writes.
 * Catalog/category membership alone is never proof that a gift is deliverable.
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class BYL_BF2026_R12_Auto_Gift_Planner {
    private const PACK='byl-black-friday-2026';

    private static function gift(array $line): bool {
        return ($line['andy_campaign_pack_id']??null)===self::PACK
            && ($line['_byl_bf2026_gift']??false)===true;
    }

    /** Manifest MUST come from approved ERP/Woo SKU + warehouse/country allowlist. */
    private static function approved(int $id,string $country,array $manifest): bool {
        $r=$manifest[$id]??null;
        if (!is_array($r) || $id<=0 || ($r['product_id']??null)!==$id
            || !is_string($r['sku']??null) || strlen($r['sku'])<2
            || ($r['approved']??false)!==true
            || ($r['warehouse_verified']??false)!==true
            || !is_string($r['warehouse_slug']??null) || trim($r['warehouse_slug'])===''
            || !is_int($r['available_units']??null) || $r['available_units']<1
            || !is_int($r['upgrade_minor']??null)
            || !in_array($r['upgrade_minor'],[0,1000,1500,2000],true)
            || !is_array($r['allowed_countries']??null)
            || !in_array($country,$r['allowed_countries'],true)) {
            return false;
        }
        if ($r['upgrade_minor']>0 && $country!=='US') { return false; }
        if (!function_exists('wc_get_product')) {return false;}
        $p=wc_get_product($id);
        return is_object($p) && method_exists($p,'get_sku')
            && (string)$p->get_sku()===$r['sku']
            && method_exists($p,'is_type') && $p->is_type('simple')
            && $p->is_purchasable() && $p->is_in_stock()
            && !$p->backorders_allowed();
    }

    public static function plan($cart,string $shipping_country,array $manifest,string $session_seed): array {
        if (!class_exists('BYL_BF2026_R9C_Woo_Adapter')
            || !class_exists('BYL_BF2026_R9_Policy')
            || strlen($session_seed)<16) {
            return ['ok'=>false,'reason'=>'policy_or_session_missing'];
        }
        $country=strtoupper(trim($shipping_country));
        if (preg_match('/^[A-Z]{2}$/D',$country)!==1) {
            return ['ok'=>false,'reason'=>'shipping_country_required'];
        }
        $quote=BYL_BF2026_R9C_Woo_Adapter::quote($cart,$country);
        if (!$quote['ok']) {return $quote;}
        $slots=(int)$quote['entitlement']['gift_slots'];
        $premium_allowed=$quote['entitlement']['premium_allowed']===true;
        $to_remove=[];$keep_free=[];$keep_paid=[];$used=[];
        foreach ($cart->get_cart() as $key=>$line) {
            if (!is_array($line) || !self::gift($line)) {continue;}
            $id=(int)($line['product_id']??0);
            $fee=$manifest[$id]['upgrade_minor']??null;
            $valid=($line['quantity']??null)===1
                && !isset($used[$id])
                && self::approved($id,$country,$manifest);
            // Never silently reinterpret a previously paid upgrade as free.
            if ($valid) {
                $valid = ($fee===0 && empty($line['_byl_bf2026_paid_slot']))
                    || ($fee>0 && ($line['_byl_bf2026_paid_slot']??null)===true);
            }
            if ($valid && $fee>0 && (!$premium_allowed || $keep_paid)) {$valid=false;}
            if (!$valid) {$to_remove[]=$key;continue;}
            $used[$id]=true;
            if ($fee===0) {$keep_free[$key]=$id;}
            else {$keep_paid[$key]=$id;}
        }
        // Premium counts as one of the same entitlement slots.
        $space=max(0,$slots-count($keep_paid));
        while(count($keep_free)>$space) {
            $key=array_key_last($keep_free);
            unset($keep_free[$key]);
            $to_remove[]=$key;
        }
        if ($slots===0) {
            foreach (array_keys($keep_paid) as $k) {$to_remove[]=$k;}
            return ['ok'=>true,'remove_keys'=>$to_remove,'add_free_ids'=>[],
                'slots'=>0,'paid_slots'=>0,'basis_minor'=>$quote['basis_minor']];
        }
        $needed=$slots-count($keep_paid)-count($keep_free);
        if ($needed<0) {return ['ok'=>false,'reason'=>'slot_overflow'];}
        $available=[];
        $existing_ids=array_values($keep_free);
        foreach ($manifest as $key=>$row) {
            $id=(int)$key;
            if ($id>0 && ($row['upgrade_minor']??null)===0
                && !in_array($id,$existing_ids,true)
                && self::approved($id,$country,$manifest)) {$available[]=$id;}
        }
        if (count($available)<$needed) {
            return ['ok'=>false,'reason'=>'insufficient_destination_stocked_gifts',
                'required'=>$needed,'available'=>count($available)];
        }
        if ($needed===0) {$add=[];}
        else {
            $chosen=BYL_BF2026_R9_Policy::random_plan($available,$needed,$session_seed);
            if (!$chosen['ok']) {return $chosen;}
            $add=$chosen['ids'];
        }
        return [
            'ok'=>true,'remove_keys'=>array_values(array_unique($to_remove)),
            'add_free_ids'=>$add,
            'keep_free_ids'=>$existing_ids,
            'keep_paid_ids'=>array_values($keep_paid),
            'slots'=>$slots,
            'paid_slots'=>count($keep_paid),
            'basis_minor'=>$quote['basis_minor'],
            'source'=>'approved_exact_sku_country_warehouse_manifest',
        ];
    }
}
