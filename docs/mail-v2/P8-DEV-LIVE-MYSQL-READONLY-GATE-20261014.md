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
Owner or authorized Hostinger hPanel administrator must create a fresh database distinct from the WordPress DB (example `u247343654_mailv2uat`, choose actual name per Hostinger rules), plus two separate users:
1. A temporary setup/testing account allowed to create P6 exact Outbox table and at most 100 synthetic tasks in **that database only**, no access to WordPress DB.
2. A dedicated runtime reader granted **SELECT only** on the new database, no write/grant/global permissions.

Use Hostinger hPanel/database tools; **do not share password in chat**. After creation, deploy the connection.php private file with correct actual socket, name, reader and password directly on authorized Dev account (0600). Verify no accidental php web access, create empty Outbox with P6 20-column DDL, seed strictly synthetic <=100 records, and backup. No credentials in GitHub.

Then allowlist PHP deploy after drift/SHA/CI/rollback preflight, and authenticated WordPress runtime smoke proving live COUNT, failure filtering, paging, a separate rejected UPDATE through read-only identity, actual readonly grants, behavior when DB is inaccessible, no SMTP/Worker. Visual iPhone 14 + desktop Owner UAT remains separate from source code UAT.

## Explicit non-goals
- No production/WWW MySQL changes, no direct use of WordPress existing DB, no SMTP/Worker or Action Scheduler registration, no actual customer email content, no real Woo notifications, no silent PR merge, no restart of any READY device.
- MariaDB 11.8 Hostinger and MySQL 8.4 US Mac are different engines/versions. Actual Dev database engine/schema must be independently checked, not inferred from prior tests.

**Current P8 status:** source implementation in progress; runtime UAT BLOCKED by independent database provisioning and SELECT-only account until verified.
