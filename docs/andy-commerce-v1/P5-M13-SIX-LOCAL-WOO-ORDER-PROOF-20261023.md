# M13: six local Woo orders from M12 projections — **test-only**, never plugin runtime

**Project source**: unified Andy Commerce integration PR #213 `67dca943343d196be364d7d28e3e624f92ef8928`. This evidence branch is stacked on that branch; do not merge GitHub main or ship an independent Dev ZIP.

## Contract
`packages/andy-commerce/tests/sandbox/p5-m13-local-execution-proof.php` is a WP-CLI **disposable, independent localhost test driver**, not automatically loaded by Andy Commerce. Requires all guards simultaneously:
- Environment variable `AICC_ALLOW_SANDBOX_WOO_WRITES=1`;
- Site home **exactly** `http://127.0.0.1:18973`;
- MySQL schema **exactly** `aicc_commerce_p5_20261023`;
- WP environment local, native shop base USD, WordPress cron disabled, external HTTP blocked;
- Gateway list **COD only**, synthetic test product/cart/customer, no external payment or messages.

There is **no real promo activation**, payment capture, external refund, Woo runtime hook registration, or pricing modification to Dev/WWW. Coupons created with a synthetic test prefix apply the **simulator-projected discount amount** as a native Woo `fixed_cart` coupon; **not** correct production BOGO/bundle item-level allocations. Gift case checks a synthetic product's stock, then puts a free gift item into a local sandbox order; this does **not** constitute complete live gift inventory/consent fulfillment. No production site code or user records are read.

## Evidence: actual local WooCommerce orders
| Test | Order | Native coupon amount | Native shipping | Native CA tax | Actual order amount |
|---|---|---:|---:|---:|---:|
| Fixed Spend & Save | #32 | USD12.00 | USD10.00 | USD14.64 | USD197.64 |
| Percent Spend & Save | #34 | USD18.50 | USD10.00 | USD14.12 | USD190.62 |
| Gift with positive local managed stock | #35 | 0, separate zero-price gift | USD10.00 | USD15.60 | USD210.60 |
| BOGO projection | #37 | USD22.50 | USD10.00 | USD13.80 | USD186.30 |
| Bundles projection | #39 | USD23.00 | USD10.00 | USD13.76 | USD185.76 |
| Tiered projection | #41 | USD37.00 | USD10.00 | USD12.64 | USD170.64 |

All `WC_Order` amounts reconcile to the respective native cart totals, remain USD, native offline COD pending, not paid, gateway transaction ID empty. These are actual orders **only in local isolated MySQL**, not financial settlement or real Commerce runtime activation. Independent sandbox evidence details: [Issue #216](https://github.com/beyourlovercom/YBY-Core/issues/216).

## Remaining P5/P6
P5 true Woo plugin-managed six-rule runtime must be explicitly designed and separately sandbox-gated, including Woo coupon/line allocation, usage limits, collisions, gift inventory and refunds; Checkout Blocks/classic, HPOS and zones require more evidence. P6 test gateway credentials, payment modes, 3DS failures, FX settlement snapshots, provider refund and isolated webhook callbacks remain unverified. Do not mark P5/P6 fully PASS or change WWW/main/Stable. Test user #208 remains untouched due 88 associated posts.
