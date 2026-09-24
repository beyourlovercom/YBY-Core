# V190-11 — Isolated Dev UAT

Date: 2026-09-24
Status: PASS
Environment: dev.beyourlover.com

## Corrected execution order

This Dev UAT was rerun only after the Windows localdev Owner UAT passed.

Correct order:
1. localdev.beyourlover.com implementation and visual Owner UAT.
2. Owner PASS.
3. isolated Dev code-only deployment.
4. Dev read-only smoke.
5. stop before Production / legacy retirement.

## Runtime

- WooCommerce 11.1.1: active.
- Andy Core feature build: active.
- Feature source HEAD: c0119974c3559d5c81343496f39210cfd1fa058e.
- Package SHA256: a2cdd96e45150e8c525d3f7a21371923a66da45145890ab7223bb291ad5ac873.
- package SHA256 matched on Dev before extraction.
- woo_order_export: enabled.
- Production was not touched.
- Dev does not contain the legacy WebToffee order export plugin.

## Dev data boundary

The Dev Woo store currently contains zero orders.

No synthetic Woo orders were created.

## Smoke result

PASS:
- Andy Commerce page contract available.
- BYL Processing Orders preset available.
- BYL Full Report preset available.
- manage_woocommerce gate available.
- WooCommerce active.
- BYL Processing Orders: exact H18 18-header CSV, 0 orders / 0 rows.
- BYL Full Report: exact H65 65-header CSV, 0 orders / 0 rows.
- Dev Woo order count remained 0 before and after.

## Gate

V190-11 PASS after localdev Owner PASS.

Do not deploy Production.
Do not disable or delete the legacy production plugin.
