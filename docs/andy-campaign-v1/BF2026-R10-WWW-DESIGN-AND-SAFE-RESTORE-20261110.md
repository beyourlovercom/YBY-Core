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
