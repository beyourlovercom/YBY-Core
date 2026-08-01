# Andy Core v1.5.0 Upgrade Guide

## Supported upgrade

Upgrade in place from Andy Core v1.4.0 or an approved v1.5.0 development build to Andy Core v1.5.0.

The installed plugin path must remain:

`wp-content/plugins/yby-core/yby-core.php`

## Pre-upgrade

1. Back up the active `yby-core` plugin directory and WordPress database.
2. Record plugin activation, Core version, database version, and governed site identity settings.
3. Record Social Login settings without exposing the Google Client ID in logs.
4. Confirm the site is healthy and no duplicate Core plugin directory exists.

## Upgrade

1. Verify the immutable v1.5.0 ZIP and SHA-256.
2. Extract and lint the staged package.
3. Replace the live `yby-core` directory atomically.
4. Preserve activation and existing site configuration.
5. Allow the idempotent database installer to upgrade `yby_database_version` to `1.2.0` and add the Lead `page_profile` column.
6. Clear only relevant WordPress and page caches.

## Post-upgrade

Confirm Andy Core `1.5.0`, database `1.2.0`, one active Core plugin entry, existing Case ID and inquiry behavior, Google login configuration, page-specific Thank You routing, Case-scoped WhatsApp behavior, and governed email identity.

Do not modify Bricks, themes, GTM, GA4, Google Ads, SMTP, or other websites as part of the Core upgrade.
