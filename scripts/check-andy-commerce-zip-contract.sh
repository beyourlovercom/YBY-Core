#!/usr/bin/env bash
set -euo pipefail
zip="$1"; source_sha="${2:-unknown}"
test -s "$zip"
unzip -tq "$zip" >/dev/null
entries="$(unzip -Z1 "$zip")"
for f in admin/class-andy-commerce-admin.php admin/views/currency-os-v2-admin.php admin/views/currency-c2c3-readonly-panel.php assets/currency-c3-dev-preview.js includes/class-andy-commerce-c3-cart-snapshot-adapter.php includes/class-andy-commerce-currency-c2c3-preview.php admin/views/marketing-m10-workbench.php admin/views/marketing-m10-six-preview.php includes/class-andy-commerce-m10-six-type-simulator.php includes/class-andy-commerce-m10-lifecycle.php includes/class-andy-commerce-currency-v2-dev.php includes/class-andy-commerce-m7-draft-studio.php andy-commerce.php; do
 echo "$entries" | grep -Fxq "andy-commerce/$f" || { echo "MISSING: $f"; exit 1; }
done
controller="$(unzip -p "$zip" andy-commerce/admin/class-andy-commerce-admin.php)"
for s in "function supported_tabs(" "function register_l3_items(" "function currency_l3_route_exists(" "'andy_core_l3_external_items'" "'register_l3_items'" "'currency-os'" "'manage_woocommerce'" "'andy-commerce.currency-os'" "'多币种'" "'route_check'"; do
 printf '%s' "$controller" | grep -Fq "$s" || { echo "ADAPTER_ZIP_MISSING: $s"; exit 1; }
done
test "$(printf '%s' "$controller" | grep -Fc "add_filter( 'andy_core_l3_external_items'")" = 1
unzip -p "$zip" andy-commerce/admin/views/currency-os-v2-admin.php | grep -Fq Currency
unzip -p "$zip" andy-commerce/admin/views/marketing-m10-workbench.php | grep -Fq marketing-create
echo "ZIP_CONTRACT_PASS source=$source_sha sha256=$(shasum -a 256 "$zip"|awk '{print $1}')"
