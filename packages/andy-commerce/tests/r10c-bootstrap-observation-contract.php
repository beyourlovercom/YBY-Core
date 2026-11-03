<?php
/**
 * Source ordering guard: observation return must precede the new R10C
 * module import and every optional module remains off without flags.
 */
declare(strict_types=1);
$entry=file_get_contents(__DIR__.'/../andy-commerce.php');
$compat=file_get_contents(__DIR__.'/../includes/class-andy-commerce-r10c-transaction-compatibility.php');
if (!is_string($entry) || !is_string($compat)) exit(1);
$observer=strpos($entry,'if ( Andy_Commerce_Observation_Mode::is_observing() )');
$guardImport=strpos($entry,'class-andy-commerce-r10c-transaction-compatibility.php');
$run=strpos($entry,"add_action( 'plugins_loaded', array( 'Andy_Commerce_R10C_Transaction_Compatibility', 'boot' ), 50 )");
$required=array(
    $observer!==false && $guardImport!==false && $run!==false,
    $observer<$guardImport && $guardImport<$run,
    str_contains($compat,"get_option($". "option,'no') === 'yes'"),
    str_contains($compat,"ANDY_COMMERCE_R10C_OWNER_RELEASE_AUTH"),
    str_contains($compat,"Andy_Commerce_Observation_Mode::is_observing()"),
    str_contains($compat,"'shipping'") && str_contains($compat,"'coupon'"),
    !str_contains($entry,"ANDY_COMMERCE_R10C_OWNER_RELEASE_AUTH','OWNER_APPROVED"),
    str_contains($compat,"check_admin_referer('andy_commerce_r10c_toggle'"),
    str_contains($compat,"current_user_can('manage_options')"),
    str_contains($compat,"$"."current !== $"."expected"),
    str_contains($compat,"AUDIT_OPTION"),
    str_contains($entry,"'register_admin_menu' ), 1201"),
);
foreach($required as $idx=>$ok)if(!$ok){
    fwrite(STDERR,'R10C_BOOTSTRAP_SOURCE_GUARD_FAIL=' . ($idx+1) . "\n");
    exit(1);
}
echo 'R10C_BOOTSTRAP_SOURCE_GUARD='.count($required).'/'.count($required).' PASS'."\n";
