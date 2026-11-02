# BYL WooPayments / YayCurrency Single-Conversion Lab

> **Experimental / Dev-only / NOT payment certified. Do not install on Production.**

## Finding (2026-11-02)

Confirmed on \`dev.beyourlover.com\` with a *fresh unauthenticated HTTP request*, using only the YayCurrency EUR cookie (\`yay_currency_widget=40060\`):

| Currency configuration | Ariel & Lippy |
|---|---:|
| WooPayments multi-currency OFF + YayCurrency EUR | €113.38 |
| WooPayments multi-currency ON + EUR configured + YayCurrency EUR | €102.00 |
| USD base product price | $126.40 |

This rules out the theory that only an administrator's old WooPayments currency session caused double conversion. WooPayments Multi-Currency was returned to **OFF**, EUR removed, and the three local methods left **OFF**, with settings readback after testing.

## Scope and behavior

- Separate experiment under \`experiments/\`; this is **not part of the Andy Core release package**.
- Requires **both** exact WordPress home host \`dev.beyourlover.com\` and HTTP request header \`X-BYL-Currency-Lab: 1\`.
- No matching header or any other hostname: **no price/currency hooks added or removed**.
- The intervention selectively detaches WooPayments 11.2.0's currency and price-format callbacks; payment gateway remains registered.
- No database writes, no refunds, no actual payments, no changes to WooPayments or YayCurrency vendor code.
- This is a diagnostic experiment, **not proof** that Store API, coupons, shipping, taxes, order metadata or refunds are correct.

## Offline test

Run:

\`\`\`sh
php -l experiments/byl-wcpay-yay-lab/byl-wcpay-yay-lab.php
php experiments/byl-wcpay-yay-lab/tests/test-scope-and-hooks.php
\`\`\`

## Controlled Dev test gate — no Production authorization

1. Review exact source package, verify Dev-only installation and rollback/deactivation. *Do not deploy this branch or auto-include it in Andy Core.*
2. Install plugin on Dev only. With no header, prove price and checkout behavior unchanged.
3. On Dev, enable WooPayments Multi-Currency and EUR temporarily (never WWW). Compare clean HTTP responses with \`yay_currency_widget=40060\`, once with header and once without.
4. If and only if prices agree, check USD, EUR, GBP and CAD/AUD on product, variation, cart and Store API; verify shipping, free shipping threshold, coupon, tax and rounding.
5. Check billing-country-qualified payment options BE/AT/NL, only then attempt *test-mode* order and refund Canary with exact order/payment intent currency and minor-unit amount audit.
6. On any mismatch **STOP**, roll back Dev settings and deactivate the lab plugin. No main merge or Production installation without Owner approval.

## Upstream

- Automattic WooPayments 11.2.0 \`includes/multi-currency/FrontendPrices.php\`, \`FrontendCurrencies.php\`, \`Compatibility.php\`
- WooCommerce WooPayments documentation: local methods require EUR; multi-currency also modifies storefront price rendering.

No actual source checkout, payment authorization, refund or Production change has been performed by this lab.
