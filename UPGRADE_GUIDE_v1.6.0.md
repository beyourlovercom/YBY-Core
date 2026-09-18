# Andy Core v1.6.0 — Upgrade Guide

## Before upgrade

- Confirm the existing site backup/rollback path is healthy.
- Leave Docs OS disabled unless the site has completed its own canonical migration UAT.
- Do not uninstall BetterDocs as part of the code upgrade.

## Upgrade

1. Install the signed Andy Core v1.6.0 package through the existing native updater.
2. Verify plugin health and existing modules.
3. Verify Docs OS remains default OFF on sites that have not explicitly enabled it.
4. For migrated sites, enable Docs OS and canonical ownership only through the approved site-specific gate.

No database migration is required. Existing posts, taxonomy, SEO metadata and media remain in place.
