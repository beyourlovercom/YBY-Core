# Andy Core v1.5.1 Risk Register

## R-001 Version Metadata Drift

- Status: resolved for the development baseline
- Finding: active plugin and documentation metadata identified the v1.5.0 release-freeze state while the feature branch begins v1.5.1 development.
- Resolution: active declarations now use `1.5.1-dev`; stable product version remains `1.5.0` and database version remains `1.2.0`.
- Protected: v1.5.0 tag, release notes, release artifacts, REST, Case ID, email, Thank You, Bottle, Irrigation, Social Login, and Google One Tap contracts.

## R-002 Database Migration Boundary

- Status: open until WP-151C
- Risk: advancing the database version without the tested idempotent migration could create an installation mismatch.
- Control: keep `YBY_DATABASE_VERSION` at `1.2.0` until the management tables, indexes, migration tests, and rollback behavior are implemented.

## R-003 Schema Assumptions

- Status: open until WP-151A
- Risk: planning fields may not match the installed `yby_leads` schema.
- Control: audit the live repository schema before writing migration or management code; do not delete, rename, or bulk rewrite Lead data.
