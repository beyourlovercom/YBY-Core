# Andy Core v2.2.5 — proposed Dev-only manual Update Now UAT

**Planning document, not current deployment authorization.**

1. Before publishing a separately approved signed Canary v2.2.5, verify exact source HEAD, four CI checks, ZIP SHA256 and detached Ed25519 signature using the pinned public verification key.
2. Isolate any Canary-auto-update Dev hosts that must not be upgraded inadvertently. `dev.ybybottle.com` normally has auto updates ON; only turn it OFF during a separately authorized release window, and restore it afterward.
3. Confirm intended Dev host, plugin currently installed and active, current database schema and previous-version code rollback snapshot. Do not use a manual ZIP replacement as native WordPress update proof.
4. Use the WordPress Plugins → Andy Core → Update Now control to upgrade a designated Dev install when explicitly authorized.
5. After successful install, check `update_plugins` contains no same-version Andy Core `response`, then perform a fresh `wp_update_plugins()` check; the banner must not reappear and an unrelated plugin update must not be deleted.
6. Verify signed updater rollback evidence, native L2 persistent expansion, concise Chinese L2/L3 labels and correct permissions. Test downgrade/equal-version package rejection in an isolated harness—not by repeated real upgrade clicks.
7. Owner visual UAT and an independent Stable/main/deployment approval remain separate gates. No real order, payment, mail sending, data migration or device restarts.
