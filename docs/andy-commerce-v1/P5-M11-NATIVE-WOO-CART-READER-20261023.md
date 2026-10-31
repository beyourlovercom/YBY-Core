# Andy Commerce · M11 Native Woo Cart Read-only Bridge (2026-10-23)

**Ownership:** Currency and Marketing stay in ONE Andy Commerce plugin. This scoped branch is code and CI only, stacked on the exact PR #213 integration HEAD `ed7ac41bce79806ef992d0c60197fff0e98a661c`. It must not independently publish a Dev ZIP. No main/Stable/WWW changes.

## Why
P5 verified a disposable WooCommerce 11.2.1 local instance with PHP 8.3, WordPress 7.1.3, Andy Core 2.2.11 and Commerce 1.0.8. Real native Store API checkout created USD113.40 local COD order #20, reflecting USD120 merchandise, USD25 Woo coupon, USD10 shipping and USD8.40 tax. Five real shipping zones and an isolated offline refund were verified. This is not evidence of real Andy Commerce promotion execution or multi-currency capture.

## M11 acceptance
- `Andy_Commerce_M11_WC_Cart_Reader::read(WC_Cart, explicitClassificationMap, thresholdUsdMinor)` **reads**, never mutates, authoritative Woo cart totals, native coupon code count, one shipping package and Woo zone; only USD store/base and two decimal places.
- Caller must explicitly classify **every cart line** by cart-key as `eligible_for_promotion`, `special_shipping`, `free_gift`; no guessed SKU or category exemption. No more than one pre-applied native coupon.
- Capture post-native-discount USD line amounts, USD shipping and tax in cents; reconcile against Woo's actual cart total within one cent. Reject unsupported foreign shop base, unknown fees, unallocated unit rounding, missing classification, multiple packages, total mismatch or unsupported precision.
- Result is a validated input for existing `Andy_Commerce_C3_Cart_Snapshot_Adapter`, with `mode=READ_ONLY_NEVER_EXECUTE_WOO`. Does **not** enable checkout, call the gateway, give gifts, rewrite orders, create refunds, apply promotions, or alter stock.
- Pure harness `marketing-m11-real-woo-readonly-harness.php` covers basic Woo-shaped basket, USD coupon and shipping/tax truth, five currency requests, seven zones, and rejection cases. GitHub CI includes this harness.
- Integration must still pass **actual Woo 11.2 sandbox** against M11 reader. This isolated branch does not authorize runtime activation or Dev deploy.

## Limitations and next gated work
- Source is not yet wired into the Woo request lifecycle; the caller must explicitly invoke the read method after Woo native totals exist.
- Fail-closed exclusions (multi-package, fee-bearing, non-divisible per-unit subtotal) need future explicit design for supported live transactions.
- Six activity classes remain OFF/shadow only. P5 full automatic discount/gift flow and P6 actual Stripe/WooPayments/PayPal currency capture, refunds and callbacks remain **NOT PASSED**.
- Issue #208 completed real Subscriber direct-link denial HTTP403, but safe cleanup of its user-id-6 fixture remains pending due to 88 associated posts. Do not alter those posts.
