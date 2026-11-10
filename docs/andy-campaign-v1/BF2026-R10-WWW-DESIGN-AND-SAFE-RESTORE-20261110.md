# R11 addendum — 2026-11-10 authoritative live/private routes and category implementation

**This addendum supersedes earlier R10 route descriptions below, including `/clearance/` and `/deals/gift/`. Those older routes MUST NOT be created by the plugin.**

Owner-frozen customer pages (WWW WordPress API verified):
- `/holiday/black-friday/`: page **33288**, PRIVATE, unchanged existing branded design
- `/deals/clearance/`: page **42276**, PRIVATE, parent Deals page **42273** (PRIVATE), product shortcode dynamically reads Woo category `clearance`
- `/holiday/gift/`: page **42280**, PRIVATE, parent Holiday **31680**, sections for `gift-free` and `gift-upgrade`, no active add-to-cart/selection CTA

Real WWW taxonomy assignment (only explicitly prevalidated source-promo items, no price mutation):
- Created Woo product category **clearance ID 2725**, assigned **16** on-sale, in-stock, purchasable simple products already on the existing 19-product BF landing; **16/16** price/SKU/regular price and original categories preserved on readback; private clearance page renders **16/16** products.
- Omitted 3 site products that have **no current sale**: `29799` Pulse Stimulator, `9098` Anita, `30454` Lubricant 35ml. Older candidate set of 24 was **NOT** an approval to assign all 24. `9118` Joy Cup Diamond is WWW canonical, not Dev candidate `9117`.
- Original 16 product taxonomy and prices snapshotted in [Issue #253 prewrite audit](https://github.com/beyourlovercom/YBY-Core/issues/253#issuecomment-6763172176).
- Created Woo gift categories **gift ID 2726**, **gift-free ID 2727** (child), **gift-upgrade ID 2728** (child). All currently have **0** approved assigned gift SKUs. Private page updated with two dynamic, nontransactional Woo category lists; standard prices/add-to-cart hidden for the preview. No unverified gift assigned as a free or paid benefit.

Real Dev isolated Woo Store API guest cart preflight:
- `9096` + `25358` = USD **138**, and + `9095` = USD **217**.
- Woo native add-to-cart, totals/readback and cart cleanup passed; **0 gift cart lines** at both levels. Thus automatic free gifting, paid selection, global warehouse carrier constraints, AffiliateWP, refund and ERP integration remain **NO-GO**. No orders/payments.
- Production ERP `BY489A-Lubes`: ERP stock rows at **us-ny 615**, **us-la 701** (not provider live proof), absent global delivery/cost/liquid proof => **NOT approved** for worldwide free gifting.

R11 source adapter now *only audits existing canonical PRIVATE pages* and uses the already-created `clearance` and parent/child `gift` categories. It **never creates page routes, publishes a page, changes original Black Friday design, or assigns a gift without the explicit approved catalog manifest.** Windows isolated PHP R11 catalog 32/32, R10 price recovery 40/40, US checkout guard 19/19 PASS (91 source assertions). GitHub CI pending exact R11 branch checks.

**Remaining production NO-GO:** This Draft stack still contains older R10 global Woo sale meta modification (US-only stated in marketing): do not deploy until regional pricing vs CDN cache/coupon behavior is corrected. No current gift inventory/fulfillment approval. WWW plugin install owner-manual.

---
# BYL Black Friday 2026 R10 — WWW design-preserving campaign plugin

Date: 2026-11-10. Status: **DRAFT, CODE-ONLY. NOT INSTALLED ON WWW OR DEV. NO PRICE/CATEGORY/PAGE/ORDER WRITES.**

## Authoritative existing landing design

- **Preserve** the existing WWW page `https://www.beyourlover.com/holiday/black-friday/`; do **not** create a second Black Friday landing or overwrite the page's visual structure.
- The Owner says the page is already built but must be viewed while logged in. Read-only review through the existing authenticated Chrome session on China Mac confirmed actual page title **Black Friday - BeYourLover®** and HTTP 200 in that session. Anonymous curl was not able to verify public access (HTTP 445); do not claim public access or publish status from that.
- Main content readback shows existing Woo product cards, `View All Products`, `Black Friday FAQs`, and `SHOP BLACK FRIDAY DEALS`. Existing featured Woo sale cards include Earpod $119->$69 (-42%), Perfume $109->$69 (-37%), Angela $109->$79 (-28%), Ariel $129->$79 (-39%), Squeeze Me $109->$79 (-28%), Leopard Duo $89->$59 (-34%), Eria $99->$79 (-20%), TARA App $99->$59 (-40%). **These are current page display values, not authorized new campaign prices.**
- Existing FAQ explicitly says **Black Friday up to 50% OFF discounts apply only to U.S. customers; free random gifts are global**. Every order has one random gift; USD $199+ orders have two. This copy is authority for country gating and must not be silently contradicted by Woo global sale meta writes.

## R10 source implementation

- Extend the **existing independent** `black-friday-2026` Campaign Pack; do not duplicate a separate competing promotional engine. R10 is a child of R9-C Draft PR #296.
- `R10_Price_Policy`: one-time randomized **15/20/25/30/35/40/45/50%** draw per approved exact SKU when preparing a campaign, held in a persisted ledger. Cannot reroll a prepared/active campaign; never raise the effective price above the prior published price. Random selection is **per product per campaign**, never per shopper/page request.
- `R10_Sale_Manager`: per-product snapshot of **original Woo regular price, sale price, current price, and native sale start/end dates**, plus original SKU and planned campaign sale value. Reject unknown, duplicate, out-of-stock or non-simple SKUs. Start/end in UTC; no activation before start. Apply only after full preflight; audit and journal each product before writing Woo data; add to Woo `clearance` category without stripping existing categories. No action is called by plugin loading. Restore only if Woo current SKU, regular price, sale value and dates still match the recorded application; conflicting human/product writes are isolated and not overwritten. An incomplete restore leaves the ledger for further review.
- Automatic scheduled **price restoration on end timestamp** once a campaign has been explicitly applied; registered deactivation and `uninstall.php` last-resort guarded restoration. **WordPress Plugins UI uninstall can run this callback; raw FTP deletion cannot**. Operational mandatory path remains **End & Restore → verify all SKUs → uninstall**. On conflict, the uninstall path fails closed instead of dropping the only snapshot; categorization intentionally persists for historic clearance page SEO.
- `R10_Catalog`: Woo `product_cat` terms `clearance` and `deals > gift`. Separates approved **random_free (USD0)** items from **selected_upgrade (USD10/15/20)** with warehouse/destination approval; never randomly gifts all premium products just because they share `gift` category. Creates only **DRAFT** `/clearance/`, `/deals/`, and `/deals/gift/` pages when explicitly invoked; does not edit existing page content or publish. Existing `/holiday/black-friday/` page is never touched.
- `R10_Country_Gate`: Classic Checkout and Store API/Blocks before-payment validation against actual U.S. shipping address for active sale SKUs, including variation ID; excludes marked global gift lines from clearance restrictions. Normal other products remain available worldwide. Guest unknown destination is fail-closed when campaign items are present.
- Promotion/country controllers are inert until explicit valid R10 ledger action; preparing categories/pages/prices must **not** be triggered by a normal page request, plugin activation or shortcode render.
- The preexisting R9-C paid gift planner remains **UNREGISTERED** and its real cart POST, automatic random gift fulfillment, destination-verified gift stocks, cart/checkout/ERP/FX/coupons remain separate release blockers.

## Explicit release gates, current status

1. **WWW Owner design read-only preservation: partial PASS**. Authenticated DOM readback, not full desktop/mobile screenshot or anonymous-public UAT.
2. **Source-only price/rollback fixture 40/40**, **catalog fixture 24/24**, **country fixture 19/19**, total **83** in local isolated Windows PHP fixture. GitHub CI must still confirm current exact PR HEAD after PR creation.
3. **DEV/WWW deployment: NOT DONE** for R10; prior Dev R9 display-only preview and WWW design are separate, do not conflate them.
4. Verify **WWW real Woo product ID/SKU, actual source USD price/previous sale dates and stock**, on-location warehouse and country restrictions, and any promotion/price overrides from Smart Coupons, AffiliateWP, YayCurrency. Dev candidate SKU map is not proof of WWW parity.
5. Implement, test and separately authorize **native gift selector on `/deals/gift/`** (one selected USD10/15/20 gift replacing one random slot; fallback random from approved per-country free pool; one slot every order, two at USD199+; US upgrade from USD99+) and coupon exclusions for Black Friday sale SKUs only.
6. Verify restoration after crash, admin edit, schedule expiry, deactivation, UI uninstall; holiday/clearance/gift pages and SEO/redirect contracts; owner desktop/mobile/transaction UAT.
7. **NO WWW writes, plugin install or true purchase/payment until Owner release authorization.** Nov20 prelaunch/Nov23–30 event remains a target; if integration or fulfillment evidence fails, keep gifting and prices OFF, protect live checkout.

## UX reference

Walmart's clearance page is a reference for faceted/listing discovery: category chips, sorting, genuine old/new prices, stock notices. It is **not** authorization to copy Walmart artwork or replace the existing BYL Black Friday design. Source: https://www.walmart.com/shop/deals/clearance .
