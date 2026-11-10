<?php
declare(strict_types=1);
define('ABSPATH','/');
require __DIR__.'/../includes/class-byl-bf2026-r13-cart-writer.php';
$tz=new DateTimeZone('America/Los_Angeles');
$start=(new DateTimeImmutable('2026-11-10 00:00:00',$tz))->getTimestamp();
$end=(new DateTimeImmutable('2026-12-01 00:00:00',$tz))->getTimestamp();
$checks=[
 [!BYL_BF2026_R13_Cart_Writer::campaign_open($start-1),'before'],
 [BYL_BF2026_R13_Cart_Writer::campaign_open($start),'start_inclusive'],
 [BYL_BF2026_R13_Cart_Writer::campaign_open($start+3600),'nov10'],
 [BYL_BF2026_R13_Cart_Writer::campaign_open($end-1),'nov30_last_second'],
 [!BYL_BF2026_R13_Cart_Writer::campaign_open($end),'dec1_exclusive'],
 [!BYL_BF2026_R13_Cart_Writer::campaign_open($end+3600),'after'],
];
foreach($checks as [$passed,$name]) {
 if(!$passed){throw new RuntimeException('FAIL_WINDOW_'.$name);}
}
echo 'BF2026_R13_CAMPAIGN_WINDOW_FIXTURE_PASS='.count($checks).PHP_EOL;
