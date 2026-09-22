# Andy Core v1.7.0 — Rollback Guide

## Preferred rollback order

1. If a problem is isolated to Article TOC, disable the `article_toc` module first.
2. If a problem is isolated to Landing Pages, disable the `landing_pages` module after confirming the site does not currently depend on its public routes.
3. If a code rollback is required, use the existing Andy Core updater backup/restore path to restore the exact pre-upgrade plugin package.

## Article TOC rollback

Disabling Article TOC results in:

- zero Article TOC runtime boot
- zero Article TOC CSS/JS enqueue
- zero Andy Core TOC DOM generation
- preserved Article TOC settings

A site with an existing legacy/theme TOC can continue using that implementation until a later controlled ownership migration.

## Landing Pages rollback

Landing Page content is stored as WordPress posts using `yby_landing_page`.

Disabling the module stops its runtime/admin registration but does not delete Landing Page posts or settings.

Do not delete Landing Page content as part of code rollback.

## Data safety

- v1.7.0 introduces no database migration.
- Runtime database version remains `1.5.0`.
- Updater compatibility database version remains `1.4.0`.
- Docs content, taxonomy, SEO metadata, and R2 mappings are not rewritten by the v1.7.0 Foundation changes.
- Module settings are retained when modules are disabled.
