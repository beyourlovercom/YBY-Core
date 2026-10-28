# Andy Core v2.3.1 — rollback procedure (candidate only)

- First mitigation: toggle Andy Core → System → Modules → Notification UI OFF and reload. Native notices stay functional with Core skin.
- If plugin-level failure, restore exact previously signed 2.3.0 only under separately authorized site change control. Never overwrite published v2.3.0 bytes.
- Preserve database, all yby_lead_notifications records, mail, Woo orders, customers, inventory, payment data and Cron schedules.
- Do not resend historical email, rerun queued Cron or alter any real order/payment while testing rollback.
- Confirm restored version, no duplicate notification, native Woo validation and accessible actionable links.
- A release candidate is not an authorization for Stable publication or Production deployment.
