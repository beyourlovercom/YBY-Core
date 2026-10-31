<?php
/** R13 TEST-only refund USD ledger arithmetic: no WordPress, no Stripe, no funds. */
declare(strict_types=1);
define('ABSPATH',__DIR__.'/');
require dirname(__DIR__).'/includes/class-andy-commerce-currency-r13-stripe-refund-ledger.php';
$pass=0;
$ok=static function(bool $v,string $description) use (&$pass):void {
    if(!$v)throw new RuntimeException('FAIL '.$description);
    ++$pass;
};
$original=['currency'=>'CAD','display_minor'=>2701,'usd_minor'=>2000,
    'rate_micros'=>1350000,'fx_source'=>'CI_SYNTHETIC_LOCKED'];
$none=['display_minor'=>0,'usd_minor'=>0];
$a=Andy_Commerce_Currency_R13_Stripe_Refund_Ledger::plan($original,$none,
    ['currency'=>'CAD','amount_minor'=>1351]);
$ok($a['reversed_usd_minor']===1001,'CAD13.51 must reverse original USD10.01');
$ok($a['cumulative_display_minor']===1351,'first partial CAD total');
$ok($a['no_gateway_refund']===false,'real provider refund flag');
$b=Andy_Commerce_Currency_R13_Stripe_Refund_Ledger::plan($original,
    ['display_minor'=>$a['cumulative_display_minor'],'usd_minor'=>$a['cumulative_reversed_usd_minor']],
    ['currency'=>'CAD','amount_minor'=>1350]);
$ok($b['reversed_usd_minor']===999,'CAD13.50 final must reverse remaining USD9.99');
$ok($b['cumulative_reversed_usd_minor']===2000
    && $b['cumulative_display_minor']===2701,'all original USD and CAD accounted');
$single=Andy_Commerce_Currency_R13_Stripe_Refund_Ledger::plan($original,$none,
    ['currency'=>'CAD','amount_minor'=>500]);
$ok($single['reversed_usd_minor']===370,'arbitrary CAD5 partial converts to USD3.70');
$usd=Andy_Commerce_Currency_R13_Stripe_Refund_Ledger::plan(
    ['currency'=>'USD','display_minor'=>2000,'usd_minor'=>2000,
      'rate_micros'=>1000000,'fx_source'=>'LOCKED_ORIGINAL'],
    $none,['currency'=>'USD','amount_minor'=>100]);
$ok($usd['reversed_usd_minor']===100,'USD-to-USD 1:1 refund');
foreach ([['currency'=>'EUR','amount_minor'=>500],['currency'=>'CAD','amount_minor'=>2702],
         ['currency'=>'CAD','amount_minor'=>0]] as $invalid) {
    $threw=false;
    try { Andy_Commerce_Currency_R13_Stripe_Refund_Ledger::plan($original,$none,$invalid); }
    catch(InvalidArgumentException $e){ $threw=true; }
    $ok($threw,'currency mismatch/zero/over-refund fails closed');
}
// Check every possible nonzero first refund, with the final residue exact.
for($first=1;$first<=2700;$first++) {
    $one=Andy_Commerce_Currency_R13_Stripe_Refund_Ledger::plan($original,$none,
        ['currency'=>'CAD','amount_minor'=>$first]);
    $two=Andy_Commerce_Currency_R13_Stripe_Refund_Ledger::plan($original,
        ['display_minor'=>$one['cumulative_display_minor'],
         'usd_minor'=>$one['cumulative_reversed_usd_minor']],
        ['currency'=>'CAD','amount_minor'=>2701-$first]);
    if($two['cumulative_reversed_usd_minor']!==2000 || $two['cumulative_display_minor']!==2701)
        throw new RuntimeException('FX cumulative miss at '.$first);
}
$ok(true,'2700 split points preserve exact USD2000 CAD2701 cumulative');
echo "R13_REFUND_PROVIDER_LEDGER_PURE_CONTRACT_PASS assertions=$pass split_points=2700\n";
