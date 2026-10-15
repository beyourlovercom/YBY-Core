# Email OS V2 P8 — REAL isolated Dev MySQL readonly connector (PROVISIONING GATE)

## Owner authorization and actual platform findings
Owner authorized P8 independent Dev MySQL database creation/configuration, no SMTP/Worker, WWW/production, WP business DB writes, READY restart, or PR merge.

GitHub parent: Draft P7 Dev snapshot PR #109 exact HEAD `786f3e2dc1712d1907d9e0427cde8c9030b9cdc2` (unmerged). The separate P7 MySQL source Draft PR #108 confirmed on US Mac that MySQL 8.4 InnoDB + CAS + SKIP LOCKED works, but that database was never live-connected to Hostinger.

**Hostinger constraints verified:**
- Dev WordPress account's `SHOW GRANTS` is database-scoped, **no global CREATE DATABASE**.
- `/usr/local/sbin/mariadbd` is installed, but private `mariadb-install-db` cannot locate its matching `fill_help_tables.sql`, and home-directory symbolic link creation is refused. Do NOT bypass provider restrictions by copying system binaries, abusing WordPress DB, or opening MySQL over public TCP.
- There is NO separate provisioned P8 Hostinger MySQL database or dedicated `SELECT`-only account in evidence. Until owner/hPanel makes this available, **P8 live Dev MySQL integration is not operational**.

## What the Draft PR implements
- New `admin/class-yby-mail-v2-p8-readonly.php`: strict `dev.beyourlover.com` + admin + `manage_options` + `andy_core_mail_view` + nonce.
- Private config file `/home/u247343654/.byl-dev-canary/mail-v2-p8-dev-readonly/connection.php` with 0600 permissions, in a 0700 directory, **never committed**. Return keys in this order: `dbname`, `user`, `password`, `socket`, `table`. No password may appear in PR, chat, logs, or GitHub Actions.
- Only Unix-socket connections from the allowlist (`/var/lib/mysql/mysql.sock`, `/run/mysqld/mysqld.sock`); the database name MUST differ from WordPress `DB_NAME`; allowlisted table is `p8_yby_mail_v2_outbox`.
- The viewer requires an independently provisioned **SELECT-only** account restricted to the isolated database; check `SHOW GRANTS` with no DML/DDL/GRANT or global SELECT privilege. `SET SESSION TRANSACTION READ ONLY` adds defense in depth.
- Before reading data, compare existing table schema in `INFORMATION_SCHEMA`: P6 exact 20 columns/types/nullability, InnoDB/ASCII binary collation, PK, unique idempotency key, state/priority/due and sender/state/due indexes. Any drift returns blocked.
- Queries read 10 non-sensitive P3 fields, independently count full state totals, use prepared `LIMIT/OFFSET`, cap to 100 synthetic Dev UAT tasks and show clear `独立 Dev MySQL · 实时只读` banner. No body, recipient, token, SMTP response, email server credentials or queue mutations exposed. No background polling.
- The canonical Dev-only Email OS menu shows `独立 MySQL 实时只读` only when its private config file exists. Existing P6/P7 snapshot tabs stay unchanged. No WWW links.
- Exact-head source CI and standalone permission tests are mandatory before any code-only deployment. This PR **does not create** the DB, user, schema or queue entries.

## Next isolated database provisioning gate
Hostinger shared hosting officially allows **one non-system database user per database**. The operator must create a fresh DB assigned to Dev and a single scoped account (for example `u247343654_mailv2uat` with Hostinger-managed name prefix), distinct from WordPress `DB_NAME`.

**Two-phase single-account permission workflow:**
1. Create the new DB/account in hPanel; it initially has privileges scoped to this **new DB only**. No other website's database can be modified.
2. Provision the exact P6 20-column `p8_yby_mail_v2_outbox` schema and at most 100 synthetic tasks using this account; verify unique keys/CAS and an independent SQL backup. Stop on drift or nonempty schema.
3. In hPanel → Databases Management → new DB ⋮ → Change Permissions, **revoke everything except SELECT**, Save/Update. This is a MUST-HAVE final gate; do not leave INSERT/CREATE or global privileges.
4. Set up the private Dev `connection.php` (0600, outside public_html) using this same DB user, with credentials entered only on the authorized server. Run P8 live readonly `SHOW GRANTS` verifier and a denied UPDATE smoke before enabling Owner UAT.

Do not share DB passwords in chat or GitHub. Hostinger's database-account model does not support the originally drafted two-user plan on this hosting tier. Creation and permission change require hPanel/API authority unavailable to the current Dev WordPress SSH account.

Then allowlist PHP deploy after drift/SHA/CI/rollback preflight, and authenticated WordPress runtime smoke proving live COUNT, failure filtering, paging, a separate rejected UPDATE through read-only identity, actual readonly grants, behavior when DB is inaccessible, no SMTP/Worker. Visual iPhone 14 + desktop Owner UAT remains separate from source code UAT.

## Explicit non-goals
- No production/WWW MySQL changes, no direct use of WordPress existing DB, no SMTP/Worker or Action Scheduler registration, no actual customer email content, no real Woo notifications, no silent PR merge, no restart of any READY device.
- MariaDB 11.8 Hostinger and MySQL 8.4 US Mac are different engines/versions. Actual Dev database engine/schema must be independently checked, not inferred from prior tests.

**Current P8 status:** source implementation in progress; runtime UAT BLOCKED by independent database provisioning and SELECT-only account until verified.


## P8 credential handoff without SSH

WordPress Dev-only credentials setup form: `/wp-admin/admin.php?page=andy-core-email&tab=p8_setup`.
Only an administrator with both `manage_options` and `andy_core_mail_view` on the exact dev.beyourlover.com site can access it. The POST endpoint enforces WordPress nonce and TLS.

Owner enters FULL hPanel-prefixed database name, user and password directly into this Dev HTTPS form, **never into chat or GitHub**. Server validates that the new database is not WordPress `DB_NAME`, that SHOW GRANTS includes CREATE/INSERT scoped only to this new schema and not global privileges, and that the target database is empty. A failure writes no configuration.

On successful verification, exclusive file creation (`fopen x`) stores a single mode-0600 private config outside public_html. No submitted values or errors are echoed in output or redirect. This form does not execute DDL, seed data, send mail or start workers.

Next the already-authorized private CLI synthetic setup script may create the exact P6 metadata-only table and up to 100 synthetic records in the NEW EMPTY database only, after an isolated backup plan. The owner then uses Hostinger hPanel Change Permissions to revoke everything except SELECT for the one database account. The P8 live reader refuses to display data until SELECT-only grants and exact schema match.

Unavailability of private credentials remains a required owner-entry gate; never fall back to WordPress business database.


## P8 Hostinger MariaDB permission outcome after Owner setup

After Owner entered the private P8 config through the Dev admin form and confirmed completion, old DC (Windows SSH) verified from the actual Hostinger DB connection that the new database differs from WordPress DB_NAME, has only its own scoped setup grants, and was initially empty. The approved P8 CLI initializer created the P6 exact 20-column Outbox with 4 indexes and inserted exactly 100 synthetic opaque records (60 deferred/permanent_failed/uncertain). CAS stale-claim and transaction rollback tests PASS. No Woo/WordPress DB was modified.

Private JSON backup of the synthetic-only Outbox and actual CREATE TABLE was independently read-back verified with SHA256 `03631ac586e8ef93cea7872de87b6c2f238e7943d18e16e7b406e2a5b8f1cbcf`, mode 0600 outside public_html. No secret values, real recipients, SMTP credentials or business events are in the backup.

Owner-approved old DC Chrome Hostinger hPanel existing Management session: located exactly one `mailv2uat` DB row, opened **Change Permissions**, unchecked all 17 permissions except **Select** (verified form state), clicked Update. However MariaDB `SHOW GRANTS` *after* update still reported `USAGE ON *.*` and scoped `SELECT, DELETE HISTORY, SHOW CREATE ROUTINE ON new_db.*`. The latter two are Hostinger/MariaDB-specific extras not separately present in the hPanel 18-checkbox UI. **Do not falsely state database account is literal SELECT-only.**

The P8 exceptional laboratory compatibility path accepts exactly that bundled scoped grant and no other grant *only on dev.beyourlover.com*, provided the isolated schema contains **exactly one** Outbox table and its SHOW CREATE TABLE is not system-versioned. DELETE HISTORY could alter a system-versioned table; if any other table appears, the entire live view refuses to run. Independent negative SQL probes using the new DB identity confirmed **UPDATE, INSERT, DELETE all denied by MariaDB**, with 100 synthetic rows retained. This exception is NOT for WWW, production, generalized customer DBs or databases with future versioned tables; should not be generalized into transport/worker permissions.

Live read-only adapter still has transaction READ ONLY, strict 20-field/index manifest, 10 metadata columns, admin+mail capabilities, nonce, 100 synthetic maximum. No mail, no Action Scheduler jobs and no queue writes. The Owner visual UAT must be for isolated synthetic Outbox only; cannot certify SMTP Pro replacement or real delivery.
