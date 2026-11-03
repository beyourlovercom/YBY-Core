# Currency R13 — Independent HTTPS Woo + Stripe TEST webhook gate
Date: 2026-10-27

## Authority and desired order
Owner authorized: **first build and verify an isolated Woo + Stripe TEST HTTPS callback, then run test-mode transactions**. No live-mode provider calls, production customer/order/stock/email integrations, WWW or Dev production-equivalent orders; no merge/release/disable YayCurrency without separate approval.

### Phase H0 — independent site (must be real, not inferred)
- Machine may be US Mac mini, but being READY is NOT evidence of a disposable Woo site.
- Create a distinct WordPress + WooCommerce database, cookies, cron/action queue, SMTP interception, plugin folder and TLS origin. Do not use Dev/WWW DB or payment account mode. No sharing sessions or live payment settings.
- Name and verify an actually reachable **public HTTPS** origin with valid CA-signed certificate, never an unverified old temporary tunnel. The old P6 test webhook points to a disabled `trycloudflare.com` hostname; do not turn it back on or presume it is live.
- The independent sandbox wp-config.php requires `AICC_CURRENCY_STRIPE_TEST_SANDBOX=true`, `AICC_CURRENCY_STRIPE_TEST_HOST='<dedicated host>'`, `AICC_CURRENCY_STRIPE_TEST_DB='<separate DB_NAME>'`, `AICC_CURRENCY_STRIPE_TEST_NO_MAIL=true`. These explicit flags are **local-only**, not committed as secrets or production settings.
- Preflight: `python3 scripts/currency-r13-woo-stripe-https-preflight.py --origin https://<dedicated-host>`. This checks public DNS, CA/hostname/TLS and native Woo Store API availability; it DOES NOT prove Stripe delivery or signature validity.
- On the isolated WP installation, use `wp eval-file scripts/currency-r13-stripe-native-woo-preflight.php` to check test-mode Stripe gateway, native webhook hook and production-host/DB guard. This reads only metadata; no keys or webhook secrets are printed.
- **Neither preflight script installs plugins, starts a tunnel, creates a domain, configures live settings or accepts transactions.**

### Phase H1 — Stripe test account connection and signed delivery
- Stripe connector confirmed test account `acct_1SrJdHCxlWhQx8Rb` (`livemode=false`), but this does NOT itself establish the Woo payment plugin's test-mode OAuth connection.
- On isolated site only, connect official WooCommerce Stripe Payment Gateway in its **Test** tab, enable **test mode** and use the native built-in callback `https://<dedicated-host>/?wc-api=wc_stripe`. Current official Stripe Woo extension provisions/reconfigures its own webhook after test-mode connection (do NOT register a competing generic callback).
- In Woo Stripe settings verify **Test account connected + Test webhook configured**; in Stripe account inspect its test-only enabled endpoint domain, SSL health and required payment/refund events.
- Generate/send one TEST-mode event to this new official endpoint. Require an actual Stripe delivery attempt with HTTP 2xx and valid signature processed by native Woo extension; a normal GET, self-signed local mock or Stripe dashboard “enabled” flag alone is NOT sufficient.
- Verify replaying the same test event cannot duplicate Woo order-state transitions. Do not disclose the `whsec_` or API secret in logs, repo, or chat.
- If HTTPS, signatures, Woo test OAuth or isolation fails, STOP before payment.

### Phase H2 — minimal currency checkout matrix (after H1 only)
- 1 Woo test-mode successful Checkout payment for each USD/EUR/GBP/AUD/CAD. Check native Woo order's `currency` + `total` matches Stripe PaymentIntent `currency` + `amount` exactly, and original USD source/locked FX metadata remains immutable. Verify Provider vs reference FX are recorded separately.
- 1 failed/canceled test payment, Woo remains unpaid.
- 1 signed webhook success or failure delivery plus replay, no duplicate payment.
- 1 partial Stripe TEST refund on linked Woo order, reconcile Stripe refund currency/amount/ID and original locked USD FX ledger. A Woo-only `wc_create_refund(refund_payment=false)` is NOT a provider test refund.
- Return currency runtime/test gateways OFF and verify USD checkout/rollback, no changes to WWW, Dev, Stable or live YayCurrency.

## Evidence already available
- Native disposable Woo Store API Checkout POST in all five currencies **PASS** (offline COD, no Stripe call), FX snapshot quote contract: https://github.com/beyourlovercom/YBY-Core/actions/runs/40443725372
- 5-country tax/shipping dual-ledger and HPOS on/off **PASS** in synthetic isolated Woo, not actual end-to-end Stripe orders: https://github.com/beyourlovercom/YBY-Core/actions/runs/40440167501
- New isolated HTTPS/WP/Stripe preflight source is checked by the disposable Woo CI; **code validity does not imply real public callback is deployed**.
- Independent US Mac remote session was identified as READY, but tool execution was stopped by OpenAI platform safety validation. Official DC also reports monthly usage paused; **do not retry or reroute blocked commands**.

## Current gate as of writing
**H0 actual isolated public HTTPS deployment: NOT VERIFIED. H1 signed native webhook: NOT VERIFIED. H2 Stripe transaction/refund: NOT STARTED.** Stripe provider TEST account remains read-only; old disabled endpoint unchanged. R13 Stable/WWW NO-GO.
