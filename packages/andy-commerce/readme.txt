=== Andy Commerce ===
Contributors: ybyglobal
Requires PHP: 8.0
Stable tag: 1.0.6

Reusable B2C / WooCommerce Addon for Andy Core.

== Dependencies ==

* Andy Core 1.9.0+
* WooCommerce

The plugin fails closed with an admin notice when dependencies are missing or incompatible.

== Changelog ==

= 1.0.6 =
* Marketing V2-M1 Dev-only typed draft-rule form for Spend & Save, BOGO, Bundles, Tiered Discount and disabled Free Gift with M8-compatible fixed/percent/quantity fields.
* Validate minor-unit and percentage conversions server-side; preserve existing M7 JSON drafts through explicit Advanced mode. Keep nonce, capability, revision guards, all drafts OFF and no runtime Woo discount/checkout hooks.
* Preserve 1.0.5 Currency OS synthetic preview, 1.0.4 Marketing Studio, BYL49 policies and native Woo Orders UI untouched.


= 1.0.5 =
* Dev-only Currency OS M1 USD-first source evaluator and five-currency synthetic shadow admin page; no Woo or payment hooks.
* Preserve v1.0.4 M7 exact-name nonce security fix and inactive Marketing M4-M8 Studio.
* Preview and tests only; no Stable/main/WWW release.


= 1.0.4 =
* Fix the M7 Dev draft Save/Edit/Archive nonce action mismatch that caused WordPress to reject fresh form submissions with "The link you followed has expired".
* Preserve all marketing promotions OFF and legacy WooCommerce/BYL49 shipping authority; no live offer execution.


= 1.0.3 =
* Reuse the M4–M6 read-only shipping comparison, trusted Woo cart adapter and free-shipping projection from reviewed PR #187, without changing the authoritative legacy BYL49 shipping eligibility.
* Add a Dev-scoped Marketing Studio with non-active draft create/edit/archive, nonce and Woo administrator permissions, maximum draft limits and optimistic revisions.
* Add M8 pure evaluator for BOGO, Bundles, Spend & Save and Tiered Discounts with deterministic non-stacking previews and strict fail-closed input validation. Free Gift remains OFF; no live cart/order discount hooks are registered.
* Prepare bounded Dev PC/M14 Marketing Studio UAT; campaign publishing, scheduler, real gifts, actual shipping policy handoff and any production rollout remain separate approvals.


= 1.0.2 =
* Consolidate previously approved native WooCommerce Orders List and full HPOS Order Detail UI behind independent Andy Core site-level switches.
* Display order number plus product SKUs in the native Order list and a concise numeric ID in the detail heading.
* Keep the original native WooCommerce Order Data and action bar above Customer/AfterShip cards on 375–390px mobile screens.
* Use WooCommerce's native Completed status and Update button from the order detail primary action, without an additional Shopify-style confirmation.
* Retain Andy Commerce 1.0.1 legacy BYL49 shipping policy and site-specific configuration until explicitly handed off; no automatic policy migration.
* Keep native AfterShip, AffiliateWP, payments, refunds and order metaboxes owned by their respective extensions; do not add independent shipment writes.

= 1.0.1 =
* Legacy BYL Platform coexistence, Store API compatibility and BYL49 shipping handoff protection.
