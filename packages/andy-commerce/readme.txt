=== Andy Commerce ===
Contributors: ybyglobal
Requires PHP: 8.0
Stable tag: 1.1.0-rc1

Reusable B2C / WooCommerce Addon for Andy Core.

== Dependencies ==

* Andy Core 1.9.0+
* WooCommerce

The plugin fails closed with an admin notice when dependencies are missing or incompatible.

== Changelog ==

= 1.1.0-rc1 (Dev-only R10-C-D Owner-approved candidate; default OFF) =
* Adds strictly default-OFF, independently controlled R10-C Shipping and Coupon compatibility adapters.
* Exact Woo/Yay/Smart Coupons versions, Owner server opt-in, Core admin controls and global OBSERVE first.
* No automatic module activation, checkout/payment actions, login route changes or database migrations.
* WWW production remains on 1.0.14 OBSERVE pending separate Owner approval.


= 1.0.14 (Draft source / not production-published) =
* Restore visible Andy Core → 交易 · 观察 navigation after Core menu consolidation without activating full Commerce runtime.
* Administrator-only typed restore confirmation, nonce-protected mode transitions and last-20 audit records.
* Read-only environment snapshot, active related-plugin inventory, HPOS flags, selected Woo hook attribution and audit log.
* Fail-closed www default retained; no automatic database migration, payment, shipping, coupon, gift or order writes.


= 1.0.13 (Draft observation-mode candidate, NOT stable/www) =
* First-party Andy Commerce observation toggle: one-click enter, explicit acknowledged exit.
* Fail-closed before loading Commerce exports/shipping/coupon/currency/marketing/orders hooks on www and unknown production hosts.
* Frontend observes with zero Andy Commerce hooks; admin can view sanitized status and switch using authenticated nonce/capability-protected POST.
* Preserve existing Dev staging and disposable local Woo default behavior; no schema migration, campaign activation, checkout or payment change.
* WWW rollout, live-feature activation and GitHub main/Stable remain independently gated.

= 1.0.9-dev.1 (Dev-only R8-R10 Candidate) =
* Adds master-OFF Dev shopper currency runtime using explicit time-limited Frankfurter reference FX snapshots; stale snapshots and legacy currency conflicts disable it.
* Preserves USD Woo merchant base, functional native Footer currency selector, Woo session and Store API currency, native fixed coupon and tax-free flat shipping preview.
* Foreign payment gateways disabled pending R11; Marketing unchanged/OFF; never activate on WWW. This is NOT Stable or YayCurrency production replacement.

= 1.0.8 (Dev-only Candidate) =
* Currency OS V2 Owner-approved native admin layout: five configuration tabs, save of isolated Dev preview settings, preserved 1.0.6 FX Ledger.
* Dev-admin-only [andy_currency_switcher] shortcode with compact Header/Bricks form and read-only storefront UAT at ?ac_currency_preview=1.
* No live Woo price/checkout/tax/shipping/payment, no FX settlement, no WWW changes; inherits Currency M2 and Marketing M9 Draft source.


= 1.0.7 =
* Dev-only M9 multi-rule USD marketing lab: independent gift/discount thresholds, discount-before-free-shipping policy projection and five-currency snapshots.
* Preserve Currency OS M1/M2 source and Marketing M4–M8; all rewards, gifts, coupons, Woo rates and checkout runtime remain OFF.
* No new live promotion write endpoint, no main/Stable/WWW release.


= 1.0.6 =
* Dev-only Currency OS M2 explicit read-only daily reference USD rates from fixed Frankfurter API endpoint, no keys or background polling.
* Secure fixed-source bounds, date freshness, four target currencies, missing/duplicate/invalid-rate refusal; no Woo price, order, payment, shipping or discount mutations.
* Preserve Currency OS M1 shadow preview, M7 nonce fix and inactive Marketing M4–M8; this is not a Stable release.


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
