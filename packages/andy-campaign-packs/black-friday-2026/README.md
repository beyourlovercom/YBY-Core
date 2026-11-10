# BYL Black Friday 2026 — v0.2.1-dev-rc2 (Canary ONLY)

**Release status:** R14 test-account Canary / Draft. Not authorized as a public Gift engine. Existing WWW page design and native product prices are not modified by this package.

## Fixed campaign and pages
- Dates: **2026-11-10 through 2026-11-30**, America/Los_Angeles.
- WWW pages: `/holiday/black-friday/`, `/deals/clearance/`, `/holiday/gift/`. Existing product categories and approved 16 Clearance items are separate from coupon or Gift activation.
- Merchandise over USD0: **one free random gift**. Post-native-coupon USD merchandise >=199: **two distinct free gifts**. US shipping + USD99 or more: customer may pay to choose **one** USD10/15/20 gift in place of one free slot (never a third gift).
- All-page cart entitlement rather than landing-page URL trigger. Promotional gift selection is printed on the existing Gift page only when authorized and ready.

## Canary release safety
1. The entire plugin is OFF for normal users by default. Neither installation nor `Andy Campaign Master=ON` alone makes Gift hooks globally eligible.
2. An administrator visits **WooCommerce → BF2026 测试**, explicitly enables a **30-minute** test session for their **current browser only**, then tests real Woo cart. Single administrator user ID + secure HttpOnly/SameSite=Lax browser token + hashed server option; wrong account/browser/guest/expiry denied.
3. Under Andy Campaign Master OFF, only the allowlisted browser detaches the host's automatic Gift-line cleanup **for that request** and boots BF2026 hooks. No change to other users' host session handling.
4. **Stop** from the same page, or let 30 minutes expire. Host will revoke leftover Gift rows on subsequent non-Canary cart requests. Full public Gift mode requires a **separate explicit reviewed release constant** `BYL_BF2026_ALLOW_GLOBAL_GIFTS` that this package never defines.
5. The R10 global Woo sale-price write path stays blocked by default because US-only discounts cannot simply modify `sale_price` for non-US shoppers. Existing normal Woo prices are untouched by Canary.
6. No real payments, production orders or fulfillment shipment during Canary testing.

## Gift inventory / runtime integrations
- Catalog / approvals are **empty by default** and require precise Woo Product IDs and SKUs, reviewed destination-country and warehouse mapping, actual available units, weight, landed cost, provider-reconciled evidence and a recent checked-at timestamp supplied through trusted server-side `byl_bf2026_gift_catalog` and `byl_bf2026_gift_approvals` filters.
- Owner reports that warehouse teams have already confirmed two gifts per warehouse. **Do not request repeat signoff**, but the selected exact SKU-to-warehouse-to-country technical mapping still must be entered before the Canary can issue live gifts. Synthetic Dev test-only mappings must not be installed on WWW.
- Gift slot assignment uses native Woo cart and stable session randomness; native total recalculation, premium replace, country change, zero-price Gift rows and order line SKU/warehouse metadata were verified in Dev CLI with temporary fixture data.
- Verified influencer Woo coupon IDs can be injected via `byl_bf2026_verified_influencer_coupon_ids`; only those coupons exclude active BF sale SKUs. Arbitrary coupons and ordinary priced merchandise remain subject to their existing Woo rules.
- FX entitlement relies exclusively on an audited USD source. Unknown or unverified foreign-currency context is rejected.

## Evidence and limits
- R14 session/permission fixture **19/19** and combined isolated Black Friday fixtures **230/230 PASS**; GitHub CI expected at exact PR HEAD.
- Dev WP plugin tested through `0.2.1-dev-rc2` installation with Master OFF; isolated CLI WordPress auth/cookie fixture 5/5, anonymous Store API cart smoke passed.
- Browser Store API signed-in Canary persistence, real Woo coupon IDs, finalized gift SKU fulfillment manifest, saved Dev order/ERP data, US-only region price display/cache and WWW manual installation/canary are **NOT yet verified or released**.
- The plugin is not a replacement for WooCommerce payment gateways, AffiliateWP, ERP or warehouse stock APIs.

Rollback: stop Canary from WooCommerce admin, retain price snapshots, and restore the previous plugin release if needed. Never assume removal of a plugin reverses changes made by unrelated pricing tools.

Documentation: `docs/andy-campaign-v1/BF2026-R14-ACCOUNT-SESSION-CANARY-20261110.md`.
