# Andy Commerce — Marketing OS V1 / BOGO & Promotions Canonical Contract
Date: 2026-10-12
Status: **M0 architecture + M1 admin read-only prototype**. No live promotion implementation, settings activation or production deployment.

## 0. Canonical source / current runtime
- **Sole current source repository**: `beyourlovercom/YBY-Core`, package `packages/andy-commerce/`. GitHub `main` is the merge target; this feature branch is isolated.
- **BYL Platform is retired**; do not reactivate or develop it. Old `platform/byl-platform` files in the website repository are historical migration references, not runtime owners.
- Read-only Dev WP inventory on 2026-10-12: `andy-commerce 1.0.1` ACTIVE, `yby-core 1.9.0` ACTIVE, `woocommerce 11.2.1` ACTIVE, BYL Platform ABSENT from plugin list.
- Andy Commerce 1.0.1 consolidation [YBY-Core PR #67](https://github.com/beyourlovercom/YBY-Core/pull/67) exact HEAD `a0e5cada8b7e571733f6329dd6c8da5ffff4377b` was observed **open/unmerged** at audit; Dev running a version does NOT mean GitHub `main` contains that PR. It establishes BYL49 legacy read-only policy handoff: historical `byl_shipping_promotion_settings.global_threshold=49` and Commerce option `...v1.global_threshold=69` differ; policy handoff marker is NOT automatically written.
- All promotion work must preserve PR #67 contracts and be reconciled with the latest `main` and PR #67 **before any merge or Dev replacement**. Never patch live plugin source manually without a separate exact-head deployment gate.
- Website Cart Drawer Draft PR #63 stays separate; its Campaign Banner/Service Note slots and Free Gift both default **OFF**. Woo Cart is the single source of order, discounts, taxes, shipping and coupon accounting.

## 1. Decision: Marketing = operations UI, Andy Commerce = one promotion engine
- Operator experience: WordPress admin `Andy Commerce → Marketing / Promotions`, with eventual link from WooCommerce Marketing navigation after its actual menu capability/slug is verified. This is a **screen within the existing Andy Commerce plugin**, not a new plugin or second runtime.
- **Andy Commerce** owns promotion definitions, eligibility evaluation, BOGO/bundle/Spend & Save calculations, Woo hook integration, versioned policies, security, audit, scheduling and activation gates.
- **WooCommerce** owns canonical cart line totals, tax, shipping, coupons, orders, returns and settlement; the promotion engine only uses official Woo hooks and server-side decisions.
- **BYL Theme** displays only read-only progression/campaign marketing slots, never decides, creates or applies rewards from JavaScript.
- **ERP Marketing** may later organize campaign plans, segmentation/EDM, reporting and analytics; it cannot independently recalculate live Woo discounts or overwrite Andy Commerce's Woo campaign settings. A future signed API is optional and separately authorized.
- **Andy Core** is a generic addon foundation; don't introduce BYL-only discount policies into Core.

## 2. Owner-approved shared threshold money basis
Canonical V2 `post_discount_merchandise_subtotal_excluding_tax_and_shipping`:
- A Woo-canonical sum of eligible merchandise line totals after eligible item/cart discounts, with **line taxes, shipping, shipping taxes and other fees removed**. Never use gross order total; do not add gift value or zero-priced reward lines to eligibility.
- Calculate in currency minor units with Woo-decimal-aware rounding, respect currency, country/zone, product exclusions and quantity limits.
- For BOGO/bundle and Spend & Save, define evaluation order to avoid self-discount recursion: base cart + one manually entered coupon → choose highest-priority eligible automatic promotion (nonstackable default) → discount line allocation → recompute other threshold rewards such as free shipping on the resulting discounted merchandise. Never let a gift count toward its own threshold or recalculate endlessly.
- Example merchandise $120 with $20 coupon => $100 qualifying; with $30 coupon => $90 qualifying. Threshold $100 at $90 means $10 remaining.
- **M0 blocker**: Current legacy shipping uses `pre_discount_merchandise_subtotal` and Woo displayed subtotal can follow tax-inclusive display; the new V2 basis MUST NOT be used to assert Woo free shipping unlocked until a single approved Andy Commerce versioned policy is activated in all relevant Woo shipping hooks.
- Do not silently rewrite current $49/$69 region settings or `andy_commerce_byl_promotion_handoff_v1` marker. Existing checkout/free shipping remains unchanged until separate Owner config migration authorization.

## 3. Campaign categories and admin fields
- Campaign types: BOGO Buy X Get Y; Bundles fixed-price/set percent; Spend & Save fixed/percent or thresholds; Tiered quantity; Free Gift; Free Shipping.
- Per-campaign: id/version, name, **Draft** by default, campaign type, eligible/product rewards incl. variable SKU and stock rules, effective date window, zone/country/currency, priority, compatible code/auto campaigns, max redemptions/per-customer, explicit exclusions, source owner, audit trail, rollback.
- The **admin is a management layer over Andy Commerce rules**. No general “Marketing” plugin may independently hook `woocommerce_before_calculate_totals`, `woocommerce_cart_calculate_fees` or `woocommerce_coupon_*` to produce duplicate discounts.
- One manually entered coupon per cart remains the existing default; automatic promotions do not occupy a coupon slot, but **are nonstackable by default**, including with the manual coupon, until explicitly approved compatibility rules exist.
- Never show misleading “free gift unlocked” before approved gift SKU, zone, inventory, return/removal and Checkout server validation exist.
- Campaign targeting privacy, consent and limited user data are separately gated.

## 4. M1 code-only admin shell contract
- Add a **read-only tab** `Marketing / Promotions` inside `Andy Commerce` admin (`manage_woocommerce` required).
- Display the supported campaign categories, stage, and **OFF / Not activated**; no POST forms, no handlers, no DB table, no option writes, no Woo hooks and no “Publish” control.
- This skeleton prepares Owner design UAT only. WordPress Marketing top-level menu navigation and actual campaign CRUD come after Owner approval and plugin PR #67 reconciliation.

## 5. Gates and roadmap
1. **M0 read-only audit** (this branch): confirm live plugin inventory, source repo, PR #67 dependency, current options/hook owner, Woo region behavior, old/new basis mismatch.
2. **M1 admin design and shell** (code only): one Marketing/Promotions read-only screen, action taxonomy, M390+desktop admin UX contract and security tests, Owner design gate.
3. **M2 engine pure sandbox**: normalized cart, discount eligibility, allocation, stacking and deterministic coupon/BOGO/bundle rules with no WordPress writes; tests for taxes, currency, product variations, refunds and looping.
4. **M3 gated persistence**: draft-only campaign CRUD in Andy Commerce; schema migration/DB writes only with independent Dev authorization.
5. **M4 shipping versioned policy transition**: shadow evaluate old BYL49 and new post-discount excluding-tax basis across zones/coupons; migrate ONLY after explicit Owner decision and rollback.
6. **M5 read-only progress API**: Woo Store API extensions for true active shipping/gift states; Theme bars inactive until business policy agrees; avoid stale cached cart state.
7. **M6 synthetic-cart Canary**, M7 Owner E2E UAT, M8 GitHub main merge and WWW code/activation independent gates.
- Do not restart READY DC devices; do not create orders, real payments, coupons, gifts, Woo shipping settings changes, Production business writes or WWW deployment without separately authorized gates.
