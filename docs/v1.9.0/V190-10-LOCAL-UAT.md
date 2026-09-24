# V190-10 — Local UAT

Date: 2026-09-24
Status: PASS
Environment: localnew.beyourlover.com local BYL clone.

## Setup

The pre-UAT local baseline was:
- Andy Core 1.5.7 installed but inactive.
- WebToffee Order Export & Order Import for WooCommerce active.
- no stored yby_core_enabled_modules_v1 option.

A filesystem backup of the 1.5.7 plugin directory was taken before a code-only overlay of the current v1.9.0 feature branch. Andy Core was then enabled only in Local, and woo_order_export was added to the existing default module set.

## Admin surface

The Commerce page rendered with:
- Andy Commerce page title;
- Order Export and Settings tabs;
- export action yby_woo_order_export;
- Default, Full Export, BYL Processing Orders, and BYL Full Report presets;
- date, order status, order ID, customer email, currency, and payment-method filters;
- manage_woocommerce permission gate.

The legacy WebToffee plugin remained active throughout UAT.

## Real order smoke

Read-only real Woo orders:
- 39115
- 39116

Both BYL Processing Orders and BYL Full Report exported exactly 2 orders / 2 rows.

Processing output:
- exact H18 base header order;
- 39 total columns for this two-order sample including dynamic item columns.

Full output:
- exact H65 base header order;
- 86 total columns for this two-order sample including dynamic item columns.

## Zero-business-write check

Before and after export, for both orders, these values were byte-for-byte unchanged:
- order status;
- order total;
- modified timestamp;
- legacy wf_order_exported_status metadata.

39115 remained processing / 161.00 / modified 2026-08-06 09:31:33 / legacy marker 1.
39116 remained completed / 89.00 / modified 2026-08-06 05:42:30 / legacy marker 1.

## Restoration

After UAT:
- Andy Core was deactivated again;
- yby_core_enabled_modules_v1 was removed back to the prior null state;
- legacy WebToffee remained active;
- the local yby-core directory was restored to the backed-up Andy Core 1.5.7 files.

## Gate

V190-10 PASS. Proceed to V190-11 isolated Dev UAT.
