# Andy Commerce R10-B: isolated candidate acceptance for #275 / #276

Date: 2026-10-31. Candidate is **NOT approved for DEV/WWW**.

## Exact-vendor isolated Windows sandbox
Fresh WordPress 7.1, WooCommerce 11.2.1, YayCurrency Pro 3.3.5, WebToffee Smart Coupons Pro 3.2.0 and StoreApps WooCommerce Smart Coupons 9.51.0, all ACTIVE. New empty MySQL 8.4, PHP 8.2, localhost-only 14141 / MySQL 14041. Five Yay currencies selected via actual Yay cookie with SYNTHETIC test rates USD 1, EUR .92, GBP .8, AUD 1.52, CAD 1.35. No existing website DB, uploaded files, customers, orders or payments.

Baseline before candidate: one EUR USD69-equivalent free-shipping failure from binary floating point; two standard coupon codes stacked. See ANDY_COMMERCE_R10B_NATIVE_WINDOWS_UAT_20261031.md.

## Deliberately sandbox-only source
File: packages/andy-commerce/tests/sandbox/class-andy-commerce-r10b-isolated-guards.php
- Not included in Andy Commerce runtime bootstrap, with NO automatic registration in Dev/WWW.
- Manual local mu-plugin test loader calls boot after plugins_loaded. Registration requires marker .andy-r10b-isolated-local and siteurl exactly http://127.0.0.1:14141.
- #275: correct only binary float tiny-negative mismatch (<1e-9), equal integer cents, matching enabled native free_shipping method and zone, requires min_amount/either and ignore_discounts=no; subsequent vendor filters retain veto power.
- #276: late standard Woo coupon validation rejects a second positive percent/fixed_cart/fixed_product discount, preserving first. Valid free-ship, zero and unknown/credit/BOGO coupon types unaffected. No order/payment hooks.
- Constraint: an automatically applied *ordinary* coupon can take first slot too. Distinction between auto/manual and store-credit exceptions NOT approved yet.

## Actual test results after candidate
- Five real Yay selectors by 8 native Woo scenarios = 40/40 matching USD shipping eligibility, including previously failing EUR USD69-equivalent.
- The second standard discount declined in all five currencies; first discount remains.
- 11 isolated HTTP Store API cases: 5 currency USD69 thresholds free; 5 currencies reject second ordinary code via HTTP 400 while retaining first; EUR valid native free-ship coupon still free.
- EUR adversarial 5/5: USD68.99 equivalent no-free; USD69.01 equivalent free; later simulated vendor denial respected (after explicitly forcing fresh Woo shipping package calculation); reverse coupon order retains first; free-shipping coupon survives.
- Source-only fail-closed safety harness 6/6: no WWW boot, no hooks registered and original native decisions unchanged.
- All above use synthetic test products/coupons and rates. No real checkout, orders, payment, customer data or production writes.

## Remains OPEN
These are **isolated test candidates only**, not released bugfixes. Need: owner-reviewed coupon auto/manual semantics; actual campaigns/BOGO/store credits/excluded SKUs; multi-zone/class/tax and full browser checkout; a module-level OFF-by-default production integration with separate Owner pass. Never copy the sandbox mu-plugin into Dev/WWW or switch WWW out of OBSERVE.

Issues #275 and #276 remain OPEN pending full integration / Owner acceptance. No proprietary plugins or real site data in repository.
