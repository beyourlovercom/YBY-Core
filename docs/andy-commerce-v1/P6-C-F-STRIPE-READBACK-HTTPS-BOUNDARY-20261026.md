# P6-C–F Test-only checkpoint — 2026-10-26

**Scope:** isolated US Mac mini Woo sandbox; GitHub is the single source of truth. This checkpoint is **not** a provider/Woo end-to-end PASS, deploy approval, or release approval.

## Exact baseline and readback
- Sole Commerce integration: Draft PR #213 `ca59db1547733c967c3eab21b1eefe7d0a07b963`. P6 isolated Draft PR #225, parent checkpoint `9b281321ec62855209613929a71f70eb43f79580`.
- The linked Stripe context is account `acct_1SrJdHCxlWhQx8Rb`, **test mode** (`livemode=false`). We independently read Stripe PaymentIntents and Charges in this session.
- Standalone USD 113.40 succeeded: PaymentIntent `pi_3UUd26CxlWhQx8Rb00bHWmbl`, received 11340 cents, `livemode=false`.
- Standalone 3DS USD 1.21 succeeded: `pi_3UUdbVCxlWhQx8Rb1pl5cxLt`, charge `ch_3UUdbVCxlWhQx8Rb1lRrcJc0`, `three_d_secure.authentication_flow=challenge`, `result=authenticated`, `captured=true`.
- Standalone card-decline USD 1.22 correctly failed: `pi_3UUdcYCxlWhQx8Rb1VuEtwzZ`, `requires_payment_method`, `card_declined/generic_decline`, received 0 cents. These three are **provider standalone** tests, NOT Woo-originated test orders.
- EUR/GBP/AUD/CAD Test Checkout sessions all remain `open/unpaid` on current API readback. Amounts are fixed synthetic examples, **not FX rates or settlement**.
- Test webhook `we_1UUcnBCxlWhQx8Rb1AmD3g0R` is **disabled**. Stripe test refund list for the USD success intent is **empty**. Previous refund write hit a platform safety block; no other channel may bypass that restriction. An Owner-initiated Stripe Dashboard Test refund, followed by read-only API verification, is an acceptable distinct future route.

## Isolated Woo readback
- Loopback-only Woo `127.0.0.1:18973`, independent DB `aicc_commerce_p5_20261023`, HPOS ON; Woo 11.2.1 and Andy Commerce 1.0.8.
- Official `woocommerce-gateway-stripe` 11.1.1 **installed but inactive**; `woocommerce_stripe_settings` **absent**, no Woo Test OAuth established.
- Sandbox `WP_ENVIRONMENT_TYPE=local`, `WP_HTTP_BLOCK_EXTERNAL=true`; MU guard retains `pre_wp_mail`, external `pre_http_request` deny, COD-only gateway and outbound webhook suppression. M14 local auto-marketing is `no`.
- Remote read-only localhost P6-F safety gate: **14/14 PASS**. 45 Commerce PHP harnesses: **45/45 PASS**. Local candidate ZIP contract: **PASS**, and test/sandbox drivers **excluded** from ZIP. These executed on local Commerce base `ca59db1`; do **not** relabel them as new P6 PR head tests.
- On parent P6 HEAD `9b28132`, four applicable GitHub workflows completed SUCCESS. Any successor HEAD needs separate readback.

## P6-C isolated HTTPS/OAuth admission contract (NOT YET SATISFIED)
1. A **dedicated stable HTTPS hostname and ingress** for a disposable Woo test site must be inspected before exposure. Never route the original entire `127.0.0.1:18973` WordPress application/admin through an unrestricted public tunnel. Do not touch unrelated live Cloudflare tunnels.
2. Public route allowlist must be limited to necessary guest checkout assets/Store API and the documented Woo Stripe **POST** webhook endpoint `/?wc-api=wc_stripe`. Block `/wp-admin`, `/wp-login.php`, `xmlrpc.php`, unrelated APIs, and any broader origin access; test both positive and negative routes. A correctly signed webhook must be checked by the official Stripe gateway, not declared valid just because an edge proxy accepts it.
3. Preserve the default sandbox external HTTP/email/shipment/tracking/ERP protections. If Woo Test OAuth/API calls require outbound access, add a **separately audited least-privilege test-only exception**, enforcing `livemode=false` and test credentials at the application layer. An `api.stripe.com` hostname allowlist **cannot** by itself rule out Live API calls.
4. Verify site URL/callback origin consistency and ensure private Owner OAuth/admin approval is not exposed through public routes. The M14 fixture currently hardcodes `http://127.0.0.1:18973`; its localhost-only readiness cannot automatically count as public HTTPS Woo/M15–M16 runtime evidence.
5. Before changing any public ingress, write a rollback/teardown plan and obtain Owner approval for any exposure outside the already authorized **limited** independent HTTPS test entry; if OAuth requires private Owner action, ask once only. Never request or paste API secrets.
6. Complete Woo Test tab connection and confirm Woo and Stripe account ID, test status, webhook `Configured`, and genuine signed delivery. Only then attempt NEW Woo-originated Test Checkout orders, Woo HPOS reconciliation, 3DS/decline/cancel, M15/M16 quotas, provider Test refunds, and each currency paid capture. Previously completed standalone Checkout sessions must not be charged again.

## Gate status
| Gate | Accurate latest status |
|---|---|
| P6-A offline HMAC/idempotency | Synthetic local only |
| P6-B Woo/HPOS M15/M16 local baseline | PASS, COD-only |
| P6-C ChatGPT Stripe Test account | PASS |
| P6-C stable HTTPS Woo Test OAuth | BLOCKED |
| P6-C actual Stripe-delivered Woo webhook | NOT TESTED |
| P6-D standalone USD, 3DS, decline | PASS (standalone only) |
| P6-D Woo Stripe orders, refunds, callbacks | BLOCKED |
| P6-E 4 foreign currencies paid/settled | NOT TESTED |
| P6-F 14/14 local safety, 45 PHP harness, local ZIP | PASS (baseline only) |
| P6-F full Owner UAT and closure | **OPEN / BLOCKED** |

**No main merge, Stable publication, Dev/WWW deployment, Stripe Live API, actual shipment, emails or READY device restarts. Issue #216 stays OPEN.**

Official behavior references:
- https://woocommerce.com/document/stripe/setup-and-configuration/connecting-to-stripe/
- https://woocommerce.com/document/stripe/setup-and-configuration/stripe-webhooks/
- https://woocommerce.com/document/stripe/customer-experience/testing/
