# Email OS V2 P9-7 — WP Mail SMTP Pro Coexistence, Future Canary, Rollback Gate

**Date:** 2026-10-15  
**Workstream:** independent Mail-only Draft PR #118 (`beyourlovercom/YBY-Core`)  
**Status:** **source-only governance and future release plan; no operational cutover authorization**  
**Owner decision:** P9-7 ends with a verified non-executable coexistence plan, never an implicit SMTP Pro replacement.

## 1. Grounded existing source — DO NOT duplicate or intercept

| Current surface | Confirmed existing source | Baseline ownership through P9-7 |
| --- | --- | --- |
| WordPress/WooCommerce transactional and recovery email | Current WordPress mail infrastructure and installed mail plugin; explicit transaction provider parity NOT proven by P9 | **Incumbent path untouched** |
| Inquiry/lead notification | `inc/class-yby-email-notification-provider.php` calls `wp_mail`, scopes PHPMailer alternate body | **Existing notification path untouched** |
| Newsletter confirmation | `inc/class-yby-mail-transport.php` has a scoped Tencent Enterprise SSL/465 adapter; it runs only for newsletter confirmation, temporarily installs and removes scoped filters | **Existing scoped adapter untouched** |
| Mail P9 priority scheduler | `inc/class-yby-mail-v2-p2-planner.php` and `p2-scheduler.php` output proposals, not live Action Scheduler jobs | **Synthetic-only, scheduling OFF** |
| Mail P9 isolated writer | `inc/class-yby-mail-v2-p9-lab-writer.php` validates a P9-only MySQL socket, strict DB/roles and synthetic metadata | **US Mac mini private engineering lab only** |
| Backend queue, failures and health | P8 #112, preserved in Andy Core V2.2 #116 email tabs/adapter | **V2.2 / P8 own UI; no second menu/CSS** |

**Important:** P9's `provider_ack` only proves acceptance by provider; it is NOT recipient inbox delivery. Site-specific host transport readiness does not establish the actual Tencent throughput quota. No first-party evidence says WP Mail SMTP Pro can safely be uninstalled.

## 2. Coexistence contract / role matrix

- **P9-7:** source-only; `QUEUE_ENABLED=false`, `OUTBOUND_ENABLED=false`, `RETRY_ENABLED=false`. No WordPress bootstrap `require`, `add_action`, `pre_wp_mail`, `phpmailer_init`, `wp_mail`, Action Scheduler registration, WP-Cron or SMTP configuration mutations from P9.
- **System / Q0:** payment/order/security/reset mail must **continue incumbent delivery path**. New queue never intercepts mail and cannot become fallback without explicit verified parity.
- **Account/inquiry / Q1:** currently incumbent; production inquiry hooks and live addresses remain unchanged.
- **Bulk/newsletter / Q2–Q3:** P9 only synthetic quotas. No import, suppression bypass, consent copy, campaign sending or sender cross-fallback.
- **P8 read-only:** never grant INSERT/UPDATE or reuse credential; do not modify P8 schema/grant exception.
- **Dev and WWW:** no deployment of P9 Worker; no actual sender switch. Never install a second global PHPMailer or `pre_wp_mail` handler alongside SMTP Pro.
- **AICC / GitHub:** main is single future integration target; Draft #118 remains unmerged, separate from V2.2 #116 and P8 #112 until a composite, independently reviewed, exact-head CI-tested Draft PR and explicit merge approval.

## 3. Provider evidence required BEFORE a future live Canary

1. Site and transport identity: explicit approved From addresses, Tencent Enterprise or other provider, per-mailbox authenticated relay, SPF/DKIM/DMARC records, bounce/complaint and suppression policy; confirm exact scopes and sender ownership.
2. Real provider limits: verified per-mailbox and domain hourly/daily/rate/inflight maxima, provider throttling semantics and error classes, separate sender budgets. No 30/20 tokens from P9 lab reused as production quota.
3. Consent and address protection: newsletter opt-in, unsubscribes, suppression/complaint fences, encryption/retention of separately approved payload store, no raw email address or content in P9 20-column Outbox/audit.
4. Correctness: transactional durable outbox+append-only audit; crash-window proof before any remote acceptance, idempotency, exact CAS, bounded retry ONLY with definite pre-acceptance evidence; timeout/unknown -> `uncertain` HOLD, manual-only review.
5. Compatibility: WooCommerce Q0 examples, reset password, newsletter confirmation, inquiry notification, background Action Scheduler isolation and WP Mail SMTP Pro instrumentation. Complete Dev real screenshot UAT (desktop and iPhone 14) before future real send.
6. Safety: explicit Owner approval for isolated Dev writer and any Worker; separate later approval for real SMTP canary, customer data, recipient scope, WWW/Production cutover, global WP hooks or SMTP Pro retirement.
7. Operational: dashboard redacted queue / failures / errors, verified exact-head CI, bounded rollback duration, maintained incumbent route and verified fallback where duplicate-send risk is zero.

## 4. Controlled *future* release phases — all intentionally NOT EXECUTED

| Future gate | Permitted validation | Exit criterion and abort |
| --- | --- | --- |
| E0 — Source and lab closure | PR #118 same-head CI, P9 private test evidence, audit gap clearly documented | Failed CI, unverified evidence or branch drift -> stop |
| E1 — Separate Dev synthetic-only | After independent approval: distinct P9 Dev writer DB/roles/service; no changes to P8 / Woo data or UI ownership | Any extra grant, data mixing, write into P8 or schema drift -> refuse |
| E2 — Dev observability + visual | Compose separate reviewed integration Draft PR with V2.2 and P8; existing Email tabs and iPhone14 screenshot UAT | Any duplicated UI/CSS, module regression, missing owner PASS -> refuse |
| E3 — Mail shadow mode | With future approval: metadata observation only, no interception/scheduling/sending, compare counts to incumbent | Any false delivery claim, PII leak or drift -> stop |
| E4 — Bounded opt-in real mail Canary | **New separate explicit approval:** verified identity, quotas/consent, small allowlisted identities, incumbent backup; no Q0 takeover initially | Unknown acceptance -> HOLD, never blindly resend; throttle/error -> auto-pause |
| E5 — Wider transition | **Further approval:** per-route rollout after proven performance, complaint/bounce monitoring, anti-starvation and rollback | Any Q0 misses, duplicate mail, order regression -> restore incumbent |
| E6 — SMTP Pro retirement | **Not part of P9** and never automatic. Only after complete incumbent feature equivalence and owner signed change control | Otherwise keep SMTP Pro installed and authoritative |

**No current E0–E6 row above authorizes Dev/WWW deployments or emails.**

## 5. Rollback matrix (pre-reviewed, not executed)

- **P9 synthetic source change fails:** revert the separately approved future composite integration ref; keep #118 Draft and Worker disabled. No effect on incumbent WordPress SMTP path.
- **P9 Dev writer down or credentials fail:** fail closed P9 only, leave P8 read-only page and incumbent mail untouched; quarantine unresolved P9 leases rather than auto-replaying.
- **Provider timeout / acceptance unknown:** `uncertain` HOLD; never switch providers or send twice. Admin verification requires audit evidence before any disposition.
- **Exhausted definite pre-acceptance:** non-dispatchable `retry_exhausted` bucket; no admin one-click mass replay in this phase.
- **WP Mail SMTP Pro conflict:** do not stack global filters; future Canary must be disabled, incumbent plugin configuration left as-is and its verified path checked independently.
- **Woo/security email failure:** retain/recover incumbent path; avoid WordPress/Woo live task write or retroactive replay of uncertain messages.
- **UI regression after future integration:** revert only independently approved Mail adapter, not V2.2 shell / P8 view / other OS components.
- **Unexpected personal information in logs:** stop P9 telemetry and follow incident handling; never print raw provider or SMTP logs into GitHub/chat.

## 6. Handoff and signoff truth table

**Already proven:** P9 real private 100-row MySQL, CAS/expired lease HOLD; subsequent 102-row isolated lab P9-5 bounded retry / two-process deferred CAS / final hold. P8 prior Dev Owner read-only PASS. Source-only versioned P9-6 health DTO and mock CI.

**Not proven:** standalone append-only transactional audit; post-crash full Worker lifecycle; actual Dev writer/Owner iPhone14 UAT; production provider quota; true SMTP delivery; Woo equivalence; WP Mail SMTP Pro take-over. Do not call P9-6 production ready or P9-7 SMTP replacement PASS.

**Approval state:** P9-7 **source review deliverable complete** once its source/test same-head CI is successful. P9-6 Dev operational Owner UAT **remains gated**, P9-5 append-only audit **requires separate scoped design / migration approval**. P9-7 itself never sends mail, registers hooks, changes configuration or merges main.
