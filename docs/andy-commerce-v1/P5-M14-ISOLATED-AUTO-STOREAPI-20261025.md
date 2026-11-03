# P5 M14 · Local-only automatic cart execution via native Woo hooks

Date: 2026-10-25. **NOT a production Commerce runtime release.** Builds on sole integrator PR #213 at `1d98610f998fdce979555ea85653afd38324872d`. No GitHub main, Stable, Dev, WWW, or live payment changes are authorized.

## Hard boundaries

- Only a one-off MU plugin **copied manually to an isolated WP sandbox's MU plugin directory** from `packages/andy-commerce/tests/sandbox/aicc-m14-local-runtime.php`; it must never be included in an installable Andy Commerce ZIP.
- Runtime verifies **all**: exact URL `http://127.0.0.1:18973`, exact isolated DB `aicc_commerce_p5_20261023`, WP environment `local`, WP cron disabled, WP external HTTP blocked, native shop currency USD. The existing MU guard intercepts outbound email and gateway webhooks. Only offline native COD is installed/enabled.
- Config absent or `aicc_m14_sandbox_active != yes` means **OFF**. All actual Commerce Marketing drafts remain OFF. Test scenarios are passed via separate WP option only on this local instance; not read from production campaign activation settings.
- Uses the **existing M11 native WC_Cart reader → M12 Woo shadow bridge → existing M10 rule evaluator**. No second independent promotions calculator.
- Automatically applies exactly one native Woo **virtual fixed_cart coupon** for fixed, percent, BOGO, bundle or tier; on changes to SKU/quantity, it removes and recalculates the coupon against clean Woo native totals. **This validates total discount only**, not item-level BOGO/bundle attribution, individual returns, or live usage policy.
- For gift case, only adds product on actual threshold qualification after verifying managed inventory and positive stock; gift marked with explicit test cart data and repriced to zero on native calculate. Same cart session prevents duplicate gifts. When qualifying items are removed, or another coupon is used, the gift is removed and totals recomputed.
- User-entered native coupon takes precedence: a validation filter allows the manual coupon to replace the local virtual auto coupon, and the Woo applied_coupon hook removes the auto one before totals.
- This sandbox still uses Woo COD: an order may return `processing` and Woo `is_paid=true` **without external funds or any gateway transaction ID**. This test does not verify true payment settlement.

## Actual HTTP / HPOS evidence

All tests were executed in the **isolated US Mac mini** with WordPress 7.1.3, WooCommerce 11.2.1, Andy Core 2.2.11 and Andy Commerce 1.0.8. Genuine Store API guest sessions sent sequential `cart/add-item` for 2 × $40, $45 and $60 products, then shipping address CA, shipping selection and `checkout` with offline COD. Discount / tax / final total were read from WC; HPOS order IDs:

| Rule, auto by Woo hooks | Woo order | Auto native discount | Native cart/order total |
|---|---:|---:|---:|
| Fixed Spend & Save | #43 | USD12.00 | USD197.64 |
| Percent Spend & Save | #44 | USD18.50 | USD190.62 |
| BOGO (total-only coupon proxy) | #45 | USD22.50 | USD186.30 |
| Bundle (total-only coupon proxy) | #46 | USD23.00 | USD185.76 |
| Tiered | #47 | USD37.00 | USD170.64 |
| Gift (stock-checked, zero-price item) | #48 | USD0.00 coupon; 1 zero-price gift | USD210.60 |

Extra native HTTP negative assertions (PASSED):
- Tiered coupon automatically changes **$8 → $12.50 → $37** as SKU/quantity rises, and **$37 → $12.50 → $8** as items are removed.
- Gift is awarded only after threshold, never duplicated; falls out of cart when total becomes ineligible.
- Buyer manual `aicc-p5-only-25` coupon overrides local automatic tiered offer with HTTP **200** (no double coupon); previous bug with individual_use rejection was fixed.
- When gift product has stock=0, gift is not auto-added. Product stock was restored after the negative test. Woo checkout #48 locally reduced managed gift stock **5 → 4**; that is a local native Woo inventory event, not real fulfillment.
- After all verification, WP option `aicc_m14_sandbox_active` was set to `no`; with default disabled, the same cart shows no auto discounts.
- Read back six HPOS orders as `processing` with native COD, no external transaction ID. Woo `is_paid()` returns true because `processing` is treated as a paid status, despite zero actual gateway settlement.

## Reproduction

**Never run in Dev/WWW**. US Mac mini sandbox already has the required isolated DB, Woo products 27/28/29, stock-managed gift 30, local plugin and external HTTP/email guards. The following are local-only test fixtures, not service operations.

```bash
base="$HOME/AI/uat/andy-commerce-p5p6-20261023"
cp packages/andy-commerce/tests/sandbox/aicc-m14-local-runtime.php "$base/site/wp-content/mu-plugins/aicc-m14-local-runtime.php"
AICC_M14_CASE=tier php "$base/tools/wp-cli.phar" eval-file "$base/tools/m14-config-case.php" --path="$base/site"
AICC_M14_CASE=tier python3 "$base/tools/m14-store-api-checkout.py"
php "$base/tools/wp-cli.phar" option update aicc_m14_sandbox_active no --path="$base/site"
```

Always verify the native sandbox guard is active and the host only binds to `127.0.0.1`. The test-only files are excluded from all installable ZIPs.

## Unresolved gates

- This is **local-only, opt-in MU plugin proof**. It does **not** implement an Andy Commerce production-class automatic marketing runtime, persisted campaign selection, usage limits, concurrency-safe promotion caps, SKU-specific BOGO/bundle discount allocation, tax/rounding under all configurations, multi-package shipping, gift inventory race/return handling, or after-payment fulfillment logic.
- Woo Store API succeeded only with synthetic US CA COD orders, not a Classic Checkout + international zones matrix.
- P6 Stripe/WooPayments/PayPal **test-mode** webhook, 3DS, decline, FX capture and provider refund remain NOT TESTED.
- Keep standalone user #208 cleanup blocked on 88-post association; do not delete or reassign content.
