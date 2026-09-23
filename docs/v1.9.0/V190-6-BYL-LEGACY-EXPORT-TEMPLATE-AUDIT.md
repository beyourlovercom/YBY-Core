# V190-6 BYL Legacy Order Export Template Audit

Status: V190-6 documentation-only audit. No PHP, runtime, database, WooCommerce, or legacy-plugin changes are authorized by this document.

## Evidence scope and limitation

The audited legacy exporter is **Order Export & Order Import for WooCommerce** by WebToffee, version **2.7.6**.

Evidence was collected through read-only plugin and database inspection of the `localnew.beyourlover.com` production-data clone. The current Dev environment does not have this legacy plugin installed. Therefore, this audit records the verified production-clone evidence and its migration implications; it is not a claim that the current Dev environment reproduces the legacy plugin state.

The relevant tables are:

- `wp_wt_iew_mapping_template`
- `wp_wt_iew_action_history`

## Current saved templates

Five current saved export/order templates were found:

| # | Saved name | Shape and settings | Historical match | Classification |
|---|---|---|---|---|
| 1 | `Export-orders-processing@2503` | 16 fields; `wc-processing`; line items included; migration/default mode | No exact current-field historical matches | ACTIVE / MUST MIGRATE as part of the processing family |
| 2 | `月度导出报表` | 54 fields; April 2025 range; separate columns | No exact current-field historical matches | Do not use as the canonical Full Report shape |
| 3 | `111` | 20 fields; `wc-processing`; from 2025-09-30; separate columns | No exact current-field historical matches | LEGACY / RETIRE |
| 4 | `22` | 18 fields; `wc-processing`; from 2025-10-11; separate columns | 86 exact successful historical runs | ACTIVE / MUST MIGRATE as part of the processing family |
| 5 | `66` | 30 fields; `wc-processing`; line items excluded; separate columns; filename `644` | 1 exact successful historical run | ACTIVE historical one-off / MAY CONSOLIDATE into Full; do not create a third preset |

Saved template names and current snapshots are not reliable proxies for actual usage. Historical action signatures are the canonical evidence for usage and migration decisions.

## Action-history evidence

There are **101 action-history rows** in total, covering **2025-10-09 through 2026-08-05**. The latest successful export was on **2026-08-05** and contained **1,091 orders**. Of the history, **98 successful export/order rows** carry `mapping_selected_fields`.

Only three successful historical field signatures were found:

| Signature | Historical usage | Orders | Relationship |
|---|---:|---:|---|
| A. Full 65 fields | 11 runs, 2025-12-11 through 2026-08-05 | 6,947 summed; maximum 1,214 | Full/monthly historical family |
| B. Processing 18 fields | 86 runs, 2025-12-10 through 2026-04-09 | 4,240 summed; maximum 134 | Exact match for current template `22` (#4) |
| C. 30 fields | 1 run, 2026-03-24 | 82 | Exact match for current template `66` (#5) |

The historical evidence is therefore materially different from the five saved-template snapshots: the 65-field signature is the dominant Full/monthly export shape, while the 18-field signature is the repeatedly used processing shape.

### Signature A: full 65-field ordered list

```text
order_id, order_number, order_date, paid_date, status, shipping_total, shipping_tax_total, fee_total, fee_tax_total, tax_total, cart_discount, order_discount, discount_total, order_total, order_subtotal, order_key, order_currency, payment_method, payment_method_title, transaction_id, customer_ip_address, customer_user_agent, shipping_method, customer_id, customer_user, customer_email, billing_first_name, billing_last_name, billing_company, billing_email, billing_phone, billing_address_1, billing_address_2, billing_postcode, billing_city, billing_state, billing_country, shipping_first_name, shipping_last_name, shipping_company, shipping_phone, shipping_address_1, shipping_address_2, shipping_postcode, shipping_city, shipping_state, shipping_country, customer_note, wt_import_key, tax_items, shipping_items, fee_items, coupon_items, refund_items, order_notes, download_permissions, meta:_wc_order_attribution_device_type, meta:_wc_order_attribution_referrer, meta:_wc_order_attribution_session_count, meta:_wc_order_attribution_session_entry, meta:_wc_order_attribution_session_pages, meta:_wc_order_attribution_session_start_time, meta:_wc_order_attribution_source_type, meta:_wc_order_attribution_user_agent, meta:_wc_order_attribution_utm_source
```

### Signature B: processing 18-field ordered list

```text
order_number, order_date, status, order_total, order_currency, customer_email, billing_email, billing_phone, shipping_first_name, shipping_last_name, shipping_company, shipping_phone, shipping_address_1, shipping_address_2, shipping_postcode, shipping_city, shipping_state, shipping_country
```

### Signature C: 30-field summary

The 30-field signature is the billing, shipping, contact, and customer-note shape plus `shipping_items` and `order_notes`. It is evidenced by one exact successful run only.

## Legacy row model and implementation boundary

The legacy separate-column exporter uses one order per row. It determines the maximum line-item count across the export and appends repeated columns for each position, including `line_item_N` and `Product Item N Name`, `Product Item N id`, `Product Item N SKU`, `Product Item N Quantity`, `Product Item N Total`, and `Product Item N Subtotal`.

This row model is part of the business semantics to preserve for the migration. The legacy exporter also contains write behavior through `wf_order_exported_status`. Andy must **not** migrate that write behavior; the V190-7 work is an export/read contract, not a port of legacy exporter state mutation.

## Migration classification and decision

1. **Templates 1 and 4: ACTIVE / MUST MIGRATE.** They belong to the processing family. Consolidate them as one canonical preset: **BYL Processing Orders**. The historical 18-field signature, not the saved names, is the canonical processing evidence. Template 1 remains relevant as an active migration/default variant even though no exact current-field historical match was found.

2. **The 65-field full/monthly historical family: ACTIVE / MUST MIGRATE.** Consolidate it as **BYL Full Report**. Do not mechanically copy stale 54-field current template 2: its current snapshot has no exact current-field historical match and is not the canonical usage evidence.

3. **Template 3: LEGACY / RETIRE.** Its 20-field snapshot has no exact current-field historical match.

4. **Template 5: ACTIVE historical one-off / MAY CONSOLIDATE into Full.** Its 30-field shape has one exact successful historical run. Do not create a third preset solely for this one-off; absorb its business-relevant fields into the Full decision where appropriate.

The target is two canonical presets—**BYL Processing Orders** and **BYL Full Report**—not a one-to-one recreation of every saved template.

## Expected V190-7 differences

V190-7 should preserve the verified business semantics and the one-order-per-row separate-column model while making the following deliberate differences:

- Omit `wt_import_key`; it is migration-only.
- Omit stale provider-specific fee fields that are not part of the 65-field evidence.
- Use semantic canonical header names rather than mechanically preserving legacy/provider labels.
- Preserve the legacy business semantics and row model, including the line-item expansion behavior where the selected report requires it.
- Do not port `wf_order_exported_status` or any other legacy exporter write behavior.

This audit is the V190-6 evidence boundary. Implementation, local UAT, Dev UAT, and deployment remain separate later workflow steps and are not performed here.

## Current saved templates disposition

1. `Export-orders-processing@2503` — 16 fields, processing filter, no exact matching successful history signature in the clone. **LEGACY / RETIRE as a saved contract**; functionality consolidates into H18.
2. `月度导出报表` — 54 fields, dated April 2025, no exact matching successful history signature. **LEGACY / RETIRE as a saved contract**; functionality consolidates into H65.
3. `111` — 20 fields, processing filter, no exact matching successful history signature. **LEGACY / RETIRE as a saved contract**; functionality consolidates into H18/H65.
4. `22` — 18 fields, exact match to H18 with 86 successful runs. **ACTIVE / MUST MIGRATE**.
5. `66` — 30 fields, exact match to H30 with one successful run. **LEGACY / RETIRE** for v1.9.0.

`RETIRE` here means “do not create a first-class Andy preset”; it does **not** authorize deletion or modification of the legacy plugin/template.

## V190-7 frozen preset contract

Create only two BYL first-class presets:

- `byl_processing_orders` — canonical H18 compatibility preset.
- `byl_full_order_report` — canonical H65 compatibility preset.

Do **not** create separate Accounting, Customer, Attribution, or Logistics presets without a later explicit requirement. The evidence supports two recurring export jobs, not those invented product categories.

H30 is covered by H65 and does not justify a third first-class preset.

## Required compatibility work before CSV diff

Current Andy generic `full` preset is not legacy-parity:
- current catalog is smaller than H65;
- current engine always expands line items to separate rows;
- H18/H65 legacy jobs use `Export line items in: Separate columns`;
- H65 includes fee/tax/discount/payment-title/IP/UA/shipping-method/notes/attribution fields not currently in the generic catalog.

V190-7 therefore must:
1. Preserve generic `default` and `full` presets for reusable B2C use.
2. Add the two BYL compatibility presets above.
3. Add canonical order-level fields needed by H18/H65 using official Woo APIs/meta accessors where available.
4. Support an **order-level / legacy-column compatibility row mode** without inventing a public import contract.
5. Keep column order deterministic and match H18/H65 ordering for parity checks.
6. Keep formula-injection protection, capability/nonce gates and bounded streaming.
7. Avoid direct order SQL and remain HPOS-compatible.

## Evidence limitations

- This audit proves historical export configuration and usage from the cloned BYL runtime.
- It does not prove a separate accounting-only or customer-only operational workflow.
- Saved template names are not reliable product names; history signatures are the stronger evidence.
- Exact CSV value parity remains a V190-9 task and must be tested on the same order range.

## Gate

**V190-6 PASS. Proceed to V190-7 BYL Preset Migration.**
