# Andy Commerce R9-B/R10-B actual-policy acceptance (2026-10-31)

Scope: www ADMIN READ ONLY, isolated/local tests only. No order, payment, checkout, coupon, shipping or production setting changes.

## R9-B: verified WordPress/WooCommerce configuration

- WWW: Andy Commerce 1.0.14 OBSERVE, WooCommerce 11.2.1, YayCurrency Pro 3.3.5, USD store currency, HPOS yes.
- Woo general settings: enable coupons YES, calculate coupon discounts sequentially YES, calculate taxes YES.
- **Both coupon vendors ACTIVE on www**: WebToffee Smart Coupons Pro 3.2.0 AND StoreApps WooCommerce Smart Coupons 9.51.0.
- StoreApps Woo settings: Auto apply coupons CHECKED; maximum coupon *display* field 5, not an application limit.
- WebToffee General: maximum auto coupons per cart and max coupons checked appear blank; effective applied cap unknown. My Coupons and Store Credits pages enabled.
- WebToffee BOGO list: seven visible offers, FIVE Inactive, TWO Expired, ZERO visibly Active. This does not establish absence of auto-apply normal coupons.
- Native Woo shipping zone list: only Rest of World (zone ID 0) observed; methods:
  - Free shipping instance 1 ENABLED. Requires = either; minimum amount USD 69; ignore discounts NO.
  - Flat rate instance 3 ENABLED, base cost USD 20, taxable; class-specific adjustments ID 932=0, ID 1874=10, ID 1939=30, customer SKU and class attribution not verified.
- Meaning of native either: qualifies if post-discount merchandise >= $69 OR cart contains a valid native free-shipping coupon. Woo compares >=, not strictly >. Custom regional overrides and excluded SKUs not exhaustively audited.
- Additional shipping options: hide costs until address entered OFF, hide rates when free OFF, shipping calculator on cart OFF, shipping debug OFF.
- **Single-coupon-per-cart is NOT proven or globally guaranteed** by observed settings: Woo sequential discounts and StoreApps auto apply enabled, WebToffee can also auto apply. Coupon-specific individual use and plugin collision remain unverified.

## R10-B real UAT status and hard blockers

- DEV plugin list (authenticated): Woo 11.2.1, YayCurrency Pro 3.3.5, Andy Commerce 1.0.14, with NO WebToffee or StoreApps Smart Coupons installed/active. It cannot certify WWW dual-vendor behavior.
- Mac local licensed archives inspected: StoreApps 9.82.2 ZIP exists but mismatches WWW 9.51.0. No WebToffee Pro 3.2.0 ZIP found. Docker binary unavailable on China Mac.
- Actual DEV public Store API GET cart HTTP 200, empty cart USD. GET products 200, UAT-only items returned. The Store API provides cart tokens; no checkout or order completed.
- GET UAT product ID 40028 and empty cart with requested ?currency=USD/EUR/GBP/AUD/CAD each HTTP 200, BUT responses all USD. Query parameter is not a verified YayCurrency switch.
- A direct Dev guest-cart POST was blocked by execution safety controls; did not retry using a different route. NO product-added native Woo price, coupon or shipping quote validated.
- **Full R10-B real two-vendor parity BLOCKED / NOT PASS**; do not label earlier Woo disposable tests or synthetic currency tests as vendor parity.

## R10 supplementary OFFLINE regression

- Extended the offline-only 5-currency USD shipping shadow comparator to support requires=either and explicitly verified synthetic native free-shipping coupon entitlement.
- Under $69: ordinary coupon does NOT bypass; a verified native free-shipping coupon MAY bypass. Missing coupon evidence fails closed.
- Supports only vetted requires=minimum or either; unsupported requirement fails closed. No normal source/bootstrap Woo hooks changed.
- 5 synthetic currencies x (7 prior scenarios + 5 either-policy scenarios + 4 negative cases) = **80/80 PASS**. Synthetic FX, NO live YayCurrency, Woo native vendor plugin or checkout.
- Important: shipping minimum comparison uses synthetic USD 69 baseline; standard Woo minimum is >=69. Do not infer shipping class exclusions or location-specific rates without live evidence.

## Required release gates

1. Obtain test-compatible exact vendor packages WebToffee Pro 3.2.0 and StoreApps 9.51.0 and install together in authorized **isolated** Woo 11.2.1 environment; verify active versions, Woo and YayCurrency 3.3.5 readback.
2. Run authorized native Woo Cart/Store API quote tests with a synthetic guest session across USD/EUR/GBP/AUD/CAD using the **actual** YayCurrency selector and verified rate snapshot; coupon one/two conflict, free-shipping coupon, BOGO, gifts, exempt SKUs, shipping classes, destination rules.
3. Compare actual native Woo quotes vs offline USD-ledger simulation, record any discrepancies and resolve by owner-reviewed source-only change.
4. Maintain WWW OBSERVE. No production mode switch, plugin settings edit, code publish, transaction or payment test without separate Owner pass.

Official context:
- https://woocommerce.com/document/free-shipping/
- https://developer.woocommerce.com/docs/apis/store-api/resources-endpoints/cart/
- https://www.webtoffee.com/docs/smart-coupons/auto-apply-coupons-woocommerce/
