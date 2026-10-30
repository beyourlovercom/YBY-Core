# Andy Commerce 1.0.14 · R2–R4 Controlled UAT and Rollback

**Owner scope:** Permanent observation (not a throwaway plugin). **Source/CI only until separate install approval.** Black Friday, Currency OS and checkout feature expansion are out of scope.

## Ground truth

- Integration publisher: [Draft PR #213](https://github.com/beyourlovercom/YBY-Core/pull/213). This R2–R4 child must be integrated there after exact-HEAD CI. Do not merge main.
- Previous WWW baseline: Andy Commerce **1.0.13 installed/active**, safe observation page responds HTTP 200 to an authenticated administrator. Menu absent under Andy Core because unified navigation checked `Andy_Commerce_Admin`, which is intentionally unavailable in observation mode.
- WWW Core, Woo, PHP, YayCurrency Pro, Smart Coupons and HPOS current versions must be **re-read** at install time. Historical versions do not authorize a release.
- 1.0.14 keeps `andy_commerce_runtime_mode_v1=observe` (or production default observe) and provides new admin-only navigation through `/wp-admin/admin.php?page=andy-commerce-safe-mode`. Existing deep link `page=andy-commerce-observation` is preserved.
- Admin runtime diagnostic reports only **current wp-admin request** registered callback metadata; it cannot prove storefront, AJAX or gateway behavior. Unattributed closures are counted separately.

## R2: Core native navigation UAT

1. Login as site administrator; ensure WooCommerce and Andy Core are present, and load `/wp-admin/admin.php?page=andy-commerce-safe-mode`.
2. Under Andy Core, verify the single visible **交易 · 观察** L2 item, positioned before 系统 (and no extra Marketing/Commerce parent); verify it opens safe mode.
3. Reopen existing `/wp-admin/admin.php?page=andy-commerce-observation`; safe page remains accessible. Verify non-admin users cannot see or change mode.
4. Check `/wp-admin/admin.php?page=andy-commerce` legacy route still opens the safe-only view when observing (no Shipping/Currency marketing runtime loaded).
5. On Dev/local only, test active-mode Core navigation remains unchanged; no duplicate safe L2.

## R3: Safety and audit gates

1. On WWW **do not exit observe**. Reload independent admin, homepage, product and cart requests; no commerce transaction module boot or sitewide changes.
2. Verify mode option persists across independent requests, no default option migration or startup write.
3. Read-only inspect `andy_commerce_runtime_audit_v1`: empty on first deployment; historical 1.0.13 changes not backfilled.
4. In disposable isolated Woo only, test `active → observe → active → observe`: nonce, administrator capability, stale form and typed acknowledgement **ENABLE_COMMERCE_RUNTIME**. Failed mode writes never show success; failed audit writes show an explicit warning.
5. Audit bounded to latest 20 events with UTC, administrator numeric ID, prior/next mode and version, without passwords, email, IP or customer PII.
6. **Limit:** no shutdown-handler auto-recovery from arbitrary PHP/plugin crashes. Emergency operator may deactivate Andy Commerce if the WP dashboard becomes inaccessible; no automatic payment/shipping fallbacks claimed.

## R4: Diagnostic evidence

- Detect WP/PHP/Core/Woo versions, base USD and HPOS/sync option values.
- Identify enabled YayCurrency and Smart Coupons by plugin slug. Missing match means **not detected**, not proven absent.
- Inspect the 11 configured Woo hooks, total and Commerce-owned callbacks plus anonymous/unattributed callbacks. Verify zero recognized Commerce transactional callbacks while observing in current wp-admin request.
- Compare shipping, multi-currency and smart-coupon actual outcomes **only in separate disposable/Dev UAT**; no synthetic result is a live customer checkout result. Shadow computation and per-module active controls are deferred to 1.1.0.

## Release / rollback

1. Freeze exact GitHub integrator SHA and completed CI. Record artifact ZIP SHA256 and installed controller SHA256. Prepare Dev ZIP only via the canonical #213 publisher script and approvals. Retain previous **1.0.13 ZIP** and current site backup.
2. Dev install and above admin UAT; test no Woo order/payment write and minimum frontend readback. Write an evidence record with timestamp and account scope.
3. Obtain **separate Owner PASS** before any WWW file replacement. Keep WWW observation status. Install through ordinary plugin ZIP update path; verify option still `observe`, visible L2, safe admin routes, admin-only read-only diagnostics and no new Commerce hooks on guest requests.
4. If update causes errors: **deactivate Andy Commerce** (or file-level replace 1.0.13 from the preserved ZIP if needed), then verify Woo cart/checkout/site readback. Do not restore a stale full Woo database because that may discard new live orders.
5. **Exit observation mode requires a second independent Owner approval** plus parity checks for WooCommerce/YayCurrency/Smart Coupons. The UI text confirmation is a safety guard, **not** a substitute for permission.
