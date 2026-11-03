# R10-H3-A/B Genuine Auto and Manual-Priority Sandbox

Date: 2026-11-02. SOURCE ONLY, not deployed.

## Frozen release boundary
Draft PR #213 base 7002e039d9fc3880f0a0c6dd37382ed6d3114ff5. Dev remains 1.1.0-rc2 OBSERVE / Shipping OFF / Coupon OFF. WWW remains 1.0.14 OBSERVE; main unmerged. No real customer orders or payments.

## Genuine installed-vendor findings
Tested locally on Windows with a dedicated marked 127.0.0.1 WooCommerce 11.2.1 database and matching WebToffee Pro 3.2.0, StoreApps Smart Coupons 9.51.0, YayCurrency Pro 3.3.5. Paid vendor files and licenses stay local, never committed.

**StoreApps H3-A PASS:** its legacy auto-coupon query required a postmeta row for wc_sc_user_role_ids, including an empty value. Adding that row to the isolated synthetic coupon allowed its real auto_apply_coupons() logic to apply a discount and write wc_sc_auto_applied_coupons. Previously, a mere wc_sc_auto_apply_coupon=yes meta flag was insufficient.

**WebToffee H3-A PASS:** its lookup table is_auto_coupon column reads absint of the _wt_make_auto_coupon meta. The correct synthetic fixture uses numeric 1 rather than string yes, which yielded lookup is_auto_coupon=1 and real plugin auto_apply_coupons() applied the coupon and wrote auto_applied_coupons.

## H3-B transaction ordering and retry behavior
Both vendors initially apply their own genuine automatic coupon in the local cart. In the test-only trusted server context, an invalid customer-entered manual coupon was tried BEFORE removing any auto coupon: native Woo rejected it and H3 exact snapshot (ordered coupons, two vendor histories, cached candidates, chosen shipping) remained identical. A valid manual coupon was applied while the auto coupon remained; only on success did the test remove that exact auto coupon. A native free-shipping coupon remained.

Both vendors retain stale auto-applied session entries after a simple coupon removal. Removing only the target entry without a replay guard leads StoreApps to auto-reapply; WebToffee re-applies upon totals calculation. Test-only guards therefore suppress only the verified, replaced, positive ordinary auto code WHILE a valid manual code remains applied:
- WebToffee: wt_is_valid_coupon and wt_sc_auto_coupons_list, preserving unrelated auto candidates and protected coupon types.
- StoreApps: wc_sc_is_auto_apply, preserving any pre-existing vendor veto and other candidate coupons.
- Targeted session reconciliation: StoreApps public unset_auto_applied_coupon and WebToffee WC session removal of only the previously verified auto code.

**Actual Windows isolated end-to-end tests** after these changes: StoreApps 20/20 PASS, WebToffee 19/19 PASS. They verify genuine auto origin, failed-manual exact snapshot, manual-first safe sequencing, free-shipping survival, no reapplication after total calculation or explicit vendor trigger, no stale auto history, and automatic coupon eligibility returning when manual coupon is subsequently removed. Source-only suppression harnesses: StoreApps 13/13 PASS; WebToffee 16/16 PASS. These tests use local disposable products/coupons and perform cleanup.

## Still OPEN — not production acceptance
This is a test-driven source candidate, NOT a registered Woo or Store API runtime adapter. Neither H3B helper is loaded by the production plugin bootstrap. Manual request provenance is issued by the local test driver, not yet authenticated from genuine Classic and Store API/Blocks handlers. Full credits, BOGO, gift-coupon subtype checks, individual-use conflicts, multiple simultaneous automatic coupons, five currencies, notices/FX totals, and a late post-commit failure requiring exact vendor-aware rollback remain OPEN. Earlier native remove-then-readd rollback changed coupon order, so that path remains explicitly forbidden. The safer validate-first model protects early validation failures but does not certify every late failure.

Further Owner approval is required for any versioned Dev install, Coupon ON, global OBSERVE exit, real checkout transaction or www/main integration. Issue #276 remains OPEN. No release permission is implied by successful source CI.
