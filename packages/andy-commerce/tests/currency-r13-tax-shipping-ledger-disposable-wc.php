<?php
/**
 * R13 native Woo tax math + trusted source-USD vs Woo-local audit matrix.
 * NOT an order/gateway execution, only isolated WP/Woo native arithmetic.
 */
if (!defined('ABSPATH') || !defined('DB_NAME') || DB_NAME!=='commerce_p5_isolated'
 || get_option('siteurl')!=='http://commerce-p5.invalid'
 || !defined('WP_HTTP_BLOCK_EXTERNAL') || !WP_HTTP_BLOCK_EXTERNAL)
 throw new RuntimeException('R13 tax/ship matrix must be in isolated Woo');
$ok=static function($yes,$name){if(!$yes)throw new RuntimeException('R13 ledger '.$name);};
$convert=static function(int $minor,int $rate):int {
 return Andy_Commerce_Currency_OS_V1::convert_usd_minor($minor,$rate);
};
$taxCents=static function(int $priceMinor,float $rate,bool $inclusive):int {
 if(!$rate)return 0;
 $r=[1=>['rate'=>number_format($rate,4,'.',''),'label'=>'R13 synthetic tax','compound'=>'no','shipping'=>'yes']];
 $amounts=WC_Tax::calc_tax(number_format($priceMinor/100,2,'.',''),$r,$inclusive);
 return (int)round(wc_round_tax_total(array_sum($amounts))*100);
};
$cases=[
 ['region'=>'US','currency'=>'USD','rate'=>1000000,'gross'=>5000,'qty'=>1,'coupon'=>500,'shipping'=>1000,'taxrate'=>0.0,'inclusive'=>false],
 ['region'=>'DE','currency'=>'EUR','rate'=>900000,'gross'=>5000,'qty'=>1,'coupon'=>500,'shipping'=>600,'taxrate'=>19.0,'inclusive'=>false],
 ['region'=>'UK','currency'=>'GBP','rate'=>780000,'gross'=>6000,'qty'=>1,'coupon'=>0,'shipping'=>1000,'taxrate'=>20.0,'inclusive'=>true],
 ['region'=>'CA','currency'=>'CAD','rate'=>1350000,'gross'=>1250,'qty'=>2,'coupon'=>500,'shipping'=>1000,'taxrate'=>5.0,'inclusive'=>false],
 ['region'=>'AU','currency'=>'AUD','rate'=>1500000,'gross'=>6900,'qty'=>1,'coupon'=>0,'shipping'=>0,'taxrate'=>0.0,'inclusive'=>false],
];
foreach($cases as $case){
 $usdMerch=$case['gross']*$case['qty'];
 $localMerch=$convert($case['gross'],$case['rate'])*$case['qty'];
 $localCoupon=$convert($case['coupon'],$case['rate']);
 $localShip=$convert($case['shipping'],$case['rate']);
 $usdTax=$taxCents($usdMerch-$case['coupon']+$case['shipping'],$case['taxrate'],$case['inclusive']);
 $localTax=$taxCents($localMerch-$localCoupon+$localShip,$case['taxrate'],$case['inclusive']);
 $localTotal=$localMerch-$localCoupon+$localShip+($case['inclusive']?0:$localTax);
 $ledger=Andy_Commerce_Currency_R13_Dual_Ledger::reconcile([
   'currency'=>$case['currency'],'rate_micros'=>$case['rate'],
   'rate_source'=>'CI_SYNTHETIC_LOCKED','rate_timestamp'=>1793000000,
   'coupon_count'=>$case['coupon']?1:0,'unit_count'=>$case['qty'],
   'tax_inclusive'=>$case['inclusive'],
   'usd'=>['merchandise'=>$usdMerch,'discount'=>$case['coupon'],
     'shipping'=>$case['shipping'],'tax'=>$usdTax],
   'woo_local'=>['merchandise'=>$localMerch,'discount'=>$localCoupon,
     'shipping'=>$localShip,'tax'=>$localTax],
   'woo_order_total_minor'=>$localTotal,
 ]);
 $ok($ledger['native_woo_total_minor']===$localTotal,'native total '.$case['region']);
 $ok($ledger['order_currency']===$case['currency']
   && $ledger['components']['tax']['usd_minor']===$usdTax
   && $ledger['components']['shipping']['usd_minor']===$case['shipping'],
   'native tax shipping source '.$case['region']);
 $ok(!$ledger['provider_settlement_verified'] && !$ledger['payment_executed'],
   'no payment assertions '.$case['region']);
 echo 'R13_TRUSTED_DUAL_LEDGER_NATIVE_WOO_TAX_PASS '
    .$case['region'].' currency='.$case['currency'].' total_minor='.$localTotal
    .' inclusive='.($case['inclusive']?'yes':'no').PHP_EOL;
}
// Reject source mismatches including wrong shipping, tax and native totals.
$bad=[
 'currency'=>'CAD','rate_micros'=>1350000,
 'rate_source'=>'CI_SYNTHETIC_LOCKED','rate_timestamp'=>1793000000,
 'coupon_count'=>0,'unit_count'=>1,'tax_inclusive'=>false,
 'usd'=>['merchandise'=>10000,'discount'=>0,'shipping'=>1000,'tax'=>100],
 'woo_local'=>['merchandise'=>13500,'discount'=>0,'shipping'=>1350,'tax'=>135],
 'woo_order_total_minor'=>14985,
];
$reject=static function($input) use($ok){
 try{Andy_Commerce_Currency_R13_Dual_Ledger::reconcile($input);}
 catch(InvalidArgumentException $e){return;}
 throw new RuntimeException('R13 must fail closed for invalid dual-ledger');
};
$bad['woo_local']['shipping']=550;
$reject($bad);
$bad['woo_local']['shipping']=1350;$bad['coupon_count']=2;
$reject($bad);
$bad['coupon_count']=0;$bad['woo_order_total_minor']=14984;
$reject($bad);
$bad['woo_order_total_minor']=14985;$bad['tax_inclusive']='yes';
$reject($bad);
echo 'R13_DUAL_LEDGER_SHIPPING_COUPON_TOTAL_TAX_GUARDS_PASS'.PHP_EOL;
