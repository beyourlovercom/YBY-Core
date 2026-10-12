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
