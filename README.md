# YBY Core

## Purpose

YBY Core is the shared WordPress foundation plugin for YBY websites.

## MVP Scope

This plugin delivers a clean, installable, extensible plugin skeleton for:

- Case ID Engine
- Config Center
- Tracking Engine
- Lead Session
- CRM Webhook placeholder

## Folder Structure

```text
yby-core/
├── yby-core.php
├── uninstall.php
├── readme.txt
├── README.md
├── CHANGELOG.md
├── LICENSE.md
├── inc/
├── admin/
├── public/
├── assets/
├── modules/
├── templates/
└── languages/
```

## Modules

- `case-id`
- `tracking`
- `lead-session`
- `crm-webhook`
- `config-center`

## What Is Included

- Plugin bootstrap
- Activation and deactivation handlers
- Config option storage under `yby_core_options`
- Admin settings page
- Case ID utility class
- Frontend lead-session helper
- Frontend tracking helper
- Safe CRM webhook placeholder

## What Is Not Included

- CRM implementation
- ERP implementation
- AI implementation
- database tables
- REST API endpoints
- real external API connections
- GTM installation
- Google Tag installation
- theme file modifications
- Bricks page modifications

## Activation Behavior

On activation, the plugin creates default settings in one option key:

`yby_core_options`

## Case ID Rule

Case IDs follow:

`YBY-IRR-YYYYMMDD-XXXXXX`

The generator avoids PII and validates pattern structure.

## Tracking Rule

Tracking helpers use `dataLayer` safely and do not install GTM, GA4, or Google Tag scripts.

## CRM Webhook Placeholder

The webhook module is disabled by default and does not send data unless explicitly enabled and configured in a future phase.

## Future Roadmap

- Stronger settings UX
- case-to-form integrations
- controlled webhook transport
- internal event wrappers
- future CRM and ERP integration adapters
