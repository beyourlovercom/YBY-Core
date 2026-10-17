# Andy Core v2.2.10 — Stable candidate upgrade plan (NOT AUTHORIZED FOR PRODUCTION)

## Baselines
- Integrated main source base: `a580f97b7e1f324b684d15caf0ff179dfaba7781` (merged PR #159).
- Public Stable / Latest: `v2.2.6`; public Canary: `v2.2.9` Prerelease. BYL Dev `2.2.9` Canary, auto OFF; Hostinger isolated Cron `0 */4 * * * --check` unchanged.
- Database: updater compatibility `1.4.0`, runtime schema `1.5.0`. Plugin root must stay `yby-core/yby-core.php`.

## Code-only preparation
- Produce exact-head Draft PR from integrated main, with only `2.2.10` header/readme/builder/CI/release docs and inert Mail P9 source-blob pin changes.
- Run all four GitHub PR checks on the actual base+head synthetic merge; require signer secret, Ed25519 signature, SHA256, ZIP, backwards compatibility and eight published-format artifact files. **Do not publish artifacts to GitHub Releases**.
- Confirm diff from current published Stable v2.2.6, not just from main. This includes previously integrated Creator read-only and Hostinger updater code that was not part of the v2.2.6 Stable release.

## Future Owner-gated Production deployment (not part of this stage)
1. Reconfirm current main and live WordPress plugin versions and channels. Inspect integration-specific module toggles, Creator permissions, Mail OS status and any site-specific Plugin/Add-on constraints read-only.
2. Obtain separate Owner authorization to merge Stable candidate PR, create a fresh signed immutable v2.2.10 public Stable release, and roll out site-by-site.
3. Before any WordPress install, preserve exact installed code version with bounded outside-webroot backup and read-only active plugin/theme/Core/DB snapshots. Keep automatic updates OFF until explicitly approved.
4. Verify Ed25519/SHA256 from the release repository, standard `yby-core` plugin path and no unintended business operations. Audit health, WordPress/DB version, other plugin/theme inventories, logs and restore original policy.
5. Do not publish Stable/Latest, turn ON auto-update, run a global WordPress cron queue, deploy WWW, merge another PR, or perform real orders/payments/emails without their distinct explicit gates.

This document is a release plan, not proof of Production readiness.
