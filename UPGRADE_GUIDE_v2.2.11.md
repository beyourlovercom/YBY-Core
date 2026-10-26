# Andy Core v2.2.11 — Combined upgrade candidate gates

1. Use exact-head PR and merge-source CI 4/4 PASS. Confirm V3 PR #173 base/head identity has not drifted.
2. Build v2.2.11 from the reviewed combined source using `scripts/build-v2.2.11-release.sh` in `RELEASE_CANDIDATE` mode. Do not publish.
3. Verify SHA256, detached Ed25519 signature, plugin root, WordPress version header, historical updater metadata compatibility and inert Mail P9 bootstrap.
4. Before any Dev install, obtain specific Owner approval for `dev.beyourlover.com` and preserve the existing nav/Update Control changes; do not replace selected PHP files with an older installation ZIP.
5. On Dev: verify navigation V3 desktop + M14 mobile, native landing/project list/edit, content/permissions; verify Update Control settings persistence and scoped Cron policy remain PASS.
6. Keep Andy Core background auto-update OFF during visual UAT. Preserve `wp_version_check` and do not run overdue WordPress/WooCommerce/affiliate/mail/ERP tasks.
7. Actual signed self-upgrade and external Cron recovery are separate Owner-authorized gates.
8. Only after Dev Owner PASS and approved exact-head main merge, prepare immutable signed release; Stable publication and per-site rollout each need separate authorization.

**P0 lead notification schema migration is required:** upgrading the plugin adds `<prefix>yby_lead_notifications` with a UNIQUE `lead_id` key; old lead tables and historical records remain untouched. Compatibility version stays 1.4.0 and runtime marker stays 1.5.0; check the table's required columns and unique index, not the marker alone.

9. In the disposable WordPress/MySQL test, verify lead stored → one Outbox row → synthetic SMTP failure → bounded retry → synthetic SMTP acceptance; confirm no real SMTP connection and no duplicate recipient notifications.
10. On approved Dev, verify Inquiry → Notification shows Case ID, recipient, status, attempts and safe error code; owner checks desktop UI, failed-only filter, admin permissions, CSRF nonce and manual retry behavior with a stubbed mail transport.
11. Confirm WP-Cron heartbeat, retry event execution and stale-`pending` hourly recovery. If database schema validation fails, lead storage may still succeed but notification must **not** be sent without a durable Outbox claim. Record the `queue_unavailable` condition.
12. Historical pre-upgrade leads do **not** have trustworthy delivery receipts. Never bulk-resend them; reconcile existing mail logs with human review. True mailbox delivery requires recipient/provider evidence, beyond `wp_mail()` acceptance.
13. Any site upgrade, main merge, Stable release or real SMTP test remains subject to separate Owner approval and exact-head verification.
