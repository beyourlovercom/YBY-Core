# Andy Core v2.2.12 — Ignore Notification signed release candidate

Not yet a Stable release. Source baseline: merged PR #229; published v2.2.11 remains immutable.

- Inquiry → Notification Records: controlled Ignore Notification for pending, failed or exhausted mail jobs, requiring a reason.
- Store actor ID, timestamp and reason in the existing lead activity table; commit with the receipt's ignored state.
- Cancel only the affected lead retry events; ignored does not count as pending and is not eligible for automatic or manual resend.
- Preserve inquiry Closed/Archive, original SMTP errors and full audit history. Processing and smtp_accepted cannot be ignored.
- Plugin build and update metadata must agree on 2.2.12; SHA256, Ed25519 and ZIP root validation required.
- No new database schema/marker bump (runtime 1.5.0; compatibility 1.4.0).
- Authorizations for final tag, Stable/latest in the public update channel and each production install are separate. Do not send real mail, execute historical Cron, or replay old failed notifications.
