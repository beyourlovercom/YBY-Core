# Andy Core L3 Registry V1 — N5 final handoff

Date: 2026-10-23. Canonical sources: Draft PR #201 (Core-only), Draft PR #206 (Core/Commerce integration on Currency PR #199), and Issue #196.

## Completed
- Core filter `andy_core_l3_external_items` validates host page, required capability, route callback, ID/tab collision, safe URLs and insertion order.
- Commerce registers `currency-os` independently. Verified Dev menu has exactly one “多币种” link; Currency six tabs, FX and legacy ledger links work.
- Dev installed Core 2.2.11 and Commerce 1.0.8. Owner approved desktop visual UAT.
- WordPress/MySQL disposable-role CI verifies real WordPress menu registration for administrator, subscriber, editor and anonymous accounts with separate bootstraps. CI is not a substitute for Dev HTTP identity testing.
- Prior applicable workflows were 4/4 SUCCESS at PR #206 head `108d4338b256e5c9feb89b11e0e5dabedefb9c63`. Currency Preview was SKIPPED.

## Remaining hard gate
Signed-in restricted-role **Dev HTTP direct-link** negative test has not been completed. Required evidence: test role cannot see the Commerce L3 menu and cannot access `/wp-admin/admin.php?page=andy-commerce&tab=currency-os`; authorized Commerce manager retains access. Use only an explicitly authorized disposable test identity. Do not alter or reuse existing customer accounts. Do not claim PASS based on WP-CLI menu tests or unauthenticated login redirects.

## Integration and rollback
Do not deploy a Currency-only Commerce package without the PR #206 self-registration adapter or its menu entry can disappear. Prior Dev rollback packages and manifests are stored on China Mac at `/Users/a/AI/007/artifacts/andy-l3-n5-dev-20261023/`; these are plugin archives, not database snapshots.

Core and Commerce currently have independent Draft PRs. PR #206 is stacked on PR #199 and is **not** a safe main-merge shortcut. Before merging, recheck HEAD/CI and separate the Core-only interface from the Commerce consumer changes.

## Approval boundaries
No GitHub main merge, Stable publication, WWW update, real customer/order/payment actions, or READY-device restart is authorized by the prior visual `pass`. Future main merge and Stable release each require explicit Owner authorization.

**Status: N5 functionality/visual/isolated-role CI PASS; real Dev restricted-role HTTP gate OPEN. This document closes documentation/handoff only.**
