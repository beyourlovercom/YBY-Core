# Andy Core v2.2.11 — Combined code-only rollback

**Candidate only:** rollback guidance is for a separately approved Dev or site installation; does not authorize a deployment.

- Before approved install: record installed plugin version, exact code-only archive outside public_html, approved source SHA, package SHA256/Ed25519, local Update Control settings and allowed update channel.
- On failed signature, health, admin navigation, Cron policy or wrong site/host: fail closed and restore previous exact WordPress plugin code snapshot atomically; do not modify unrelated plugins.
- Preserve WordPress options and runtime schema; rolling back code does not roll back database, orders, scheduled jobs or newsletter data.
- P0 creates a new `<prefix>yby_lead_notifications` Outbox table. **Do not drop that table or delete error/audit records as a routine rollback step.** Record outstanding `pending`, `failed`, `exhausted` and `processing` entries before code rollback; an older runtime does not execute the new retry callbacks. Reconcile the queue manually after restoration without bulk resending or triggering existing Cron batches.
- Rollback of plugin files cannot undo already accepted SMTP deliveries. Never retry `processing` / unknown-result receipts without checking provider logs first.
- Do not re-enable unattended upgrades or bulk-execute delayed Cron as a rollback measure.
- Revalidate Andy Core settings, native L2/L3 navigation and manual plugin updates after rollback.
