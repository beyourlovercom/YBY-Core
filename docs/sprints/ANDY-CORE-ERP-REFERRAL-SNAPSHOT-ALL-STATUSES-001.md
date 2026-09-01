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
