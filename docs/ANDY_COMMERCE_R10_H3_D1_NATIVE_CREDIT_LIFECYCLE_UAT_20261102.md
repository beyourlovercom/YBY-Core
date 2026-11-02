# Andy Commerce R10-H3-D1 — Native credit lifecycle UAT

Date: 2026-11-02. Environment: Windows OWNED disposable WooCommerce 11.2.1,
YayCurrency Pro 3.3.5, WebToffee Smart Coupons Pro 3.2.0,
StoreApps Smart Coupons 9.51.0. WordPress URL strictly http://127.0.0.1:14141.
GitHub parent Draft PR #213 base: eae451da605555aec59d73491cc1e73476348193.
**NO Dev / WWW / main merge / runtime integration / external payment.**

## Executed native lifecycle

Script: `packages/andy-commerce/tests/r10h3d1-credit-lifecycle-disposable.php`.
It loads the REAL local licensed plugins, creates an ephemeral virtual USD75
product, per-case real WC_Coupon of initial USD100, native WC Cart and
WC_Checkout::create_order on the owned disposable WordPress, transitions
its WC_Order to processing and then failed OR refunded. It invokes the
equivalent current provider callback once more for an idempotence check,
reads persisted WC_Coupon amount from database, records real order coupon
discount and StoreApps contribution. No payment, remote request or email
(send suppressed by pre_wp_mail). Owned orders and fixtures are deleted.

Each scenario runs in a distinct PHP process to avoid provider in-memory
cache contamination across fake customer carts.

| Vendor, native WC status | Cart used | Order discount | Coupon amount after processing | Duplicate callback | Credit amount after failure/refund | Assertions |
| --- | ---: | ---: | ---: | ---: | ---: | --- |
| WebToffee, failed | USD75 | USD75 | USD25 | USD25 | USD100 | 5/5 PASS |
| WebToffee, refunded | USD75 | USD75 | USD25 | USD25 | USD100 | 5/5 PASS |
| StoreApps, failed | USD75 | USD75 | USD0 | USD0 | USD75 | 3/5 FAIL |
| StoreApps, refunded | USD75 | USD75 | USD0 | USD0 | USD75 | 3/5 FAIL |

For StoreApps, the stored `smart_coupons_contribution` on each owned
order was `{coupon_code: "100"}` although the native order coupon item
reported a discount of USD75. Processing used the wrong amount and
the failed/refunded status restored only the order line amount. This
mismatch was reproduced twice, once for each terminal order status.
Do NOT label it a production-wide vendor defect until fully front-end
Classic and Blocks/Store API HTTP checkout confirms the same behavior.
A direct CLI `WC_Checkout::create_order` bypasses portions of the
outer HTTP request lifecycle.

## Contract and hard blockers

- WebToffee status-based failure/refund replay passes in isolated CLI;
  real `WC_Refund` objects, payment gateway refunds, cross-process
  crash, concurrent customer cart requests and after-order ledger
  reconciliation have NOT been tested and cannot be declared PASS.
- StoreApps has a repeatable FAIL on persistent balance / restore.
  Must verify front-end HTTP Woo checkout and either fix the provider
  interaction at source-compatible adapter level or fail closed.
- Never use H3-C3 in-memory coupon/session rollback as compensation
  for persistent coupon balance, refund, order attribution or history.
- No Dev install, main merge, Shipping/Coupon activation, or OBSERVE exit.
  Issue #276 remains OPEN. H3-D2 isolated gift/cart testing may proceed
  independently; release of combined transaction policy remains BLOCKED.
