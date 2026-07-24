# YBY Core

## Purpose

YBY Core is the shared WordPress foundation plugin for YBY websites.

## Current Version

v1.3.2

## Development Status

YBY Core v1.3.2 is the current stable release candidate. It supersedes v1.3.1 for deployment because v1.3.1 contained incorrect WordPress readme stable-tag metadata.

M5B is CLOSED / PASS, and the planned next development line is v1.4.0. Dev and Production deployment are recorded only after the v1.3.2 release candidate validates and passes the required QA gates.

## Stable Scope

YBY Core v1.3.0 currently includes:

- Case ID frontend and backend consistency
- Lead Session helper
- Thank You Page integration helper
- Config Center runtime output
- Project Engine
- Page Profile Engine
- Content Runtime Engine
- Project Template Engine
- Tracking helper standardization
- Safe email template generator
- Project Studio admin layer
- Runtime Viewer
- Admin settings page
- Governed public lead REST endpoint
- Notification Center architecture
- Governed inquiry email delivery
- Lead notification recipient settings
- Case ID validation and generation
- backward-compatible lead schema v1.1.0
- structured Inquiry source metadata storage
- structured `custom_fields` JSON storage
- preset-based server allowlisting for flexible fields
- non-fatal notification dispatch after successful lead storage

## What Is Included

- installable plugin skeleton
- settings storage under `yby_core_options`
- frontend runtime config output
- frontend project runtime output
- frontend page profile runtime output
- frontend content runtime output
- frontend project template runtime output
- `window.YBYCoreConfig`
- `window.YBYProject`
- `window.YBYContent`
- `window.YBYTemplate`
- `window.YBYLead`
- `window.YBYTracking`
- `window.YBYThankYou`
- `window.YBYPageProfile`
- safe email template generator
- existing custom lead table support
- public `POST /wp-json/yby/v1/leads` endpoint
- enterprise HTML inquiry email with multipart AltBody

## Notification Center

Lead notifications dispatch through `YBY_Notification_Manager` after successful lead storage.

Current provider:

- `email`

Current Email settings:

- Primary Recipient Email
- CC Recipient Emails
- BCC Recipient Emails
- Reply-To Policy
- Lead Email Subject Template
- Email Branding settings

Email delivery failure is non-fatal to the REST success response, and future providers are reserved for a later phase.

## Public Stable Runtime API

The following browser runtime objects are now frozen as public stable APIs for v1.x:

- `window.YBYCoreConfig`
- `window.YBYProject`
- `window.YBYContent`
- `window.YBYTemplate`
- `window.YBYLead`
- `window.YBYTracking`
- `window.YBYThankYou`
- `window.YBYPageProfile`

## What Is Not Included

- CRM implementation
- ERP implementation
- AI implementation
- new database schema changes in M1
- SQL
- GTM installation
- Google Tag installation
- theme modifications
- Bricks page modifications

## Activation Behavior

On activation, default options are created under:

- `yby_core_options`
- `yby_lead_notification_primary_recipient_email`

## Case ID Rule

Case IDs follow:

`YBY-IRR-YYYYMMDD-XXXXXX`

The generator avoids ambiguous characters and keeps PII out of the identifier.

## Tracking Rule

Tracking helpers push safe payloads to `window.dataLayer` only and do not inject GTM or Google tags.

Lead tracking in v1.1.1 also filters blocked PII keys before payloads are pushed.

## Page Profile Rule

Page Profile values follow this priority:

1. Page Profile
2. YBY Core global settings
3. System defaults

## Project Rule

Project values are the canonical runtime layer and follow this priority:

1. YBY Project
2. YBY Page Profile
3. YBY Core global settings
4. System defaults

## CRM Webhook Placeholder

Webhook behavior remains disabled by default and performs no external request unless a future phase explicitly enables a safe implementation.

## Inquiry Foundation Status

YBY Core v1.3.0 includes:

- Inquiry Field Registry and Inquiry Preset Registry foundations
- `[yby_inquiry_modal]` shortcode and server-side renderer
- Inquiry Modal frontend runtime and SDK submission bridge
- Email-or-WhatsApp contact compatibility
- database schema v1.1.0 for Inquiry metadata
- `source_component`, `source_preset`, `source_page`, and `form_version` lead storage
- `custom_fields` JSON storage for preset-allowed flexible fields
- automatic database upgrade checks without requiring reactivation
- notification dispatch after successful lead storage
- custom Inquiry details in HTML and plain-text email notifications

Inquiry CSS remains brand-neutral, the REST success response remains unchanged, `generate_lead` remains owned by the Thank You flow, and full multi-brand email identity work is deferred to M5.

Example usage:

```html
<button type="button" data-yby-modal-open="homepage-inquiry">
    Get Free Quote
</button>
```

```text
[yby_inquiry_modal
    id="homepage-inquiry"
    preset="irrigation_quick_inquiry"
]
```

Shared trigger usage:

```html
<button
    type="button"
    data-yby-inquiry-trigger
    data-yby-source="site_header_get_quote"
>
    Get Quote
</button>
```

Backward-compatible triggers remain supported:

```html
<button type="button" data-yby-modal-open="homepage-inquiry">
    Get Free Quote
</button>

<button type="button" data-yby-quote-trigger data-yby-source="site_header_get_quote">
    Get Quote
</button>
```

The modal shortcode may be placed with an explicit preset or with the default
Core fallback:

```text
[yby_inquiry_modal]
```

Sticky CTA usage:

```text
[yby_sticky_cta
    label="Get Quote"
    href="#yby-inquiry"
    source="site_global_sticky_cta"
    profile="site_global"
]
```

The sticky CTA opens the shared Core inquiry modal when one is present. It keeps
the `href` as a safe fallback anchor and does not create a separate Lead or Case
ID runtime.

Bottle usage:

```html
<button type="button" data-yby-modal-open="bottle-inquiry">
    Request Wholesale Quote
</button>
```

```text
[yby_inquiry_modal
    id="bottle-inquiry"
    preset="bottle_wholesale_inquiry"
]
```

## Stability Note

YBY Core v1.3.0 is the current stable release. The planned next development line is v1.4.0, and that work has not started.

Its public runtime API signatures remain frozen for the v1.x line under the compatibility policy documented in the stable release package.

## Documentation

- [Brand Runtime Configuration](docs/brand-runtime/BRAND_RUNTIME_CONFIGURATION.md)
- [M5B Development Deployment and Rollback](docs/deployment/M5B_DEV_DEPLOYMENT_AND_ROLLBACK.md)
- [YBY Irrigation Runtime Profile](docs/configuration/YBY_IRRIGATION_RUNTIME_PROFILE.md)
- [M5B Closeout](docs/sprints/M5B_CLOSEOUT.md)
- [v1.3.0 Release Notes](docs/releases/V1.3.0_RELEASE_NOTES.md)

## Release Process

All future YBY Core releases must follow the official workflow in [docs/RELEASE_WORKFLOW.md](docs/RELEASE_WORKFLOW.md).

## Development Process

All YBY Core feature, fix, refactor, and documentation work must follow [docs/DEVELOPMENT_WORKFLOW.md](docs/DEVELOPMENT_WORKFLOW.md).

## Sprint 001.5 Validation Result

- Test URL: `https://ybyirrigation.com/lp/irrigation-solution/`
- Test Date: `2026-07-16`
- Result: SDK availability and submit interface validated in a simulated frontend load path; `window.YBYLead` and `window.YBYLead.submit` are available, and debug mode logs initialize and submit events.

## v1.1.1 Stable Release Notes

- Brand Settings module included
- Logo management added
- Brand color management added
- Font governance added
- Activation fatal error fix included
- Release QA validation completed

## v1.2.0 Lead Intake

The official website inquiry endpoint is:

`POST /wp-json/yby/v1/leads`

This route:

- accepts public lead submissions
- validates and normalizes Case ID
- stores core lead data and structured Inquiry metadata
- persists preset-allowed flexible fields in `custom_fields`
- sends one notification after successful storage
- keeps the existing HTTP 200 success response shape unchanged
- treats email delivery failure as non-fatal

The configured primary recipient is stored in the dedicated option:

`yby_lead_notification_primary_recipient_email`

Default recipient:

`sale@yby-irrigation.com`

## Database Schema

Current development schema version:

`1.1.0`

The `yby_leads` table includes backward-compatible nullable additions for:

- `source_component`
- `source_preset`
- `source_page`
- `form_version`
- `custom_fields`

`custom_fields` stores normalized JSON object data, defaulting to `{}` when no flexible Inquiry fields are saved.
