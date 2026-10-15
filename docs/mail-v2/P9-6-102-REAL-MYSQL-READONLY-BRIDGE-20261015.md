# Mail OS V2 P9-6 — 102-row real MySQL read-only bridge evidence (2026-10-15)

**Scope:** independent synthetic P9 lab -> V2.2/P8 source-only **data contract**, not Hostinger Dev, real WordPress runtime, SMTP, Worker, real messages or owner visual UAT. Composite Draft PR #120 remains unmerged against Andy Core V2.2 #116, not GitHub main.

## Pin and fail-closed mechanics

Before execution, the following GitHub PR exact HEADs were independently read:
- P9 Writer Draft #118: `445365cd0cf43d856bf9ab7ef285593749e070a6`
- P8 read-only Draft #112: `803bfcc15f318cf6a6eda5e769080593c0eb5bf7`
- Andy Core V2.2 Draft #116: `faec2467277216872487a42744b9fa4b7a2779cb`

Added `tests/mail-v2-p9-102-synthetic-bridge-harness.php` (18 mock assertions PASS on US Mac mini) to check an exact versioned nine-state DTO, strict sum and retry-exhausted subset, privacy, no fake event history or delivery proof, no second Mail UI, rejection of all three branch HEAD drifts and any Dev/SMTP/Worker switch.

Added `tests/mail-v2-p9-real-102-readonly-bridge.php` as a **lab-only CLI, not a WordPress plugin or web endpoint**. Exact opt-in environment marker and fixed private read-only config under `~/.byl-dev-canary/mail-v2-p9-mysql-20261015/artifacts/` (mode 0600) are required. The CLI authenticates as `p9_reader@localhost`, checks fixed schema/socket and `@@skip_networking=1`, checks strict `SHOW GRANTS` (only USAGE + isolated schema SELECT), aggregates fixed state/diagnostic count pairs, then feeds those counts into the existing source-only `YBY_Mail_V2_P9_Integration_Plan::preview()`. The 40-hex branch HEAD inputs are passed from the previously independently checked GitHub readings, and this distinction is preserved: the CLI does not itself call GitHub.

The real isolated MySQL 8.4.11 (existing P9 data, no new schema) printed this outcome with **exit code 0**:

```
MAIL_P9_102_REAL_MYSQL_READONLY_BRIDGE_PASS rows=102 queued=98 leased=1 uncertain=2 exhausted=1 user=p9_reader mysql_network=off dev=off SMTP=off worker=off
```

The observed **102 physical records** are 98 queued + 1 leased + 2 uncertain + **1 deferred with diagnostic retry_exhausted**. The "retry_exhausted" DTO counter is a **subset** of deferred, not an extra 103rd record. An uncertain record cannot auto-retry and no send claim is inferred. Queries contain no recipient, token, body, order data or SMTP response. All `can_deploy/can_write/can_send` fields remain false.

Original private P9 MySQL was then **gracefully shut down** and shutdown log verified. Unrelated Homebrew system MySQL kept running. The preexisting isolated cold archive (SHA256 `1fd73f8915f0b05b38c2c99f22c739ff182121d254b3a4bdcedf0e489b6374f7`) remained intact.

## What this does and does not close

**PASS:** US Mac private P9 MySQL SELECT-only role -> real 102-row aggregate -> preexisting P9-6 DTO source contract, with strict P8/V2.2 UI ownership and zero sending/production capability.

**NOT PASS:** Hostinger Dev P9 Writer and distinct Reader deployment; V2.2 live UI consuming this data; screenshot-based real desktop/iPhone14 Owner UAT; MySQL transactional append-only event log; real SMTP / Woo / WWW cutover. The existing Dev Cloud Startup hPanel was read-only inspected: it offers combined DB+one-user creation and Remote MySQL IP allowlisting, not a demonstrated two-account per-DB editor. Do not promote P8 privileges or expose port 3306 to pass.

## Next safe progression

1. Keep both #118 and #120 Draft, exact-head CI and all existing V2.2/P8 UI untouched.
2. Continue source-only quality gates and the existing 102-row private lab; do not create a new Dev/WWW database.
3. A *separately approved* multi-role private P9 Dev storage/network topology is required before any real Dev writer data path or desktop/iPhone14 Owner UAT for a P9 integrated runtime.
4. P9-7 SMTP Pro coexistence stays a **plan only**. WP Mail SMTP Pro retains all real delivery authority.
