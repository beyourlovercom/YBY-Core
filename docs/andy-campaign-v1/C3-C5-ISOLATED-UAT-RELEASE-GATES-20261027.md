# Andy Campaign C3–C5 isolated UAT and release gates
Date: 2026-10-27. Owner-approved development continuation. This is a **readable evidence ledger**, not authority to deploy to production.

## Scope / frozen architecture
- Andy Core alone owns WordPress `landing_page` CPT, native Landing Page list/create/edit and unified L2/L3 admin navigation.
- Andy Commerce continues to own Currency OS, checkout/payment/shipping integration and prior Marketing V1 Dev-UAT source; do not replace its Dev installed package or main branch with a stale PR.
- Andy Campaign Lite is standalone Code-First kernel; BF2026 is an independent PHP plugin. Both require separately reviewed packages and OFF-by-default activation.
- Existing marketing Dev/WWW and other READY machines left untouched by this C3 test. All WordPress installation work happened in a fresh **US Mac mini local-only test tree** under `/Users/a/AI/uat/andy-campaign-c3-20261027`. Dedicated database `aicc_campaign_c3_20261027`. HTTP loopback only at `127.0.0.1:18974`. Original Currency/P6 database/site not changed. Local fixture credentials are private, never committed.

## C3 — isolated WordPress runtime evidence
- Local WordPress installed to new DB; actual WooCommerce, Andy Core and Andy Campaign activated and load successfully.
- Using updated Andy Core navigation adapter from this PR, `YBY_Andy_OS_Navigation::destination('andy-core-marketing')` returned `admin.php?page=andy-campaign&tab=campaigns`; legacy old Core initially pointed to Commerce (proving adapter dependency).
- Campaign L3 readback: `活动列表,执行记录,系统设置`; original Core Landing Page links `全部落地页,新建落地页` and `edit.php?post_type=landing_page` unchanged.
- Real native WordPress `landing_page` draft was created *only in the new isolated DB*, verified CPT and `get_edit_post_link()`, then deleted. No Core CPT/data migration or page copies.
- Loopback HTTP: homepage **200**, anonymous admin request **302** to authentication, authenticated Andy Campaign admin **200**, settings GET **200**. Authenticated nonce-bearing settings POST ON and OFF each **302**, subsequent WordPress option readback `andy_campaign_lite_master=no`.
- Isolated demo-pack: initial OFF yields `DEMO_HOOKS=0`; ON yields `DEMO_HOOKS=1`; after OFF returns `DEMO_HOOKS=0`. An anonymous request lacks `manage_options` and `manage_woocommerce` capabilities.
- **C3 evidence state**: isolated server-side + basic HTTP admin/permission **PASS**; not an Owner visual browser signoff, and not a Dev deployment or full role matrix.

## C4 — independent Black Friday pack evidence
- New plugin source at `packages/andy-campaign-packs/black-friday-2026/`. Registers only through `andy_campaign_register`, resources=`gift`; when core OFF, no promotion hooks launch.
- Woo native sale prices remain the sole authority for “Up to 50% OFF”; no duplicate discount engine.
- After native coupons, eligible USD merchandise line net (excluding taxes, shipping, upgrade gift lines) >0 gets 1 gift; net >= USD199.00 gets 2. Gift catalog is code-filtered and empty by default; explicit fixture-only isolated SKUs required.
- Real separate-database Woo cart test with four hidden fixture products:
  - USD205 merchandise yields 2 slots.
  - Gift #1 costs USD0; premium gift #2 upgrade USD9.99; native merchandise+gift total USD214.99.
  - Woo native USD20 fixed-cart coupon reduces merchandise basis to USD185; entitlement becomes 1 and extra gift is removed.
  - USD180 earns 1; empty cart earns 0; Kernel OFF removes marked gift lines.
- Pure PHP contract covers catalog validation, USD199 boundary, coupon eligibility, duplicate/unqualified gift handling, USD-only fail closed and native-coupon gift exclusion.
- POST gift pick and removal flow is implemented with nonce; local full GUI shopper test is not signed.
- Attempt to execute actual native checkout/order creation+cleanup was blocked by platform safety inspection before execution. **No order transaction or refund verification is claimed; no workaround attempted.**
- **C4 evidence state**: PHP source, CI, real Woo cart **PASS**; native Classic/Blocks order item metadata, Stripe refunds, tax/shipping and real FX checkout remain **OPEN/NO-GO**.
- Business-policy release questions remain: a shopper may opt not to select a gift (no automatic fallback); refund clawback policy and overlapping third-party discounts not yet finalized.

## C5 — packaging and release boundary
- GitHub Draft PR #242 remains open; no main merge or official Dev/Stable/WWW deployment.
- Exact source commit `17dfdf3d6711d9884081445a692537e0a2cf185e`: **6/6 applicable GitHub Actions SUCCESS** (Andy Campaign Lite CI, package preview, Andy Core Regression, Release Package Validation, Newsletter M1, Inquiry Inbox).
- Package preview run https://github.com/beyourlovercom/YBY-Core/actions/runs/40521479718
- Artifact ID `12742520505`, name `andy-campaign-c3c5-preview`. Contains two independently installable, *not production signed* ZIPs and SHA256SUMS:
  - `andy-campaign-0.1.0-preview.zip`: `6c7794a26fdb3455be69b4cc618b59cf665bb7079b25860a2b58c37453b2738f`
  - `byl-black-friday-2026-0.1.0-preview.zip`: `aa3c195e7acf4b961c34143a05d1e6c4ec2401b6b947c480b53fec588d2c03fc`
- Neither package contains a test harness or writable PHP code editor.
- **C5 release signoff = NO-GO** until C4 real native Woo orders/Store API/FX/tax/stock/refund test, separate Dev version/publisher coordination with Currency PR #213, security/role and visual UAT, staged rollback and explicit Owner Production approval. Keep both global and campaign switches OFF.

## Continued work
1. Preserve this exact source + hashes; review pack gift policy including no-selection and refunds.
2. Owner-supported manual isolated order checkout, or an otherwise authorized test surface, with positive Classic and Blocks + refund, tax and currency cases. Do **not** bypass the platform safety block.
3. Once all commerce gates genuinely PASS, coordinate the existing sole Dev publisher and owner signoff, then only separately stage C5 Dev and Production. Do not change WWW under this Draft PR.

## Continuation — Code-First safety and role gate (2026-10-27, after baseline)
Source proof at `5892110b91e8c8a70927317f63fa5d16c0c267f0`, with later README-only changes tracked in this branch:
- Added optional **host-level `ready` predicate**, checked on both runtime eligibility and authorized operator enable POST. False/errors fail closed with `pack_not_ready`; no shopper pricing callback is attached. An array-valued admin field is rejected before WordPress scalar sanitization. The full host still defaults OFF.
- BF2026 readiness requires >=2 different, in-stock, purchasable **free SIMPLE gift products** from its reviewed PHP catalog. A premium-price upgrade cannot substitute for either promised free choice. An empty catalog remains OFF. Stockouts/duplicates/invalid gifts are removed on cart recalculation. Non-USD currency fails closed. Shopper POST mode, nonce, gift ID/cart key reject array-shaped input. All Landing Page content remains Core-owned.
- Pure tests: `ANDY_CAMPAIGN_LITE_DECISIONS_PASS=11`, `BYL_BF2026_PURE_CONTRACT_PASS=17`, `ANDY_CAMPAIGN_CORE_NAV_PASS=8`. Pure order metadata callback tests are **NOT** real persisted orders.
- Separate live isolated WordPress/Woo checkout-free tests: when global enable exists **before the request boots**, gift hooks launch; a second free SKU provides a valid catalog; depleting it => `pack_not_ready`; restoring it => `eligible`; switching store currency to EUR => `pack_not_ready`; stockout of an in-cart premium gift revokes that item. All fixture product stock, currency and global master were restored (master `no`). An earlier same-request test incorrectly toggled master after WP bootstrap and failed because no hooks were attached; after correcting setup to a **new booted request**, all stockout checks passed. Do not classify this as a code regression.
- Real read-only employee UAT: isolated Woo Shop Manager has `manage_woocommerce` but not `manage_options`; authenticated GET `admin.php?page=andy-campaign` => **200**, no campaign mutation form rendered, unauthorized POST `admin-post.php` => **403**, master still `no`. Temporary test employee removed and loopback HTTP server stopped.
- Checkout Blocks **source-level compatibility only**: installed WooCommerce `src/StoreApi/Utilities/OrderController.php::update_line_items_from_cart` invokes `wc()->checkout->create_order_line_items`, which invokes `woocommerce_checkout_create_order_line_item` in `includes/class-wc-checkout.php`. This confirms a shared hook invocation path for gift metadata, but **not** an actual Store API order/HPOS/refund or payment test.
- Source commit `5892110...` six GitHub PR CI workflows success. Package preview artifact from run `40525581899`, artifact ID `12742824195`, digest `sha256:47e8d0fefb59606f50a94565799b60a6bfbd98612e30614fc86af1b8a5aea5bc`, preview ZIPs and SHA256SUMS:
  - Lite ZIP: `21a7af5a8fbf3a34140e9d71feeaa84b7fbce34730ea815b65b633eb605c1f54`
  - Black Friday pack ZIP: `982960b991adea0a49954587ac12ce255443a88c84af1edf839944492799003a`
  - These are **unsanctioned preview packages**, not a signed production release. Later commits must use their own workflow runs/digests.
- Remaining owner/site acceptance: actual isolated Woo Classic/Blocks order+refund (blocked by the prior safety restriction), tax-inclusive and dynamic-shipping combinations, HPOS/order-item verification, live real FX/multicurrency Checkout, real gift SKU selection, shopper-responsive UI, refunds/coupon stack and no-selection policy. **C5 Production = NO-GO; PR Draft**, no merge or official Dev/WWW deployment.

## Additional C4 native Woo cart/Blocks/Taxes/Shipping regression (continuation)
All results are from the **existing dedicated local-only US Mac mini WordPress database**, *not* official Dev or WWW. No real order, payment, shipping label, fulfillment or customer records were created.

### Native inclusive tax
- In one isolated request, simulated US CA **8.25%** rate via Woo native `woocommerce_matched_rates` and `woocommerce_base_tax_rates` filters, and temporarily enabled `woocommerce_calc_taxes=yes` / `woocommerce_prices_include_tax=yes`.
- Merchandise labeled **USD205 tax-inclusive** became Woo merchandise `line_total=189.37644342` excluding **USD15.62** sales tax. The USD199 threshold correctly evaluated **18,938 cents**, yielding **one gift**, removing the ineligible second gift.
- Shop tax options restored to `no/no`; Campaign master restored `no`. This is an isolated tax-rate simulation, not a claim about live multi-jurisdiction tax engines.

### Native Woo Blocks Store API cart read
- `WP_REST_Request('GET','/wc/store/v1/cart')` returned **200**, three cart lines and `USD`: merchandise **20,500 minor units**, free gift **0**, paid upgrade **999**; total **21,499 minor units**.
- This validates **Store API cart serialization/read**, not a Blocks payment or persisted order.

### Native shipping with coupon
- The isolated WordPress install initially had no enabled shipping method. One attempted shipping-only test was **invalid** because Woo `WC_Cart::needs_shipping()` returns false if shipping method count is zero (not a Campaign calculation failure).
- Added a **temporary WooCommerce flat-rate shipping method** with cost **USD12.34** in isolated Zone 0, then executed separate native Woo cart requests:
  - USD205 merchandise + one free + one USD9.99 premium gift + shipping USD12.34 = **USD227.33** and **two slots**.
  - Applying isolated native USD20 fixed-cart coupon reduced qualifying merchandise to **USD185**, revoked the second gift, left shipping at **USD12.34**; final total **USD197.34**.
- Temporary shipping method and option deleted, isolated cart emptied, Campaign master restored **OFF**. This is a native flat-rate stand-in for a dynamically selected Woo shipping rate, **not** certification of every third-party carrier/dynamic shipping provider.

### Fixed-cart coupon upgrade-fee P0 found and repaired
- **Initial reproducible failure** before fix: coupon USD20 on merchandise USD205 + one premium gift USD9.99 incorrectly yielded merchandise **USD194.99** and premium upgrade **USD0**. Thus the previous `woocommerce_coupon_is_valid_for_product` guard alone **did not protect fixed-cart coupons**, due Woo `get_items_to_apply_coupon` accepting globally valid coupons.
- **Root cause traced in installed WooCommerce 11.2.1 source:** `WC_Discounts::get_items_to_apply_coupon()` lets `is_valid_for_cart()` bypass per-product coupon applicability. Woo 8.8+ instead offers `woocommerce_coupon_get_items_to_apply` filter before discount allocation.
- **Patch:** filter the Woo discount-item list to exclude all entries with this Campaign's protected gift marker for *all* coupon types; require WooCommerce >=8.8.0 for BF pack readiness. Existing per-product guard remains supplementary.
- **Corrected native Woo readback:** merchandise **USD185** after USD20 coupon, premium upgrade **USD9.99** unchanged, final **USD194.99**, one gift. This matches the Code-First campaign policy.
- Other newly tested boundaries: gifts on backorder or with zero managed stock do not qualify for the two-free-gift promise; invalid gift before payment is rejected by `woocommerce_checkout_validate_order_before_payment` for Store API; classic cart notices and pre-payment Store API share a single validator. Pure PHP contract suite **BYL_BF2026_PURE_CONTRACT_PASS=24**.
- **Source code/isolated cart tests are NOT real persisted Classic or Blocks checkouts.** Actual HPOS/Checkout/Stripe, full refund, multi-currency FX and full visitor browser UAT remain release-blocking, as previously recorded.

### Protection / ownership
- Core-owned `landing_page` CPT and native menu remain untouched.
- Campaign master was restored OFF in the isolated site after each verification; no live Dev or WWW activation, no merge of Draft PR.
- Vendor checkout integration remains isolated from the separately active Currency/P6 branch. No claim of Production readiness.

## CI base-commit synchronization after concurrent Core main updates
- While PR #242 remained Draft, the independent Andy Core Notification PR #243 advanced `main` from `03422efc4...` to `5bb00a71de3600a0f9bc2b895d76e0cfef33d296`.
- A prior shared **Release Package Validation** job rejected its own stale GitHub PR base payload while GitHub generated a merge test with the newer main as parent; it did not reach campaign packaging. It is **not** counted PASS.
- Refreshed PR #242's existing `main` base to the current SHA through GitHub PR metadata only, without force-push, merge to main, or deployment. Fresh synchronized PR CI is required to supersede the stale provenance failure.
- No production action is authorized by this refresh; PR remains Draft.
