# Andy Core v2.2.7 — BYL Dev Hostinger-compatible automatic updater UAT gates

## Prepublication (this PR is candidate-only)
1. Check exact GitHub PR SHA, 4/4 CI, signed 8-file Actions candidate (ZIP SHA256, detached Ed25519 and signed metadata), WordPress ZIP layout and backwards compatibility.
2. Separately obtain Owner approval before creating public GitHub tag and `v2.2.7` **Prerelease / Canary**. Keep `v2.2.6` Stable/Latest unchanged; never overwrite its original release ZIP/signature.
3. Dev test target is **`dev.beyourlover.com` (BYL Dev)**. The actual read-only preflight on 2026-10-17 showed Andy Core **2.2.6 ACTIVE**, channel **Canary**, auto **OFF**, schema 1.5.0, and Hostinger Smart Auto Updates 1.0.8 registering global `automatic_updater_disabled=__return_true`. **No Andy-compatible Dev MU shim** exists on BYL Dev.
4. `dev.ybybottle.com` remains **2.2.2 / Canary / auto OFF**, including its historical Dev-only shim. Never change Bottle Dev automatic-update setting in this UAT.

## Critical bootstrapping distinction: 2.2.7 cannot update itself into place
The priority-1000 Hostinger auto-update override **first exists in 2.2.7 code**. Installed 2.2.6 cannot execute that new logic. Therefore simply switching BYL Dev auto ON and publishing v2.2.7 will **not** make native WP-Cron install v2.2.7, because Hostinger's global MU plugin continues blocking 2.2.6. Do not report a manually triggered Update Now or temporary shim path as proof of built-in 2.2.7 autonomous upgrades.

## Phase A — BYL Dev-only v2.2.7 bootstrap (Owner approval required)
5. After the public signed Canary exists and Owner approves, use BYL Dev WordPress native **Update Now** to install 2.2.7 (manual bootstrap), after verifying the signed update source and existing rollback readiness. No production site, BYL WWW, Bottle Dev or managed Hostinger MU source may be changed.
6. Verify v2.2.7 Active with its new background-update filter hooks registered at priority 1000; ensure the signed channel/host/auto safeguards and existing L2/L3 admin UI work. Keep Andy auto OFF after this phase. A temporary Dev-only MU shim is an alternative explicitly authorized historical bootstrap test but would **not** prove the new built-in updater; do not add such a shim by default.

## Phase B — genuine built-in automatic install (separate version + Owner gate)
7. Prepare a distinct newer signed **v2.2.8 Canary** (or a later higher version) with explicit Owner approval and unchanged guard contracts. GitHub PR, source and signature are separately versioned; never republish or modify v2.2.7 assets to simulate a version increase.
8. With approval, enable Andy auto **ON only on BYL Dev**, keep channel Canary, and inspect native WordPress background-updater eligibility, scheduled WP-Cron path and existing code-only backup readiness.
9. Allow normal WordPress `wp_maybe_auto_update` WP-Cron to install the newer signed version **without any manual ZIP upload, Update Now click, WP-CLI `wp plugin update` or direct file replacement**. Preserve actual hook/upgrade evidence; confirm no other plugin, WordPress Core, theme or translation auto-installs.
10. Verify new version Active, signed metadata/ZIP SHA256/Ed25519 and post-install backup health PASS, no equal-version update notice after two refreshes. Return BYL Dev auto to its previous **OFF** setting unless separately approved; Bottle Dev must remain auto OFF throughout.

## Failure/rollback and separation
11. On any runtime failure, STOP and preserve non-sensitive evidence; code-only rollback requires separate Owner approval and never restores DB, WooCommerce, orders, payments, SMTP or real customer data.
12. GitHub main merges of PR #153/#154, any Stable promotion, Production auto-update enablement and Web-PHP Sodium changes remain **independent, separately authorized gates**. The existing Stable V2.2.6 production install is outside this Canary UAT.
