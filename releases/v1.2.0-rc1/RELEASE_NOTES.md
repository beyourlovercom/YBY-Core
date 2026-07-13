# YBY Core v1.2.0 RC1

Release stage: `RC1`

Plugin version: `1.2.0`

Release name: `Lead Intake & Notification Foundation`

## Summary

This release candidate preserves the governed YBY Core platform baseline and adds the approved lead intake foundation required for production verification.

## Included in RC1

- governed `POST /wp-json/yby/v1/leads`
- governed HTML inquiry email delivery
- multipart plain-text AltBody support
- dedicated lead recipient email option
- server-side Case ID validation and canonical fallback
- 30-minute idempotency window
- 90-second send lock
- safe public REST error responses
- PII-safe analytics payload filtering

## Preserved platform baseline

- Project Studio
- Runtime Viewer
- Brand Settings
- Brand PHP helpers
- Google Fonts control
- Project Engine
- Page Profile Engine
- Content Runtime Engine
- Project Template Runtime
- frozen v1.x public runtime APIs

## RC1 source commit

`09d6a890d64f6b4463c7af7bf2e49f16ef514eae`

## Validation status

- PHP lint: passed for all plugin PHP files
- `git diff --check`: clean
- ZIP root: verified as `yby-core/`
- ZIP paths: verified forward-slash only

## Not done in RC1 preparation

- no deployment
- no WordPress upload
- no Git tag
- no GitHub Release publication
