<?php
/** No side-effect isolated entry test. */
$source = file_get_contents(__DIR__ . '/../andy-commerce.php');
$guard = strpos($source, "if ( Andy_Commerce_Observation_Mode::is_observing() ) {");
$firstModule = strpos($source, "require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-yby-woo-order-export-presets.php';");
if ($guard === false || $firstModule === false || $guard >= $firstModule) {
    fwrite(STDERR,"FAIL_OBSERVATION_BOOTSTRAP_GATE_ORDER\n");exit(1);
}
if (strpos($source,"require_once ANDY_COMMERCE_PLUGIN_DIR . 'includes/class-andy-commerce-observation-mode.php';") === false) {
    fwrite(STDERR,"FAIL_OBSERVATION_CLASS_LOAD\n");exit(1);
}
$cases = ['www-frontend','www-admin','www-admin-hook-alert','www-post-active-confirmed','www-post-active-denied','unknown-host','malformed-option','dev-explicit-observe','dev-default','local-default','www-local','dev-nonstaging','www-explicit-active','www-post-observe'];
foreach ($cases as $case) {
    $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__.'/observation-mode-entry-fixture.php')
        . ' ' . escapeshellarg($case);
    $output=[];$status=0;
    exec($cmd.' 2>&1',$output,$status);
    if ($status !== 0 || !str_contains(implode("\n",$output),($case==='www-post-observe'?'MODE_PERSISTED=observe':($case==='www-post-active-confirmed'?'MODE_PERSISTED=active':'PASS_MODE '.$case)))) {
        fwrite(STDERR, "FAIL_OBSERVATION_CASE=$case output=".implode(' ',$output)."\n");exit(1);
    }
}
echo 'OBSERVATION_MODE_FAIL_CLOSED_PASS=14_ENTRY_CASES_BOOTSTRAP_ORDER\n';
