# M14 isolated Classic Checkout UAT — real local native Woo order

Date: 2026-10-25. Parent M14 automatic sandbox proof: `8e854686a1301ea28d201be61eaa012ff25ff755` on unique integrator PR #213. All activity toggles remain OFF outside exact isolated local test instance; no main/Stable/Dev/WWW deployment.

## Environment and checkout configuration
The separate U.S. Mac mini Woo 11.2.1 sandbox was originally installed using `wp core download --skip-content`; no front-end WordPress theme was installed. This caused Classic Checkout rendering to return empty HTML even though Store API/Blocks had passed. A default **Twenty Twenty-Five v1.5** theme was installed and activated **in that isolated localhost-only sandbox only**, resolving the missing front end. Theme installation was not required on Dev/WWW.

The default Woo checkout page **#7** uses Woo Blocks. Created a **temporary classic shortcode page #49** containing `[woocommerce_checkout]`, temporarily set local `woocommerce_checkout_page_id=49`, then restored the original Woo checkout page setting to **7** after UAT. M14 sandbox active option was reset to **no** after UAT.

## Actual Classic HTTP checkout
With isolated M14 fixed Spend & Save rule enabled, one fresh guest HTTP session added 2 × product 27 ($40), product 28 ($45), product 29 ($60) through native Woo classic add-to-cart URLs. GET of page #49 rendered an actual Woo classic checkout form with a WordPress checkout nonce **and the M14 automatic coupon**; POST `?wc-ajax=checkout` successfully returned an order-received URL on localhost.

WC HPOS order **#51** persisted and was independently read back:
- Merchandise subtotal **USD185.00**
- Automatic virtual native Woo coupon `aicc-m14-only-fixed` **USD12.00**
- Shipping **USD10.00**, US-CA tax **USD14.64**
- Final local Woo checkout/order **USD197.64**
- Native COD only, order `processing`, external transaction ID empty. `is_paid()` is **true** because Woo treats processing as a paid order status; **no funds were collected**.

Test driver `packages/andy-commerce/tests/sandbox/m14-classic-checkout.py` is **test-only**, hard-coded to loopback and a local page; it asserts exact local redirect and reports only order ID, not an order key. It must not be included in installable plugin ZIP.

## What is and is not proven
**PASS:** Woo Blocks/Store API six-type local automatic hook path from M14, plus Classic Checkout fixed total-discount path and native Woo/HPOS persistence. **NOT PROVEN:** Classic Checkout for other five rule types; production-ready Andy Commerce promotion activation, SKU-specific BOGO/bundle allocation, usage cap/race control, stock reverts/refund line attribution, real FX/3DS/payment-provider transaction or callback. P5/P6 remain open for those gates.

As before, [Issue #208](https://github.com/beyourlovercom/YBY-Core/issues/208) has real Subscriber authorization HTTP403 PASS but an unrelated 88-post author association cleanup HOLD. No account deletion is part of P5/P6.
