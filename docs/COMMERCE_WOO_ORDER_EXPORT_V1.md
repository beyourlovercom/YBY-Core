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

This does not pre-guess BYL legacy preset shape. BYL presets are added only after reading the real five legacy templates and diffing real exports.

## Settings V1

- `default_preset`: `default`
- `batch_size`: 200, bounded 25..500
- `bom`: true
- `audit_enabled`: true

No GTM/analytics tracking of exports. No public/static export files.

## Generic presets

- `default`: operational essentials
- `full`: full generic field contract

BYL-specific presets are intentionally absent until V190-6/7 legacy-template audit.

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
