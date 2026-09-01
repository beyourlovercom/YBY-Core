# ANDY-CORE-ERP-REFERRAL-SNAPSHOT-ALL-STATUSES-001

## Purpose

Restore complete AffiliateWP Referral provider truth for ERP Connector snapshots.

## Baseline

- Repository: `beyourlovercom/YBY-Core`
- Base: `main@715b83e4df9591cc5d47eb73dc8f4df30d157054`
- Base tag: `v1.5.3`
- One WP / one branch / one worktree / one PR.

## Defect

AffiliateWP `get_referrals()` excludes internal `draft` and `failed` statuses when `status` is empty. The Connector therefore returned 2,700 referrals while the Local provider contained 3,299 rows; the missing 599 rows were exactly `failed=598` and `draft=1`.

## Contract fix

- Referral snapshots explicitly request `array_keys( affwp_get_referral_statuses( true ) )`.
- Defensive fallback includes `paid`, `unpaid`, `rejected`, `pending`, `draft`, `failed`.
- Affiliate, Coupon, Payout and mutation contracts are unchanged.
- Version number, tag, release and production deployment are out of scope.

## Validation

- Connector provider mapping harness: PASS.
- Connector snapshot harness: PASS.
- Connector foundation harness: PASS.
- Connector security harness: PASS.
- All 26 repository harnesses: PASS.

## Local WordPress UAT — 2026-09-01

- Windows Local source guard matched exact v1.5.3 connector hash before patch.
- UAT patch file hash matched PR #28 source exactly.
- With the patch, ERP received all `3,299` real AffiliateWP Referral rows.
- Status parity: paid `1,548`; unpaid `834`; failed `598`; rejected `223`; pending `95`; draft `1`.
- Payout ID present: `1,549`, matching raw provider data.
- Clean-window idempotency replay returned `3,299` rows again with no duplicates.
- `LOCAL_WORDPRESS_UAT = PASS`.
- All 26 repository harnesses remain PASS.
- After UAT, Windows Local connector was restored to exact v1.5.3 base hash and the temporary backup was deleted.

## Delivery gate

- Connector compatibility defect is fixed and Local UAT verified.
- Version/tag/release remain unchanged.
- Production deploy remains NOT RUN.
- PR may enter Ready-for-Review; merge still requires explicit Owner authorization.
