# Andy Core Three-OS M6 + M7 — Dev-only Owner UAT

**Scope:** Owner approved M6 hide inactive menu items + M7 connect existing data read-only. **NOT** approval for M8 newsletter composer, manager approvals, SMTP cutover, real mail, queue worker, WWW changes, DB migration, or merge.

## Parent / delivery
- This isolated **stacked Draft PR** is based on Three-OS M1–M5 Draft PR #93 at exact HEAD `dafe66f5c7f272c774beda1769021dc70b74b41e`.
- Main remains GitHub canonical. Base PR #93 and Mail OS V2 P1/P2 Draft PRs #91/#92 remain UNMERGED.
- New source: `admin/class-yby-three-os-readonly.php`, modifications only to Three-OS tabs, Inquiry stats route, scoped CSS, core backend class include and dedicated offline tests.

## M6 visible navigation
- Inquiry OS retains all existing functional tabs and replaces stats placeholder with scoped read-only status summaries.
- Newsletter visible tabs: **Subscribers, Consent/Unsubscribe, Subscribe Popup, Subscribe Shortcode**; hide **Lottery, Marketing Campaigns, Approvals/Schedule, Activity Statistics** until each has a real backend. Old direct links remain truthful not-enabled messages, not fake actions.
- Email visible tabs: **Overview, Email Templates, Transport, Health**; hide **Queue, Failures, Event Logs** pending verified true Outbox/log sources and separate runtime authority. Old direct links continue to display truthful inactivity.
- No menu writes; tab visibility is an internal list choice only. Existing legacy GET and POST routing and nonce/save ownership remain intact.

## M7 real read-only status data
- Inquiry statistics query existing leads + lead-management tables using recognized statuses; **enforces salesperson ownership in SQL** exactly as `YBY_Lead_Management::list_leads()` (restricted `salesperson` without `manage_options`). No raw lead personal data in aggregated result. Never display unavailable DB as 0.
- Newsletter Consent tab uses existing M1 subscribers table with **COUNT by status only** (pending/subscribed/unsubscribed/suppressed). No export or manual status mutation, no confirmation/unsubscribe tokens, no fake subscriber counts.
- Email Overview / Transport / Health reuse `YBY_Email_Health_Center::transport_status()`, limited to configured flag and non-secret plugin/provider labels. No SMTP credentials or URLs rendered. Shows Worker and Outbox **inactive**, no live operational delivery claims.
- Inactive Newsletter campaign approval and mail queue remain DISABLED. Never create data or send mail to fill UI.

## Checks / independent gates
1. Exact HEAD CI 4/4 PASS, no parent PR merge.
2. M6/M7 source-only test includes channel ownership, no sensitive email fields, staff capability rules and unauthorized access refusal; existing P3 tests continue PASS.
3. Dev-only deploy must verify active v1.9.0 host/site and preserve three preexisting Business OS registration lines inside Dev `inc/class-yby-core.php`, documenting an exact SHA & verified backup before changing.
4. Read-only Dev WP-CLI smoke must prove real database source is available and user-scoped counts are correct; no PII in logs or Owner output.
5. Desktop and iPhone 14 Owner UAT required. Marketing-role capability mapping remains separately open if Dev has no dedicated marketing role. Browser form POST save from original M5 also remains pending.
6. WWW/production, real outbound, Action Scheduler, refunds/orders/payments, migrations, DB writes and READY device restart PROHIBITED.

**M6/M7 is complete for Owner UAT only after exact-head CI, safe Dev-only deploy and runtime read-only smoke.**
