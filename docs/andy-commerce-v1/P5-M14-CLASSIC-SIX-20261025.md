# M14 · Native Woo Classic Checkout 6/6 independent-local UAT

Tested 2026-10-25. This is **localhost-only opt-in sandbox** code, never a production Andy Commerce marketing runtime. Existing unique integrator PR #213 retains sole Dev release authority. Do not merge main, publish Stable, touch WWW/Dev, use real payment credentials or invoke real customer APIs.

## Scope & prerequisites
- US Mac mini, `http://127.0.0.1:18973`, independent `aicc_commerce_p5_20261023` MySQL DB (HPOS enabled), Woo 11.2.1, WordPress 7.1.3 with Twenty Twenty-Five theme, Andy Core 2.2.11, Andy Commerce 1.0.8, M14 test-only MU plugin.
- Native Woo **Classic Checkout** page #49 contains `[woocommerce_checkout]`; original Woo Blocks checkout page #7 was restored after testing.
- Real HTTP session used native classic `?add-to-cart` requests, loaded the Classic nonce form, then POSTed `?wc-ajax=checkout`. Full checkout returned `success` with local order ID. Order readback from HPOS validated each amount.
- All 6 rule configurations were chosen one at a time through local-only test-option fixtures; Commerce Marketing drafts still OFF.
- Native COD gateway was the **only** checkout method. No Stripe/WooPayments/PayPal/payment-token, external transaction ID, email or real fund settlement.

## Reconciled native Woo orders

| M14 sandbox automatic case | Classic HPOS order | Native coupon | CA tax | Native total |
|---|---:|---:|---:|---:|
| Fixed spend-save | #52 | USD12.00 | USD14.64 | **USD197.64** |
| Percent spend-save | #53 | USD18.50 | USD14.12 | **USD190.62** |
| BOGO (total-only native coupon proxy) | #54 | USD22.50 | USD13.80 | **USD186.30** |
| Bundle (total-only native coupon proxy) | #55 | USD23.00 | USD13.76 | **USD185.76** |
| Tier | #56 | USD37.00 | USD12.64 | **USD170.64** |
| Gift (stock-checked, zero-price) | #57 | USD0.00 | USD15.60 | **USD210.60** |

Every order was Woo native USD `processing`, COD only, with empty external gateway transaction ID. Woo `is_paid()` may report true for `processing`, although no funds moved. Gift SKU #30 appeared exactly once with line total zero; local gift stock after the previous Store API gift order and this Classic gift order is 3. No remote inventory was affected.

## Safety cleanup
- `woocommerce_checkout_page_id` restored to original **7**; isolated Classic test page #49 remains as an optional explicit test artifact, not the site's configured checkout.
- `aicc_m14_sandbox_active=no` restored and verified. The test MU code is not installed on Dev/WWW and is excluded from Commerce plugin ZIP.
- Test runners: `packages/andy-commerce/tests/sandbox/m14-classic-six.py` (six scenario parameterization) and the prior fixed-case `m14-classic-checkout.py`. Test-only and only local-loopback targets.

## Remaining real P5/P6 gates
This does **not** prove complete production Woo promotion behavior: M14 continues to represent BOGO/Bundle as a single total-equivalent native Woo `fixed_cart` coupon. Usage caps, concurrency, SKU-specific coupon attribution, returns, country/zones, tax-included prices and real activated campaign settings require separate design. Provider 3DS/decline/FX settlement/refunds/webhooks remain NOT TESTED. Original user #208 account must not be deleted due unresolved 88 authored-post association.
