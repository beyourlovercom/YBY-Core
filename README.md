# YBY Core

## Purpose

YBY Core is the shared WordPress foundation plugin for YBY websites.

## Current Version

v1.1.0

## Stable Scope

YBY Core v1.1.0 includes:

- Case ID frontend and backend consistency
- Lead Session helper
- Thank You Page integration helper
- Config Center runtime output
- Project Engine
- Page Profile Engine
- Tracking helper standardization
- Safe email template generator
- Project Studio admin layer
- Runtime Viewer
- Admin settings page

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
- safe email template generator with no sending

## Public Stable Runtime API

The following browser runtime objects are now frozen as public stable APIs for v1.x:

- `window.YBYCoreConfig`
- `window.YBYProject`
- `window.YBYContent`
- `window.YBYTemplate`
- `window.YBYLead`
- `window.YBYTracking`
- `window.YBYThankYou`

## What Is Not Included

- CRM implementation
- ERP implementation
- AI implementation
- database tables
- SQL
- real webhook delivery
- SMTP or `wp_mail`
- GTM installation
- Google Tag installation
- theme modifications
- Bricks page modifications

## Activation Behavior

On activation, default options are created under:

`yby_core_options`

## Case ID Rule

Case IDs follow:

`YBY-IRR-YYYYMMDD-XXXXXX`

## Tracking Rule

Tracking helpers push safe payloads to `window.dataLayer` only and do not inject GTM or Google tags.

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

YBY Core v1.1.0 continues the stable public platform release line.

Its public runtime API signatures are frozen for the v1.x line under the compatibility policy documented in the stable release package.

## Future Roadmap

- staging validation
- email sending integration after approval
- controlled webhook transport
- approved theme integration
