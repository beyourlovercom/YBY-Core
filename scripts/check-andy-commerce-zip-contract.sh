#!/usr/bin/env bash
set -euo pipefail
zip="$1"; source_sha="${2:-unknown}"
test -s "$zip"
unzip -tq "$zip" >/dev/null
entries="$(unzip -Z1 "$zip")"
for f in includes/class-andy-commerce-observation-mode.php includes/class-andy-commerce-observation-diagnostics.php includes/class-andy-commerce-r10c-transaction-compatibility.php admin/class-andy-commerce-admin.php admin/views/currency-os-v2-admin.php admin/views/currency-c2c3-readonly-panel.php assets/currency-c3-dev-preview.js includes/class-andy-commerce-c3-cart-snapshot-adapter.php includes/class-andy-commerce-currency-c2c3-preview.php admin/views/marketing-m10-workbench.php admin/views/marketing-m10-six-preview.php includes/class-andy-commerce-m10-six-type-simulator.php includes/class-andy-commerce-m10-lifecycle.php includes/class-andy-commerce-marketing-p1-sandbox-contract.php includes/class-andy-commerce-marketing-p2-woo-adapter.php includes/class-andy-commerce-marketing-p3-sku-woo.php includes/class-andy-commerce-marketing-p4-order-cap.php includes/class-andy-commerce-marketing-p5-checkout-hooks.php includes/class-andy-commerce-marketing-dev-uat.php includes/class-andy-commerce-currency-v2-dev.php includes/class-andy-commerce-m7-draft-studio.php andy-commerce.php; do
 echo "$entries" | grep -Fxq "andy-commerce/$f" || { echo "MISSING: $f"; exit 1; }
done
# Exact ZIP must contain the production fail-closed entrypoint before any
# Commerce business modules or Woo hooks are imported.
entry="$(unzip -p "$zip" andy-commerce/andy-commerce.php)"
mode_source="$(unzip -p "$zip" andy-commerce/includes/class-andy-commerce-observation-mode.php)"
# Here-strings avoid false SIGPIPE/pipefail negatives from grep -q on PHP.
grep -Fq 'Andy_Commerce_Observation_Mode::is_observing()' <<< "$entry" || {
 echo "OBSERVATION_ZIP_GUARD_MISSING"; exit 1;
}
grep -Fq 'function handle_toggle(' <<< "$mode_source" || {
 echo "OBSERVATION_ZIP_TOGGLE_MISSING"; exit 1;
}
repo_root="$(cd "$(dirname "$0")/.." && pwd)"
cmp -s "$repo_root/packages/andy-commerce/andy-commerce.php" <(unzip -p "$zip" andy-commerce/andy-commerce.php) || {
 echo "OBSERVATION_ZIP_BOOTSTRAP_SOURCE_MISMATCH"; exit 1;
}
cmp -s "$repo_root/packages/andy-commerce/includes/class-andy-commerce-observation-mode.php" <(unzip -p "$zip" andy-commerce/includes/class-andy-commerce-observation-mode.php) || {
 echo "OBSERVATION_ZIP_MODE_SOURCE_MISMATCH"; exit 1;
}
cmp -s "$repo_root/packages/andy-commerce/includes/class-andy-commerce-observation-diagnostics.php" <(unzip -p "$zip" andy-commerce/includes/class-andy-commerce-observation-diagnostics.php) || {
 echo "OBSERVATION_ZIP_DIAGNOSTICS_SOURCE_MISMATCH"; exit 1;
}
controller="$(unzip -p "$zip" andy-commerce/admin/class-andy-commerce-admin.php)"
for s in "function supported_tabs(" "function register_l3_items(" "function currency_l3_route_exists(" "'andy_core_l3_external_items'" "'register_l3_items'" "'currency-os'" "'manage_woocommerce'" "'andy-commerce.currency-os'" "'多币种'" "'route_check'"; do
 printf '%s' "$controller" | grep -Fq "$s" || { echo "ADAPTER_ZIP_MISSING: $s"; exit 1; }
done
test "$(printf '%s' "$controller" | grep -Fc "add_filter( 'andy_core_l3_external_items'")" = 1
unzip -p "$zip" andy-commerce/admin/views/currency-os-v2-admin.php | grep -Fq Currency
unzip -p "$zip" andy-commerce/admin/views/marketing-m10-workbench.php | grep -Fq marketing-create
rc_source="$(unzip -p "$zip" andy-commerce/includes/class-andy-commerce-r10c-transaction-compatibility.php)"
grep -Fq "ANDY_COMMERCE_R10C_OWNER_RELEASE_AUTH" <<< "$rc_source" || {
 echo "R10C_OWNER_GATE_MISSING"; exit 1;
}
grep -Fq "Andy_Commerce_Observation_Mode::is_observing()" <<< "$rc_source" || {
 echo "R10C_OBSERVATION_GATE_MISSING"; exit 1;
}
cmp -s "$repo_root/packages/andy-commerce/includes/class-andy-commerce-r10c-transaction-compatibility.php" <(unzip -p "$zip" andy-commerce/includes/class-andy-commerce-r10c-transaction-compatibility.php) || {
 echo "R10C_ZIP_SOURCE_MISMATCH"; exit 1;
}
if [[ "$entry" != *"1.1.0-rc1"* ]]; then echo "R10C_RC1_ZIP_VERSION_MISSING"; exit 1; fi
echo "ZIP_CONTRACT_PASS source=$source_sha sha256=$(shasum -a 256 "$zip"|awk '{print $1}')"
