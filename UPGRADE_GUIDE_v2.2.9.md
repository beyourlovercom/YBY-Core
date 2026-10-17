# Andy Core v2.2.9 — U3D genuine Hostinger-unattended auto-update UAT

## Proven prerequisites
- U3B isolated native WordPress updater 2.2.7 -> 2.2.8 PASS; Dev remained healthy and auto OFF.
- U3C hPanel custom Cron scheduled `--check` at 05:00:10Z and produced its own check-only PASS output. The final check-only schedule is `0 */4 * * *` (UTC).
- U3C source Draft PR #156 is **not** installed as part of this plugin ZIP; its shell runner is outside webroot, exact source/target guards pinned at 2.2.8 -> 2.2.9.
- BYL Dev is intended sole test site: currently Andy Core 2.2.8 Active / Canary / auto OFF. WordPress Core 7.1.3, runtime DB 1.5.0.

## Before any site-change UAT
1. Sign 2.2.9 as a distinct immutable candidate from exact-source HEAD; CI 4/4, 8/8 assets, pinned Ed25519 and SHA256 verification.
2. **Get separate Owner permission** to publish a public Prerelease tagged v2.2.9 (NOT Latest). Keep v2.2.6 Stable and earlier Canary tags intact.
3. **Get separate Owner permission** to temporarily change BYL Dev only to auto ON and hPanel U3C job from `--check` to `--execute` (no global `wp-cron.php` dispatch). Prove actual unattended Hostinger invocation; do not call the runner manually for the successful test. Keep an exact 2.2.8 code-only rollback snapshot outside webroot and a before/after plugin/theme/Core list.

## Acceptance
- Hostinger provider cron output shows autonomous `--execute` and the native `wp_maybe_auto_update` event installing **exactly 2.2.9** from the signed Canary.
- Confirm no other plugin, theme, translation, WordPress Core or DB migration changes; verify downloaded candidate ZIP SHA256 and installed package main-file hash.
- Confirm Andy Core 2.2.9 Active, normal homepage HTTP 200, WordPress health, and built-in version 2.2.8 code-only backup. Confirm automatic update setting is restored OFF by the runner.
- Remove or revert temporary U3D execution scheduler to read-only `--check`, avoid a recurring UAT attempt; do not change Bottle Dev or WWW.
- On any failure, stop, preserve provider/output evidence and offer bounded, separately approved rollback.

**Candidate preparation itself authorizes no website mutations or Cron execution.**
