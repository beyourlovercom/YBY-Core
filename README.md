# YBY Core

## Purpose

YBY Core is the shared WordPress foundation plugin for YBY websites.

## Current Version

v1.2.0

## Stable Scope

YBY Core v1.2.0 includes:

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
- Governed inquiry email delivery
- Lead recipient email setting
- Case ID validation, idempotency, and send-lock protection
- `mail_sent` response support for lead submission flows

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
- `yby_lead_recipient_email`

## Case ID Rule

Case IDs follow:

`YBY-IRR-YYYYMMDD-XXXXXX`

The generator avoids ambiguous characters and keeps PII out of the identifier.

## Tracking Rule

Tracking helpers push safe payloads to `window.dataLayer` only and do not inject GTM or Google tags.

Lead tracking in v1.2.0 also filters blocked PII keys before payloads are pushed.

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

YBY Core v1.2.0 continues the stable public platform release line.

Its public runtime API signatures remain frozen for the v1.x line under the compatibility policy documented in the stable release package.

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
- keeps the lead recipient email in WordPress only

The configured recipient is stored in the dedicated option:

`yby_lead_recipient_email`

Default recipient:

`beyourlovercom@gmail.com`
