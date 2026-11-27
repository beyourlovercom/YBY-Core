# Upgrade 2.3.4 — WWW only

1. Download the validated Andy Core ZIP and verify SHA256. Do not publish to the automatic-update channel.
2. Preserve the current complete WWW yby-core plugin directory and verify any site-specific changes against this package before replacing it. Do not overwrite unknown customization. Preserve yby_lead_notifications and all business data.
3. In the existing WWW WordPress admin, upload and replace Andy Core via Plugins > Add New > Upload Plugin. Do not uninstall/delete first; do not install on other sites.
4. Verify 2.3.4 active, frontend, admin, signed ERP health and AffiliateWP installed hooks. Keep YBY_ERP_NATIVE_WELCOME_ENABLED and ERP KOL_CREATE_NATIVE_WELCOME disabled until coordinated switch; do not use new tags in the live template before then.
5. Verify no message-body logging or credential-bearing trace and approve a single controlled onboarding test. No historical resend. Mail acceptance is not proof of delivery.
