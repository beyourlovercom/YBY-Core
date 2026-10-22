=== Andy Commerce ===
Contributors: ybyglobal
Requires PHP: 8.0
Stable tag: 1.0.2

Reusable B2C / WooCommerce Addon for Andy Core.

== Dependencies ==

* Andy Core 1.9.0+
* WooCommerce

The plugin fails closed with an admin notice when dependencies are missing or incompatible.

== Changelog ==

= 1.0.2 =
* Consolidate previously approved native WooCommerce Orders List and full HPOS Order Detail UI behind independent Andy Core site-level switches.
* Display order number plus product SKUs in the native Order list and a concise numeric ID in the detail heading.
* Keep the original native WooCommerce Order Data and action bar above Customer/AfterShip cards on 375–390px mobile screens.
* Use WooCommerce's native Completed status and Update button from the order detail primary action, without an additional Shopify-style confirmation.
* Retain Andy Commerce 1.0.1 legacy BYL49 shipping policy and site-specific configuration until explicitly handed off; no automatic policy migration.
* Keep native AfterShip, AffiliateWP, payments, refunds and order metaboxes owned by their respective extensions; do not add independent shipment writes.

= 1.0.1 =
* Legacy BYL Platform coexistence, Store API compatibility and BYL49 shipping handoff protection.
