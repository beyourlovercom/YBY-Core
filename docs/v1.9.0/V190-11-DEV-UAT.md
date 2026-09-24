# V190-11 — Isolated Dev UAT

Date: 2026-09-24
Status: PASS
Environment: dev.beyourlover.com

## Runtime

- WooCommerce 11.1.1: active.
- Andy Core feature build: active.
- Feature source HEAD used for package: 228892cb84b50d21335b2a1a2946887ba1ff7634.
- Package SHA256 matched before installation: 6f3b31ed43663070eab1c422591411aab1e5ed6ec5d4342592b15d81d7e2b8ea.
- woo_order_export: enabled alongside the existing default module set.
- Production was not touched.

The Dev environment does not contain the legacy WebToffee order export plugin. This differs from the older handoff assumption. Legacy coexistence was instead verified in V190-10 Local UAT, where WebToffee remained active.

## Dev data boundary

The Dev Woo store currently contains zero orders.

No synthetic/test Woo orders were created because that would violate the zero-business-data-write boundary.

## Admin and export smoke

The Andy Commerce page rendered successfully under an administrator context with manage_woocommerce.

Verified:
- Order Export action is present.
- BYL Processing Orders is present.
- BYL Full Report is present.
- module is enabled and available.
- WooCommerce is active.

With an impossible order ID filter, both presets produced header-only CSV output:
- BYL Processing Orders: 18 exact H18 headers, 0 orders, 0 rows.
- BYL Full Report: 65 exact H65 headers, 0 orders, 0 rows.

Dev Woo order count remained 0 before and after the smoke test.

## HTTP health

- Dev homepage: HTTP 200.
- unauthenticated Commerce admin URL: HTTP 302, expected WordPress login redirect.

## Gate

V190-11 PASS.

Proceed to V190-12 Owner UAT. Keep Dev Andy Core and woo_order_export enabled for Owner review. Do not deploy Production and do not disable/delete the production legacy plugin.
