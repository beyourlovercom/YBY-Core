# M4 BYL49 Versioned Migration · Dev-only Execution Gate · 2026-10-12

**STATE: HOLD. No Owner authorization to change live Dev BYL49 eligibility yet.** This is a preflight and rollback contract, not a migration or an activation.

## Canonical context / exact sources

- Existing Marketing M3 Operator List V2 and M4/M5 synthetic lab Visual **Owner PASS** (scope limited to shadow UI). GitHub Draft PR #87 (M3 PR #80-derived), Woo Store API source-only M5, standalone Cart V2 M5 composite [Theme Draft PR #80](https://github.com/beyourlovercom/beyourlover-web-v3/pull/80). Do not merge composite Draft PRs as shortcuts for M3 #80, M4 #81, M5 #82 or Cart #63/T4 #79.
- Hostinger Dev `dev.beyourlover.com`: staging, Andy Commerce 1.0.1. Current persisted `andy_commerce_shipping_promotion_settings_v1` exists; `mode=automatic`, `eligibility_basis=pre_discount_merchandise_subtotal`, global threshold `49`. Existing policy PHP SHA256 `a7bca41e60105e7d6aac01c0da41f9cd0f7a2d8b93a0017795090b48c6951e21`.
- **Do not flatten zones.** Dev persisted v1 zone overrides: zone 0 global 49, zone 1 override 0, zone 3 override 169, zone 4 override 129, zone 5 override 69. Woo native free shipping methods are configured at zone 1 min 0, zone 5 min 69, zone 4 min 129, zone 3 min 169. Shipping eligibility must use the customer-matched zone/rate, NOT an unconditional global `$49` headline.
- Dev `BYL_Shipping_Promotion_Runtime` class **absent**; `woocommerce_shipping_free_shipping_is_available` registered exactly once in this read-only snapshot: `Andy_Commerce_Shipping_Promotion_Runtime::filter_free_shipping` priority 10. Recheck just before authorized Canary; configuration can change.
- Existing `get_displayed_subtotal()` used for eligibility = **pre-discount**. Woo `line_total` post-discount, excluding shipping/tax; automatic gifts must not self-qualify. Woo shipping address/zone selection and chosen rate remain the only live shipping authority. Currency decimal units must match Woo.

## Future Dev-only migration protocol (NOT AUTHORIZED YET)

1. Verify explicit separate Owner statement authorizing **Dev-only BYL49 v2 shipping policy**. Existing M4/M5 *visual* PASS does not authorize this.
2. Confirm isolated Dev vs WWW database/servers, existing code/plugin exact SHA, Woo zones, legacy single authority, no active promotions; create and verify narrow rollback snapshot for code and shipping config v1. Preserve v1 bytes/value as a rollback pointer.
3. Introduce **separate v2 configuration** default `active=false`. Do not overwrite v1 or silently reinterpret `schema_version=1`. At first only compute in shadow, compare both bases for each zone. Exclude all auto-gift and nonqualifying merchandise by trusted server product/cart line contract. If classification/zone/currency/selected Woo method ambiguous, fail closed (no new free award).
4. Verify below/equal/above threshold and coupon after-discount arithmetic in every zone ($0/$49/$69/$129/$169); cart/retry/one-coupon behavior; Store API/Checkout GET; multi-package and missing address; no real orders or payment.
5. Only if approved and gates PASS, run bounded Dev-only v2 single-authority switch: prevent old filter + new filter racing; neither promotions nor gifts enabled. Any inconsistency => rollback code + v2 switch; confirm v1 policy checksum and no order/cart write side effects.
6. Dev M390 + PC1440 browser screenshot tests, Cart V2 visually check new progress (only a confirmed Woo rate), Checkout GET, owner Final UAT. Separate PR SHA/CI review, Dev activation and WWW production go/no-go later; never infer Owner approval to merge PR or deploy WWW.

## Acceptance fixtures

| Matching zone | Persisted threshold | Example post-discount subtotal | Result after Owner-approved v2 | Note |
| --- | ---: | ---: | --- | --- |
| Zone 0 fallback | 49 | 60 before, 15 coupon = 45 | Below by 4 | Shipping rate still must exist and be Woo-confirmed |
| Zone 1 | 0 | 0 | Zone free shipping settings already min 0 | Preserve availability semantics; no invented gift |
| Zone 5 | 69 | 80 before, 15 coupon = 65 | Below by 4 | Preserve 69 threshold |
| Zone 4 | 129 | 140 before, 15 coupon = 125 | Below by 4 | Preserve 129 threshold |
| Zone 3 | 169 | 180 before, 15 coupon = 165 | Below by 4 | Preserve 169 threshold |

## Current release closure

- **PASS:** M3 UI, M4/M5 synthetic Dev lab Owner Visual, offline PHP 35/35; M5 Woo v2 source-only 23/23; M5 Store API read-only bridge 13/13; Theme Cart V2 M5 display source-only 13/13 + old Cart mock regression; related GitHub CI.
- **HOLD:** actual policy basis change, v2 runtime activation, read-only Store API bridge Dev deployment (prior safety block), Cart V2 new progress Dev deployment, M7 Owner Final UAT, M8 merge/WWW/marketing activation.
- No true order, payment, coupon, Woo shipping policy mutation, or WWW deployment has occurred in this development continuation.
