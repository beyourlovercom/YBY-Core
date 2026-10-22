<?php
/** Currency OS M2 reference FX HTTP and validation regression, never live calls. */
declare(strict_types=1);
define('ABSPATH','/tmp/andy-currency-os-m2/');
$GLOBALS['fakeStatus']=200;
$GLOBALS['fakeBody']='';
$GLOBALS['calls']=0;
function wp_safe_remote_get(string $url,array $args): array {
    ++$GLOBALS['calls'];
    if ($url !== 'https://api.frankfurter.dev/v2/rates?base=USD&quotes=EUR,GBP,AUD,CAD'
        || ($args['redirection']??null)!==0 || ($args['timeout']??99)>8
        || ($args['limit_response_size']??999999)>16384) {
        throw new RuntimeException('Invalid read-only fetch contract');
    }
    return ['status'=>$GLOBALS['fakeStatus'],'body'=>$GLOBALS['fakeBody']];
}
function is_wp_error($response): bool { return false; }
function wp_remote_retrieve_response_code(array $response): int { return $response['status']; }
function wp_remote_retrieve_body(array $response): string { return $response['body']; }
require dirname(__DIR__).'/includes/class-andy-commerce-currency-reference-fx-v1.php';
require dirname(__DIR__).'/includes/class-andy-commerce-currency-os-v1.php';
$count=0;
function check2(bool $truth,string $message): void {
    global $count; ++$count;
    if (!$truth)throw new RuntimeException('FAIL '.$message);
}
function reject2(callable $fn,string $message):void {
    try {$fn();}catch(Throwable $e){check2(true,$message);return;}
    check2(false,'Expected rejection: '.$message);
}
$now=strtotime('2026-10-22 12:00:00 UTC');
$rows=[
 ['date'=>'2026-10-22','base'=>'USD','quote'=>'EUR','rate'=>0.9],
 ['date'=>'2026-10-22','base'=>'USD','quote'=>'GBP','rate'=>0.78],
 ['date'=>'2026-10-22','base'=>'USD','quote'=>'AUD','rate'=>1.5],
 ['date'=>'2026-10-22','base'=>'USD','quote'=>'CAD','rate'=>1.35],
];
$snapshot=Andy_Commerce_Currency_Reference_FX_V1::parse($rows,$now);
check2($snapshot['base']==='USD','only USD reference base');
check2($snapshot['rate_micros']===['USD'=>1000000,'EUR'=>900000,'GBP'=>780000,'AUD'=>1500000,'CAD'=>1350000],
    'accurate USD micro FX rates');
check2($snapshot['quote_dates']['EUR']==='2026-10-22','daily observation preserved');
check2($snapshot['retrieved_at_utc']===$now,'immutable retrieval stamp');
check2($snapshot['mode']==='DEV_READONLY_REFERENCE_NOT_PAYMENT','no settlement');
foreach (['EUR','GBP','AUD','CAD'] as $currency) {
    $input=['currency'=>$currency,'rate_micros'=>$snapshot['rate_micros'][$currency],
      'rate_source'=>$snapshot['rate_source'],'rate_timestamp'=>$snapshot['retrieved_at_utc'],
      'threshold_usd_minor'=>9900,'shipping_usd_minor'=>1000,'tax_usd_minor'=>700,
      'lines'=>[['qty'=>1,'unit_usd_minor'=>12000,'discount_usd_minor'=>2500,
        'eligible_for_promotion'=>true]]];
    $quote=Andy_Commerce_Currency_OS_V1::quote($input);
    check2(!$quote['promotion_eligible'],'provider '.$currency.' keeps USD $99 eligibility');
    check2($quote['accounting_total_usd_minor']===11200,'provider '.$currency.' keeps USD accounting');
    check2($quote['payable_local_minor']===Andy_Commerce_Currency_OS_V1::convert_usd_minor(9500,$input['rate_micros'])
      +Andy_Commerce_Currency_OS_V1::convert_usd_minor(1000,$input['rate_micros'])
      +Andy_Commerce_Currency_OS_V1::convert_usd_minor(700,$input['rate_micros']),
      'provider '.$currency.' local sum reconciles');
}
$GLOBALS['fakeBody']=json_encode($rows,JSON_THROW_ON_ERROR);
$live=Andy_Commerce_Currency_Reference_FX_V1::fetch_readonly($now);
check2($GLOBALS['calls']===1,'one bounded HTTPS read-only request');
check2($live['rate_micros']===$snapshot['rate_micros'],'HTTP body contract matches pure parser');
$GLOBALS['fakeStatus']=500;
reject2(fn()=>Andy_Commerce_Currency_Reference_FX_V1::fetch_readonly($now),'provider HTTP error fail closed');
$GLOBALS['fakeStatus']=200;
$GLOBALS['fakeBody']='not JSON';
reject2(fn()=>Andy_Commerce_Currency_Reference_FX_V1::fetch_readonly($now),'invalid JSON fail closed');
$GLOBALS['fakeBody']=str_repeat('x',17000);
reject2(fn()=>Andy_Commerce_Currency_Reference_FX_V1::fetch_readonly($now),'oversize response fail closed');
$GLOBALS['fakeBody']='{"rate":1}';
reject2(fn()=>Andy_Commerce_Currency_Reference_FX_V1::fetch_readonly($now),'object response shape fail closed');
$GLOBALS['fakeBody']='[]';
reject2(fn()=>Andy_Commerce_Currency_Reference_FX_V1::fetch_readonly($now),'missing provider observations fail closed');
reject2(fn()=>Andy_Commerce_Currency_Reference_FX_V1::parse([], $now),'empty observations');
reject2(fn()=>Andy_Commerce_Currency_Reference_FX_V1::parse(array_slice($rows,0,3),$now),'missing CAD');
reject2(fn()=>Andy_Commerce_Currency_Reference_FX_V1::parse([...$rows,$rows[0]],$now),'duplicate EUR');
$bad=$rows;$bad[0]['quote']='JPY';
reject2(fn()=>Andy_Commerce_Currency_Reference_FX_V1::parse($bad,$now),'unknown quote fails');
$bad=$rows;$bad[0]['base']='EUR';
reject2(fn()=>Andy_Commerce_Currency_Reference_FX_V1::parse($bad,$now),'not USD provider base');
$bad=$rows;$bad[0]['rate']=-1;
reject2(fn()=>Andy_Commerce_Currency_Reference_FX_V1::parse($bad,$now),'negative rate');
$bad=$rows;$bad[0]['rate']=NAN;
reject2(fn()=>Andy_Commerce_Currency_Reference_FX_V1::parse($bad,$now),'nonfinite rate');
$bad=$rows;$bad[0]['rate']='0.9';
reject2(fn()=>Andy_Commerce_Currency_Reference_FX_V1::parse($bad,$now),'numeric string rejected');
$bad=$rows;$bad[0]['rate']=12.0;
reject2(fn()=>Andy_Commerce_Currency_Reference_FX_V1::parse($bad,$now),'outlier rate');
$bad=$rows;$bad[0]['date']='2026-09-01';
reject2(fn()=>Andy_Commerce_Currency_Reference_FX_V1::parse($bad,$now),'stale date');
$bad=$rows;$bad[0]['date']='2026-11-01';
reject2(fn()=>Andy_Commerce_Currency_Reference_FX_V1::parse($bad,$now),'future date');
$bad=$rows;$bad[0]['date']='2026-02-30';
reject2(fn()=>Andy_Commerce_Currency_Reference_FX_V1::parse($bad,$now),'invalid calendar date');
echo "PASS Currency OS M2 reference FX: {$count} test assertions; no live payment/checkout/site writes\n";
