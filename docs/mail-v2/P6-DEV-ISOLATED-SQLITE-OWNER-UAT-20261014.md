# Email OS V2 P6 — Dev-only isolated SQLite Preview / Owner UAT

**Scope and Owner approval:** Isolated disposable Dev database, up to 100 synthetic Outbox records, backup and rollback rehearsal, readonly Email OS queue and failure views, until Owner UAT. NO real email, SMTP, Action Scheduler jobs, Worker, production/WWW changes, WordPress business DB writes, merges or READY-device restarts.

## Branch safety
- Integration branch rooted at Three-OS UI M6/M7 Draft PR #94, `75e7510c231fc497916b1e319ad75612bae3467f`, **NOT main**.
- Mail architecture source P1–P6 is a separate stack ending Draft PR #100, `5d1f1082fd8af09fb88313032ffce2bab4ca1d67`, still UNMERGED.
- This Dev test integrates only a fixed-schema synthetic viewer. It does not silently merge either stack.
- Dev-only plugin deployment must preserve the three preexisting Business OS register lines in the site's `inc/class-yby-core.php` (Dev source overlay). Only allowlisted files and verified rollback.

## Independent database
- Private **SQLite database** `~/.byl-dev-canary/mail-v2-p6-uat-20261014/outbox.sqlite` with directory mode 0700 and file mode 0600, outside `public_html`.
- Created by explicit gated fixture script; **fails if target exists** rather than overwriting. Max 100 deterministic synthetic records; no customer IDs, email addresses, subject, body, SMTP connection or payload.
- 20 fields in the same order as P6 metadata-only schema; SQLite equivalent constraints/indexes. Unique `idempotency_key`, status/priority/due and sender/status/due indexes.
- CAS version and duplicate idempotency conflict checked via savepoint with rollback; independent byte-for-byte rollback copy validated with read-only reopen; no changes to WordPress MySQL at any point.
- Engine qualification: **SQLite is not MySQL**. SQLite tests can validate synthetic UI, uniqueness and optimistic CAS in the laboratory; MySQL-specific DDL, locking, isolation, concurrent claims and collation still need their own independently approved and isolated MySQL test. Never label that gate PASS based on SQLite.

## WordPress preview gate
- ONLY when `site_url()` host is exactly `dev.beyourlover.com` and `is_admin()`, plus `manage_options`, `andy_core_mail_view`, nonce validated via `wp_verify_nonce`. No broad role grants.
- Email OS gains **队列（Dev 预览）** and **失败处理（Dev 预览）** tabs, each nonce-bearing. Existing Email OS tabs and functionality untouched.
- UI explicitly warns **DEV 隔离测试数据**, not actual business queue or delivery. Only opaque short ID, channel, sender alias, state, priority, attempts, synthetic due integer, allowlisted code and version are shown. No email, subject, payload, headers or SMTP response.
- SQLite3 is opened with `SQLITE3_OPEN_READONLY`, rechecks ordered 20-field schema and unique index, stops if table record count not in 1..100, no send/retry/delete/write button.
- No separate event log UI: Outbox current state cannot represent append-only event history.

## UAT gates
1. GitHub exact head source CI and dedicated offline security harness PASS, Draft PR unmerged.
2. Dev site/SSH and target plugin exact-file drift precheck. Backup changed originals with SHA256 rollback pointer.
3. Create private one-time SQLite fixture; verify 100 synthetic rows, unique key, CAS and rollback backup, read-only query. No WordPress DB writes.
4. Dev-only allowlisted plugin update, PHP lint and authenticated readonly WP-CLI smoke. Verify queue/failure page renders, nonce/user role gate, and no WWW exposure.
5. Desktop and iPhone 14 Owner visual UAT. If not yet performed, report **Owner UAT pending** and provide direct links.
6. Revert affected plugin files from private rollback package if smoke fails. Never drop a production database; no automatic deletion of the fixture to preserve Owner UAT evidence.

**Non-goals:** full MySQL schema install, Worker, real SMTP and quota tests, production Outbox, queue event audit, message payload storage, staff sends, automatically merging Draft PRs.
