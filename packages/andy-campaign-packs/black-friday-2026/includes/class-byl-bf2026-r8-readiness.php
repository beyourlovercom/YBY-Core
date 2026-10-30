<?php
/**
 * R8 READ-ONLY merchandising preflight. Never registers a Woo hook, adds a
 * product to cart, changes inventory, or enables a Campaign pack.
 *
 * $candidates map Woo product ID => ['upgrade_minor' => nonnegative USD cents].
 * $approvals map Woo product ID => owner-approved warehouse/cost/freight proof:
 *   ['approved'=>true, 'warehouse_available_qty'=>positive integer,
 *    'landed_cost_usd_minor'=>positive integer, 'packed_weight_g'=>positive integer,
 *    'warehouse_code'=>machine code, 'warehouse_checked_at'=>Unix seconds,
 *    'inventory_evidence_kind'=>'provider_verified'|'erp_provider_reconciled',
 *    'reviewed_by'=>non-empty accountable reviewer,
 *    'allowed_countries'=>list of uppercase ISO-3166 alpha-2 codes]
 * Warehouse stock must be explicitly provider-confirmed within 24 hours.
 * A nonempty ERP/Woo stock-status alone is never sufficient.
 * Unlisted approval values are NOT inferred from Woo stock.
 */
if (!defined('ABSPATH')) exit;
final class BYL_BF2026_R8_Readiness {
    public static function inspect(array $candidates, array $approvals=[]): array {
        $rows=[]; $free_approved=0; $all_approved=true;
        foreach($candidates as $id=>$config) {
            $reasons=[];
            if (!is_int($id) || $id<1
                || !is_array($config)
                || !is_int($config['upgrade_minor']??null)
                || $config['upgrade_minor']<0) {
                $rows[]=['id'=>$id, 'status'=>'BLOCKED', 'reasons'=>['invalid_php_catalog_row']];
                $all_approved=false; continue;
            }
            $p=function_exists('wc_get_product')?wc_get_product($id):false;
            $sku=$p&&method_exists($p,'get_sku')?(string)$p->get_sku():'';
            if (!$p || !method_exists($p,'is_type') || !$p->is_type('simple'))$reasons[]='simple_product_required';
            if (!$p || !$p->is_purchasable() || !$p->is_in_stock())$reasons[]='purchasable_stock_required';
            if ($p && $p->backorders_allowed())$reasons[]='backorder_not_allowed';
            if (preg_match('/^(AICC-UAT-|R6-|TEST-)/i',$sku)
                || ($p && stripos((string)$p->get_name(),'DO NOT FULFILL')!==false))
                $reasons[]='test_fixture_never_fulfill';
            $a=$approvals[$id]??[];
            if (($a['approved']??null)!==true)$reasons[]='owner_signoff_missing';
            if (!is_int($a['warehouse_available_qty']??null)
                || $a['warehouse_available_qty']<1)$reasons[]='warehouse_qty_unverified';
            $warehouse=$a['warehouse_code']??null;
            if (!is_string($warehouse) || preg_match('/^[a-z][a-z0-9-]{1,30}$/D', $warehouse)!==1)
                $reasons[]='warehouse_identity_unverified';
            $evidence=$a['inventory_evidence_kind']??null;
            if (!in_array($evidence, ['provider_verified','erp_provider_reconciled'], true))
                $reasons[]='warehouse_provider_evidence_missing';
            $checked=$a['warehouse_checked_at']??null;
            // Do not accept expired or future-dated inventory attestations.
            if (!is_int($checked) || $checked<=0 || $checked>time()
                || time()-$checked>86400)
                $reasons[]='warehouse_snapshot_stale_or_missing';
            $reviewer=$a['reviewed_by']??null;
            if (!is_string($reviewer) || trim($reviewer)==='')
                $reasons[]='accountable_reviewer_missing';
            if (!is_int($a['landed_cost_usd_minor']??null)
                || $a['landed_cost_usd_minor']<1)$reasons[]='landed_cost_unverified';
            if (!is_int($a['packed_weight_g']??null)
                || $a['packed_weight_g']<1)$reasons[]='packed_weight_unverified';
            $countries=$a['allowed_countries']??null;
            if (!is_array($countries) || !$countries
                || count(array_filter($countries,static fn($v)=>
                    !is_string($v) || preg_match('/^[A-Z]{2}$/D',$v)!==1))>0)
                $reasons[]='destination_policy_unverified';
            // A premium gift is a USD upgrade; product list/base retail price
            // is never interpreted as an automatic discount entitlement.
            $approved=!$reasons;
            if (!$approved)$all_approved=false;
            if ($approved && $config['upgrade_minor']===0)$free_approved++;
            $rows[]=[
                'id'=>$id,'sku'=>$sku,
                'upgrade_usd_minor'=>$config['upgrade_minor'],
                'status'=>$approved?'APPROVED':'BLOCKED',
                'reasons'=>$reasons
            ];
        }
        return [
            'release_ready'=>$all_approved && $free_approved>=2 && count($rows)>=2,
            'free_approved_count'=>$free_approved,
            'rows'=>$rows,
            'scope'=>'report_only_no_catalog_activation'
        ];
    }
}
