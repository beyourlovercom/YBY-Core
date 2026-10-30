# Andy Core 2.3.3 — rollback guide

- R6 Stable publication is authorized; this guide does NOT authorize installing on any WordPress site. YBY production currently remains on independently restored 2.2.10.
- Keep the exact prior plugin directory and all data intact.
- If an authorized future 2.3.3 deployment fails, restore only its original plugin files via a controlled atomic replacement; do not restore or overwrite the WordPress database.
- Validate uncached home, API, wp-admin, wp-login, Ads landing URLs, Case ID and Inquiry service.
- Preserve logs and package SHA256. Keep public 2.3.2 release immutable for audit; do not downgrade YBY's data based on version number.

- Keep yby_lead_notifications, lead data, WP users and Connector tables unchanged during rollback.
