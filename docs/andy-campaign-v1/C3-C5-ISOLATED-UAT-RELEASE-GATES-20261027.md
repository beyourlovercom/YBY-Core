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
