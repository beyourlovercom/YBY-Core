# R10-G2 — Legacy Woo runtime quarantine (source-only)

Date: 2026-11-01. Owner's "继续" authorizes safe source/isolated UAT progress, **not** Dev/www mode changes or a new plugin release.

## Pre-change evidence
- Main integration PR #213 Draft, exact baseline `98d7b32169b277b9d5099db9aab0ce368af88dd9`; main unmerged.
- Dev currently **Andy Commerce 1.1.0-rc2 ACTIVE, global OBSERVE, R10-C Shipping OFF, Coupon OFF**. WooCommerce 11.2.1, YayCurrency Pro 3.3.5 and both licensed Smart Coupons vendor plugins are WordPress active.
- R10-G live Dev *read-only* source-policy evaluation found `byl-platform-legacy` settings persisted and valid, with `Shipping_Promotion_Policy::is_runtime_active()=true`; simply exiting OBSERVE previously would bring up old Commerce Shipping hooks independently of the R10-C switches. Issue #284 is the blocker.
- R10-H manual-versus-auto priority is still unresolved; this change does NOT modify coupon behavior.

## Implementation in source candidate only
- Added `includes/class-andy-commerce-r10g2-legacy-runtime-quarantine.php` with a three-module allowlist: `shipping`, `currency`, `marketing`.
- **Default OFF** for each: `andy_commerce_r10g2_legacy_{shipping|currency|marketing}_enabled_v1` must be exact string `yes`, independently, AND server must define `ANDY_COMMERCE_R10G2_OWNER_LEGACY_AUTH=OWNER_APPROVED_R10G2_DEV_LEGACY`; additionally WordPress must identify exact Dev URL `https://dev.beyourlover.com` and type `staging`/`development`. Production/www/unexpected hosts are categorically denied even if stray settings/config exist. No option writes or server configuration changes were made.
- `class-andy-commerce.php::run()` wraps Currency R13 Canary/V2/Runtime C1/Dev V1 (including admin hooks), Marketing P2/P5 and its admin UAT/draft handlers, and both branches of legacy Shipping Promotion Runtime/default preference, plus Shipping Policy settings registration, in the corresponding gates.
- **Existing early global OBSERVE return in `andy-commerce.php` is unchanged and remains first**; existing R10-C Shipping and Coupon remain separately gated and are NOT enabled by this allowlist.
- Andy Commerce general admin, order-export and addon registration code paths outside the old transaction runtime are otherwise unchanged. This candidate does not promise that the entire app has no non-commerce admin hooks after a future OBSERVE exit; only explicitly enumerated legacy runtime entries are quarantined.
- No change to current version header `1.1.0-rc2` yet because **no installation is authorized**. A future owner-approved Dev build must carry a new version (e.g. rc3), verified ZIP and private rollback.
- Official ZIP contract now requires the quarantine file and bytewise source parity of both quarantine and updated legacy bootstrap.

## Evidence
- Isolated PHP no-WP test `tests/r10g2-legacy-quarantine-gate-harness.php` **21/21 default/no-server-auth PASS** and **21/21 explicit-server-auth PASS**, across site/env/OBSERVE/module switch combinations, including WWW deny, production env deny, deceptive host deny, malformed flag deny, missing authorization deny, per-module independence.
- Source AST-like brace-aware checks `tests/r10g2-legacy-boot-source-contract.py`: all 12 old startup points are within corresponding module guards; R10-C independence, OBSERVE priority, unguarded boot count zero.
- **True disposable Windows WordPress/WooCommerce 11.2.1 bootstrap**: with local Woo and vendor stack already present, test required an exact disposable marker/site URL and loaded the new source class; all three legacy group gates evaluated false; `(new Andy_Commerce())->run()` registered **zero new callbacks** for old Shipping/Currency/Marketing transaction classes (`before=0, after=0`). No orders or payments were created. First harness attempt had a WP-CLI `eval-file` file-level `declare(strict_types)` limitation; removing that declaration from the test driver (not the plugin source) allowed true WP boot PASS. No production impact.
- Disposable MySQL 127.0.0.1:14041 stopped; HTTP 14141 stopped; no sandbox services left.
- CI now runs new gate-source lint, authorized/denied suites and source guard, in addition to existing R10 tests. GitHub exact-head CI pending.

## Constraints / next gates
**This is source-only; NOT a Dev/WWW release.** Dev stays 1.1.0-rc2/OBSERVE, both R10-C OFF, no legacy server auth or WordPress option change. No manual-coupon priority change, and no live real Checkout POST/quoting acceptance has been conducted. No main merge or production transaction activation.

To close Issue #284: confirm CI, complete full disposable native Woo/browser shipping/coupon conflict coverage with actual configured vendor/zone/test FX, then propose an Owner-approved **Dev-only rc3 candidate** for a controlled OBSERVE/OFF installation and independent activation gate. Any actual OBSERVE exit or transaction-hook enable needs a distinct Owner approval, even if this source change passes CI.
