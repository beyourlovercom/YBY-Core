# Andy Core v2.2.11 — Combined code-only rollback

**Candidate only:** rollback guidance is for a separately approved Dev or site installation; does not authorize a deployment.

- Before approved install: record installed plugin version, exact code-only archive outside public_html, approved source SHA, package SHA256/Ed25519, local Update Control settings and allowed update channel.
- On failed signature, health, admin navigation, Cron policy or wrong site/host: fail closed and restore previous exact WordPress plugin code snapshot atomically; do not modify unrelated plugins.
- Preserve WordPress options and runtime schema; rolling back code does not roll back database, orders, scheduled jobs or newsletter data.
- Do not re-enable unattended upgrades or bulk-execute delayed Cron as a rollback measure.
- Revalidate Andy Core settings, native L2/L3 navigation and manual plugin updates after rollback.
