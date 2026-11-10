<?php
declare(strict_types=1);
define('ABSPATH','/');
require __DIR__.'/../includes/class-byl-bf2026-r10-price-policy.php';
require __DIR__.'/../includes/class-byl-bf2026-r10-sale-manager.php';
$r=BYL_BF2026_R10_Sale_Manager::apply();
if (($r['ok']??null)!==false || ($r['reason']??'')!=='global_sale_meta_disabled_us_only_policy') {
 throw new RuntimeException('GLOBAL_SALE_META_NOT_GATED');
}
echo "BF2026_R13_US_ONLY_META_WRITE_DEFAULT_OFF_PASS=1\n";
