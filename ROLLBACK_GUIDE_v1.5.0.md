# Andy Core v1.5.0 Rollback Guide

## Rollback target

Use the exact previously deployed plugin backup and database backup captured before the v1.5.0 deployment.

## When to rollback

Rollback for activation failure, PHP fatal errors, authentication regression, duplicate Leads, Case ID mismatch, wrong Thank You routing, cross-Case WhatsApp leakage, email identity leakage, or failed database migration.

## Procedure

1. Enter the approved controlled deployment state.
2. Move the v1.5.0 `yby-core` directory out of the live plugin path.
3. Restore the validated previous `yby-core` directory atomically.
4. Restore the pre-upgrade database backup when rolling back from schema `1.2.0` to `1.1.0`.
5. Restore the previous activation state and clear only relevant caches.
6. Verify plugin version, database version, site identity, Lead intake, Case ID, Thank You, WhatsApp, email, and Google login health.

Do not manually remove the `page_profile` column or change the database version option outside a verified database rollback.
