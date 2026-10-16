# Andy Core v2.2.5 — draft Canary candidate release notes

**NOT published / NOT approved for installation.** Source-only continuation from v2.2.4 Canary.

## What changed
- Fix #143: WordPress's cached `update_plugins.response` must not present a same-version or older Andy Core release after successful Update Now.
- Clear the update-plugins site transient after a successful signed installation health check so a future WordPress update scan can rebuild accurate offers.
- Preserve all unrelated WordPress plugin update offers and retain fail-closed package/version/signature verification. The downloader continues to reject redundant same-version installation.
- Includes already Owner-accepted WordPress native second-level menu expansion and compact Chinese menu labels from v2.2.4.
- No change to runtime database schema 1.5.0, updater compatibility schema 1.4.0, permission checks, WooCommerce payments, Mail P9 live writing or real SMTP sending.

## Provenance and governance
- Source branch is stacked on PR #143, itself stacked on still-Draft PR #142; both remain unmerged. `main` already carries the earlier #141 integration but not #143.
- Public signed v2.2.4 Canary must stay immutable. Any future public v2.2.5 release requires separate exact-head Owner approval, package SHA256/Ed25519 validation, site-by-site opt-in and genuine WordPress update UAT.
- Current package is intended for GitHub Actions candidate artifact/testing only; no public GitHub Release, Stable promotion or WordPress deployment is authorized.
