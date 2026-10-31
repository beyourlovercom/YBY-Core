# Andy Commerce R10-B — Windows isolated Woo + dual-vendor native UAT
Date: 2026-10-31 Asia/Taipei. Production WWW unchanged, Andy Commerce 1.0.14 remains OBSERVE. Source integrator remains Draft PR #213. No checkout order, payment, refund, customer email, or website Production write.

## Environment provenance — actual exact versions, not substitute archives

An independently initialized Windows sandbox under D:\ai\r10b-vendor-uat\disposable\www was created from WordPress core, WITHOUT existing Localnew/Localdev wp-content, uploads, wp-config, DB or customer records. Fresh WordPress 7.1, PHP 8.2.29 CLI, isolated MySQL 8.4 on loopback port 14041, new DB r10b_woo_compat. WordPress URL http://127.0.0.1:14141 with filesystem isolation marker; separate loopback-only PHP test HTTP server.

Four actually ACTIVE plugins read back using WP-CLI / WordPress get_plugins:
- WooCommerce 11.2.1, from official Woo plugin archive.
- YayCurrency Pro 3.3.5, exact local archive transferred once via private Tailnet; transfer checksum verified, sender stopped.
- WebToffee Smart Coupons for WooCommerce Pro 3.2.0, read-only copied from Windows Localnew plugin directory into isolated sandbox.
- StoreApps WooCommerce Smart Coupons 9.51.0, read-only copied from Windows Localnew plugin directory into isolated sandbox.

Both coupon plugins in Windows Localnew and Localdev also had identical PHP-tree hashes:
- WebToffee 3e86c29cfc11e7c65213e07d8d3a28d8be3706452d53bb55d1a9225632106665
- StoreApps 18df7c238401a03c885d5ebecccd0bf917352b7153a47d051c0d090c9fc341f7

No paid plugin source, files, WP database/config or licensing data are included in this repository.

## Real Woo filter collision surface

Source-only overlap: both coupon vendors register 56 overlapping WooCommerce hook names. Actual combined WP runtime readback showed:
- woocommerce_coupon_is_valid: WebToffee 10 callbacks, StoreApps 12, Woo 1.
- woocommerce_coupon_get_discount_amount: WebToffee 6, StoreApps 1.
- woocommerce_apply_with_individual_use_coupon: WebToffee 2, StoreApps 1.
- woocommerce_applied_coupon: WebToffee 6, StoreApps 3, Woo 1.
- woocommerce_before_calculate_totals: WebToffee 1, StoreApps 1.
- woocommerce_cart_calculate_fees: StoreApps 2, YayCurrency 1.
- woocommerce_shipping_free_shipping_is_available: StoreApps 1.

Hook overlap alone does not imply a bug; actual cart results are below.

## Actual native Woo calculations — 8 scenarios x 5 REAL Yay currency selections

The sandbox owns five synthetic test product amounts USD 60,69,75,79,99 and three isolated coupon codes: 10% off, fixed USD5 off, and valid native free-shipping coupon. Sandbox free shipping instance 1: requires EITHER USD 69 minimum after discounts OR valid free-shipping coupon; flat rate USD20. Actual YayCurrency selector uses the yay_currency_widget cookie, with isolated test currency posts/rates; NO ?currency= spoofing.

The five test FX rates 1.00/0.92/0.80/1.52/1.35 for USD/EUR/GBP/AUD/CAD are SYNTHETIC, not WWW real FX rates. The test uses exact vendor plugin code.

Summary: **40 actual WP-CLI native Woo cart executions, 5 currencies and 8 scenarios, 39/40 shipping-eligibility outcomes in parity with the USD baseline.** A single **EUR at exactly USD69 equivalent** failed; this is a HARD blocker, not 40/40 pass.

Selected genuine native outcomes:
| Scenario | USD cart outcome | EUR cart outcome (test rate 0.92) |
| --- | --- | --- |
| USD60 plain | flat shipping USD20; no free shipping | EUR55.20; no free shipping |
| USD69 threshold | FREE SHIPPING | EUR63.48; **free shipping NOT offered — ERROR** |
| USD75 plain | free shipping offered | EUR69; free shipping offered |
| USD75 with 10% off | USD67.50, only flat shipping20 | EUR62.10, only flat shipping (EUR18.40) |
| USD79 with 10% off | USD71.10, free shipping offered | EUR65.41, free shipping offered |
| USD60 valid free shipping coupon | free shipping offered | free shipping offered |
| USD75 with TWO coupons | **both coupons accepted**, discount USD12.50, only flat shipping | **both accepted**, converted discount EUR11.50, only flat shipping |
| USD60 minus fixed5 | USD55, only flat shipping | EUR50.60, only flat shipping |

All 5 currencies accepted TWO simultaneously applied discount coupons in the isolated setup. This violates the Owner target of one manually applied code per order; the existing plugin stack does not enforce that goal globally. This is a second HARD policy gap.

## Live HTTP Store API confirmation — local-only, no checkout

Using isolated loopback Woo REST Store API and new ephemeral guest cart sessions:
- USD75 plain: HTTP 200 Store API, free+flat rates offered.
- USD75, 10% and 5 USD: both Store API coupon POSTs HTTP 200; discount 1250 cents; free shipping not offered.
- EUR63.48 (USD69 baseline): Store API EUR, only flat EUR18.40, **free shipping absent**.
- EUR55.20 (USD60 baseline) + valid free-shipping coupon: free+flat offered.
- All item-add operations took place only on the NEW isolated localhost sandbox, with synthetic products; NO order, payment, stock or emails. No production or hosted Dev cart POST.

## EUR threshold precision diagnosis — verified root

WooCommerce native free-shipping instance is enabled, requires=either, min_amount 69 base USD, ignore_discounts=no; Yay converted effective min_amount shown 63.48.

At actual EUR USD69-equivalent native request:
- Cart subtotal float at 20-decimal precision: 63.47999999999999687361
- Native shipping minimum float: 63.48000000000000397904
- Delta: negative 7.1054273576010019e-15.
- Native $cart_total >= converted $min_amount evaluates FALSE. At integer currency minor units, 6348 >= 6348 evaluates TRUE.
- Early filter on woocommerce_shipping_free_shipping_is_available saw FALSE before the StoreApps filter; later filter also saw FALSE. Therefore this reproduction is **not caused by StoreApps overriding an otherwise TRUE rate**. Binary float discrepancy in the native EUR boundary comparison accounts for the false negative.
- Source-only fixture in packages/andy-commerce/tests/r10b-eur-shipping-float-boundary-harness.php reproduces captured float ordering and demonstrates integer-minor comparison, but DOES NOT change Woo or the live site.

Do NOT blindly override native free shipping with a late hook; other shipping zones, coupon restrictions and excluded SKUs must be preserved. Any actual shipping fix should be narrow, fail-closed and module-gated, with independent sandbox/native Store API proof before Owner authorizes www activation.

## Acceptance verdict and next engineering work

PASS: exact vendor versions present/active in an isolated local Woo 11.2.1/YayCurrency 3.3.5 environment, 40 actual native cart computations, actual 5 currency selection, local HTTP Store API key outcomes, two-vendor hook attribution and narrow floating-point root cause.

FAIL / OPEN:
1. Single-coupon business rule: both coupons accepted; requires a separate verified global one-code policy guard, including vendor auto-apply and individual-use behavior.
2. EUR boundary: native rate incorrectly absent exactly at USD69 equivalence under test FX. Fix must be precise without overriding legitimate native zone/product exemptions.
3. The sandbox is independent and has *synthetic* FX, no actual WWW promotion campaigns, gift/BOGO activation, country-specific shipping-class exclusions, or full browser checkout quotation. These need separate positive verification; no live checkout performed.
4. The one-coupon and EUR fixes are not deployed to WWW or Dev; Andy Commerce WWW remains global OBSERVE.

Strict next: isolate small patch candidate(s) in a new scoped PR, test them in the same sandbox + Store API; then expand browser/Store API tests to configured exclusion SKUs, auto-coupon/BOGO cases, shipping classes and all currencies. Separate Owner pass before production installation, enabling transaction hooks or exiting observation.

### Local run artifacts

On Windows test machine only:
- D:\ai\r10b-vendor-uat\results\R10B_NATIVE_USD.json
- D:\ai\r10b-vendor-uat\results\R10B_NATIVE_EUR.json
- D:\ai\r10b-vendor-uat\results\R10B_NATIVE_GBP.json
- D:\ai\r10b-vendor-uat\results\R10B_NATIVE_AUD.json
- D:\ai\r10b-vendor-uat\results\R10B_NATIVE_CAD.json
- D:\ai\r10b-vendor-uat\results\R10B_NATIVE_PARITY_DIFFS.json

Only sanitized outcome tables, no copied premium plugin files or customer data, are entered into GitHub. Explicit raw result SHA256 recorded during Windows UAT: USD 1cea65ec784ffd6940666cc8ea7bf75d011326d3f46bb981fe551e2ee64e6d1d; EUR 28b9d4f03dd961dedb1fdcf060c7102f9008c96713c76338aa0f09310825b74b; GBP 137a8fcf8cccb7c06cce5be66a0fd8a14cbabe5852802eadcdc427fbd3cb05edd; AUD d808b989cf18264e98f8bbb8c035cc4075e79294e6866216e218b43c11ec6106; CAD e6f73a532227be4e6df62f73bbb4fdfa9a20a09f55b1b291d62b0a1d7faa9.
