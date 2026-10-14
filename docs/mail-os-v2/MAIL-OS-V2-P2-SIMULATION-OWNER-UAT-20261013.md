# Mail OS V2 P2 — Offline Scheduler/Rate Simulation Owner UAT

Status: **P2 CODE ONLY — DRAFT / NO ACTIVATION**. Source: beyourlovercom/YBY-Core. P2 is stacked on Owner-PASS P1 code at exact SHA \`78ea82761853ee4901b8fa276dbc8d39eaddb5d9\`; P1 Draft PR #91 and P0 Draft PR #85 remain unmerged. Do not merge or deploy any PR without the separate release authorization.

## 0. Authority and facts inherited

- Owner accepted P1 Code UAT and explicitly requested P2. Existing P0 WWW SMTP Pro and heterogeneous failures remain documented; do not repeat the 579-event census to build inert scheduler code.
- WWW retains active WP Mail SMTP Pro; WooCommerce owns its normal order/payment email rendering and scheduler actions. Newsletter V1 Canary remains separately gated.
- Dev Action Scheduler 4.1.0 was observed previously; Dev DB connectivity and six failed tasks were not proven healthy. **P2 only passes fake/synthetic readiness snapshots** and does not claim a verified production runtime.
- The provider's actual verified sender quotas remain unknown. **There are no live sending rates, credentials or customer addresses in P2 fixtures**.

## 1. Code scope and behavior

- \`inc/class-yby-mail-v2-p2-planner.php\` is an **inert pure function** over synthetic queue snapshots. Inputs are whitelisted opaque hashes and structured fields only. It rejects malformed items, duplicate idempotency keys, missing/unsupported sender budget, invalid channel priority, and unhealthy/non-synthetic planning. Output is **proposed leases**, never persisted claims or real sends.
- \`inc/class-yby-mail-v2-p2-scheduler.php\` declares a namespaced \`andy_core_mail_v2\` Action Scheduler *proposal*, but **does not register hooks or call any Action Scheduler function**. The proposed action arguments carry only opaque ID and expected state-version, and \`scheduled=false\`, \`executed=false\`.
- Per-sender limits use **supplied synthetic budgets**, max inflight, token count, cooldown, and capacity; no guessed Tencent quota. Refill rejects clock rollback, negative capacity and unknown quota provenance.
- Critical-system P0 lane is ordered ahead of normal P1/P2/P3 lanes. Configurable \`critical_reserve\` prevents campaign tasks from consuming all shared proposal slots. Within a priority lane, select the sender with the fewest current-batch claims, with deterministic tie-breaks.
- CAS/lease proposals include expected \`state_version\` and \`where_state\`, **not atomic writes**. Concurrency harness simulates two claimers against one state version; only one wins. A real transactional compare-and-swap and durable unique constraint are still needed in a future separate gate.
- On lease expiry, any **sending** item is \`uncertain\` regardless of timeout; a leased item goes to \`deferred\` only if independent upstream evidence proves it was never transmitted. Never automatically resend a possibly accepted message.

## 2. Acceptance tests (synthetic only)

1. P1 offline harnesses still PASS — 56 contract assertions + 60 governance assertions.
2. P2 offline harness PASS — **63 assertions**, including 1,000 synthetic queue items competing for a 50-item maximum batch; ten critical tasks reserved, 40 campaign proposals, no duplicate claims.
3. Sender quota/cooldown/inflight; no default provider quota; invalid fields and raw addresses fail closed; invalid priority cannot escalate; unknown/accepted/exhausted items excluded.
4. CAS race simulates a single winner; TTL bounds and expiry paths; \`uncertain\` never auto-requeued.
5. Scheduler proposal requires a **synthetic** compatible Action Scheduler snapshot, **fake** DB health and **fake** Woo isolation evidence; normal runtime always disabled.
6. Source PR diff contains only **P2 new files** in existing \`inc/\`, \`tests/\` and \`docs/mail-os-v2/\`. No edits to \`yby-core.php\`, runtime bootstraps, WooCommerce, Checkout/G5, real CRM/Newsletter flows, secrets or schema.
7. Exact-head GitHub PR CI passes independently; no assumption from parent P1 checks.

## 3. Explicitly NOT completed by P2

- No live Action Scheduler hook, queue worker, WP-Cron entry, real scheduler table, durable DB outbox, quota configuration, rate-lock storage, actual atomic claim, SMTP provider integration or email transmission.
- No capacity measurements or Woo task noninterference **in real Dev runtime**. Synthetic isolation tests demonstrate *policy*, not operational throughput/SLA.
- No ability to retire WWW WP Mail SMTP Pro or replay historical failed records.
- No P3 management UI. Real Dev-only deployment, schema, data writes, worker, provider auth, WWW change or mail send require their own authorization.

## 4. P2 Owner Code UAT decision card

| Gate | Expected | Status |
|---|---|---|
| Four inherited P1 files retained, P1 Owner PASS | No reimplementation | Ready for source review |
| P2 dry-run planner and scheduler proposal | No side effects | Ready for CI validation |
| 1,000-item synthetic overload + 63 P2 assertions | Safe bounded results | Ready for CI validation |
| GitHub branch/PR scope | Stacked P2-only diff; no main merge | Ready for PR verification |
| Real Dev queue execution | **Not in P2 Code UAT** | BLOCKED pending new authorization |
| Real Woo isolation, DB health and Tencent quota | **Not in P2 Code UAT** | OPEN for future runtime gate |

P2 Code UAT must not be confused with Dev/Production deployment UAT. **P2 is complete only when the exact PR HEAD has CI PASS and the Owner explicitly accepts the code.**
