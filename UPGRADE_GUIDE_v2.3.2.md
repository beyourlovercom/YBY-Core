# Andy Core v2.3.2 — upgrade guide

1. Confirm separate site approval and signed SHA256/Ed25519 evidence from public andy-core-release v2.3.2.
2. Backup current plugin files, note exact installed version, preserve database/yby_lead_notifications and Woo payments/orders.
3. Install signed yby-core plugin ZIP only. Do not overwrite Andy Commerce or other plugins.
4. Confirm independent 系统 → 后台登录 L3 entry and original 社交登录 Google controls.
5. Login alias and Hide Legacy are opt-in on an existing site. Before hiding, open secure login in a separate private browser and verify.
6. Verify anonymous legacy login and admin paths return 404 without redirect to secret URL; authenticated admin, admin-ajax/post, and Woo /my-account/ work.
7. Secure URL must provide noindex/nofollow/noarchive and no-store; never include the alias in robots.txt.
8. No 2FA change; no DB migration; updater DB 1.4.0 and runtime DB 1.5.0 unchanged.
9. Publication is not authority to install on legacy www.beyourlover.com; that is subject to BYL full-site cutover approval.
