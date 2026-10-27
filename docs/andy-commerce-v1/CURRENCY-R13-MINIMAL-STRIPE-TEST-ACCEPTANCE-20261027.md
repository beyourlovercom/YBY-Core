# Currency OS — Minimal Stripe Test-mode acceptance for full YayCurrency replacement
Date: 2026-10-27

## User-approved final product
- Andy Commerce Currency OS must **replace YayCurrency only after feature parity and explicit production cutover authorization**.
- Woo customers may checkout directly in **USD, EUR, GBP, AUD, CAD**, not just view translated prices.
- WooCommerce native line/cart/order taxes, shipping and payment amounts are the authoritative amounts in the **selected shopper's currency**.
- Canonical product cost/discount policy and internal reporting remain **USD-first**, with immutable order-time FX metadata. Provider processing/settlement currency is separately recorded and **MUST NOT** be conflated with reference FX.
- Compact currency selector remains shortcode-rendered in footer, including independent Cart footer. Marketing owns campaign eligibility; Currency does not reimplement it.
- No standalone Currency plugin: use **Draft PR #213** as single Andy Commerce integration entry; coordinate with Marketing without modifying its rules.

## Completed evidence — do not repeat
- Dev Andy Commerce 1.0.12 installed and active, anonymous product/catalog/cart API currencies match USD/EUR/GBP (Issue #233 CLOSED).
- Source-only 5-currency Woo Store API **Checkout POST** in disposable WordPress/Woo/MySQL, offline in-memory COD, explicit external HTTP block and no customer emails: run https://github.com/beyourlovercom/YBY-Core/actions/runs/40443725372. Every Woo order persisted correct shopper currency, minor-unit totals, original USD $20.00 and FX snapshot:
  - USD 2000; EUR 1800; GBP 1560; AUD 3000; CAD 2701 (CAD +1 cent native per-unit rounding).
  - Quote contract rejects Stripe amount mismatch, currency mismatch, and livemode true; no Stripe API was invoked in CI.
- USD merchant-source and native Woo shipping/tax dual-ledger 5-region pure contract CI previously PASS; Woo HPOS on/off persisted FX metadata; internal no-provider cumulative refunds and over-refund rejection PASS. These are **not** live-provider/actual-zone checkout signoff.

## Smallest acceptable Stripe TEST mode transaction UAT
1. **Identity and isolation preflight**: verify Woo test site is an independent HTTPS disposable instance with no shared customer/order DB, disabled SMTP/stock/ERP/fulfillment, test-only Woo Stripe credentials and merchant account context (test mode). Confirm Woo-selected currency & total match Stripe PaymentIntent `amount` and lowercase `currency` exactly. Confirm native cart + shipping + tax, reference FX age and no dual FX engine. Start with webhook endpoint OFF until reachable signed test receiver verified. Never recycle stale trycloudflare URL. No Stripe live mode, no real funds.
2. **Five payments**: 1 *test-mode* successful Checkout payment in each USD, EUR, GBP, AUD, CAD. Validate Stripe PI/charge `livemode=false` and exact currency/amount; Woo order total equals PI captured amount; record PI id, test charge id, event ID, order FX rate and any provider settlement/fees, no double conversion. Do not substitute an offline COD order or raw PaymentIntent created outside Woo for this acceptance.
3. **One failure + callback**: 1 intentional test-mode declined/canceled payment, verify Woo not marked paid; 1 signed `payment_intent.succeeded` or `payment_intent.payment_failed` webhook delivered to independent HTTPS receiver with accepted signature, matching WC order ID/currency/amount. Replay same event and ensure idempotence (zero duplicate payments/order transitions). Never log signing secret or PII.
4. **One partial refund**: create 1 Stripe TEST mode refund tied to the above authorized test PI, verify linked Woo native refund exists, refund amount/currency and Stripe refund ID match, original order FX snapshot remains immutable, original USD ledger reversal uses locked checkout FX, cumulative amount does not exceed paid amount. Do not claim refund completion solely from WC bookkeeping.
5. **Rollback**: switch OFF Currency only in isolated sandbox, verify base USD products, Woo/USD checkout, ordinary test gateway, order history/FX metadata and Marketing independent OFF remain intact. Restore test gates to OFF and remove disposable endpoints and temporary keys. WWW and live YayCurrency remain unchanged.

## No-go gates
- No verified independent HTTPS Woo Stripe test gateway/receiver or signed webhook; legacy Stripe test endpoint is **disabled** and uses ephemeral Cloudflare URL, so cannot count as current evidence.
- Connected test Stripe account previously only exposed USD test PaymentIntents, with no new provider PI creation, foreign payment, or Stripe refund tested in this branch.
- Existing DEV 1.0.12 runtime explicitly filters out ALL payment gateways for non-USD. Must remain OFF until a separately signed, isolated, reversible gateway bridge is verified.
- Complex multi-coupon/BOGO, actual five-zone tax/shipping Checkout amounts, provider foreign-currency support, gateway states and callback retry/HPOS need explicit end-to-end signoff.
- No main merge, Stable, WWW deployment, YayCurrency deactivation, real customer order, real funds or new production payment method in this PR without separate explicit approval.

## CI contract/source
- `packages/andy-commerce/tests/currency-r13-five-currency-stripe-contract-disposable-wc.php` uses real WC Store API POST with offline COD and no provider calls.
- `.github/workflows/andy-commerce-p5-disposable-woo.yml` runs USD/EUR/GBP/AUD/CAD isolated checks with external HTTP blocked.
- CI PASS proves five-currency Checkout/order/FX **data and quote** contract, NOT Stripe test-mode payment settlement/callback/refund.

## Completion rule
Do not mark Currency OS complete or remove YayCurrency until all 5 Stripe test payments + failed payment + signed idempotent webhook + real Stripe test refund + Dev owner UAT + rollback are evidenced, linked in Issue #234, and final production cutover separately approved.
