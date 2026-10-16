# Andy Core v2.2.5 — proposed code-only rollback

**Candidate-only documentation. Do not perform rollback without a real failed installation and appropriate authorization.**

- The signed updater must verify the exact package SHA256, metadata signature, database schema compatibility and prior-code snapshot before replacing plugin code.
- On failed post-install health validation, restore the verified previous-code snapshot, record exact installed version, and inspect WordPress's plugin update transient. Never re-offer an equal-version package.
- Rollback applies to plugin code only; it must not modify customer data, WordPress database schema, WooCommerce orders, payments, SMTP or secrets.
- Keep Canary host and automatic-update settings under explicit per-site controls; WWW/Production and Stable releases require separate approvals.
