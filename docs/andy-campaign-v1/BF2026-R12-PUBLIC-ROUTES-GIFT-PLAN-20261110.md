# BF2026 R12 — Public activity routes, idempotent gift entitlement planner
Date: 2026-11-10. Status: **source Draft / Gift transaction OFF / WWW pages PUBLIC**.

## Owner current authority
- Warehouse teams have already confirmed their two designated free gifts each. **Do not send them back through a redundant approval cycle.** Integration still needs the exact selected Woo product IDs / SKU names, warehouse IDs and shipping-country mappings in the code manifest, because the database cannot fulfill by an ambiguous natural-language assertion.
- Three frozen canonical WWW customer pages: `/holiday/black-friday/` #33288, `/deals/clearance/` #42276 (Deals parent #42273), `/holiday/gift/` #42280. Owner authorized all to become public. Independent WP REST readback: #33288 had ALREADY been published by a parallel workflow; #42273/#42276/#42280 explicitly transitioned from `private` to `publish` with modified-time/parent checks. No changes to BF landing content, product prices, coupons, orders, payment, inventory or campaign activation. Anonymous US Mac mini HTTP status **200/200/200/200**, pages titled Black Friday, Deals, Clearance, Gift Collection.
- Woo Clearance product term ID 2725 still contains 16 genuinely discounted items. Gift categories: 2726 parent, 2727 free, 2728 upgrade, all zero assigned as of previous readback. Their public page status does NOT itself grant gift entitlement.

## R12 changes in code
- Existing `BYL_BF2026_R10_Catalog` now accepts exactly `private` or `publish` for pre-existing canonical customer pages, rejecting Draft/Trash and preventing automatic page creation. Exact path is `deals/clearance` or `holiday/gift`, never old R10 suggested routes.
- New **source-only** `BYL_BF2026_R12_Auto_Gift_Planner` adds a strict read-only plan using native post-coupon USD `WC_Cart` basis via R9-C:
  - One free gift on positive qualifying merchandise; two when >=USD199; gift fee excluded from threshold
  - One paid US upgrade at >=USD99 occupies one of the same slots; non-US or insufficient spend revokes paid slot and reselects free
  - Repeated cart recomputation retains valid gift identities and deterministic HMAC-based selection; no duplicate gifts
  - Destination-country, exact Woo SKU, available warehouse units and mapped approved manifest required; on absence returns NO-GO, never invents a gift
  - Does NOT mutate WC cart, orders, pricing, coupons, inventory or ERP. Can only prepare remove/add plans; a future standalone nonce/session-validated transaction writer must perform the mutation atomically.
- Root plugin imports the read-only R9 pure policy, R9-C adapter and R12 planner; no new Woo gift write/auto-issue hooks registered. Existing Campaign runtime remains owner-controlled/OFF; live Woo cart behavior unchanged.

## Validations
- Isolated PHP R12 public catalog: 33/33; R10 original-price restoration: 40/40; R10 US checkout gate: 19/19; R12 random gift planner: 21/21 => **113/113** isolated regression assertions PASS.
- These are synthetic PHP/Woo-shaped fixtures. They are NOT real Gift checkout PASS.
- Prior Dev anonymous Store API test at $69, $138 and $217 still had **zero** Black Friday gift lines; all test products removed, no paid checkout.

## Unclosed production gate
1. Configure already-approved warehouse gift pairs as exact verified product IDs / SKU / warehouse / country matrix in native plugin; no re-approval.
2. Implement real nonce/session-bound Gift add/replace/auto-random and Woo price recalculation, preventing cart races and double-charge. Preserve Woo source USD and native coupons.
3. Resolve R10 **US-only** pricing strategy: current inactive global Woo sale meta approach would advertise/charge USA-only prices internationally. Respect caching, existing actual discounts and global cart.
4. Live **Dev** native Store API, Classic / Blocks checkout (no real charge), US / non-US, $99/$199, add/remove/retry, FX, influencer coupons, refunds/ERP line UAT. No production real payment.
5. Signed plugin release, compare exact GitHub SHA/CI, Owner's manual WWW installation rule, restricted canary and readback. Do not claim gifts active until verified.

## Immediate code status
Draft PR #303 contains old route assumptions; #306 corrects Private canonical routes; this R12 branch extends #306 to support public routes and read-only auto-gift plan. No GitHub main merge or WWW plugin deployment has been executed in R12. Any new production code must avoid changing live BF landing page.
