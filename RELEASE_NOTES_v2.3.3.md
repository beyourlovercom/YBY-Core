# Andy Core 2.3.3 — P0 YBY Bricks collision hotfix (candidate only)

## Purpose
Fix the confirmed Andy Core 2.3.2 production outage on the YBY irrigation Bricks 2.3.7 child theme and the independently reproduced Connector dbDelta migration error.

## Fixes
1. Preserve existing site-owned Bricks element yby-page-inquiry in a child theme. Defer Core registration and use an independent PHP class name if Core must register fallback.
2. Reformat the payout binding and payout claim Connector SQL so WordPress dbDelta can run idempotently on upgrade from 2.2.10.
3. Preserve existing functionality: Login OS, Inquiry OS, Case ID, notification outbox, Module OS, signed updater. No intentional new schema fields.

## Release safety
- Candidate only. No production publication or installation authorized.
- Never replace or mutate the immutable 2.3.2 package.
- YBY production remains at 2.2.10 until approved.
- Validation required on actual YBY Bricks parent+child, MySQL upgrade, synthetic inquiry attribution and signed SHA256/Ed25519 before any future rollout.

- The existing yby_lead_notifications outbox must be preserved without truncation or backfill changes.
