# Andy Core v1.7.0 — Release Notes

Release date: 2026-09-20

## Highlights

- Adds Registry V2 extension-module contracts with enabled-only boot, dependency fail-close, versioned settings storage, and conditional request-level asset loading.
- Adds the canonical `Andy Content` admin shell and nests Docs under it without changing stable Docs routes or content contracts.
- Restores Landing Pages as the namespaced `yby_landing_page` post type with canonical public URLs at `/lp/{slug}`.
- Adds Article TOC V1 as a default-off, Posts-only feature module with Inline Summary, desktop Floating TOC, stable H2 anchors, Scroll Spy, responsive gating, accessibility states, and legacy duplicate prevention.
- Preserves the existing signed updater and runtime database contracts.

## Article TOC V1

Article TOC is OFF by default.

When enabled for WordPress Posts it can provide:

- H2 discovery
- stable anchor generation while preserving existing IDs
- Inline Summary before the first eligible H2
- Desktop Floating TOC
- Scroll Spy and `aria-current`
- request-level CSS/JS gating
- mobile/tablet floating-TOC suppression
- legacy TOC duplicate detection

Theme code may override brand variables, but Andy Core owns TOC behavior.

## Landing Pages

- internal post type: `yby_landing_page`
- public path: `/lp/{slug}`
- admin location: `Andy Content → Landing Pages`
- no archive route
- block editor / REST enabled

Existing sites with an explicit module list receive a one-time non-destructive adoption that appends only `landing_pages` and preserves all other module choices.

## Compatibility

- Runtime database version remains `1.5.0`.
- Updater compatibility database version remains `1.4.0`.
- No database migration is required.
- Existing Docs data, routes, taxonomy, SEO metadata, and R2 behavior remain compatible.
- Existing site-specific legacy TOC implementations are not force-removed by the code upgrade; Article TOC V1 detects compatible legacy markers to avoid duplicate output during migration.
