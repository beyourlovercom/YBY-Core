# Mail OS V2 P9 — Independent Branch Final Closure and Deferred Release Gates
Date: 2026-10-15

## Decision

**Close the authorized P9 independent engineering workstream as SOURCE/LAB COMPLETE, not as a live-Mail or Hostinger Dev deployment.** Keep composite PR #120, P9 writer PR #118, P8 PR #112 and Andy Core V2.2 PR #116 **Draft, unmerged and independently owned**. No Dev/WWW publishing, payment, SMTP/Worker, original database/schema, P8 permissions, live messages, customer/recipient data or READY device restart is authorized by this closure.

GitHub main remains the only future integration target; Draft branches are not release truth. Source-only composite work has its own exact-head CI evidence.

## Completed milestones and objective evidence

| Stage | Truth | Technical proof |
| --- | --- | --- |
| P9-0 through P9-4 | ISOLATED LAB PASS | Dedicated MySQL8 Unix-socket synthetic writer/reader, strict P6 20 columns/4 indexes, CAS, idempotency, mock send only, no WP runtime |
| P9-5 recovery | ISOLATED LAB PASS | Two-PHP-process race 1 winner/1 loser, bounded 3-attempt mock pre-acceptance backoff, exhausted HOLD, expired ambiguous lease -> uncertain HOLD, no replay authorization |
| P9-5 100-row baseline | BACKUP HASH PASS | SHA256 `04a9bef5a5a96b18b54c7bb336f5e00217fcba1b33fd87ab4c60601b18d19e47` |
| P9-5 102-row cold recovery | ISOLATED RESTORE PASS | Private cold archive SHA256 `1fd73f8915f0b05b38c2c99f22c739ff182121d254b3a4bdcedf0e489b6374f7`, separate socket/database cold restoration, SELECT-only verified 102 records, restored instance shut down |
| P9-5 append-only event ledger | SOURCE/SQLITE SIMULATION ONLY | 24 SQLite transaction fault assertions PASS; inert audit event manifest; no live MySQL audit table/atomic event writer |
| P9-6 composite source | SOURCE PASS | V2.2 original bootstrap, 3-OS admin, P8 reader preserved exact blob, 47 boot-safety assertions, no mail/worker/autoload hook; 23 grants parser tests, 24 Dev topology tests |
| P9-6 real isolated aggregate bridge | ISOLATED LAB PASS | Fixed P9 reader actual MySQL8 SELECT-only grants, 102 rows: queued 98, leased 1, uncertain 2, deferred retry-exhausted 1; 18 bridge assertions; all send/write/deploy claims false |
| P9-6 Hostinger Dev writer / UI UAT | BLOCKED / NOT EXECUTED | Hostinger Cloud Startup hPanel inspection: one combined DB+user creation and remote IP allowlist, no demonstrated independent same-DB scoped Reader/Writer control; no Dev deploy/desktop or iPhone14 live screenshot |
| P9-7 coexistence | SOURCE PLAN PASS | 26 regression assertions, preserve incumbent WP Mail SMTP Pro and existing mail routes; no production canary, no cutover |

## Verified Hostinger evidence / no unauthorized workaround

Owner-approved read-only hPanel navigation verified `dev.beyourlover.com` is on Cloud Startup (plan shown expires 2030-05-26). Database Management displays **Create a New MySQL Database And Database User** and existing P8 synthetic database, but no separate second DB identity control. Remote MySQL allows an **IP** and **database** selector, with **Any Host** unchecked; IP allowlisting is NOT a new user or proof of private networking. Do not reuse one RW user as Reader or promote P8's existing restricted user; do not choose public wildcard `%`, purchase a VPS or modify production for the sake of marking PASS.

## Risk acceptance boundaries

1. Ambiguous/provider-acceptance-unknown `uncertain` messages are HOLD. No automatic retry, real transmission or replay path is offered.
2. `retry_exhausted` is an inert *subset* of `deferred`, not a physical dead-letter queue or delivery failure guarantee.
3. Inert `P9-5` audit hash and SQLite atomicity harness are **not** a deployed MySQL append-only event ledger. Implementing transactionally coupled ledger requires new approved schema, restricted principal, independent restore and real MySQL tests.
4. P9 lab's real MySQL8 read-only aggregate is **not** the Hostinger MariaDB Dev connector or visual Owner UAT. Existing V2.2/P8 UI owns any future display; no duplicate WordPress menu, CSS, or override.
5. Real SMTP Pro coexistence and rollback plan is **not** a cutover. Existing Woo/inquiry/newsletter mail routes retain authority.
6. Composite Draft PR #120 is based on provisional V2.2 #116, NOT main. Neither is authorized to merge into main or deploy to Dev/WWW. An eventual main integration must be independently reconciled against current main and approved at exact HEAD.

## Reopen gates (not work left running in this branch)

A. **Operational append-only MySQL V2 audit:** separately authorize a new *isolated synthetic-only* schema/tables/role and backup/rollback on a dedicated lab or suitable Dev database. Verify 1:1 CAS+event transactional integrity under injected failures and multi-process replay. Never silently expand the P8 one-table schema.

B. **Hostinger Dev Writer + screenshots:** provide an approved truly private multirole Dev database and network topology, independent P9 writer SELECT/INSERT/UPDATE and reader SELECT, and explicit Dev synthetic-write permission. Create reviewed integration adapter/Dev-only allowlist; verify independent PHP grant/schema guard; then take real desktop and iPhone14 Dev screenshot UAT. Hostinger Cloud Startup UI findings do not establish those prerequisites.

C. **SMTP cutover:** a separate Owner-approved route/transport/consent/failure/idempotency/rollback exercise. No live mail or WP Mail SMTP Pro removal as part of P9 closure.

## Branch closure acceptance

**Engineer/source closure may be recorded if exact-head PR #120 CI 4/4 SUCCESS and this closure document is committed with Mail-only diff**, and PR #118 / #112 / #116 remain Draft and unchanged. This document does NOT attest to real Dev Owner PASS, production delivery reliability or operational append-only MySQL history. Preserve the cold backup privately; do not export private MySQL state to GitHub.

The P9 source engineering workstream is **STAGED CLOSED**. Reopen only under A/B/C exact-scope approval; unrelated V2.2 and Mail/SMTP lanes continue independently.
