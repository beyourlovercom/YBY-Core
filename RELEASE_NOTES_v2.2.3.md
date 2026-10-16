# Andy Core V2.2.3 Canary Release Notes

- Signed Canary-only patch for Dev manual WordPress upgrade UAT from V2.2.2.
- Cache Ed25519/SHA256 verified release evidence to avoid repeat asset downloads on admin refresh and WordPress Cron.
- Explicit Check for Updates bypasses the verified-release cache to detect freshly published Canary versions.
- No database migration, no new workers, no SMTP/email sending, no changes to Commerce/Cart/Checkout logic.
- Strict Dev Canary host allowlist; no Stable publication or Production deployment.
