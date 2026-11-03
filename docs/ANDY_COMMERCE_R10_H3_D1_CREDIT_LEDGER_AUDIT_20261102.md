# Andy Commerce R10-H3-D1 — Real vendor credit-ledger lifecycle source audit

Date: 2026-11-02. **READ-ONLY SOURCE INVENTORY; NOT a Store Credit redemption or refund UAT.**
Baseline: Draft PR #213 exact `eae451da605555aec59d73491cc1e73476348193`.
Only licensed, owned Windows disposable plugin files were inspected. Never copy the paid PHP or license into GitHub.

## Why H3-C3 is not a credit-ledger transaction gate

H3-C3 restored the native Woo cart's ordered coupon list, totals, notices and selected
vendor session markers after test-injected post-manual errors. It did NOT restore persisted
credit coupon amounts, credit histories, order coupon items, store credit attribution,
customer-visible gift inventory, payment captures, refunds or cross-request crash state.

The exact paid plugin sources show independent persistent side effects beyond this cart scope:

- **WebToffee Smart Coupons Pro 3.2.0**:
  `Wt_Smart_Coupon_Store_Credit_Apply` attaches `update_credit_amount` to
  `woocommerce_new_order` (Classic) and
  `woocommerce_store_api_checkout_order_processed` (Blocks/Store API).
  It checks existing per-order credit usage before persisting a changed `WC_Coupon`
  amount, saves coupon data, adds credit history and writes order usage.
  Its own code documents preventing duplicate early deduction on Block checkout;
  this does not prove crash-safe distributed rollback or universal idempotence.
- **StoreApps Smart Coupons 9.51.0**:
  `WC_SC_Coupon_Process` handles credit updates on order-status transitions
  including pending → processing/completed; it has restoration paths for
  refundable/failed/cancelled transitions. H3-C3's cart/session restoration
  does not exercise these order-level side effects.

## Repeatable exact-vendor static inventory

From the Windows-owned worktree, run with Python 3:

```powershell
python scripts/r10h3d1-paid-credit-hooks-readonly.py --plugins D:\ai\r10b-vendor-uat\disposable\www\wp-content\plugins
```

Observed `H3D1_STATIC_HOOK_INVENTORY=10/10`: WebToffee Classic and Blocks
order hooks, coupon amount save, per-order dedup read and credit history;
StoreApps processing/completed transition hooks, refund path and balance handler.
This script **does not load WordPress, apply a coupon, create an order,
redeem a credit, trigger a payment, or guarantee executable behavior**.
A missing source marker fails the inventory rather than silently passing.
It contains no paid source code or credentials.

## H3-D1 remaining mandatory UAT

1. Use disposable **owned** Woo orders and vendor-generated Store Credit fixtures only
   after separately checking isolated order/checkout boundaries; no customer orders.
2. Independently test Classic and Blocks Store API creation, checkout retry,
   repeated callback, partial and full redemption, and credit balance readback.
3. Include cancellation, failed checkout, partial/full refunds, mixed-tax
   and all five YayCurrency fixture currencies, with actual vendor currency semantics.
4. Reconcile the **persisted** coupon amount, per-order usage metadata,
   credit history, refund credits, order items and provider business ledger.
   Failed recovery or unknown metadata must deny runtime activation.
5. Document an idempotent compensation and outbox/reconciliation mechanism
   for process crash, concurrent requests and external side effects.
   A single in-memory cart restore is insufficient.

## Release authority / result

H3-D1 static lifecycle audit **10/10 PASS**, but **REAL CREDIT TRANSACTION
AND RECOVERY ARE UNVERIFIED / BLOCKED**. No plugin bootstrap import, hook,
release ZIP, database mutation, local WordPress start, Dev install,
www change or main merge was made by this audit.
Do not change the approved Dev `1.1.0-rc2 / OBSERVE / Shipping OFF / Coupon OFF`
or www `1.0.14 OBSERVE`. Keep Issue #276 OPEN. Parent Draft PR #213
is the sole future Dev publisher; this is only a child source/evidence producer.
