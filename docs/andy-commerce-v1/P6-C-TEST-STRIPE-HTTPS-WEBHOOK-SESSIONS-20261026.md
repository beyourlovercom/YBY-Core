# P6-C Stripe test-mode HTTPS and five-currency Checkout preflight — 2026-10-26

## Verified directly from the connected Stripe account

The connected account was read via Stripe account-list API and shows **livemode=false**. This is independently confirmed through Stripe GET records, not inferred from the browser being signed in. This is a **ChatGPT Stripe connection**, not automatically a WooCommerce-specific OAuth connection.

A **test-only** Stripe webhook endpoint was created successfully and was read back as `enabled`, `livemode=false`. Its ID is `we_1UUcnBCxlWhQx8Rb1AmD3g0R`. The webhook signing secret was written exclusively to a mode-0600 private file on authorized `us-macmini`; **neither secret nor opaque URL path is stored in GitHub**.

## Public HTTPS receiver (isolated from WordPress)

A separate Cloudflare quick tunnel on `us-macmini` forwards only to local TCP `127.0.0.1:19318`, an isolated Python receiver. WordPress WooCommerce remains on `127.0.0.1:18973` and is **not proxied**. No existing Cloudflare tunnels or READY devices were restarted. Receiver handles only the opaque P6 webhook POST path; all of `/`, `/wp-admin/`, `/wp-login.php`, `/wp-json/` and GET to webhook path returned HTTP 404.

Webhook verification uses Stripe-style signed header `t=...,v1=...`, HMAC-SHA256 on raw body, five-minute freshness window, `livemode=false`, an explicit event type allowlist and SQLite event-ID deduplication. Local signed request returned HTTP **200**, duplicate returned HTTP **200** and remained one stored event; bad signature and expired signature each returned HTTP **401**. Repeating over the **public HTTPS URL** returned signed HTTP **200**, replay HTTP **200**, invalid-signature HTTP **401**. **These were synthetic locally signed probes, not actual Stripe-delivered webhooks.** Successful actual Stripe event delivery remains unproven.

## Five Stripe test Checkout Sessions

Five genuine Stripe API Checkout Sessions were created under `livemode=false`, with **fixed synthetic display values, NOT verified provider FX settlement**:

| Currency | Test session ID prefix | Amount (minor units) | Latest GET |
|---|---|---:|---|
| USD | `cs_test_a1rRdh...` | 11340 | `open / unpaid` |
| EUR | `cs_test_a1bnSG...` | 10206 | `open / unpaid` |
| GBP | `cs_test_a1sGLB...` | 8845 | `open / unpaid` |
| AUD | `cs_test_a1b7NW...` | 17010 | `open / unpaid` |
| CAD | `cs_test_a1HIvC...` | 15309 | `open / unpaid` |

All five were independently read back from Stripe using GET Checkout Sessions with `livemode=false`, `status=open`, `payment_status=unpaid`. These Checkout Sessions were created directly through the connected Stripe API rather than within WooCommerce; therefore they DO NOT establish a Woo order-to-provider transaction link. No card payment, 3DS challenge, capture, decline or refund was executed. The attempt to automatically open the test Checkout in the US Mac Chrome session was blocked by the platform safety gate; it was not retried via circumvention.

## Status and follow-up

- **P6-C partial PASS:** connected Stripe test account verified; isolated public HTTPS endpoint registered/testmode enabled; synthetic signed public receiver tests passed; official Stripe plugin 11.1.1 is installed only on isolated US Mac Woo but currently inactive.
- **P6-C still OPEN:** actual provider webhook delivery, WooCommerce extension's own **Test** OAuth connection, and a stable isolated HTTPS Woo callback. The quick tunnel is temporary, not a durable production or Woo OAuth URL.
- **P6-D/E not PASS:** live-in-test card charges, 3DS, declines, refunds, actual multi-currency provider settlement and HPOS payment-link reconciliation remain unverified. Session creation does not equal capture.
- **P6-F final closure BLOCKED** until those real test transactions and approved final UAT are demonstrated.
- No production payment, WWW/Dev site change, main merge, Stable release, live-email, stock/ERP/fulfillment or READY restart was performed.

Keep Issue #216 and P6 Draft PR #225 open. The sole Commerce integrator PR #213 is unchanged. If the ephemeral tunnel changes, disable or replace the stale Stripe test endpoint before reusing it. Remove test endpoint and stop the temporary tunnel/receiver after the final test run.
