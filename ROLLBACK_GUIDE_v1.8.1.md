# Andy Core v1.8.1 — Rollback Guide

## Preferred rollback order

1. If Analytics was enabled, disable the `analytics` module first.
2. Recheck that the historical GTM4WP / `window.YBYTracking` path is functioning.
3. If a code rollback is still required, restore the exact pre-upgrade Andy Core package through the governed updater backup/restore path.

## GTM and consent safety

- Do not delete GTM4WP or remove a GTM container as part of ordinary plugin rollback.
- Do not manually rewrite GTM4WP settings unless a separately approved site-specific remediation requires it.
- Do not invent or overwrite consent state during rollback.
- Duplicate-GTM diagnostics are read-only and require no data cleanup.

## Data and schema

- v1.8.1 introduces no database migration.
- Runtime database version remains `1.5.0`.
- Updater compatibility database version remains `1.4.0`.
- Existing Project/Page analytics metadata remains unchanged.
