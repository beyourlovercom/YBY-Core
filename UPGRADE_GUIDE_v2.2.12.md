# Andy Core v2.2.12 — Controlled upgrade checklist

1. Record current site plugin version, original code snapshot, signed update channel and existing outbox/lead tables.
2. Compare exact source commit with the signed release candidate. Confirm ZIP/plugin/readme/metadata all declare 2.2.12; verify SHA256 and Ed25519 using the pinned updater key.
3. The update adds no schema columns and preserves runtime marker 1.5.0 and updater compatibility marker 1.4.0.
4. Isolated Dev only: disable/guard actual mail sending, retain Cron backlog, ensure code-only rollback snapshot.
5. Validate Ignore Notification action requires a reason, appends an audit record and reduces alert count while preserving inquiry data. Processing and smtp_accepted must remain unaltered.
6. Do not resend historical cases automatically; check recipient and provider records before any real retry.
7. Merge, immutable tag, public-channel Stable release and individual website installation are distinct approval gates.
