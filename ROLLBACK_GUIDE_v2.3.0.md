# Andy Core v2.3.0 — rollback plan

- Immediate local rollback: Andy Core → System → Modules → Notification UI OFF, save, reload; native Woo notices remain operable with compact skin.
- If plugin fails, restore previously signed 2.2.12 code through an approved rollback; deactivating Core also removes the Core-native skin.
- Do not delete yby_lead_notifications, outbox, mail/audit or order/customer data; database/runtime marker unchanged.
- Do not replay old Cron, auto-send emails or modify customer payment state.
- Verify Woo messages, module settings, updater integrity and code version after rollback.
- Stable publishing and every Production rollback require independent Owner approval.
