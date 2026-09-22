# CHANGELOG

## v1.7.0 - 2026-09-20

- Added Registry V2 extension-module contracts with enabled-only boot, dependency fail-close, versioned settings storage, and conditional request-level asset gates.
- Added canonical `Andy Content` admin IA and nested Docs under it while preserving existing Docs slugs, routes, content, taxonomy, and settings contracts.
- Restored Landing Pages as the namespaced `yby_landing_page` CPT with canonical `/lp/{slug}` public URLs and a one-time non-destructive existing-site module adoption gate.
- Added Article TOC V1 as a default-off, Posts-only module with Inline Summary, desktop Floating TOC, stable H2 anchors, Scroll Spy, responsive behavior, accessibility states, and legacy TOC duplicate prevention.
- Preserved runtime database version `1.5.0` and updater compatibility database version `1.4.0`; no database migration is required.

## v1.6.0 - 2026-09-18

- Added Docs OS V1 as a default-off modular runtime with Home, Category, Document, Search, TOC and Related Docs surfaces.
- Added reversible canonical takeover that preserves existing Docs post IDs, slugs, canonical URLs, taxonomy and Rank Math metadata.
- Added BetterDocs migration compatibility mapping without requiring BetterDocs as the long-term runtime owner.
- Added Cloudflare R2 render-time asset resolution from existing offloader metadata; no media re-upload and no post-content rewrite.
- Added reusable `yby_docs_os_contract` adapters for post type, taxonomy, base path and related metadata.
- Preserved runtime database version `1.5.0` and updater compatibility database version `1.4.0`; no database migration.

## v1.5.8 - 2026-09-16

- Added Email OS V1.1 native WordPress / Andy Core template lifecycle, immutable Published snapshots, SHA-256 integrity, health/test tooling and ERP read-only Published metadata contract.
- Preserved WooCommerce / VillaTheme runtime ownership and added read-only Legacy Customizer governance.
- Integrated PR #40 `completed-delivered / 完结&送达` with the ordered Woo REST segment `completed → completed-delivered → cancelled`.
- Advanced runtime database schema to `1.5.0` for Email OS tables.
- Preserved updater compatibility marker `1.4.0` so existing 1.5.7 installations can validate and install the signed 1.5.8 package; release metadata also records `runtime_database_version=1.5.0`.
- The obsolete `v1.5.4-hotfix-completed-delivered-20260915` is not part of this release path.

## v1.5.7 - 2026-09-11

- Added Page-Owned Inquiry Modal architecture so each landing page can own its modal instance and presentation while reusing the canonical Andy Core inquiry engine.
- Added reusable `split_visual` desktop and `compact` mobile layout variants with page-level field order, labels, placeholders, media, copy, and CTA configuration.
- Added the generic `page_owned_inquiry` preset for landing-page-owned modal instances.
- Preserved combined Email / WhatsApp routing, Case ID, source attribution, tracking, hidden product-interest context, and compact no-image mobile submissions.
- Retained `used_forklift_inquiry` and existing saved preset registries for backward compatibility without resetting custom configuration.
- Database version remains 1.4.0; no database migration is required.

## v1.5.6 - 2026-09-07

- Moved native update discovery and downloads to the public `beyourlovercom/andy-core-release` GitHub Release channel.
- Removed the WordPress-side GitHub token requirement; installed sites update with zero GitHub credentials.
- Preserved SHA-256, Ed25519, ZIP validation, pre-update code backup, post-update health validation, and rollback.
- Prepared cross-repository Release publication and fixed draft-release verification to use the draft Release ID before stable publication.
- Database version remains `1.4.0`; no migration or business-runtime change.

## v1.5.5 - 2026-09-06

- Added the first production-native secure-updater canary release.
- Preserved all v1.5.4 runtime behavior while exercising signed private GitHub Release discovery and WordPress native update delivery.
- Preserved database version `1.4.0`; no database migration or business-runtime change.
## v1.5.4 - 2026-09-05

- Added the bounded private GitHub Releases updater.
- Added authenticated package download, SHA-256 evidence validation, safe ZIP structure checks, bounded code backups, post-install health validation, and code-only rollback.
- Preserved database version `1.4.0`; this release introduces no database migration.

## v1.5.3 - 2026-08-31

### Added

- Added the WP-API settings tab with truthful connection, security, and provider health status.
- Added HMAC/signature validation, replay protection, rate limiting, mutation idempotency, and safe audit persistence.
- Added five provider snapshots: affiliates, coupons, referrals, payouts, and subscribers.
- Added Affiliate provision/status, Coupon check/provision, and payout-complete reconciliation endpoints.

### Fixed

- Added provider-safe behavior when WooCommerce or AffiliateWP is unavailable.
- Fixed WooCommerce coupon code canonical readback when the provider normalizes code casing.

### Compatibility

- Updated the database schema version to `1.4.0` for Connector persistence.
- Payout completion records an ERP-completed manual PayPal payout in AffiliateWP; Andy Core does not transfer money through PayPal.
- ERP policy logic remains outside Andy Core.

## v1.5.2 - 2026-08-28

### Added

- Added Brand & Theme runtime presentation controls for shared multi-site deployment.
- Added the Email admin hub for floating inquiry, popup inquiry/subscribe, and shortcode management.
- Added Global Inquiry Popup and Subscribe Popup runtime.
- Added reusable Inquiry and Subscribe shortcodes, including the minimal Subscribe mode.
- Added dual-mode global floating inquiry: Mode A single Inquiry and Mode B WhatsApp + Inquiry bottom dock.
- Added first-screen deferred display, responsive previews, per-button color controls, and site-scoped Custom CSS support.
- Added Windows local-first sync and runtime UAT helpers.

### Changed

- Centralized WhatsApp and Email Notification configuration under `Settings -> 璇㈢洏閫氱煡` without changing their underlying option authorities.
- Reused the existing WhatsApp URL/message runtime for the new floating dock instead of introducing a second WhatsApp configuration path.

### Compatibility

- Preserves database schema version `1.3.0`; this release introduces no database migration.
- Preserves existing Lead, Inquiry, Case ID, notification, Thank You, WhatsApp, REST, shortcode, and technical namespace contracts.

## v1.5.1 - 2026-08-07

### Added

- Added the local Inquiry Inbox and follow-up management foundation.
- Added status, priority, owner, next follow-up, archive, restore, notes, and Activity Timeline management for existing Leads.
- Added Inquiry Settings and read-only System Status tabs.
- Added Active Inquiry Owners configuration for assigning new Inquiries to active Salesperson users.
- Added WordPress/MySQL validation for the Inquiry management migration and runtime permission gates.

### Fixed

- Separated Salesperson identity from Active Inquiry Owner assignment eligibility; Salesperson identity now comes from the WordPress `salesperson` role only.
- Preserved inactive historical inquiry owners while denying new assignments to inactive owners and filtering stale Active Owner settings.
- Remediated Project Studio routing and Inquiry admin asset cache busting.

### Compatibility

- Preserves the v1.5.0 Lead REST, Case ID, notification, Thank You, and site-profile contracts.

## v1.5.0 - 2026-08-01

### Added

- Added the Social Login admin submenu and provider overview.
- Added the Google settings page.
- Added secure Google option storage and validation.
- Added the Google login shortcode and on-demand Google Identity Services button.
- Added the POST-only Google authentication endpoint.
- Added local RS256 Google ID Token verification with cached PEM certificates.
- Added restricted WordPress user registration and login mapped by Google `sub`.
- Added double-submit CSRF, short-lived nonce, authoritative-email, disabled-role, duplicate-identity, and internal redirect protections.
- Added optional Google sign-in on the standard WordPress login page.
- Added the canonical Social Login shortcode and Copy control to the admin overview.
- Added opt-in verified-email association for existing Subscriber and Customer accounts, with privileged, custom, mixed, and disabled roles blocked.
- Added opt-in Google One Tap on eligible public pages with same-origin, single-use challenges and asynchronous same-page WordPress authentication.
- Preserved ordinary public page caching by issuing One Tap challenges after page load.
- Added the Bottle OEM inquiry preset, governed fields, and profile validation.
- Added persisted Lead `page_profile` attribution with database version `1.2.0`.
- Added page-profile-specific Thank You routing and deterministic SDK transport coverage.

### Fixed

- Fixed One Tap session confirmation by validating the returned WordPress logged-in cookie through a dedicated no-store endpoint instead of reusing anonymous challenge issuance.
- Fixed malformed Page Profile URL acceptance with centralized HTTP(S) and relative-path validation.
- Fixed WhatsApp project summaries to include only explicitly confirmed fields scoped to the current Case ID.
- Fixed cross-brand WhatsApp template and email identity leakage.
- Fixed page-specific Thank You precedence across both Core runtime and Lead SDK loading orders.

### Not yet included

- Client Secret, Google API authorization, and access or refresh token storage.
- Manual account linking or unlinking and global avatar replacement.
- Facebook, X, and TikTok authentication.

## v1.4.0 - 2026-07-29

### Changed

- Changed the user-facing plugin name from YBY Core to Andy Core.
- Changed the user-facing WordPress admin product label to Andy Core.
- Updated plugin and runtime version identity to 1.4.0.
- Reset the active product roadmap to a feature-driven sequence, with Social Login planned next.

### Compatibility

- Preserved the `yby-core/yby-core.php` plugin identity for in-place upgrades.
- Preserved the `YBY_*` PHP namespace, `yby_*` functions and options, `[yby_*]` shortcodes, `data-yby-*` attributes, `.yby-*` selectors, `/wp-json/yby/v1/` routes, and existing database tables.
- Preserved database schema version 1.1.0.
- No Lead, Case ID, Inquiry, Thank You, WhatsApp, email, tracking, Bricks, theme, or Google Ads behavior changed.

## v1.3.2 - 2026-07-24

### Fixed

- Corrected WordPress readme stable-tag metadata.
- Synchronized release identity across plugin header, runtime constant, VERSION.md, README.md, and readme.txt.
- No runtime or database behavior changed.

## v1.3.1 - 2026-07-24

### Added

- Added shared inquiry trigger support for site-wide modal integration.
- Added the Core-owned sticky inquiry CTA shortcode.
- Added default modal preset fallback for global shortcode placement.

## v1.3.0 - 2026-07-22

### Added

- reusable Inquiry components
- Site Profile
- Brand Profile
- Brand Runtime configuration
- WhatsApp Message Template
- notification provider
- email presentation configuration
- deterministic runtime Harnesses
- development rollback evidence

### Changed

- server now owns stored site identity and Case ID generation
- Brand Runtime governs Catalog, Return, YouTube, and Thank You URLs
- customer-facing WhatsApp messages now use Brand Runtime template or neutral default
- asset versions use deployment-safe cache busting
- Thank You runtime hydrates Case ID and tracks accepted events

### Fixed

- duplicate Inquiry root rendering
- Case ID runtime generation and cleanup
- Thank You tracking behavior
- `generate_lead` duplicate counting in the same session
- stale Project/Page values overriding Brand Runtime links
- WhatsApp escaped newline rendering
- missing and duplicate Case ID in WhatsApp messages
- internal Source values appearing in customer messages
- public CRM webhook exposure
- unsafe protocol-relative path handling
- unconditional test-helper exposure

### Security

- recipients, SMTP values, and CRM webhook URL remain server-only
- REST ignores client brand, website, and Case ID
- no customer PII is included in the Thank You URL
- public test helpers require exact test mode

### Development validation

- stable release prepared from the accepted feature branch
- development validation completed on `dev.ybyirrigation.com`
- exact accepted development runtime commit: `9e0363aa2f1516c0ec2a7f785cfb0521a25ba83c`
- documentation freeze commit: `059a33567fc8db0f132f8e251c90698118184dd8`
- package SHA-256: `C5DBC1A365192E0F6DCEA255FCA31F9A0DCCE2DD5900B9740514ED043175BE71`
- Lead baseline remained `15`
- Post SMTP baseline remained `39`
- production deployment is handled after tag publication and production QA
- no Bottle deployment

## v1.1.1

- Promoted YBY Core to the official stable release line
- Added Brand Settings module support
- Added logo, color, and font governance documentation
- Fixed the activation fatal error and completed release QA validation

## v1.2.0

- Added `POST /wp-json/yby/v1/leads` for governed public lead submission
- Added enterprise HTML inquiry email delivery with multipart AltBody support
- Added Notification Center architecture with configurable lead recipients
- Added configurable lead email subject templates and email branding settings
- Added server-side Case ID validation and generation
- Added 30-minute email idempotency and short send lock protection
- Added PII-safe frontend tracking payload filtering
- Added `mail_sent` in REST success and duplicate responses
- Added email provider Reply-To policy and CC/BCC validation
- Preserved the governed v1.1.0 platform baseline, including Project Studio and frozen v1.x runtime APIs

## v1.1.0

Official release engineering and packaging update for the first post-stable minor release.

- Added Project Studio admin layer to the official release line
- Added Project List, Project Overview, and Runtime Viewer packaging
- Preserved the frozen public runtime APIs with no breaking changes
- Packaged the next backward-compatible release for sites still on v0.4.0

## v1.0.0

First stable public platform release.

- Platform Certified
- Runtime API Frozen
- No breaking runtime signature changes from the final pre-stable platform architecture

## v0.4.0 Project Template Layer

Added the Project Template Engine architecture, exposed `window.YBYTemplate`, defined page, content, tracking, asset, and integration maps, and kept rendering and page generation out of scope.

## v0.4.0 Content Runtime Layer

Added the Content Runtime Engine architecture, exposed `window.YBYContent`, defined standard section and field models, and introduced runtime helper methods without starting dynamic rendering.

## v0.4.0

Added the Project Engine as the canonical project-level runtime object, exposed `window.YBYProject`, preserved `window.YBYPageProfile` compatibility, and updated frontend helpers to prefer project values before page profile and global config.

## v0.3.0

Added the Page Profile Engine for page-level runtime overrides with ACF or post meta fallback, exposed `window.YBYPageProfile`, and updated frontend helpers to prefer page-level Thank You, catalog, WhatsApp, and tracking values.

## v0.2.0

Upgraded YBY Core for first enterprise-ready staging use.

Added Thank You helper integration, expanded Lead Session helpers, standardized tracking helpers, runtime config output, extra settings fields, and a safe email template generator with no sending.

## v0.1.0

Initial MVP plugin skeleton.
