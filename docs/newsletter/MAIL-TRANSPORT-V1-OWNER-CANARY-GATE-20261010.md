# Andy Core Mail Transport V1 — Newsletter-scoped SMTP, default OFF

Status: **Core Mail Transport V1 source merged via PR #75; isolated Dev code installation reported PASS.** This sender-identity update is code/doc/test only. Newsletter API and outbound remain OFF, SMTP credentials remain unconfigured, and no real mail or subscriber data change has been authorized. The second independent Dev readback was blocked by execution safety; its result remains unverified.

## Source and ownership

Andy Core owns the Newsletter double-opt-in process. The hosting/infrastructure layer exclusively owns SMTP credentials and relay. This adapter configures PHPMailer **only while sending a single Andy Core Newsletter confirmation**, then restores all temporary hooks in a finally block. WooCommerce and WordPress mail retain existing authoritative transports. No global phpmailer_init or pre_wp_mail override is installed by Andy Core.

The installed Dev Safety MU retains its terminal pre_wp_mail deny for non-authorized sends, including G5 payment/checkout and Woo. This adapter cannot override that veto. An accepted wp_mail() return is NOT verified inbox receipt.

## Host-only transport keys (never place values in GitHub, WP database, tool logs or PR comments)

| Key | Requirement |
| --- | --- |
| YBY_MAIL_TRANSPORT_ENABLED | WordPress code constant must equal boolean true |
| YBY_MAIL_TRANSPORT_DRIVER | WordPress code constant must be "smtp" |
| YBY_MAIL_TRANSPORT_PROVIDER | WordPress code constant must be "tencent_exmail"; default OFF |
| YBY_MAIL_APPROVED_FROM_EMAIL | Required site-local constant: `newsletter@beyourlover.com` for BeYourLover Newsletter only. Other Andy Core installations must set their own approved sender; never hard-code BYL sender globally |
| YBY_MAIL_SMTP_HOST | Hosting-controlled SMTP hostname |
| YBY_MAIL_SMTP_PORT | **465 only** for this Tencent Enterprise Mail V1 profile; other ports blocked |
| YBY_MAIL_SMTP_SECURITY | Only "tls" or "ssl" |
| YBY_MAIL_SMTP_USER | Confidential hosting environment variable |
| YBY_MAIL_SMTP_PASSWORD | Confidential hosting environment variable |
| YBY_MAIL_FROM_EMAIL | Authenticated sender address |
| YBY_MAIL_FROM_NAME | Trusted sender name |

Configuration is OFF unless all values validate. No PHP mail fallback, no plaintext SMTP, no auto-retries, no TLS-peer-verification override, no WordPress option for SMTP credentials.

The existing two independent Newsletter release flags still apply: API requires both YBY_NEWSLETTER_API_ENABLED and yby_newsletter_enabled; outbound requires YBY_NEWSLETTER_OUTBOUND_ENABLED and yby_newsletter_outbound_enabled. The shared `YBY_Newsletter_REST_Controller::subscribe_ready()` method checks API, outbound and read-only SMTP transport readiness for **REST, shortcode and global popup**. It refuses an unconfigured transport before beginning pending subscription (HTTP 503 with no pending row), while setting the public form data attribute to `0`. This checks readiness of configuration, NOT SMTP connectivity/delivery.


## Tencent Enterprise Mail profile — selected by Owner

Owner selected Tencent Enterprise Mail with **`newsletter@beyourlover.com` as the separate Andy Core Newsletter confirmation sender**. The Owner revised the earlier support-account testing plan. **`support@beyourlover.com` remains WWW's existing public/customer-service and transactional communication address; it must not be switched or repurposed for this Newsletter Canary.** Whether the newsletter mailbox itself exists, permits independent Tencent SMTP login, and has a client authorization credential must be verified independently before any real auth or mail. No automatic WWW configuration migration is assumed. The first runtime adapter explicitly accepts only these **user-configured** relay hostnames, without automatic fallback:
- Primary: smtp.exmail.qq.com — SSL / port 465.
- Overseas alternative: hwsmtp.exmail.qq.com — SSL / port 465, only after separately verifying the host and selecting it explicitly.

For this Newsletter, the intended Tencent SMTP username, From and site-local approved constant must all be **`newsletter@beyourlover.com`**. This is a planned identity contract, not proof that a separate Tencent client-app SMTP credential is active. Other Andy Core sites may independently configure their own email; no global hardcoding. Sending as an alias/delegated address requires separate review. The corporate login/client app-specific password, if required, is entered **only in the secret manager / approved private host configuration**; it is never pasted into chat, GitHub, .env committed to repo, WP options or normal logs.

Tencent Enterprise Mail admin may need to allow SMTP client access to that account; if secure login is enabled, the member may need a client authorization password. Official service information: https://service.rtxmail.net/help/232.html. Do not assume MX, SPF inclusion, or successful webmail sign-in means SMTP authentication is ready.

BeYourLover DNS baseline (observed, not a send-pass guarantee): root MX includes Tencent Enterprise Mail, SPF includes spf.mail.qq.com and amazonses.com, DMARC p=none. DKIM must be verified against the **real chosen Tencent sender identity** through provider admin/actual received mail headers before claiming alignment. Do not alter DNS without separate approval.

**Sender / recipient are different roles:** `newsletter@beyourlover.com` is the planned **outbound SMTP sender**, not automatically the recipient. **Safe Canary recipient**: Owner must separately confirm the exact test recipient to the approved secure execution workflow, not to source or logs. Only one approved email is allowed; no duplicate retries after uncertain transport acceptance. A transport-ready configuration means only **static validity**, not SMTP login, provider acceptance, spam-folder receipt, or confirmed subscription.

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
