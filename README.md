# YBY Core

## Purpose

YBY Core is the shared WordPress foundation plugin for YBY websites.

## Current Version

v1.2.1-dev

## Stable Scope

YBY Core v1.2.1-dev includes:

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
- 30-minute email idempotency
- short send lock for duplicate-click protection
- `mail_sent` success flag in REST responses

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
- public `POST /wp-json/yby/v1/leads` endpoint
- enterprise HTML inquiry email with multipart AltBody

## Notification Center

Lead notifications now dispatch through `YBY_Notification_Manager`.

Current provider:

- `email`

Current Email settings:

- Primary Recipient Email
- CC Recipient Emails
- BCC Recipient Emails
- Reply-To Policy
- Lead Email Subject Template
- Email Branding settings

Future providers are reserved for a later phase and are not implemented in this release candidate.

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
- database tables
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

## Stability Note

YBY Core v1.2.1-dev is the current development line.

Its public runtime API signatures remain frozen for the v1.x line under the compatibility policy documented in the stable release package.

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
- checks honeypot and origin rules
- enforces duplicate-send protection
- sends one governed HTML email per Case ID within 30 minutes
- returns `success`, `case_id`, `duplicate`, and `mail_sent`
- keeps the lead notification recipients in WordPress only

The configured primary recipient is stored in the dedicated option:

`yby_lead_notification_primary_recipient_email`

Default recipient:

`sale@yby-irrigation.com`
