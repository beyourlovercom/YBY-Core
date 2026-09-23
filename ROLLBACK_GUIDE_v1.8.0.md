# Andy Core v1.8.0 — Rollback Guide

## Preferred rollback order

1. Stop ERP Content Publishing calls first.
2. If only a canary article is affected, keep the plugin in place and correct/restore that bounded WordPress post through the approved content workflow.
3. If a code rollback is required, use the existing Andy Core updater backup/restore path to restore the exact pre-upgrade plugin package.

## Content safety

- Do not delete real article posts as part of plugin code rollback.
- ERP binding metadata on already-created posts is retained unless a separately approved content cleanup explicitly removes it.
- The Connector idempotency/audit foundation should not be manually cleared during ordinary rollback.

## Data and schema

- v1.8.0 introduces no database migration.
- Runtime database version remains `1.5.0`.
- Updater compatibility database version remains `1.4.0`.
- Existing Docs, Landing Page, Inquiry, Email OS and Connector data are not rewritten by this release identity change.
