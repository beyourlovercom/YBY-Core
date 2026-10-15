# P9-6 Dev Writer topology gate — single-account hosting limitation (2026-10-15)

**Repository:** `beyourlovercom/YBY-Core`; this is for the separate source-only composite Draft PR #120, NOT a deployment plan. Baseline V2.2 #116: `faec2467277216872487a42744b9fa4b7a2779cb`. Imported Mail #118: `445365cd0cf43d856bf9ab7ef285593749e070a6`. P8 #112 remains independent and already accepted for its own isolated read-only Dev UAT.

## Verified topology blocker

Hostinger's currently published official guidance states **each normal shared/cloud web-hosting MySQL database has one database user**; multiple distinct principals may require a VPS plan. This agrees with actual P8 Hostinger hPanel observations. Official source:
https://www.hostinger.com/support/1583542-how-to-create-a-new-mysql-database-in-hostinger/

Existing P9 contract requires separate **P9 writer** (scoped SELECT/INSERT/UPDATE, no DDL/DELETE) and **P9 reader** (SELECT-only) for the same synthetic queue, separated from **P8's currently accepted read-only DB** and WordPress business `DB_NAME`. Hostinger's routine create-new-DB + one-user workflow **cannot satisfy two P9 DB principals**. Creating one new DB with a single account then sharing its RW password to a reader would be a security regression; changing P8 grants or writing to WordPress DB is prohibited.

Hostinger also documents remotely connecting to MySQL by allowlisting hosts; this is an **access path**, not an additional database identity or safe private-network proof. Never enable wildcard `%` for this project merely to bypass a blocker:
https://www.hostinger.com/support/1583546-how-to-set-up-remote-mysql-access-in-hostinger/

## Permitted architecture alternatives for a separately approved Dev Canary

A. **Prefer a private multi-role MySQL deployment** with distinct account grants, isolated P9 synthetic schema, private site-to-DB networking, backup/restore and transactional Outbox+append-only audit validation. Examples may include a user-managed VPS or appropriately configured managed database. Provider, budget and network path must be independently approved and verified. Do **not** expose MySQL port 3306 publicly or invent existing VPN connectivity.

B. **Investigate existing Hostinger plan capabilities in read-only mode** before buying any new service. Some other hosting products/Agency configurations expose different remote-user controls; this is not evidence the current Dev account supports them. If actual distinct scoped P9 Reader and Writer principals are demonstrably available within the same isolated DB, verify SHOW GRANTS, destructive-permission denial, restore, identity and network policies separately. No silent permission change.

C. **Inert source-only operation** on existing GitHub candidate and independently approved local P9 lab until A or B is validated. The P8 Dev UI stays on its original strictly read-only Hostinger database and the existing production SMTP infrastructure remains authoritative. No fake operational P9 Dev stats can be shown as if live.

**Rejected:** repurpose P8's `SELECT, DELETE HISTORY, SHOW CREATE ROUTINE` exception; change its one-table schema; use the same RW user for Dev P9 Reader; reuse P7/MySQL production datadir; public-MySQL wildcard; make a Dev Worker communicate with an unapproved US Mac service; write into WordPress `DB_NAME`; autodeploy V2.2/CSS; global `wp_mail` takeover.

## Executable guard — but not a deployment executor

`inc/class-yby-mail-v2-p9-dev-topology-gate.php` is a pure, non-bootstrapped planner; it refuses known one-user-per-DB topology, missing role isolation, shared WordPress/P8 access, absent private networking, lack of backup/schema validation and any runtime/owner toggle. Even with all *hypothetical* checks set true, it returns **architecture_review_only**, with every `can_provision/can_write/can_deploy/can_send` false. It stores or reads no credentials, launches no process, opens no SQL connection and calls no WP hooks.

`tests/mail-v2-p9-dev-topology-harness.php` exercises these gates in GitHub CI. This tests source logic only; it does not prove Dev capability exists.

## Release and Owner UAT sequence (not executed)

1. Preserve Andy Core V2.2 #116 admin shell and P8 #112 reader; track their exact HEAD and independent M6-C/M6-D Owner UAT.
2. Review separate composite Draft PR #120 against #116 only, with strict Mail-only allowlist and independent same-HEAD CI. **Do not merge** into #116 or main absent future authorization.
3. Resolve **one viable two-account private P9 DB topology** from A/B, obtain explicit Owner approval of Dev writer provisioning, synthetic-only DML scope and rollback.
4. Only then create and verify separate Dev schema/roles, private credentials, grants denial, cold backup/readback and rollback. Do not modify P8 or WordPress business DB.
5. Future reviewed composite adapter may display sanitized synthetic queue counts in the existing V2.2 Email OS page; desktop plus iPhone14 real screenshot Owner UAT.
6. Real SMTP/worker/WWW migration is a **separate future approval**, not implied by Dev Canary or this composite Draft.

**Gate status:** P9-5 source/isolated US Mac MySQL PASS; P9-6 source-only composite PASS only after its latest CI, Dev Owner UAT **NOT STARTED**; P9-7 source-only coexistence review PASS, no actual outbound switch.
