# Andy Core Three-OS Menu Canonical V1 — Dev Owner UAT

**Owner authorization:** isolated GitHub source development, Draft PR, CI and Dev-only controlled deployment up to Owner UAT. No main merge, WWW, real email, workers, production data writes or READY-device restarts.

## Canonical WordPress sidebar
- Inquiry OS: existing andy-core-leads, default lead list; Inquiry capture components and notification setting.
- Newsletter OS: new andy-core-newsletter, default read-only subscriber list; subscription popup/shortcode; lottery entry (reward rules remain owned by Andy Commerce Marketing OS); campaign approvals are future gated features.
- Email OS: new andy-core-email, default truthful inactive system overview; queue, failures, logs, templates, transport and health. WooCommerce remains owner of its native transactional emails.

Project Studio, Projects and Settings remain unchanged.

## Legacy paths
Old mixed page yby-core-popups remains registered but hidden in sidebar. Authorized GET bookmarks redirect by known tab to the appropriate OS. Original no-tab default preserved; unknown/disabled pages remain on legacy handler. POST requests are NEVER redirected; existing nonce and form processors retain ownership. Existing users' settings remain untouched. New views embed original Inquiry/Newsletter popup handlers without the mixed old tab ribbon. Existing mail template controller is reused.

## Permission contract
Inquiry sales view/ownership and settings cap continue unchanged. Newsletter subscriber SELECT requires new capability andy_core_newsletter_view; complete email addresses are visible to authorized marketers per Owner Option B; no token hashes, unsub token, IP or SMTP credentials retrieved. Filter by status/email, max 20 rows/page, GET only. There are no export, consent-write, approve or send endpoints. Email OS overview requires andy_core_mail_view; template edit/publish/test require existing andy_core_settings_manage. Two new read capabilities are granted automatically ONLY to administrator, never guessed marketing roles. Real non-admin marketing specialist/manager role mapping needs explicit identity verification during UAT; don't mark resolved without this.

Marketing authorization remains specialist draft → manager approval → Email OS execution after consent, quota and immutable approved scope, with Owner first activation/high risk. This PR does NOT implement campaigns or real sending.

## Acceptance checklist
1. GitHub exact HEAD + four CI workflows successful, intended files only; all PRs remain Draft until distinct Owner merge approval.
2. Dev-only deployment candidate must be bound to known Git SHA, site directory, isolated Dev target and rollback pointer. Stop on unknown host/credentials.
3. Desktop: three menus only, correct default homes, existing popup, shortcodes and email template pages render, old GET bookmarks navigate correctly and old POST saves remain valid.
4. iPhone 14: navigation, table horizontal scrolling and forms work without clipping.
5. Roles: administrator sees all, salesperson only scoped Inquiry, explicit Newsletter view capability sees full addresses, unauthorized access denied. Email transport and enqueue/write interfaces remain disabled.
6. No real mail, new queue/cron/jobs, database migrations, subscriber writes, Woo overrides or WWW changes.

## Implementation boundaries
P1/P2 Mail OS V2 Draft PRs #91/#92 are not yet merged, are not dependencies for P3 navigation, and do not imply operational queue data. Newsletter table absent => honest unavailable message, never auto-install. Future newsletter campaign editor, manager approval and real Action Scheduler adapter need separate implementation gates.
