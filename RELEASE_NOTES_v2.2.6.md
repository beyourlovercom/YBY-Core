# Andy Core v2.2.6 — Canary Prerelease

## What changed

- Fix the Owner-reproduced WordPress plugin-row defect: installed Andy Core 2.2.5 incorrectly displayed an available **2.2.5** update even after native Update Now.
- Filter cached WordPress plugin updates on **read** (`site_transient_update_plugins`), complementing the prior **write**-time stale-offer filter.
- Remove only the invalid, older, same-version, or channel-excluded Andy Core update offer; preserve every unrelated plugin and true newer Canary offer. Read-time guard performs no network request or transient writes.
- Keep Ed25519/SHA256 package verification, fail-closed install policy, code-only rollback, existing Chinese menus and runtime compatibility schema.
- No changes to database schema (runtime 1.5.0, updater compatibility 1.4.0), WooCommerce, checkout/payment, orders, SMTP/Mail P9 workers, production URLs, or roles.

## Release governance

- Canary publication and **BYL Dev manual WordPress Update Now UAT** approved by Owner 2026-10-16. No auto-install on BYL Dev; Bottle Dev Canary automatic updates remain isolated OFF until follow-up.
- Private source SHA, four GitHub CI checks, all eight signed artifacts, SHA256 and Ed25519 must pass before public prerelease. Set **Prerelease**, not Latest / Stable, with tag `v2.2.6` in `beyourlovercom/andy-core-release`.
- Existing v2.2.5 public release remains immutable. PRs stay Draft/Open and unmerged; WWW and Stable unchanged.
