# M16 · Woo durable order ↔ M15 quota reservation / idempotent recovery

Date 2026-10-25. Based on single-source Commerce Draft PR #213 at M15 exact HEAD 456a5000d6d6acb8f60a9bd4610e00f851687f3e. Source and isolated sandbox evidence only; no main/Stable/Dev installed plugin/WWW writes.

## M16 contract
- M15 cap ledger has explicit, inert-at-load reserve / commit / abort-release APIs, keyed by a unique claim token and campaign. Concurrent InnoDB+MySQL GET_LOCK cap checks from M15 remain the source of truth.
- M16 adds explicit helper Andy_Commerce_M16_Order_Cap_Reconcile::confirm(...). It verifies a **durable native Woo shop_order** (not a refund), USD ledger, acceptable pending/on-hold/processing/completed state, exact campaign ID and claim token embedded in Woo order metadata **before** committing the held slot.
- Two-phase protocol for future guarded checkout integration: (1) reserve; (2) persist Woo HPOS or classic order with campaign/token audit metadata; (3) commit; (4) if a process fails after step (2) and before step (3), safely retry confirm() from the stored order. Wrong campaign/token fails closed, replay of same confirmed order is idempotent. Never guess an order ID or release an already-persisted sale. Aborted checkout may release a slot only when it is known that **no Woo order exists**.
- No automatic migration, background job, order hook, gateway action, refund, or existing Marketing draft activation is registered by this class. It intentionally does not claim external settlement verification.

## Actual isolated HPOS tests
US Mac mini, local-only URL 127.0.0.1:18973, independent DB aicc_commerce_p5_20261023, WordPress 7.1.3, Woo 11.2.1, Andy Core 2.2.11, Andy Commerce 1.0.8, native USD, HPOS ON, only offline COD; outbound mail/HTTP intercepted. WordPress M14 sandbox MU auto runtime OFF.

1. Explicit guarded driver m16-local-woo-order-cap-uat.php created real local Woo orders **#60 and #61**, each persisted with campaign/token metadata and only then committed one quota slot. Immutable cap=2: after two real orders, used=2 held=0; third reservation rejected as exhausted. One synthetic failure before any order was created successfully released its held slot.
2. Another test driver m16-post-save-recovery-uat.php reserved a separate cap=1, created genuine HPOS order **#62**, persisted audit metadata and **deliberately did not immediately commit**. Verified counters held=1 used=0; a forged/incorrect token was rejected with balances intact; first recovery confirmed order #62 and yielded held=0 used=1; repeat confirm() was idempotent, a second-sale reservation denied.
3. All are local-only synthetic COD orders, never third-party payments. No Dev/WWW Woo orders or live customer data were read or written.

## Release gates STILL OPEN
- **Not yet registered in native Woo Store API / Classic checkout hooks:** tests execute coordinator calls from isolated WP-CLI, not live production route orchestration.
- Reservation TTL/reconciliation scheduler, per-customer caps, data retention, order failure/cancellation policy, changes to campaign revisions/limits, retryable DB outages, and consistency of order metadata under stock/transaction races require explicit implementation and UAT before live use.
- M15 native SKU refund allocation still requires real return/partial-refund and mixed-rate tax testing.
- P6 Stripe/WooPayments/PayPal test credentials and isolated callbacks, 3DS, actual FX capture/settlement and provider refunds are still absent. No external payment PASS may be inferred from Woo COD processing statuses.

Only the two isolated test drivers under packages/andy-commerce/tests/sandbox/ have order-write code, are guarded with explicit local schema/URL/env opt-in, and are **excluded from installable Commerce ZIP**. The helper class is included as inert source and is not loaded by runtime without explicit call.

Related tracking: GitHub Issue #216. #208 Subscriber HTTP403 PASS, fixture author 88-post cleanup HOLD.
