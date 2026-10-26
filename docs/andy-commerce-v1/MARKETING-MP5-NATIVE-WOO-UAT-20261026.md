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

## M-P5 actual Checkout quota hooks and negative HTTP proof (follow-up)

At tested #228 source HEAD `8b7a51d0d8a80fa7477bf8bc316932e008164436`, the additional `class-andy-commerce-marketing-p5-checkout-hooks.php` hooks are only booted by the temporary isolated test bridge (not by shared Commerce bootstrap). The real HTTP order pathway was exercised using Store API #81 and Classic #82. The database's transactional cap was never overrun: 2 accepted/committed, third rejected, no held reservations left. The first missing-quota Store API error initially returned generic HTTP500 and was fixed to explicit HTTP409 `aicc_marketing_cap_exhausted`; Classic presents an actionable checkout notice. Audit script `marketing-p5-http-quota-order-audit.php` confirmed 23/23 assertions: order tokens distinct, coupon and USD tax exact, per-unit BOGO metadata, safe cancellation without quota reuse. Test configurations/bridge/flags were removed and original plugin state restored.

The source-only cross-branch preview combined current #213 SHA `7ac0499a721eec0a150857775f9f4dbf9b7223b3` with #228's five PHP classes in a **temporary uncommitted scratch worktree**. All PHP lint and pure harness passed, and `build-andy-commerce-dev-candidate.sh` / ZIP Contract passed while excluding tests. **This does not meet the canonical exact remote SHA release gate, is not a Dev install, and shared bootstrap was deliberately unmodified.**

## Unfinished required gates (not PASS)
1. **M-P4 automatic checkout quota wiring — DISPOSABLE LOCAL TEST PASS AFTER INITIAL REPORT**: Marketing-only `class-andy-commerce-marketing-p5-checkout-hooks.php` was added as explicit, guarded source on #228. Real Store API COD order **#81** and Classic COD order **#82** reserved and committed distinct tokens against `campaign_p5_bogo_01` max2, yielding `max=2 used=2 held=0`; native order-level M15/M16 audit metadata and BOGO unit discount allocations were read back (23/23 checks PASS), both later cancelled **without returning used quota**. Third Store API checkout correctly rejected with actionable `HTTP 409 aicc_marketing_cap_exhausted` rather than HTTP500; third Classic checkout returned explicit limit-reached notice, no successful order. No external provider settlement. **This is local sandbox only; #213 bootstrap/Dev runtime remains inactive.** Request retries after a crash, checkout-draft lifetime, safe held-claim reclaim, per-customer limits and automated gift quotas are NOT fully verified for Dev or production.
2. **Real Dev activation policy**: M-P1/2/3/4 are deliberately locked to the named disposable local origin, local DB and wp-config flag. They cannot run on `dev.beyourlover.com` without a new, separately guarded and approved Dev-only activation contract and #213 shared bootstrap. Never bypass local guard by loosening its production boundaries.
3. **#213 sole publisher**: exact-head reviewed source integration, Currency + Marketing CI complete green, installable release script ZIP contract, one rollback artifact, Woo admin Desktop-only UAT and customer-facing mobile checkout UAT. No independent #228 Dev install, main/Stable/WWW change.
4. Broader cases (mixed tax jurisdictions/shipping multi-package, stock concurrency, automatic refund-restores-used-quota, per-user rate limits) require separate explicit scope and are not evidenced by this test.

**M-P5 decision (latest)**: local Checkout/Store API **six-type promotions, SKU/tax/offline refund, statuses AND two-quota real HTTP checkout hook flows PASS within the named disposable sandbox**. Local checkout cap-exhaustion UX after a 500-to-409 correction also PASS. The **#213 Dev final gate remains HOLD** pending exact-head full CI, Dev-only guarded approval/activation (current P1 explicitly blocks Dev), integration-owner bootstrap, live Dev preview and rollback/Owner UAT. Never treat this as WWW/Stable or provider payment acceptance.
