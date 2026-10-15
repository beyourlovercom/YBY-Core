# Mail OS V2 P9 — Independent Synthetic MySQL Lab Technical Closure (2026-10-15)

## Scope and status

**P9-0 architecture + P9-1/2/3/4/5 SYNTHETIC LAB technical UAT PASS; P9 integrated Dev Owner UAT NOT STARTED.** Owner directed Mail OS to continue separately from Andy Core V2.2. This branch is **Draft** [PR #118](https://github.com/beyourlovercom/YBY-Core/pull/118), based exclusively on Mail P7 architecture [#108](https://github.com/beyourlovercom/YBY-Core/pull/108). V2.2 [#116](https://github.com/beyourlovercom/YBY-Core/pull/116) and P8 Dev Owner-PASS UI [#112](https://github.com/beyourlovercom/YBY-Core/pull/112) remain untouched. No new admin menu, CSS, WordPress bootstrap, plugin deploy or real mail hooks are included.

## Executed evidence — independent US Mac mini private lab, not Hostinger Dev

- Private P9 MySQL **8.4.11** instance under `~/.byl-dev-canary/mail-v2-p9-mysql-20261015`; its own **datadir and Unix socket**, `@@skip_networking=1`, mysqlx disabled. **P7 data directory remains intact and its MySQL daemon was not started.** No READY device restart.
- New database **`p9_mail_synthetic_uat`**, and **two separate DB principals** `p9_writer` (SELECT/INSERT/UPDATE **only**, no DDL/DELETE) and `p9_reader` (SELECT only), each scoped only to the P9 database, with randomized private passwords written once to a 0600 private file and **never surfaced in chat, logs or GitHub**. P6 canonical **20 metadata columns / 4 indexes**, InnoDB, ASCII binary collation.
- `tests/mail-v2-p9-real-mysql-runner.php`: **100 synthetic opaque tasks inserted**, all idempotency keys unique. Exact replay => duplicate/no insert; same key with divergent event => blocked. P9 reader's INSERT, UPDATE and DELETE explicitly **denied** by MySQL. Two separately authenticated PDO writer connections: stale CAS replay blocked, one lease state-version increment.
- `tests/mail-v2-p9-two-process-cas-race.php`: **two independently exec'd PHP processes** waited on one barrier and raced a single synthetic queued row. **Exactly one claimed and one not_claimed**, one state-version increment, 100 rows retained. No PDO socket inheritance, no background worker or mail.
- `tests/mail-v2-p9-mysql-p2-dryrun.php`: read **100** actual P9 MySQL rows with `p9_reader`, reuse existing P2 planner; 50 **proposed/inert** claims (24 critical, 6 account, 20 marketing) under synthetic system_tx=30, newsletter_bulk=20 budgets. Marketing tokens=0 => 30 system-only proposals; no borrowed budget. 50 Action Scheduler **preview** items, all scheduled=false, executed=false.
- `tests/mail-v2-p9-mock-transport-recovery-harness.php`: **33 offline assertions PASS** for existing P4 routing/outcome classification and P2 recovery, no cross-mailbox fallback, provider unknown HOLD, no actual sends.
- `tests/mail-v2-p9-real-mysql-expired-lease.php`: synthetic lease expiry with wrong token, early expiry and replay all **DENIED**; verified expiration transitions leased→**uncertain** (version 2, `acceptance_unknown`, lease cleared), no automatic requeue/SMTP, 100 rows retained.
- `tests/mail-v2-p9-lab-writer-harness.php`: **23 offline PDO mock assertions PASS**; wrong database/P7 datadir, network-on, root/overprivileged role, extra table, schema drift and non-synthetic payload fail-closed.
- Reused existing P5 **10,000 fully synthetic events** offline load test and P2 budget conservation, not 10,000 rows inserted into this lab. No fabricated provider throughput.
- Private lab backup `~/.byl-dev-canary/mail-v2-p9-mysql-20261015/artifacts/p9-synthetic-outbox-100.json`, source SELECT-only `p9_reader`, **100 synthetic records**, final state 98 queued / 1 leased / 1 uncertain, canonical CREATE TABLE included, mode 0600, validated read-back. SHA256 `04a9bef5a5a96b18b54c7bb336f5e00217fcba1b33fd87ab4c60601b18d19e47`.
- Private lab MySQL service **cleanly shut down** after backup. Datadir, passwords and evidence retained for later authorized UAT. US Mac mini READY machine was NOT restarted.

## GitHub exact-head gates

Run `GitHub CI 4/4 SUCCESS` for the final PR #118 HEAD after this doc is committed; **do not assume earlier CI implies latest HEAD**. Initial prior regression evidence included 23 P9 writer assertions, 33 mock transport/recovery, P2 63, P4 43, P5 34 plus automatic PHP lint. The real MySQL tests above were run on the isolated US Mac, NOT by CI and NOT on Dev WordPress. Preserve this distinction.

## Critical remaining limits / next gates

- **P9-5 runtime recovery not complete:** the lab has a conservative persisted `uncertain` HOLD transition, **not** a complete failed-send/dead-letter/append-only event log, recovery worker or real delivery proof.
- **P9-6 Dev integrated Owner UAT not complete:** P9 source is not integrated with the separately accepted P8 Dev read-only interface and must not overwrite Andy Core V2.2 M6-C/M6-D or #116's adapter. No synthetic live Dev background Worker has been activated.
- **P9-7 actual SMTP/WWW cutover not authorized:** real provider quotas unknown, no customer payload store or consent/suppression ingress, no official end-to-end delivery evidence, no actual Tencent or WP Mail SMTP Pro changes. Keep Woo and WWW current mail paths unchanged.
- Future integration requires an approved separate writer topology usable by Dev, cross-branch exact-HEAD composite diff after V2.2 visual acceptance, and owner review. **Do not elevate P8's Hostinger single-user read-only account, write into WordPress business DB or create duplicate WordPress UI.**
- **PR #118 stays OPEN/DRAFT/UNMERGED; no main merge or production deploy without a new explicit Owner gate.** This milestone's UAT is *isolated engineering lab*, not a production sending acceptance.
