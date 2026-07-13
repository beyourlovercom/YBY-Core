# YBY Core v1.2.0 RC2

Release stage: `RC2`

Plugin version: `1.2.0`

Release name: `Lead Intake, Notification Center, and Brand Email Release`

## Summary

RC2 packages the governed lead intake foundation together with the Notification Center email provider enhancements and brand-aware email presentation settings.

## New in RC2

- Notification Center
- Configurable Primary / CC / BCC recipients
- Reply-To Policy
- Configurable Subject Template
- Email Branding
- Brand logo fallback

## Unchanged

- REST response contract
- Case ID format
- Landing behavior
- Thank You ownership of `generate_lead`
- Tracking API
- frozen Runtime APIs

## Included in RC2

- governed `POST /wp-json/yby/v1/leads`
- governed HTML inquiry email delivery
- multipart plain-text AltBody support
- Notification Center architecture with Email provider
- primary recipient, CC, BCC, and reply-to policy settings
- configurable lead email subject templates
- email branding settings with Brand fallback for logos
- server-side Case ID validation and canonical fallback
- 30-minute idempotency window
- 90-second send lock
- safe public REST error responses
- PII-safe analytics payload filtering

## Known limitations

- live WordPress activation pending
- live Gmail delivery pending
- live Bricks form submission pending

