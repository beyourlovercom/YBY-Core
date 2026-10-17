# Andy Core v2.2.9 — signed updater code-only rollback

- Preserve the pinned Ed25519 signature and SHA256 verification before any public Canary release.
- The native WordPress updater must create a compatible **code-only** backup of installed 2.2.8 outside the webroot before upgrading. A separate pre-UAT restricted snapshot is recommended.
- On failure of signed package verification, Hostinger unattended Cron, upgrade health checks, or runtime behavior: restore BYL Dev Andy Core auto OFF; preserve exact-head/cron/output/target/version/error/backup evidence; do NOT automatically repeat.
- A rollback may restore the plugin code only with separate Owner authorization. Do NOT restore or rewrite WordPress DB, orders, payments, WooCommerce settings, lead/mail tables, SMTP or customer data.
- Verify old code reactivated, WordPress core/theme/other-plugin inventories unchanged, website HTTP 200 and no equal-version offer; leave Bottle Dev, Stable 2.2.6 and all Production sites unchanged.
