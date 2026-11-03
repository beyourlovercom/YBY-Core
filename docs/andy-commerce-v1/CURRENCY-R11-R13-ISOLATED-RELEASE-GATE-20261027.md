# Currency OS R11–R13 — isolated Woo acceptance and production release gates
Date: 2026-10-27

## Canonical source and evidence
- Single Andy Commerce integrator: **Draft PR #213** (`beyourlovercom/YBY-Core`).
- Exact code HEAD verified: `834d53ceae42f47fcc05f1ef27a46a98c160c705`.
- One-use WordPress 7.0, WooCommerce 11.2.1, MySQL 8.0, `commerce_p5_isolated`, `http://commerce-p5.invalid`.
- External HTTP denied, wp_mail intercepted, WP Cron OFF; COD stub is in-memory and offline only. No real provider or WWW requests.
- GitHub Actions disposable Woo: https://github.com/beyourlovercom/YBY-Core/actions/runs/40438636139 — **SUCCESS**.
- Exact code HEAD CI: **6 SUCCESS, one conditional Currency preview SKIPPED, zero failures**.
- Dev customer-facing currency runtime remains separate and guarded. Production/WWW Checkout, refunds and gateways **not modified**.

## R11: Classic, Checkout Blocks and immutable FX order metadata
- Native `WC()->checkout()->create_order()` actually creates a local isolated CAD WC_Order and saves the snapshot through the Classic Woo hook.
- Native Checkout Blocks pre-payment `woocommerce_store_api_checkout_order_processed` hook stores the same data without rewriting an existing FX snapshot.
- Native GET `/wc/store/v1/checkout` with Woo Store API nonce returned **200**, read-only preview, no payment.
- Native **POST `/wc/store/v1/checkout`** with Woo Store API nonce, virtual synthetic product, expected total `2701` minor, and sandbox-only offline COD returned **200** and created disposable order **#41**. The order was read back with CAD total **27.01**, original USD 20.00 ledger and locked synthetic FX snapshot.
- Example original USD merchandise `$12.50 × 2 = $25`, native fixed-cart USD coupon `$5`, source USD net `$20`. Unit-first CAD conversion yields CAD 33.76 - CAD 6.75 = **CAD 27.01**, theoretical direct conversion CAD 27.00; **+1 CAD cent** rounding recorded explicitly.
- Original USD product values remain unchanged in Woo source data.
- **Unsupported mixed taxes or shipping charges cause no "reconciled" FX snapshot** — the fail-closed test passes; this is **NOT acceptance of tax-inclusive/shipping-inclusive orders**.
- Blocks hook and POST test are guarded by `AICC_CURRENCY_RUNTIME_SANDBOX=true`, exact isolated DB + hostname + WP local environment. No activation in Dev/WWW.

## R12: refunds and locked-rate reconciliation
- Real Woo `wc_create_refund()` uses `refund_payment=false`, `restock_items=false` only.
- Three cumulative internal refunds **CAD 5.00 + CAD 5.00 + CAD 17.01 = CAD 27.01** preserve original rate and reverse total **USD 20.00** exactly.
- Each partial refund has its own immutable source-order FX rate and cumulative USD/display minor-unit ledger.
- Invalid cumulative over-refund is denied. Mixed unverified prior refunds are denied, not silently included.
- No Stripe/PayPal, no payment reversal or real settlement was tested.

## R13: safety, rollback and Go/No-Go
**PASS for disposable code-stage safety**:
- Checkout hook absent when isolated flag is OFF.
- Repeated Blocks hook does not mutate a stored FX snapshot.
- Woo product source USD unchanged; synthetic Marketing campaigns OFF.
- Existing P6-F exact ZIP file restore/reinstall, Woo deactivation/reactivation and CI regressions remain passing.
- R8–R10 native USD Free Shipping $69 boundary tests remain passing.

**NO-GO for production-ready Currency OS / YayCurrency cutover** (not yet exercised and/or not implemented):
1. Real Dev mixed country-zone shipping, shipping tax, inclusive/exclusive taxes and price-basis alignment with BYL49; these orders are currently intentionally excluded from auditable FX snapshots.
2. Full payment gateway/currency compatibility (Stripe test **provider** settlement/currency, refunded gateway funds, callbacks/webhooks, COD accounting, WooPayments legacy behavior). The offline COD in one-use CI is **not** a gateway/payment signoff.
3. Multi-currency Woo Blocks/Classic actual customer Checkout and complete order totals on an authorized HTTPS Dev sandbox, including multicoupon/BOGO, special goods and promotion interactions. Marketing remains OFF unless separately approved.
4. HPOS + refund/partial shipping-item tax rounding cross-checks and Woo order-management UI evidence.
5. Browser/cache/CDN country/session routing, fresh-rate expiry/refill, cross-country shipping zones, and undo plan. Existing pure reference FX is **not settlement FX**.
6. Controlled Dev candidate rollout and owner UAT. Do not copy the isolated WP config activation flag to Dev.

## Decision
- **R11-R12 isolated implementation/tests: PASS.**
- **R13 isolated safety tests: PASS; full release gate: NO-GO / OPEN.**
- PR #213 stays Draft. No merge to main, Stable release or WWW installation; no YayCurrency removal. Keep the single Commerce integration source, Andy Core unchanged, Marketing OFF.
- Prioritize mixed tax/shipping total+USD ledger contract and supported payment-processor sandbox validation before requesting final stable authorization.
