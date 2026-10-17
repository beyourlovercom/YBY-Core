# Andy Core v2.2.8 — U3B signed Canary candidate (NOT PUBLIC)

## Purpose
- Version-only successor to v2.2.7. No new application feature or runtime policy change.
- Prepare a true WordPress WP-Cron / WP_Automatic_Updater automatic installation **2.2.7 -> 2.2.8** on `dev.beyourlover.com`, ONLY after separate Owner publication and UAT authorization.
- Preserve the v2.2.7 signed channel/host allowlist, explicit update opt-in, priority-1000 Hostinger background update exception, and prohibition on background updates to unrelated plugins, WordPress Core, themes and translations.
- Preserve runtime database 1.5.0 and updater compatibility database 1.4.0, plus Mail OS, WooCommerce, checkout, orders, payments, SMTP and Hostinger managed MU code.

## Separate gates
1. Verify exact source HEAD, CI 4/4 SUCCESS, eight signed GitHub Actions Artifact files, ZIP SHA256, pinned Ed25519 detached verification, archive layout, historical updater compatibility and rollback documentation.
2. A GitHub Actions Artifact is NOT a public Canary. Public v2.2.8 Canary Prerelease, new Tag and eight assets require distinct Owner approval. Stable/Latest v2.2.6 and published Canary v2.2.7 must remain unchanged.
3. Real WP-Cron U3B requires another Owner approval to temporarily set **BYL Dev only** to auto ON and observe native scheduled background installation. Manual install, Update Now, WP-CLI plugin update and a temporary MU shim do not constitute U3B PASS.
4. Keep Bottle Dev Canary/OFF, both Production sites and BYL WWW untouched. Do not merge PRs or edit Hostinger managed MU.
5. After authorized U3B testing, return BYL Dev auto OFF. A successful candidate build is not actual automatic-install acceptance.
