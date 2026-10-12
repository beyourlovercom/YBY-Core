# Andy Commerce Marketing OS — M2 Pure Promotion Evaluator
Date: 2026-10-12
Stage: **M2 offline calculation / CI**, NO runtime deployment, NOT a Woo discount implementation.

## 0. Canonical repository, pending approvals
- GitHub main of `beyourlovercom/YBY-Core` is the only merge target.
- The feature branch `feat/andy-commerce-marketing-m2-evaluator-20261012` is split from main specifically so M1 [Draft PR #77](https://github.com/beyourlovercom/YBY-Core/pull/77) may remain at Owner admin-design UAT without changing its HEAD.
- [PR #67](https://github.com/beyourlovercom/YBY-Core/pull/67) Andy Commerce v1.0.1 consolidation is still independent and unmerged. Before any integration/merge, reconcile #67 and #77. BYL Platform is retired; do not reintroduce it.
- Owner has **approved in conversation** that BOGO/bundles/spend-save operations belong to Marketing UI and only **Andy Commerce** calculates promotions. Woo owns cart totals, coupon, shipping/tax/order. Cart Drawer/Checkout display read-only status. Andy Core is generic infrastructure.
- **Default merchandise qualification base**: Woo canonical *post-discount line_total* (in integer currency minor units), excluding all line tax, shipping, shipping tax and fees. Exclude free reward/self-qualification lines. Do not silently change the currently active older BYL49 pre-discount shipping policy.

## 1. Intent and hard isolation
This file is **only** `packages/andy-commerce/includes/class-andy-commerce-marketing-pure-evaluator.php`.
- It is **NOT required by** `andy-commerce.php` or `includes/class-andy-commerce.php`.
- `evaluate(..., ['sandbox'=>true])` must be explicit, otherwise it throws.
- No WordPress hooks, Woo extensions, DB reads/writes, plugin flags, admin publishing, cookies, remote calls, orders, payment changes, price writes or gift insertion.
- The winning promotion is only a proposed *discount_minor*, never an applied coupon/order fee/line adjustment. It must be wired to Woo with a separately authorized policy, loop-safe discount allocator and Checkout recomputation.
- Gift and free-shipping **presentation remains OFF**, especially during BYL49 policy migration and without real Woo chosen free-shipping rate.

## 2. Data contract
Synthetic normalized cart:
```php
[
  'currency' => 'USD',
  'applied_coupon_count' => 0, // already represented in line totals
  'lines' => [
    ['key'=>'item-1','product_id'=>123,'quantity'=>2,
     'line_total_minor'=>1499,'eligible'=>true,'is_reward'=>false],
  ],
]
```
- `line_total_minor`: complete line total after manual Woo coupon discounts, excluding line tax and shipping. Already integer minor units: USD $14.99 = 1499; never subtract coupon a second time.
- Blank/duplicate cart keys, negative/float totals, negative quantities, excessive money, missing currency or overlarge quantity cause a **fail-closed exception**; invalid offer selectors are never allowed to invent a discount.
- Empty cart returns no discount. `eligible=false` and `is_reward=true` lines excluded from merchandise basis.
- No coupon codes, customer PII, addresses or payment methods are accepted in calculator inputs or output.

Offer contract (all values synthetic in M2):
```php
['id'=>'buy-two-get-one','status'=>'draft','currency'=>'USD',
 'priority'=>20,'type'=>'bogo','buy_product_ids'=>[10],
 'get_product_ids'=>[11],'buy_qty'=>2,'get_qty'=>1,
 'reward_bps'=>10000, 'stack_with_coupon'=>false]
```
Other templates:
- `type=bundle`, `requires=[['product_id'=>10,'quantity'=>1],...]`, `bundle_price_minor` OR `discount_bps`.
- `type=spend_save`, `threshold_minor`, `discount_minor` OR `discount_bps`, optional `product_ids`.
- `type=tiered`, `tiers=[['min_qty'=>2,'discount_bps'=>1000],...]`, optional `product_ids`.
- `priority` descending, then higher discount, then lexicographically smaller stable `id`, with **exactly one** winner; by default coupons and automatic offers do not stack. Explicit `stack_with_coupon=true` is only an M2 simulation flag pending policy approval.
- `country`, `start_unix`/`end_unix` are fail-closed when unknown; caller injects synthetic clock `now_unix`, no actual time/network dependency.
- BOGO shared buy/reward SKU pools reserve buy+reward units, preventing a free self-award on the same unit; mixed/partially overlapping SKU pools fail closed.
- Cheapest discounted reward units win; post-coupon rounding to integer minor units and discount <= merchandise total. Zero-value items cannot inflate BOGO/tier quantity qualification.
- Future scope: complex mixed-pool allocation, variation SKU vs parent category semantics, line-item discount distribution for Woo refunds, shipping zones, currency/FX, stock validation, advanced stacking matrices, true gift inventory/selection, campaign persistence and activation. **Not implemented in M2.**

## 3. Regression matrix
Offline `packages/andy-commerce/tests/marketing-m2-pure-evaluator-harness.php` covers:
- $120 minus $20 coupon => $100 eligible; minus $30 => $90, below a $100 threshold.
- Exclude tax, shipping, gifts, ineligible merchandise; post-auto amount is the later shipping eligibility candidate **not an actual shipping unlock**.
- Manual coupon limit one; automatic promo vs coupon no-stack by default; explicit sandbox opt-in needed.
- BOGO buy1/get1 same SKU ratio 1/2 etc.; buy2 get1 disjoint reward pools; half-price, cheapest unit, fractional cents; mixed pools blocked.
- Bundles fixed total or percentage, incomplete, multiple bundle sets, nonbeneficial pricing fails closed.
- Spend & Save, quantity tiers, exclusion SKU, free item inflation.
- Priority, max allowed discount, campaign currency/zone/time, duplicate IDs, invalid inputs, and runtime bootstrap isolation.
- Offline harness is included automatically in `.github/workflows/core-regression.yml` glob `packages/andy-commerce/tests/*-harness.php`; no new CI workflow or custom plugin required.

## 4. Forward gates
- **M2**: GitHub CI + code review, pure evaluator only. Existing shipping untouched.
- **M3**: Campaign schema and versioned draft CRUD after Owner M1 design approval; any Dev persistence/DB migration requires separate explicit authorization.
- **M4**: Legacy BYL49 pre-discount policy vs new V2 post-discount/ex-tax policy shadow evaluation; do not change active $49/$69 settings or handoff marker without approval.
- **M5**: Cart/Checkout read-only Store API progress extension after rules become authoritative; do not show reward unlocked before Woo eligibility.
- **M6–M8**: bounded Dev Canary, Owner UAT, independent GitHub merge and WWW production gate. All campaigns OFF until explicitly published.
