# Andy Core v2.2.6 — BYL Dev manual Canary UAT

1. Confirm GitHub source exact HEAD + CI 4/4 SUCCESS. Verify the new signed 8-asset Canary release ZIP SHA256, metadata schema and Ed25519 with the existing trusted public key.
2. Preflight `https://dev.beyourlover.com`: Andy Core 2.2.5 ACTIVE, Canary, auto-upgrade OFF; DB runtime 1.5.0. Confirm existing compatible prior-code rollback snapshot. Confirm `dev.ybybottle.com` Canary auto-upgrade remains OFF during this release window.
3. **Owner** uses WordPress Plugins -> Andy Core -> **Update Now** to install 2.2.6. Do not force via SSH, bulk plugin updater or manual file replacement as substitute for native UAT.
4. Refresh Plugins page, verify **installed 2.2.6** is active, **no 'upgrade to 2.2.6' banner**, and plugin update transient has no `yby-core/yby-core.php` response for identical/older version. Reopen page / fresh update scan to ensure it stays gone.
5. Verify L2 menu stays expanded, compact Chinese labels, L3 highlight, PHP lint, WordPress core and relevant site smoke checks. Genuine future new Canary should remain discoverable; other plugins' available updates must not disappear.
6. If version mismatch or health regression: stop, preserve diagnostics and use signed updater's compatible code-only rollback under separate authority; do not touch business data.
7. Record Owner UAT evidence on GitHub PR; restore Bottle Dev auto-update to original ON only after Owner UAT and controlled release decision, since it can then adopt the new public Canary. Never change PR/main, Stable or WWW without separate approval.
