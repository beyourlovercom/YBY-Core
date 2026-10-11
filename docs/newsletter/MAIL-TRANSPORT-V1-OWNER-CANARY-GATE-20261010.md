# Andy Core Mail Transport V1 — Newsletter-scoped SMTP, default OFF

Status: **DRAFT / CODE-ONLY**. This change does not send real mail, enable Newsletter API/outbound, configure SMTP credentials, write subscribers, alter WooCommerce mail, or add a plugin.

## Source and ownership

Andy Core owns the Newsletter double-opt-in process. The hosting/infrastructure layer exclusively owns SMTP credentials and relay. This adapter configures PHPMailer **only while sending a single Andy Core Newsletter confirmation**, then restores all temporary hooks in a finally block. WooCommerce and WordPress mail retain existing authoritative transports. No global phpmailer_init or pre_wp_mail override is installed by Andy Core.

The installed Dev Safety MU retains its terminal pre_wp_mail deny for non-authorized sends, including G5 payment/checkout and Woo. This adapter cannot override that veto. An accepted wp_mail() return is NOT verified inbox receipt.

## Host-only transport keys (never place values in GitHub, WP database, tool logs or PR comments)

| Key | Requirement |
| --- | --- |
| YBY_MAIL_TRANSPORT_ENABLED | WordPress code constant must equal boolean true |
| YBY_MAIL_TRANSPORT_DRIVER | WordPress code constant must be "smtp" |
| YBY_MAIL_SMTP_HOST | Hosting-controlled SMTP hostname |
| YBY_MAIL_SMTP_PORT | 587 for tls/STARTTLS or 465 for ssl/implicit TLS |
| YBY_MAIL_SMTP_SECURITY | Only "tls" or "ssl" |
| YBY_MAIL_SMTP_USER | Confidential hosting environment variable |
| YBY_MAIL_SMTP_PASSWORD | Confidential hosting environment variable |
| YBY_MAIL_FROM_EMAIL | Authenticated sender address |
| YBY_MAIL_FROM_NAME | Trusted sender name |

Configuration is OFF unless all values validate. No PHP mail fallback, no plaintext SMTP, no auto-retries, no TLS-peer-verification override, no WordPress option for SMTP credentials.

The existing two independent Newsletter release flags still apply: API requires both YBY_NEWSLETTER_API_ENABLED and yby_newsletter_enabled; outbound requires YBY_NEWSLETTER_OUTBOUND_ENABLED and yby_newsletter_outbound_enabled. The REST subscribe handler calls the new read-only transport readiness check **before beginning pending subscription**; unconfigured transport returns 503 without inserting a pending subscriber. This checks readiness of configuration, NOT SMTP connectivity/delivery.

## Synthetic CI scope

The only test-specific config bypass is WP_CLI in disposable WordPress at newsletter-validation.test, with an explicit CI-only test constant plus an existing pre_wp_mail fake interceptor. It cannot send through real Dev/WWW. Validate:
- New no-network tests: invalid host/TLS/port/from, missing server secret, Dev Safety veto, strictly limited subject/recipient, temporary hook removal, no Woo email transport override.
- Existing M1 and M3 WordPress/MySQL REST tests: actual disposable pending, fake intercepted confirmation, explicit user confirmation POST and replay denial, no real email and precise synthetic cleanup.
- Exact-head Andy Core Regression and Newsletter WordPress/MySQL GitHub CI.

## Real Canary gates (NOT opened by this code PR)

1. Owner Code UAT of scoped SMTP adapter, safety and noninterference.
2. Separately authorized exact-head GitHub merge, then independently approved Dev-only deployment/rollback.
3. Identify actual SMTP provider and hosting integration; verify sender domain SPF, DKIM and DMARC and provider transport diagnostics **without exposing secrets**.
4. Use a separately authorized approved executor and one-use mail permit at Dev for at most one Owner-designated mailbox. Prior unsafe process-local mail execution was blocked: do not retry via alternate tools/encoding, fake config, weak security, different identities or permission bypass.
5. Verify real inbox receipt and explicit human confirm POST, pending→subscribed, replay prevention, one-test-row cleanup, default flags restored OFF and no G5/WWW/ERP side effects.
6. Dev→ERP real signed GET remains separately blocked under ERP Issue #452; runner-only synthetic HMAC E2E is not proof of that live integration.

If a previously blocked execution path is still unavailable, stop at REAL_MAIL_CANARY=BLOCKED. Do not claim SMTP delivery, functional active Newsletter, complete ERP live integration, or Owner Final PASS from synthetic CI alone.

**No WWW/Production modifications or plugin installations in this branch.**
