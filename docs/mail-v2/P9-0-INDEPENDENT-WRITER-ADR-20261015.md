# Mail OS V2 P9-0 — Branch & Writer Isolation Architecture Decision (2026-10-15)

## Decision: independent Mail development; zero Andy Core V2.2 interference

P9 is developed **only on** `feature/mail-os-v2-p9-isolated-writer-20261015`, initially based on the **Mail architecture** PR [#108](https://github.com/beyourlovercom/YBY-Core/pull/108) exact HEAD `e33378bab41baca4743a4fd9178580e23b2c413d`. PR #108 contains parent Mail P1→P2→P3→P4→P5→P6 contracts and isolated P7 MySQL evidence. It **does not contain** the P8 Dev WordPress reader/admin interface from [#112](https://github.com/beyourlovercom/YBY-Core/pull/112), which is instead stacked on P6 **UI** PR #101, itself stacked on navigation PR #94. The existing P8 running Dev UAT was tested independently. **Both branches remain separate.**

Other concurrent Andy Core V2.2 branch [#116](https://github.com/beyourlovercom/YBY-Core/pull/116) exact HEAD `46525e68590b86fb1ad92c5d958afee0529b2b89` contains V2.2 admin shell + Email P8 UI preservation. It is also **Draft/unmerged**. **Do not cherry-pick, rebase, merge, deploy, overwrite or register hooks from P9 into #116/Dev/WWW while V2.2 is working.** P9 may later expose a versioned adapter contract for a separate reviewed integration PR; no duplicate menus/CSS.

## Reuse vs new engineering (owner requested no double work)

- **Reuse:** P1 state/idempotency/allowlist, P2 lease & quota planner, P4 sender routing/error statuses, P5 10k synthetic load model, P6 20-column/4-index DDL manifest, P7 isolation/CAS/MySQL8 runner, P8 separate host DB real read-only Dev UAT/permissions and rollback.
- **Missing:** P9 writer scoped to a different synthetic lab, durable atomic enqueue/claim without real transport, isolated scheduler harness/virtual clock and crash/retry recovery. Real smtp/WWW producer/worker integration is a separate release gate.
- **Explicit non-feature:** new mail menu/CSS/dashboard; Andy Core V2.2 owns this. No (re)implementation of Email P8.

## Writer topology ADR

### Rejected
- Elevating Hostinger P8's single shared database account (currently provider-scoped `SELECT, DELETE HISTORY, SHOW CREATE ROUTINE` for its single non-versioned synthetic table) to INSERT/UPDATE is **rejected**: violates P8 visual-UAT read-only guarantees.
- Writing inside Dev WordPress `DB_NAME` is **rejected**.
- Reusing the US Mac P7 baseline database/Unix socket and historical rows as a P9 writer is **rejected**; P7 evidence stays intact.
- Using a browser/API endpoint that can write recipient payloads or call SMTP is **rejected** in P9 initial phases.

### Accepted for P9 **isolated synthetic engineering lab only**
- A **separate P9 MySQL 8.4 lab database and private datadir/socket** on an owner-authorized computer, with TCP `@@skip_networking=1`. Do not install a second MySQL server/OS daemon if an already-running isolated test daemon can be used *without altering P7*; prefer a private disposable P9-only instance if practical.
- Database identities `p9_writer` and `p9_reader` **distinct**; writer has only SELECT/INSERT/UPDATE restricted to P9 table(s); reader only SELECT. Setup/migration credentials never reused by Worker; no CREATE/DROP on runtime identities.
- Exact P6 schema, private backups, negative grants probe, unique-idempotency and versioned CAS/lease claim validated in SQL on synthetic opaque jobs. Add no message body, customer address or real Woo order identifiers.
- P9 runtime code is **CLI/offline only**, must be explicitly enabled for lab testing, and cannot register WordPress hooks, Action Scheduler tasks, SMTP transport or HTTP handlers.
- Actual direct Dev WordPress integration with a durable writer requires a separate provider/topology review (Hostinger shared hosting may only provision one account per DB), approved identity isolation and composite PR. **P9 lab UAT does not establish Dev operational readiness.**

## Stage and proof gates

1. **P9-0** canonical source diff and this ADR; capture exact P8/#112 and V2.2/#116 HEAD and CI. PASS when branch contract and non-interference documented.
2. **P9-1** read/write identity and isolated synthetic durable queue repository; no migration against Hostinger Dev P8 database. Dedicated lab + executable tests; no SMTP.
3. **P9-2** deterministic duplicate-safe enqueue/claim/replay test on actual isolated SQL with independent processes and hard rollback.
4. **P9-3–P9-5** integrate existing P2/P4/P5 planners, mock transport and uncertainty recovery via a contract, not deployed WP hooks.
5. **P9-6** source CI same HEAD, 10k synthetic load and mobile UI integration proposal reviewed with V2.2 owner; **only then** separately authorize Dev deployment and Owner UAT.
6. **P9-7** WWW/SMTP Pro coexistence and rollback *plan only*, not actual cutover.

**Owner authorization:** Continue Mail OS P9 independently, without affecting V2.2. In this phase source-only development and isolated synthetic testing are permitted; real emails, real credentials, customer/order data, WWW, production, Woo global hooks, CI bypass, PR main merge and Dev database writes remain prohibited unless separately authorized. Do not reboot READY devices.
