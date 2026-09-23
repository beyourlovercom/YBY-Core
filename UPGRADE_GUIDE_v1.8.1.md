# Andy Core v1.8.1 — Upgrade Guide

## Before upgrade

- Confirm the existing Andy Core signed-updater backup/rollback path is healthy.
- Record the target site's current GTM4WP version and existing GTM container behavior.
- Do not remove or disable GTM4WP.
- Do not manually add a second GTM/GA4/Ads stack for this upgrade.

## Upgrade behavior

The `analytics` module is **default OFF**. Upgrading the plugin alone must preserve the site's historical tracking behavior.

## Validation

1. Upgrade Andy Core through the governed signed package flow.
2. Verify the site and existing inquiry/lead/Thank You tracking behavior before enabling Analytics.
3. Confirm only the expected GTM container is present.
4. Confirm GTM4WP is in the supported V1 range (`>=2.0.0 <3.0.0`).
5. Enable Analytics only on the approved target site.
6. Verify provider compatibility reports supported/runtime-ready.
7. Verify duplicate-GTM diagnostics report the expected single container and do not remove any tag.
8. Verify Consent remains `respect_existing` and Andy Core does not mutate the CMP/Google consent state.
9. Verify existing business events still appear through the historical `window.YBYTracking` API.
10. If validation fails, disable Analytics first; the module is designed to return to the historical tracking path without a data migration.

## Data and schema

- Runtime database version remains `1.5.0`.
- Updater compatibility database version remains `1.4.0`.
- No database migration is required.
- Existing Project/Page tracking metadata is not migrated or rewritten.

## Production policy

Production deployment remains a separate explicit gate and is intentionally deferred while v1.9.0 development continues.
