# P9-6 next-stage: actual MySQL grant evidence and zero-purchase Dev hosting decision

**Date:** 2026-10-15. **Scope:** source-only candidate PR #120; neither the Andy Core V2.2 branch nor Hostinger Dev is modified. This is a safety/evidence note; no new paid infrastructure, credential writes, real mail or Worker activation.

## Actual independent laboratory verification

The existing owner-authorized US Mac mini private P9 MySQL **8.4.11** database was reopened through its own Unix socket with TCP disabled (`@@skip_networking=1`). Source-only preflight was fetched from GitHub's composite branch and its logic tested using a mock regression suite (`MAIL_P9_MYSQL_GRANT_SOURCE_ONLY_PASS assertions=23`).

`tests/mail-v2-p9-real-mysql-grant-readback.php` (explicit environment gate and fixed private credentials file outside webroot) authenticated separately as **p9_writer** and **p9_reader**, executed only `SELECT DATABASE()`, `CURRENT_USER()`, `SELECT @@skip_networking`, `SHOW GRANTS` and `SELECT COUNT(*)`. It printed **no passwords, grants, customer payloads or tokens**. Actual result:

`MAIL_P9_PRIVATE_MYSQL84_GRANTS_READBACK_PASS rows=102 separate_principals=1 writer=select_insert_update reader=select_only network=off smtp=off`

The source `YBY_Mail_V2_P9_Grant_Preflight` checks literal MySQL8 grants against exact synthetic schema and identity; rejects `*.*` privileges, unapproved `DELETE/DDL/GRANT OPTION`, any P8 Hostinger non-SELECT grant exception, wrong DB/user/host, or a missing second identity. It only returns an inert private-lab verdict; **never grants Dev provisioning/deploy/sending permissions**.

After proof, the P9 private mysqld was shut down **gracefully**, verified by its log. The system's existing Homebrew MySQL remained running; no READY device restarted. Original 100-record snapshot SHA256 remains `04a9bef5a5a96b18b54c7bb336f5e00217fcba1b33fd87ab4c60601b18d19e47`; original 102-row cold archive and readback evidence remain as documented.

### Honest limits

- These are **MySQL 8.4 private lab** grants, not Hostinger MariaDB 11 grants.
- The code's `p8_reader_unchanged` input is a source contract, NOT an independent live P8-host grant audit; P8 must be checked separately before integration.
- There is no verified new multi-user Dev database, P9 runtime Dev Writer, operational append-only audit ledger or desktop/iPhone14 Dev Owner UAT. Real SMTP and WWW are future releases.

## Avoid unnecessary VPS expenditure

Official Hostinger general database guide (updated September 2026) says **one user per regular hPanel MySQL database** and suggests VPS if multiple users are needed:
https://www.hostinger.com/support/1583542-how-to-create-a-new-mysql-database-in-hostinger/

**However**, a different official Hostinger Agency plan article (updated October 2026) describes an **Add remote user** option with explicit remote IP scope. This is evidence of a possible *plan-dependent* exception, NOT proof that the current dev.beyourlover.com hosting plan offers it or that the remote user gets independent safe grants or localhost connectivity:
https://www.hostinger.com/support/how-to-set-up-remote-mysql-access-on-agency-plan/

**Next read-only decision sequence (no purchases):**
1. Identify the exact **existing Dev hosting tier** from the authorized Hostinger account without changing its plan.
2. If it offers an authenticated **additional MySQL user** for the same database, inspect scope and permission UI read-only. NEVER select `Any host (%)`, open MySQL publicly, or create new accounts merely to test.
3. If full Reader (SELECT only) + Writer (SELECT/INSERT/UPDATE only) on a new dedicated P9 schema is possible with private connectivity, prepare the exact grants, account/scheme backup plan and a separate narrowly-scoped Owner approval. No P8 or WordPress DB changes.
4. If the tier does not support it, do **not** buy VPS automatically. Retain the already-paid US Mac private lab for source/engineering UAT, and present an actual costed/private-network Dev service option for future authorization; do not claim it is a live Dev queue.
5. Perform a live negative permission test and Dev real screenshot UAT only after explicit Dev synthetic writer and network authorization. Preserve V2.2 Email UI ownership, incumbent WP Mail SMTP Pro and no Worker/SMTP.

**Decision gate:** P9-6 source-only isolation PASS; real Dev Writer/Reader **not provisioned**. P9-7 SMTP Pro coexistence remains source-only; no live route switch.
