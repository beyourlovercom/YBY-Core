# Andy Commerce Observer 0.1.0-rc1 — Dev only candidate

This is a **separate, standalone WordPress plugin**, **not** the existing Andy Commerce package, and is **not** a release of Commerce M7-M10 / Currency OS. It is an optional, administrator-only runtime inventory plugin used to compare Dev and www configurations safely.

## Non-mutation guarantees
- On **frontend requests**: registers **zero** actions and filters. Does not enqueue assets, touch carts/sessions or inspect customers.
- On **admin requests**: registers only `admin_menu` and exposes a `Tools → Andy Commerce Observer` read-only page restricted to `manage_options`.
- No `woocommerce_*` hooks, public REST routes, AJAX, cron, mail, pricing, gift, coupons, shipping, checkout, payment, order, inventory, ERP or customer-data modifications.
- No activation hook, option updates, schema changes, SQL writes, cache writes or settings/save buttons.
- Reports relevant plugin names/versions, WP/PHP/Woo/Core/theme, Woo base currency, HPOS option, existence (not values) of known shipping options, and count (not identity) of existing WooCommerce hook callbacks.
- No auto-install or modification of Andy Core or Andy Commerce. `Requires Plugins: yby-core,woocommerce`.

## Installation boundary
1. **Dev only** until isolated CI plus live Dev UAT is approved. This package has **NOT** been installed on dev.beyourlover.com or www.beyourlover.com by construction.
2. Install ZIP via existing Dev WordPress plugin UI; activate; open `Tools → Andy Commerce Observer`.
3. Read only; collect environment versions and current callback counts; deactivate and delete the plugin if any new warnings appear. Do not touch production without explicit separate permission.
4. Dev functional test: plug active (observational), Woo Store API and Classic checkout pages still load, coupons/gifts/shipping are unchanged compared with the baseline, no payment submissions/order creations needed. This is **an observer-only compatibility smoke**, not checkout correctness proof.

## Source and test
```
php -l andy-commerce-observer/andy-commerce-observer.php
php tests/observer-nohooks-harness.php
```
Do not merge this plugin into the actual Commerce shipping, marketing or currency runtime.
