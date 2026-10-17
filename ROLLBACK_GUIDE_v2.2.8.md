# Andy Core v2.2.8 — signed update code-only rollback

- Canary package is signed with the existing pinned Ed25519 key; WordPress updater rejects unsigned, wrong-sha256, invalid-ZIP, equal/older-version or excluded-host offers before installation.
- Normal update pre-install hook creates a bounded compatible **code-only** snapshot; do not call the manual ZIP uploader as an automatic-upgrade test.
- On a Dev failed install/health check, record plugin version, signed target metadata and rollback-reference state, stop repeat attempts, and request Owner authorization before any manual restore.
- Rollback restores plugin **code only**, not WordPress database, orders, payments, WooCommerce settings, Mail OS SMTP credentials or customer data. Keep secrets and PII out of logs and public PRs.
- After restoration verify prior version active, no same-version cache offer, unrelated plugin updates unaffected, and Dev policy reset to OFF.
- Bottle Dev automatic updates stay OFF and both Production sites retain their independently controlled v2.2.6 installations. GitHub Stable v2.2.6 signed assets are immutable.
