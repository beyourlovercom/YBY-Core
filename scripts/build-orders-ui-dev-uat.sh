#!/usr/bin/env bash
set -euo pipefail
# Build a standalone Dev-only preview addon from the exact Commerce PR sources.
# It never overwrites the installed Andy Commerce plugin or WooCommerce.
root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
src="$root/packages/andy-commerce"
out="$root/dist"
stage="$(mktemp -d)"
trap 'rm -rf "$stage"' EXIT
plugin="$stage/andy-commerce-orders-ui-dev-uat"
mkdir -p "$plugin/includes" "$plugin/assets/css" "$plugin/assets/js" "$out"
cp "$src/assets/css/orders-ui-v1.css" "$plugin/assets/css/"
cp "$src/assets/js/orders-ui-v1.js" "$plugin/assets/js/"
sed \
  -e 's/ANDY_COMMERCE_PLUGIN_URL/AC_ORDERS_UI_UAT_PLUGIN_URL/g' \
  -e 's/ANDY_COMMERCE_VERSION/AC_ORDERS_UI_UAT_VERSION/g' \
  "$src/includes/class-andy-commerce-orders-ui.php" > "$plugin/includes/class-andy-commerce-orders-ui.php"
cat > "$plugin/andy-commerce-orders-ui-dev-uat.php" <<'PHP'
<?php
/**
 * Plugin Name: Andy Commerce Orders UI — DEV UAT Only
 * Description: Temporary standalone preview of Orders UI V1 (standard/compact/cards). Remove after Commerce integration.
 * Version: 0.1.0
 * Requires Plugins: woocommerce
 * Author: BeYourLover Dev
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
define( 'AC_ORDERS_UI_UAT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AC_ORDERS_UI_UAT_VERSION', '0.1.0' );
add_action( 'plugins_loaded', function () {
    if ( ! class_exists( 'WooCommerce' ) || class_exists( 'Andy_Commerce_Orders_UI' ) ) {
        return;
    }
    require_once __DIR__ . '/includes/class-andy-commerce-orders-ui.php';
    if ( is_admin() ) {
        Andy_Commerce_Orders_UI::boot();
    }
}, 31 );
PHP
php -l "$plugin/andy-commerce-orders-ui-dev-uat.php"
php -l "$plugin/includes/class-andy-commerce-orders-ui.php"
test -s "$plugin/assets/js/orders-ui-v1.js"
test -s "$plugin/assets/css/orders-ui-v1.css"
rm -f "$out/andy-commerce-orders-ui-dev-uat.zip"
(cd "$stage" && zip -qr "$out/andy-commerce-orders-ui-dev-uat.zip" "andy-commerce-orders-ui-dev-uat")
unzip -t "$out/andy-commerce-orders-ui-dev-uat.zip"
sha256sum "$out/andy-commerce-orders-ui-dev-uat.zip"
echo "DEV preview only; no full Commerce replacement, no main merge, no release."
