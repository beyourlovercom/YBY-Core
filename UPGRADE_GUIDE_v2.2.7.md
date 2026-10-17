# Andy Core v2.2.7 — BYL Dev native WP-Cron Canary UAT plan (not yet approved for execution)

## Prepublication
1. Check exact source HEAD, GitHub 4/4 CI and eight-asset candidate signatures (SHA256, detached Ed25519, signed metadata, WordPress ZIP structure).
2. Publish a new `v2.2.7` GitHub **Prerelease** only with separate Owner authorization, leaving `v2.2.6` published Stable/Latest and original signed assets unchanged. An Actions artifact alone is not a publicly discoverable Canary.
3. Dev UAT primary target: `dev.beyourlover.com` at **2.2.6 / Canary / automatic updates OFF**. `dev.ybybottle.com` remains **2.2.2 / Canary / automatic updates OFF**, strictly isolated.

## Owner-gated native auto-update proof
4. With separate Owner authorization, enable Andy Core automatic updates ON **only on BYL Dev** and confirm Canary channel. Do not change Bottle Dev or any Production site. Record baseline version, active plugin, current WP-Cron updater eligibility and code-backup health.
5. Use the normal WordPress native WP-Cron `wp_maybe_auto_update` execution path under a bounded and authenticated Dev-only test; **never** use WP-CLI `wp plugin update`, manual ZIP upload, or file replacement as substitute for real auto-install.
6. Confirm WordPress installs **exact signed 2.2.7** without human clicking Update Now; Ed25519/SHA256/ZIP health/rollback evidence PASS; no equal-version update banner after two refreshes. Confirm no other plugin, theme, translation or WordPress Core was auto-updated during the test. Run compact Chinese L2/L3 admin visual smoke.
7. Restore BYL Dev Andy Core automatic-updates option to its prior **OFF** state unless Owner separately authorizes leaving it enabled. Bottle Dev automatic updates must stay OFF throughout.
8. On failure stop, retain safe logs and use only separately authorized compatible code-only rollback; no real orders, payments, database mutations beyond what WordPress native plugin updater intrinsically requires, SMTP sends or WWW changes.

## Separate release gates
9. Only after Dev Owner UAT PASS discuss exact-head GitHub main merge and a separately approved newer Stable version (do not modify v2.2.6). Production auto-update requires further controlled validation and Owner authorization; existing Irrigation Stable/ON site must not receive a new Stable as a side effect of Dev Canary UAT.
