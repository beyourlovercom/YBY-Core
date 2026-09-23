# Andy Core v1.9.0 — Commerce Utilities / Woo Order Export V1

Status: V190-0 architecture contract.

## Product boundary

WooCommerce remains the **Order Source of Truth**. Andy Core Woo Order Export is a **read-only export utility**. It does not create/import/update orders, status, payment, inventory, customer, shipment, refund, or WooCommerce metadata.

ERP remains responsible for operational fulfillment, logistics, finance, attribution downstream, and business workflows.

## Module

- id: `woo_order_export`
- name: `Woo Order Export`
- version: `1.0.0`
- schema version: `1`
- default: OFF
- capability: `manage_woocommerce`
- settings option: `yby_woo_order_export_settings_v1`
- internal dependencies: `core_runtime`, `module_registry`
- external availability: WooCommerce runtime (`WooCommerce` class or `WC_VERSION`)

Registry V2 gains an optional `availability` callback so external dependencies can fail closed without pretending to be Andy Core modules. Existing modules without an availability callback remain available by default.

## Admin IA

`Andy Core → Commerce`

Commerce page tabs:

- Order Export
- Settings

The Commerce menu is visible only when the Woo Order Export module is registered and WooCommerce is available. The export action itself additionally requires the module to be enabled.

## Export row model

The engine is preset-driven. Generic V1 presets use **flat line-item rows**: each WooCommerce line item produces one CSV row and order-level fields repeat. Orders with no line items still produce one row with empty item fields.

BYL compatibility presets are recorded from the V190-6 audit: `byl_processing_orders` (BYL Processing Orders) and `byl_full_order_report` (BYL Full Report). Both use `order_row` mode: one order per row with deterministic repeated item columns after the canonical base fields; generic `default` and `full` remain `line_item` presets. Canonical audited H18/H65 base headers are preserved. `wt_import_key` remains a read-only compatibility column using the legacy order-number value; stale provider-specific fields remain expected V190-9 differences.

## Settings V1

- `default_preset`: `default`
- `batch_size`: 200, bounded 25..500
- `bom`: true
- `audit_enabled`: true

No GTM/analytics tracking of exports. No public/static export files.

## Generic presets

- `default`: operational essentials
- `full`: full generic field contract

BYL-specific presets are limited to the two audited compatibility jobs. No accounting, customer, attribution, logistics, import, or third BYL preset is part of this contract.

## Extensibility hooks

Frozen V1 hooks:

- `andy_core_order_export_presets`
- `andy_core_order_export_columns`
- `andy_core_order_export_row`
- `andy_core_order_export_query_args`

## CSV and privacy

- UTF-8, optional BOM
- deterministic column order
- `fputcsv` escaping
- formula-injection protection for text cells beginning with `=`, `+`, `-`, `@`
- no full customer row logging
- no public cache/static CSV URL
- authenticated, capability + nonce gated streaming response

## Performance

Orders are fetched in bounded batches via official WooCommerce APIs. No giant all-orders PHP array is permitted. HPOS compatibility is maintained by avoiding direct `wp_posts/wp_postmeta` order queries.

## Legacy plugin

`Order Export & Order Import for WooCommerce` remains TEMP KEEP until:

legacy template audit → Andy preset migration → same-range CSV diff → Owner UAT → real operating cycle → disable canary → explicit delete approval.

Order Import is not implemented in v1.9.0.

## V190-3 Query Adapter

`YBY_Woo_Order_Query_Adapter` uses `wc_get_orders()` with paginated object results, deterministic date ordering, and bounded batch size 25..500. Supported generic filters: date range, statuses, explicit order IDs, billing email, currency, and payment method. No direct order SQL is used.

## V190-4 CSV Streaming

`YBY_Woo_Order_CSV_Streamer` writes directly to a supplied stream using `fputcsv`, optional UTF-8 BOM, deterministic preset columns, one row per line item, and formula-injection protection for text cells. It never constructs an all-orders CSV string/array in memory.

## V190-5 Security and response contract

Exports use authenticated `admin-post.php` POST only, capability `manage_woocommerce`, dedicated nonce, enabled-module + Woo availability gates, no-cache/no-store response headers, and direct `php://output` streaming. No public or persistent CSV URL/file is created. Bounded audit metadata stores only preset, dates/status/currency/payment summary, order-ID count, boolean email-filter presence, counts, duration, user ID, and result status; it never stores exported customer rows, email value, phone, address, or CSV content.
