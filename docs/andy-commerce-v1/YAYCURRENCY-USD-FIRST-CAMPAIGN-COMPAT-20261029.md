# Currency OS → YayCurrency compatibility: USD-first campaign contract (2026-10-29)

Status: **Draft / DEV-only, NO-GO for payment or WWW**. Source PR branch stacked on Commerce integrator PR #213; does not supersede the 2026-10-29 freeze on new self-built FX/payment features.

## Owner decision 2026-10-29: **Pro-only, approved Dev tests**
- **YayCurrency Pro is mandatory for the five-currency release** (USD, EUR, GBP, AUD, CAD); the Free plugin may not be used as a release substitute. Code now fails closed when Pro is absent, Free is active, or Free and Pro coexist. Synthetic contract now includes **73 source-only assertions**; separate real five-currency Dev read-only pricing checks are documented below and do not establish payment compatibility.
- Pro's installed/plugin-active status is not proof of a valid vendor **license**, configured five enabled currencies, real Woo/Yay checkout sessions, payment gateway support or webhook processing. Each remains a signed Dev UAT gate.
- Official upgrade process: first obtain a legitimate YayCurrency Pro archive and authorization from YayCommerce or WooCommerce.com, preserve current Dev config and a rollback copy; **only then** deactivate Free, install/activate Pro, and verify license. Do not download a nulled ZIP, invent a license, silently purchase a subscription, or disable the existing working Free instance prematurely.
- The official Pro plugin entry point can be `yaycurrency-pro/yay-currency.php`; the Pro-only adapter checks a small explicit allowlist of possible vendor file basenames. Verify the actual plugin basename against the licensed ZIP before runtime promotion; do not infer Pro from the current Free `Yay_Currency` shared PHP namespace alone.
- Approved testing **only on dev.beyourlover.com**, with all test gateways still OFF until an authorized verified TEST merchant and prior platform safety requirements permit real payments; no WWW/main change.
- Vendor docs: https://docs.yaycommerce.com/manual/getting-started/update-plugin ; https://docs.yaycommerce.com/yaycurrency/getting-started/enter-license-key ; https://docs.yaycommerce.com/yaycurrency/why-upgrade .


**R7 review addendum:** the original synthetic adapter had incorrectly granted one free gift for an otherwise zero-dollar basket. The source now returns 0 eligible gifts when verified source USD merchandise is exactly 0; three dedicated regression assertions cover the pure basis function, a Woo-shaped zero-price cart, and a full-cart coupon reducing the USD merchandise basis to zero. This is source-only and not proof of real Yay/HPOS/Stripe payments. The synthetic check count advances from 51 to 54. CI release provenance remains strict after PR #213 base updates; do not disable it to turn a failed check green.

## Owner-approved architecture
- WooCommerce store base currency is **USD**, canonical promotion thresholds and budgets expressed as exact **integer USD cents**.
- **YayCurrency is the sole storefront price/checkout currency engine.** Do not run the Andy Currency R8–R13 frontend converter, payment bridge, or checkout currency filter at the same time. Existing `Andy_Commerce_Currency_Runtime_Dev_V1::legacy_conflict()` already recognizes YayCurrency slugs.
- **Andy Campaign** owns business eligibility (gift count, upgrades, coupon policy), not exchange rates; Andy Commerce can supply a **read-only, server-verified USD merchandise net quote** from trusted Woo product/coupon/campaign data. The new adapter must not hook gateway/order updates.
- For Black Friday gift eligibility: **USD merchandise AFTER approved coupons, BEFORE tax and shipping**, EXCLUDING gift lines, gift paid upgrades, other campaign nonqualifying lines; **>= 19900 USD cents** grants **2 gifts**, lower qualifying orders grant **1 gift**. Evaluate in USD once, independently of shopper currency.
- Never maintain separate authoritative EUR/GBP/AUD/CAD gift thresholds. Localized "about €X more" prompts may call YayCurrency's published convert API for display only and must be clearly approximate where rounding, fixed currency pricing, conversion fees, discounts or timing could differ. Display amounts must NEVER authorize gifts.
- Woo native line/cart/order totals remain authoritative for amount actually due and paid. No inverse conversion of rounded local Woo totals as proof of source USD entitlements. FX reference rate is NOT gateway settlement rate. Never derive entitlement from arbitrary browser-submitted totals or cookie currency codes.

## Verified DEV runtime before compatibility integration
- `https://dev.beyourlover.com`, Woo 11.2.1, Andy Commerce 1.0.12, base USD, taxes calculation OFF, HPOS enabled.
- 2026-10-29: installed **YayCurrency Pro 3.3.5**, active at actual entry point `yaycurrency-pro/yay-currency.php`; Yay Free is no longer active. Five live Dev currency records: USD #40058, EUR #40060, GBP #40062, AUD #40063 and CAD #40064. Woo Currency OS `andy_commerce_currency_runtime_dev_v1.enabled` remains `"false"` (not strict `true`), TEST Stripe gateway OFF and R13 Canary OFF. WWW untouched.
- YayCurrency Pro is installed and the five currency records are published; payment eligibility/Pro vendor license validity and full browser checkout remain distinct from plugin-activation and read-only UAT.
- YayCurrency initially created a native `yay-currency-manage` post titled USD. Adding test EUR/GBP must go through supported plugin settings/API, not hand-writing guessed CPT metadata.
- R6 `Andy_Commerce_Campaign_USD_Basis_V1::quote()` presently depends on the old `Andy_Commerce_Currency_Runtime_Dev_V1` rate source and selected currency and will correctly fail with Yay enabled. It must NOT be used as proof of Yay compatibility.

## Evidence — isolated Woo and real Pro read-only source USD acceptance (2026-10-29)
- Source-only/mocked agreement: **73 assertions PASS** for five target currencies, gift/upgrade exclusion, $69/$199 thresholds, fixed_cart, percent and fixed_product native replay, Pro-only guard, fail-closed conflict and Woo USD settlement fallback.
- Native WooCommerce 11.2.1 disposable CI run [40840569338](https://github.com/beyourlovercom/YBY-Core/actions/runs/40840569338): 7 genuine Woo `WC_Discounts` replay cases PASS (fixed_cart, percent, fixed_product, multiple quantities, multiple SKUs, and unknown coupon rejection), no gateway and 5 test fixtures cleaned.
- Dev actual YayCurrency Pro read-only PHP CLI: `YAY_DEV_PRO_READONLY_PASS=3` independently for **USD/EUR/GBP/AUD/CAD**; verified original USD 19899 / 19900 / 19901 cent qualifications map to 1 / 2 / 2 gifts in every shopper currency without Woo order/payment/session/product persistence. Actual PHP code in `tests/campaign-yay-dev-pro-preboot.php` and `tests/campaign-yay-dev-pro-readonly-uat.php`. In this test Woo checkout currency remained USD because Pro option `yay_currency_checkout_different_currency=0`.
- Actual Woo Store API product and empty cart GET for each selected currency returned locally converted numeric values/symbols, but `currency_code=USD` with fallback Checkout mode. **Do not assume this proves different-currency payment, cart-with-coupon totals, or native Store API currency metadata consistency**.
- Security finding: old `Andy_Commerce_Currency_Runtime_Dev_V1::legacy_conflict()` missed actual Pro entry point `yaycurrency-pro/yay-currency.php`. Added strict actual-slug conflict detection; no self-built converter may be turned on simultaneously with Pro.
- Current adapter is source-only and loads with **no payment/price/coupon/shipping hooks**; it has NOT yet been connected to runtime Campaign gift allocation, nor deployed from PR #258 onto live Dev. Real compound coupons, BOGO, Bundle and fixed-foreign-price variants remain unsupported/fail-closed until native evidence.

## BF2026 consumer handoff and reproducible CI closure evidence — 2026-10-29

- Dev Black Friday 2026 gift selector v0.1.0 must NOT be treated as compatible merely because Yay Pro is active. Its original runtime references old `Andy_Commerce_Currency_Runtime_Dev_V1`, and its USD-only path can misinterpret locally converted Woo totals as USD.
- Verified original Dev gift class source SHA256: `b08dc6993b533387eb2811c9d56ef5b216b65d0c04bd901b92ccd8a1587e147d`. Guarded builder `integrations/black-friday-2026/build-dev-yay-gift-bridge.py` generated **candidate only** at `~/aicc-dev-staging/yaycurrency-pro-usd-uat/black-friday-2026-candidate.php` with SHA256 `9111c0cb31605ff1111a40586b399218d84ec5c70f2d3c15ec55a68ea017f58a`; PHP lint PASS. Live Dev plugin file was NOT replaced.
- Candidate routes BF gift entitlement to `Andy_Commerce_Campaign_Yay_USD_Basis_V1::quote($cart)`, with source USD merchandise after approved Woo coupons, gift and paid upgrade excluded; no added checkout hooks. Local upgrade-fee label is approximate when native checkout falls back to USD. The native Woo product price setter always starts with source USD.
- Reproducible BF independent candidate class test: `tests/bf2026-yay-patch-fixture.py` generates a synthetic source candidate, then `tests/campaign-yay-bf-bridge-synthetic.php` executes **54 PASS** threshold, coupon, gift-exclusion, and fail-closed checks across USD/EUR/GBP/AUD/CAD. The test fixture is NOT the entire installed BF plugin. This proves transformed core method behavior, not live WordPress registration.
- Native isolated WooCommerce CI [run 40848163738](https://github.com/beyourlovercom/YBY-Core/actions/runs/40848163738): **11/11 PASS** including fixed-cart, percent, fixed-product, two SKUs, quantity 2, unknown coupon, and USD coupon min/max boundaries at 199.99/200.00/200.01. All five disposable fixture objects removed. Newest child PR head `0f1ae8fffeff5f852c0a782ec9a300189fea34db`: 6 CI SUCCESS + 1 conditional publisher SKIPPED.
- **Remaining blocker:** An earlier attempt to run the complete candidate with the live Dev Woo plugin intentionally skipped returned a WordPress CLI critical error; diagnostic safety check prevented obtaining the causal trace. No attempt to bypass it. Must not deploy candidate nor enable actual BF gift allocation until independently verified Dev plugin loading/cart/checkout UAT.
- Sole publishing authority remains Commerce draft PR #213. No GitHub main merge, Stable, WWW deployment, payment test or change to existing Dev BF runtime.

## Acceptance matrix before any DEV campaign enablement
1. USD native no-coupon, USD fixed-cart coupon, unrestricted percent, Woo sale price and capped discount; exact 19899 / 19900 / 19901 USD cents thresholds.
2. EUR and GBP with Yay Free; after authenticated Pro installation additionally AUD and CAD. USD eligibility MUST remain identical on every shopper currency.
3. Multi-SKU and quantity >1 with native Woo allocation, cart refresh, coupon apply/remove, gift line exclusion, premium gift upgrade exclusion, $69 free-shipping interaction, taxes and shipping excluded.
4. Fixed product/percent/cart coupons with restrictions, BOGO, stacking, fixed local price, shipping fees, coupon allocation mismatches and unsupported pricing engines MUST be verified with exact server-side evidence or **fail closed** (no free premium award on guess). Do not silently assume every plugin discount is recognizable.
5. Cart/Checkout/Thank You amounts, native Woo coupon totals and storefront currency must remain correct; if currency engine disabled/updated, check backward compatibility/rollback. Payment and refunds remain separately gated; no ordinary Dev checkout test creates real transactions.
6. Confirm selected Yay currency across AJAX/Store API, native Classic Checkout and cart drawer, logged-in / guest and cache; verify Woo Order total on isolated test only.
7. Read-only first; all DEV campaign runtime changes require a separate CI + signed publisher #213 integration UAT. No main merge/Stable/WWW or YayCurrency activation on WWW.

## Fail-closed rule
If the trusted USD after-discount basis is not provable for this exact cart, return an **ineligible / needs-verification** state with an explicit reason. Do not grant 2 gifts based on the displayed EUR/GBP amounts and do not call YayCurrency's inverse conversion of a rounded cart total as entitlement evidence.

## Vendor source
- https://docs.yaycommerce.com/yaycurrency/configurations/developer-zone/available-yaycurrency-hooks (conversion/reverse-conversion, cart subtotal/discount hooks; some are Pro-only).
- https://docs.yaycommerce.com/yaycurrency/why-upgrade (Free 3 including base; Pro unlimited).
- https://wordpress.org/plugins/yaycurrency/ (YayCurrency v3.3.5 package and supported switcher).
