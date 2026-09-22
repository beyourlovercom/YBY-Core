# Andy Core v1.7.0 — Upgrade Guide

## Before upgrade

- Confirm the existing Andy Core backup/rollback path is healthy.
- Record the currently enabled module list.
- If the site has a theme-owned article TOC, leave Article TOC OFF until the site-specific migration/UAT is complete.
- Do not remove BetterDocs or other site-specific content providers solely because of this code upgrade.

## Upgrade

1. Install the signed Andy Core v1.7.0 package through the governed updater/deployment flow.
2. Verify plugin activation and System Status.
3. Verify existing enabled/disabled module choices.
4. Verify `Andy Content` is present and Docs remains reachable through its stable routes.
5. Verify Landing Pages appears under `Andy Content` and that existing site modules were not re-enabled unexpectedly.
6. Leave Article TOC OFF unless the target site has completed its own TOC compatibility UAT.
7. When enabling Article TOC, verify at least one real Post at desktop/tablet/mobile widths before removing any legacy theme TOC runtime.

## Data and schema

- Runtime database version remains `1.5.0`.
- Updater compatibility database version remains `1.4.0`.
- No database migration is required.
- Module settings use non-destructive option storage and remain when a module is disabled.

## Article TOC migration

For sites with an existing theme/runtime TOC:

1. Enable Article TOC only in a controlled environment.
2. Verify legacy duplicate detection first.
3. Test Andy Core ownership with the legacy TOC disabled in the controlled environment.
4. Confirm desktop Floating TOC, Inline Summary, Scroll Spy, mobile behavior, and overflow.
5. Only after site-specific Owner UAT should the old theme TOC generation/runtime be removed.
