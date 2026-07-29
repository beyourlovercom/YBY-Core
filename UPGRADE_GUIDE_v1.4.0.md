# Andy Core v1.4.0 Upgrade Guide

## Supported upgrade

Upgrade in place from YBY Core v1.3.2 to Andy Core v1.4.0.

## Important identity rule

Do not rename the plugin directory or main plugin file during this release.

The installed path must remain:

`wp-content/plugins/yby-core/yby-core.php`

This preserves the existing WordPress plugin identity and activation state.

## Pre-upgrade checks

1. Confirm the active plugin version is `1.3.2`.
2. Confirm the database version is `1.1.0`.
3. Back up the active `yby-core` plugin directory.
4. Record the plugin activation state.
5. Confirm existing Inquiry Modal and Sticky CTA counts.
6. Confirm homepage, active Landing Pages, and Thank You Page return HTTP 200.

## Upgrade

1. Validate the exact v1.4.0 release ZIP and checksum.
2. Extract to a separate staging directory.
3. Run PHP lint against every staged PHP file.
4. Replace the live `yby-core` directory atomically; do not overlay-copy individual files.
5. Preserve the current activation state.
6. Flush only relevant WordPress and page caches.

## Post-upgrade checks

Confirm:

- WordPress Plugins displays `Andy Core` version `1.4.0`.
- The plugin remains active.
- The admin top-level menu displays `Andy Core`.
- The settings screen heading displays `Andy Core`.
- `YBY_CORE_VERSION` is `1.4.0`.
- `YBY_DATABASE_VERSION` remains `1.1.0`.
- existing shortcodes and frontend selectors still work.
- Lead, Case ID, Thank You, WhatsApp, email, and tracking behavior remain unchanged.
- no duplicate plugin directory or duplicate Modal runtime exists.

## Prohibited during this upgrade

- Do not create an `andy-core/` plugin directory.
- Do not install v1.4.0 beside v1.3.2.
- Do not rename `YBY_*`, `yby_*`, `[yby_*]`, `data-yby-*`, `.yby-*`, REST routes, options, or database tables.
- Do not change database version manually.
- Do not modify Bricks, themes, GTM, GA4, or Google Ads as part of the plugin upgrade.
