# Andy Commerce R10-B exact two-vendor gate — 2026-10-31

Classification: TEST PREPARATION, plus actual isolated Woo UAT evidence. Not WWW checkout acceptance or www install authorization.

2026-10-31 UPDATE: previously missing exact vendor versions WERE FOUND in Windows Localnew and Localdev. Both copied read-only to NEW isolated Windows Woo, with Woo 11.2.1 and YayCurrency 3.3.5 matched. Actual native 40 cases and 4 independent Store API scenarios completed. See ANDY_COMMERCE_R10B_NATIVE_WINDOWS_UAT_20261031.md. Two blocking findings: BOTH coupons accepted, and EUR USD69-equivalent free shipping missing due float precision.

## Source baseline from authenticated admin and local package checks

- WWW: WooCommerce 11.2.1; YayCurrency Pro 3.3.5; WebToffee Smart Coupons Pro 3.2.0; StoreApps WooCommerce Smart Coupons 9.51.0. Both coupon vendors active.
- WWW Andy Commerce 1.0.14 remains OBSERVE. No approved production SSH: do not attempt to retrieve production files that way.
- DEV: Woo 11.2.1 / YayCurrency 3.3.5 / Andy Commerce 1.0.14; neither coupon vendor installed. Its quotes CANNOT establish dual-vendor parity.
- Historical Mac ZIPs were mismatched, but later Windows installed plugin directories yielded exact WebToffee 3.2.0 and StoreApps 9.51.0 matching www. Exact versions were loaded in fresh isolated Windows; never substitute those older ZIPs.
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

Verdict: R9-B WWW settings snapshot PASS; actual R10-B exact two-vendor runtime UAT EXECUTED, with two HARD FAILS (multi-coupon stacking and EUR boundary free shipping). Not eligible for production activation. GitHub contains no paid plugin files or customer data.
