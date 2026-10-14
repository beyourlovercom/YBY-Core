# Andy Core Mail OS V2 — P0 architecture / queue gate draft

Status: **DRAFT / DOCUMENT-ONLY**. No queue worker, new plugin, SMTP setting, migration, mail send, action insertion, Dev deployment or WWW/ERP update is authorized by this document.

Owner direction (2026-10-12): aim for functional parity with valuable SMTP Pro features (queue, scheduled/drip send, sender rate limits, retries, logs) in Andy Core over staged versions. Preserve the current standalone Newsletter V1 path until its genuine Canary is authorized and verified.

## 0. Canonical source and known current behavior

- GitHub `beyourlovercom/YBY-Core` main at P0 baseline `2cdae873d3b0831a942c6a9e95ef8f151a9ecc26`, Core Mail Transport V1 merged PR #75, Newsletter sender docs/testing PR #83.
- Existing `docs/v1.5.8/ANDY-CORE-EMAIL-TEMPLATE-OS-V1-ARCHITECTURE.md` **explicitly keeps SMTP credentials, provider delivery, queues and retries outside Email Template OS V1.1** and explicitly keeps the WooCommerce renderer/runtime owned by WooCommerce. The proposed V2 worker is an opt-in **new infrastructure ownership contract** requiring Owner architecture acceptance. It is not an implied modification of the frozen V1.1 baseline.
- Existing `inc/class-yby-mail-transport.php` is a narrowly scoped, default-OFF Newsletter confirmation transport wrapper; it does **not** establish a general outbound `wp_mail` queue. Site-local sender controls distinguish `newsletter@beyourlover.com` from WWW support/business `support@beyourlover.com`.
- Dev is not WWW. Authenticated Owner-approved WWW WP Admin inspection confirmed **WP Mail SMTP Pro 4.5.0 active** and **Other SMTP** routing through `smtp.exmail.qq.com` SSL port `465`, Auto TLS ON, Authentication ON, username `support@beyourlover.com` (stored credential never inspected). Both Force From Email and Force From Name were observed enabled, but the actual From field values were not read. Dev plugin inventory does **not** show standalone WP Mail SMTP Pro; the two environments remain separate. **Owner screenshot of Settings → Misc confirms Optimize Email Sending ON, Email Rate Limiting OFF, Do Not Send OFF and Hide Email Delivery Errors OFF.** Existing queue length/worker failures, email delivery logs, production throughput, actual Tencent quotas and any pending scheduled mail actions remain unverified.
- Dev read-only plugin list: `yby-core` 1.9.0 active, `andy-commerce` 1.0.1 active, `woocommerce` 11.2.1 active, `woocommerce-payments` 11.2.0 active, `woocommerce-gateway-stripe` 11.0.1 active, AfterShip 1.18.4 active.
- WooCommerce bundles Action Scheduler; its **actual registered Dev version is 4.1.0**, DB store `ActionScheduler_DBStore`, standard `ActionScheduler_QueueRunner`. One successful read-only `wp action-scheduler status` snapshot: 310 complete, 6 failed, 16 pending. Pending hook metadata included `woocommerce_cancel_unpaid_orders`, `wc-admin_process_pending_orders_batch`, `wcpay_store_setup_sync`, `wc_stripe_database_cache_cleanup_async` and other maintenance jobs. No payloads or sensitive arguments inspected.
- **Health uncertainty:** two unrelated Dev WP-CLI reads transiently returned `Error establishing a database connection`, while subsequent commands succeeded and public Dev Newsletter page was HTTP 200. Treat stability/capacity as **UNVERIFIED**, not as a verified healthy worker/database or a confirmed outage. Do not repair DB, run jobs or restart a READY device in P0.
- Existing Dev Newsletter SMTP account secret is not provisioned and real SMTP AUTH / real email Canary not yet evidenced; do not combine the V2 queue UAT with the V1 one-time mail authorization.

## 1. V2 bounded ownership model

| Component | V2 proposed ownership | Explicitly forbidden until later gated review |
| --- | --- | --- |
| Email OS template registry and brand VI | Andy Core; continue v1.5.8 governance contracts | WooCommerce template replacement |
| Mail Queue Engine | Andy Core, default OFF; only explicitly opted-in Andy Core-owned emails | Global `wp_mail` interception; sharing real Woo payment worker slots without isolation |
| Newsletter campaigns/confirmation | Newsletter business policy and consent; enqueue only after independent rollout | Importing or emailing real subscribers in synthetic testing |
| Woo/order/account events | Native Woo/WordPress/Andy Commerce business rules remain source authority | Hijacking native Woo mail hooks or new mail side effects from ERP |
| SMTP transport adapter | Per-channel provider-owned host secrets and verified From identity | SMTP passwords in WP options, GitHub, logs or queue payloads |
| ERP | Read-only metadata projection | Queue execution, template editing, credentials or subscriber imports |

**Migration principle:** queue Andy Core-owned synthetic notifications first. Any future proposal to route transactional Woo email through Andy Core requires a separate contract, cutover gate, parity proof and rollback; no direct renderer ownership transfer. Existing SMTP Pro remains installed in WWW until a separately approved production migration has Owner Final UAT PASS.

## 2. Queue architecture and invariants

1. **Reusable scheduler:** integrate with Action Scheduler already loaded by WooCommerce *if* runtime version and DB state passes gate. Dedicated action group(s), e.g. `andy_core_mail_v2`, and explicit namespace. Do not install a second scheduler plugin, increase global workers or rewrite Woo queue defaults. A group label by itself does not guarantee resource isolation: initial volumes capped; measure backlog and later consider authorized dedicated worker/cron with exclusive group selection.
2. **Outbox / idempotency:** store stable immutable message record and unique `idempotency_key`; queue actions reference only immutable record ID, not recipient/body/password in scheduler args. A transactionally committed outbox row is the only source for delivery. No duplicate enqueue on HTTP retries, double-click or concurrent cron claims.
3. **States:** `queued`, `leased`, `sending`, `accepted_by_provider`, `deferred`, `permanent_failed`, `uncertain`, `cancelled`; audit transitions. SMTP accepted means accepted by relay, **not inbox delivered**. Provider-response ambiguity → `uncertain` and do **not** auto-resend.
4. **Priority and fairness:** P0 security and account-critical, P1 double opt-in and service notification, P2 campaigns, P3 low urgency. Separate rate/token budgets per actual SMTP identity; reserved critical capacity, starvation bounds, and a throttle/backpressure response when DB or SMTP is unhealthy. Priority on top of shared Action Scheduler requires its **own bounded dispatch algorithm**; selecting a group alone is insufficient.
5. **Provider quotas and send windows:** derive Tencent quota from verified corporate subscription/provider account rather than invent messages/minute/day. Per-sender, per-domain and optional recipient-domain limits; quiet hours only where policy permits, campaign pacing. If Tencent policy does not support desired marketing volume, plan a separate bulk ESP without changing account-critical relay.
6. **Failures:** fixed maximum attempts + exponential backoff with jitter only for **unambiguously retryable** preacceptance failures; no automatic retry for accepted/uncertain states. Permanent authentication/authorization failures, safety veto, expired consent, unsubscribe/suppression or recipient policy mismatch fail closed.
7. **Safety/privacy:** default `WORKER_ENABLED=OFF`, `OUTBOUND_ENABLED=OFF`; admin capability+nonce for pause/unpause/manual retry. No real email in CI or synthetic stress tests, credentials only via approved host secret manager, redact recipients/tokens and SMTP responses, retention window to be approved, and audit who approved replay.
8. **Admin UX:** status overview; queue count by priority/state; age p50/p95; per-sender throttle; failures without leaking PII; pause/drain/safe cancel; permission-gated retry with audit. Logs distinguish `queued`, `SMTP accepted`, verified provider events if available, and `inbox unknown`.
9. **Workload isolation:** do not block Woo financial/checkout tasks or G2/G5. Test simultaneous synthetic mail floods and existing Woo pending jobs with latencies measured. WordPress WP-Cron trigger may not drain sustained volume: choose future scheduler model only after capacity measurements and Owner-approved host control.
10. **Rollback:** disable queue dispatcher immediately, leave durable unsent rows queued, restore prior Andy Core-owned synchronous path only after approved business review; preserve audit, no bulk replay on restart and no silent abandonment.

## 3. Phased development and minimum acceptance gates

| Stage | Scope | Acceptance criteria |
| --- | --- | --- |
| P0 Read-only audit | GitHub V1 architecture; Dev scheduler/WordPress plugin inventory; separately authorized WWW SMTP Pro inventory; DB capacity | Observed plugin/provider settings, real limits, existing queue age/failures; no source or runtime mutation |
| P0.4 Architecture Owner UAT | Explicit amendment authorizing isolated Mail OS V2 worker while retaining Woo runtime template owner | Written OWNER ARCHITECTURE PASS, no implied global WP mail interception |
| P1 Code-only foundations | Mail outbox schema *definition*, state machine, unique idempotency, consent, redaction, queue contracts | 100% offline deterministic tests, no DB migration or external mail |
| P2 Dispatcher | Action Scheduler adapter, scoped group, per-identity token buckets, priority/fairness, lease/cancel/replay control | Concurrent and crash/timeout/uncertain transport tests, zero Woo hooks changed, worker OFF by default |
| P3 Admin | Status, logs, filters, incident controls; permission gates | Mobile 390px/M14 visual Owner UAT; no secret exposure or unintended data writes |
| P4 Multi-account | Separate newsletter and support/transactional channels; source-specific provider capabilities | Auth identities/config separation; no fallback and no WWW credentials re-use |
| P5 Synthetic load | Isolated disposable WordPress/MySQL with simulated SMTP and 10k synthetic work items | Bounded backlog/p95 latency/no duplicate/no starvation, **zero external mail**; no shared Dev DB stress until separately allowed |
| P6 Controlled Dev | Deliberate limited outbound with existing Safety/G5 isolation and separately granted ticket | One real authorized message maximum when independently approved; monitor while Woo unaffected |
| P7 WWW migration | Real provider/plugin feature parity, staged and reversible cutover, same sender/auth behavior, rollback drill | Production owner approval and final UAT before stopping any SMTP Pro functionality |

## 4. Mandatory blockers and dependencies

- **WWW plugin and key settings partly audited:** authenticated Chrome + Owner screenshot verified WP Mail SMTP Pro **4.5.0**, Other SMTP Tencent `support@beyourlover.com`, **Optimize Email Sending ON** and **Email Rate Limiting OFF**. This confirms background async optimization is active while plugin-controlled quotas are not presently enabled. It does **not** prove queue worker health, absence of backlog, actual per-provider quota, or email deliverability. No credentials requested and no WWW SSH. Further evidence needed through read-only Email Log / Email Reports (aggregate failures and delivery only; redact recipient data) and scheduler diagnostics, plus host/provider quotas. Attempts to automate additional WWW admin navigation or form value reads were execution-safety-blocked; do not circumvent.
- **Dev DB health is uncertain:** intermittent connection errors require an independently authorized operations diagnosis before any mass queue action or migration. No current inference about root cause.
- **Existing Action Scheduler has 6 failed jobs** at observed snapshot; their categories and root causes remain **unverified** because a subsequent failed-action listing encountered DB connection error. Do not clear or replay them.
- **Newsletter V1 one-message mail safety permission is independent of Mail OS V2:** `newsletter@` SMTP enabled according to Owner, but Dev has no securely provisioned client credential or verified SMTP AUTH. Do not re-attempt any tool command that was previously safety-denied, and do not treat future queue worker as a means to bypass mail veto.
- **New plugin governance:** No separate WordPress plugin, no new directory outside this already-approved code/docs scope, no production theme/Woo/G5/payment changes, no Dev/WWW DB schema install, cron addition, email or service restart until explicit specific release gates.

References: Core Issue #84; Website Newsletter Issue #67; Email OS v1.5.8 canonical architecture; official Action Scheduler usage/performance/WP-CLI docs at https://actionscheduler.org/ .

## 5. WWW Misc audit evidence (Owner screenshot, 2026-10-12)

| WP Mail SMTP Pro 4.5.0 / Misc toggle | Observed state | Consequence |
| --- | --- | --- |
| Do Not Send | OFF | WWW mail globally not disabled by this option |
| Hide Announcements | ON | Notification display preference only |
| Hide Email Delivery Errors | OFF | Delivery warning visibility remains enabled |
| Hide Dashboard Widget | OFF | Dashboard widget not disabled by this setting |
| Disable Email Summaries | ON | Weekly SMTP summaries suppressed |
| **Optimize Email Sending** | **ON** | SMTP Pro asynchronous optimization enabled; site responses need not await SMTP sending, but this alone is not proof of healthy drain or actual delivery |
| **Email Rate Limiting** | **OFF** | Plugin-side rate cap/overflow scheduling disabled, not proof provider quotas do not exist |
| Uninstall WP Mail SMTP | OFF | Uninstall-time deletion toggle not activated |

**Acceptance impact:** V2 must preserve async dispatch parity before any production migration. Do not invent currently active SMTP Pro rate limits: none have been configured via the visible plugin toggle. V2 rate-limiting remains an opt-in enhancement, default OFF until provider/account quotas and business needs are documented. Verify queue/failure metrics and actual delivery timing before proposing SMTP Pro retirement. The screenshot contains no visible SMTP password or authorization code and no settings were changed.

## 6. WWW failed-log P0 evidence closure with explicit residual gates (2026-10-13)

This section records **verified read-only sampling**, not a diagnosis of mail failure or an approval to replace production SMTP Pro. Source: official OAuth MCP on `us-macmini` READY with the Owner's signed-in WWW WP Mail SMTP Pro Email Log → Failed view, plus Owner-supplied non-secret plugin screenshots and [Issue #84's previously recorded pagination evidence](https://github.com/beyourlovercom/YBY-Core/issues/84). No recipient addresses, email bodies, subjects, tokens or SMTP credentials were collected.

### 6.1 Evidence ledger

- Owner's later retained-log snapshot: **All 3,759 / Sent 3,623 / Failed 136**; preceding screenshot had All 3,758 / Sent 3,622 / Failed 136. These are distinct moments, **not** a period-specific failure rate, inbox-delivery proof, active backlog measurement or a count of still-unresolved incidents.
- Failed view: **136 records across 14 pages**, approximately 10 per page; five previously audited **distinct** pages cover **50 failure rows**, all 50 labeled `Source=WP Core`; observed mailer `Other SMTP`. These rows alone do **not** characterize the remaining 86.
- Previously verified pages/date ranges as shown in site UI (timezone not established):
  - **Page 1:** 10 WP Core rows, recent entries spanning October 12–13, 2026 (latest previously reported UI value October 13 21:42).
  - **Page 2:** 10 WP Core rows, October 11 20:43 → October 8 18:16.
  - **Page 3:** 10 WP Core rows, October 8 13:29 → October 6 02:43.
  - **Page 4:** 10 WP Core rows, October 5 05:34 → October 4 00:06.
  - **Page 8:** 10 WP Core rows, September 25 16:49 → September 23 01:55.
- **Pages 5–7 and 9–14: 86 rows not yet individually classified.** No per-message SMTP reply code, provider-side rejection, retry metadata, actual queue wait time or duplicate-delivery evidence has been verified.
- **Current-session reproducibility check:** official OAuth MCP reported `us-macmini=READY`. The signed-in Chrome tab titled `Email Log ‹ BeYourLover® — WordPress` was present at the Failed-filter URL with `status=0&paged=4`. This confirms the tab URL only; it is **not** a fresh page-4 DOM/record validation. A further DOM/table-read request was blocked by OpenAI execution safety; it was **not retried by alternate command, tool or endpoint**. No WWW modifications were made.

### 6.2 Root-cause classification and architecture consequence

| Hypothesized failure class | Evidence status | Mail OS V2 decision (not a claim of WWW cause) |
| --- | --- | --- |
| SMTP AUTH / credentials / sender authorization | **UNVERIFIED** | Fail closed; no automatic retry on permanent authentication failures; keep WWW production identity unchanged |
| Provider quota / rate limit | **UNVERIFIED** | Obtain actual Tencent account-level caps before setting sender token buckets; current WP Mail SMTP plugin rate limiting is OFF |
| Temporary connection / TLS / DNS / timeout | **UNVERIFIED** | Only retry when relay acceptance is definitely absent; bounded backoff with jitter |
| Recipient rejection / suppression / consent | **UNVERIFIED** | Permanent/safety failures are not auto-replayed |
| Application / Action Scheduler / async worker backlog | **UNVERIFIED** | Preserve WWW async parity, measure queue health and isolate Woo/payment tasks before proposing migration |
| Accepted-but-response-uncertain / duplicate risk | **UNVERIFIED** | Distinct `uncertain` state, no blind retry; durable idempotency key and manual review |

The 50 observed `WP Core` rows identify **plugin-reported source**, not the precise initiating WordPress hook, message purpose or SMTP failure reason. No historical failed item is approved for resend, deletion, or bulk export.

### 6.3 P0 disposition / Owner Architecture UAT gate

**P0 evidence package: PREPARED WITH BLOCKERS. P0 ROOT-CAUSE CLOSURE: NOT PASSED. Owner Architecture UAT: PENDING (not a production migration UAT).** Do not close Issue #84 as fully resolved or merge Draft PR #85 based on a document-only CI result.

Remaining gate items:
1. Obtain **sanitized representative failed-log diagnostic codes/categories** through an approved read-only UI/screenshot workflow; no recipient, subject/body, token, password or raw PII. Classify sample vs full population explicitly; no inferred SMTP root cause.
2. Obtain a safe aggregate breakdown of the remaining **86 failures** by source/date or mark residual uncertainty as an accepted investigation limitation; no workaround to a safety-blocked browser command.
3. Verify Dev Action Scheduler **six failed-job types**, database stability/queue table capacity and Woo/payment job contention before any worker/schema authorization. Intermittent DB-connection errors remain unresolved.
4. Verify Tencent sending quota for each production identity, actual async queue age/health, retention, replay and deliverability semantics without initiating real sends.
5. Owner explicitly approves/rejects the **V1.1→V2 queue/transport ownership amendment** while Woo retains transactional rendering and billing triggers; separate authorization is required for any PR merge, Dev test, real-mail Canary or WWW cutover.

**Safety closure for this read-only attempt:** no email sent/re-sent/deleted, no account/setting change, no data export, no action/job runs, no orders/payments or WWW/Dev/ERP code writes, and no READY device restart. Production WP Mail SMTP Pro remains the active mail transport.

## 7. Read-only diagnostic evidence acquisition and Owner Architecture UAT pack (2026-10-13)

**Official product documentation cross-check; it does not add any WWW observation:** [WP Mail SMTP Viewing Email Logs](https://wpmailsmtp.com/docs/how-to-view-email-logs/) documents `Source` as origin (WordPress core, plugin, theme) and places delivery errors in individual-entry `Technical Details`, which may also display sensitive headers. [Debugging Sending Issues](https://wpmailsmtp.com/docs/how-to-debug-email-sending-issues-in-wp-mail-smtp/) documents the preexisting `WP Mail SMTP → Tools → Debug Events` list with `Type / Content / Source / Date`, and says error recording is enabled by default. Do **not** enable extra debug traces, open private email payloads or change settings to generate evidence. [Troubleshooting Action Scheduler Issues](https://wpmailsmtp.com/docs/how-to-troubleshoot-action-scheduler-issues-wp-mail-smtp-2-1-1/) establishes the plugin's Action Scheduler dependency but does **not** prove the WWW worker is healthy.

### 7.1 Safe evidence specification for a newly approved read-only UI/screenshot workflow

The previous official OAuth MCP browser DOM request was explicitly blocked; **this spec is not permission to rerun it by scripting, an alternate connector, or another endpoint**. User-provided screenshots or a separately approved supported read-only diagnostic are required. If using a screenshot, crop/redact locally **before sharing**.

| Existing WWW view (do not alter settings) | Collect ONLY | Must never collect |
| --- | --- | --- |
| Email Log → Failed list | Total/page count; dates to day/hour; source; mailer; status; optional anonymized page number | To/From addresses, subject, body, attachments, message link or ID, token |
| Tools → Debug Events (existing errors) | Date/time to minute, `Error` type, source, **sanitized SMTP/transport failure code and broad cause category** | Raw SMTP transcript, account addresses, mail headers, paths, secrets, email message content |
| Individual Log → Technical Details **only when safe** | Short normalized cause class and numeric code if visible and safely redactable | Full headers, MIME body, recipient, authentication material, raw unredacted debug blob |
| Tools → Scheduled Actions / Woo Status (aggregate) | Per-group/hook name if non-sensitive, counts by pending/failed/complete, age p50/p95 if available | Arguments/payloads, order IDs, recipient identifiers, payments or customer records |

**Correlation rule:** Do not assume that a Debug Event corresponds one-to-one to any of the 136 Email Log failed entries. If no non-sensitive join key is available, report separate counts and qualitative pattern only. Timestamps/site timezone and log retention differ by subsystem; normalize only after confirming them.

### 7.2 Deterministic classification protocol (proposed, not observed)

1. **Authorization/configuration:** explicit `535`-type authentication rejection, invalid credentials, sender policy denial → fail closed; no brute-force or automatic replay.
2. **Provider rate/policy:** explicit `421` / `4xx` temporary limit or explicit per-account quota → bounded cooldown **only after confirming actual provider semantics**, never derive quotas from a sample without policy evidence.
3. **Recipient permanent rejection:** explicit permanent `5xx` address/domain rejection → suppression/review; do not repeatedly send.
4. **Connectivity / timeout:** distinguish confirmed pre-acceptance transport failure from possible post-acceptance response loss; ambiguous acceptance → `uncertain`, no blind retry.
5. **Scheduler/runtime:** actual stalled or failed scoped scheduled action evidence and timestamps required; historical `Failed` email status alone is insufficient.
6. **Unknown / mixed:** keep `UNKNOWN` when diagnostic text is missing or insufficient, and split by source/time without claiming a mechanism.

Codes above are **examples of classification rules, not errors found at WWW**. A `4xx` SMTP code alone is not proof of quota, and a `5xx` alone is not proof of invalid recipient: interpret the complete sanitized provider error category.

### 7.3 Owner architecture UAT card (decision-ready; not automatically PASS)

| Question to decide | Current recommendation | Evidence gate |
| --- | --- | --- |
| May Andy Core own opt-in Mail OS V2 outbox/queue/transport? | **Yes as isolated architecture proposal**, default OFF; amend prior V1.1 non-ownership boundary expressly | Owner Architecture PASS in GitHub; no global `wp_mail` takeover |
| Can current WWW SMTP Pro be retired now? | **No** | P0 historical diagnostics, actual worker health, provider quota, Dev synthetic noninterference, rollback and separate WWW migration approval |
| Can Dev share the Woo Action Scheduler worker for campaigns? | **Not assumed safe** | Stable DB, measured capacity and isolation/fairness under synthetic load; Woo order/payment job latency preserved |
| Can historical Failed entries be auto-retried? | **No** | Per-entry proven retryability, idempotency, consent/suppression, uncertain-state review, separate replay authorization |
| Is Newsletter V1 SMTP Canary covered by this UAT? | **No** | Independent Website Issue #67 safety gate and explicit one-mailbox authorization |

**Proposed UAT declaration, pending actual Owner decision:** `Mail OS V2 P0 documentation and architecture review ACCEPTED WITH OPEN EVIDENCE GATES; no permission granted for merge, runtime code, worker, real mail, or WWW change`. Until the Owner explicitly decides, status stays **OWNER ARCHITECTURE UAT PENDING**. Root-cause P0 remains **NOT CLOSED**.

### 7.4 Single source of truth / status

- P0 GitHub source/CI document gate: PASS on the *previous* HEAD `363e6366b1a52caf341b6183bdf4522301701f21` (4/4 GitHub checks). **This new documentation edit creates a new HEAD and must receive its own CI validation**.
- WWW failed-log evidence: **50 of 136 sampled; 86 unsampled; exact causal codes not established**.
- Blocked diagnostic: **explicit safety block respected**; no rerouted browser access.
- Production safety: WWW WP Mail SMTP Pro active with asynchronous optimization ON; no send/config/queue/Dev/WWW/ERP or order/payment data writes authorized.
