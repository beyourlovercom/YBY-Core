# V190-9 — Legacy CSV Diff

Date: 2026-09-24
Status: PASS
Scope: read-only same-order formatter parity against WebToffee 2.7.6 on the local BYL clone.

## Sources

Legacy plugin:
- Order Export & Order Import for WooCommerce
- WebToffee 2.7.6
- formatter: get_orders_csv_row()

Historical CSV evidence:
- order-export-2026-06-05-01-15-24.csv
- order-export-2026-07-01-06-58-33.csv
- order-export-2026-08-05-08-42-32.csv

The 2026-08-05 file confirms the active H65 base header contract plus legacy separate-column line-item ordering.

## Same-current-order comparison

The same current WC_Order objects were passed through the legacy row formatter and Andy Core byl_full_order_report.

Orders:
- 39115 — multi-item processing order
- 39116 — current completed order

WooCommerce runtime: 10.9.4.
Maximum line-item width: 3.

Results:
- H65 base headers: exact
- 39115 base fields: 0 differences
- 39116 base fields: 0 differences
- standard dynamic header order: exact
- 39115 standard dynamic values: 0 differences
- 39116 standard dynamic values: 0 differences

## Compatibility corrections

V190-9 aligned legacy two-decimal formatting where applicable, blank empty collections, shipping/fee/tax/coupon/refund/note serialization, false customer-note marker behavior, line_item_N serialization, dynamic column ordering, SKU header capitalization, and Product Item total/subtotal formatting.

shipping_total intentionally remains the raw Woo shipping-total representation because that is the verified WebToffee behavior.

The comparison performed no Woo business-data writes and did not invoke the legacy export controller/history mutation path.

## Deliberate non-migrations

Andy Core does not migrate WebToffee export-state writes, Order Import, arbitrary all-meta dumps, or provider-specific fields outside the active H65 evidence.

Direct isolated calls to the legacy helper emit an include_timezone_offset warning because that variable is normally supplied by its controller. Compared dates still matched exactly.

## Regression gate

All V190 harnesses pass after the parity changes, including the 1k / 5k / 10k bounded large-export regression. git diff --check is clean.

**V190-9 PASS. Proceed to V190-10 Local UAT.**
