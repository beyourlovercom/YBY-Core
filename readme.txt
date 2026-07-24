=== YBY Core ===
Contributors: ybyglobal
Tags: yby, case-id, tracking, lead-session, configuration
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.3.2
License: Proprietary
License URI: https://ybyglobal.com/

Core platform plugin for YBY websites, including Case ID, tracking, configuration, lead session, governed lead email delivery, and CRM webhook foundation.

== Description ==

YBY Core is the governed foundation plugin for YBY WordPress websites.

Included in v1.3.0:

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
* Database tables
* GTM, GA4, Ads, or Bricks modifications

== Installation ==

1. Upload the `yby-core` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the `Plugins` screen in WordPress.
3. Open `YBY OS > Settings` in the WordPress admin menu to review settings.

== Frequently Asked Questions ==

= Does this plugin support a canonical project object? =

Yes. `window.YBYProject` is the canonical runtime object. `window.YBYPageProfile` remains available for compatibility.

= Does this plugin support page-level profile values? =

Yes. If ACF is available the plugin reads approved page profile fields with `get_field()`. Otherwise it falls back to `get_post_meta()`.

= Does this plugin install GTM or GA4? =

No. GTM remains globally installed by the approved site method only.

= Does this plugin send real CRM data? =

No. The CRM webhook module is a safe placeholder in MVP.

= Does this plugin send inquiry emails? =

Yes. v1.3.0 includes a governed lead endpoint that can send one HTML inquiry email per Case ID within the configured idempotency window.

= What does the REST response return? =

Successful and duplicate responses include `success`, `case_id`, `duplicate`, and `mail_sent`. No recipient email, stack trace, or transport credential is exposed.

= How does lead notification work? =

Lead notifications dispatch through `YBY_Notification_Manager` with the Email provider enabled in this release. Future providers are reserved for later phases.

= What are the Email settings? =

The Email provider supports Primary Recipient Email, CC Recipient Emails, BCC Recipient Emails, Reply-To Policy, Lead Email Subject Template, and Email Branding settings. Invalid addresses are ignored.

== Changelog ==

= 1.3.0 =

* Added the reusable Inquiry component system with shortcode and frontend runtime support.
* Added structured Inquiry metadata persistence with database schema version 1.1.0.
* Added Site Profile, Brand Profile, and Brand Runtime governance for shared multi-brand deployment.
* Added governed WhatsApp Message Template support with customer-safe formatting and Case ID enforcement.
* Added Thank You runtime hydration and same-session `generate_lead` deduplication.
* Added deterministic Harness coverage for runtime, identity, presentation, and notification behavior.
* Preserved server-owned Case ID, brand identity, and secret exclusion rules.

= 1.1.1 =

* Brand Settings module included.
* Logo management added.
* Brand color management added.
* Font governance added.
* Activation fatal error fix included.
* Release QA validation completed.

= 1.2.0 =

* Added `POST /wp-json/yby/v1/leads`.
* Added enterprise HTML inquiry email delivery with multipart AltBody.
* Added Notification Center architecture with Email provider.
* Added configurable lead notification recipients and reply-to policy.
* Added Case ID validation, idempotency, and send-lock protection.
* Added PII-safe tracking payload filtering.
* Added `mail_sent` in REST success and duplicate responses.
* Preserved Project Studio, Runtime Viewer, and frozen v1.x runtime APIs.

= 1.1.0 =

* Added Project Studio as a managed admin layer under `YBY OS`.
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
