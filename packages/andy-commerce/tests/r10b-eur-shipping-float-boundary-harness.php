<?php
/**
 * R10B float-vs-minor shipping threshold regression.
 * Offline only: no WP bootstrap, hooks, shipping quote or Woo mutation.
 */
declare(strict_types=1);
$thresholdUsdMinor = 6900;
$syntheticEurFxMicros = 920000;
$thresholdEurMinor = intdiv($thresholdUsdMinor * $syntheticEurFxMicros + 500000, 1000000);
if ($thresholdEurMinor !== 6348) {
    fwrite(STDERR, "R10B_WRONG_EUR_THRESHOLD_MINOR\n");
    exit(1);
}
// Exact 20-decimal printouts captured from the isolated native Woo EUR cart.
$cartEurFloat = (float) '63.47999999999999687361';
$thresholdEurFloat = (float) '63.48000000000000397904';
$floatDelta = $cartEurFloat - $thresholdEurFloat;
$displayEurMinor = (int) round($cartEurFloat * 100);
$minorEligible = $displayEurMinor >= $thresholdEurMinor;
if ( ! $minorEligible || abs($floatDelta) > 1e-10 || $cartEurFloat >= $thresholdEurFloat ) {
    fwrite(STDERR, "R10B_INTEGER_MINOR_COMPARISON_FAILED\n");
    exit(1);
}
echo "R10B_EUR_BOUNDARY_FLOAT_DIAGNOSTIC_PASS"
    . " direct_float_eligible=" . (($cartEurFloat >= $thresholdEurFloat) ? '1' : '0')
    . " exact_minor_eligible=" . ($minorEligible ? '1' : '0')
    . " usd_threshold_minor=" . $thresholdUsdMinor
    . " eur_threshold_minor=" . $thresholdEurMinor
    . " discrepancy_note=isolated_Woo_baseline_2026-10-31_confirmed_false\n";
