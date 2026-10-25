# Andy Commerce P6 — Independent payment test gates (2026-10-25)

## Scope and source of truth
This branch is a **Draft source-only** continuation of unique Commerce integrator PR #213 at exact parent `ca59db1547733c967c3eab21b1eefe7d0a07b963`. GitHub main, Stable, WWW and installed Dev versions must remain unchanged. WP backend is Desktop-only. No official DC device or READY computer may be restarted.

## Confirmed environment
The disposable localhost WooCommerce 11.2.1 + WordPress 7.1.3 / Andy Core 2.2.11 / Commerce 1.0.8 instance on US Mac mini has a distinct MySQL schema, HPOS, `127.0.0.1:18973` listener and mail / external WP HTTP blocking. M14 isolated automatic-promotion switch remains OFF. M15/M16 proved native SKU allocation, concurrent campaign quota and persisted local Woo orders with claim recovery.

## P6 test layers
1. **P6-A (synthetic, no provider connection):** deterministic status machine for success, decline, 3DS-required, cancellation, partial/multiple refunds, idempotent event IDs, false live-mode rejection, currency/amount checks, display-only FX snapshot. Negative tests include replay, changed replay payload, stale/forged signature, over-refund, duplicate capture, invalid payment transitions.
2. **P6-B (local Woo):** bind the synthetic event to a genuine HPOS Woo test order and reconcile its authoritative USD total, captured/refunded minor units and per-product M15 refund allocation, without ever calling a real provider; test Store API and Classic as feasible.
3. **P6-C (actual external test mode):** requires dedicated **test** Stripe/WooPayments/PayPal account, explicit test-only credential scope, separately registered callback URL, documented webhook secret and server-side signature verification, test-mode capability inspection. Do not substitute production credentials or copy any tokens into GitHub.
4. **P6-D (provider UAT):** using actual isolated provider test calls, verify capture, 3DS success/failure, hard decline, cancellation, webhook duplicate/out-of-order, full/partial provider refunds, USD authority and 5-currency checkout/settlement (only where provider actually supports each currency). Every result needs exact order ID, provider test-mode object ID and redacted evidence. Never invent provider results.

## Gate status
M15/M16 local COD tests are PASS but **do not prove P6 provider payment**. No dedicated provider test credentials or isolated provider callback have yet been verified in this branch; therefore P6-C/D stay BLOCKED. Synthetic P6-A cannot be marked equivalent to an actual Stripe/WooPayments/PayPal test charge.

## Boundary invariants
- No external emails, inventory/fulfillment, production orders, main merge, Stable, or WWW deployment.
- Code tests live under `packages/andy-commerce/tests`. Any local-only HTTP or Woo-writing driver belongs under `packages/andy-commerce/tests/sandbox` and must be excluded from an installable Commerce ZIP.
- Do not alter or delete #208's Dev Subscriber account or the 88 posts attributed to it; this unresolved cleanup is separate.
