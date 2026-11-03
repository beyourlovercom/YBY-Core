<?php
/** P6-E pure TEST fixture based on actual Owner-approved A Stripe test ledger. */
define('ABSPATH',__DIR__.'/');
require dirname(__DIR__).'/includes/class-andy-commerce-currency-r13-settlement-audit.php';
$ok=static function($pass,$msg):void {if(!$pass)throw new RuntimeException('P6E '.$msg);};
$order=['id'=>105,'currency'=>'CAD','source_usd_minor'=>2000,
 'woo_total_minor'=>2701,'locked_fx_source'=>'CI_SYNTHETIC_LOCKED',
 'locked_fx_rate_micros'=>1350000];
$charge=['id'=>'ch_3UV5mKGR9MwFzg431O3hzY4C','payment_intent'=>'pi_3UV5mKGR9MwFzg431L5k3gDq',
 'metadata'=>['order_id'=>'105'],'status'=>'succeeded','livemode'=>false,
 'currency'=>'cad','amount'=>2701,'amount_refunded'=>1351];
$balance=['currency'=>'hkd','amount'=>14807,'fee'=>812,'net'=>13995,
 'exchange_rate'=>5.4821,'status'=>'pending'];
$refund=['id'=>'re_3UV5mKGR9MwFzg431FWMoCBW','charge'=>$charge['id'],
 'payment_intent'=>$charge['payment_intent'],'status'=>'succeeded',
 'amount'=>1351,'currency'=>'cad'];
$refundBalance=['currency'=>'hkd','amount'=>-7557,'fee'=>0,'net'=>-7557,
 'exchange_rate'=>5.59397,'status'=>'pending'];
$out=Andy_Commerce_Currency_R13_Settlement_Audit::reconcile($order,$charge,$balance,$refund,$refundBalance);
$ok($out['source_usd_minor']===2000 && $out['source_locked_rate_micros']===1350000,'USD snapshot unchanged');
$ok($out['shopper_currency']==='CAD' && $out['shopper_charge_minor']===2701,'customer CAD charge');
$ok($out['shopper_refund_minor']===1351,'Stripe CAD refund');
$ok($out['provider_balance_currency']==='HKD','merchant balance HKD distinct from CAD');
$ok($out['provider_net_after_refund_minor']===6438,'HKD pending net 13995-7557=6438');
$ok($out['charge_balance']['fx_rate']!==$out['refund_balance']['fx_rate'],'provider rate differences preserved');
$ok($out['payout_verified']===false && $out['charge_balance']['status']==='pending','do not claim payout');
$ok($out['source_fx_not_settlement_fx']===true,'no false synthetic FX settlement parity');
$reject=function($o,$c,$b,$r,$rb)use($ok):void{
 $failed=false;
 try{Andy_Commerce_Currency_R13_Settlement_Audit::reconcile($o,$c,$b,$r,$rb);}
 catch(InvalidArgumentException $e){$failed=true;}
 $ok($failed,'unsafe TEST settlement must fail closed');
};
$reject($order,array_replace($charge,['livemode'=>true]),$balance,$refund,$refundBalance);
$reject($order,array_replace($charge,['amount'=>2702]),$balance,$refund,$refundBalance);
$reject($order,array_replace($charge,['currency'=>'usd']),$balance,$refund,$refundBalance);
$reject($order,$charge,array_replace($balance,['net'=>13994]),$refund,$refundBalance);
$reject($order,$charge,$balance,array_replace($refund,['amount'=>3000]),$refundBalance);
$reject($order,$charge,$balance,$refund,array_replace($refundBalance,['currency'=>'usd']));
$reject($order,$charge,$balance,$refund,array_replace($refundBalance,['amount'=>7557]));
echo 'R13_P6E_STRIPE_TEST_THREE_LEDGER_PURE_CONTRACT_PASS assertions=15 payout_verified=NO\n';
