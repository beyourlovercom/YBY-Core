# Production Verification

## Goal

Verify that `YBY Core v1.2.0 RC1` is trustworthy for controlled WordPress validation.

## Completed engineering checks

- complete governed baseline restored before packaging
- approved lead intake commits integrated
- PHP lint passed for every plugin PHP file
- `git diff --check` passed
- ZIP root verified
- ZIP path separators verified
- SHA256 generated

## Validation checklist

1. Confirm plugin activates without fatal errors.
2. Confirm `YBY OS > Settings` loads.
3. Confirm Brand Settings still load.
4. Confirm Project Studio still exists.
5. Confirm Runtime Viewer still exists.
6. Confirm frontend runtime globals still exist.
7. Submit a new lead and verify:
   - valid canonical Case ID
   - `duplicate: false`
   - `mail_sent: true`
8. Repeat the same lead within the idempotency window and verify:
   - `duplicate: true`
   - `mail_sent: false`
9. Confirm no recipient email appears in frontend config, REST payloads, or `dataLayer`.
10. Confirm no stack trace or internal path leaks in failure responses.

## Release source rules

- active plugin source: `yby-core`
- historical reference only: `YBY-OS-v1` plugin copies
- official future production package source: GitHub Release
