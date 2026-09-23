# V190-8 — Large Export / HPOS Regression

Date: 2026-09-23
Status: PASS

## Scope

This gate validates the V190-7 exporter against the V190-8 Handoff matrix without Production changes or Woo business-data writes.

Covered order shapes:
- one order and multi-item order
- variable product
- coupon and refund
- cancelled and failed status
- guest and registered customer
- multiple currencies
- missing phone and company
- international address
- long/free text
- emoji / non-ASCII

The matrix runs through the real Andy CSV streamer with generated WC-like objects and a generator-backed adapter.
## Large export measurements

Harness: `tests/v190-large-export-hpos-regression-harness.php`

| Orders | Batch | Rows | Adapter passes | Elapsed | End-memory delta |
|---:|---:|---:|---:|---:|---:|
| 1,000 | 200 | 1,000 | 2 | 0.044 s | 0.00 MB |
| 5,000 | 200 | 5,000 | 2 | 0.213 s | 0.00 MB |
| 10,000 | 200 | 10,000 | 2 | 0.425 s | 0.00 MB |

The BYL compatibility presets intentionally use two bounded passes:
1. discover the maximum line-item width required by legacy separate-column output;
2. stream one CSV row per order.

The harness writes to a temporary file and the adapters yield orders lazily. It does not construct a 1k/5k/10k order array.
## HPOS / legacy storage contract

The production code continues to obtain orders through `wc_get_orders()` in `YBY_Woo_Order_Query_Adapter`.

Regression assertions reject direct dependencies on:
- `$wpdb`
- `wp_posts`
- `wp_postmeta`

Therefore the exporter delegates order storage to WooCommerce and does not choose HPOS tables or legacy posts storage itself.

Runtime storage-mode toggling is intentionally not performed by this gate because changing Woo storage configuration would mutate the cloned site. Local and isolated-Dev runtime smoke remain V190-10/V190-11 gates.

## Batch freeze

Default batch size remains **200**.

Reason:
- 10,000-order synthetic regression remains bounded at 200;
- no memory-growth signal requires lowering it;
- there is no measured evidence in this gate that raising it improves the two-pass compatibility export enough to justify a wider query batch.

## Gate

**V190-8 PASS. Proceed to V190-9 Legacy CSV Diff.**
