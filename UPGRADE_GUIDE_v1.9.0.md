# Andy Core v1.9.0 — Upgrade Guide

## Before upgrade

1. Confirm the existing Andy Core updater backup / rollback path is healthy.
2. Record the current Andy Core version and enabled module set.
3. Do not remove or disable existing site-specific business plugins as part of the Core upgrade.
4. Do not merge or install Commerce functionality into Core for this release.

## Upgrade behavior

v1.9.0 adds the generic Addon Registry and read-only Addons status UI.

The upgrade does not:

- install an Addon;
- activate or deactivate an Addon;
- install WooCommerce;
- add Woo Order Export to Core;
- migrate business data;
- change the runtime database schema.

## Validation

After upgrading:

1. Verify Andy Core loads normally with no Addon installed.
2. Open Andy Core settings and confirm the **Addons** tab is available.
3. Confirm Core-only status is healthy when no Addon is registered.
4. If an approved Addon is present, confirm its installed/active/compatibility/dependency status is truthful.
5. Verify Modules, Content, Docs, Inquiry, Connector, Email and Analytics behavior remain unchanged for the site's enabled set.
6. Verify System Status includes the Addon Registry summary.
7. Confirm no Andy Commerce / Woo Order Export runtime files are present inside the Andy Core package.

## Data and schema

- Runtime database version: `1.5.0`
- Updater compatibility database version: `1.4.0`
- No migration is required.

## Production policy

Production deployment remains separately authorized.
