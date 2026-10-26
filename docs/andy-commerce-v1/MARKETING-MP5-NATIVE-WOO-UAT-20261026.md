# Marketing OS M-P5 — native Store API / Classic checkout, SKU, tax, refund and handoff
Date: 2026-10-26

## Scope and exact evidence

Marketing-only Draft PR #228 source was tested at exact HEAD `ff9029a744b03a876fe93251783d02f9bfb4a6dd` in disposable WooCommerce 11.2.1, WordPress 7.1.3 / local HPOS DB `aicc_commerce_p5_20261023`, origin `http://127.0.0.1:18973`. Only synthetic Woo products 27/28/29/30 and `example.invalid` customer identities. Woo COD/offline payment, no external Stripe, provider refund, email or live order. The temporary test-only MU bridge was removed after both HTTP test runs; the original Commerce plugin state was restored, test wp-config flag removed and test-case option deleted.

**Native end-to-end HTTP**: 12/12 successful POST checkout flows, with actual customer sessions/cart cookies and Woo checkout endpoint; six promotional templates each tested once via **Store API/Blocks** and once via **Classic**. Local order IDs:
| Promotion | Store API order | Classic order | Discount USD | Order total USD | Native tax USD |
|---|---:|---:|---:|---:|---:|
| Fixed spend-save | #73 | #77 | 12.00 | 197.64 | 14.64 |
| Percentage spend-save | #74 | #78 | 18.50 | 190.62 | 14.12 |
| Free gift | #75 | #79 | 0.00 + 1 zero-price managed-stock gift | 210.60 | 15.60 |
| BOGO | #68 | #70 | 22.50 | 186.30 | 13.80 |
| Bundle | #69 | #71 | 23.00 | 185.76 | 13.76 |
| Tiered | #76 | #80 | 37.00 | 170.64 | 12.64 |

All tests used **USD10.00 native shipping**. Post-checkout readback verified all twelve as real Woo HPOS orders and every SKU discount/shipping/tax/total against native order items, not only preview calculations.

**M-P3 native SKU invariant (BOGO + Bundle)**:
- BOGO: #27 buyer USD0 discount, #28 reward USD22.50, unrelated #29 USD0. Native tax on #28 is USD1.80; merchandise SKU lines reconcile with Woo coupon total.
- Bundle: SKU #27 USD13.14 and #29 USD9.86, unrelated #28 USD0. Native tax on #27 USD5.35 and #29 USD4.01 (Woo minor rounding), total native tax USD13.76.
- Four orders #68–#71: `marketing-p5-order-tax-refund.php` 65/65 PASS with native order audit, one offline partial refund and cancellation; eight additional orders #73–#80: `marketing-p5-six-native-order-audit.php` 134/134 PASS and cancelled with no external payment.
- One real Woo native `WC_Order_Refund` (#72) on order #68: refunding **reward SKU #28** merchandise USD5.00 + proportional tax USD0.40 = **USD5.40**, with `refund_payment=false`, `restock_items=false`, no gateway charge. Parent retained total USD186.30, `total_refunded=USD5.40`, unrefunded USD180.90. No M15 marketing quota restored.
- All twelve synthetic test orders were then put in Woo `cancelled` state, no fulfillment. Owner Commerce/Dev/WWW databases were not modified.
- The prior isolated M-P4 test used synthetic order #67 and validated capped reservations and refund/cancel classification separately (26/26 PASS); **its quota coordinator is explicit-API only**, not wired into these HTTP checkout requests.

## Isolation/safety readback
After test teardown: `AICC_MARKETING_RUNTIME_SANDBOX` undefined; temporary P5 MU bridge absent; `aicc_marketing_p5_local_case` absent; M14 activity OFF, P1 activity OFF, Woo native currency still USD. Existing Andy Commerce plugin returned to its prior installed state. No Stripe/OAuth/live gateway tests, payment funds, production discounts or customer emails. **Stripe-specific provider UAT is `WAIVED — Owner Approved`, not technically PASS.**

## Integrator handoff evidence
- Dedicated Marketing PR #228: `ff9029a744b03a876fe93251783d02f9bfb4a6dd` was the tested **code + test** HEAD; this documentation is a later-only commit.
- At integration dry-run, sole #213 integrated Currency head was `7ac0499a721eec0a150857775f9f4dbf9b7223b3`. Its latest version included Currency R8-R10 test source. In an isolated **temporary scratch tree only**, Marketing source and tests were placed over exact #213; all four new PHP classes and P5 PHP tests passed syntax; 22/22 pure negative harness passed. The normal Commerce preview ZIP script and ZIP contract check PASSED, with no test files packed. **This was NOT an authorized canonical Dev ZIP**, not a remote merge, not an installed plugin. Bootstrap remained unchanged.
- GitHub Actions for #228 source HEAD: Commerce ZIP Contract, Core Regression, Newsletter and Inquiry Inbox successful; Currency Preview skipped. **Release Package Validation failed** stale merge-source provenance; **P5 Disposable Woo workflow failed** at Currency R8–R10 expected-file step because #228 tracks an older #213 base that does not yet contain the Currency test. Never fake all-green CI, disable release gates or overwrite sibling Currency work.

## Unfinished required gates (not PASS)
1. **M-P4 automatic checkout quota wiring**: real Woo Classic and Store API reserve-before-order/confirm-after-persist/error/timeout hooks are NOT registered. Current P4 explicit API UAT alone is not true HTTP quota closure. Integrator must validate request retries, checkout-draft lifetime, release only proven no-order holds, idempotence and partial refund policy.
2. **Real Dev activation policy**: M-P1/2/3/4 are deliberately locked to the named disposable local origin, local DB and wp-config flag. They cannot run on `dev.beyourlover.com` without a new, separately guarded and approved Dev-only activation contract and #213 shared bootstrap. Never bypass local guard by loosening its production boundaries.
3. **#213 sole publisher**: exact-head reviewed source integration, Currency + Marketing CI complete green, installable release script ZIP contract, one rollback artifact, Woo admin Desktop-only UAT and customer-facing mobile checkout UAT. No independent #228 Dev install, main/Stable/WWW change.
4. Broader cases (mixed tax jurisdictions/shipping multi-package, stock concurrency, automatic refund-restores-used-quota, per-user rate limits) require separate explicit scope and are not evidenced by this test.

**M-P5 decision**: local Checkout/Store API six-type promotions + SKU/tax/refund/status smoke **PASS within scope**; #213 Dev final gate **HOLD** pending the true checkout quota hooks, exact-head full CI and Dev-only guarded integration.
