# Email OS V2 — P9-5/6 Owner Code Gate Candidate (2026-10-15)

**Scope:** independent Mail-only Draft PR #118; no GitHub main merge, Dev/WWW deploy or SMTP.
**Source of truth:** re-read latest #118/#112/#116 exact heads and same-HEAD CI before any review. 
**Historical P8 baseline:** #112 at `803bfcc15f318cf6a6eda5e769080593c0eb5bf7`.
**Historical V2.2 baseline:** #116 at `faec2467277216872487a42744b9fa4b7a2779cb`.

## 1. Verified P9-only evidence

- US Mac mini isolated MySQL 8.4.11 socket, `@@skip_networking=1`, dedicated schema and separate `p9_reader`/ `p9_writer` roles.
- Existing immutable 100-record snapshot hash rechecked:
  `04a9bef5a5a96b18b54c7bb336f5e00217fcba1b33fd87ab4c60601b18d19e47`.
- New 102-row synthetic recovery evidence: distinct PHP processes racing a deferred task produced 1 winner/1 loser, exact CAS, 3-attempt cap and non-dispatchable `retry_exhausted`; uncertain expired-lease task HOLD. No SMTP, no WordPress or Action Scheduler runtime.
- A **cold, private 102-row-stage MySQL datadir backup** (NOT a JSON 102-row Outbox export) was created *after clean MySQL shutdown* on the same device, private archive:
  `~/.byl-dev-canary/mail-v2-p9-mysql-20261015/artifacts/p9-5-cold-datadir-102-20261015.tar.gz`;
  permissions `0600`, archive size **3,006,048 bytes**, 179 listed entries.
  SHA256 `1fd73f8915f0b05b38c2c99f22c739ff182121d254b3a4bdcedf0e489b6374f7`.
  Contents include private MySQL system state; do NOT upload to GitHub, chats or public storage. Archive listing + SHA verified. **Independent cold restore PASS**: extracted into a distinct private `mail-v2-p9-restore-20261015` directory; started only the isolated MySQL 8.4.11 instance with a separate Unix socket, `@@skip_networking=1`, authenticated independently as `p9_reader@localhost` and performed read-only query. The restored database contains exactly **102 synthetic records: 98 queued, 1 leased, 2 uncertain, 1 deferred with retry_exhausted**; no customer records or SMTP. Restore instance shut down cleanly and remains OFF. No other MySQL was restarted. This is a **verified point-in-time cold restore**, not a proof of actual operational sender recovery.
- Original P9 dedicated MySQL instance is **cleanly shut down**, system Homebrew MySQL remains running. No READY machine restart. Do not initialize or overwrite any datadir.

## 2. Additional source-only audit fault simulation

`inc/class-yby-mail-v2-p9-audit-contract.php` strictly accepts opaque digests, allowlisted state transitions and fixed diagnostics and always returns `review_only`. It contains no SQL writers, hooks or any SMTP.

`tests/mail-v2-p9-atomic-audit-harness.php` runs in an **in-memory disposable SQLite** and exercised 24 assertions on atomic paired Outbox CAS and future ledger INSERT: crash after CAS, crash before commit, unique key collision, rollback integrity, stale replay refusal, durable unknown HOLD and read-after-ambiguous-commit. The harness intentionally tears down its disposable audit collision fixture. **Do not conflate SQLite simulation with an operational MySQL append-only audit**.

Production-strength event history needs a NEW approved, isolated V2 ledger schema and role with atomic transactions and readback, described in `docs/mail-v2/P9-5-APPEND-ONLY-AUDIT-V2-SOURCE-GATE-20261015.md`. Existing single-table P9 writer manifest (20 columns/4 indexes) and P8 Dev readonly fixture must not be amended, relaxed or reused.

## 3. P9-6 cross-branch source compatibility (read-only verification)

Current read-only GitHub source review of P8 #112 vs V2.2 #116:

| Source | P8 file SHA | V2.2 file SHA | Decision |
| --- | --- | --- | --- |
| `admin/class-yby-mail-v2-p8-readonly.php` | `ea587589f8d76d1a8d4e6d310fe5afcc04822956` | same | Reuse existing P8 reader unchanged |
| `admin/class-yby-mail-v2-p6-dev-preview.php` | `463e352584ba956fc594b3bd63b16e33edd97ef2` | same | Reuse existing preview unchanged |
| `admin/class-yby-three-os-admin.php` | `f0af7a29aef0147ae01d01865a819810d9223c36` | `50d55ac5b7233132ee020f8e27143dc2d5c51c18` | Divergence: **V2.2 owns future UI**, do not copy P8 version over it |

P9 #118 changed-file list remains exclusively `inc/class-yby-mail-v2-p9-*`, `tests/mail-v2-p9-*`, and `docs/mail-v2/*`. No CSS/admin/commerce/other modules.

`mail_p9_health_contract_v1` stays **inert, versioned and source-only**, rejects P9/P8/V2.2 exact HEAD drift and PII. New P9 source must not register Mail or Action Scheduler hooks. Future Hostinger Dev writer requires separate credentials/database and Owner authorization. P8 readonly principal stays readonly.

## 4. Owner Code UAT checklist (not Owner-PASS)

1. Verify #118 latest HEAD and 4/4 success on precisely that SHA; inspect GitHub changed-file allowlist.
2. Read and accept true limitations: `uncertain` has no automatic replay, permanent `retry_exhausted` is non-dispatchable, mock proof token can never authorize a real send.
3. Confirm independent P9 real MySQL 102-row crash/race technical test and 100-row snapshot hashes; confirm private cold archive and original unrelated MySQL unaffected.
4. Confirm P9-6 will use only existing V2.2 Email tab/P8 readonly interface; no second menus/CSS.
5. Authorize separately (if desired) an isolated **Dev synthetic writer database and scoped role**, backup/rollback and a reviewed composite Draft PR. Do not modify Hostinger P8 database/permissions.
6. Complete **Dev** desktop and real iPhone14 screenshot UAT and same-head regression only after authorized source integration; do not count previous US Mac lab screenshots as Dev Owner evidence.
7. Leave live SMTP Pro and all current transactional/inquiry/newsletter paths untouched. P9-7 is only a source review + future canary/rollback proposal.

**Gate truth:** P9-5 isolated real recovery PASS, append-only audit design/simulation PASS but operational ledger not started. P9-6 cross-branch source planning PASS, **Dev Owner UAT not started**. P9-7 source-only coexistence PASS, **no actual SMTP/WWW change**. Preserve Draft/unmerged status.
