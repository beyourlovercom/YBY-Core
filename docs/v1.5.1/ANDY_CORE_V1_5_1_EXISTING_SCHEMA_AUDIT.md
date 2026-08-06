# Andy Core v1.5.1 Existing Schema Audit

## Stage 0 Baseline

- Product stable version: `1.5.0`
- Development version: `1.5.1-dev`
- Database version before WP-151C: `1.2.0`
- Authoritative release commit: `80a04c14da6106e58a837345cdc9bfc3de804984`

## Version Metadata Finding

The v1.5.0 release commit carried stable `1.5.0` values in the plugin header and `YBY_CORE_VERSION`, while the development branch initially retained release-freeze metadata. Stage 0 updates the active plugin header, runtime version constant, `VERSION.md`, `README.md`, and `CHANGELOG.md` to `1.5.1-dev`. Historical release notes, tags, and release artifacts remain unchanged.

`YBY_DATABASE_VERSION` remains `1.2.0`; the database declaration will not advance until WP-151C implements and tests the idempotent migration.

## Scope

The complete Lead schema, migration entry point, and admin architecture audit are Stage 1 deliverables. No schema or production data changes occur in Stage 0.
