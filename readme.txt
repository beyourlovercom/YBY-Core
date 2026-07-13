=== YBY Core ===
Contributors: ybyglobal
Tags: yby, case-id, tracking, lead-session, configuration
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.1.0
License: Proprietary
License URI: https://ybyglobal.com/

Core platform plugin for YBY websites, including Case ID, tracking, configuration, lead session, and CRM webhook foundation.

== Description ==

YBY Core is the shared foundation plugin for YBY WordPress websites.

Included in v1.1.0:

* Case ID engine
* Config center
* Project engine
* Page Profile engine
* Content runtime engine
* Project Template engine
* Tracking engine
* Lead session helper
* Thank You page helper
* Safe email template generator
* CRM webhook placeholder
* Admin settings page
* Project Studio admin layer
* Runtime Viewer
* Stable public runtime API contract

Not included in MVP:

* Real CRM connection
* Real ERP connection
* AI services
* REST API endpoints
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

= Does this plugin send real email? =

No. The email template module only generates safe message content for future use.

== Changelog ==

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
