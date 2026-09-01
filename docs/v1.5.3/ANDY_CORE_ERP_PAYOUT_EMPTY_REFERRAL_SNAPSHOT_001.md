# ANDY-CORE-ERP-PAYOUT-EMPTY-REFERRAL-SNAPSHOT-001

Status: IN_PROGRESS
Base: `YBY-Core/main@1b5d1b2ad13d26c15cf47db259351083c418230e`
Branch: `feature/andy-core-erp-payout-empty-referral-snapshot-001`

## Trigger

ERP M1.4E read-only Live Provider UAT found exact payout-count parity but one referral-link mismatch.

Provider database truth:
- 1,550 payouts
- 1,549 Referral → payout links
- exactly one historical zero-Referral payout: #948

Connector `/snapshot/payouts` returned payout #948 with `referral_ids: [null]` instead of an empty list.

No provider mutation was performed.## Root Cause

AffiliateWP `affwp_get_payout_referrals()` may yield false/empty entries for a payout with no Referral association. Andy Core snapshot mapping appended the unresolved entry as `null`.

Andy Core already had `payout_referral_ids()` which canonicalizes IDs, removes zero/null values, sorts IDs, and is also used by payout matching.

## Change

- Route payout snapshot mapping through `payout_referral_ids()`.
- Route payout reconciliation initial/final referral-ID comparisons through the same canonical helper.
- Add a provider regression fixture where a historical zero-Referral payout returns a false helper entry and must serialize as `referral_ids: []`.

## Gate

- Connector harnesses: 9 / 9 PASS.
- `git diff --check`: PASS.
- Version bump: NO.
- Tag / release: NO.
- Production deploy: NO.
- Real payout mutation: NO.## Live Root-Cause Refinement

Local AffiliateWP payout #948 has a stale raw relation containing Referral ID 1617, while Referral #1617 no longer exists. `affwp_get_payout_referrals()` therefore hydrates that relation as `[false]`.

Canonical payout Referral IDs must prefer AffiliateWP's hydrated helper and filter non-existent Referral entities. Raw payout relation IDs are only a fallback when the official helper is unavailable. This preserves historical payout #948 while correctly representing its current Referral entity set as empty.
