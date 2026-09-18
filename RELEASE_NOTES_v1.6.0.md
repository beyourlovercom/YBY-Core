# Andy Core v1.6.0 — Release Notes

Release date: 2026-09-18

## Highlights

- Introduces Docs OS V1 as a default-off Andy Core module.
- Adds native Docs Home, Category, Document, Search, TOC and Related Docs runtime.
- Adds reversible canonical ownership for existing WordPress docs without recreating posts or changing canonical URLs.
- Adds Cloudflare R2 render-time asset resolution from existing media-offloader metadata.
- Adds reusable `yby_docs_os_contract` adapters for cross-site reuse.

## Compatibility

- Runtime database version remains `1.5.0`.
- Updater compatibility database version remains `1.4.0`.
- No database migration is required.
- BetterDocs may remain installed during transition; runtime retirement is a separate controlled gate.
