# Andy Core v1.5.3 Upgrade Guide

Upgrade target: Andy Core 1.5.3
Database target: 1.4.0
Previous stable baseline: 1.5.2 / DB 1.3.0

## Before upgrade

1. Back up the current Andy Core plugin package and WordPress database.
2. Confirm the source package is the official v1.5.3 GitHub Release artifact and verify its SHA256.
3. Confirm WordPress can load the existing v1.5.2 site without critical errors before replacement.
4. Record current plugin version, database version, active providers, and key business-data counts needed for rollback verification.

## Upgrade

1. Replace the existing `yby-core` plugin with the official `andy-core-v1.5.3.zip` package through the approved WordPress operations flow.
2. Activate/load Andy Core normally. The database installer advances `yby_database_version` to `1.4.0` after required tables and indexes are present.
3. Do not manually create or edit Connector tables; the migration is governed by `YBY_Database` and is idempotent.
4. Do not copy Local/UAT settings, Shared Secrets, or test credentials into another environment.

## Post-upgrade verification

- Plugin header and `YBY_CORE_VERSION` report `1.5.3`.
- Stored database version reports `1.4.0`.
- Existing Inquiry, Sticky CTA, Brand, Bottle, Google authentication, and frontend behavior remain available.
- WP-API settings load normally and provider status is truthful for the target site.
- Connector tables/indexes exist and existing business data counts remain unchanged unless normal site activity occurred during the maintenance window.
