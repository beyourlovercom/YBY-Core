# Andy Commerce R10-B exact two-vendor gate — 2026-10-31

Classification: TEST PREPARATION. Not live checkout acceptance or www install authorization.

## Source baseline from authenticated admin and local package checks

- WWW: WooCommerce 11.2.1; YayCurrency Pro 3.3.5; WebToffee Smart Coupons Pro 3.2.0; StoreApps WooCommerce Smart Coupons 9.51.0. Both coupon vendors active.
- WWW Andy Commerce 1.0.14 remains OBSERVE. No approved production SSH: do not attempt to retrieve production files that way.
- DEV: Woo 11.2.1 / YayCurrency 3.3.5 / Andy Commerce 1.0.14; neither coupon vendor installed. Its quotes CANNOT establish dual-vendor parity.
- China Mac local historical WebToffee archive main header = 2.4.1; StoreApps downloaded archive = 9.82.2 (older 9.29.1 also present); YayCurrency archive = 3.3.5 exact. Mac mini/Windows/007windows usual download locations yielded no matching 3.2.0/9.51.0 packages. DO NOT substitute mismatched versions.
- WWW native Free Shipping zone 0 requires EITHER post-discount USD 69 minimum OR valid free-shipping coupon; Flat Rate base USD 20 with class costs. StoreApps auto apply enabled; Woo sequential coupons enabled; single-coupon global restriction NOT proved.

## Source-only tools

1. scripts/r10b-vendor-zip-preflight.py:
   Argument --webtoffee PATH --storeapps PATH --yaycurrency PATH. ZIP-only, exact plugin header identity and versions, archive SHA256, basic ZIP integrity. No extraction/install/network/WordPress. Fails on mismatched, absent, encrypted, path-traversal archives.
2. scripts/r10b-vendor-zip-preflight-test.py:
   Synthetic exact-header and negative-case tests 10/10; no proprietary code or ZIP committed.
3. scripts/r10b-isolated-runtime-preflight.sh:
   Single argument a locally isolated WordPress root. Requires .andy-r10b-isolated-local file and localhost/.test URL, refuses web/public_html paths. Uses wp-cli to READ installed and active vendor versions and Woo shipping/coupon settings. No cart/order/coupon/payment/session writes.
4. Existing R10 offline 80/80 shadow tests are complementary only, NOT real WWW parity.

## Strict remaining full R10-B acceptance conditions

A. Obtain authorized exact WebToffee 3.2.0 and StoreApps 9.51.0 copies from vendor licensed downloads or an approved READ-ONLY backup/file manager; licensed packages MUST stay outside GitHub.
B. Load both in a disposable Woo 11.2.1 + YayCurrency Pro 3.3.5 test installation, verify versions and hook ownership.
C. With approved isolated shopper test, use actual YayCurrency selection mechanism (arbitrary URL currency param did NOT switch DEV); compare native five-currency cart/Store API quotations for one-vs-two coupons, valid free-shipping coupon vs normal, auto coupons, BOGO, gifts, exempt SKUs and shipping classes/destinations. No real payment, order, email or stock.
D. Resolve all mismatches, record native quotes, vendor version hashes and regression results. Do not interpret source-only 80/80 and general isolated Woo CI as vendor parity.
E. No WWW plugin update, module activation, exit OBSERVE, or production config changes without separate Owner pass.

Verdict: R9-B WWW settings snapshot PASS. R10-B exact two-vendor real quote parity BLOCKED due to missing exact licensed copies. GitHub stores only the toolchain and test plan, not paid plugins.
