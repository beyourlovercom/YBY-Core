<?php
/** Currency OS V1 shadow-only deterministic regression. */
declare(strict_types=1);
define('ABSPATH', '/tmp/andy-commerce-currency-os-shadow/');
require dirname(__DIR__) . '/includes/class-andy-commerce-currency-os-v1.php';

$count = 0;
function currency_check(bool $condition, string $label): void {
    global $count;
    ++$count;
    if (!$condition) { throw new RuntimeException('FAIL: ' . $label); }
}
function denied(callable $operation, string $label): void {
    try {
        $operation();
    } catch (InvalidArgumentException | OverflowException $e) {
        currency_check(true, $label); return;
    }
    currency_check(false, 'Expected rejection: ' . $label);
}
function fixture(string $currency, int $rate, int $threshold = 9900): array {
    return [
        'currency' => $currency, 'rate_micros' => $rate,
        'rate_source' => 'DEV_SYNTHETIC',
        'rate_timestamp' => 1792600000,
        'threshold_usd_minor' => $threshold,
        'shipping_usd_minor' => 1000, 'tax_usd_minor' => 700,
        'lines' => [
            ['qty'=>1,'unit_usd_minor'=>12000,'discount_usd_minor'=>2500,
                'eligible_for_promotion'=>true,'free_gift'=>false],
        ],
    ];
}
$fx = ['USD'=>1000000,'EUR'=>900000,'GBP'=>780000,'AUD'=>1500000,'CAD'=>1350000];
foreach ($fx as $currency=>$rate) {
    $request = fixture($currency,$rate);
    $result = Andy_Commerce_Currency_OS_V1::quote($request);
    currency_check($result['base_currency']==='USD' && $result['currency']===$currency,
        "$currency ledger separates base and customer currency");
    currency_check($result['eligible_subtotal_usd_minor']===9500,
        "$currency post-discount USD subtotal fixed at $95");
    currency_check(!$result['promotion_eligible'],
        "$currency USD 99 threshold not unlocked by tax/shipping");
    currency_check($result['accounting_total_usd_minor']===11200,
        "$currency $95+$10+$7 accounting total USD112");
    currency_check($result['payable_local_minor']
        ===$result['merchandise_net_local_minor']
        +$result['shipping_local_minor']+$result['tax_local_minor'],
        "$currency local payable equals line plus shipping plus tax");
    currency_check($result['gross_local_minor']-$result['discount_local_minor']
        ===$result['merchandise_net_local_minor'],
        "$currency rounded local display discount reconciles");
    currency_check($result['shipping_local_minor']
        ===Andy_Commerce_Currency_OS_V1::convert_usd_minor(1000,$rate),
        "$currency native USD10 shipping converted once");
    currency_check($result['tax_local_minor']
        ===Andy_Commerce_Currency_OS_V1::convert_usd_minor(700,$rate),
        "$currency supplied Woo USD7 tax converted once for preview");
    currency_check($result['mode']==='DEV_SHADOW_NO_PAYMENT',
        "$currency no live payment claim");
}
foreach ($fx as $currency=>$rate) {
    $request = fixture($currency,$rate);
    $request['lines']=[
        ['qty'=>1,'unit_usd_minor'=>9900,'discount_usd_minor'=>0,'eligible_for_promotion'=>true],
        ['qty'=>2,'unit_usd_minor'=>3000,'discount_usd_minor'=>0,'eligible_for_promotion'=>false],
        ['qty'=>1,'unit_usd_minor'=>6000,'discount_usd_minor'=>0,'eligible_for_promotion'=>true,'free_gift'=>true],
    ];
    $result = Andy_Commerce_Currency_OS_V1::quote($request);
    currency_check($result['eligible_subtotal_usd_minor']===9900 && $result['promotion_eligible'],
        "$currency eligible $99 inclusive; special/gift excluded");
    currency_check($result['merchandise_net_usd_minor']===21900,
        "$currency noneligible special and free gift value still in sales ledger");
    currency_check($result['accounting_total_usd_minor']===23600,
        "$currency order accounting includes noneligible and shipping/tax");
    $request['lines'][0]['discount_usd_minor']=1;
    $result2=Andy_Commerce_Currency_OS_V1::quote($request);
    currency_check(!$result2['promotion_eligible'] && $result2['eligible_subtotal_usd_minor']===9899,
        "$currency $98.99 threshold fails regardless of converted local total");
}
$r=fixture('EUR',900000);
denied(fn()=>Andy_Commerce_Currency_OS_V1::quote([...$r,'currency'=>'JPY']), 'JPY not supported in V1');
denied(fn()=>Andy_Commerce_Currency_OS_V1::quote([...$r,'rate_micros'=>null]), 'No missing exchange rate');
denied(fn()=>Andy_Commerce_Currency_OS_V1::quote([...$r,'rate_micros'=>0]), 'Zero exchange rate fails');
denied(fn()=>Andy_Commerce_Currency_OS_V1::quote([...$r,'rate_micros'=>99999999]), 'Suspicious exchange rate fails');
denied(fn()=>Andy_Commerce_Currency_OS_V1::quote([...$r,'currency'=>'USD']), 'USD must be identity rate');
denied(fn()=>Andy_Commerce_Currency_OS_V1::quote([...$r,'rate_source'=>'']), 'FX source mandatory');
denied(fn()=>Andy_Commerce_Currency_OS_V1::quote([...$r,'rate_timestamp'=>0]), 'FX timestamp mandatory');
denied(fn()=>Andy_Commerce_Currency_OS_V1::quote([...$r,'lines'=>[]]), 'Empty cart fails');
denied(fn()=>Andy_Commerce_Currency_OS_V1::quote([...$r,'lines'=>[
    ['qty'=>0,'unit_usd_minor'=>100,'discount_usd_minor'=>0]
]]), 'Bad quantity fails');
denied(fn()=>Andy_Commerce_Currency_OS_V1::quote([...$r,'lines'=>[
    ['qty'=>1,'unit_usd_minor'=>100,'discount_usd_minor'=>101]
]]), 'Over-discount fails');
denied(fn()=>Andy_Commerce_Currency_OS_V1::quote([...$r,'shipping_usd_minor'=>-100]), 'Negative shipping fails');
denied(fn()=>Andy_Commerce_Currency_OS_V1::quote([...$r,'tax_usd_minor'=>-1]), 'Negative tax fails');
denied(fn()=>Andy_Commerce_Currency_OS_V1::quote([...$r,'lines'=>[
    ['qty'=>1000,'unit_usd_minor'=>10000000000,'discount_usd_minor'=>0]
]]), 'Line overflow fails');
denied(fn()=>Andy_Commerce_Currency_OS_V1::convert_usd_minor(10000000000,10000000), 'PHP integer FX overflow fails');
currency_check(Andy_Commerce_Currency_OS_V1::convert_usd_minor(1,500000)===1,
    'Half-up rounding tiny USD minor');
currency_check(Andy_Commerce_Currency_OS_V1::convert_usd_minor(1,100000)===0,
    'Sub-half rounding tiny USD minor');
currency_check(Andy_Commerce_Currency_OS_V1::convert_usd_minor(100,1000000)===100,
    'USD identity conversion');

echo "PASS Currency OS V1: {$count} synthetic USD-first ledger assertions; no Woo hooks, payments or orders\n";
