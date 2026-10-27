<?php
/** M4 shadow-only synthetic fixtures; no WordPress/Woo boot. */
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/class-andy-commerce-m4-shipping-shadow.php';
$tests = 0;
$ok = static function(bool $value, string $why) use (&$tests):void {
    $tests++;
    if (!$value) { throw new RuntimeException('M4 shadow failed: '.$why); }
};
$line = static fn(int $before,int $after,bool $gift=false,bool $eligible=true):array =>
    ['line_subtotal_minor'=>$before,'line_total_minor'=>$after,'gift'=>$gift,'eligible'=>$eligible];
$exact=Andy_Commerce_M4_Shipping_Shadow::compare([$line(4900,4900)],4900);
$ok($exact['legacy_eligible'] && $exact['proposed_eligible'] && !$exact['disagreement'],'exact threshold parity');
$discount=Andy_Commerce_M4_Shipping_Shadow::compare([$line(6000,4500)],4900);
$ok($discount['legacy_eligible'] && !$discount['proposed_eligible'] && $discount['disagreement'],'discount changes threshold');
$gift=Andy_Commerce_M4_Shipping_Shadow::compare([$line(4900,4900),$line(5000,5000,true)],10000);
$ok(!$gift['proposed_eligible'] && $gift['proposed_merchandise_minor']===4900,'gift never self-qualifies');
$filtered=Andy_Commerce_M4_Shipping_Shadow::compare([$line(4500,4500),$line(99999,99999,false,false)],4900);
$ok(!$filtered['proposed_eligible'],'ineligible merchandise excluded');
$ok($discount['runtime_enabled']===false && $discount['shadow_only']===true,'no runtime decisions');
$blocked=static function(array $lines,int $threshold) use (&$tests):void {
    try { Andy_Commerce_M4_Shipping_Shadow::compare($lines,$threshold); }
    catch (InvalidArgumentException $e) { $tests++; return; }
    throw new RuntimeException('M4 accepted invalid input');
};
$blocked([$line(100,-1)],4900);
$blocked([$line(100,200)],4900);
$blocked([['line_total_minor'=>4900,'gift'=>false,'eligible'=>true]],4900);
$blocked([$line(100,100)],-1);
$boot = file_get_contents(dirname(__DIR__) . '/andy-commerce.php');
$ok(!str_contains($boot,'class-andy-commerce-m4-shipping-shadow'), 'no M4 runtime bootstrap');
echo "M4_SHADOW_HARNESS_PASS={$tests}\n";
