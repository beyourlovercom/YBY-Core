# Andy Core v1.6.0 — Rollback Guide

## Docs OS rollback

For a site using the Docs OS canonical bridge, disable the canonical ownership option first. Existing provider rendering immediately resumes because post IDs, slugs, taxonomy and URLs were never recreated.

## Plugin rollback

Use the existing Andy Core updater backup/restore path to restore the exact pre-upgrade code package.

## Data safety

- v1.6.0 introduces no database migration.
- Docs content is not rewritten by the canonical bridge.
- R2 image resolution is render-time only and does not rewrite `post_content`.
- BetterDocs data is preserved during the transition.
