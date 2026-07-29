# Andy Core v1.4.0 Rollback Guide

## Rollback target

The approved rollback target is the exact previously deployed YBY Core v1.3.2 plugin directory or the immutable v1.3.2 release package.

## When to rollback

Rollback if the upgrade causes:

- plugin activation failure
- PHP fatal error
- missing WordPress admin menu
- missing or duplicated Inquiry Modal runtime
- duplicate Lead creation
- Case ID mismatch
- Thank You, WhatsApp, email, or tracking regression
- unexpected database version change

## Rollback procedure

1. Put the site into the approved maintenance or controlled deployment state when required.
2. Move the v1.4.0 `yby-core` directory out of the live plugin path.
3. Restore the validated v1.3.2 `yby-core` directory atomically.
4. Restore the previous activation state.
5. Confirm plugin version `1.3.2` and database version `1.1.0`.
6. Flush only relevant WordPress and page caches.
7. Recheck homepage, active Landing Pages, Thank You Page, Modal, Sticky CTA, Lead, Case ID, email, and tracking health.

## Database rule

Andy Core v1.4.0 does not change the database schema. A database rollback should not normally be required.

Do not manually change the stored database version unless a separate verified database incident requires it.

## Retained technical identity

Because v1.4.0 preserves the `yby-core` plugin path and all legacy technical identifiers, rollback restores the previous plugin directory without page, shortcode, API, or database migration.
