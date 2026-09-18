=== Andy Core ===
Contributors: ybyglobal
Tags: andy-core, yby, case-id, tracking, lead-session, configuration
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.6.0
License: Proprietary
License URI: https://ybyglobal.com/

Core platform plugin for managed WordPress websites, including Case ID, tracking, configuration, lead sessions, governed lead email delivery, inquiry runtime, and the bounded WordPress Connector.

== Description ==

Andy Core is the shared foundation plugin for managed WordPress websites. Version 1.6.0 adds modular Docs OS V1, reversible canonical documentation takeover, Cloudflare R2 render-time asset resolution, and a reusable cross-site Docs contract while preserving existing Andy Core runtime and signed updater contracts.

Included in v1.6.0:

* Docs OS V1 module registry with default OFF gate and full data-retention behavior
* Native Docs Home, Category, Document, Search, TOC and Related Docs runtime
* Reversible canonical takeover that preserves existing post IDs, slugs, URLs, taxonomy and SEO metadata
* Cloudflare R2 render-time image/srcset resolution from existing offloader metadata; no re-upload or post-content rewrite
* Reusable `yby_docs_os_contract` for post type, taxonomy, base path and related metadata adapters
* Database schema remains runtime 1.5.0 and updater compatibility 1.4.0; no migration

Included in v1.5.8:

* Email OS V1.1 for WordPress and Andy Core native template Draft / Published lifecycle, integrity validation, health/test tooling, and read-only ERP Published metadata
* WooCommerce remains provider-owned; Email OS adds governance, diagnostics and deep links without runtime takeover
* Canonical `completed-delivered / 完结&送达` status with REST ordering `completed → completed-delivered → cancelled`
* Runtime database schema 1.5.0 with updater compatibility marker 1.4.0 for native upgrade from 1.5.7
* No installation of `v1.5.4-hotfix-completed-delivered-20260915`

Included in v1.5.7:

* Landing pages can own their inquiry modal instance while reusing the Andy Core lead, Case ID, validation, attribution, and tracking engine
* Reusable `split_visual` desktop and `compact` mobile layout variants support page-level field order, labels, placeholders, media, copy, and CTA configuration
* Compact mobile inquiry can hide media and use Name, Email / WhatsApp, and Inquiry Details without internal scrolling
* Existing presets remain backward compatible, including the legacy `used_forklift_inquiry` preset; new landing pages can use `page_owned_inquiry`
* Database schema remains version 1.4.0 with no migration

Included in v1.5.6:

* Public zero-config GitHub Release update discovery and downloads
* No WordPress-side GitHub token, SSH, or wp-config.php update credential
* Existing SHA-256, Ed25519, ZIP validation, backup, health-check, and rollback controls remain enforced
* Database schema remains version 1.4.0 with no migration

Included in v1.5.5:

* First real native-update canary from an installed v1.5.4 bootstrap
* No database migration and no business-runtime contract change

Included in v1.5.4:

* Private GitHub Releases update discovery with authenticated downloads
* SHA-256 package evidence, ZIP structure validation, bounded code backup, health validation, and code-only rollback

* WP-API settings tab with truthful health and provider-safe status behavior
* HMAC/signature, replay, rate-limit, mutation idempotency, and safe audit protections
* Affiliates, Coupons, Referrals, Payouts, and Subscribers provider snapshots
* Affiliate provision/status, Coupon check/provision, and payout-complete reconciliation
* Canonical provider read-back, including WooCommerce coupon code normalization
* Database schema version 1.4.0 for Connector persistence

Andy Core records ERP-completed manual PayPal payouts in AffiliateWP; it does not transfer money through PayPal. ERP policy logic remains outside this plugin.

Included in v1.5.2:

* Brand & Theme runtime presentation controls for shared multi-site deployment
* Email admin hub for floating inquiry, popup inquiry/subscribe, and shortcode management
* Global Inquiry Popup and Subscribe Popup runtime
* Global floating inquiry with Mode A single Inquiry and Mode B WhatsApp + Inquiry bottom dock
* First-screen deferred display, responsive previews, per-button color controls, and Custom CSS override support
* Reusable Inquiry and Subscribe shortcodes, including minimal Subscribe mode
* `Settings -> 閻犲洢鍨诲ú蹇涙焻濮樿京鍙€` centralizes WhatsApp and Email Notification while preserving existing option keys
* Database schema remains version 1.3.0 with no new migration

Included in v1.5.1:

* Inquiry Inbox with list, search, filters, pagination, and detail views
* Follow-up Management for status, priority, owner, next follow-up, notes, archive, and restore
* Active Inquiry Owners configuration scoped to WordPress users with role `salesperson`
* Salesperson role isolation and historical inactive owner retention
* Activity Timeline, Inquiry Settings, and read-only System Status
* Project Studio routing remediation and Inquiry admin asset cache busting
* Database schema version 1.3.0 adds Inquiry management and Activity tables

Included in v1.5.0:

* User-facing product name changed to Andy Core
* Google Social Login and opt-in Google One Tap
* Bottle OEM inquiry preset and page profile transport
* Page-specific Thank You URLs with strict URL validation
* Confirmed-only, Case-scoped WhatsApp project summaries
* Plugin directory and main file remain `yby-core/yby-core.php`
* Technical `YBY_*`, `yby_*`, `[yby_*]`, `data-yby-*`, and `/wp-json/yby/v1/` contracts remain unchanged
* Database schema version 1.2.0 adds Lead page_profile attribution

Existing capabilities:

* Case ID engine
* Config center
* Project engine
* Page Profile engine
* Content runtime engine
* Project Template engine
* Tracking engine
* Lead session helper
* Thank You page helper
* Public `POST /wp-json/yby/v1/leads` endpoint
* Enterprise HTML inquiry email
* Multipart AltBody generation
* Notification Center architecture
* Lead notification recipient settings
* Lead email subject template
* Email branding settings
* CRM webhook placeholder
* Admin settings page
* Project Studio admin layer
* Runtime Viewer
* Stable public runtime API contract
* `mail_sent` flag in REST responses

Not included:

* Real CRM connection
* Real ERP connection
* AI services
* Facebook, X, or TikTok authentication
* GTM, GA4, Ads, or Bricks modifications

== Installation ==

1. Upload the `yby-core` folder to the `/wp-content/plugins/` directory.
2. Activate Andy Core through the `Plugins` screen in WordPress.
3. Open `Andy Core > Settings` in the WordPress admin menu to review settings.

== Frequently Asked Questions ==

= Why is the folder still named yby-core? =

Version 1.4.0 is a compatibility-safe product-name transition. The plugin path and technical identifiers remain unchanged so existing installations upgrade in place instead of being treated as a second plugin.

= Does this plugin support a canonical project object? =

Yes. `window.YBYProject` is the canonical runtime object. `window.YBYPageProfile` remains available for compatibility.

= Does this plugin support page-level profile values? =

Yes. If ACF is available the plugin reads approved page profile fields with `get_field()`. Otherwise it falls back to `get_post_meta()`.

= Does this plugin install GTM or GA4? =

No. GTM remains globally installed by the approved site method only.

= Does this plugin send real CRM data? =

No. The CRM webhook module is a safe placeholder in the current release.

= Does this plugin send inquiry emails? =

Yes. The governed lead endpoint can send one HTML inquiry email per Case ID within the configured idempotency window.

= What does the REST response return? =

Successful and duplicate responses include `success`, `case_id`, `duplicate`, and `mail_sent`. No recipient email, stack trace, or transport credential is exposed.

= How does lead notification work? =

Lead notifications dispatch through `YBY_Notification_Manager` with the Email provider enabled in this release. Future providers are reserved for later phases.

= What are the Email settings? =

The Email provider supports Primary Recipient Email, CC Recipient Emails, BCC Recipient Emails, Reply-To Policy, Lead Email Subject Template, and Email Branding settings. Invalid addresses are ignored.

== Changelog ==

= 1.5.3 =

* Added the bounded WP-API WordPress Connector settings tab and truthful connection, security, and provider health presentation.
* Added HMAC/signature validation, replay protection, rate limiting, mutation idempotency, and safe audit persistence.
* Added Affiliates, Coupons, Referrals, Payouts, and Subscribers snapshots with provider-safe unavailable behavior.
* Added Affiliate provision/status, Coupon check/provision, and payout-complete reconciliation with canonical provider read-back.
* Fixed WooCommerce coupon code canonical readback handling when the provider normalizes code casing.
* Updated the database schema version to 1.4.0 for Connector persistence.
* Andy Core records ERP-completed manual PayPal payouts in AffiliateWP; it does not transfer money through PayPal, and ERP policy logic remains out of scope.

= 1.5.2 =

* Added Brand & Theme runtime presentation controls for shared multi-site deployment.
* Added the Email admin hub for floating inquiry, popup inquiry/subscribe, and shortcode management.
* Added Global Inquiry Popup, Subscribe Popup, and reusable Inquiry/Subscribe shortcode runtime.
* Added dual-mode global floating inquiry with WhatsApp + Inquiry support, deferred display, responsive previews, and per-button colors.
* Moved WhatsApp and Email Notification settings into `Settings -> 閻犲洢鍨诲ú蹇涙焻濮樿京鍙€` while preserving the existing option authorities.
* Preserved database schema version 1.3.0 and existing Lead, Inquiry, notification, Case ID, and WhatsApp contracts.

= 1.5.1 =

* Added the local Inquiry Inbox and follow-up management foundation.
* Added status, priority, owner, next follow-up, archive, restore, notes, and Activity Timeline management for existing Leads.
* Added Inquiry Settings and read-only System Status tabs.
* Added Active Inquiry Owners configuration and Salesperson role isolation.
* Preserved historical inactive owners while denying new assignments to inactive owners.
* Remediated Project Studio routing and Inquiry admin asset cache busting.
* Validated the WordPress/MySQL database upgrade to schema version 1.3.0.

= 1.5.0 =

* Added secure Google Social Login and opt-in Google One Tap.
* Added Bottle OEM inquiry preset, profile validation, and Lead page_profile persistence.
* Added page-specific Thank You routing with hardened URL validation.
* Added confirmed-only, Case-scoped WhatsApp project summaries and cross-brand identity isolation.
* Updated database schema version to 1.2.0.

= 1.4.0 =

* Changed the user-facing product name from YBY Core to Andy Core.
* Updated the plugin and runtime version to 1.4.0.
* Preserved the `yby-core` plugin path, technical namespace, shortcodes, REST routes, database tables, options, and frontend runtime APIs.
* Preserved database schema version 1.1.0.
* Added no Social Login behavior and made no Lead, Case ID, Thank You, WhatsApp, email, tracking, Bricks, theme, or Ads changes.

= 1.3.2 =

* Corrected WordPress stable-tag metadata.
* Preserved the v1.3.1 runtime without behavior or database changes.

= 1.3.0 =

* Added the reusable Inquiry component system with shortcode and frontend runtime support.
* Added structured Inquiry metadata persistence with database schema version 1.1.0.
* Added Site Profile, Brand Profile, and Brand Runtime governance for shared multi-brand deployment.
* Added governed WhatsApp Message Template support with customer-safe formatting and Case ID enforcement.
* Added Thank You runtime hydration and same-session `generate_lead` deduplication.
* Added deterministic Harness coverage for runtime, identity, presentation, and notification behavior.
* Preserved server-owned Case ID, brand identity, and secret exclusion rules.

= 1.2.0 =

* Added `POST /wp-json/yby/v1/leads`.
* Added enterprise HTML inquiry email delivery with multipart AltBody.
* Added Notification Center architecture with Email provider.
* Added configurable lead notification recipients and reply-to policy.
* Added Case ID validation, idempotency, and send-lock protection.
* Added PII-safe tracking payload filtering.
* Added `mail_sent` in REST success and duplicate responses.
* Preserved Project Studio, Runtime Viewer, and frozen v1.x runtime APIs.

= 1.1.1 =

* Brand Settings module included.
* Logo management added.
* Brand color management added.
* Font governance added.
* Activation fatal error fix included.
* Release QA validation completed.

= 1.1.0 =

* Added Project Studio as a managed admin layer under the plugin menu.
* Added the `yby_project` Custom Post Type for Project management records.
* Added read-only Project List, Project Overview, and Runtime Viewer screens.
* Preserved the existing stable public runtime APIs with no signature changes.

= 1.0.0 =

* First stable public platform release.
* Platform Certified.
* Runtime API Frozen for `window.YBYCoreConfig`, `window.YBYProject`, `window.YBYContent`, `window.YBYTemplate`, `window.YBYLead`, `window.YBYTracking`, and `window.YBYThankYou`.

= 0.4.0 =

* Added Project Engine with canonical project runtime object, ACF or post meta sourcing, Page Profile compatibility, and project-first frontend fallback logic.

= 0.3.0 =

* Added Page Profile Engine with page-level config priority, ACF or post meta fallback, `window.YBYPageProfile`, and frontend integration for Thank You, catalog, WhatsApp, and tracking values.

= 0.2.0 =

* Upgraded plugin skeleton with Thank You helper, runtime config, extended lead session methods, standardized tracking helper, and safe email template generation.

= 0.1.0 =

* Initial MVP plugin skeleton.
