<?php
/** R10-H2 source-only planner and optimistic rollback simulator. No WP writes. */
declare(strict_types=1);
define('ABSPATH',__DIR__.DIRECTORY_SEPARATOR);
require dirname(__DIR__).'/includes/class-andy-commerce-r10h2-manual-priority-shadow.php';
final class H2Test {
    public static int $total=0;
    public static int $failed=0;
    public static function expect($okay, string $name): void {
        self::$total++;
        if(!$okay) {self::$failed++; fwrite(STDERR, 'FAIL H2 '.$name.PHP_EOL);}
        else echo 'PASS H2 '.$name.PHP_EOL;
    }
}
function coupon(string $code,string $type='fixed_cart',string $amount='10',
    string $origin='manual',bool $verified=true,bool $free=false): array {
    return [
        'code'=>$code,'type'=>$type,'amount'=>$amount,'free_shipping'=>$free,
        'origin'=>$origin,'origin_verified'=>$verified,
        'origin_evidence'=>$origin==='manual'
            ? 'trusted_server_customer_submission':'trusted_vendor_auto_apply',
        'vendor'=>$origin==='auto'?'storeapps':null,
    ];
}
function protected_coupon(string $code,string $type): array {
    $r=coupon($code,$type,'5','auto');
    $r['protected_verified']=true;
    return $r;
}
function decision(string $name,array $incoming,array $applied,bool $valid,
    string $expected,string $why=''): array {
    $v=Andy_Commerce_R10H2_Manual_Priority_Shadow::plan($incoming,$applied,$valid);
    H2Test::expect($v['action']===$expected && ($why==='' || $v['reason']===$why),$name);
    H2Test::expect($v['writes_performed']===0,$name.'_pure');
    return $v;
}
$m=coupon('MANUAL5');
$m2=coupon('MANUAL7','fixed_product','7');
$a=coupon('AUTO10','percent','10','auto');
$a2=coupon('AUTO15','fixed_cart','15','auto');
$free=coupon('SHIPFREE','fixed_cart','0','auto',true,true);
$credit=protected_coupon('CREDIT','store_credit');
$bogo=protected_coupon('BOGO','bogo');
$gift=protected_coupon('GIFT','gift');
$zero=coupon('ZERO','fixed_cart','0');
decision('empty_manual',$m,[],true,'keep','ordinary_first');
decision('empty_auto',$a,[],true,'keep','ordinary_first');
decision('manual_replay',$m,[$m],true,'keep','idempotent_same_code');
decision('auto_replay',$a,[$a],true,'keep','idempotent_same_code');
$replace=decision('auto_first_manual_overrides',$m,[$a],true,'replace_auto','manual_priority_verified');
H2Test::expect($replace['remove_codes']===['auto10'],'only_auto_removed');
H2Test::expect(Andy_Commerce_R10H2_Manual_Priority_Shadow::snapshot_matches(
    $replace['expected_snapshot'],[$a]),'snapshot_match');
H2Test::expect(!Andy_Commerce_R10H2_Manual_Priority_Shadow::snapshot_matches(
    $replace['expected_snapshot'],[$a,$credit]),'snapshot_stale');
decision('manual_first_auto_rejected',$a,[$m],true,'deny','one_ordinary_code_limit');
decision('manual_first_other_manual_rejected',$m2,[$m],true,'deny');
decision('auto_first_other_auto_rejected',$a2,[$a],true,'deny');
decision('preserve_credit_while_replacing_auto',$m,[$credit,$a],true,'replace_auto');
decision('preserve_bogo_while_replacing_auto',$m,[$a,$bogo],true,'replace_auto');
decision('preserve_gift_while_replacing_auto',$m,[$gift,$a],true,'replace_auto');
decision('preserve_free_shipping_while_replacing_auto',$m,[$free,$a],true,'replace_auto');
decision('protected_free_shipping_with_manual',$free,[$m],true,'keep','protected_reward');
decision('protected_credit_with_manual',$credit,[$m],true,'keep');
decision('protected_bogo_with_manual',$bogo,[$m],true,'keep');
decision('protected_gift_with_manual',$gift,[$m],true,'keep');
decision('zero_ordinary_exempt',$zero,[$m],true,'keep');
decision('native_vendor_veto',$m,[$a],false,'deny','vendor_veto');
decision('unverified_incoming_manual',array_merge($m,['origin_verified'=>false]),[$a],true,'deny','unverified_origin');
decision('unverified_existing_auto',$m,[array_merge($a,['origin_verified'=>false])],true,'deny','unverified_origin');
decision('manual_evidence_from_client_spoof',$m,[array_merge($a,['origin_evidence'=>'client_flag'])],true,'deny');
decision('manual_evidence_invalid',array_merge($m,['origin_evidence'=>'client_flag']),[$a],true,'deny');
decision('unknown_vendor_origin',$m,[array_merge($a,['vendor'=>'unknown_vendor'])],true,'deny');
decision('unknown_coupon_type',$m,[array_merge($a,['type'=>'unsupported'])],true,'deny');
decision('unknown_protected_credit',$m,[array_merge($credit,['protected_verified'=>false])],true,'deny');
decision('unknown_protected_bogo',$m,[array_merge($bogo,['protected_verified'=>false])],true,'deny');
decision('unknown_protected_gift',$m,[array_merge($gift,['protected_verified'=>false])],true,'deny');
decision('two_ordinary_rejected',$m,[$a,$m2],true,'deny','multiple_ordinary_existing');
decision('duplicate_coupon_snapshot_rejected',$m,[$a,$a],true,'deny','unverified_coupon_snapshot');
decision('invalid_code_rejected',array_merge($m,['code'=>'weird code']),[$a],true,'deny');
decision('bad_amount_rejected',array_merge($m,['amount'=>'1e12']),[$a],true,'deny');
decision('unknown_amount_rejected',array_merge($m,['amount'=>null]),[$a],true,'deny');
decision('empty_snapshot_manual_still_first',$m,[],true,'keep');

$tooMany=array_fill(0,51,$credit);
decision('oversize_snapshot_rejected',$m,$tooMany,true,'deny','unverified_coupon_snapshot');
decision('malformed_row_rejected',$m,[['code'=>'AUTO10']],true,'deny');
decision('auto_vendor_another_supported',$m,[array_merge($a,['vendor'=>'webtoffee'])],true,'replace_auto');
decision('auto_vendor_woocommerce',$m,[array_merge($a,['vendor'=>'woocommerce'])],true,'replace_auto');
decision('free_shipping_without_origin_proof',$m,[array_merge($free,['origin_verified'=>false]),$a],true,'replace_auto');
decision('multi_credit_preserved',$m,[$credit,$bogo,$gift,$free,$a],true,'replace_auto');
decision('auto_reapply_after_manual',$a,[$m],true,'deny');
decision('manual_idempotence_after_auto_removed',$m,[$m,$free,$credit],true,'keep');
decision('same_code_with_other_ordinary_corruption',$m,[$m,$a],true,'deny','multiple_ordinary_existing');
decision('native_veto_preserved_on_protected',$free,[$m],false,'deny','vendor_veto');

/**
 * This simulator is NOT wired to a WooCommerce callback. It demonstrates
 * necessary future adapter rules: optimistic concurrency and compensation.
 */
function simulated_commit(array &$cart,array $incoming,bool $nativeValid,
    bool $applySucceeds=true,?callable $race=null): string {
    $plan=Andy_Commerce_R10H2_Manual_Priority_Shadow::plan($incoming,$cart,$nativeValid);
    if($plan['action']!=='replace_auto')return $plan['action'];
    if($race!==null)$race($cart);
    if(!Andy_Commerce_R10H2_Manual_Priority_Shadow::snapshot_matches(
        $plan['expected_snapshot'],$cart))return 'stale_rejected';
    $original=$cart;
    $codes=$plan['remove_codes'];
    $cart=array_values(array_filter($cart,static fn($v)=>
        !in_array(strtolower($v['code']),$codes,true)));
    if(!$applySucceeds){
        $cart=$original; // exact order & metadata restored, including credits
        return 'rolled_back';
    }
    $cart[]=$incoming;
    return 'applied';
}
$cart=[$free,$a,$credit,$bogo,$gift];
H2Test::expect(simulated_commit($cart,$m,true)==='applied','transaction_success');
H2Test::expect(array_column($cart,'code')===['SHIPFREE','CREDIT','BOGO','GIFT','MANUAL5'],'preserved_entitlement_order');
H2Test::expect(simulated_commit($cart,$m,true)==='keep','transaction_repeat_idempotent');
H2Test::expect(simulated_commit($cart,$a,true)==='deny','auto_reapply_stays_rejected');
$cart=[$a,$credit,$free,$bogo];
$before=$cart;
H2Test::expect(simulated_commit($cart,$m,true,false)==='rolled_back','failed_manual_application_rollback');
H2Test::expect($cart===$before,'rollback_exact_metadata_and_order');
$cart=[$a,$gift,$credit];
$before=$cart;
H2Test::expect(simulated_commit($cart,$m,false)==='deny','vendor_veto_has_no_changes');
H2Test::expect($cart===$before,'vendor_veto_cart_untouched');
$cart=[$a,$free];
H2Test::expect(simulated_commit($cart,$m,true,true,static function(&$rows)use($credit){
    $rows[]=$credit;
})==='stale_rejected','concurrent_cart_change_rejected');
H2Test::expect(array_column($cart,'code')===['AUTO10','SHIPFREE','CREDIT'],'concurrent_rows_unmodified');
H2Test::expect(H2Test::$failed===0,'all_assertions');
echo 'R10H2_MANUAL_PRIORITY_SHADOW='.(H2Test::$total-H2Test::$failed).'/'.H2Test::$total."\n";
echo "R10H2_LIVE_WOO_HOOKS=0; WRITE_ACTIONS=0; PROVENANCE_SERVER_ONLY=CONTRACT\n";
exit(H2Test::$failed?1:0);
