# Email OS V2 P7 — Verified MySQL Test Snapshot UAT (Dev-only)

Owner authorized independent real MySQL verification and Dev read-only WordPress UAT; no real SMTP/Worker, WordPress business database writes, WWW or PR merge.

## Evidence separation
- **MySQL truth:** Draft PR #108 (based on Mail P6 #100) tested a standalone MySQL 8.4.11 Unix-socket-only instance on US Mac mini. Actual 128-assertion MySQL runner PASS: 100 synthetic tasks, 60 failures, unique idempotency, CAS and process-concurrent stale claim denial, SKIP LOCKED, read-only transaction. Its `tests/fixtures/mail-v2-p7-mysql-verified-snapshot.json` is exported by SQL from that actual isolated lab and has SHA256 `aa10d6ee5a12ecfdeb6ac580f464a6fe75acea1e89ce217464ba7ed7f15e04c4`.
- **Dev UI truth:** This PR stacks on P6 Dev SQLite Owner PASS Draft #101, keeps all P6 tabs and introduces **MySQL 验证快照**, a separate, explicitly **non-real-time / synthetic** view. It is NOT a live MySQL bridge. Source PR #108 is not merged into this PR.
- **Hostinger MySQL:** Existing WordPress MySQL user has only database-scoped CREATE privileges. Its business DB is never used as P7 Outbox; no real Outbox table created.

## Dev-only media / owner UAT protocol
- Copy only the verified synthetic snapshot from GitHub source #108 into private `/home/u247343654/.byl-dev-canary/mail-v2-p7-verified-snapshot-20261014/p7-mysql-verified-snapshot.json`, outside web root; make directory 0700, file 0600.
- Dev UI opens snapshot only for site `dev.beyourlover.com`, is_admin(), `manage_options`, `andy_core_mail_view`, and valid WordPress nonce.
- Before rendering, reject missing/symlink/out-of-bounds file, filesize outside bounded limits, nonmatching pinned SHA256, wrong exact 9 top-level keys, altered counters, unexpected row fields, malformed IDs, unsupported statuses, noninteger counters or actual email content fields.
- Present at most 20 metadata-only records per page; allow all 100 vs 60 failed/uncertain test rows. No POST buttons, SMTP, Worker, bulk send, retry, cancellation or DB writes. Prominent banner: **MySQL 8.4 验证快照；非实时、非正式 Outbox**.
- The true MySQL instance remains network-isolated on US Mac; Dev does not initiate any SQL connection to it. SHA pinned fixtures protect from accidental substitution and document reproducibility.

## Release isolation & rollback
- Deploy only two backend allowlisted PHP files over P6 Dev Owner-PASS baseline: `admin/class-yby-mail-v2-p6-dev-preview.php`, `admin/class-yby-three-os-admin.php`. **No Core overlay changes.**
- Check PR #101 exact-baseline file drift before deploy, ZIP SHA and PHP lint, backup both originals with rollback SHA. Only after exact-head CI PASS.
- After deploy authenticated WordPress Dev-only read-only WP CLI smoke for snapshot all/failed filter, existing P6 Queue/Failure pages, caps/nonce/WWW gating and Business OS retention.
- Owner UAT desktop+iPhone14 after smoke; stop there for visual PASS/bug feedback. Future **live MySQL read-only WordPress adapter** requires a separately provisioned Dev isolated database with dedicated read-only credentials and MySQL schema verification; not authorized to alter WP business DB. No WWW, SMTP or Worker until separate gate.
