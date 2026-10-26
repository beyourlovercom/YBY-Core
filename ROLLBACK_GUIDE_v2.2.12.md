# Andy Core v2.2.12 — Code-only rollback

- Preserve exact previous plugin archive and all current outbox/lead/activity records before an authorized install.
- Restore only the plugin code; never delete ignored receipts or stored ignore reasons. v2.2.11 will not display the Ignore action but will not retry ignored status.
- Do not force ignored to pending, release historical Cron backlog, or resend SMTP-ambiguous cases.
- Confirm site update-channel settings, code version, admin navigation and mail/Cron isolation after restoration.
- Production rollback is a separate authorized operation.
