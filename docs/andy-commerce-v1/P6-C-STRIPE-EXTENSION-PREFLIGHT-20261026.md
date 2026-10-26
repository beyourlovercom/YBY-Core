# P6-C official Woo Stripe extension isolated compatibility preflight — 2026-10-26

**Source/release boundary**: This evidence is for the existing *Draft* P6 PR #225, stacked on the only Commerce integration PR #213. This test did not merge main, publish Stable, alter installed Commerce on Dev/WWW, request payment credentials, create external payment or refund, or restart any READY device.

## User connection status

The **ChatGPT Stripe plugin now reports installed=true** from the connected plugin directory. However, this ChatGPT Stripe connection is **not the same** as the WooCommerce Stripe Gateway test OAuth connection on a specific WordPress site. No Stripe provider account action tools were exposed to this conversation, so account test-mode identity, live-vs-test objects, webhook destinations, or payment/refund records were **not independently read** via the ChatGPT Stripe connector.

## Isolated Woo extension preparation actually performed

- Machine: authorized `us-macmini`, disposable local WooCommerce site `http://127.0.0.1:18973`, MySQL `aicc_commerce_p5_20261023`, WP 7.1.3, WooCommerce 11.2.1, Andy Core 2.2.11, Andy Commerce 1.0.8.
- Fetched the official version-specific WordPress plugin distribution over HTTPS:
  `https://downloads.wordpress.org/plugin/woocommerce-gateway-stripe.11.1.1.zip`.
- Verified HTTP 200, complete archive integrity (`unzip -t` PASS). SHA256 of downloaded ZIP:
  `420621d736a74d7a785f80b69aeed0cbe07f1a4810c013a2b86205423ebb994e`.
- Installed **WooCommerce Stripe Payment Gateway 11.1.1** on disposable local site **without activation**.
- In a separate controlled local compatibility pass, activated it briefly and read back: loaded successfully, no fatal error, Woo gateway **allowlist still `cod` only**, WordPress outbound HTTP blocked, M14 OFF.
- Immediately **deactivated** official Stripe extension. Read-back verified `woocommerce-gateway-stripe,inactive,11.1.1` and `woocommerce,active,11.2.1` / `andy-commerce,active,1.0.8`. Store API `/wc/store/v1/cart` responded HTTP 200.
- No Woo Stripe test OAuth account connection has been established or attempted on localhost. No API keys, login state, Stripe charge/refund objects or external webhooks were accessed.

## Remaining P6-C requirements

1. Set up an **independent test-only public HTTPS callback target**, bound to disposable WordPress staging or an authenticated test-only webhook receiver, without exposing the existing localhost admin to the internet. At present `http://127.0.0.1:18973` is **not** an internet-reachable Stripe callback.
2. Using a secure, explicitly authorized OAuth/provider flow, connect Woo Stripe's **Test** tab separately from Live. Independently verify test mode and test webhook status, do not copy or reveal API secrets.
3. Before permitting any provider calls, verify `livemode=false`, connection/account identity, webhook signature verification, public callback restrictions, Woo email/stock/ERP/shipment suppression and fully isolated test orders.
4. Then execute P6-D/E actual test-only success, decline, 3DS, currency and provider refund scenarios, with provider test object IDs redacted and durable Woo/HPOS reconciliation.

### References

WooCommerce official [Stripe extension testing guide](https://woocommerce.com/document/stripe/customer-experience/testing/) documents **separate Test-mode connection**, which is not automatically implied by a Live or ChatGPT connector. Official [Webhook configuration](https://woocommerce.com/document/stripe/setup-and-configuration/stripe-webhooks/) requires webhook endpoints reachable from Stripe; the extension can automatically configure them upon connection.

**P6-F final status remains BLOCKED** until genuine provider evidence exists. Existing local Woo COD/M15/M16 results are not Stripe capture, real 3DS or Stripe refund verification. Keep #216 OPEN and main/Stable/Dev/WWW unchanged.
