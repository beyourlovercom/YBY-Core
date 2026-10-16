# Andy Core v2.2.6 — guarded code-only rollback

- The signed WordPress updater must verify ZIP SHA256 and detached Ed25519 metadata, version > installed, DB compatibility and a prior compatible plugin-code snapshot **before** install.
- On failed post-install health checks, inspect `yby_core_updater_pending`, version and backup evidence; restore the exact compatible previous code using established updater rollback controls if authorized. Do not silently overwrite a newer release or retry same-version upgrade.
- WordPress site database, WooCommerce orders, payments, SMTP credentials, subscriber records, and production websites must not be changed by rollback. Keep run logs free from signing secrets.
- After rollback, ensure updater cannot display an equal/older version, and preserve third-party updates. Owner/CI/Canary/Dev gates remain distinct.
