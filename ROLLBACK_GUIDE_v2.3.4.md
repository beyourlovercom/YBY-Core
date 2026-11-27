# Rollback 2.3.4

Disable the new ERP/WordPress welcome flags before rollback; restore the previous native email body if changed. Restore exact original WWW plugin directory, not an inferred generic 2.3.3 build. Preserve yby_lead_notifications, all database records, users, coupons, orders and Connector bindings. Do not restore an old database over live business data. Validate public site, admin and signed ERP health. Do not recreate accounts or resend ambiguous credential-bearing email.
