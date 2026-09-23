# Andy Core v1.8.0 — Upgrade Guide

## Before upgrade

- Confirm the existing Andy Core signed-updater backup/rollback path is healthy.
- Confirm the ERP Connector connection key/secret remains configured through the existing governed secret path.
- Do not enable any automated Production article publishing workflow as part of the plugin upgrade.

## Upgrade validation

1. Install the signed Andy Core v1.8.0 package through the governed updater/deployment flow.
2. Verify plugin activation and System Status.
3. Verify existing v1.7.0 modules and admin IA remain available.
4. Run an authenticated `/content/preview` canary and confirm `write_performed=false`.
5. Run one controlled Draft publish canary and confirm the returned WordPress post is Draft and indexing read-back is `noindex`.
6. Replay the same idempotency key and verify no second provider write/post is created.
7. Update the same ERP article with a later accepted layout and verify the same WordPress post ID is reused.
8. Only after Owner UAT may a separately authorized Publish canary set the article to Published/index.

## Data and schema

- Runtime database version remains `1.5.0`.
- Updater compatibility database version remains `1.4.0`.
- No database migration is required.
- Content Publishing writes only the governed target WordPress post and bounded binding/SEO metadata after authenticated publish requests.

## Production policy

Production plugin deployment is intentionally deferred while v1.8.1 and v1.9.0 continue development/UAT.
