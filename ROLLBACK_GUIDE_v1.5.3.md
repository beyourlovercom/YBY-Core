# Andy Core v1.5.3 Rollback Guide

Rollback target: last verified Andy Core v1.5.2 package

## When to roll back

Use rollback only for an in-scope production failure that cannot be safely corrected in place during the approved maintenance window.

## Required rollback assets

- The last verified v1.5.2 plugin package and SHA256.
- The pre-upgrade WordPress database backup.
- The pre-upgrade plugin/database versions and business-data verification record.

## Plugin rollback

1. Disable the v1.5.3 plugin if required by the approved WordPress operations flow.
2. Restore the verified v1.5.2 plugin package; do not use an ad hoc Local or extracted development folder.
3. Load WordPress and verify the v1.5.2 plugin/runtime is healthy.
4. Re-check Inquiry, Sticky CTA, Brand, Bottle, Google authentication, and other existing runtime surfaces.

## Database handling

The v1.5.3 Connector schema is additive. Do not manually drop Connector tables as a routine plugin rollback step. A v1.5.2 code rollback may re-establish its own `1.3.0` database metadata while leaving later additive tables unused.

If database contents or schema must be returned exactly to the pre-upgrade state, restore the verified pre-upgrade database backup instead of hand-editing tables or version options.

## After rollback

Record the restored plugin hash, database state, operator, time, reason, and verification results before reopening production traffic or resuming deployment work.
