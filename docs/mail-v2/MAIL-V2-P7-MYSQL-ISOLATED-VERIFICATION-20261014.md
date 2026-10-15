# Mail OS V2 P7 — Isolated MySQL 8.4 persistence / race audit

**Authorization:** Owner authorized P7 isolated MySQL verification and read-only Dev Email OS integration until Owner UAT. Real SMTP, Worker, Action Scheduler execution, WWW/production DB, main merges and READY-device restarts remain forbidden.

## MySQL setup actually verified

- Hostinger Dev WP DB: read-only `SHOW GRANTS` shows **CREATE scoped to current WP DB**, no global CREATE DATABASE. It was **not** used to create a test table. WordPress MySQL Outbox remains absent.
- US Mac mini: Homebrew MySQL **8.4.11**, independently initialized datadir under `~/.byl-dev-canary/mail-v2-p7-mysql-20261014/data`, private Unix socket, `@@skip_networking=1`, no TCP binding and no machine restart.
- `tests/mail-v2-p7-isolated-mysql-runner.php` refuses any DB other than the hard-named synthetic lab, requires exact separate datadir and socket, isolated-test environment flag and version 8.4; creates **only** `p7_mail_synthetic_uat` on this isolated instance.
- P6 `ddl_proposal('p7_')` materialized only inside the independent test instance: exact 20 metadata-only columns, InnoDB, four required indexes, unique idempotency key. **No real recipients, subject, body or SMTP secrets.**
- Test results on US Mac: `MAIL_P7_ISOLATED_MYSQL_PASS assertions=128 rows=100 failures=60 unique=100 concurrent_CAS=1 skip_locked=PASS network=OFF`.
- Covered: table metadata manifest, unique key rejection, 100 insert/read, 60 failure statuses, transaction CAS rollback, row-level `FOR UPDATE SKIP LOCKED` on two sessions, two **independent PHP processes** racing CAS against same row (exactly one accepted), and a read-only transaction that cannot UPDATE.
- Initial runner used `pcntl_fork` and accidentally inherited parent PDO sockets; runner was fixed to `pcntl_exec` in the child, the **isolated synthetic database only** was reset after validating the affected schema/row count, and the full test reran successfully. No WP business DB touched.

## Verified MySQL-derived snapshot for Dev UAT

- Generated with `tests/mail-v2-p7-export-verified-snapshot.php` directly from isolated MySQL socket, SELECT ten P3-approved fields, 100 rows / 60 failure states.
- Snapshot JSON in `tests/fixtures/mail-v2-p7-mysql-verified-snapshot.json`; source is an immutable **point-in-time lab export**, not a live email queue. SHA256 `aa10d6ee5a12ecfdeb6ac580f464a6fe75acea1e89ce217464ba7ed7f15e04c4`. The dedicated offline harness pins this SHA and asserts P3 redaction/unique identity.
- A separate Dev-only WordPress integration branch may copy this **sanitized fixture** to an inaccessible private location and render a paged, read-only **MySQL 验证快照** tab, but MUST label it `非实时 / 非正式 Outbox`. No cross-machine DB tunnel, no credentials, no automated snapshot import from live customer records.

## Explicitly unverified for production

- MySQL tests used **100 synthetic rows**, not real provider traffic. No durable message-payload resolver exists yet.
- Dev WordPress cannot directly SELECT the isolated US Mac Unix-socket MySQL; website is a separate host. Reading a validated exported snapshot from Dev does NOT prove production MySQL read-only repository integration.
- Retention worker, append-only event logs, actual MySQL schema under production site, signed message-payload reference, SMTP/Worker, provider true quotas, Woo transactional fallback and transport cutover remain CLOSED.
- A real Dev MySQL integration requires **a genuinely separate MySQL database and dedicated read-only credential** accessible to the site, verified not equal to the WordPress business DB. Do not create tables inside the existing WordPress schema.
- Rollback: no deployment to WWW; isolated test data and validated artifacts preserved for Owner UAT. No automatic destructive database cleanup.

## GitHub gates

Mail architecture P7 Draft PR stacks on P6 Draft PR #100; three-menu Dev UI remains Draft PR #101 and earlier. Preserve both independent stacks until a separately reviewed integration/merge plan is approved. Owner UAT for the sanitized P7 snapshot is a visual check only; does not grant SMTP/Worker/Production activation.
