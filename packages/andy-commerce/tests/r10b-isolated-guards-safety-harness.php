<?php
/**
 * R10-B negative contract: UAT guard cannot boot on WWW / DEV / unmarked WP.
 * No WordPress, vendor, customer cart, network or database access.
 */
declare(strict_types=1);
define('ABSPATH', __DIR__ . '/r10b-nonexistent-unmarked-root/');
$GLOBALS['r10b_filters_registered'] = 0;
function WC() { return (object) array('cart' => null); }
function get_option($key, $fallback = null) {
    return $key === 'siteurl' ? 'https://www.beyourlover.com' : $fallback;
}
function add_filter(...$args) { ++$GLOBALS['r10b_filters_registered']; }
require __DIR__ . '/sandbox/class-andy-commerce-r10b-isolated-guards.php';
$checks = array(
    'www_refuses' => !Andy_Commerce_R10B_Isolated_Guards::sandbox_allowed(),
    'boot_refuses' => !Andy_Commerce_R10B_Isolated_Guards::boot(),
    'zero_hooks' => $GLOBALS['r10b_filters_registered'] === 0,
    'false_free_remains_false' =>
        Andy_Commerce_R10B_Isolated_Guards::boundary_only(false, array(), null) === false,
    'true_availability_remains_true' =>
        Andy_Commerce_R10B_Isolated_Guards::boundary_only(true, array(), null) === true,
    'invalid_coupon_unchanged' =>
        Andy_Commerce_R10B_Isolated_Guards::single_ordinary_discount(false, null) === false,
);
foreach ($checks as $name => $ok) {
    if (!$ok) {
        fwrite(STDERR, "R10B_ISOLATION_CONTRACT_FAILED: " . $name . "\n");
        exit(1);
    }
}
echo "R10B_SANDBOX_ONLY_GUARD_CONTRACT=" . count($checks) . "/" . count($checks) . " PASS\n";
