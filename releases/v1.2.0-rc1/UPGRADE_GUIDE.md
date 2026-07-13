# Upgrade Guide

## Target

Upgrade from the governed v1.1.x platform line to `YBY Core v1.2.0 RC1`.

## Expected impact

This is an additive release candidate.

No frozen v1.x browser runtime API is removed or renamed.

## New capability

- public governed lead intake endpoint
- governed inquiry notification email
- dedicated lead recipient setting
- richer safe REST success contract with `mail_sent`

## Before validation

1. Back up the current verified plugin package.
2. Confirm the target site already has the expected YBY website integration layer.
3. Confirm GTM or site tracking remains managed outside the plugin.
4. Record the current plugin version and active settings.

## After installing RC1 in a validation environment

1. Open the plugin settings page.
2. Confirm existing settings still load.
3. Confirm Project Studio and Runtime Viewer still exist.
4. Submit a controlled lead request.
5. Verify success response fields:
   - `success`
   - `case_id`
   - `duplicate`
   - `mail_sent`
6. Repeat the same Case ID within 30 minutes and confirm:
   - `duplicate: true`
   - `mail_sent: false`
