# Andy Core v2.2.11 — Combined V3 + Update Control RELEASE CANDIDATE

**NOT published. Not approved for main merge, Stable, or any production install.**

Source baseline: stacked PR #173 (Navigation V3 Owner interactive design PASS; Dev implementation UAT still pending) including PR #164 and the merged PR #167/#170 Update Control runtime.

## Single plugin package
- WordPress package path `yby-core/yby-core.php`, version 2.2.11.
- Andy Core Navigation V3: seven Chinese native L2 items, four collapsible right-side groups (文档 / 招商 / 落地页 / 项目), original WordPress CPT management and controllers preserved.
- Andy Core Update Control V1: hide native plugin update notices/badges, deny third-party plugin automatic upgrades while retaining manual updating, selectively clean update-check Cron. The signed Andy updater remains independently opted-in and hostname gated.
- Cron Health V1 read-only diagnostics, with separate "policy PASS" and "scheduled execution health" signals.
- P0 lead notification reliability: every successful Lead is associated with one durable `yby_lead_notifications` Outbox receipt; HTTP lead storage status and SMTP status are separate. `smtp_accepted` means transport acceptance, **not confirmed inbox delivery**. Failed notification retries are bounded, atomic, audited, and visible under Inquiry → Notification; historical messages are not replayed.
- **Database schema change:** adds `<prefix>yby_lead_notifications` with a unique `lead_id` key and status/retry/error/recipient timestamps. The runtime installer detects missing outbox and creates it using dbDelta. Compatibility marker remains 1.4.0; runtime database marker stays at 1.5.0 for the previously established release contract, while schema presence/indices must be verified independently. Do not infer schema completeness from version marker alone.

## Security gate
- Candidate is built from the combined source SHA, with SHA256 and Ed25519 metadata signature; no download/package acceptance without verification.
- Native WordPress core security check `wp_version_check` is preserved. Hostinger Dev Cron execution infrastructure remains P0 Issue #171 and must be checked independently before enabling unattended updates.
- BYL Dev Update Control UI/Cron policy Owner UAT PASS. Navigation V3 actual Dev visual UAT and signed 2.2.11 install are **NOT** yet Owner PASS.
- All three update controls remain opt-in OFF on fresh installs. Dev's existing settings remain stored in WordPress options, not reset by the package.

## Forbidden without separate approval
- No merging to main, immutable tag, GitHub Stable/Latest release or enabling update automation on production.
- No changes to BYL WWW, Bottle/Irrigation production, orders, payments, mail, ERP or queued Cron without separately approved rollout. The P0 Outbox **does** schedule normal lead-specific retry events *after* a future authorized install; older pending Cron batches are not to be bulk-run.
