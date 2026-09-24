# Andy Core v1.9.0 — Rollback Guide

## Preferred rollback order

1. If an Addon is involved in the observed issue, leave business data untouched and first isolate whether the issue is Core compatibility or the Addon's own runtime.
2. If the Core upgrade itself must be rolled back, restore the exact pre-upgrade Andy Core package through the governed updater backup / restore path.
3. Do not delete Addon data or settings as part of an ordinary Core rollback.

## Addon safety

- v1.9.0 does not automatically install, activate, deactivate, or delete Addons.
- Rolling back Core does not require deleting an installed Addon.
- If an Addon requires Core >=1.9.0, it may correctly report incompatible after Core rollback; do not force-enable incompatible runtime.

## Commerce boundary

Andy Commerce is a separate Addon stream. Do not copy Commerce runtime into Andy Core to work around a rollback.

Historical pre-1.9 provider-specific Connector behavior remains unchanged by this release.

## Data and schema

- v1.9.0 introduces no database migration.
- Runtime database version remains `1.5.0`.
- Updater compatibility database version remains `1.4.0`.
- No data cleanup is required for Addon Registry rollback.
