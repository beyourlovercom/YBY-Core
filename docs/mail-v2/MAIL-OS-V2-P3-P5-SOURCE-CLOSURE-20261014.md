# Mail OS V2 — P3/P4/P5 Source-only Closure and Runtime Gates

**Owner instruction:** continue priority development of SMTP queue/sender, sorting, rate limiting, and isolated pressure tests; no approval for real SMTP transmissions, Worker, database mutation, deployment of unmerged P1/P2 code to WordPress, WWW or main merge.

## Source baseline

- P1 Draft PR #91 — metadata-only outbox state/schema/CAS/idempotency, code PASS.
- P2 Draft PR #92 — priority, sender tokens, cooldown, critical reserve, synthetic Action Scheduler previews, code PASS.
- P3 Draft PR #95 — capability/nonce attestation and strict data minimization for paginated synthetic queue/failure/log views. A real source connector and WordPress admin controller are NOT yet built.
- P4 Draft PR #96 — sender identity policy and provider-outcome classifier **only in synthetic mode**. Existing `YBY_Mail_Transport` owns the Tencent enterprise mailbox adapter and remains unchanged. No SMTP credentials are read.
- P5 Draft PR (this branch) — 10,000 opaque synthetic items across ten independently planned windows, 1,000 items maximum to P2 at a time; global priority ordering means P0 is processed before low-priority backlog. Uses P2 `plan()` and inert `preview_actions()`; never calls Action Scheduler to schedule actions. **P5 repeated windows share a single sender-token and in-flight budget at the same synthetic instant**; there is no invented refill. The corrected baseline permits 50 `system_tx` + 45 `newsletter_bulk` claims total from 10,000 candidates, not 455. Additional records remain queued in this rehearsal; a future real dispatcher needs persisted refill and next-batch scheduling before these would be eligible.

## Explicit caveats

A dry-run claim is NOT a persisted job, executed SMTP attempt, or provider accepted delivery. The simulated sender budgets are synthetic. The account-specific Tencent quota is **unknown**, and must be verified separately without reading or exposing credentials. Success of P3/P4/P5 tests does not authorize activation or an SMTP Pro replacement.

The current independent Three-OS menu, Newsletter M6/M7 and its Dev deployment are on a separate Draft PR #94, which stacks on #93. **Do not combine these branches casually or deploy the source-only mail simulation to Dev**. A separately approved integration PR/rebase and isolated disposable WP DB are required to connect real Outbox UI and transport routing.

## Tests

Run:
```
php tests/mail-v2-p3-visibility-harness.php
php tests/mail-v2-p4-routing-harness.php
php tests/mail-v2-p5-stress-harness.php
```

Also rerun P1/P2 offline harness and GitHub CI for the exact HEAD. Every generated fixture lacks address, subject, body, tokens, SMTP secrets or Woo IDs. Fail-closed inputs include missing capability/nonce, uncertain provider outcome, wrong sender identity, unverified quotas, cooldown, duplicate opaque key and oversized/non-synthetic fixture.

## Final gates

1. Code Owner UAT for P3/P4/P5: CI and offline test evidence plus scoped source review.
2. Staged WordPress admin integration with verified read-only **real** Outbox source only after additive schema design, backup/rollback approval and site isolation.
3. Isolated disposable Dev database, queue insert authorization and fake SMTP interception only. Never use real recipients or transport for synthetic tests.
4. Confirm provider quotas/identities and Woo transactional safety, independent rollback, rate fairness over time, exact job version/CAS lease and bounce handling.
5. Separate explicit Owner approval **before** real Worker, production DB schema or credential injection, real SMTP send, WWW deploy, SMTP Pro replacement or PR merge.

All unknowns must remain clearly not-completed in UI. Do not fabricate data or claim delivery.
