# CHANGELOG

## Unreleased

- Added ZIP internal path separator validation to YBY Core Release Packaging QA Standard.
- Added Inquiry Field Registry foundation.
- Added Inquiry Preset Registry foundation.
- Added `irrigation_quick_inquiry`.
- Added `bottle_wholesale_inquiry`.
- No frontend Inquiry Components added in M1.
- Added `[yby_inquiry_modal]` shortcode.
- Added neutral server-side Inquiry Modal renderer.
- Added accessible Modal markup contract.
- Added safe Field Renderer.
- Modal remains hidden and disabled until M3 runtime.

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
