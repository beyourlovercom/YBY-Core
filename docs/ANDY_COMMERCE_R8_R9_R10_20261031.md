# Andy Commerce R8–R10: 2026-10-31 evidence and safe next gates

Scope: WWW READ-ONLY, local offline synthetic tests only; NO live commerce activation.
Base integration Draft PR #213: 2a98a8db3c56996155f1d42c8022f012c221c926.

## R8: WWW observation acceptance

- Actual WP admin readback: Andy Commerce 1.0.14 Active plugin; mode OBSERVE, WooCommerce 11.2.1, Andy Core 2.3.2, HPOS yes.
- Observed this admin request: 0 recognized Andy Commerce transaction callbacks; native Core > 交易 safe-mode menu 200.
- WWW homepage, cart, shop, checkout and product page HTTP 200 (earlier read-only checks). Not payment, checkout, guest-session or Store API acceptance.
- WWW mode remains observe. No production writes or switches authorized.

## R9: actual WWW plugin inventory

| Category | Enabled plugin | Version | Risk |
|---|---|---|---|
| Shop | WooCommerce | 11.2.1 | USD base, tax & coupon ON, HPOS ON |
| FX | YayCurrency Pro | 3.3.5 | converted presentation must never replace USD ledger |
| Coupons vendor A | Smart Coupons for WooCommerce Pro (WebToffee) | 3.2.0 | HIGH: stacking, gift, individual use |
| Coupons vendor B | WooCommerce Smart Coupons (StoreApps) | 9.51.0 | HIGH: both active at the same time |
| Payments | WooPayments | 11.2.0 | installed does not prove configured gateway |
| Payments | Airwallex Online Payments Gateway | 1.38.0 | no real payment attempted |
| Payments | Checkout Plugins Stripe for WooCommerce | 1.11.2 | no real payment attempted |
| Payments | Payment Plugins for PayPal WooCommerce | 2.0.28 | no real payment attempted |
| Checkout | Checkout Field Editor Pro | 3.7.4 | checkout hooks need separate review |
| Cart UI | CommerceGurus CommerceKit | 2.4.2 | possible cart/checkout overlap |
| Tracking | AfterShip | 1.18.5 | no order write |

Dev previous readback: Woo 11.2.1, Core 2.3.1, USD, YayCurrency present, Smart Coupons matched 0. Dev is NOT equivalent to WWW with both coupon vendors. Refresh before new Dev tests.

Woo general settings: currency USD, coupon ON, tax ON. Woo shipping UI disclosed zone 0 Rest of World with Free Shipping + Flat Rate. Other destination rules and live shipping minimums have NOT been exhaustively verified. Historical $69/$129/$169 thresholds are not authoritative quotes. Never disable either Smart Coupons plugin on WWW for testing.

R9 status: plugin inventory PASS; real coupon policy and country shipping rate parity OPEN.

## R10: offline shadow fixture baseline

- Source: includes/class-andy-commerce-r10-fx-shipping-parity-shadow.php; not loaded by plugin bootstrap, registers ZERO hooks.
- Calls existing pure M4 shipping comparator and Currency OS USD-minor conversion.
- USD/EUR/GBP/AUD/CAD use fixed synthetic display rates 1/0.92/0.80/1.52/1.35. These are NOT YayCurrency live rates or actual Woo quotes.
- Seven synthetic cart scenarios for each currency: USD 68.99, 69.00, 69.01; 79 discounted to 64; 79 discounted to 69; gift line excluded; ineligible SKU excluded.
- Two failures per currency: two simultaneous manual coupon codes rejected; invalid FX rate rejected.
- 45/45 synthetic scenario checks PASS. Eligibility stays based on USD post-discount merchandise, excluding gifts; local-display conversions do not drive threshold logic.
- Test comparator models a >= $69 synthetic threshold, NOT proof of WWW's actual "over $69" inclusive/exclusive rule.
- Example before-discount USD $79, after coupon USD $64: pre-discount legacy comparator qualifies but post-discount candidate does not. This is NOT an approved shipping method change.
- No WordPress bootstrap, Woo cart, vendor coupon plugin, true customer data, checkout, payment, stock or database mutation; cannot claim live compatibility.

## Next hard gates

R9-B: Compare WWW vs Dev exact coupon vendor settings, enabled Woo shipping methods and rate thresholds, including exceptions, with authorized read-only settings access. Confirm which plugin can create discounts/gifts, and what enforces one coupon.
R10-B: On isolated Woo with compatible copies of BOTH coupon vendor plugins, run native Woo cart and Store API readback across five currencies, one-coupon, two-coupon rejection, gifts/excluded items and country-level shipping. Record provider signatures and differences; resolve before any activation.
R10-C: only then prepare module-by-module fallback design and separate Owner PASS for any WWW runtime change.
WWW stays OBSERVE. R11/R12 and production activation remain outside this test package.
