# R7 payment acceptance — YayCurrency compatibility rebase (2026-10-29)

**Status:** DEV TEST payment **NO-GO**. R6 remains frozen; this document changes the R7 acceptance path after Owner approved YayCurrency as the storefront currency authority. It does **not** request a renewed Stripe OAuth or authorize a Dev plugin upload, payment, refund, or WWW release.

## Observed vs unverified
- GitHub child Draft PR #258 records Dev WooCommerce 11.2.1, USD store base, YayCurrency Free 3.3.5 ACTIVE, independent Andy Currency Dev FX engine OFF, independent Stripe TEST Gateway OFF, R13 Canary OFF; HPOS enabled. This is the current cited Dev observation of that branch, **not a fresh Dev readback in this R7 task**.
- Dev historically passed a TEST-key account proof for the independent Stripe plugin merchant `acct_1RdAGvPKD6QEqtsG`. That evidence **does not establish WooPayments' connected TEST merchant identity**, and does not authorize reusing the old R13 canary with YayCurrency.
- US Mac mini disposable sandbox `acct_1R57M7GR9MwFzg43` and ChatGPT Stripe connection `acct_1SrJdHCxlWhQx8Rb` are separate and must not be silently mapped to Dev.
- Historical R7 exact HEAD `e3c5672` ZIP attested SHA256 `ee99ba53fa79620fb79510f7d0f220ff74204ae6bc9e48e76ddd081fc878c72d` is **superseded by subsequent publisher changes**. Do **not** deploy it blindly. Source-only R7 canary conflict regression was added to the sole publisher after that ZIP.

## Frozen authority mapping
| Concern | Sole authority |
|---|---|
| Base merchandise, coupons, line/order totals and taxes | WooCommerce USD product edit prices and native Woo financial facts |
| Storefront/checkout currency selection and price presentation | YayCurrency; do not turn on the old Andy Currency converter |
| USD199 Black Friday gift eligibility | Andy Campaign with trusted Commerce read-only original-USD after-coupon quote, never inverse rounded FX |
| Payment capture, webhook, charge/refund | A *single* approved official Woo payment gateway, independently identified and proven to be TEST only |
| HPOS orders and refund records | WooCommerce/HPOS plus provider readback |
| Owner-only, time-boxed test access | Explicit approved Dev test path; never reuse the old R13 self-built switcher or enable it beside YayCurrency |

WooPayments' built-in multi-currency pricing and YayCurrency's currency switcher are **separate possible price authorities**. Before test orders, explicitly audit their current settings; do not assume two concurrent converters are safe. YayCurrency's vendor documentation lists WooCommerce Payments as supported, but vendor compatibility is not a real merchant/order/webhook acceptance.

## Payment gate phases
1. **R7-Y0 READ ONLY.** Read current Dev plugin/gateway modes and currency settings through an already permitted official admin readback; confirm live installed Commerce payload hash, YayCurrency enabled currencies, WooPayments built-in multi-currency mode, Woo base USD, HPOS, checkout type and endpoint. Do not read or print secret values. Existing platform safety block on R7 Dev repair and account verification remains in force: no alternate transport/credential path to bypass it.
2. **R7-Y1 INDEPENDENT SOURCE.** Only after PR #258 source integration into the exact sole Commerce publisher, run its fail-closed USD-basis tests and R7 legacy-canary conflict negatives. Retain standard checkout and payment authority. No PR child direct Dev publisher, no patching installed files from an obsolete ZIP.
3. **R7-Y2 GATEWAY AUTHORITY.** Through the approved process confirm the exact Dev gateway for checkout, corresponding TEST merchant, testmode, currency support, supported Classic/Blocks checkout, webhook endpoint and TEST refund permissions. A Stripe plugin `acct_...` proof does not identify WooPayments. No merchant switch or OAuth reset as a shortcut.
4. **R7-Y3 CURRENCY GATE.** Yay Free allows up to 3 currencies including USD; first verify USD/EUR/GBP (only after settings legitimately support them). AUD/CAD real checkout is **blocked** until legitimately licensed Pro is installed by Owner. Synthetic five-currency checks are NOT five real gateway payments.
5. **R7-Y4 REAL TEST CHECKOUT (separate permitted approval).** For each supported local currency: validate precise tax-excluded original USD merchandise basis and Woo order currency/amount, provider TEST payment intent, Woo HPOS order ID/currency/totals, callback signature and webhook dedupe, thank-you state; include failed/declined and 3DS. Prove both Classic and Blocks when supported; do not run R13 Blocks-only test as if it were Yay+Woo.
6. **R7-Y5 REFUNDS & CLEANUP.** TEST full and partial refunds and duplicate webhook replay; compare Woo refund records with provider receipts and currency. Restore all Dev-only test gates to OFF, verify no test-only gift entitlements or lingering checkout switches. WWW/Production must remain unchanged.

## Read-only software tests already completed on sole publisher
R7 synthetic `r7-stripe-account-proof-contract.php` now also has **five negative-path assertions** for the conflict flag: retained owner session cannot arm old R13; stale Stripe proof cannot pass; retained foreign checkout rejected; foreign old gateway list stays empty; ordinary USD WooPayments remains unmodified. These are synthetic regression assertions, **not live Yay payment proof**.

## R7 hard blockers
- Previous safety system blocked an exact-head Dev repair and TEST-account ownership verification. **Never retry via an alternate tool, host or method.** An explicitly approved execution route is necessary.
- New YayCurrency adapter in #258 is currently source-only, with a deliberately limited verified coupon subset; not integrated into gift runtime or Dev publisher.
- No current WooPayments TEST merchant authority or true end-to-end checkout/refund receipt has been signed.
- No evidenced AUD/CAD real checkout with Yay Free; real 5-currency R7 cannot pass until licensed Pro or a separately authorized release-scope decision.

## References
- Internal source: PR #213 sole publisher; PR #258 YayCurrency adapter; Issue #252 R7 evidence; `R7-DEV-DEPLOYMENT-DRIFT-20261029.md`.
- YayCurrency vendor integration list: https://docs.yaycommerce.com/yaycurrency/compatibility
- WooPayments own optional multi-currency capability: https://woocommerce.com/document/woopayments/currencies/multi-currency-setup/
- YayCurrency currency checkout options: https://docs.yaycommerce.com/yaycurrency/configurations/checkout-options
