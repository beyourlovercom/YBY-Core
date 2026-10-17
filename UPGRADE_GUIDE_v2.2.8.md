# Andy Core v2.2.8 — U3B native WP-Cron Canary UAT Plan (not approved for site install)

## Baselines
- BYL Dev `dev.beyourlover.com`: v2.2.7 manually installed in U3A, Active; channel Canary, automatic updates OFF; runtime schema 1.5.0, Hostinger MU present.
- Bottle Dev `dev.ybybottle.com`: v2.2.2 Canary, auto OFF; MUST remain OFF, not a U3B test target.
- Public Stable/Latest is v2.2.6. Public Canary v2.2.7 and its eight signed files must NOT be replaced.

## Pre-UAT (separate Owner gates)
1. Verify independently signed v2.2.8 GitHub Actions candidate at the exact PR HEAD: CI, Ed25519, ZIP SHA256, package top-level path, backward compatibility and rollback notes.
2. Require separate Owner approval before publishing v2.2.8 as public Prerelease **not Latest**, with its own tag and eight signed files.
3. Require another separate Owner approval for a genuine BYL Dev-only automatic upgrade; prepare bounded rollback, native WP-Cron telemetry, pre-upgrade versions and WordPress health. Do not alter Hostinger managed MU or any other site.

## Genuine automatic installation — ONLY after U3B approval
4. Temporarily opt into Andy Core automatic updates **on BYL Dev only**, while keeping Canary channel; verify conditional Hostinger `automatic_updater_disabled` / `auto_update_plugin` exception and non-target isolation.
5. Let genuine scheduled **WP-Cron** invoke `wp_maybe_auto_update` -> `WP_Automatic_Updater` to install 2.2.7 -> 2.2.8 from signed public Canary. Manual ZIP upload, WordPress Update Now, WP-CLI plugin update, file replacement or extra MU shim do NOT prove U3B.
6. Preserve timestamps, update metadata and signature/ZIP checks, plugin and non-target versions, WP-Cron/upgrader log, website health, stale equal-version update absence and rollback evidence.
7. Return BYL Dev Andy Core auto to OFF promptly; confirm Bottle Dev still Canary/OFF.

## Failure boundaries
- On error or absent auto-install: stop, preserve non-sensitive evidence and do not claim PASS. Any code-only rollback needs separately approved scope.
- No WordPress Core/theme/unrelated plugin updates, database migrations, customer data changes, payments, orders, mail, Stable promotion, Production/WWW changes, PR merges or READY device restarts in this candidate stage.
