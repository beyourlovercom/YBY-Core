# Mail OS V2 P9-5/6 — Bounded Recovery & Source-only Integration Gate (2026-10-15)

## Evidence scope and non-interference

This document belongs to dedicated **Draft PR #118**, not the V2.2 or P8 branches.
The independently verified handoff heads before this incremental work were P9
`99a3f91b33281c2bd1884ce427b874f98fff8f17`, P8 #112
`803bfcc15f318cf6a6eda5e769080593c0eb5bf7`, V2.2 #116
`faec2467277216872487a42744b9fa4b7a2779cb`. Re-check current heads
and same-head CI **after each update**. The three branches are **diverged**;
do not merge/rebase/cherry-pick or infer that P8/V2.2 code is in P9.
P8 and V2.2 own WordPress admin/UI; the P9 branch changes **no admin,
CSS, Dev WordPress runtime, Commerce, Inquiry, real user or WWW files**.

## P9-5 implemented incremental source and offline tests

`inc/class-yby-mail-v2-p9-lab-writer.php` is still gated by the existing
CLI-only `MAIL_P9_LAB_ONLY=EXPLICIT_ISOLATED_SYNTHETIC_ONLY`, exact P9 database
and table manifest, `@@skip_networking=1`, unique principal `p9_writer`,
minimal SQL grants and synthetic `template_key`. No hooks, SMTP, external
transport calls, actual producer or Worker are registered.

- Existing expired ambiguous leased/sending task -> durable `uncertain`
  HOLD remains unchanged and has **no transition back to dispatch**.
- **New strictly lab-only** `record_mock_nontransmission()`: requires
  explicit synthetic pre-acceptance evidence marker, exact lease hash,
  expected state version and active lease; a timeout/unknown result is denied.
  Atomic MySQL UPDATE requires matched CAS version, attempt and max_attempts.
- Reuses P1 deterministic capped backoff with bounded retry count (fixture=3,
  contract maximum=5). `deferred` now reclaims only if due and
  `attempt<max_attempts`, all send-capability gates still OFF.
- When retries exhaust, `deferred + retry_exhausted +
  attempt=max_attempts` is an **inert logical failure bucket**. Both P2
  planner and P9 claim gate deny further dispatch. This is **not** a new
  physical dead-letter table; no schema change has been authorized.
- `synthetic_health()` yields only state and fixed diagnostic counts,
  including `uncertain` and `retry_exhausted`; explicitly returns
  `event_log_verified=false`. It is a **current-state snapshot**, not
  a historical append-only event source.
- `manual_replay_gate()` is an inert review proposal:
  `can_execute=false`, `requeue=false`, `smtp=false`. No owner
  approval/replay mutation has been implemented.

**Evidence:** `tests/mail-v2-p9-bounded-recovery-harness.php` covers
unknown proof, wrong token, expired lease, stale/replayed CAS,
three bounded attempts, jittered cooldown, re-claim denial after exhaustion,
redacted health and inert owner review. It runs in GitHub CI against a
**mock PDO**, not real private MySQL, Dev/WWW or transport. Prior real MySQL
100-row / two-process race / expired lease HOLD UAT is a **separate,
pre-existing result**, not re-run by this PR.

### Not yet proven / must not be silently called PASS

- Transactionally coupled append-only audit event history requires a
  separate isolation/DDL/grant decision. The current 20-column/4-index,
  one-table manifest must not be modified or called an event log.
- Real MySQL multi-process post-crash retry/exhaustion and durable
  audit/transaction reconstruction still require a new authorized
  **P9-only** lab execution. No P8 read-only grants are promoted.
- No complete Worker lifecycle, scheduler registration, Dev read/write
  account topology, real-mail provider quotas or transport handoff.
- As a result **P9-5 is PARTIAL/SOURCE-ONLY PASS**, not final full
  reliability or Dev Owner UAT PASS.

## P9-6 minimum integration (source-only, no deployment)

New `YBY_Mail_V2_P9_Integration_Plan::preview()` exposes a
strict `mail_p9_health_contract_v1` DTO **only from a synthetic verified
health snapshot**, with exactly 9 known state count keys and a 10,000-row
lab cap. PII, raw recipients, credentials, emails, inferred delivery
proof, event history and provider traffic are absent. It refuses:

- Any P9/P8/V2.2 current vs expected **exact 40-hex HEAD** mismatch;
- Unverified or inconsistent counts, extra keys, claimed audit history;
- SMTP, Worker, Dev approval or non-synthetic switches being enabled.

Return remains `source_only_plan`, with
`can_deploy=false`, `can_write=false`, `can_send=false`.
It **does not load P8 or V2.2 code**, configure a DB connection, register
WP hooks or add UI. Offline `tests/mail-v2-p9-dev-interface-harness.php`
verifies branch-drift rejection, count conservation, privacy, no duplicate
UI and every release gate fails closed.

### Cross-branch Dev integration decision (not authorized yet)

1. Finish V2.2 M6-C Dev deployment and M6-D Owner visual UAT
   **in its own workstream**. Keep Mail P8/three OS navigation source
   untouched while that happens.
2. Independently recheck #118, #112 and #116 **latest exact HEAD,
   CI and changed files**. Candidate composite Draft PR is a separate
   reviewed branch, not a direct merge of diverging drafts.
3. No new Mail OS menu, no duplicate CSS or PHP page. Reuse the existing
   V2.2 `admin/class-yby-three-os-admin.php` Email tabs and P8 read-only
   adapter `admin/class-yby-mail-v2-p8-readonly.php`; these are
   read-owned by their respective branches. A future approved
   integration adapter may only render sanitized state counts from
   a **separate approved P9 data source**.
4. Hostinger P8 current SELECT-like restricted principal stays intact.
   P9 Dev requires a **new writer database, separate writer/reader
   principals or isolated service**, tested grants, separate backups,
   no WordPress business DB changes, rollback plan and scoped owner
   authorization. US Mac mini private lab is **not Hostinger Dev**.
5. Code UAT -> isolated synthetic Dev Canary (after authorization) ->
   desktop and iPhone 14 **real screenshot** UAT. Fresh same-head CI
   and rollback evidence required before signoff. No WWW/Production.

## P9-7 SMTP Pro coexistence: review only

Existing WP Mail SMTP Pro continues to own existing live outbound traffic.
P9 does not hook `wp_mail`, WooCommerce order events, WordPress Action
Scheduler, or replace Tencent transport credentials. Future partial
cutover needs independently verified provider identities/quotas,
consent/suppression, failed/uncertain non-duplication rules,
observability/append-only proof, fail-back matrix, real dry-run evidence,
owner approval and scoped deployment. This document **does not approve
or perform any sending or production change**.

## Roadmap integrity

P9-0–4 previously evidenced isolated-lab technical PASS; P9-5 adds mock
bounded recovery but full durable audit/crash recovery remains OPEN;
P9-6 currently source-only contract and test, **Dev integration / Owner
UAT NOT STARTED**; P9-7 plan only. Only the real same-head CI outcome
may be reported as CI PASS. Keep Draft #118 unmerged and Worker OFF.
