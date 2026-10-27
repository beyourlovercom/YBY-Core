## v2.3.0 - 2026-10-27 (Notification UI release candidate only)

- Andy Core independent All-Notice Dynamic Island for WooCommerce success, information, warning, error and some Block-style banners.
- OFF-by-default module under Andy Core System Modules; when OFF, Core still gives native Woo notices a compact clean skin.
- Critical errors persist; actionable or contextual notices remain operable; failures are fail-open with original messages restored.
- BYL V3 and Twenty Twenty-Five isolated R5 UAT and Owner visual approval PASS.
- Database markers unchanged: runtime 1.5.0, updater compatibility 1.4.0; existing yby_lead_notifications history preserved.
- No Stable publication, production upgrade, real Cron replay or mail sending is authorized.

## v2.2.12 - 2026-10-26 (Ignore Notification signed candidate)

- Inquiry notification history gains the Ignore Notification action, mandatory reason, actor identity and timestamp. Lead Closed/Archive and SMTP failure history remain unchanged.
- Only pending, failed and exhausted transport receipts are eligible. Atomic state transition quarantines processing/accepted states and stops notification retries.
- Ignore reason is visible after dismissal, and alert counts exclude ignored entries.
- PR #229 Owner Bottle Dev UAT PASS: synthetic case pending -> ignored, attention 1 -> 0, mail and Cron isolated. WordPress/MySQL 26 assertions and admin 13 assertions passed.
- Runtime database remains 1.5.0; signed updater compatibility remains 1.4.0. v2.2.11 Stable remains immutable. Stable publication and production upgrade are independent gates.

## v2.2.11 - 2026-10-19 (integrated V3 + Update Control candidate; NOT published)

- Navigation V3 per Owner-approved interactive design: exactly seven Chinese left L2 entries, four collapsible right L3 groups (文档 / 招商 / 落地页 / 项目), and native WordPress landing/project edit controls. PR #173; Dev visual UAT remains independent and pending.
- Update Control V1 from PR #165/#167: hide native plugin update alerts, block non-Andy plugin unattended upgrades, scoped WordPress update-Cron cleanup; manual updates remain available.
- Cron Health V1 from PR #170: read-only scheduler diagnostics, overdue job alerts, no automatic catching up of business jobs.
- Preserve signed GitHub Stable/Canary updater, SHA256 + Ed25519 checks, code-only rollback, hostname allowlists, and Andy self-update OFF by default.
- No database version bump; no Stable/Latest publication or WWW/Bottle/Irrigation deployment authorized. Hostinger Dev WP-Cron runner remains tracked at Issue #171.

## v2.2.10 - 2026-10-17 (signed Stable release; production deployment Owner-gated)

- Derived from latest integrated GitHub main after PR #159, the U3D-proven signed Hostinger native auto-updater and PR #157's exact PR merge-source CI provenance guard.
- Version bump, signed candidate build/workflow, compatibility-pinned regression and release docs only. No runtime PHP feature changes or database migration **relative to integrated main**.
- **Compared with published Stable v2.2.6, current main includes additional already integrated features** (including Creator read-only code). Stable rollout must audit those differences before publication or per-site install.
- Final tagged eight-asset signed release is isolated from immutable Canary v2.2.9; publication does not install on WordPress/Hostinger/Production sites. Stable/Latest live status is established by GitHub Releases, not source docs.
- Database stays updater compatibility 1.4.0 and runtime 1.5.0; production rollout separately Owner-gated.

## v2.2.9 - 2026-10-17 (U3D Canary candidate; NOT published)

- Version-only signed Canary candidate based on v2.2.8 Draft PR #155. No change to Hostinger managed MU, application/runtime policy, signed updater/verifier, or business code.
- Prepare independent 8-file signed release artifact for future BYL Dev-only **genuine unattended Hostinger scheduled WordPress upgrade 2.2.8 -> 2.2.9**, separate from already PASS U3B controlled updater and U3C scheduled --check evidence.
- U3C Hostinger task remains `--check` every 4h and Andy auto OFF; real U3D installation requires separately approved public Canary and website/cron gates.
- Database remains runtime 1.5.0, signed updater compatibility 1.4.0; Stable/Latest v2.2.6, Bottle Dev, Production/BYL WWW, Mail OS, WooCommerce, payments and orders unchanged.

## v2.2.8 - 2026-10-17 (U3B Canary candidate; NOT published)

- Independent version-only Canary candidate based on v2.2.7 PR #154. No new Hostinger policy code: keeps signed-channel and hostname allowlist plus explicit opt-in, with non-Andy background updates denied during the scoped exception.
- Separately versioned ZIP, metadata, GitHub Actions Artifact, tests and rollback instructions to enable future native WordPress WP-Cron 2.2.7 -> 2.2.8 UAT.
- BYL Dev manually installed v2.2.7 with auto OFF (U3A PASS). Bottle Dev Canary/OFF. No public v2.2.8 Release, site install or WP-Cron auto-install authorized or completed.
- Stable/Latest 2.2.6 and Production / BYL WWW unchanged; runtime database 1.5.0, updater compatibility database 1.4.0; Mail OS, WooCommerce, payment, order, SMTP runtime unchanged.

## v2.2.7 - 2026-10-17 (Canary candidate; not published)

- Prepare Hostinger Smart Auto Updates compatibility within Andy Core: scoped, priority-1000 WordPress background-update gate only for explicit signed channel + allowlisted domain + automatic-update opt-in.
- While an Andy Core automatic-update exception is active, disallow background updates to unrelated plugins, WordPress Core, themes and translations.
- Reuse Dev-only v2.2.2 native WP-Cron auto-install evidence and PR #153 Hostinger policy CI; add separately signed candidate and upcoming BYL Dev-only WP-Cron UAT gate.
- Preserve v2.2.6 Stable release and existing site versions, no Hostinger managed MU modifications, database migrations (runtime 1.5.0 / updater compatibility 1.4.0), BYL WWW, payment, orders or mail changes.
- Do not publish Canary/Stable or enable Dev automatic updates without new Owner approval.

## v2.2.6 - 2026-10-16

- Fix Owner-reproduced WordPress same-version upgrade banner even after Andy Core 2.2.5 is installed: on reading cached `update_plugins`, suppress only invalid, older or same-version Andy Core update offers.
- Preserve legitimate newer signed Canary offers and all unrelated WordPress plugin updates; safeguard channel-excluded sites; add U1-U4 runtime regression assertions.
- Code scope: updater + regression harness only; no database schema migration (runtime 1.5.0, updater compatibility 1.4.0), menus, mail, orders, payments or WooCommerce changes.
- Canary release and Dev manual UAT separately approved by Owner; GitHub PR merge, Stable and WWW remain blocked by separate gates.

## v2.2.5 - 2026-10-16

- Prevent an already-installed Andy Core version from being offered again in WordPress's cached plugin updates; preserve other plugin offers and reject unsigned/older releases.
- Clear the WordPress plugin update transient after successful signed update health verification, ensuring a subsequent admin refresh does not repeat the same-version upgrade banner (PR #143).
- Based on published v2.2.4 Canary; no new permissions, route, schema, Mail OS P9 activation, checkout, payments or SMTP changes. **Candidate only: no public release or website deployment authorized.**

## v2.2.4 - 2026-10-16

- Main-integrated Canary candidate carrying Andy Core v2.2.3, native WordPress L2 persistent-open navigation (#138), and concise Chinese L2/L3 admin labels (#140).
- No route/capability/schema changes; Mail OS P9 live writer, real SMTP and standalone irrigation country cloning remain separately gated.
- Prerelease signing and publication not approved by this code-only candidate. Existing v2.2.3 public assets stay unchanged.

## v2.2.3 - 2026-10-16

- Signed Canary manual upgrade UAT patch (2.2.2 to 2.2.3).
- Reuse SHA256/Ed25519 verified release evidence across admin/Cron requests for 10 minutes; allow explicit Check for Updates to fetch new version.
- No schema migration, payments, orders or email workers.

## v2.2.2 - 2026-10-16

- Signed Dev Canary for automatic update UAT from v2.2.1.
- No database schema, email send, or production rollout changes.

## v2.2.1 - 2026-10-16

- Signed Canary update channel and WordPress automatic upgrade policy test candidate.
- Only Dev sites can access Canary; production stable channel unchanged.

## v2.2.0 - 2026-10-15

- Integrated admin navigation shell, Inquiry OEM original styling, read-only Three OS and Commerce UI.
- Mail OS P9 source-only contracts, zero operational worker/SMTP/queue hooks.
- Public stable distribution held pending excluded-site update isolation and Dev UAT.

# CHANGELOG

## v1.9.0 - 2026-09-24

- Added the generic Addon Registry and andy_core_register_addons extension contract.
- Added installed/inactive Addon discovery through explicit Andy Core Addon headers.
- Added Core-version and dependency readiness reporting plus a read-only Addons admin surface.
- Preserved Core-only operation for B2B sites.
- Kept Andy Commerce and WooCommerce business runtime outside the Andy Core package.
- Preserved runtime database version 1.5.0 and updater compatibility database version 1.4.0; no database migration is required.
- Windows localdev and isolated Dev validation passed before merge; Dev remains the primary Website V3 integration/UAT environment.
- Production deployment remains a separate explicit gate.

## v1.8.1 - 2026-09-23

- Added Analytics / GTM Control Layer V1 as a default-OFF Registry V2 extension module with versioned settings.
- Added the canonical Analytics Business Event Contract while preserving historical `YBY_Tracking` event names and browser API compatibility.
- Routed Inquiry tracking through the governed tracking facade instead of direct business-module `dataLayer.push` ownership.
- Added a GTM4WP provider adapter with explicit `>=2.0.0 <3.0.0` compatibility gating and no dependency on private GTM4WP APIs.
- Added a read-only Analytics Site Profile over trusted site identity plus existing Project/Page tracking dimensions without metadata migration.
- Added duplicate-GTM diagnostics with a strict `diagnose_only` policy; Andy Core does not automatically remove third-party tags.
- Added a read-only Consent Adapter in `respect_existing` mode; Andy Core does not own or mutate consent state.
- Dev UAT confirmed GTM4WP 2.0.2, one GTM container (`GTM-MQS84CR4`), no duplicate injection, and clean OFF/ON/restoration behavior.
- Preserved runtime database version `1.5.0` and updater compatibility database version `1.4.0`; no database migration is required.

## v1.8.0 - 2026-09-23

- Added the bounded ERP Content Publishing V1 contract for `blog`, `guide`, and `comparison` article-like content.
- Added HMAC-authenticated `/content/preview` with zero WordPress writes and `/content/publish` with durable Connector idempotency.
- Added exact ERP article/layout/hash binding, same-post updates, bounded provider read-back, and fail-closed binding/provider mismatch behavior.
- Enforced Draft => `noindex` and Publish => `index`, with Rank Math robots read-back verification.
- Preserved runtime database version `1.5.0` and updater compatibility database version `1.4.0`; no database migration is required.
- Production deployment remains intentionally deferred while later versions continue through development/UAT.

## v1.7.0 - 2026-09-20

- Added Registry V2 extension-module contracts with enabled-only boot, dependency fail-close, versioned settings storage, and conditional request-level asset gates.
- Added canonical `Andy Content` admin IA and nested Docs under it while preserving existing Docs slugs, routes, content, taxonomy, and settings contracts.
- Preserved the canonical existing `landing_page` CPT as the first-class B2B workbench at `/lp/{slug}` and removed duplicate Andy Core CPT registration.
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
- Integrated PR #40 `completed-delivered / 瀹岀粨&閫佽揪` with the ordered Woo REST segment `completed 鈫?completed-delivered 鈫?cancelled`.
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

- Centralized WhatsApp and Email Notification configuration under `Settings -> 鐠囥垻娲忛柅姘辩叀` without changing their underlying option authorities.
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
