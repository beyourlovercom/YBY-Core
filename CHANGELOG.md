# CHANGELOG

## Unreleased

Documentation freeze date: `2026-07-22`

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

### Deployment

- accepted on `dev.ybyirrigation.com`
- exact accepted commit: `9e0363aa2f1516c0ec2a7f785cfb0521a25ba83c`
- package SHA-256: `C5DBC1A365192E0F6DCEA255FCA31F9A0DCCE2DD5900B9740514ED043175BE71`
- Lead baseline remained `15`
- Post SMTP baseline remained `39`
- no production or Bottle deployment

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
