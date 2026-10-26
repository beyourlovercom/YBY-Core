# Andy Commerce Currency Runtime C-R4–C-R7 — isolated acceptance (2026-10-26)

## Canonical source and environment
- **Single integrator:** Draft PR #213, `beyourlovercom/YBY-Core`.
- Tested source HEAD: `17b36488948597aff4b78e39c965267373d00895`.
- GitHub Actions: https://github.com/beyourlovercom/YBY-Core/actions/runs/40291670181 (SUCCESS).
- Runtime: disposable WordPress 7.0 + WooCommerce 11.2.1 + MySQL 8, with `commerce-p5.invalid` and `commerce_p5_isolated`.
- All external WordPress HTTP blocked, email intercepted, Cron disabled. No Dev, WWW, production order, external payment, ERP, SMTP or gateway refund.
- Currency runtime is **always OFF** unless all of these are true: explicit `AICC_CURRENCY_RUNTIME_SANDBOX` wp-config flag; exact synthetic DB; exact synthetic hostname; local WP environment. No enabled runtime on Dev/WWW.

## Actual evidence
1. **C-R1–R3 preserved:** five currencies USD, EUR, GBP, AUD, CAD through real WC product, cart and Store API product routes; raw USD data unchanged; CAD unit rounding discrepancy +1 minor unit reported, never silently rewritten.
2. **C-R4:** Native fixed-cart USD coupon 5.00 becomes CAD 6.75, Woo native item subtotal CAD 33.76 and cart payable CAD 27.01. Native flat_rate cost 10.00 -> CAD 13.50, idempotent within request (only no shipping tax). Woo native 5% tax calculator yields CAD 1.35 on CAD 27.01 **in a separate tax calculation**, not in an actual mixed tax/shipping checkout.
3. **C-R5:** isolated Woo session USD/CAD/EUR selection and WordPress REST nonce rejected without valid token, accepted with token. Actual native Woo Store API /cart reports CAD 27.01.
4. **C-R6:** WooCommerce `WC()->checkout()->create_order()` creates local, non-paying COD order in CAD, total CAD 27.01, persisted FX snapshot USD 20.00 source, with locked synthetic rate, CAD rounding +1 minor, saved metadata read-back. This is **not** a payment provider checkout or Woo Store API /checkout POST.
5. **C-R7:** Woo `wc_create_refund(..., refund_payment=false, restock_items=false)`, CAD 5.00 -> reverse USD 3.70; separate fully refunded local order CAD 27.01 -> **exact** original USD 20.00 (not USD 20.01). Plugin default-OFF and unchanged Marketing OFF verified. All workflows same HEAD: 6 SUCCESS, one conditional Currency preview SKIPPED, zero failures.

## Scope boundaries before production
- **Not production feature-complete.** Client-facing Footer shortcode selection is presently a separate Dev-only preview; it has not been bound to the guarded sandbox session-selection endpoint. The test REST route must NOT be opened to production.
- **Not covered:** mixed/country-specific tax+shipping checkout, shipping-tax recalculation, coupons across multi-campaign bundles/BOGO/gifts, HPOS legacy matrix beyond the isolated native Woo order, Woo Store API checkout and payment methods, provider FX/payment settlement, real refunds, currency persistence across HTTP/browser sessions, cache/CDN country detection, stale rate governance, production site-by-site YayCurrency parity and rollback.
- Runtime filters/REST/checkout hooks for C-R4/C-R5/C-R6 are guarded and not registered on production. Currency OS remains experimental, not a YayCurrency replacement.
- No merge to main; no Stable/Dev/WWW deployment; no activation. Keep Marketing OFF and Andy Core unchanged. A future release requires separate fully scoped production-readiness acceptance.

## Conclusion
**C-R4–C-R7 isolated experimental acceptance PASSED.** This milestone covers auditable compatibility/reconciliation and no-money Woo test flows; it is not the original full Currency OS production Definition of Done from Issue #196.
